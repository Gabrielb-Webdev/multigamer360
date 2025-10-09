-- Script para actualizar la tabla users eliminando username y haciendo phone único
-- Ejecutar en XAMPP/phpMyAdmin o terminal MySQL

-- 1. Primero eliminar el índice de username
DROP INDEX idx_username ON users;

-- 2. Eliminar la columna username
ALTER TABLE users DROP COLUMN username;

-- 3. Hacer el campo phone único (pero permitir NULL)
ALTER TABLE users 
MODIFY COLUMN phone VARCHAR(20) UNIQUE;

-- 4. Crear índice para phone
CREATE INDEX idx_phone ON users(phone);

-- Verificar la estructura actualizada
-- DESCRIBE users;