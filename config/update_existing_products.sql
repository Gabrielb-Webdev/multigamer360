-- =====================================================
-- ACTUALIZAR PRODUCTOS EXISTENTES
-- =====================================================
-- Este script actualiza tus productos actuales con:
-- 1. console_id (relación con tabla consoles)
-- 2. Géneros (relación N:N con tabla product_genres)
-- =====================================================

-- =====================================================
-- PRODUCTO 1: Kingdom Hearts II (PlayStation 2)
-- =====================================================
-- Actualizar con console_id
UPDATE products 
SET console_id = (SELECT id FROM consoles WHERE slug = 'ps2')
WHERE name LIKE '%Kingdom Hearts%';

-- Asignar géneros: Acción, Aventura, RPG
INSERT INTO product_genres (product_id, genre_id) 
SELECT 
    (SELECT id FROM products WHERE name LIKE '%Kingdom Hearts%' LIMIT 1),
    id
FROM genres 
WHERE slug IN ('accion', 'aventura', 'rpg')
ON DUPLICATE KEY UPDATE product_id=product_id;

-- =====================================================
-- PRODUCTO 2: Super Mario 64 (Nintendo 64)
-- =====================================================
-- Actualizar con console_id
UPDATE products 
SET console_id = (SELECT id FROM consoles WHERE slug = 'n64')
WHERE name LIKE '%Super Mario 64%' OR name LIKE '%Mario 64%';

-- Asignar géneros: Plataformas, Acción, Aventura
INSERT INTO product_genres (product_id, genre_id) 
SELECT 
    (SELECT id FROM products WHERE name LIKE '%Mario 64%' LIMIT 1),
    id
FROM genres 
WHERE slug IN ('plataformas', 'accion', 'aventura')
ON DUPLICATE KEY UPDATE product_id=product_id;

-- =====================================================
-- PRODUCTO 3: Zelda Ocarina of Time (Nintendo 64)
-- =====================================================
-- Actualizar con console_id
UPDATE products 
SET console_id = (SELECT id FROM consoles WHERE slug = 'n64')
WHERE name LIKE '%Zelda%' AND name LIKE '%Ocarina%';

-- Asignar géneros: Aventura, Acción, RPG
INSERT INTO product_genres (product_id, genre_id) 
SELECT 
    (SELECT id FROM products WHERE name LIKE '%Zelda%' AND name LIKE '%Ocarina%' LIMIT 1),
    id
FROM genres 
WHERE slug IN ('aventura', 'accion', 'rpg')
ON DUPLICATE KEY UPDATE product_id=product_id;

-- =====================================================
-- PRODUCTO 4: Metal Gear Solid (PlayStation)
-- =====================================================
-- Actualizar con console_id
UPDATE products 
SET console_id = (SELECT id FROM consoles WHERE slug = 'ps1')
WHERE name LIKE '%Metal Gear Solid%';

-- Asignar géneros: Acción, Sigilo
INSERT INTO product_genres (product_id, genre_id) 
SELECT 
    (SELECT id FROM products WHERE name LIKE '%Metal Gear Solid%' LIMIT 1),
    id
FROM genres 
WHERE slug IN ('accion', 'sigilo')
ON DUPLICATE KEY UPDATE product_id=product_id;

-- =====================================================
-- PRODUCTO 5: Chrono Trigger (Super Nintendo)
-- =====================================================
-- Actualizar con console_id
UPDATE products 
SET console_id = (SELECT id FROM consoles WHERE slug = 'snes')
WHERE name LIKE '%Chrono Trigger%';

-- Asignar géneros: RPG, Aventura
INSERT INTO product_genres (product_id, genre_id) 
SELECT 
    (SELECT id FROM products WHERE name LIKE '%Chrono Trigger%' LIMIT 1),
    id
FROM genres 
WHERE slug IN ('rpg', 'aventura')
ON DUPLICATE KEY UPDATE product_id=product_id;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================
-- Ver productos con sus consolas y géneros
SELECT 
    p.id,
    p.name as producto,
    co.name as consola,
    GROUP_CONCAT(g.name SEPARATOR ', ') as generos
FROM products p
LEFT JOIN consoles co ON p.console_id = co.id
LEFT JOIN product_genres pg ON p.id = pg.product_id
LEFT JOIN genres g ON pg.genre_id = g.id
WHERE p.is_active = TRUE
GROUP BY p.id, p.name, co.name
ORDER BY p.id;

SELECT '✅ Productos actualizados exitosamente' AS resultado;
