-- Agregar columnas postal_code y last_login a la tabla users

-- Agregar columna postal_code si no existe
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(10) NULL DEFAULT NULL AFTER `phone`,
ADD INDEX IF NOT EXISTS `idx_postal_code` (`postal_code`);

-- Agregar columna last_login si no existe
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL DEFAULT NULL AFTER `created_at`,
ADD INDEX IF NOT EXISTS `idx_last_login` (`last_login`);

-- Comentarios descriptivos
COMMENT ON COLUMN `users`.`postal_code` IS 'Código postal del usuario para cálculo automático de envío';
COMMENT ON COLUMN `users`.`last_login` IS 'Fecha y hora del último inicio de sesión exitoso';
