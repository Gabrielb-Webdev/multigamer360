<?php
require_once 'database.php';

try {
    // Crear tabla de cupones
    $coupon_table_sql = "
    CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
        value DECIMAL(10,2) NOT NULL,
        minimum_amount DECIMAL(10,2) DEFAULT 0,
        maximum_discount DECIMAL(10,2) DEFAULT NULL,
        usage_limit INT DEFAULT NULL,
        used_count INT DEFAULT 0,
        per_user_limit INT DEFAULT 1,
        start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        end_date DATETIME DEFAULT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_code (code),
        INDEX idx_active (is_active),
        INDEX idx_dates (start_date, end_date)
    )";
    
    $pdo->exec($coupon_table_sql);
    echo "✅ Tabla 'coupons' creada exitosamente\n";
    
    // Crear tabla de uso de cupones
    $usage_table_sql = "
    CREATE TABLE IF NOT EXISTS coupon_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id INT NOT NULL,
        user_id INT NOT NULL,
        order_id INT DEFAULT NULL,
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        discount_amount DECIMAL(10,2) NOT NULL,
        
        FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        
        INDEX idx_coupon_user (coupon_id, user_id),
        INDEX idx_user (user_id),
        INDEX idx_order (order_id)
    )";
    
    $pdo->exec($usage_table_sql);
    echo "✅ Tabla 'coupon_usage' creada exitosamente\n";
    
    // Verificar si ya hay cupones
    $check_stmt = $pdo->query("SELECT COUNT(*) FROM coupons");
    $count = $check_stmt->fetchColumn();
    
    if ($count == 0) {
        // Insertar cupones de ejemplo
        $sample_coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Bienvenido 10%',
                'description' => 'Descuento de bienvenida del 10%',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 50.00,
                'maximum_discount' => 20.00,
                'usage_limit' => 100,
                'per_user_limit' => 1,
                'end_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ],
            [
                'code' => 'GAMER25',
                'name' => 'Gamer 25%',
                'description' => 'Descuento especial para gamers',
                'type' => 'percentage',
                'value' => 25.00,
                'minimum_amount' => 100.00,
                'maximum_discount' => 50.00,
                'usage_limit' => 50,
                'per_user_limit' => 1,
                'end_date' => date('Y-m-d H:i:s', strtotime('+60 days'))
            ],
            [
                'code' => 'SAVE50',
                'name' => 'Ahorra $50',
                'description' => 'Descuento fijo de $50',
                'type' => 'fixed',
                'value' => 50.00,
                'minimum_amount' => 200.00,
                'maximum_discount' => null,
                'usage_limit' => 25,
                'per_user_limit' => 1,
                'end_date' => date('Y-m-d H:i:s', strtotime('+45 days'))
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Envío Gratis',
                'description' => 'Descuento en envío',
                'type' => 'fixed',
                'value' => 15.00,
                'minimum_amount' => 0.00,
                'maximum_discount' => null,
                'usage_limit' => null,
                'per_user_limit' => 2,
                'end_date' => date('Y-m-d H:i:s', strtotime('+90 days'))
            ],
            [
                'code' => 'BLACKFRIDAY',
                'name' => 'Black Friday',
                'description' => 'Descuento especial Black Friday',
                'type' => 'percentage',
                'value' => 30.00,
                'minimum_amount' => 75.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 200,
                'per_user_limit' => 1,
                'end_date' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]
        ];
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO coupons (code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, per_user_limit, end_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sample_coupons as $coupon) {
            $insert_stmt->execute([
                $coupon['code'],
                $coupon['name'],
                $coupon['description'],
                $coupon['type'],
                $coupon['value'],
                $coupon['minimum_amount'],
                $coupon['maximum_discount'],
                $coupon['usage_limit'],
                $coupon['per_user_limit'],
                $coupon['end_date']
            ]);
        }
        
        echo "✅ Cupones de ejemplo insertados exitosamente\n";
    } else {
        echo "ℹ️ Los cupones ya existen en la base de datos\n";
    }
    
    echo "\n🎉 Sistema de cupones configurado correctamente!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>