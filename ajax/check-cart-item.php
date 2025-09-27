<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';

// Inicializar sesión con configuración segura
$sessionId = initSecureSession();

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

try {
    // Crear instancia del manejador de carrito
    $cartManager = new CartManager($pdo);
    
    // Aceptar tanto GET como POST
    $product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido', 'in_cart' => false]);
        exit;
    }

    // Verificar si el producto está en el carrito
    $in_cart = $cartManager->isInCart($product_id);
    $quantity = $cartManager->getProductQuantity($product_id);

    echo json_encode([
        'success' => true,
        'inCart' => $in_cart,
        'in_cart' => $in_cart, // Mantener ambos por compatibilidad
        'quantity' => $quantity,
        'cart_count' => $cartManager->getCartCount(),
        'cart_total' => $cartManager->getCartTotal()
    ]);

} catch (Exception $e) {
    error_log("Error en check-cart-item.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'in_cart' => false
    ]);
}
?>