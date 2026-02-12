-- =====================================================
-- SINCRONIZAR main_image CON product_images
-- =====================================================
-- Copiar la imagen principal de product_images a products.main_image
-- =====================================================

UPDATE products p
INNER JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
SET p.main_image = pi.image_url
WHERE p.main_image IS NULL OR p.main_image = '';

-- Verificar resultado
SELECT 
    p.id,
    p.name,
    p.main_image,
    pi.image_url as product_images_url
FROM products p
LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
LIMIT 10;
