-- Agregar columnas postal_code y last_login a la tabla users

-- Agregar columna postal_code si no existe (con comentario en línea)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Código postal del usuario para cálculo automático de envío' AFTER `phone`,
ADD INDEX IF NOT EXISTS `idx_postal_code` (`postal_code`);

-- Agregar columna last_login si no existe (con comentario en línea)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL DEFAULT NULL COMMENT 'Fecha y hora del último inicio de sesión exitoso' AFTER `created_at`,
ADD INDEX IF NOT EXISTS `idx_last_login` (`last_login`);
