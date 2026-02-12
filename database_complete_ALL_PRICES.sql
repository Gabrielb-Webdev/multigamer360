-- =====================================================
-- SCRIPT COMPLETO CON PRICE_PESOS Y PRICE_USD
-- =====================================================
-- Este script agrega TODAS las columnas necesarias
-- incluyendo price_pesos y price_usd (NO elimina nada)
-- =====================================================

SET @dbname = 'u851317150_mg360_db';

-- =====================================================
-- PASO 1: AGREGAR COLUMNAS DE PRECIO
-- =====================================================

-- Agregar price_pesos si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_pesos'),
    'ALTER TABLE products ADD COLUMN price_pesos DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'SELECT "price_pesos ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar price_dollars si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_dollars'),
    'ALTER TABLE products ADD COLUMN price_dollars DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'SELECT "price_dollars ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar price_usd si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_usd'),
    'ALTER TABLE products ADD COLUMN price_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'SELECT "price_usd ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar price genérico si no existe (por compatibilidad)
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price'),
    'ALTER TABLE products ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'SELECT "price ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 2: AGREGAR COLUMNAS ADICIONALES DE PRODUCTS
-- =====================================================

-- Agregar console_id si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'console_id'),
    'ALTER TABLE products ADD COLUMN console_id INT NULL',
    'SELECT "console_id ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar sku si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'sku'),
    'ALTER TABLE products ADD COLUMN sku VARCHAR(100) NULL UNIQUE',
    'SELECT "sku ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar slug si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'slug'),
    'ALTER TABLE products ADD COLUMN slug VARCHAR(255) NULL UNIQUE',
    'SELECT "slug ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar main_image si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'main_image'),
    'ALTER TABLE products ADD COLUMN main_image VARCHAR(500) NULL',
    'SELECT "main_image ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar is_featured si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_featured'),
    'ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0',
    'SELECT "is_featured ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar is_new si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_new'),
    'ALTER TABLE products ADD COLUMN is_new TINYINT(1) DEFAULT 0',
    'SELECT "is_new ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar product_type si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_type'),
    "ALTER TABLE products ADD COLUMN product_type ENUM('physical', 'digital', 'service') DEFAULT 'physical'",
    'SELECT "product_type ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar meta_title si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_title'),
    'ALTER TABLE products ADD COLUMN meta_title VARCHAR(255) NULL',
    'SELECT "meta_title ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar meta_description si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_description'),
    'ALTER TABLE products ADD COLUMN meta_description TEXT NULL',
    'SELECT "meta_description ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar meta_keywords si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_keywords'),
    'ALTER TABLE products ADD COLUMN meta_keywords VARCHAR(500) NULL',
    'SELECT "meta_keywords ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar is_on_sale si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_on_sale'),
    'ALTER TABLE products ADD COLUMN is_on_sale TINYINT(1) DEFAULT 0',
    'SELECT "is_on_sale ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar discount_percentage si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'discount_percentage'),
    'ALTER TABLE products ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00',
    'SELECT "discount_percentage ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar is_active si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_active'),
    'ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1',
    'SELECT "is_active ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar min_stock_level si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'min_stock_level'),
    'ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 5',
    'SELECT "min_stock_level ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar status si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'status'),
    "ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive', 'draft') DEFAULT 'active'",
    'SELECT "status ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar created_at si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'created_at'),
    'ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "created_at ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar updated_at si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'updated_at'),
    'ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 3: CREAR TABLA CONSOLES
-- =====================================================

CREATE TABLE IF NOT EXISTS consoles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    manufacturer VARCHAR(100) NULL,
    icon VARCHAR(255) NULL,
    description TEXT NULL,
    release_year INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar consolas básicas
INSERT IGNORE INTO consoles (id, name, slug, manufacturer) VALUES
(1, 'PlayStation 5', 'playstation-5', 'Sony'),
(2, 'PlayStation 4', 'playstation-4', 'Sony'),
(3, 'Xbox Series X', 'xbox-series-x', 'Microsoft'),
(4, 'Xbox Series S', 'xbox-series-s', 'Microsoft'),
(5, 'Xbox One', 'xbox-one', 'Microsoft'),
(6, 'Nintendo Switch', 'nintendo-switch', 'Nintendo'),
(7, 'Nintendo Switch OLED', 'nintendo-switch-oled', 'Nintendo'),
(8, 'PC', 'pc', 'Varios'),
(9, 'Multiplataforma', 'multiplataforma', 'Varios'),
(29, 'Nintendo 64', 'nintendo-64', 'Nintendo');

-- =====================================================
-- PASO 4: CREAR TABLA GENRES
-- =====================================================

CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar géneros básicos
INSERT IGNORE INTO genres (name, slug) VALUES
('Acción', 'accion'),
('Aventura', 'aventura'),
('RPG', 'rpg'),
('Deportes', 'deportes'),
('Carreras', 'carreras'),
('Shooter', 'shooter'),
('Estrategia', 'estrategia'),
('Simulación', 'simulacion'),
('Puzzle', 'puzzle'),
('Plataformas', 'plataformas'),
('Fighting', 'fighting'),
('Horror', 'horror'),
('Música', 'musica'),
('Party', 'party'),
('Educativo', 'educativo');

-- =====================================================
-- PASO 5: CREAR TABLA PRODUCT_GENRES
-- =====================================================

CREATE TABLE IF NOT EXISTS product_genres (
    product_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (product_id, genre_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_genre (genre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 6: CREAR/ARREGLAR TABLA BRANDS
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

-- Agregar columnas faltantes en brands
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
(100, 'Bayer', 'bayer');

-- =====================================================
-- PASO 7: CREAR/ARREGLAR TABLA CATEGORIES
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

-- Agregar columnas faltantes en categories
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

-- Insertar categorías
INSERT IGNORE INTO categories (id, name, slug) VALUES
(1, 'Videojuegos', 'videojuegos'),
(2, 'Consolas', 'consolas'),
(3, 'Accesorios', 'accesorios'),
(100, 'Medicina General', 'medicina-general');

-- =====================================================
-- PASO 8: AGREGAR ÍNDICES
-- =====================================================

-- Índice para console_id
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_console'),
    'ALTER TABLE products ADD INDEX idx_console (console_id)',
    'SELECT "idx_console ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 9: FOREIGN KEY
-- =====================================================

SET @query = (SELECT IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = @dbname 
        AND TABLE_NAME = 'products' 
        AND CONSTRAINT_NAME = 'fk_products_console'
    ),
    'ALTER TABLE products ADD CONSTRAINT fk_products_console FOREIGN KEY (console_id) REFERENCES consoles(id) ON DELETE SET NULL',
    'SELECT "fk_products_console ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 10: GENERAR SKUs
-- =====================================================

UPDATE products 
SET sku = CONCAT('MG360-', LPAD(id, 6, '0'))
WHERE sku IS NULL OR sku = '';

-- =====================================================
-- PASO 11: VERIFICACIÓN FINAL
-- =====================================================

-- Ver todas las columnas de products
SELECT 
    'PRODUCTS' as tabla,
    COLUMN_NAME as columna,
    DATA_TYPE as tipo,
    COLUMN_DEFAULT as valor_default
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products'
ORDER BY ORDINAL_POSITION;

-- Ver datos de ejemplo
SELECT 
    id, 
    name, 
    price_pesos, 
    price_dollars, 
    price_usd,
    price,
    stock_quantity,
    sku,
    console_id,
    is_active
FROM products 
LIMIT 5;

-- Ver totales
SELECT 'PRODUCTS' as tabla, COUNT(*) as total FROM products
UNION ALL
SELECT 'BRANDS' as tabla, COUNT(*) as total FROM brands
UNION ALL
SELECT 'CATEGORIES' as tabla, COUNT(*) as total FROM categories
UNION ALL
SELECT 'CONSOLES' as tabla, COUNT(*) as total FROM consoles
UNION ALL
SELECT 'GENRES' as tabla, COUNT(*) as total FROM genres;

-- =====================================================
-- FIN - BASE DE DATOS COMPLETA
-- =====================================================
