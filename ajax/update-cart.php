<?php
// Detectar si se ejecuta desde subdirectorio ajax o desde raíz
$base_path = dirname(__DIR__);
if (file_exists($base_path . '/config/database.php')) {
    require_once $base_path . '/config/database.php';
    require_once $base_path . '/includes/cart_manager.php';
} else {
    require_once '../config/database.php';
    require_once '../includes/cart_manager.php';
}

header('Content-Type: application/json');

// Debug logging
error_log("update-cart.php - Datos recibidos: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Crear instancia del manejador de carrito
    $cartManager = new CartManager($pdo);
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? 'update';

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
        exit;
    }

    if ($action === 'set') {
        // Establecer cantidad específica
        $quantity = intval($_POST['quantity'] ?? 0);
        $result = $cartManager->updateQuantity($product_id, $quantity);
        
    } elseif ($action === 'update') {
        // Actualizar cantidad (incrementar/decrementar)
        $quantity_change = intval($_POST['quantity_change'] ?? 0);
        $current_quantity = $cartManager->getProductQuantity($product_id);
        $new_quantity = $current_quantity + $quantity_change;
        
        $result = $cartManager->updateQuantity($product_id, $new_quantity);
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida'];
    }

    error_log("update-cart.php - Resultado: " . print_r($result, true));

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error en update-cart.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>