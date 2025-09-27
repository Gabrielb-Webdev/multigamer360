<?php
session_start();

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
    
    // Limpiar carrito usando el manejador
    $result = $cartManager->clearCart();
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error en clear-cart.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>