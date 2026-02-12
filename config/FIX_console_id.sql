-- =====================================================
-- SOLUCIÓN RÁPIDA: Agregar columna console_id
-- =====================================================
-- Ejecuta este SQL en phpMyAdmin en la pestaña SQL
-- =====================================================

-- Verificar si la columna ya existe
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    COLUMN_KEY
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'u851317150_mg360_db'
    AND TABLE_NAME = 'products'
    AND COLUMN_NAME = 'console_id';

-- Si la consulta anterior devuelve 0 filas, ejecuta lo siguiente:
-- (Si devuelve 1 fila, la columna ya existe y NO ejecutes lo siguiente)

-- PASO 1: Agregar columna console_id
ALTER TABLE products 
ADD COLUMN console_id INT DEFAULT NULL AFTER brand_id;

-- PASO 2: Agregar índice
ALTER TABLE products 
ADD INDEX idx_console (console_id);

-- PASO 3: Agregar foreign key (solo si existe la tabla consoles)
ALTER TABLE products 
ADD CONSTRAINT fk_products_console 
FOREIGN KEY (console_id) REFERENCES consoles(id) 
ON DELETE SET NULL;

-- Verificación final
SHOW COLUMNS FROM products LIKE 'console_id';
