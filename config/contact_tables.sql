-- Tabla para mensajes de contacto
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    asunto ENUM('consulta_producto', 'soporte_tecnico', 'sugerencia', 'reclamo', 'otro') NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    leido BOOLEAN DEFAULT FALSE,
    respondido BOOLEAN DEFAULT FALSE,
    fecha_respuesta TIMESTAMP NULL,
    notas_admin TEXT,
    INDEX idx_fecha_envio (fecha_envio),
    INDEX idx_email (email),
    INDEX idx_asunto (asunto),
    INDEX idx_leido (leido)
);
