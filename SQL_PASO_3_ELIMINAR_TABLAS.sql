-- =====================================================
-- PASO 3: ELIMINAR TABLAS (después de eliminar constraints)
-- Ejecuta estas líneas UNA POR UNA
-- =====================================================

DROP TABLE IF EXISTS `order_notes`;

DROP TABLE IF EXISTS `order_status_history`;

DROP TABLE IF EXISTS `order_items`;

DROP TABLE IF EXISTS `orders`;

-- Verificar que se eliminaron
SHOW TABLES LIKE 'order%';
