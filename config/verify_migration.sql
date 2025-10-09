-- =====================================================
-- VERIFICACIÓN POST-MIGRACIÓN
-- =====================================================
-- Ejecuta estos queries para verificar que todo salió bien
-- =====================================================

-- 1. Ver todas las tablas nuevas
SHOW TABLES LIKE '%console%';
SHOW TABLES LIKE '%genre%';

-- 2. Verificar estructura de products (debe tener console_id)
SHOW COLUMNS FROM products;

-- 3. Ver cuántas consolas se insertaron (debe ser ~30)
SELECT COUNT(*) as total_consolas FROM consoles;

-- 4. Ver cuántos géneros se insertaron (debe ser ~20)
SELECT COUNT(*) as total_generos FROM genres;

-- 5. Ver lista de consolas disponibles
SELECT id, name, manufacturer FROM consoles ORDER BY manufacturer, name;

-- 6. Ver lista de géneros disponibles
SELECT id, name, description FROM genres ORDER BY name;

-- 7. Ver productos actuales (verás que console_id está NULL por ahora)
SELECT 
    p.id,
    p.name as producto,
    c.name as categoria,
    b.name as marca,
    co.name as consola
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN brands b ON p.brand_id = b.id
LEFT JOIN consoles co ON p.console_id = co.id
WHERE p.is_active = TRUE
ORDER BY p.id;
