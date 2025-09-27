<?php
require_once 'database.php';

echo "=== VERIFICANDO SISTEMA DE CUPONES ===" . PHP_EOL;

try {
    // Verificar tabla coupons
    echo "1. Verificando tabla coupons..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE coupons");
        echo "   ✓ Tabla coupons existe" . PHP_EOL;
        echo "   Columnas:" . PHP_EOL;
        while ($row = $result->fetch()) {
            echo "     - " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "   ❌ Tabla coupons no existe" . PHP_EOL;
        echo "   Creando tabla..." . PHP_EOL;
        
        $sql = "CREATE TABLE coupons (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            type ENUM('percentage', 'fixed') NOT NULL,
            value DECIMAL(10,2) NOT NULL,
            minimum_amount DECIMAL(10,2) DEFAULT 0,
            maximum_discount DECIMAL(10,2) NULL,
            usage_limit INT NULL,
            used_count INT DEFAULT 0,
            per_user_limit INT DEFAULT 1,
            start_date DATE NOT NULL,
            end_date DATE NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla coupons creada" . PHP_EOL;
    }
    
    // Verificar tabla coupon_usage
    echo PHP_EOL . "2. Verificando tabla coupon_usage..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE coupon_usage");
        echo "   ✓ Tabla coupon_usage existe" . PHP_EOL;
    } catch (Exception $e) {
        echo "   ❌ Tabla coupon_usage no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE coupon_usage (
            id INT PRIMARY KEY AUTO_INCREMENT,
            coupon_id INT NOT NULL,
            user_id INT NOT NULL,
            order_id INT NULL,
            discount_amount DECIMAL(10,2) NOT NULL,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
            UNIQUE KEY unique_usage (coupon_id, user_id, order_id)
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla coupon_usage creada" . PHP_EOL;
    }
    
    // Verificar cupones existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM coupons");
    $total_coupons = $stmt->fetch()['total'];
    echo PHP_EOL . "3. Cupones en el sistema: " . $total_coupons . PHP_EOL;
    
    if ($total_coupons == 0) {
        echo "   Creando cupones de ejemplo..." . PHP_EOL;
        
        $sample_coupons = [
            ['WELCOME10', 'Bienvenida 10%', 'Descuento de bienvenida para nuevos usuarios', 'percentage', 10, 50, 20, 100, 1, '2024-01-01', '2024-12-31'],
            ['GAMING20', 'Gaming 20%', 'Descuento especial en productos gaming', 'percentage', 20, 100, 50, 50, 1, '2024-01-01', '2024-12-31'],
            ['FIXED25', 'Descuento $25', 'Descuento fijo de $25', 'fixed', 25, 150, null, 25, 1, '2024-01-01', '2024-12-31'],
            ['BLACKFRIDAY', 'Black Friday 30%', 'Mega descuento Black Friday', 'percentage', 30, 200, 100, 200, 2, '2024-11-20', '2024-11-30']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO coupons (code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, per_user_limit, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        
        foreach ($sample_coupons as $coupon) {
            $stmt->execute($coupon);
            echo "     ✓ Cupón creado: " . $coupon[0] . PHP_EOL;
        }
    } else {
        echo "   Mostrando cupones existentes:" . PHP_EOL;
        $stmt = $pdo->query("SELECT code, name, type, value, is_active FROM coupons LIMIT 5");
        while ($row = $stmt->fetch()) {
            $status = $row['is_active'] ? '✅' : '❌';
            $type = $row['type'] == 'percentage' ? $row['value'] . '%' : '$' . $row['value'];
            echo "     " . $status . " " . $row['code'] . " - " . $row['name'] . " (" . $type . ")" . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "✅ Sistema de cupones verificado exitosamente!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>