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
$shippingMethod = $_POST['shipping_method'] ?? null;
$shippingName = $_POST['shipping_name'] ?? '';
$postalCode = $_POST['postal_code'] ?? $_SESSION['postal_code'] ?? '';

// DEBUG: Log de valores recibidos
error_log("=== SET-SHIPPING DEBUG ===");
error_log("POST data: " . print_r($_POST, true));
error_log("shipping_method recibido: " . var_export($shippingMethod, true));
error_log("isset: " . (isset($shippingMethod) ? 'true' : 'false'));
error_log("valor comparado con '': " . ($shippingMethod === '' ? 'true' : 'false'));
error_log("valor comparado con null: " . ($shippingMethod === null ? 'true' : 'false'));

// Validar datos - IMPORTANTE: No usar empty() porque "0" es válido para retiro local
if (!isset($shippingMethod) || $shippingMethod === '' || $shippingMethod === null) {
    error_log("RECHAZADO: Método de envío no especificado");
    echo json_encode(['success' => false, 'message' => 'Método de envío no especificado', 'debug' => ['received' => $shippingMethod, 'post' => $_POST]]);
    exit;
}

error_log("ACEPTADO: shipping_method = " . $shippingMethod);

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
