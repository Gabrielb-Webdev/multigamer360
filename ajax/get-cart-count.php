<?php
// Prevenir caché del navegador para respuestas siempre actualizadas
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

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
    // Debug logging para diagnóstico
    error_log("GET-CART-COUNT DEBUG: Session ID = " . $sessionId);
    error_log("GET-CART-COUNT DEBUG: Session cart = " . print_r($_SESSION['cart'] ?? [], true));
    
    // Si $pdo no está definido, crear conexión directamente
    if (!isset($pdo)) {
        // Configuración de base de datos (replicada para casos de emergencia)
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
    
    // Sincronizar carrito desde base de datos
    $cartManager->syncCartFromDatabase();
    
    // Obtener información completa del carrito
    $cartInfo = $cartManager->getCartInfo();
    
    // Debug logging del resultado
    error_log("GET-CART-COUNT DEBUG: Cart info = " . print_r($cartInfo, true));
    
    echo json_encode($cartInfo);

} catch (Exception $e) {
    error_log("Error en get-cart-count.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'cart_count' => 0,
        'cart_total' => 0,
        'cart' => []
    ]);
}
?>