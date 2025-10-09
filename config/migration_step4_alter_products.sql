-- =====================================================
-- PASO 4: MODIFICAR TABLA PRODUCTS
-- =====================================================
-- Ejecuta esto después del paso 3
-- IMPORTANTE: Esto agrega la columna console_id
-- =====================================================

-- Agregar columna console_id
ALTER TABLE products 
ADD COLUMN console_id INT DEFAULT NULL AFTER brand_id;

-- Agregar índice
ALTER TABLE products 
ADD INDEX idx_console (console_id);

-- Agregar foreign key
ALTER TABLE products 
ADD CONSTRAINT fk_products_console 
FOREIGN KEY (console_id) REFERENCES consoles(id) ON DELETE SET NULL;

-- Verificar que se agregó
SELECT 'Columna console_id agregada exitosamente' AS status;
SHOW COLUMNS FROM products LIKE 'console_id';
