<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Inicializar wishlist si no existe
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Aceptar tanto GET como POST
$product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido', 'in_wishlist' => false]);
    exit;
}

// Verificar si el producto está en la wishlist
$in_wishlist = in_array($product_id, $_SESSION['wishlist']);

echo json_encode([
    'success' => true,
    'in_wishlist' => $in_wishlist,
    'wishlist_count' => count($_SESSION['wishlist'])
]);
?>