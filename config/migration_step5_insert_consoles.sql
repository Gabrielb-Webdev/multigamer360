-- =====================================================
-- PASO 5: INSERTAR CONSOLAS
-- =====================================================
-- Ejecuta esto después del paso 4
-- Esto inserta ~30 consolas populares
-- =====================================================

INSERT INTO consoles (name, slug, manufacturer, icon) VALUES
-- Nintendo
('Nintendo Entertainment System (NES)', 'nes', 'Nintendo', 'fas fa-gamepad'),
('Super Nintendo (SNES)', 'snes', 'Nintendo', 'fas fa-gamepad'),
('Nintendo 64', 'n64', 'Nintendo', 'fas fa-gamepad'),
('GameCube', 'gamecube', 'Nintendo', 'fas fa-gamepad'),
('Nintendo Wii', 'wii', 'Nintendo', 'fas fa-gamepad'),
('Nintendo Wii U', 'wii-u', 'Nintendo', 'fas fa-gamepad'),
('Nintendo Switch', 'switch', 'Nintendo', 'fas fa-gamepad'),
('Game Boy', 'gameboy', 'Nintendo', 'fas fa-gamepad'),
('Game Boy Color', 'gbc', 'Nintendo', 'fas fa-gamepad'),
('Game Boy Advance', 'gba', 'Nintendo', 'fas fa-gamepad'),
('Nintendo DS', 'nds', 'Nintendo', 'fas fa-gamepad'),
('Nintendo 3DS', '3ds', 'Nintendo', 'fas fa-gamepad'),

-- Sony PlayStation
('PlayStation', 'ps1', 'Sony', 'fas fa-gamepad'),
('PlayStation 2', 'ps2', 'Sony', 'fas fa-gamepad'),
('PlayStation 3', 'ps3', 'Sony', 'fas fa-gamepad'),
('PlayStation 4', 'ps4', 'Sony', 'fas fa-gamepad'),
('PlayStation 5', 'ps5', 'Sony', 'fas fa-gamepad'),
('PlayStation Portable (PSP)', 'psp', 'Sony', 'fas fa-gamepad'),
('PlayStation Vita', 'ps-vita', 'Sony', 'fas fa-gamepad'),

-- Sega
('Sega Genesis / Mega Drive', 'genesis', 'Sega', 'fas fa-gamepad'),
('Sega Saturn', 'saturn', 'Sega', 'fas fa-gamepad'),
('Sega Dreamcast', 'dreamcast', 'Sega', 'fas fa-gamepad'),
('Sega Game Gear', 'game-gear', 'Sega', 'fas fa-gamepad'),

-- Microsoft Xbox
('Xbox', 'xbox', 'Microsoft', 'fas fa-gamepad'),
('Xbox 360', 'xbox-360', 'Microsoft', 'fas fa-gamepad'),
('Xbox One', 'xbox-one', 'Microsoft', 'fas fa-gamepad'),
('Xbox Series X/S', 'xbox-series', 'Microsoft', 'fas fa-gamepad'),

-- Otras
('Atari 2600', 'atari-2600', 'Atari', 'fas fa-gamepad'),
('PC', 'pc', 'Múltiples', 'fas fa-desktop'),
('Retro / Clásicas', 'retro', 'Múltiples', 'fas fa-ghost')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Verificar
SELECT COUNT(*) as total_consoles FROM consoles;
SELECT * FROM consoles ORDER BY name;
