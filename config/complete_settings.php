<?php
require_once 'database.php';

echo "=== COMPLETANDO CONFIGURACIONES DEL SITIO ===" . PHP_EOL;

try {
    // Configuraciones adicionales que faltan
    $additional_settings = [
        // Configuraciones de Envío
        ['shipping_cost', '500', 'number', 'Costo de envío estándar'],
        ['processing_time', '1', 'number', 'Tiempo de procesamiento en días'],
        ['delivery_time_standard', '3-5', 'text', 'Tiempo de entrega estándar'],
        ['delivery_time_express', '1-2', 'text', 'Tiempo de entrega express'],
        
        // Configuraciones de Pago
        ['payment_paypal_enabled', '1', 'boolean', 'Habilitar pagos con PayPal'],
        ['payment_stripe_enabled', '1', 'boolean', 'Habilitar pagos con Stripe'],
        ['payment_cash_enabled', '1', 'boolean', 'Habilitar pago en efectivo'],
        
        // Configuraciones de Email
        ['email_smtp_host', 'smtp.gmail.com', 'text', 'Servidor SMTP'],
        ['email_smtp_port', '587', 'number', 'Puerto SMTP'],
        ['email_smtp_username', '', 'text', 'Usuario SMTP'],
        ['email_smtp_password', '', 'text', 'Contraseña SMTP'],
        ['email_from', 'info@multigamer360.com', 'text', 'Email del remitente'],
        ['email_from_name', 'MultiGamer360', 'text', 'Nombre del remitente'],
        ['email_notifications_enabled', '1', 'boolean', 'Habilitar notificaciones por email'],
        
        // Configuraciones SEO
        ['seo_meta_title', 'MultiGamer360 - Tu tienda gaming online', 'text', 'Título meta principal'],
        ['seo_meta_description', 'Los mejores videojuegos, consolas y accesorios gaming al mejor precio', 'text', 'Descripción meta principal'],
        ['seo_meta_keywords', 'videojuegos, gaming, consolas, PC gaming, accesorios', 'text', 'Palabras clave principales'],
        ['seo_sitemap_enabled', '1', 'boolean', 'Generar sitemap automáticamente'],
        
        // Redes Sociales
        ['social_facebook', '', 'text', 'URL de Facebook'],
        ['social_twitter', '', 'text', 'URL de Twitter'],
        ['social_instagram', '', 'text', 'URL de Instagram'],
        ['social_youtube', '', 'text', 'URL de YouTube'],
        
        // Configuraciones Avanzadas
        ['maintenance_mode', '0', 'boolean', 'Modo mantenimiento activo'],
        ['user_registration_enabled', '1', 'boolean', 'Permitir registro de usuarios'],
        ['reviews_auto_approve', '0', 'boolean', 'Auto-aprobar reseñas'],
        ['inventory_low_stock_alert', '5', 'number', 'Alerta de stock bajo'],
        ['inventory_auto_update', '1', 'boolean', 'Actualización automática de inventario'],
        
        // Configuraciones adicionales importantes
        ['site_address', '123 Gaming Street, Tech City', 'text', 'Dirección física del negocio'],
        ['currency_symbol', '$', 'text', 'Símbolo de la moneda'],
        ['default_language', 'es', 'text', 'Idioma por defecto del sitio'],
        ['timezone', 'America/Bogota', 'text', 'Zona horaria del sitio'],
        ['max_upload_size', '10', 'number', 'Tamaño máximo de archivo en MB'],
        ['items_per_page', '20', 'number', 'Productos por página en listados']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description)");
    
    $added = 0;
    foreach ($additional_settings as $setting) {
        try {
            $stmt->execute($setting);
            echo "     ✓ Configuración: " . $setting[0] . " = " . $setting[1] . PHP_EOL;
            $added++;
        } catch (Exception $e) {
            // Ignorar duplicados
        }
    }
    
    echo PHP_EOL . "✅ Configuraciones agregadas: " . $added . PHP_EOL;
    
    // Mostrar estadísticas finales
    echo PHP_EOL . "=== ESTADÍSTICAS FINALES ===" . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM site_settings");
    $total = $stmt->fetch()['total'];
    echo "📊 Total de configuraciones: " . $total . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as boolean_count FROM site_settings WHERE setting_type = 'boolean'");
    $boolean_count = $stmt->fetch()['boolean_count'];
    echo "⚡ Configuraciones boolean: " . $boolean_count . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as number_count FROM site_settings WHERE setting_type = 'number'");
    $number_count = $stmt->fetch()['number_count'];
    echo "🔢 Configuraciones numéricas: " . $number_count . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as text_count FROM site_settings WHERE setting_type = 'text'");
    $text_count = $stmt->fetch()['text_count'];
    echo "📝 Configuraciones de texto: " . $text_count . PHP_EOL;
    
    echo PHP_EOL . "🎉 Sistema de configuraciones completado!" . PHP_EOL;
    echo "🔧 Accede al panel en: admin/settings.php" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>