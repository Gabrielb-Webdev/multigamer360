-- ===============================================
-- DATOS DE PRUEBA PARA MULTIGAMER360
-- ===============================================

USE multigamer360;

-- ===============================================
-- CATEGORÍAS
-- ===============================================
INSERT INTO categories (name, slug, description, image_url) VALUES
('PlayStation', 'playstation', 'Consolas y juegos de PlayStation (1, 2, 3, 4, 5)', 'assets/images/categories/playstation.jpg'),
('Nintendo', 'nintendo', 'Consolas y juegos de Nintendo (NES, SNES, N64, GameCube, Wii, Switch)', 'assets/images/categories/nintendo.jpg'),
('Xbox', 'xbox', 'Consolas y juegos de Xbox (Original, 360, One, Series)', 'assets/images/categories/xbox.jpg'),
('Sega', 'sega', 'Consolas y juegos de Sega (Genesis, Saturn, Dreamcast)', 'assets/images/categories/sega.jpg'),
('Retro Gaming', 'retro-gaming', 'Consolas clásicas y juegos vintage', 'assets/images/categories/retro.jpg'),
('Accesorios', 'accesorios', 'Controles, cables, memory cards y más', 'assets/images/categories/accessories.jpg');

-- ===============================================
-- MARCAS
-- ===============================================
INSERT INTO brands (name, slug, logo_url) VALUES
('Sony', 'sony', 'assets/images/brands/sony.png'),
('Nintendo', 'nintendo', 'assets/images/brands/nintendo.png'),
('Microsoft', 'microsoft', 'assets/images/brands/microsoft.png'),
('Sega', 'sega', 'assets/images/brands/sega.png'),
('Atari', 'atari', 'assets/images/brands/atari.png'),
('Capcom', 'capcom', 'assets/images/brands/capcom.png'),
('Square Enix', 'square-enix', 'assets/images/brands/square-enix.png'),
('Konami', 'konami', 'assets/images/brands/konami.png');

-- ===============================================
-- MÉTODOS DE ENVÍO
-- ===============================================
INSERT INTO shipping_methods (id, name, description, cost, estimated_days) VALUES
('0', 'Retiro por Multigamer360', 'Retira tu pedido en nuestro local', 0.00, 'Inmediato'),
('2207', 'Retiro por Punto', 'Retira en punto de retiro más cercano', 3500.00, '1-3 días hábiles'),
('1425', 'Envío a Domicilio', 'Entrega en tu domicilio', 1425.00, '3-7 días hábiles');

-- ===============================================
-- PRODUCTOS
-- ===============================================
INSERT INTO products (name, slug, description, short_description, price, stock_quantity, sku, category_id, brand_id, image_url, is_featured, condition_product) VALUES

-- PlayStation Games
('Rayman 2 The Great Escape PlayStation 1', 'rayman-2-great-escape-ps1', 'Rayman 2: The Great Escape es un videojuego de plataformas en 3D desarrollado por Ubisoft. Considerado uno de los mejores juegos de plataformas de todos los tiempos.', 'Clásico juego de plataformas 3D para PlayStation 1', 45000.00, 15, 'PSX-RAY2-001', 1, 1, 'assets/images/products/product1.jpg', TRUE, 'used'),

('Final Fantasy VII PlayStation 1', 'final-fantasy-vii-ps1', 'Final Fantasy VII es un RPG japonés que revolucionó el género. Sigue la historia de Cloud Strife en su lucha contra Shinra Corporation.', 'RPG épico que marcó una generación', 55000.00, 8, 'PSX-FF7-001', 1, 7, 'assets/images/products/product3.jpg', TRUE, 'used'),

('Metal Gear Solid PlayStation 1', 'metal-gear-solid-ps1', 'Juego de sigilo táctico dirigido por Hideo Kojima. Una obra maestra del gaming que cambió la industria para siempre.', 'Clásico de acción sigilosa', 48000.00, 12, 'PSX-MGS-001', 1, 8, 'assets/images/products/metal-gear-solid.jpg', FALSE, 'used'),

('Crash Bandicoot PlayStation 1', 'crash-bandicoot-ps1', 'El marsupial más famoso de los videojuegos en su aventura original. Plataformas puras con el carisma inconfundible de Crash.', 'Aventura de plataformas con Crash', 42000.00, 20, 'PSX-CRASH-001', 1, 1, 'assets/images/products/crash-bandicoot.jpg', FALSE, 'used'),

-- Nintendo Games
('Super Mario 64 Nintendo 64', 'super-mario-64-n64', 'Super Mario 64 fue el juego de lanzamiento de Nintendo 64 y revolucionó los juegos de plataformas en 3D. Un clásico absoluto.', 'Revolución en plataformas 3D', 65000.00, 10, 'N64-SM64-001', 2, 2, 'assets/images/products/product2.jpg', TRUE, 'used'),

('The Legend of Zelda: Ocarina of Time Nintendo 64', 'zelda-ocarina-time-n64', 'Considerado por muchos como el mejor videojuego de todos los tiempos. Una aventura épica en Hyrule con mecánicas revolucionarias.', 'El mejor Zelda según los fans', 75000.00, 6, 'N64-ZELDA-001', 2, 2, 'assets/images/products/product4.jpg', TRUE, 'used'),

('Super Metroid SNES', 'super-metroid-snes', 'La obra maestra de la saga Metroid. Exploración, atmósfera y gameplay perfecto en 16 bits.', 'Metroidvania en su máxima expresión', 85000.00, 4, 'SNES-SMET-001', 2, 2, 'assets/images/products/super-metroid.jpg', TRUE, 'used'),

('Donkey Kong Country SNES', 'donkey-kong-country-snes', 'Plataformas pre-renderizadas que marcaron época. Rare en su mejor momento con gráficos revolucionarios.', 'Gráficos pre-renderizados impresionantes', 52000.00, 18, 'SNES-DKC-001', 2, 2, 'assets/images/products/donkey-kong-country.jpg', FALSE, 'used'),

-- Xbox Games
('Halo Combat Evolved Xbox', 'halo-combat-evolved-xbox', 'El juego que definió Xbox y revolucionó los FPS en consolas. Master Chief comienza su leyenda.', 'FPS que revolucionó las consolas', 38000.00, 25, 'XBOX-HALO-001', 3, 3, 'assets/images/products/halo-ce.jpg', FALSE, 'used'),

('Fable Xbox', 'fable-xbox', 'RPG británico con humor y personalidad única. Toma decisiones que afectan tu personaje y el mundo.', 'RPG con decisiones morales', 35000.00, 14, 'XBOX-FABLE-001', 3, 3, 'assets/images/products/fable.jpg', FALSE, 'used'),

-- Sega Games
('Sonic the Hedgehog Genesis', 'sonic-hedgehog-genesis', 'El erizo azul que compitió con Mario. Velocidad pura en 16 bits con bandas sonoras inolvidables.', 'Velocidad azul en 16 bits', 45000.00, 22, 'GEN-SONIC-001', 4, 4, 'assets/images/products/sonic-genesis.jpg', FALSE, 'used'),

('Streets of Rage 2 Genesis', 'streets-rage-2-genesis', 'Beat em up cooperativo considerado el mejor del género. Música de Yuzo Koshiro y gameplay perfecto.', 'El mejor beat em up de Sega', 48000.00, 16, 'GEN-SOR2-001', 4, 4, 'assets/images/products/streets-rage-2.jpg', FALSE, 'used'),

-- Consolas Retro
('Nintendo Entertainment System NES', 'nintendo-nes-console', 'La consola que salvó la industria de los videojuegos en los 80. Incluye controles y cables.', 'Consola que salvó la industria', 125000.00, 3, 'CONS-NES-001', 5, 2, 'assets/images/retro/retro-consola-1.jpg', TRUE, 'refurbished'),

('Sega Genesis Console', 'sega-genesis-console', 'La consola de 16 bits de Sega con su procesador Blast Processing. Incluye un control.', 'Consola de 16 bits con actitud', 95000.00, 5, 'CONS-GEN-001', 5, 4, 'assets/images/retro/retro-consola-2.jpg', FALSE, 'refurbished'),

('Super Nintendo SNES Console', 'super-nintendo-snes-console', 'La consola de 16 bits de Nintendo con la mejor biblioteca de juegos de la historia.', 'La mejor biblioteca de juegos', 110000.00, 4, 'CONS-SNES-001', 5, 2, 'assets/images/retro/snes-console.jpg', TRUE, 'refurbished'),

-- Accesorios
('Control DualShock PlayStation 1', 'dualshock-controller-ps1', 'Control original de PlayStation con vibración dual. En excelente estado de funcionamiento.', 'Control original con vibración', 25000.00, 30, 'ACC-DS1-001', 6, 1, 'assets/images/products/dualshock-ps1.jpg', FALSE, 'used'),

('Memory Card PlayStation 1', 'memory-card-ps1', 'Memory card oficial de 1MB para guardar tus partidas de PlayStation. Indispensable para RPGs.', 'Guarda tus partidas favoritas', 15000.00, 45, 'ACC-MC1-001', 6, 1, 'assets/images/products/memory-card-ps1.jpg', FALSE, 'used'),

('Control N64 Original', 'n64-controller-original', 'Control original de Nintendo 64 con stick analógico. El control que cambió la industria.', 'Control que revolucionó el gaming', 28000.00, 20, 'ACC-N64-001', 6, 2, 'assets/images/products/n64-controller.jpg', FALSE, 'used');

-- ===============================================
-- USUARIOS DE PRUEBA
-- ===============================================
INSERT INTO users (email, password, first_name, last_name, phone, role, is_active, email_verified) VALUES
('admin@multigamer360.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Multigamer360', '+54 11 1234-5678', 'admin', TRUE, TRUE),
('cliente@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Pérez', '+54 11 9876-5432', 'customer', TRUE, TRUE),
('maria@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María', 'González', '+54 11 5555-1234', 'customer', TRUE, TRUE),
('carlos@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos', 'Rodríguez', '+54 11 7777-8888', 'customer', TRUE, FALSE);

-- ===============================================
-- DIRECCIONES DE USUARIOS
-- ===============================================
INSERT INTO user_addresses (user_id, type, first_name, last_name, address_line_1, city, state, postal_code, phone, is_default) VALUES
(2, 'both', 'Juan', 'Pérez', 'Av. Corrientes 1234', 'Buenos Aires', 'CABA', '1043', '+54 11 9876-5432', TRUE),
(3, 'both', 'María', 'González', 'San Martín 567', 'Córdoba', 'Córdoba', '5000', '+54 11 5555-1234', TRUE),
(4, 'shipping', 'Carlos', 'Rodríguez', 'Mitre 890', 'Rosario', 'Santa Fe', '2000', '+54 11 7777-8888', TRUE);

-- ===============================================
-- CUPONES DE DESCUENTO
-- ===============================================
INSERT INTO coupons (code, description, type, value, minimum_amount, usage_limit, is_active, expires_at) VALUES
('BIENVENIDO10', 'Descuento de bienvenida del 10%', 'percentage', 10.00, 30000.00, 100, TRUE, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('RETRO5000', 'Descuento fijo de $5000 en juegos retro', 'fixed_amount', 5000.00, 40000.00, 50, TRUE, DATE_ADD(NOW(), INTERVAL 15 DAY)),
('ENVIOGRATIS', 'Envío gratis en compras mayores a $80000', 'percentage', 0.00, 80000.00, 200, TRUE, DATE_ADD(NOW(), INTERVAL 60 DAY));

-- ===============================================
-- PEDIDOS DE EJEMPLO
-- ===============================================
INSERT INTO orders (order_number, user_id, status, payment_status, payment_method, shipping_method_id, subtotal, shipping_cost, total, billing_first_name, billing_last_name, billing_email, billing_phone, billing_address_1, billing_city, billing_state, billing_postal_code, shipping_first_name, shipping_last_name, shipping_address_1, shipping_city, shipping_state, shipping_postal_code) VALUES
('MG360-001', 2, 'delivered', 'paid', 'local', '0', 45000.00, 0.00, 45000.00, 'Juan', 'Pérez', 'cliente@ejemplo.com', '+54 11 9876-5432', 'Av. Corrientes 1234', 'Buenos Aires', 'CABA', '1043', 'Juan', 'Pérez', 'Av. Corrientes 1234', 'Buenos Aires', 'CABA', '1043'),
('MG360-002', 3, 'shipped', 'paid', 'online', '1425', 65000.00, 1425.00, 66425.00, 'María', 'González', 'maria@ejemplo.com', '+54 11 5555-1234', 'San Martín 567', 'Córdoba', 'Córdoba', '5000', 'María', 'González', 'San Martín 567', 'Córdoba', 'Córdoba', '5000');

-- ===============================================
-- ITEMS DE PEDIDOS
-- ===============================================
INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, total) VALUES
(1, 1, 'Rayman 2 The Great Escape PlayStation 1', 'PSX-RAY2-001', 1, 45000.00, 45000.00),
(2, 2, 'Super Mario 64 Nintendo 64', 'N64-SM64-001', 1, 65000.00, 65000.00);

-- ===============================================
-- RESEÑAS DE PRODUCTOS
-- ===============================================
INSERT INTO product_reviews (product_id, user_id, rating, title, comment, is_approved, is_verified_purchase) VALUES
(1, 2, 5, 'Excelente juego clásico', 'Rayman 2 sigue siendo una obra maestra. El juego llegó en perfecto estado y funciona sin problemas.', TRUE, TRUE),
(2, 3, 5, 'El mejor Mario 3D', 'Super Mario 64 es simplemente perfecto. Un clásico que nunca pasa de moda. Muy recomendado.', TRUE, TRUE),
(3, 2, 4, 'Gran juego de sigilo', 'Metal Gear Solid marcó una época. La historia es increíble y el gameplay sigue siendo sólido.', TRUE, FALSE);

-- ===============================================
-- FAVORITOS
-- ===============================================
INSERT INTO user_favorites (user_id, product_id) VALUES
(2, 3), (2, 6), (2, 13),
(3, 1), (3, 2), (3, 15),
(4, 5), (4, 7), (4, 9);

-- ===============================================
-- CONFIGURACIONES DEL SITIO
-- ===============================================
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Multigamer360', 'text', 'Nombre del sitio web'),
('site_description', 'Tu tienda de videojuegos retro de confianza', 'text', 'Descripción del sitio'),
('contact_email', 'info@multigamer360.com', 'text', 'Email de contacto principal'),
('contact_phone', '+54 11 1234-5678', 'text', 'Teléfono de contacto'),
('contact_address', 'Av. Corrientes 1234, Buenos Aires, Argentina', 'text', 'Dirección física'),
('currency', 'ARS', 'text', 'Moneda por defecto'),
('tax_rate', '21', 'number', 'Tasa de impuesto (IVA)'),
('free_shipping_minimum', '80000', 'number', 'Monto mínimo para envío gratis'),
('featured_products_limit', '8', 'number', 'Cantidad de productos destacados a mostrar'),
('products_per_page', '12', 'number', 'Productos por página en listados');

-- ===============================================
-- CONTACTOS DE EJEMPLO
-- ===============================================
INSERT INTO contacts (name, email, phone, subject, message, status) VALUES
('Pedro López', 'pedro@ejemplo.com', '+54 11 2222-3333', 'Consulta sobre envíos', 'Hola, quería saber si hacen envíos a Mar del Plata. Gracias.', 'replied'),
('Ana García', 'ana@ejemplo.com', '+54 11 4444-5555', 'Estado de mi pedido', 'Buenos días, quería consultar el estado de mi pedido MG360-002.', 'new'),
('Roberto Silva', 'roberto@ejemplo.com', NULL, 'Producto defectuoso', 'Recibí un control de PS1 que no funciona correctamente. ¿Pueden ayudarme?', 'read');