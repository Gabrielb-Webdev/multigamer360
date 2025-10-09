-- =====================================================
-- MIGRACIÓN: SISTEMA DE FILTROS CORRECTO
-- =====================================================
-- Elimina sistema de tags JSON y crea campos específicos
-- para Consola y Género, manteniendo category_id y brand_id
-- =====================================================

USE multigamer360;

-- =====================================================
-- PASO 1: CREAR TABLAS DE REFERENCIA
-- =====================================================

-- Tabla de consolas
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

-- Tabla de géneros
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-tag',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla relacional: productos y géneros (muchos a muchos)
CREATE TABLE IF NOT EXISTS product_genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    genre_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_genre (product_id, genre_id),
    INDEX idx_product (product_id),
    INDEX idx_genre (genre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 2: MODIFICAR TABLA PRODUCTS
-- =====================================================

-- Agregar columna de consola (relación muchos a uno)
ALTER TABLE products 
ADD COLUMN console_id INT DEFAULT NULL AFTER brand_id,
ADD CONSTRAINT fk_products_console FOREIGN KEY (console_id) REFERENCES consoles(id) ON DELETE SET NULL,
ADD INDEX idx_console (console_id);

-- Eliminar columna tags si existe (ya no la necesitamos)
ALTER TABLE products 
DROP COLUMN IF EXISTS tags;

-- =====================================================
-- PASO 3: INSERTAR CONSOLAS INICIALES
-- =====================================================

INSERT INTO consoles (name, slug, manufacturer, icon) VALUES
-- Nintendo
('Nintendo Entertainment System (NES)', 'nes', 'Nintendo', 'fas fa-gamepad'),
('Super Nintendo (SNES)', 'snes', 'Nintendo', 'fas fa-gamepad'),
('Nintendo 64', 'n64', 'Nintendo', 'fas fa-gamepad'),
('GameCube', 'gamecube', 'Nintendo', 'fas fa-gamepad'),
('Nintendo Wii', 'wii', 'Nintendo', 'fas fa-gamepad'),
('Nintendo Wii U', 'wii-u', 'Nintendo', 'fas fa-gamepad'),
('Nintendo Switch', 'switch', 'Nintendo', 'fas fa-gamepad'),
('Game Boy', 'gameboy', 'Nintendo', 'fas fa-gamepad'),
('Game Boy Color', 'gbc', 'Nintendo', 'fas fa-gamepad'),
('Game Boy Advance', 'gba', 'Nintendo', 'fas fa-gamepad'),
('Nintendo DS', 'nds', 'Nintendo', 'fas fa-gamepad'),
('Nintendo 3DS', '3ds', 'Nintendo', 'fas fa-gamepad'),

-- Sony PlayStation
('PlayStation', 'ps1', 'Sony', 'fas fa-gamepad'),
('PlayStation 2', 'ps2', 'Sony', 'fas fa-gamepad'),
('PlayStation 3', 'ps3', 'Sony', 'fas fa-gamepad'),
('PlayStation 4', 'ps4', 'Sony', 'fas fa-gamepad'),
('PlayStation 5', 'ps5', 'Sony', 'fas fa-gamepad'),
('PlayStation Portable (PSP)', 'psp', 'Sony', 'fas fa-gamepad'),
('PlayStation Vita', 'ps-vita', 'Sony', 'fas fa-gamepad'),

-- Sega
('Sega Genesis / Mega Drive', 'genesis', 'Sega', 'fas fa-gamepad'),
('Sega Saturn', 'saturn', 'Sega', 'fas fa-gamepad'),
('Sega Dreamcast', 'dreamcast', 'Sega', 'fas fa-gamepad'),
('Sega Game Gear', 'game-gear', 'Sega', 'fas fa-gamepad'),

-- Microsoft Xbox
('Xbox', 'xbox', 'Microsoft', 'fas fa-gamepad'),
('Xbox 360', 'xbox-360', 'Microsoft', 'fas fa-gamepad'),
('Xbox One', 'xbox-one', 'Microsoft', 'fas fa-gamepad'),
('Xbox Series X/S', 'xbox-series', 'Microsoft', 'fas fa-gamepad'),

-- Otras
('Atari 2600', 'atari-2600', 'Atari', 'fas fa-gamepad'),
('PC', 'pc', 'Múltiples', 'fas fa-desktop'),
('Retro / Clásicas', 'retro', 'Múltiples', 'fas fa-ghost')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =====================================================
-- PASO 4: INSERTAR GÉNEROS INICIALES
-- =====================================================

INSERT INTO genres (name, slug, description, icon) VALUES
('Acción', 'accion', 'Juegos de acción y combate', 'fas fa-fist-raised'),
('Aventura', 'aventura', 'Juegos de aventura y exploración', 'fas fa-compass'),
('RPG', 'rpg', 'Juegos de rol (Role-Playing Game)', 'fas fa-dragon'),
('Estrategia', 'estrategia', 'Juegos de estrategia y planificación', 'fas fa-chess'),
('Simulación', 'simulacion', 'Juegos de simulación', 'fas fa-plane'),
('Deportes', 'deportes', 'Juegos deportivos', 'fas fa-futbol'),
('Carreras', 'carreras', 'Juegos de carreras', 'fas fa-flag-checkered'),
('Plataformas', 'plataformas', 'Juegos de plataformas', 'fas fa-running'),
('Shooter', 'shooter', 'Juegos de disparos (FPS/TPS)', 'fas fa-crosshairs'),
('Peleas', 'peleas', 'Juegos de pelea', 'fas fa-hand-rock'),
('Puzzle', 'puzzle', 'Juegos de rompecabezas', 'fas fa-puzzle-piece'),
('Musical', 'musical', 'Juegos musicales y ritmo', 'fas fa-music'),
('Terror', 'terror', 'Juegos de terror y supervivencia', 'fas fa-ghost'),
('Sigilo', 'sigilo', 'Juegos de sigilo e infiltración', 'fas fa-user-secret'),
('MMORPG', 'mmorpg', 'Juegos de rol multijugador masivo', 'fas fa-users'),
('Battle Royale', 'battle-royale', 'Juegos tipo Battle Royale', 'fas fa-trophy'),
('Sandbox', 'sandbox', 'Juegos de mundo abierto', 'fas fa-cube'),
('Party', 'party', 'Juegos de fiesta y minijuegos', 'fas fa-birthday-cake'),
('Educativo', 'educativo', 'Juegos educativos', 'fas fa-graduation-cap'),
('Indie', 'indie', 'Juegos independientes', 'fas fa-lightbulb')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =====================================================
-- PASO 5: VERIFICACIÓN DE ESTRUCTURA
-- =====================================================

-- Ver estructura de products
SHOW COLUMNS FROM products;

-- Ver consolas insertadas
SELECT COUNT(*) as total_consoles FROM consoles;

-- Ver géneros insertados
SELECT COUNT(*) as total_genres FROM genres;

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- 1. Ahora products tiene: category_id, brand_id, console_id
-- 2. Los géneros se asignan mediante tabla product_genres (relación N:N)
-- 3. Un producto puede tener MÚLTIPLES géneros (ej: "Acción/Aventura")
-- 4. La columna tags fue eliminada
-- 5. Todas las tablas tienen índices para búsquedas rápidas
-- =====================================================

SELECT '✅ Migración completada. Ahora debes actualizar tus productos con console_id y géneros.' AS mensaje;
