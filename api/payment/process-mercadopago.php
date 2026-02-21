<?php
/**
 * =====================================================
 * PROCESAR PAGO CON MERCADO PAGO
 * MultiGamer360
 * =====================================================
 * 
 * Este archivo crea una preferencia de pago en Mercado Pago
 * y redirige al usuario a la página de pago
 */

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependencias
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/payment_helper.php';

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /checkout.php');
    exit();
}

// Verificar que haya orden en sesión
if (!isset($_SESSION['pending_order_id'])) {
    $_SESSION['checkout_error'] = 'No se encontró la orden pendiente.';
    header('Location: /checkout.php');
    exit();
}

try {
    // Cargar configuración
    $config = require __DIR__ . '/../../config/payment_config.php';
    $paymentHelper = new PaymentHelper($pdo);
    
    // Verificar que Mercado Pago esté habilitado
    if (!$config['mercadopago']['enabled']) {
        throw new Exception('Mercado Pago no está habilitado');
    }
    
    // Obtener orden de la base de datos
    $orderId = $_SESSION['pending_order_id'];
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Orden no encontrada');
    }
    
    // Obtener items de la orden
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image_url 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determinar credenciales según modo
    $mode = $config['mercadopago']['mode']; // 'sandbox' o 'production'
    $credentials = $config['mercadopago'][$mode];
    $accessToken = $credentials['access_token'];
    
    // Construir items para Mercado Pago
    $mpItems = [];
    foreach ($items as $item) {
        $mpItems[] = [
            'title' => $item['name'],
            'quantity' => (int)$item['quantity'],
            'unit_price' => (float)$item['price'],
            'currency_id' => 'ARS'
        ];
    }
    
    // Agregar costo de envío como item si existe
    if ($order['shipping_cost'] > 0) {
        $mpItems[] = [
            'title' => 'Envío - ' . $order['shipping_method'],
            'quantity' => 1,
            'unit_price' => (float)$order['shipping_cost'],
            'currency_id' => 'ARS'
        ];
    }
    
    // Construir preferencia de pago
    $preference = [
        'items' => $mpItems,
        'payer' => [
            'name' => $order['customer_first_name'],
            'surname' => $order['customer_last_name'],
            'email' => $order['customer_email'],
            'phone' => [
                'number' => $order['customer_phone']
            ]
        ],
        'back_urls' => [
            'success' => $config['mercadopago']['success_url'] . '?order=' . $order['order_number'],
            'failure' => $config['mercadopago']['failure_url'] . '?order=' . $order['order_number'],
            'pending' => $config['mercadopago']['pending_url'] . '?order=' . $order['order_number']
        ],
        'auto_return' => 'approved',
        'external_reference' => $order['order_number'],
        'statement_descriptor' => $config['mercadopago']['statement_descriptor'],
        'notification_url' => $config['mercadopago']['webhook_url']
    ];
    
    // Configurar cuotas si está habilitado
    if ($config['mercadopago']['installments']['enabled']) {
        $preference['payment_methods'] = [
            'installments' => (int)$config['mercadopago']['installments']['max_installments']
        ];
    }
    
    // Configurar expiración
    if ($config['mercadopago']['expires']) {
        $expirationDate = new DateTime();
        $expirationDate->add(new DateInterval('P' . $config['mercadopago']['expiration_days'] . 'D'));
        $preference['expires'] = true;
        $preference['expiration_date_to'] = $expirationDate->format('c');
    }
    
    // Hacer request a Mercado Pago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 201) {
        $error = json_decode($response, true);
        throw new Exception('Error al crear preferencia: ' . ($error['message'] ?? 'Desconocido'));
    }
    
    $preferenceData = json_decode($response, true);
    
    // Guardar preference ID en la orden
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET mercadopago_preference_id = ?,
            payment_gateway = 'mercadopago',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$preferenceData['id'], $orderId]);
    
    // Guardar transacción inicial
    $paymentHelper->saveTransaction([
        'order_id' => $orderId,
        'gateway' => 'mercadopago',
        'transaction_id' => $preferenceData['id'],
        'amount' => $order['total_amount'],
        'currency' => 'ARS',
        'status' => 'pending',
        'raw_response' => $preferenceData
    ]);
    
    // Determinar URL de redirección según modo
    if ($mode === 'sandbox') {
        $redirectUrl = $preferenceData['sandbox_init_point'];
    } else {
        $redirectUrl = $preferenceData['init_point'];
    }
    
    // Redirigir a Mercado Pago
    header('Location: ' . $redirectUrl);
    exit();
    
} catch (Exception $e) {
    error_log('Error en process-mercadopago.php: ' . $e->getMessage());
    $_SESSION['checkout_error'] = 'Error al procesar el pago: ' . $e->getMessage();
    header('Location: /checkout.php');
    exit();
}
