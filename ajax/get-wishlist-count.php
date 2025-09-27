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

try {
    $user_id = $_SESSION['user_id'];
    
    // Obtener el conteo total de la wishlist
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ?");
    $countStmt->execute([$user_id]);
    $count = $countStmt->fetchColumn();
    
    // Si se solicita un producto específico, verificar si está en la wishlist
    $product_id = intval($_GET['product_id'] ?? 0);
    $in_wishlist = false;
    
    if ($product_id > 0) {
        $checkStmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        $in_wishlist = (bool)$checkStmt->fetch();
    }
    
    echo json_encode([
        'success' => true,
        'count' => (int)$count,
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