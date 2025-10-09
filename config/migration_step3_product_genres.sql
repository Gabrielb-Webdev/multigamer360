-- =====================================================
-- PASO 3: CREAR TABLA PRODUCT_GENRES (Relación N:N)
-- =====================================================
-- Ejecuta esto después del paso 2
-- =====================================================

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

-- Verificar que se creó
SELECT 'Tabla product_genres creada exitosamente' AS status;
SHOW TABLES LIKE 'product_genres';
