-- =====================================================
-- AGREGAR COLUMNAS FALTANTES A TABLAS ORDERS
-- Solo ejecutar SI las tablas orders ya existen pero les faltan columnas
-- =====================================================

-- IMPORTANTE: Ejecuta estos ALTER TABLE uno por uno
-- Si alguno da error "columna ya existe", simplemente continúa con el siguiente

-- Agregar tracking_number si no existe
ALTER TABLE `orders` ADD COLUMN `tracking_number` VARCHAR(100) NULL AFTER `notes`;

-- Agregar shipped_at si no existe
ALTER TABLE `orders` ADD COLUMN `shipped_at` DATETIME NULL AFTER `tracking_number`;

-- Agregar delivered_at si no existe
ALTER TABLE `orders` ADD COLUMN `delivered_at` DATETIME NULL AFTER `shipped_at`;

-- Agregar completed_at si no existe (aunque ya debería existir)
ALTER TABLE `orders` ADD COLUMN `completed_at` DATETIME NULL AFTER `updated_at`;

-- Agregar índices si no existen
ALTER TABLE `orders` ADD KEY `idx_user_id` (`user_id`);
ALTER TABLE `orders` ADD KEY `idx_status` (`status`);
ALTER TABLE `orders` ADD KEY `idx_payment_status` (`payment_status`);
ALTER TABLE `orders` ADD KEY `idx_created_at` (`created_at`);

-- Para order_items
ALTER TABLE `order_items` ADD KEY `idx_order_id` (`order_id`);
ALTER TABLE `order_items` ADD KEY `idx_product_id` (`product_id`);

-- =====================================================
-- NOTA: Es normal que algunos ALTER TABLE den error
-- "Duplicate column name" o "Duplicate key name"
-- Esto significa que la columna/índice ya existe
-- =====================================================

SELECT 'Proceso completado - revisa los errores si los hay' as resultado;
