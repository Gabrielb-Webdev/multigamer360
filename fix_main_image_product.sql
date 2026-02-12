-- =====================================================
-- SINCRONIZAR main_image CUANDO FALTA
-- =====================================================
-- Este script actualiza products.main_image con la primera
-- imagen de product_images cuando main_image está vacío o no existe
-- =====================================================

-- Para el producto específico (ID 7)
UPDATE products p
LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
SET p.main_image = COALESCE(
    pi.image_url,
    (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order ASC LIMIT 1)
)
WHERE p.id = 7;

-- Para todos los productos que tengan imágenes pero main_image vacío
UPDATE products p
SET p.main_image = (
    SELECT image_url 
    FROM product_images 
    WHERE product_id = p.id 
    ORDER BY is_primary DESC, display_order ASC 
    LIMIT 1
)
WHERE (p.main_image IS NULL OR p.main_image = '')
  AND EXISTS (SELECT 1 FROM product_images WHERE product_id = p.id);

-- Verificar resultado
SELECT 
    p.id,
    p.name,
    p.main_image as product_main_image,
    GROUP_CONCAT(CONCAT(pi.image_url, ' (primary:', pi.is_primary, ')') ORDER BY pi.display_order SEPARATOR ', ') as all_images
FROM products p
LEFT JOIN product_images pi ON p.id = pi.product_id
GROUP BY p.id
HAVING p.id = 7;
