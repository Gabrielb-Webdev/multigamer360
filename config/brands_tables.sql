-- Tabla de marcas
CREATE TABLE IF NOT EXISTS brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    logo VARCHAR(255) DEFAULT '',
    banner_image VARCHAR(255) DEFAULT '',
    website VARCHAR(255) DEFAULT '',
    meta_title VARCHAR(255) DEFAULT '',
    meta_description TEXT,
    meta_keywords TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_brands_slug (slug),
    INDEX idx_brands_active (is_active),
    INDEX idx_brands_featured (is_featured)
);

-- Agregar campo brand_id a la tabla products si no existe
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS brand_id INT NULL AFTER category_id;

-- Crear índice solo si no existe
CREATE INDEX IF NOT EXISTS idx_product_brand ON products(brand_id);

-- Insertar marcas de ejemplo para gaming
INSERT IGNORE INTO brands (name, slug, description, website, is_active, is_featured) VALUES
('Sony', 'sony', 'Consolas PlayStation y accesorios', 'https://www.sony.com', 1, 1),
('Microsoft', 'microsoft', 'Consolas Xbox y servicios gaming', 'https://www.xbox.com', 1, 1),
('Nintendo', 'nintendo', 'Consolas y juegos Nintendo', 'https://www.nintendo.com', 1, 1),
('NVIDIA', 'nvidia', 'Tarjetas gráficas GeForce', 'https://www.nvidia.com', 1, 1),
('AMD', 'amd', 'Procesadores y tarjetas gráficas Radeon', 'https://www.amd.com', 1, 1),
('Intel', 'intel', 'Procesadores y componentes', 'https://www.intel.com', 1, 0),
('ASUS', 'asus', 'Componentes y periféricos gaming', 'https://www.asus.com', 1, 1),
('MSI', 'msi', 'Componentes gaming y laptops', 'https://www.msi.com', 1, 0),
('Corsair', 'corsair', 'Periféricos y componentes gaming', 'https://www.corsair.com', 1, 0),
('Logitech', 'logitech', 'Ratones, teclados y auriculares gaming', 'https://www.logitech.com', 1, 0),
('Razer', 'razer', 'Periféricos gaming premium', 'https://www.razer.com', 1, 1),
('SteelSeries', 'steelseries', 'Auriculares y periféricos gaming', 'https://steelseries.com', 1, 0),
('HyperX', 'hyperx', 'Memoria RAM y auriculares gaming', 'https://www.hyperxgaming.com', 1, 0),
('Gigabyte', 'gigabyte', 'Tarjetas madre y tarjetas gráficas', 'https://www.gigabyte.com', 1, 0),
('ASRock', 'asrock', 'Tarjetas madre y mini PCs', 'https://www.asrock.com', 1, 0);