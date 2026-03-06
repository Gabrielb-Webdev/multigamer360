<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST (sistema dinámico)
$shippingCost = floatval($_POST['shipping_cost'] ?? 0);
$shippingMethod = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : null;
$shippingName = $_POST['shipping_name'] ?? '';
$postalCode = $_POST['postal_code'] ?? $_SESSION['postal_code'] ?? '';

// Validar datos - IMPORTANTE: "0" es un valor válido para retiro local
if ($shippingMethod === null || $shippingMethod === '') {
    echo json_encode([
        'success' => false, 
        'message' => 'Método de envío no especificado',
        'debug' => [
            'POST' => $_POST,
            'shipping_method' => $shippingMethod,
            'isset' => isset($_POST['shipping_method']),
            'value_type' => gettype($shippingMethod)
        ]
    ]);
    exit;
}

// Guardar en sesión
$_SESSION['shipping_cost'] = $shippingCost;
$_SESSION['shipping_method'] = $shippingMethod;
$_SESSION['shipping_name'] = $shippingName;

// Guardar código postal en sesión si se proporcionó
if (!empty($postalCode)) {
    $_SESSION['postal_code'] = $postalCode;
}

// Determinar tipo (pickup vs delivery)
if ($shippingMethod === '0' || $shippingMethod === 'multigamer_360') {
    $_SESSION['shipping_type'] = 'pickup';
} else {
    $_SESSION['shipping_type'] = 'delivery';
}

echo json_encode([
    'success' => true,
    'message' => 'Método de envío guardado',
    'shipping_method' => $shippingMethod,
    'postal_code' => $postalCode
]);
