<?php
require_once '../inc/config.php';
require_once '../inc/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar permisos
if (!hasPermission('inventory', 'update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ajustar inventario']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

try {
    // Obtener datos del formulario
    $product_id = intval($_POST['product_id'] ?? 0);
    $adjustment_type = $_POST['adjustment_type'] ?? '';
    $adjustment_quantity = intval($_POST['adjustment_quantity'] ?? 0);
    $adjustment_reason = $_POST['adjustment_reason'] ?? '';
    $adjustment_notes = $_POST['adjustment_notes'] ?? '';
    
    // Validar datos
    if (!$product_id) {
        throw new Exception('ID de producto inválido');
    }
    
    if (!in_array($adjustment_type, ['increase', 'decrease', 'set'])) {
        throw new Exception('Tipo de ajuste inválido');
    }
    
    if ($adjustment_quantity < 0) {
        throw new Exception('Cantidad inválida');
    }
    
    if (empty($adjustment_reason)) {
        throw new Exception('Debe especificar un motivo');
    }
    
    // Obtener producto actual
    $stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Producto no encontrado');
    }
    
    $current_stock = $product['stock_quantity'];
    $new_stock = $current_stock;
    
    // Calcular nuevo stock
    switch ($adjustment_type) {
        case 'increase':
            $new_stock = $current_stock + $adjustment_quantity;
            $quantity_change = $adjustment_quantity;
            break;
        case 'decrease':
            $new_stock = max(0, $current_stock - $adjustment_quantity);
            $quantity_change = -$adjustment_quantity;
            break;
        case 'set':
            $new_stock = $adjustment_quantity;
            $quantity_change = $adjustment_quantity - $current_stock;
            break;
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Actualizar stock del producto
    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);
    
    // Registrar el movimiento en historial de inventario (si existe la tabla)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO inventory_movements 
            (product_id, user_id, type, quantity_change, previous_quantity, new_quantity, reason, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $product_id,
            $_SESSION['user_id'],
            $adjustment_type,
            $quantity_change,
            $current_stock,
            $new_stock,
            $adjustment_reason,
            $adjustment_notes
        ]);
    } catch (PDOException $e) {
        // Si la tabla no existe, continuar sin error
        if ($e->getCode() != '42S02') { // Tabla no existe
            throw $e;
        }
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    // Log de auditoría (opcional si existe la función)
    // logActivity(
    //     'inventory_adjustment',
    //     "Ajuste de stock para producto '{$product['name']}': {$current_stock} → {$new_stock}",
    //     ['product_id' => $product_id, 'old_stock' => $current_stock, 'new_stock' => $new_stock, 'reason' => $adjustment_reason]
    // );
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock ajustado correctamente',
        'data' => [
            'product_id' => $product_id,
            'previous_stock' => $current_stock,
            'new_stock' => $new_stock,
            'change' => $quantity_change
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
