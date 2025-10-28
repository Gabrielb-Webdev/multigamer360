-- =====================================================
-- PASO 1: Ver qué foreign keys existen
-- Ejecuta este primero para ver qué está bloqueando
-- =====================================================
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_NAME = 'orders'
    AND TABLE_SCHEMA = 'u851317150_mg360_db';
