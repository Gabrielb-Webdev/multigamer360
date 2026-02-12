-- =====================================================
-- SCRIPT COMPLETO DE BASE DE DATOS - MULTIGAMER360
-- =====================================================
-- Ejecuta este archivo completo en phpMyAdmin > Importar
-- O copia todo y pégalo en la pestaña SQL
-- =====================================================

-- =====================================================
-- PASO 1: LIMPIEZA DE COLUMNAS OBSOLETAS EN PRODUCTS
-- =====================================================

-- Eliminar columnas antiguas de precio si existen
SET @dbname = 'u851317150_mg360_db';

-- Eliminar price_pesos
SET @query = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_pesos'),
    'ALTER TABLE products DROP COLUMN price_pesos',
    'SELECT "price_pesos no existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Eliminar price_dollars
SET @query = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_dollars'),
    'ALTER TABLE products DROP COLUMN price_dollars',
    'SELECT "price_dollars no existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Eliminar price_usd
SET @query = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_usd'),
    'ALTER TABLE products DROP COLUMN price_usd',
    'SELECT "price_usd no existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Eliminar birth_date de users si existe
SET @query = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'birth_date'),
    'ALTER TABLE users DROP COLUMN birth_date',
    'SELECT "birth_date no existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Eliminar last_login de users si existe
SET @query = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login'),
    'ALTER TABLE users DROP COLUMN last_login',
    'SELECT "last_login no existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 2: AGREGAR COLUMNAS NECESARIAS EN PRODUCTS
-- =====================================================

-- Agregar price si no existe
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price'),
    'ALTER TABLE products ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'SELECT "price ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

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
-- PASO 5: CREAR TABLA PRODUCT_GENRES (Relación N:N)
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
-- PASO 6: AGREGAR ÍNDICES IMPORTANTES EN PRODUCTS
-- =====================================================

-- Índice para console_id
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_console'),
    'ALTER TABLE products ADD INDEX idx_console (console_id)',
    'SELECT "idx_console ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para sku
SET @query = (SELECT IF(
    NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_sku'),
    'ALTER TABLE products ADD INDEX idx_sku (sku)',
    'SELECT "idx_sku ya existe" as msg'
));
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 7: AGREGAR FOREIGN KEY CONSOLE_ID
-- =====================================================

-- Primero verificar que no exista el constraint
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
-- PASO 8: GENERAR SKUs AUTOMÁTICOS
-- =====================================================

-- Generar SKUs para productos que no tienen
UPDATE products 
SET sku = CONCAT('MG360-', LPAD(id, 6, '0'))
WHERE sku IS NULL OR sku = '';

-- =====================================================
-- PASO 9: VERIFICACIÓN FINAL
-- =====================================================

-- Ver columnas de products
SELECT 
    'PRODUCTS COLUMNS' as tabla,
    COLUMN_NAME as columna, 
    DATA_TYPE as tipo,
    IS_NULLABLE as nullable
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'products'
  AND COLUMN_NAME IN ('id', 'name', 'price', 'sku', 'console_id', 'main_image', 'is_featured', 'is_new', 'is_on_sale', 'discount_percentage', 'is_active')
ORDER BY ORDINAL_POSITION;

-- Ver tablas creadas
SELECT 
    'TABLAS' as info,
    TABLE_NAME as nombre
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = @dbname
  AND TABLE_NAME IN ('products', 'consoles', 'genres', 'product_genres')
ORDER BY TABLE_NAME;

-- Ver cantidad de registros
SELECT 'PRODUCTS' as tabla, COUNT(*) as total FROM products
UNION ALL
SELECT 'CONSOLES' as tabla, COUNT(*) as total FROM consoles
UNION ALL
SELECT 'GENRES' as tabla, COUNT(*) as total FROM genres;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- ✅ Base de datos configurada correctamente
-- ✅ Columnas obsoletas eliminadas
-- ✅ Nuevas columnas agregadas
-- ✅ Tablas relacionadas creadas
-- ✅ Datos básicos insertados
-- ✅ Índices y foreign keys configurados
-- =====================================================
