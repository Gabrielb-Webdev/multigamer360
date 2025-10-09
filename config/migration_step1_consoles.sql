-- =====================================================
-- PASO 1: CREAR TABLA CONSOLES
-- =====================================================
-- Ejecuta esto primero
-- =====================================================

CREATE TABLE IF NOT EXISTS consoles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    manufacturer VARCHAR(100),
    icon VARCHAR(50) DEFAULT 'fas fa-gamepad',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar que se creó
SELECT 'Tabla consoles creada exitosamente' AS status;
SHOW TABLES LIKE 'consoles';
