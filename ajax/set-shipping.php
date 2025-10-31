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

// Obtener datos del POST
$shippingMethod = $_POST['shippingMethod'] ?? '';
$postalCode = $_POST['postalCode'] ?? '';

// Validar que se recibió el método de envío
if (empty($shippingMethod) && $shippingMethod !== '0') {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos - No se recibió método de envío']);
    exit;
}

// Solo validar código postal si NO es retiro en local
if ($shippingMethod !== '0' && empty($postalCode)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos - Se requiere código postal para este método de envío']);
    exit;
}

// Guardar en sesión
$_SESSION['shipping_method'] = $shippingMethod;
$_SESSION['postal_code'] = $postalCode;

// Mapear el método de envío a un costo y nombre
$shipping_info = [
    '3500' => ['cost' => 3500, 'name' => 'Envío en moto dentro de CABA', 'type' => 'delivery'],
    '5648' => ['cost' => 5648, 'name' => 'Envío Nube - Correo Argentino', 'type' => 'delivery'],
    '6214' => ['cost' => 6214, 'name' => 'Envío Expreso - Correo Argentino', 'type' => 'delivery'],
    '0' => ['cost' => 0, 'name' => 'Retiro en Multigamer 360', 'type' => 'pickup'],
    '2207' => ['cost' => 2207, 'name' => 'Retiro en Punto de Retiro', 'type' => 'pickup']
];

if (isset($shipping_info[$shippingMethod])) {
    $_SESSION['shipping_cost'] = $shipping_info[$shippingMethod]['cost'];
    $_SESSION['shipping_name'] = $shipping_info[$shippingMethod]['name'];
    $_SESSION['shipping_type'] = $shipping_info[$shippingMethod]['type'];
}

echo json_encode([
    'success' => true,
    'message' => 'Método de envío guardado',
    'shipping_method' => $shippingMethod,
    'postal_code' => $postalCode
]);
