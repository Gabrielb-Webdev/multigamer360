-- =====================================================
-- PASO 6: INSERTAR GÉNEROS
-- =====================================================
-- Ejecuta esto después del paso 5
-- Esto inserta ~20 géneros populares
-- =====================================================

INSERT INTO genres (name, slug, description, icon) VALUES
('Acción', 'accion', 'Juegos de acción y combate', 'fas fa-fist-raised'),
('Aventura', 'aventura', 'Juegos de aventura y exploración', 'fas fa-compass'),
('RPG', 'rpg', 'Juegos de rol (Role-Playing Game)', 'fas fa-dragon'),
('Estrategia', 'estrategia', 'Juegos de estrategia y planificación', 'fas fa-chess'),
('Simulación', 'simulacion', 'Juegos de simulación', 'fas fa-plane'),
('Deportes', 'deportes', 'Juegos deportivos', 'fas fa-futbol'),
('Carreras', 'carreras', 'Juegos de carreras', 'fas fa-flag-checkered'),
('Plataformas', 'plataformas', 'Juegos de plataformas', 'fas fa-running'),
('Shooter', 'shooter', 'Juegos de disparos (FPS/TPS)', 'fas fa-crosshairs'),
('Peleas', 'peleas', 'Juegos de pelea', 'fas fa-hand-rock'),
('Puzzle', 'puzzle', 'Juegos de rompecabezas', 'fas fa-puzzle-piece'),
('Musical', 'musical', 'Juegos musicales y ritmo', 'fas fa-music'),
('Terror', 'terror', 'Juegos de terror y supervivencia', 'fas fa-ghost'),
('Sigilo', 'sigilo', 'Juegos de sigilo e infiltración', 'fas fa-user-secret'),
('MMORPG', 'mmorpg', 'Juegos de rol multijugador masivo', 'fas fa-users'),
('Battle Royale', 'battle-royale', 'Juegos tipo Battle Royale', 'fas fa-trophy'),
('Sandbox', 'sandbox', 'Juegos de mundo abierto', 'fas fa-cube'),
('Party', 'party', 'Juegos de fiesta y minijuegos', 'fas fa-birthday-cake'),
('Educativo', 'educativo', 'Juegos educativos', 'fas fa-graduation-cap'),
('Indie', 'indie', 'Juegos independientes', 'fas fa-lightbulb')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Verificar
SELECT COUNT(*) as total_genres FROM genres;
SELECT * FROM genres ORDER BY name;
