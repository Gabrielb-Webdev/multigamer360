<?php
require_once 'database.php';

echo "=== CONFIGURANDO SISTEMA DE AJUSTES DEL SITIO ===" . PHP_EOL;

try {
    // Crear tabla site_settings
    echo "1. Creando tabla site_settings..." . PHP_EOL;
    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'email', 'url', 'textarea') DEFAULT 'text',
        category VARCHAR(50) DEFAULT 'general',
        description TEXT,
        is_public TINYINT(1) DEFAULT 0 COMMENT 'Si la configuración es visible en el frontend',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "   ✓ Tabla site_settings creada" . PHP_EOL;
    
    // Insertar configuraciones por defecto
    echo "2. Insertando configuraciones por defecto..." . PHP_EOL;
    
    $default_settings = [
        // Configuraciones Generales
        ['site_name', 'MultiGamer360', 'text', 'general', 'Nombre del sitio web', 1],
        ['site_description', 'Tu tienda gaming online - Los mejores videojuegos, consolas y accesorios', 'textarea', 'general', 'Descripción del sitio', 1],
        ['site_email', 'info@multigamer360.com', 'email', 'general', 'Email principal de contacto', 1],
        ['site_phone', '+1 (555) 123-4567', 'text', 'general', 'Teléfono de contacto', 1],
        ['site_address', '123 Gaming Street, Tech City, TC 12345', 'textarea', 'general', 'Dirección física', 1],
        
        // Configuraciones de Moneda
        ['currency', 'USD', 'text', 'financial', 'Moneda principal del sitio', 0],
        ['currency_symbol', '$', 'text', 'financial', 'Símbolo de la moneda', 1],
        ['tax_rate', '8.5', 'number', 'financial', 'Tasa de impuesto por defecto (%)', 0],
        
        // Configuraciones de Envío
        ['shipping_cost', '9.99', 'number', 'shipping', 'Costo de envío estándar', 0],
        ['free_shipping_threshold', '100.00', 'number', 'shipping', 'Monto mínimo para envío gratis', 1],
        ['processing_time', '1', 'number', 'shipping', 'Tiempo de procesamiento en días', 1],
        ['delivery_time_standard', '3-5', 'text', 'shipping', 'Tiempo de entrega estándar', 1],
        ['delivery_time_express', '1-2', 'text', 'shipping', 'Tiempo de entrega express', 1],
        
        // Configuraciones de Pago
        ['payment_paypal_enabled', '1', 'boolean', 'payment', 'Habilitar pagos con PayPal', 0],
        ['payment_stripe_enabled', '1', 'boolean', 'payment', 'Habilitar pagos con Stripe', 0],
        ['payment_cash_enabled', '1', 'boolean', 'payment', 'Habilitar pago en efectivo', 0],
        
        // Configuraciones de Email
        ['email_smtp_host', 'smtp.gmail.com', 'text', 'email', 'Servidor SMTP', 0],
        ['email_smtp_port', '587', 'number', 'email', 'Puerto SMTP', 0],
        ['email_smtp_username', '', 'text', 'email', 'Usuario SMTP', 0],
        ['email_smtp_password', '', 'text', 'email', 'Contraseña SMTP', 0],
        ['email_from', 'info@multigamer360.com', 'email', 'email', 'Email del remitente', 0],
        ['email_from_name', 'MultiGamer360', 'text', 'email', 'Nombre del remitente', 0],
        
        // Configuraciones SEO
        ['seo_meta_title', 'MultiGamer360 - Tu tienda gaming online', 'text', 'seo', 'Título meta principal', 1],
        ['seo_meta_description', 'Los mejores videojuegos, consolas y accesorios gaming al mejor precio. Envío gratis en compras mayores a $100.', 'textarea', 'seo', 'Descripción meta principal', 1],
        ['seo_meta_keywords', 'videojuegos, gaming, consolas, PC gaming, accesorios, PlayStation, Xbox, Nintendo', 'textarea', 'seo', 'Palabras clave principales', 1],
        
        // Redes Sociales
        ['social_facebook', '', 'url', 'social', 'URL de Facebook', 1],
        ['social_twitter', '', 'url', 'social', 'URL de Twitter', 1],
        ['social_instagram', '', 'url', 'social', 'URL de Instagram', 1],
        ['social_youtube', '', 'url', 'social', 'URL de YouTube', 1],
        
        // Configuraciones Avanzadas
        ['maintenance_mode', '0', 'boolean', 'advanced', 'Modo mantenimiento activo', 0],
        ['user_registration_enabled', '1', 'boolean', 'advanced', 'Permitir registro de usuarios', 0],
        ['reviews_auto_approve', '0', 'boolean', 'advanced', 'Auto-aprobar reseñas', 0],
        ['inventory_low_stock_alert', '5', 'number', 'advanced', 'Alerta de stock bajo', 0],
        ['inventory_auto_update', '1', 'boolean', 'advanced', 'Actualización automática de inventario', 0],
        
        // Configuraciones adicionales
        ['email_notifications_enabled', '1', 'boolean', 'email', 'Habilitar notificaciones por email', 0],
        ['seo_sitemap_enabled', '1', 'boolean', 'seo', 'Generar sitemap automáticamente', 0]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_type = VALUES(setting_type), category = VALUES(category), description = VALUES(description), is_public = VALUES(is_public)");
    
    foreach ($default_settings as $setting) {
        $stmt->execute($setting);
        echo "     ✓ Configuración: " . $setting[0] . " = " . $setting[1] . PHP_EOL;
    }
    
    echo PHP_EOL . "3. Verificando configuraciones..." . PHP_EOL;
    
    // Mostrar estadísticas
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM site_settings GROUP BY category");
    echo "   Configuraciones por categoría:" . PHP_EOL;
    while ($row = $stmt->fetch()) {
        echo "     - " . ucfirst($row['category']) . ": " . $row['count'] . " configuraciones" . PHP_EOL;
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM site_settings");
    $total = $stmt->fetch()['total'];
    echo "   Total de configuraciones: " . $total . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as public_count FROM site_settings WHERE is_public = 1");
    $public_count = $stmt->fetch()['public_count'];
    echo "   Configuraciones públicas: " . $public_count . PHP_EOL;
    
    echo PHP_EOL . "✅ Sistema de configuraciones completado exitosamente!" . PHP_EOL;
    echo "📝 Accede al panel de configuraciones en: admin/settings.php" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>