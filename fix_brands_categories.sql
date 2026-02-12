-- =====================================================
-- ARREGLAR BRANDS Y CATEGORIES
-- =====================================================
-- Copia y pega esto en la pestaña SQL de phpMyAdmin
-- =====================================================

SET @dbname = 'u851317150_mg360_db';

-- =====================================================
-- PASO 1: ASEGURAR ESTRUCTURA DE BRANDS
-- =====================================================

CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    logo VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas faltantes si no existen
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'brands' AND COLUMN_NAME = 'logo'),
    'ALTER TABLE brands ADD COLUMN logo VARCHAR(500) NULL',
    'SELECT "logo ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'brands' AND COLUMN_NAME = 'is_active'),
    'ALTER TABLE brands ADD COLUMN is_active TINYINT(1) DEFAULT 1',
    'SELECT "is_active ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'brands' AND COLUMN_NAME = 'display_order'),
    'ALTER TABLE brands ADD COLUMN display_order INT DEFAULT 0',
    'SELECT "display_order ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Insertar marcas populares
INSERT IGNORE INTO brands (id, name, slug) VALUES
(1, 'Sony', 'sony'),
(2, 'Microsoft', 'microsoft'),
(3, 'Nintendo', 'nintendo'),
(4, 'Electronic Arts', 'electronic-arts'),
(5, 'Ubisoft', 'ubisoft'),
(6, 'Activision', 'activision'),
(7, 'Rockstar Games', 'rockstar-games'),
(8, 'Square Enix', 'square-enix'),
(9, 'Capcom', 'capcom'),
(10, 'Bandai Namco', 'bandai-namco'),
(11, 'Konami', 'konami'),
(12, 'SEGA', 'sega'),
(13, 'CD Projekt', 'cd-projekt'),
(14, '2K Games', '2k-games'),
(15, 'Bethesda', 'bethesda'),
(16, 'Epic Games', 'epic-games'),
(17, 'Valve', 'valve'),
(18, 'Naughty Dog', 'naughty-dog'),
(19, 'Insomniac Games', 'insomniac-games'),
(20, 'Santa Monica Studio', 'santa-monica-studio'),
(21, 'FromSoftware', 'fromsoftware'),
(22, 'Kojima Productions', 'kojima-productions'),
(23, 'Blizzard Entertainment', 'blizzard-entertainment'),
(24, 'Riot Games', 'riot-games'),
(25, 'Bungie', 'bungie'),
(100, 'Bayer', 'bayer');

-- =====================================================
-- PASO 2: ASEGURAR ESTRUCTURA DE CATEGORIES
-- =====================================================

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas faltantes si no existen
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'icon'),
    'ALTER TABLE categories ADD COLUMN icon VARCHAR(255) NULL',
    'SELECT "icon ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'is_active'),
    'ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1',
    'SELECT "is_active ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'display_order'),
    'ALTER TABLE categories ADD COLUMN display_order INT DEFAULT 0',
    'SELECT "display_order ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Insertar categorías de productos
INSERT IGNORE INTO categories (id, name, slug) VALUES
(1, 'Videojuegos', 'videojuegos'),
(2, 'Consolas', 'consolas'),
(3, 'Accesorios', 'accesorios'),
(4, 'Controles', 'controles'),
(5, 'Auriculares', 'auriculares'),
(6, 'Cámaras', 'camaras'),
(7, 'Tarjetas de Regalo', 'tarjetas-regalo'),
(8, 'Merchandising', 'merchandising'),
(9, 'Suscripciones', 'suscripciones'),
(10, 'Ediciones Especiales', 'ediciones-especiales'),
(11, 'Preventa', 'preventa'),
(12, 'Usados', 'usados'),
(100, 'Medicina General', 'medicina-general');

-- =====================================================
-- PASO 3: VERIFICACIÓN
-- =====================================================

-- Ver estructura de brands
SELECT 
    'BRANDS COLUMNS' as info,
    COLUMN_NAME as columna,
    DATA_TYPE as tipo
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'brands'
ORDER BY ORDINAL_POSITION;

-- Ver estructura de categories  
SELECT 
    'CATEGORIES COLUMNS' as info,
    COLUMN_NAME as columna,
    DATA_TYPE as tipo
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories'
ORDER BY ORDINAL_POSITION;

-- Ver datos insertados
SELECT 'BRANDS' as tabla, COUNT(*) as total FROM brands
UNION ALL
SELECT 'CATEGORIES' as tabla, COUNT(*) as total FROM categories;

-- Ver primeras marcas
SELECT * FROM brands ORDER BY name LIMIT 10;

-- Ver primeras categorías
SELECT * FROM categories ORDER BY name LIMIT 10;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- ✅ Brands y Categories configuradas correctamente
-- =====================================================
