-- ============================================================
-- COPIAR Y PEGAR CADA COMANDO POR SEPARADO EN PHPMYADMIN
-- ============================================================

-- 1️⃣ Ejecuta este comando primero:
ALTER TABLE products ADD COLUMN discount_percentage_ars DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Descuento en pesos argentinos (ARS)';

-- 2️⃣ Luego ejecuta este:
ALTER TABLE products ADD COLUMN discount_percentage_usd DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Descuento en dólares (USD)';

-- 3️⃣ Finalmente ejecuta este (migra datos antiguos):
UPDATE products SET discount_percentage_ars = discount_percentage WHERE discount_percentage > 0;

-- ============================================================
-- Si recibes error "Duplicate column name", significa que
-- la columna YA EXISTE - eso está bien, ignóralo y continúa
-- ============================================================
