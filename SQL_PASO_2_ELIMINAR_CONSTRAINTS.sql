-- =====================================================
-- PASO 2: ELIMINAR FOREIGN KEYS MANUALMENTE
-- Copia y pega CADA línea UNA POR UNA
-- Si alguna da error "no existe", continúa con la siguiente
-- =====================================================

-- Eliminar constraint de order_items si existe
ALTER TABLE `order_items` DROP FOREIGN KEY `fk_order_items_order`;

-- Eliminar constraint de order_items si tiene otro nombre
ALTER TABLE `order_items` DROP FOREIGN KEY `order_items_ibfk_1`;

-- Eliminar constraint de order_status_history si existe
ALTER TABLE `order_status_history` DROP FOREIGN KEY `fk_order_status_order`;

-- Eliminar constraint de order_status_history si tiene otro nombre
ALTER TABLE `order_status_history` DROP FOREIGN KEY `order_status_history_ibfk_1`;

-- Eliminar constraint de order_notes si existe
ALTER TABLE `order_notes` DROP FOREIGN KEY `fk_order_notes_order`;

-- Eliminar constraint de order_notes si tiene otro nombre
ALTER TABLE `order_notes` DROP FOREIGN KEY `order_notes_ibfk_1`;

-- Eliminar constraint de coupon_usage si existe
ALTER TABLE `coupon_usage` DROP FOREIGN KEY `coupon_usage_ibfk_order`;

-- =====================================================
-- Ahora las tablas se pueden eliminar sin problemas
-- =====================================================
