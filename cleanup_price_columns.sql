-- =====================================================
-- LIMPIEZA FINAL DE COLUMNAS DE PRECIO
-- =====================================================
-- Ejecuta esto en phpMyAdmin pestaña SQL
-- =====================================================

-- Verificar columnas actuales
SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'u851317150_mg360_db' 
  AND TABLE_NAME = 'products' 
  AND COLUMN_NAME IN ('price', 'price_pesos', 'price_dollars', 'price_usd');

-- Si existe price_pesos pero NO existe price, renombrar
-- Si existen ambas, eliminar price_pesos

-- OPCIÓN 1: Si price NO existe, renombrar price_pesos a price
-- Descomenta si es necesario:
-- ALTER TABLE products CHANGE COLUMN price_pesos price DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- OPCIÓN 2: Si price YA existe, eliminar price_pesos y price_dollars
-- Ejecuta estos uno por uno:

-- Primero verifica que price tiene datos
SELECT COUNT(*) as productos_con_price FROM products WHERE price > 0;

-- Si hay productos con price > 0, es seguro eliminar las antiguas
-- Eliminar columna price_pesos si existe
SET @query = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = 'u851317150_mg360_db'
        AND TABLE_NAME = 'products'
        AND COLUMN_NAME = 'price_pesos'
    ),
    'ALTER TABLE products DROP COLUMN price_pesos',
    'SELECT "Columna price_pesos no existe" as mensaje'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar columna price_dollars si existe
SET @query = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = 'u851317150_mg360_db'
        AND TABLE_NAME = 'products'
        AND COLUMN_NAME = 'price_dollars'
    ),
    'ALTER TABLE products DROP COLUMN price_dollars',
    'SELECT "Columna price_dollars no existe" as mensaje'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar columna price_usd si existe
SET @query = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = 'u851317150_mg360_db'
        AND TABLE_NAME = 'products'
        AND COLUMN_NAME = 'price_usd'
    ),
    'ALTER TABLE products DROP COLUMN price_usd',
    'SELECT "Columna price_usd no existe" as mensaje'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificación final
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'u851317150_mg360_db' 
  AND TABLE_NAME = 'products' 
  AND COLUMN_NAME LIKE '%price%';

-- Mostrar primeros 3 productos para verificar
SELECT id, name, price, stock_quantity 
FROM products 
LIMIT 3;
