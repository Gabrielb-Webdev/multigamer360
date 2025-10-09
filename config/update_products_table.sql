-- Actualización de la tabla products para agregar stock y clasificación
-- Ejecutar este script SQL para actualizar la base de datos

-- Agregar columnas nuevas a la tabla products
ALTER TABLE products 
ADD COLUMN stock INT DEFAULT 0 COMMENT 'Cantidad disponible en inventario',
ADD COLUMN is_featured TINYINT(1) DEFAULT 0 COMMENT '1 = producto destacado, 0 = normal',
ADD COLUMN is_new TINYINT(1) DEFAULT 0 COMMENT '1 = novedad, 0 = normal',
ADD COLUMN low_stock_threshold INT DEFAULT 5 COMMENT 'Umbral de stock bajo',
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización';

-- Actualizar productos existentes con stock inicial
UPDATE products SET 
    stock = FLOOR(RAND() * 50) + 10,  -- Stock aleatorio entre 10 y 59
    is_featured = CASE 
        WHEN RAND() < 0.3 THEN 1  -- 30% serán destacados
        ELSE 0 
    END,
    is_new = CASE 
        WHEN RAND() < 0.2 THEN 1  -- 20% serán novedades
        ELSE 0 
    END,
    low_stock_threshold = 5
WHERE id > 0;

-- Asegurar que algunos productos específicos sean destacados y novedades
UPDATE products SET is_featured = 1 WHERE id IN (1, 2, 7, 8, 12);
UPDATE products SET is_new = 1 WHERE id IN (3, 4, 5, 9, 10);

-- Crear índices para mejorar rendimiento
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_products_new ON products(is_new);
CREATE INDEX idx_products_stock ON products(stock);
CREATE INDEX idx_products_active_stock ON products(is_active, stock);

-- Crear tabla para auditoría de stock (opcional, para tracking)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_stock_movements_product (product_id),
    INDEX idx_stock_movements_date (created_at)
);

-- Función para verificar stock antes de agregar al carrito (trigger)
DELIMITER //
CREATE TRIGGER before_cart_insert_stock_check
BEFORE INSERT ON cart_sessions
FOR EACH ROW
BEGIN
    DECLARE available_stock INT;
    SELECT stock INTO available_stock FROM products WHERE id = NEW.product_id AND is_active = 1;
    
    IF available_stock IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no encontrado o inactivo';
    END IF;
    
    IF NEW.quantity > available_stock THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente';
    END IF;
END//

CREATE TRIGGER before_cart_update_stock_check
BEFORE UPDATE ON cart_sessions
FOR EACH ROW
BEGIN
    DECLARE available_stock INT;
    SELECT stock INTO available_stock FROM products WHERE id = NEW.product_id AND is_active = 1;
    
    IF available_stock IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no encontrado o inactivo';
    END IF;
    
    IF NEW.quantity > available_stock THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente';
    END IF;
END//
DELIMITER ;

-- Verificar la estructura actualizada
DESCRIBE products;