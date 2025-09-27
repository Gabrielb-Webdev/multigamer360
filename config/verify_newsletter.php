<?php
require_once 'database.php';

echo "=== VERIFICANDO SISTEMA DE EMAIL MARKETING ===" . PHP_EOL;

try {
    // Verificar tabla newsletter_subscribers
    echo "1. Verificando tabla newsletter_subscribers..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE newsletter_subscribers");
        echo "   ✓ Tabla newsletter_subscribers existe" . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = 'active'");
        $active_subscribers = $stmt->fetch()['total'];
        echo "   ✓ Suscriptores activos: " . $active_subscribers . PHP_EOL;
        
    } catch (Exception $e) {
        echo "   ❌ Tabla newsletter_subscribers no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE newsletter_subscribers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) UNIQUE NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active',
            source ENUM('manual', 'website', 'import') DEFAULT 'website',
            subscription_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            unsubscribe_token VARCHAR(255),
            last_email_sent TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla newsletter_subscribers creada" . PHP_EOL;
    }
    
    // Verificar tabla email_campaigns
    echo PHP_EOL . "2. Verificando tabla email_campaigns..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE email_campaigns");
        echo "   ✓ Tabla email_campaigns existe" . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM email_campaigns");
        $total_campaigns = $stmt->fetch()['total'];
        echo "   ✓ Campañas creadas: " . $total_campaigns . PHP_EOL;
        
    } catch (Exception $e) {
        echo "   ❌ Tabla email_campaigns no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE email_campaigns (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            template_id INT,
            content TEXT,
            recipient_type ENUM('all', 'customers', 'subscribers') DEFAULT 'all',
            status ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') DEFAULT 'draft',
            scheduled_at TIMESTAMP NULL,
            sent_at TIMESTAMP NULL,
            total_recipients INT DEFAULT 0,
            sent_count INT DEFAULT 0,
            open_count INT DEFAULT 0,
            click_count INT DEFAULT 0,
            bounce_count INT DEFAULT 0,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla email_campaigns creada" . PHP_EOL;
    }
    
    // Verificar tabla email_templates
    echo PHP_EOL . "3. Verificando tabla email_templates..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE email_templates");
        echo "   ✓ Tabla email_templates existe" . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM email_templates");
        $total_templates = $stmt->fetch()['total'];
        echo "   ✓ Templates disponibles: " . $total_templates . PHP_EOL;
        
        if ($total_templates == 0) {
            echo "   Creando templates de ejemplo..." . PHP_EOL;
            
            $templates = [
                ['Newsletter Bienvenida', 'welcome', '<h1>¡Bienvenido a MultiGamer360!</h1><p>Gracias por suscribirte a nuestro newsletter. Mantente al día con las últimas ofertas en gaming.</p>'],
                ['Newsletter Promocional', 'promotional', '<h1>🎮 ¡Ofertas Especiales en Gaming!</h1><p>No te pierdas nuestras increíbles ofertas en los mejores videojuegos y accesorios.</p>'],
                ['Newsletter Nuevos Productos', 'new_products', '<h1>🆕 Nuevos Productos Disponibles</h1><p>Descubre los últimos lanzamientos que hemos agregado a nuestro catálogo.</p>']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO email_templates (name, slug, content, is_active) VALUES (?, ?, ?, 1)");
            foreach ($templates as $template) {
                $stmt->execute($template);
                echo "     ✓ Template creado: " . $template[0] . PHP_EOL;
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Tabla email_templates no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE email_templates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            variables TEXT COMMENT 'JSON con variables disponibles',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla email_templates creada" . PHP_EOL;
        
        // Crear templates de ejemplo
        $templates = [
            ['Newsletter Bienvenida', 'welcome', '<h1>¡Bienvenido a MultiGamer360!</h1><p>Gracias por suscribirte a nuestro newsletter.</p>'],
            ['Newsletter Promocional', 'promotional', '<h1>🎮 ¡Ofertas Especiales!</h1><p>Ofertas increíbles en gaming.</p>'],
            ['Newsletter Nuevos Productos', 'new_products', '<h1>🆕 Nuevos Productos</h1><p>Últimos lanzamientos disponibles.</p>']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO email_templates (name, slug, content, is_active) VALUES (?, ?, ?, 1)");
        foreach ($templates as $template) {
            $stmt->execute($template);
            echo "     ✓ Template creado: " . $template[0] . PHP_EOL;
        }
    }
    
    // Estadísticas generales
    echo PHP_EOL . "=== ESTADÍSTICAS DE EMAIL MARKETING ===" . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = 'active'");
    $subscribers = $stmt->fetch()['total'];
    echo "📧 Suscriptores activos: " . $subscribers . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM email_campaigns WHERE status = 'sent'");
    $sent_campaigns = $stmt->fetch()['total'];
    echo "📮 Campañas enviadas: " . $sent_campaigns . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM email_templates WHERE is_active = 1");
    $active_templates = $stmt->fetch()['total'];
    echo "📄 Templates activos: " . $active_templates . PHP_EOL;
    
    echo PHP_EOL . "✅ Sistema de email marketing verificado exitosamente!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>