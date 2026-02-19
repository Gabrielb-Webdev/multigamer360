<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';
initSecureSession();

// Verificar que el usuario esté logueado
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para usar la wishlist']);
    exit;
}

// Incluir base de datos
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? 'toggle'; // 'add', 'remove', 'toggle'
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

try {
    // Verificar que el producto existe
    $productStmt = $pdo->prepare("
        SELECT id, name, 
               COALESCE(price_pesos, price_dollars, 0) as price, 
               COALESCE(main_image, '') as image_url 
        FROM products 
        WHERE id = ? AND is_active = 1
    ");
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    
    // Verificar si el producto ya está en la wishlist
    $checkStmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$user_id, $product_id]);
    $exists = $checkStmt->fetch();
    
    $was_in_wishlist = (bool)$exists;
    $message = '';
    $in_wishlist = false;
    
    switch ($action) {
        case 'add':
            if (!$was_in_wishlist) {
                $addStmt = $pdo->prepare("INSERT INTO user_favorites (user_id, product_id) VALUES (?, ?)");
                $addStmt->execute([$user_id, $product_id]);
                $message = '❤️ ' . $product['name'] . ' agregado a tu wishlist';
                $in_wishlist = true;
            } else {
                $message = 'El producto ya estaba en tu wishlist';
                $in_wishlist = true;
            }
            break;
            
        case 'remove':
            if ($was_in_wishlist) {
                $removeStmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND product_id = ?");
                $removeStmt->execute([$user_id, $product_id]);
                $message = '💔 ' . $product['name'] . ' eliminado de tu wishlist';
                $in_wishlist = false;
            } else {
                $message = 'El producto no estaba en tu wishlist';
                $in_wishlist = false;
            }
            break;
            
        case 'toggle':
        default:
            if ($was_in_wishlist) {
                $removeStmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND product_id = ?");
                $removeStmt->execute([$user_id, $product_id]);
                $message = '💔 ' . $product['name'] . ' eliminado de tu wishlist';
                $in_wishlist = false;
            } else {
                $addStmt = $pdo->prepare("INSERT INTO user_favorites (user_id, product_id) VALUES (?, ?)");
                $addStmt->execute([$user_id, $product_id]);
                $message = '❤️ ' . $product['name'] . ' agregado a tu wishlist';
                $in_wishlist = true;
            }
            break;
    }
    
    // Obtener el conteo actualizado de la wishlist (solo productos activos)
    $countStmt = $pdo->prepare("
        SELECT COUNT(uf.product_id) 
        FROM user_favorites uf
        JOIN products p ON uf.product_id = p.id
        WHERE uf.user_id = ? AND p.is_active = 1
    ");
    $countStmt->execute([$user_id]);
    $count = $countStmt->fetchColumn();
    
    // Calcular el valor total de la wishlist
    $totalStmt = $pdo->prepare("
        SELECT SUM(COALESCE(p.price_pesos, p.price_dollars, 0)) as total
        FROM user_favorites uf
        JOIN products p ON uf.product_id = p.id
        WHERE uf.user_id = ? AND p.is_active = 1
    ");
    $totalStmt->execute([$user_id]);
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $wishlistTotal = $totalResult['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'in_wishlist' => $in_wishlist,
        'count' => (int)$count,
        'new_total' => (float)$wishlistTotal,
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image_url' => $product['image_url']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en toggle-wishlist.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage(), // Temporal para debug
        'line' => $e->getLine()
    ]);
}
?>