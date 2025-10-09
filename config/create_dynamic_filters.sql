-- ===============================================
-- SISTEMA DE ETIQUETAS CATEGORIZADAS
-- Filtros dinámicos basados en etiquetas
-- ===============================================

USE multigamer360;

-- Crear tabla para categorías de etiquetas
CREATE TABLE IF NOT EXISTS tag_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    filter_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_order (is_active, filter_order)
);

-- Crear tabla para asociar etiquetas con categorías
CREATE TABLE IF NOT EXISTS product_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    display_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    auto_detected BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tag_category (tag, category_id),
    FOREIGN KEY (category_id) REFERENCES tag_categories(id) ON DELETE CASCADE,
    INDEX idx_tag (tag),
    INDEX idx_category (category_id),
    INDEX idx_active (is_active)
);

-- Insertar categorías principales
INSERT INTO tag_categories (name, display_name, description, icon, filter_order) VALUES
('consolas', 'Consolas', 'Consolas de videojuegos y plataformas', 'fas fa-gamepad', 1),
('marcas', 'Marcas', 'Marcas y fabricantes de productos', 'fas fa-building', 2),
('generos', 'Géneros', 'Géneros de videojuegos', 'fas fa-tags', 3),
('accesorios', 'Accesorios', 'Accesorios y complementos', 'fas fa-plug', 4),
('condicion', 'Condición', 'Estado del producto', 'fas fa-star', 5),
('tipo', 'Tipo de Producto', 'Categoría general del producto', 'fas fa-box', 6)
ON DUPLICATE KEY UPDATE
display_name = VALUES(display_name),
description = VALUES(description),
icon = VALUES(icon);

-- Insertar etiquetas predefinidas por categoría
INSERT INTO product_tags (tag, category_id, display_name, auto_detected) VALUES
-- Consolas
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'playstation', 'PlayStation', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'nintendo', 'Nintendo', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'xbox', 'Xbox', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'psp', 'PlayStation Portable', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'ps1', 'PlayStation 1', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'ps2', 'PlayStation 2', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'ps3', 'PlayStation 3', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'ps4', 'PlayStation 4', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'ps5', 'PlayStation 5', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'switch', 'Nintendo Switch', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'switch-2', 'Nintendo Switch 2', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'wii', 'Nintendo Wii', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'wiiu', 'Nintendo Wii U', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), '3ds', 'Nintendo 3DS', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'xbox360', 'Xbox 360', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'xboxone', 'Xbox One', FALSE),
((SELECT id FROM tag_categories WHERE name = 'consolas'), 'xboxseriesx', 'Xbox Series X/S', FALSE),

-- Marcas
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'sony', 'Sony', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'nintendo', 'Nintendo', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'microsoft', 'Microsoft', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'square-enix', 'Square Enix', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'capcom', 'Capcom', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'konami', 'Konami', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'bandai', 'Bandai Namco', FALSE),
((SELECT id FROM tag_categories WHERE name = 'marcas'), 'sega', 'Sega', FALSE),

-- Géneros
((SELECT id FROM tag_categories WHERE name = 'generos'), 'accion', 'Acción', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'aventura', 'Aventura', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'rpg', 'RPG', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'plataformas', 'Plataformas', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'sigilo', 'Sigilo', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'deportes', 'Deportes', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'carreras', 'Carreras', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'lucha', 'Lucha', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'shooter', 'Shooter', FALSE),
((SELECT id FROM tag_categories WHERE name = 'generos'), 'estrategia', 'Estrategia', FALSE),

-- Accesorios
((SELECT id FROM tag_categories WHERE name = 'accesorios'), 'mando', 'Mandos/Controles', FALSE),
((SELECT id FROM tag_categories WHERE name = 'accesorios'), 'cable', 'Cables', FALSE),
((SELECT id FROM tag_categories WHERE name = 'accesorios'), 'cargador', 'Cargadores', FALSE),
((SELECT id FROM tag_categories WHERE name = 'accesorios'), 'funda', 'Fundas/Protección', FALSE),
((SELECT id FROM tag_categories WHERE name = 'accesorios'), 'memoria', 'Tarjetas de Memoria', FALSE),

-- Condición
((SELECT id FROM tag_categories WHERE name = 'condicion'), 'nuevo', 'Nuevo', FALSE),
((SELECT id FROM tag_categories WHERE name = 'condicion'), 'usado', 'Usado', FALSE),
((SELECT id FROM tag_categories WHERE name = 'condicion'), 'coleccion', 'Colección', FALSE),
((SELECT id FROM tag_categories WHERE name = 'condicion'), 'refurbished', 'Reacondicionado', FALSE),

-- Tipo de Producto
((SELECT id FROM tag_categories WHERE name = 'tipo'), 'videojuego', 'Videojuegos', FALSE),
((SELECT id FROM tag_categories WHERE name = 'tipo'), 'accesorio', 'Accesorios', FALSE),
((SELECT id FROM tag_categories WHERE name = 'tipo'), 'consola', 'Consolas', FALSE),
((SELECT id FROM tag_categories WHERE name = 'tipo'), 'bundle', 'Paquetes', FALSE)

ON DUPLICATE KEY UPDATE
display_name = VALUES(display_name),
auto_detected = VALUES(auto_detected);

-- Función para auto-detectar categoría de una etiqueta
DELIMITER //

CREATE OR REPLACE FUNCTION get_tag_category(tag_name VARCHAR(100)) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE category_id INT DEFAULT NULL;
    
    -- Buscar en tabla de etiquetas existentes
    SELECT pt.category_id INTO category_id
    FROM product_tags pt
    WHERE pt.tag = tag_name AND pt.is_active = 1
    LIMIT 1;
    
    -- Si no existe, intentar detectar automáticamente
    IF category_id IS NULL THEN
        -- Consolas por patrones comunes
        IF tag_name REGEXP '(ps[0-9]|playstation|xbox|nintendo|switch|wii|3ds|psp)' THEN
            SELECT id INTO category_id FROM tag_categories WHERE name = 'consolas';
        -- Géneros por patrones
        ELSEIF tag_name REGEXP '(accion|aventura|rpg|shooter|estrategia|deportes|carreras|lucha)' THEN
            SELECT id INTO category_id FROM tag_categories WHERE name = 'generos';
        -- Marcas por patrones
        ELSEIF tag_name REGEXP '(sony|nintendo|microsoft|square|capcom|konami|bandai|sega)' THEN
            SELECT id INTO category_id FROM tag_categories WHERE name = 'marcas';
        -- Accesorios por patrones
        ELSEIF tag_name REGEXP '(mando|cable|cargador|funda|memoria|control)' THEN
            SELECT id INTO category_id FROM tag_categories WHERE name = 'accesorios';
        -- Condición por patrones
        ELSEIF tag_name REGEXP '(nuevo|usado|coleccion|refurbished)' THEN
            SELECT id INTO category_id FROM tag_categories WHERE name = 'condicion';
        -- Tipo por defecto
        ELSE
            SELECT id INTO category_id FROM tag_categories WHERE name = 'tipo';
        END IF;
    END IF;
    
    RETURN category_id;
END//

DELIMITER ;

-- Migrar etiquetas existentes de productos
INSERT IGNORE INTO product_tags (tag, category_id, auto_detected)
SELECT DISTINCT 
    JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) as tag,
    get_tag_category(JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']')))) as category_id,
    TRUE as auto_detected
FROM products p
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
WHERE JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']')) IS NOT NULL
AND tags IS NOT NULL
AND JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) != ''
AND get_tag_category(JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']')))) IS NOT NULL;

-- Mostrar resumen
SELECT 
    tc.display_name as categoria,
    COUNT(pt.id) as cantidad_etiquetas,
    GROUP_CONCAT(pt.display_name ORDER BY pt.display_name SEPARATOR ', ') as etiquetas
FROM tag_categories tc
LEFT JOIN product_tags pt ON tc.id = pt.category_id AND pt.is_active = 1
WHERE tc.is_active = 1
GROUP BY tc.id, tc.display_name
ORDER BY tc.filter_order;