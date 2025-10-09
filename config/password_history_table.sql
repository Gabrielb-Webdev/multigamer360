-- Tabla para almacenar el historial de contraseñas
CREATE TABLE IF NOT EXISTS password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Insertar las contraseñas actuales de todos los usuarios al historial
-- (solo ejecutar una vez para inicializar)
INSERT INTO password_history (user_id, password_hash, created_at)
SELECT id, password, updated_at 
FROM users 
WHERE id NOT IN (SELECT DISTINCT user_id FROM password_history);