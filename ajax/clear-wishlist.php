<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Limpiar wishlist
$_SESSION['wishlist'] = [];

echo json_encode([
    'success' => true,
    'message' => 'Lista de deseos limpiada',
    'wishlist_count' => 0
]);
?>