<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';

// Inicializar sesión con configuración segura
$sessionId = initSecureSession();

// Debug logging
error_log("ADD-TO-CART DEBUG: Session ID = " . $sessionId);
error_log("ADD-TO-CART DEBUG: POST data = " . print_r($_POST, true));
error_log("ADD-TO-CART DEBUG: Session before = " . print_r($_SESSION['cart'] ?? [], true));

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Si $pdo no está definido, crear conexión directamente
    if (!isset($pdo)) {
        $host = 'localhost';
        $dbname = 'multigamer360';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    
    // Crear instancia del manejador de carrito
    $cartManager = new CartManager($pdo);
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
        exit;
    }

    // Limitar cantidad entre 1 y 10
    $quantity = max(1, min($quantity, 10));

    // Agregar al carrito usando el manejador
    $result = $cartManager->addToCart($product_id, $quantity);
    
    // Debug logging después de agregar
    error_log("ADD-TO-CART DEBUG: Result = " . print_r($result, true));
    error_log("ADD-TO-CART DEBUG: Session after = " . print_r($_SESSION['cart'] ?? [], true));
    error_log("ADD-TO-CART DEBUG: Cart count = " . $cartManager->getCartCount());
    
    if ($result['success']) {
        $result['message'] = $cartManager->isInCart($product_id) ? 'Cantidad actualizada en el carrito' : 'Producto agregado al carrito';
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error en add-to-cart.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>