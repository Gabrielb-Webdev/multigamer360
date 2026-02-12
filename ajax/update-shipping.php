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

$shipping_option = $_POST['shipping_option'] ?? '';

// Validar opción de envío
$validOptions = ['standard', 'express', 'overnight'];
if (!in_array($shipping_option, $validOptions)) {
    echo json_encode(['success' => false, 'message' => 'Opción de envío no válida']);
    exit;
}

// Guardar en sesión
$_SESSION['shipping_option'] = $shipping_option;

echo json_encode([
    'success' => true,
    'message' => 'Opción de envío actualizada',
    'shipping_option' => $shipping_option
]);
?>