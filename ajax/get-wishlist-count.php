<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';
initSecureSession();

// Verificar que el usuario esté logueado
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado', 'count' => 0]);
    exit;
}

// Incluir base de datos
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    $user_id = $_SESSION['user_id'];
    
    // Obtener el conteo total de la wishlist (solo productos activos)
    // Eliminamos DISTINCT para que coincida con la lista visual si hay duplicados
    $countStmt = $pdo->prepare("
        SELECT COUNT(uf.product_id) 
        FROM user_favorites uf
        JOIN products p ON uf.product_id = p.id
        WHERE uf.user_id = ? AND p.is_active = 1
    ");
    $countStmt->execute([$user_id]);
    $count = $countStmt->fetchColumn();
    
    // Obtener todos los IDs de productos en la wishlist (solo activos)
    $itemsStmt = $pdo->prepare("
        SELECT DISTINCT uf.product_id 
        FROM user_favorites uf
        JOIN products p ON uf.product_id = p.id
        WHERE uf.user_id = ? AND p.is_active = 1
    ");
    $itemsStmt->execute([$user_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si se solicita un producto específico, verificar si está en la wishlist
    $product_id = intval($_GET['product_id'] ?? 0);
    $in_wishlist = false;
    
    if ($product_id > 0) {
        $in_wishlist = in_array($product_id, $items);
    }
    
    echo json_encode([
        'success' => true,
        'count' => (int)$count,
        'items' => array_map('intval', $items), // Array de IDs de productos
        'in_wishlist' => $in_wishlist,
        'product_id' => $product_id
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-wishlist-count.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'count' => 0
    ]);
}
?>