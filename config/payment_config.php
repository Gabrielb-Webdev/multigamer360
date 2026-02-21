<?php
/**
 * =====================================================
 * CONFIGURACIÓN DE MÉTODOS DE PAGO - ARGENTINA
 * MultiGamer360
 * =====================================================
 * 
 * IMPORTANTE: 
 * - Actualizar con tus credenciales reales
 * - NO subir a repositorio público con datos reales
 * - Usar variables de entorno en producción
 */

return [
    
    // =====================================================
    // MERCADO PAGO - CONFIGURACIÓN
    // =====================================================
    'mercadopago' => [
        'enabled' => true,
        'mode' => 'sandbox', // 'sandbox' para pruebas, 'production' para real
        
        // SANDBOX (PRUEBAS) - Obtener en: https://www.mercadopago.com.ar/developers/panel/app
        'sandbox' => [
            'public_key' => 'TEST-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Tu Public Key de TEST
            'access_token' => 'TEST-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxx', // Tu Access Token de TEST
        ],
        
        // PRODUCTION (REAL) - Obtener credenciales en: https://www.mercadopago.com.ar/developers/panel/app
        'production' => [
            'public_key' => 'APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Tu Public Key REAL
            'access_token' => 'APP_USR-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxx', // Tu Access Token REAL
        ],
        
        // URLs de retorno (actualizar con tu dominio)
        'success_url' => 'https://teal-fish-507993.hostingersite.com/order_confirmation.php',
        'pending_url' => 'https://teal-fish-507993.hostingersite.com/order_pending.php',
        'failure_url' => 'https://teal-fish-507993.hostingersite.com/checkout.php?error=payment',
        
        // URL del webhook (actualizar con tu dominio)
        'webhook_url' => 'https://teal-fish-507993.hostingersite.com/api/payment/webhook-mercadopago.php',
        
        // Configuraciones adicionales
        'statement_descriptor' => 'MultiGamer360', // Nombre que aparece en resumen de tarjeta
        'auto_return' => 'approved', // Retorno automático cuando se aprueba
        'binary_mode' => false, // true = solo aprobado/rechazado, false = incluye pending
        'expires' => true,
        'expiration_days' => 3, // Días para que expire la preferencia de pago
        
        // Cuotas disponibles
        'installments' => [
            'enabled' => true,
            'max_installments' => 12,
            'min_amount_for_installments' => 10000 // Mínimo $10.000 para cuotas
        ],
        
        // Comisión (para calcular costos)
        'commission_percentage' => 4.99,
    ],
    
    // =====================================================
    // TRANSFERENCIA BANCARIA - CONFIGURACIÓN
    // =====================================================
    'bank_transfer' => [
        'enabled' => true,
        
        // Datos bancarios principales (ACTUALIZAR CON TUS DATOS REALES)
        'primary_account' => [
            'bank_name' => 'Banco Ejemplo', // Ej: "Banco Nación", "Galicia", "BBVA"
            'account_type' => 'Caja de Ahorro', // 'Caja de Ahorro', 'Cuenta Corriente', 'Cuenta Virtual'
            'cbu' => '0000000000000000000000', // 22 dígitos
            'alias' => 'MULTIGAMER.360', // Tu alias bancario
            'cuit' => '00-00000000-0', // CUIT del titular
            'holder_name' => 'MultiGamer360', // Nombre del titular
            'currency' => 'ARS'
        ],
        
        // Cuenta alternativa (CVU para billeteras digitales)
        'virtual_account' => [
            'enabled' => false,
            'provider' => 'Mercado Pago', // 'Mercado Pago', 'Ualá', 'Brubank', etc
            'cvu' => '0000000000000000000000',
            'alias' => 'MULTI.GAMER.MP',
            'holder_name' => 'MultiGamer360'
        ],
        
        // Configuración operativa
        'payment_deadline_hours' => 48, // Plazo para realizar la transferencia (48hs hábiles)
        'requires_proof' => true, // Requiere subir comprobante
        'manual_validation' => true, // Validación manual por admin
        'auto_approve' => false, // Auto-aprobar (NO RECOMENDADO, usar manual_validation)
        
        // Descuento por transferencia
        'discount' => [
            'enabled' => true,
            'percentage' => 5, // 5% de descuento
            'description' => '5% OFF pagando con transferencia'
        ],
        
        // Formatos de comprobante aceptados
        'accepted_formats' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_file_size_mb' => 5,
        
        // Email de notificación cuando suben comprobante
        'notify_email' => 'ventas@multigamer360.com', // Tu email para notificaciones
    ],
    
    // =====================================================
    // MODO (BILLETERA DIGITAL) - CONFIGURACIÓN
    // =====================================================
    'modo' => [
        'enabled' => false, // Activar cuando tengas cuenta MODO
        'cvu' => '0000000000000000000000',
        'alias' => 'MULTI.GAMER.MODO',
        'qr_code' => '', // URL del QR de MODO si lo tenés
        'description' => 'Transferencia instantánea con MODO',
    ],
    
    // =====================================================
    // PAGO PRESENCIAL - CONFIGURACIÓN
    // =====================================================
    'presential' => [
        'enabled' => true,
        
        // Reserva con código
        'reservation' => [
            'enabled' => true,
            'expiration_hours' => 48, // Tiempo para que venza la reserva
            'code_prefix' => 'MG360', // Prefijo del código de reserva
            'auto_cancel_expired' => true, // Auto-cancelar reservas vencidas
            'release_stock_on_expire' => true // Liberar stock si vence
        ],
        
        // Métodos aceptados en local
        'accepted_methods' => [
            'cash' => [
                'enabled' => true,
                'name' => 'Efectivo',
                'description' => 'Efectivo en pesos argentinos'
            ],
            'debit_card' => [
                'enabled' => true,
                'name' => 'Tarjeta de Débito',
                'description' => 'Débito en terminal POS',
                'terminal_brand' => 'Posnet' // 'Posnet', 'Lapos', etc
            ],
            'credit_card' => [
                'enabled' => true,
                'name' => 'Tarjeta de Crédito',
                'description' => 'Crédito en terminal POS (hasta 12 cuotas)',
                'max_installments' => 12
            ],
            'qr_mercadopago' => [
                'enabled' => true,
                'name' => 'QR Mercado Pago',
                'description' => 'Escanear QR con app de Mercado Pago'
            ],
            'transfer_immediate' => [
                'enabled' => true,
                'name' => 'Transferencia Inmediata',
                'description' => 'Transferir desde tu celular en el momento'
            ]
        ],
        
        // Datos del local
        'store_info' => [
            'name' => 'MultiGamer360',
            'address' => 'Dirección del local', // ACTUALIZAR
            'city' => 'Ciudad', // ACTUALIZAR
            'postal_code' => '0000', // ACTUALIZAR
            'phone' => '+54 9 11 XXXX-XXXX', // ACTUALIZAR
            'whatsapp' => '+54 9 11 XXXX-XXXX', // ACTUALIZAR
            'email' => 'tienda@multigamer360.com',
            
            // Horarios de atención
            'schedule' => [
                'monday' => '10:00 - 20:00',
                'tuesday' => '10:00 - 20:00',
                'wednesday' => '10:00 - 20:00',
                'thursday' => '10:00 - 20:00',
                'friday' => '10:00 - 20:00',
                'saturday' => '10:00 - 18:00',
                'sunday' => 'Cerrado'
            ],
            
            // Google Maps
            'maps_url' => 'https://maps.google.com/?q=...' // ACTUALIZAR con link de Google Maps
        ]
    ],
    
    // =====================================================
    // CONTRA ENTREGA (COD - Cash On Delivery)
    // =====================================================
    'cash_on_delivery' => [
        'enabled' => false, // Activar si querés permitir pago contra entrega
        'only_cash' => true, // Solo efectivo al recibir
        'extra_fee' => 500, // Cargo adicional por este servicio
        'max_amount' => 100000, // Monto máximo para COD (100k)
        'restricted_zones' => [], // Zonas donde NO está disponible
        'description' => 'Pago en efectivo al recibir el producto (+$500 cargo adicional)'
    ],
    
    // =====================================================
    // CONFIGURACIÓN GENERAL
    // =====================================================
    'general' => [
        // Moneda
        'currency' => 'ARS',
        'currency_symbol' => '$',
        
        // Email para notificaciones de pagos
        'notification_emails' => [
            'ventas@multigamer360.com', // ACTUALIZAR
            // 'admin@multigamer360.com', // Agregar más si necesario
        ],
        
        // WhatsApp Business
        'whatsapp_notifications' => [
            'enabled' => false, // Activar cuando integres WhatsApp Business API
            'number' => '+54 9 11 XXXX-XXXX' // ACTUALIZAR
        ],
        
        // Logs
        'log_transactions' => true,
        'log_webhooks' => true,
        
        // Seguridad
        'verify_webhook_signature' => true, // Validar firma de webhooks
        'allowed_webhook_ips' => [], // IPs permitidas (vacío = todas), ej: Mercado Pago IPs
        
        // Testing
        'test_mode' => true, // true = modo prueba, false = producción
        'debug_emails' => false, // Enviar copia de emails a tu email personal
    ],
    
    // =====================================================
    // MENSAJES PERSONALIZADOS
    // =====================================================
    'messages' => [
        'reservation_success' => 'Tu reserva fue confirmada exitosamente. Recordá venir dentro de las 48hs con tu código de reserva.',
        'payment_pending' => 'Tu pago está siendo procesado. Te notificaremos cuando se confirme.',
        'payment_approved' => '¡Pago confirmado! Tu pedido está listo para retirar.',
        'payment_rejected' => 'El pago fue rechazado. Podés intentar nuevamente o elegir otro método.',
        'transfer_instructions' => 'Realizá la transferencia a los datos bancarios indicados y subí el comprobante.',
        'reservation_expired' => 'Tu reserva ha vencido. Los productos volvieron al stock.',
    ],
    
    // =====================================================
    // DESCUENTOS Y PROMOCIONES
    // =====================================================
    'promotions' => [
        // Descuento por método de pago
        'payment_method_discounts' => [
            'bank_transfer' => 5, // 5% descuento
            // 'mercadopago_debit' => 3, // 3% descuento solo débito
        ],
        
        // Descuento por retiro en tienda
        'pickup_discount' => [
            'enabled' => false,
            'percentage' => 0
        ],
        
        // Recargo por envío
        'shipping_fee' => [
            'free_from' => 50000, // Envío gratis desde $50.000
        ]
    ]
];
