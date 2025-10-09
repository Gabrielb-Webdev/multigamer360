-- Tabla de cupones de descuento
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
);

-- Tabla de uso de cupones por usuario
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    discount_amount DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    
    INDEX idx_coupon_user (coupon_id, user_id),
    INDEX idx_user (user_id),
    INDEX idx_order (order_id)
);

-- Insertar algunos cupones de ejemplo
INSERT INTO coupons (code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, per_user_limit, start_date, end_date) VALUES
('WELCOME10', 'Bienvenido 10%', 'Descuento de bienvenida del 10%', 'percentage', 10.00, 50.00, 20.00, 100, 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('GAMER25', 'Gamer 25%', 'Descuento especial para gamers', 'percentage', 25.00, 100.00, 50.00, 50, 1, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY)),
('SAVE50', 'Ahorra $50', 'Descuento fijo de $50', 'fixed', 50.00, 200.00, NULL, 25, 1, NOW(), DATE_ADD(NOW(), INTERVAL 45 DAY)),
('FREESHIP', 'Envío Gratis', 'Descuento en envío', 'fixed', 15.00, 0.00, NULL, NULL, 2, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY)),
('BLACKFRIDAY', 'Black Friday', 'Descuento especial Black Friday', 'percentage', 30.00, 75.00, 100.00, 200, 1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY));