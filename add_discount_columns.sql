-- =================================================================
-- SCRIPT SQL PARA AGREGAR COLUMNAS DE DESCUENTO POR MONEDA
-- Ejecutar en phpMyAdmin o consola MySQL
-- IMPORTANTE: Ejecuta cada bloque por separado si ya existen las columnas
-- =================================================================

-- Agregar columna para descuento en Pesos Argentinos (ARS)
-- NOTA: Si la columna ya existe, este comando dará error - es normal, ignóralo
ALTER TABLE products 
ADD COLUMN discount_percentage_ars DECIMAL(5,2) DEFAULT 0.00 
COMMENT 'Descuento en pesos argentinos (ARS)';

-- Agregar columna para descuento en Dólares (USD)
-- NOTA: Si la columna ya existe, este comando dará error - es normal, ignóralo
ALTER TABLE products 
ADD COLUMN discount_percentage_usd DECIMAL(5,2) DEFAULT 0.00 
COMMENT 'Descuento en dólares (USD)';

-- Migrar datos existentes (si hay columna discount_percentage antigua)
-- Este UPDATE es seguro ejecutar múltiples veces
UPDATE products 
SET discount_percentage_ars = discount_percentage 
WHERE discount_percentage > 0 
AND discount_percentage_ars = 0;
