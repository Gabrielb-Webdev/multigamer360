<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';
initSecureSession();

// Verificar que el usuario esté logueado
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

// Incluir base de datos
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit;
}

try {
    // Verificar que el pedido pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    
    // Obtener productos del pedido original
    $stmt = $pdo->prepare("
        SELECT oi.product_id, oi.quantity, p.stock_quantity, p.is_active
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos en el pedido']);
        exit;
    }
    
    $added_count = 0;
    $errors = [];
    
    // Agregar cada producto al carrito
    foreach ($items as $item) {
        if (!$item['is_active']) {
            $errors[] = "Producto ID {$item['product_id']} ya no está disponible";
            continue;
        }
        
        if ($item['stock_quantity'] < $item['quantity']) {
            $errors[] = "Stock insuficiente para producto ID {$item['product_id']}";
            continue;
        }
        
        // Verificar si ya existe en el carrito
        $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $item['product_id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Actualizar cantidad
            $new_quantity = $existing['quantity'] + $item['quantity'];
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$new_quantity, $user_id, $item['product_id']]);
        } else {
            // Insertar nuevo item
            $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $item['product_id'], $item['quantity']]);
        }
        
        $added_count++;
    }
    
    $message = "Se agregaron $added_count productos al carrito";
    if (!empty($errors)) {
        $message .= ". Algunos productos no pudieron agregarse: " . implode(', ', $errors);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'added_count' => $added_count,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    error_log("Error en reorder: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>