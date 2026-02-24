<?php
/**
 * =====================================================
 * AJAX: Calcular envíos dinámicos
 * Endpoint para obtener opciones de envío en tiempo real
 * =====================================================
 */

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/shipping_calculator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$postalCode = trim($_POST['postal_code'] ?? '');
$cartTotal = floatval($_POST['cart_total'] ?? 0);
$cartWeight = floatval($_POST['cart_weight'] ?? 1.0); // Peso en kg

// Validar código postal argentino (4 dígitos)
if (empty($postalCode) || !preg_match('/^\d{4}$/', $postalCode)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Código postal inválido. Debe tener 4 dígitos.'
    ]);
    exit;
}

try {
    // Inicializar calculador
    $calculator = new ShippingCalculator($pdo);
    
    // Calcular opciones de envío
    $shippingOptions = $calculator->calculateShipping(
        $postalCode,
        $cartWeight,
        $cartTotal
    );
    
    if (empty($shippingOptions)) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay opciones de envío disponibles para este código postal.'
        ]);
        exit;
    }
    
    // Formatear respuesta
    $formattedOptions = [];
    foreach ($shippingOptions as $option) {
        $formattedOptions[] = [
            'provider' => $option['provider'],
            'id' => $option['provider'] . '_' . sanitizeProviderName($option['service_name']),
            'name' => $option['service_name'],
            'price' => $option['price'],
            'price_formatted' => $calculator->formatPrice($option['price']),
            'delivery_days' => $option['delivery_days'],
            'delivery_text' => getDeliveryText($option['delivery_days']),
            'description' => $option['description'] ?? '',
            'is_estimated' => $option['estimated'] ?? false
        ];
    }
    
    // Guardar en sesión
    $_SESSION['postal_code'] = $postalCode;
    $_SESSION['shipping_options'] = $formattedOptions;
    
    echo json_encode([
        'success' => true,
        'postal_code' => $postalCode,
        'options' => $formattedOptions,
        'total_options' => count($formattedOptions)
    ]);
    
} catch (Exception $e) {
    error_log("Error calculando envío: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al calcular opciones de envío. Intenta nuevamente.'
    ]);
}

/**
 * Sanitizar nombre de proveedor para usar como ID
 */
function sanitizeProviderName($name) {
    $sanitized = strtolower($name);
    $sanitized = str_replace(' ', '_', $sanitized);
    $sanitized = preg_replace('/[^a-z0-9_]/', '', $sanitized);
    return $sanitized;
}

/**
 * Obtener texto de entrega según días
 */
function getDeliveryText($days) {
    if ($days == 1) {
        return 'Llega mañana';
    } elseif ($days == 2) {
        return 'Llega en 2 días hábiles';
    } elseif ($days <= 4) {
        return "Llega en {$days} días hábiles";
    } else {
        return "Llega entre {$days} y " . ($days + 2) . " días hábiles";
    }
}
?>
