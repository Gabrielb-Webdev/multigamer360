<?php
require_once 'database.php';

try {
    // Tabla de suscriptores al newsletter
    $newsletter_table_sql = "
    CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active',
        source ENUM('website', 'checkout', 'manual', 'import') DEFAULT 'website',
        user_id INT DEFAULT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        unsubscribed_at TIMESTAMP NULL,
        last_email_sent TIMESTAMP NULL,
        email_count INT DEFAULT 0,
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_subscribed (subscribed_at)
    )";
    
    $pdo->exec($newsletter_table_sql);
    echo "✅ Tabla 'newsletter_subscribers' creada exitosamente\n";
    
    // Tabla de plantillas de email
    $email_templates_table_sql = "
    CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        template_type ENUM('newsletter', 'promotional', 'welcome', 'abandoned_cart', 'order_confirmation') NOT NULL,
        html_content TEXT NOT NULL,
        text_content TEXT,
        variables JSON,
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_type (template_type),
        INDEX idx_active (is_active)
    )";
    
    $pdo->exec($email_templates_table_sql);
    echo "✅ Tabla 'email_templates' creada exitosamente\n";
    
    // Tabla de campañas de email
    $email_campaigns_table_sql = "
    CREATE TABLE IF NOT EXISTS email_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        template_id INT,
        recipient_type ENUM('all_subscribers', 'active_users', 'customers', 'custom_list') NOT NULL,
        recipient_filter JSON,
        status ENUM('draft', 'scheduled', 'sending', 'sent', 'paused', 'completed') DEFAULT 'draft',
        scheduled_at TIMESTAMP NULL,
        sent_at TIMESTAMP NULL,
        total_recipients INT DEFAULT 0,
        emails_sent INT DEFAULT 0,
        emails_delivered INT DEFAULT 0,
        emails_opened INT DEFAULT 0,
        emails_clicked INT DEFAULT 0,
        emails_bounced INT DEFAULT 0,
        emails_unsubscribed INT DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_scheduled (scheduled_at)
    )";
    
    $pdo->exec($email_campaigns_table_sql);
    echo "✅ Tabla 'email_campaigns' creada exitosamente\n";
    
    // Tabla de logs de emails enviados
    $email_logs_table_sql = "
    CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT DEFAULT NULL,
        template_id INT DEFAULT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        recipient_id INT DEFAULT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed') NOT NULL,
        tracking_id VARCHAR(100) UNIQUE,
        user_agent TEXT,
        ip_address VARCHAR(45),
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_campaign (campaign_id),
        INDEX idx_recipient (recipient_email),
        INDEX idx_status (status),
        INDEX idx_tracking (tracking_id)
    )";
    
    $pdo->exec($email_logs_table_sql);
    echo "✅ Tabla 'email_logs' creada exitosamente\n";
    
    // Verificar si ya hay plantillas
    $check_stmt = $pdo->query("SELECT COUNT(*) FROM email_templates");
    $count = $check_stmt->fetchColumn();
    
    if ($count == 0) {
        // Insertar plantillas de ejemplo
        $sample_templates = [
            [
                'name' => 'Newsletter Semanal',
                'subject' => '🎮 Las mejores ofertas de la semana en MultiGamer360',
                'template_type' => 'newsletter',
                'html_content' => '
                    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
                        <header style="background: #1a1a1a; color: white; padding: 20px; text-align: center;">
                            <h1>MultiGamer360</h1>
                            <p>Las mejores ofertas en videojuegos</p>
                        </header>
                        <main style="padding: 20px;">
                            <h2>🎮 Ofertas de la Semana</h2>
                            <p>Hola {{first_name}},</p>
                            <p>No te pierdas estas increíbles ofertas en videojuegos retro:</p>
                            {{featured_products}}
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{shop_url}}" style="background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Ver Todas las Ofertas</a>
                            </div>
                        </main>
                        <footer style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px;">
                            <p>MultiGamer360 - Tu tienda de videojuegos de confianza</p>
                            <p><a href="{{unsubscribe_url}}">Cancelar suscripción</a></p>
                        </footer>
                    </div>
                ',
                'variables' => json_encode(['first_name', 'featured_products', 'shop_url', 'unsubscribe_url'])
            ],
            [
                'name' => 'Bienvenida Nuevo Suscriptor',
                'subject' => '¡Bienvenido a MultiGamer360! 🎮',
                'template_type' => 'welcome',
                'html_content' => '
                    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
                        <header style="background: #1a1a1a; color: white; padding: 20px; text-align: center;">
                            <h1>¡Bienvenido a MultiGamer360!</h1>
                        </header>
                        <main style="padding: 20px;">
                            <h2>¡Hola {{first_name}}!</h2>
                            <p>¡Gracias por suscribirte a nuestro newsletter! Ahora recibirás:</p>
                            <ul>
                                <li>🎮 Las mejores ofertas en videojuegos</li>
                                <li>🆕 Novedades y lanzamientos</li>
                                <li>🏆 Ofertas exclusivas para suscriptores</li>
                                <li>📰 Noticias del mundo gaming</li>
                            </ul>
                            <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                                <h3>🎁 Regalo de Bienvenida</h3>
                                <p>Usa el código <strong>WELCOME10</strong> y obtén 10% de descuento en tu primera compra.</p>
                            </div>
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{shop_url}}" style="background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Explorar Tienda</a>
                            </div>
                        </main>
                        <footer style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px;">
                            <p>MultiGamer360 - Tu tienda de videojuegos de confianza</p>
                            <p><a href="{{unsubscribe_url}}">Cancelar suscripción</a></p>
                        </footer>
                    </div>
                ',
                'variables' => json_encode(['first_name', 'shop_url', 'unsubscribe_url'])
            ],
            [
                'name' => 'Carrito Abandonado',
                'subject' => '¿Olvidaste algo? 🛒 Completa tu compra',
                'template_type' => 'abandoned_cart',
                'html_content' => '
                    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
                        <header style="background: #1a1a1a; color: white; padding: 20px; text-align: center;">
                            <h1>¿Olvidaste algo?</h1>
                        </header>
                        <main style="padding: 20px;">
                            <h2>Hola {{first_name}},</h2>
                            <p>Notamos que dejaste algunos videojuegos geniales en tu carrito. ¡No dejes que se escapen!</p>
                            
                            <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin: 20px 0;">
                                <h3>Productos en tu carrito:</h3>
                                {{cart_items}}
                                <hr>
                                <p style="font-size: 18px; font-weight: bold;">Total: ${{cart_total}}</p>
                            </div>
                            
                            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                                <p><strong>💨 ¡Acción limitada!</strong> Estos productos están en alta demanda.</p>
                            </div>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{checkout_url}}" style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Completar Compra</a>
                            </div>
                        </main>
                        <footer style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px;">
                            <p>MultiGamer360 - Tu tienda de videojuegos de confianza</p>
                            <p><a href="{{unsubscribe_url}}">Cancelar suscripción</a></p>
                        </footer>
                    </div>
                ',
                'variables' => json_encode(['first_name', 'cart_items', 'cart_total', 'checkout_url', 'unsubscribe_url'])
            ]
        ];
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO email_templates (name, subject, template_type, html_content, variables) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($sample_templates as $template) {
            $insert_stmt->execute([
                $template['name'],
                $template['subject'],
                $template['template_type'],
                $template['html_content'],
                $template['variables']
            ]);
        }
        
        echo "✅ Plantillas de email de ejemplo insertadas exitosamente\n";
    } else {
        echo "ℹ️ Las plantillas de email ya existen en la base de datos\n";
    }
    
    echo "\n🎉 Sistema de email marketing configurado correctamente!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>