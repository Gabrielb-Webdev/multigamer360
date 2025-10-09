-- ===============================================
-- ACTUALIZACIÓN DE TABLA PRODUCTS
-- Agregar: etiquetas internas y precios en pesos/USD
-- ===============================================

USE multigamer360;

-- Agregar nuevas columnas a la tabla products
ALTER TABLE products 
ADD COLUMN tags JSON COMMENT 'Etiquetas internas para identificación y clasificación',
ADD COLUMN price_pesos DECIMAL(10, 2) COMMENT 'Precio en pesos argentinos',
ADD COLUMN price_usd DECIMAL(10, 2) COMMENT 'Precio en dólares americanos',
ADD INDEX idx_tags_search ((CAST(tags AS CHAR(255)) COLLATE utf8mb4_bin));

-- Migrar precios actuales a price_pesos (asumiendo que los precios actuales están en pesos)
UPDATE products 
SET price_pesos = price, 
    price_usd = ROUND(price / 1000, 2)  -- Conversión temporal: 1 USD = 1000 ARS (ajustar según tipo de cambio real)
WHERE price_pesos IS NULL;

-- Agregar ejemplos de etiquetas para productos existentes
UPDATE products 
SET tags = JSON_ARRAY('videojuego', 'playstation', 'accion', 'aventura') 
WHERE name LIKE '%Kingdom Hearts%';

UPDATE products 
SET tags = JSON_ARRAY('videojuego', 'nintendo', 'rpg', 'aventura') 
WHERE name LIKE '%Final Fantasy%';

UPDATE products 
SET tags = JSON_ARRAY('videojuego', 'nintendo', 'accion', 'plataformas') 
WHERE name LIKE '%Super Mario%';

UPDATE products 
SET tags = JSON_ARRAY('videojuego', 'nintendo', 'aventura', 'accion') 
WHERE name LIKE '%Zelda%';

UPDATE products 
SET tags = JSON_ARRAY('videojuego', 'playstation', 'accion', 'sigilo') 
WHERE name LIKE '%Metal Gear%';

-- Agregar etiquetas genéricas para productos sin etiquetas específicas
UPDATE products 
SET tags = JSON_ARRAY('videojuego', 'general', 'producto') 
WHERE tags IS NULL;

-- Crear tabla de configuración de monedas (opcional para futuras expansiones)
CREATE TABLE IF NOT EXISTS currency_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL UNIQUE,
    currency_name VARCHAR(50) NOT NULL,
    exchange_rate DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
    is_primary BOOLEAN DEFAULT FALSE,
    symbol VARCHAR(10) NOT NULL,
    symbol_position ENUM('before', 'after') DEFAULT 'before',
    decimal_places INT DEFAULT 2,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_primary (is_primary),
    INDEX idx_active (is_active)
);

-- Insertar monedas por defecto
INSERT INTO currency_settings (currency_code, currency_name, exchange_rate, is_primary, symbol, symbol_position) VALUES
('ARS', 'Peso Argentino', 1.0000, TRUE, '$', 'before'),
('USD', 'Dólar Americano', 0.0010, FALSE, 'USD $', 'before') -- Ajustar según tipo de cambio real
ON DUPLICATE KEY UPDATE
currency_name = VALUES(currency_name),
exchange_rate = VALUES(exchange_rate),
symbol = VALUES(symbol);

-- Verificar cambios
SELECT 
    id, 
    name, 
    price AS price_original,
    price_pesos,
    price_usd,
    tags,
    JSON_EXTRACT(tags, '$[0]') AS primera_etiqueta
FROM products 
LIMIT 5;

SHOW COLUMNS FROM products WHERE Field IN ('tags', 'price_pesos', 'price_usd');