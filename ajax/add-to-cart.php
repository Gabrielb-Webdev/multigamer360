<?php
// Configurar log personalizado para debugging
$log_dir = __DIR__ . '/../logs';
$custom_log = $log_dir . '/debug_cart.log';

// Crear directorio de logs si no existe
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Asegurar que el archivo de log existe
if (!file_exists($custom_log)) {
    file_put_contents($custom_log, '');
}

ini_set('error_log', $custom_log);

// Función helper para logging
function debug_log($message) {
    global $custom_log;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message\n";
    @file_put_contents($custom_log, $formatted, FILE_APPEND);
    error_log($message);
}

// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';

// Inicializar sesión con configuración segura
$sessionId = initSecureSession();

// Debug logging
debug_log("=== INICIO PETICIÓN ADD-TO-CART ===");
debug_log("Session ID = " . $sessionId);
debug_log("POST data = " . print_r($_POST, true));
debug_log("Session cart before = " . print_r($_SESSION['cart'] ?? [], true));

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
        debug_log("✓ Conexión PDO creada exitosamente");
    } else {
        debug_log("✓ Usando conexión PDO existente");
    }
    
    // Crear instancia del manejador de carrito
    $cartManager = new CartManager($pdo);
    debug_log("✓ CartManager instanciado exitosamente");
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    debug_log("Producto ID recibido: $product_id");
    debug_log("Cantidad recibida: $quantity");

    if ($product_id <= 0) {
        debug_log("✗ ERROR - ID de producto inválido");
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
        exit;
    }

    // Verificar que el producto existe en BD antes de agregar
    debug_log("Verificando producto directamente en BD...");
    $stmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $productCheck = $stmt->fetch();
    
    if ($productCheck) {
        debug_log("✓ Producto encontrado en BD:");
        debug_log("  - ID: {$productCheck['id']}");
        debug_log("  - Nombre: {$productCheck['name']}");
        debug_log("  - Stock: {$productCheck['stock_quantity']}");
    } else {
        debug_log("✗ PRODUCTO NO ENCONTRADO EN BD con ID: $product_id");
        
        // Buscar productos disponibles
        $stmt = $pdo->query("SELECT id, name FROM products LIMIT 5");
        $available = $stmt->fetchAll();
        debug_log("Productos disponibles en BD:");
        foreach ($available as $p) {
            debug_log("  - ID: {$p['id']} | {$p['name']}");
        }
    }

    // Limitar cantidad entre 1 y 10
    $quantity = max(1, min($quantity, 10));

    // Agregar al carrito usando el manejador
    debug_log("Llamando a cartManager->addToCart($product_id, $quantity)");
    $result = $cartManager->addToCart($product_id, $quantity);
    
    // Debug logging después de agregar
    debug_log("Resultado: " . print_r($result, true));
    debug_log("Session cart después: " . print_r($_SESSION['cart'] ?? [], true));
    debug_log("Cart count: " . $cartManager->getCartCount());
    
    if ($result['success']) {
        $result['message'] = $cartManager->isInCart($product_id) ? 'Cantidad actualizada en el carrito' : 'Producto agregado al carrito';
        debug_log("✓ ÉXITO: " . $result['message']);
    } else {
        debug_log("✗ ERROR: " . $result['message']);
    }

    debug_log("=== FIN PETICIÓN ADD-TO-CART ===\n");
    echo json_encode($result);

} catch (Exception $e) {
    debug_log("✗ EXCEPCIÓN CAPTURADA: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    debug_log("=== FIN PETICIÓN (CON ERROR) ===\n");
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>