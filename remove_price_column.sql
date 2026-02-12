-- =====================================================
-- ELIMINAR COLUMNA PRICE Y USAR SOLO PRICE_PESOS Y PRICE_DOLLARS
-- =====================================================

-- Eliminar columna price si existe
SET @dbname = 'u851317150_mg360_db';

SET @query = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price'),
    'ALTER TABLE products DROP COLUMN price',
    'SELECT "price no existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verificar columnas restantes
SELECT 
    'COLUMNAS DE PRECIO' as info,
    COLUMN_NAME as columna,
    DATA_TYPE as tipo,
    COLUMN_DEFAULT as valor_default
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'products'
  AND COLUMN_NAME IN ('price_pesos', 'price_dollars', 'price_usd', 'price')
ORDER BY COLUMN_NAME;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
