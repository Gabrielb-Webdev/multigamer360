<?php
/**
 * =====================================================
 * PAYMENT HELPER - FUNCIONES AUXILIARES DE PAGOS
 * MultiGamer360
 * =====================================================
 */

class PaymentHelper {
    
    private $pdo;
    private $config;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config/payment_config.php';
    }
    
    /**
     * Generar código de reserva único
     */
    public function generateReservationCode() {
        $prefix = $this->config['presential']['reservation']['code_prefix'] ?? 'MG360';
        
        do {
            // Formato: MG360-YYMMDD-XXXX
            $code = $prefix . '-' . date('ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            // Verificar que no exista
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE reservation_code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn();
            
        } while ($exists > 0);
        
        return $code;
    }
    
    /**
     * Calcular fecha de vencimiento de reserva
     */
    public function calculateReservationExpiry() {
        $hours = $this->config['presential']['reservation']['expiration_hours'] ?? 48;
        
        // Calcular solo horas hábiles (Lun-Sab, 10:00-20:00)
        $expiry = new DateTime();
        $hoursAdded = 0;
        
        while ($hoursAdded < $hours) {
            $expiry->add(new DateInterval('PT1H'));
            
            // Si es domingo, saltar al lunes
            if ($expiry->format('w') == 0) {
                $expiry->modify('next monday 10:00');
            }
            
            // Si está en horario hábil (10-20hs), contar
            $hour = (int)$expiry->format('H');
            $dayOfWeek = (int)$expiry->format('w');
            
            if ($dayOfWeek >= 1 && $dayOfWeek <= 6 && $hour >= 10 && $hour < 20) {
                $hoursAdded++;
            }
        }
        
        return $expiry->format('Y-m-d H:i:s');
    }
    
    /**
     * Calcular fecha límite para pago de transferencia
     */
    public function calculateTransferDeadline() {
        $hours = $this->config['bank_transfer']['payment_deadline_hours'] ?? 48;
        
        $deadline = new DateTime();
        $deadline->add(new DateInterval('PT' . $hours . 'H'));
        
        return $deadline->format('Y-m-d H:i:s');
    }
    
    /**
     * Obtener métodos de pago disponibles según tipo de entrega
     */
    public function getAvailablePaymentMethods($deliveryType = 'pickup_store') {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payment_methods_config 
            WHERE is_active = 1 
            AND JSON_CONTAINS(for_delivery_types, ?)
            ORDER BY display_order ASC
        ");
        
        $stmt->execute([json_encode($deliveryType)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener datos bancarios activos
     */
    public function getActiveBankAccounts() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM bank_accounts 
            WHERE is_active = 1 
            ORDER BY is_primary DESC, id ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener cuenta bancaria principal
     */
    public function getPrimaryBankAccount() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM bank_accounts 
            WHERE is_active = 1 AND is_primary = 1 
            LIMIT 1
        ");
        
        $stmt->execute();
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay en DB, usar config
        if (!$account) {
            return $this->config['bank_transfer']['primary_account'];
        }
        
        return $account;
    }
    
    /**
     * Calcular descuento por método de pago
     */
    public function calculatePaymentDiscount($subtotal, $paymentMethod) {
        $discount = 0;
        
        // Descuento por transferencia
        if ($paymentMethod === 'bank_transfer' && 
            $this->config['bank_transfer']['discount']['enabled']) {
            
            $percentage = $this->config['bank_transfer']['discount']['percentage'];
            $discount = ($subtotal * $percentage) / 100;
        }
        
        return $discount;
    }
    
    /**
     * Guardar transacción de pago
     */
    public function saveTransaction($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_transactions 
            (order_id, gateway, transaction_id, amount, currency, status, 
             payment_method, installments, payer_email, payer_name, raw_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['order_id'],
            $data['gateway'] ?? 'unknown',
            $data['transaction_id'] ?? null,
            $data['amount'],
            $data['currency'] ?? 'ARS',
            $data['status'] ?? 'pending',
            $data['payment_method'] ?? null,
            $data['installments'] ?? 1,
            $data['payer_email'] ?? null,
            $data['payer_name'] ?? null,
            json_encode($data['raw_response'] ?? [])
        ]);
    }
    
    /**
     * Actualizar estado de transacción
     */
    public function updateTransactionStatus($transactionId, $status, $rawResponse = []) {
        $stmt = $this->pdo->prepare("
            UPDATE payment_transactions 
            SET status = ?, 
                raw_response = ?,
                webhook_received_at = NOW(),
                updated_at = NOW()
            WHERE transaction_id = ?
        ");
        
        return $stmt->execute([
            $status,
            json_encode($rawResponse),
            $transactionId
        ]);
    }
    
    /**
     * Registrar webhook recibido
     */
    public function logWebhook($gateway, $eventType, $payload) {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_webhooks_log 
            (gateway, event_type, transaction_id, payload, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $gateway,
            $eventType,
            $payload['transaction_id'] ?? null,
            json_encode($payload),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Formatear precio argentino
     */
    public function formatPrice($amount, $includeSymbol = true) {
        $formatted = number_format($amount, 0, ',', '.');
        return $includeSymbol ? '$' . $formatted : $formatted;
    }
    
    /**
     * Enviar email de confirmación
     */
    public function sendPaymentEmail($orderId, $type = 'reservation') {
        // Obtener datos de la orden
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   CONCAT(o.customer_first_name, ' ', o.customer_last_name) as customer_name
            FROM orders o
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        $to = $order['customer_email'];
        $customerName = $order['customer_name'];
        $orderNumber = $order['order_number'];
        $total = $this->formatPrice($order['total_amount']);
        
        // Configurar según tipo
        switch ($type) {
            case 'reservation':
                $subject = "🎮 Reserva Confirmada - #{$orderNumber}";
                $message = $this->getReservationEmailTemplate($order);
                break;
                
            case 'payment_approved':
                $subject = "✅ Pago Confirmado - #{$orderNumber}";
                $message = $this->getPaymentApprovedEmailTemplate($order);
                break;
                
            case 'transfer_instructions':
                $subject = "📋 Instrucciones de Transferencia - #{$orderNumber}";
                $message = $this->getTransferInstructionsEmailTemplate($order);
                break;
                
            default:
                return false;
        }
        
        // Headers
        $headers = [
            'From: MultiGamer360 <noreply@multigamer360.com>',
            'Reply-To: ventas@multigamer360.com',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Template de email de reserva
     */
    private function getReservationEmailTemplate($order) {
        $code = $order['reservation_code'];
        $total = $this->formatPrice($order['total_amount']);
        $expiry = date('d/m/Y H:i', strtotime($order['reservation_expires']));
        $storeInfo = $this->config['presential']['store_info'];
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #DC3545, #b02a37); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .code-box { background: #fff; border: 3px dashed #DC3545; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
                .code { font-size: 32px; font-weight: bold; color: #DC3545; letter-spacing: 2px; }
                .info-box { background: #fff; padding: 15px; margin: 15px 0; border-left: 4px solid #DC3545; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎮 ¡Reserva Confirmada!</h1>
                    <p>Orden #{$order['order_number']}</p>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$order['customer_first_name']}</strong>,</p>
                    <p>Tu reserva fue confirmada exitosamente. Usá este código para retirar tu pedido:</p>
                    
                    <div class='code-box'>
                        <div style='font-size: 14px; margin-bottom: 10px;'>CÓDIGO DE RETIRO</div>
                        <div class='code'>{$code}</div>
                    </div>
                    
                    <div class='info-box'>
                        <h3>📍 Dónde retirar:</h3>
                        <p>
                            <strong>{$storeInfo['name']}</strong><br>
                            {$storeInfo['address']}<br>
                            {$storeInfo['city']}, CP {$storeInfo['postal_code']}<br>
                            📞 {$storeInfo['phone']}
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>🕐 Horarios de atención:</h3>
                        <p>
                            Lunes a Viernes: {$storeInfo['schedule']['monday']}<br>
                            Sábados: {$storeInfo['schedule']['saturday']}<br>
                            Domingos: {$storeInfo['schedule']['sunday']}
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>💰 Total a pagar:</h3>
                        <p style='font-size: 24px; font-weight: bold; color: #DC3545;'>{$total}</p>
                        <p>Métodos de pago aceptados:</p>
                        <ul>
                            <li>✅ Efectivo</li>
                            <li>✅ Tarjeta de débito/crédito</li>
                            <li>✅ Transferencia bancaria</li>
                            <li>✅ QR Mercado Pago</li>
                        </ul>
                    </div>
                    
                    <div class='info-box' style='background: #fff3cd; border-color: #ffc107;'>
                        <h3>⏰ Importante:</h3>
                        <p>Esta reserva vence el <strong>{$expiry}</strong></p>
                        <p>Si no retirás el producto antes de esa fecha, la reserva se cancelará automáticamente.</p>
                    </div>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <strong>¡Gracias por tu compra!</strong><br>
                        Equipo MultiGamer360
                    </p>
                </div>
                <div class='footer'>
                    <p>Este es un email automático, por favor no respondas.<br>
                    Para consultas: {$storeInfo['email']}</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de email de pago aprobado
     */
    private function getPaymentApprovedEmailTemplate($order) {
        // Similar al anterior, ajustando el mensaje
        return $this->getReservationEmailTemplate($order);
    }
    
    /**
     * Template de email de instrucciones de transferencia
     */
    private function getTransferInstructionsEmailTemplate($order) {
        $total = $this->formatPrice($order['total_amount']);
        $deadline = date('d/m/Y H:i', strtotime($order['payment_deadline']));
        $bankAccount = $this->getPrimaryBankAccount();
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745, #20853a); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .bank-box { background: #fff; padding: 20px; margin: 20px 0; border-radius: 10px; border: 2px solid #28a745; }
                .bank-data { font-family: monospace; font-size: 16px; padding: 5px; background: #f0f0f0; margin: 5px 0; }
                .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Instrucciones de Pago</h1>
                    <p>Orden #{$order['order_number']}</p>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$order['customer_first_name']}</strong>,</p>
                    <p>Para completar tu pedido, realizá la transferencia bancaria con los siguientes datos:</p>
                    
                    <div class='bank-box'>
                        <h3>🏦 Datos para la transferencia:</h3>
                        <p><strong>Banco:</strong> {$bankAccount['bank_name']}</p>
                        <div class='bank-data'>
                            <strong>CBU:</strong> {$bankAccount['cbu']}
                        </div>
                        <div class='bank-data'>
                            <strong>Alias:</strong> {$bankAccount['alias']}
                        </div>
                        <p><strong>Titular:</strong> {$bankAccount['holder_name']}</p>
                        <p><strong>CUIT:</strong> {$bankAccount['holder_cuit']}</p>
                        <div style='margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 8px;'>
                            <strong>Monto a transferir:</strong><br>
                            <span style='font-size: 28px; color: #28a745; font-weight: bold;'>{$total}</span>
                        </div>
                    </div>
                    
                    <div class='warning-box'>
                        <h3>⏰ Plazo de pago:</h3>
                        <p>Realizá la transferencia antes del <strong>{$deadline}</strong></p>
                        <p>Si no recibimos el comprobante antes de esa fecha, tu pedido será cancelado.</p>
                    </div>
                    
                    <div class='bank-box'>
                        <h3>📸 Después de transferir:</h3>
                        <ol>
                            <li>Tomá captura del comprobante</li>
                            <li>Subilo en: <a href='https://teal-fish-507993.hostingersite.com/order_tracking.php?code={$order['reservation_code']}'>Ver mi pedido</a></li>
                            <li>Validaremos tu pago en 24hs hábiles</li>
                            <li>Te avisaremos cuando esté listo para retirar</li>
                        </ol>
                    </div>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <strong>¡Gracias por tu compra!</strong><br>
                        Equipo MultiGamer360
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Verificar si una reserva está vencida
     */
    public function isReservationExpired($reservationExpires) {
        $now = new DateTime();
        $expiry = new DateTime($reservationExpires);
        return $now > $expiry;
    }
    
    /**
     * Cancelar reservas vencidas automáticamente
     */
    public function cancelExpiredReservations() {
        if (!$this->config['presential']['reservation']['auto_cancel_expired']) {
            return 0;
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled',
                notes = CONCAT(IFNULL(notes, ''), ' [Cancelado automáticamente por vencimiento]')
            WHERE payment_type = 'presential'
            AND status = 'pending'
            AND reservation_expires < NOW()
        ");
        
        $stmt->execute();
        return $stmt->rowCount();
    }
}
