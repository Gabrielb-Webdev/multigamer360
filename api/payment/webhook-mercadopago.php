<?php
/**
 * =====================================================
 * WEBHOOK DE MERCADO PAGO
 * MultiGamer360
 * =====================================================
 * 
 * Este archivo recibe las notificaciones de Mercado Pago
 * cuando un pago cambia de estado
 */

// Incluir dependencias
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/payment_helper.php';

// Cargar configuración
$config = require __DIR__ . '/../../config/payment_config.php';
$paymentHelper = new PaymentHelper($pdo);

// Obtener datos del webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Registrar webhook recibido
$paymentHelper->logWebhook('mercadopago', $data['type'] ?? 'unknown', $data);

// Log para debugging
if ($config['general']['log_webhooks']) {
    $logDir = __DIR__ . '/../../logs/payments';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/webhooks.log';
    $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode([
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'data' => $data
    ]) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Verificar que sea un evento de pago
if (!isset($data['type']) || $data['type'] !== 'payment') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'reason' => 'not a payment event']);
    exit();
}

// Obtener el ID de la transacción
$paymentId = $data['data']['id'] ?? null;

if (!$paymentId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing payment ID']);
    exit();
}

try {
    // Determinar credenciales según modo
    $mode = $config['mercadopago']['mode'];
    $credentials = $config['mercadopago'][$mode];
    $accessToken = $credentials['access_token'];
    
    // Consultar información del pago a Mercado Pago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$paymentId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Error al consultar pago: HTTP ' . $httpCode);
    }
    
    $payment = json_decode($response, true);
    
    // Obtener referencia externa (order_number)
    $orderNumber = $payment['external_reference'] ?? null;
    
    if (!$orderNumber) {
        throw new Exception('No external reference found');
    }
    
    // Buscar orden en la base de datos
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found: ' . $orderNumber);
    }
    
    // Mapear estados de Mercado Pago a nuestros estados
    $statusMap = [
        'approved' => 'approved',
        'pending' => 'pending',
        'in_process' => 'pending',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'charged_back' => 'refunded'
    ];
    
    $mpStatus = $payment['status'] ?? 'pending';
    $ourStatus = $statusMap[$mpStatus] ?? 'pending';
    
    // Actualizar transacción
    $paymentHelper->updateTransactionStatus(
        $payment['id'],
        $ourStatus,
        $payment
    );
    
    // Si el pago fue aprobado, actualizar la orden
    if ($mpStatus === 'approved') {
        // Inicio de transacción
        $pdo->beginTransaction();
        
        try {
            // Actualizar orden
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid',
                    payment_transaction_id = ?,
                    status = 'processing',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$payment['id'], $order['id']]);
            
            // Generar código de retiro si es pickup
            if (in_array($order['delivery_type'], ['pickup_store', 'pickup_point'])) {
                if (empty($order['reservation_code'])) {
                    $code = $paymentHelper->generateReservationCode();
                    
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET reservation_code = ?,
                            status = 'ready_for_pickup'
                        WHERE id = ?
                    ");
                    $stmt->execute([$code, $order['id']]);
                }
            }
            
            $pdo->commit();
            
            // Enviar email de confirmación
            $paymentHelper->sendPaymentEmail($order['id'], 'payment_approved');
            
            // Notificar al admin
            if (!empty($config['general']['notification_emails'])) {
                $adminEmails = implode(', ', $config['general']['notification_emails']);
                $subject = "💰 Nuevo Pago Aprobado - {$orderNumber}";
                $message = "Se aprobó el pago de la orden {$orderNumber}\n";
                $message .= "Cliente: {$order['customer_first_name']} {$order['customer_last_name']}\n";
                $message .= "Total: $" . number_format($payment['transaction_amount'], 0, ',', '.') . "\n";
                $message .= "Método: {$payment['payment_type_id']} - {$payment['payment_method_id']}\n";
                
                mail($adminEmails, $subject, $message);
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    // Si el pago fue rechazado
    elseif ($mpStatus === 'rejected') {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'failed',
                status = 'cancelled',
                notes = CONCAT(IFNULL(notes, ''), ' [Pago rechazado: ', ?, ']')
            WHERE id = ?
        ");
        $stmt->execute([
            $payment['status_detail'] ?? 'unknown',
            $order['id']
        ]);
    }
    
    // Responder a Mercado Pago
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook processed',
        'order' => $orderNumber,
        'payment_status' => $mpStatus
    ]);
    
} catch (Exception $e) {
    error_log('Error en webhook Mercado Pago: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
