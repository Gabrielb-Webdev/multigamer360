-- Actualizar tabla de categorías existente
ALTER TABLE categories 
MODIFY COLUMN name VARCHAR(255) NOT NULL,
MODIFY COLUMN slug VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS parent_id INT NULL AFTER description,
ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT '' AFTER parent_id,
ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT '' AFTER image,
ADD COLUMN IF NOT EXISTS icon_class VARCHAR(100) DEFAULT '' AFTER banner_image,
ADD COLUMN IF NOT EXISTS meta_title VARCHAR(255) DEFAULT '' AFTER icon_class,
ADD COLUMN IF NOT EXISTS meta_description TEXT AFTER meta_title,
ADD COLUMN IF NOT EXISTS meta_keywords TEXT AFTER meta_description,
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE AFTER is_active,
ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 AFTER is_featured;

-- Crear índices solo si no existen
CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug);
CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_id);
CREATE INDEX IF NOT EXISTS idx_categories_active ON categories(is_active);
CREATE INDEX IF NOT EXISTS idx_categories_sort ON categories(sort_order);

-- Agregar foreign key si no existe
-- ALTER TABLE categories ADD CONSTRAINT fk_categories_parent 
-- FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Agregar campo category_id a la tabla products si no existe
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS category_id INT NULL AFTER id;

-- Agregar índice solo si no existe
CREATE INDEX IF NOT EXISTS idx_category ON products(category_id);

-- Insertar categorías de ejemplo para gaming
INSERT IGNORE INTO categories (name, slug, description, icon_class, is_active, is_featured, sort_order) VALUES
('Consolas', 'consolas', 'Consolas de videojuegos de todas las marcas', 'fas fa-gamepad', 1, 1, 1),
('PC Gaming', 'pc-gaming', 'Componentes y accesorios para PC Gaming', 'fas fa-desktop', 1, 1, 2),
('Videojuegos', 'videojuegos', 'Videojuegos para todas las plataformas', 'fas fa-compact-disc', 1, 1, 3),
('Accesorios', 'accesorios', 'Accesorios y periféricos gaming', 'fas fa-headset', 1, 1, 4),
('Retro Gaming', 'retro-gaming', 'Consolas y juegos retro clásicos', 'fas fa-history', 1, 0, 5);

-- Subcategorías para Consolas
INSERT IGNORE INTO categories (name, slug, description, parent_id, icon_class, is_active, sort_order) VALUES
('PlayStation', 'playstation', 'Consolas PlayStation', (SELECT id FROM categories WHERE slug = 'consolas'), 'fab fa-playstation', 1, 1),
('Xbox', 'xbox', 'Consolas Xbox', (SELECT id FROM categories WHERE slug = 'consolas'), 'fab fa-xbox', 1, 2),
('Nintendo', 'nintendo', 'Consolas Nintendo', (SELECT id FROM categories WHERE slug = 'consolas'), 'fas fa-gamepad', 1, 3);

-- Subcategorías para PC Gaming
INSERT IGNORE INTO categories (name, slug, description, parent_id, icon_class, is_active, sort_order) VALUES
('Tarjetas Gráficas', 'tarjetas-graficas', 'GPUs para gaming', (SELECT id FROM categories WHERE slug = 'pc-gaming'), 'fas fa-microchip', 1, 1),
('Procesadores', 'procesadores', 'CPUs para gaming', (SELECT id FROM categories WHERE slug = 'pc-gaming'), 'fas fa-microchip', 1, 2),
('Memoria RAM', 'memoria-ram', 'Memoria RAM para gaming', (SELECT id FROM categories WHERE slug = 'pc-gaming'), 'fas fa-memory', 1, 3),
('Almacenamiento', 'almacenamiento', 'SSDs y HDDs', (SELECT id FROM categories WHERE slug = 'pc-gaming'), 'fas fa-hdd', 1, 4);

-- Subcategorías para Videojuegos
INSERT IGNORE INTO categories (name, slug, description, parent_id, icon_class, is_active, sort_order) VALUES
('PS5', 'juegos-ps5', 'Juegos para PlayStation 5', (SELECT id FROM categories WHERE slug = 'videojuegos'), 'fab fa-playstation', 1, 1),
('Xbox Series', 'juegos-xbox-series', 'Juegos para Xbox Series X/S', (SELECT id FROM categories WHERE slug = 'videojuegos'), 'fab fa-xbox', 1, 2),
('Nintendo Switch', 'juegos-nintendo-switch', 'Juegos para Nintendo Switch', (SELECT id FROM categories WHERE slug = 'videojuegos'), 'fas fa-gamepad', 1, 3),
('PC', 'juegos-pc', 'Juegos para PC', (SELECT id FROM categories WHERE slug = 'videojuegos'), 'fas fa-desktop', 1, 4);

-- Subcategorías para Accesorios
INSERT IGNORE INTO categories (name, slug, description, parent_id, icon_class, is_active, sort_order) VALUES
('Controles', 'controles', 'Controles y mandos', (SELECT id FROM categories WHERE slug = 'accesorios'), 'fas fa-gamepad', 1, 1),
('Auriculares', 'auriculares', 'Auriculares gaming', (SELECT id FROM categories WHERE slug = 'accesorios'), 'fas fa-headphones', 1, 2),
('Teclados', 'teclados', 'Teclados mecánicos gaming', (SELECT id FROM categories WHERE slug = 'accesorios'), 'fas fa-keyboard', 1, 3),
('Ratones', 'ratones', 'Ratones gaming', (SELECT id FROM categories WHERE slug = 'accesorios'), 'fas fa-mouse', 1, 4),
('Sillas', 'sillas-gaming', 'Sillas gaming', (SELECT id FROM categories WHERE slug = 'accesorios'), 'fas fa-chair', 1, 5);