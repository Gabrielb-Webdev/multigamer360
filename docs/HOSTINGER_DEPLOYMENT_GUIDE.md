# 🚀 GUÍA DE DEPLOYMENT A HOSTINGER

## ✅ CHECKLIST RÁPIDO

- [ ] Subir 3 archivos PHP vía File Manager
- [ ] Ejecutar 7 secciones SQL en phpMyAdmin de producción
- [ ] Verificar productos en https://teal-fish-507993.hostingersite.com/productos.php

---

## 📁 PARTE 1: SUBIR ARCHIVOS PHP

### Archivos que debes subir:

1. **includes/smart_filters_v2.php** (NUEVO - no existe en el servidor)
2. **includes/product_manager.php** (MODIFICADO - reemplazar el existente)
3. **productos.php** (MODIFICADO - reemplazar el existente)

### ¿Cómo subirlos?

#### Opción A: File Manager de Hostinger (MÁS FÁCIL)

1. Ve a tu panel de Hostinger: https://hpanel.hostinger.com
2. Click en **"File Manager"** (Administrador de archivos)
3. Navega a la carpeta `public_html`
4. Para subir **smart_filters_v2.php**:
   - Entra a la carpeta `includes`
   - Click en **"Upload Files"** (Subir archivos)
   - Selecciona el archivo desde tu PC: `f:\xampp\htdocs\multigamer360\includes\smart_filters_v2.php`
   - Click en **"Upload"**

5. Para subir **product_manager.php**:
   - Ya estás en `includes`
   - Click en **"Upload Files"**
   - Selecciona: `f:\xampp\htdocs\multigamer360\includes\product_manager.php`
   - Si pregunta "File already exists", selecciona **"Replace"** (Reemplazar)

6. Para subir **productos.php**:
   - Regresa a `public_html` (carpeta raíz)
   - Click en **"Upload Files"**
   - Selecciona: `f:\xampp\htdocs\multigamer360\productos.php`
   - Selecciona **"Replace"** si existe

#### Opción B: FTP con FileZilla

1. Abre FileZilla
2. Conecta con tus credenciales:
   - Host: `ftp.teal-fish-507993.hostingersite.com`
   - Usuario: (tu usuario FTP de Hostinger)
   - Contraseña: (tu contraseña FTP)
   - Puerto: 21

3. Navega al lado derecho (servidor) a `public_html`
4. Navega al lado izquierdo (local) a `f:\xampp\htdocs\multigamer360\`
5. Arrastra los 3 archivos mencionados

---

## 🗄️ PARTE 2: EJECUTAR MIGRACIÓN EN BASE DE DATOS

### Acceder a phpMyAdmin de Hostinger:

1. Ve a tu panel de Hostinger
2. Click en **"Databases"** → **"MySQL databases"**
3. Encuentra tu base de datos: **u851317150_mg360**
4. Click en **"Enter phpMyAdmin"**
5. Selecciona la base de datos `u851317150_mg360` en el panel izquierdo
6. Click en la pestaña **"SQL"** arriba

### Ejecutar Scripts (UNO POR UNO):

⚠️ **IMPORTANTE**: NO ejecutes todo junto. Hazlo paso por paso.

#### Script 1: Crear tabla CONSOLES

```sql
CREATE TABLE IF NOT EXISTS consoles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    manufacturer VARCHAR(100),
    generation INT,
    release_year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_manufacturer (manufacturer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

✅ **Verifica**: Debe decir "1 tabla creada" o "Query OK"

---

#### Script 2: Crear tabla GENRES

```sql
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

✅ **Verifica**: Debe decir "1 tabla creada"

---

#### Script 3: Crear tabla PRODUCT_GENRES

```sql
CREATE TABLE IF NOT EXISTS product_genres (
    product_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (product_id, genre_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_genre (genre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

✅ **Verifica**: Debe decir "1 tabla creada"

---

#### Script 4: Agregar columna console_id

```sql
ALTER TABLE products 
ADD COLUMN console_id INT NULL AFTER brand_id,
ADD INDEX idx_console (console_id),
ADD CONSTRAINT fk_product_console FOREIGN KEY (console_id) REFERENCES consoles(id) ON DELETE SET NULL;
```

✅ **Verifica**: Debe decir "Tabla alterada" o "1 fila afectada"

---

#### Script 5: Insertar 30 CONSOLAS

Copia y pega este INSERT completo:

```sql
INSERT INTO consoles (name, slug, manufacturer, generation, release_year) VALUES
('NES', 'nes', 'Nintendo', 3, 1983),
('SNES', 'snes', 'Nintendo', 4, 1990),
('Nintendo 64', 'nintendo-64', 'Nintendo', 5, 1996),
('GameCube', 'gamecube', 'Nintendo', 6, 2001),
('Wii', 'wii', 'Nintendo', 7, 2006),
('Wii U', 'wii-u', 'Nintendo', 8, 2012),
('Nintendo Switch', 'nintendo-switch', 'Nintendo', 9, 2017),
('Game Boy', 'game-boy', 'Nintendo', 4, 1989),
('Game Boy Color', 'game-boy-color', 'Nintendo', 5, 1998),
('Game Boy Advance', 'game-boy-advance', 'Nintendo', 6, 2001),
('Nintendo DS', 'nintendo-ds', 'Nintendo', 7, 2004),
('Nintendo 3DS', 'nintendo-3ds', 'Nintendo', 8, 2011),
('PlayStation', 'playstation', 'Sony', 5, 1994),
('PlayStation 2', 'playstation-2', 'Sony', 6, 2000),
('PlayStation 3', 'playstation-3', 'Sony', 7, 2006),
('PlayStation 4', 'playstation-4', 'Sony', 8, 2013),
('PlayStation 5', 'playstation-5', 'Sony', 9, 2020),
('PSP', 'psp', 'Sony', 7, 2004),
('PS Vita', 'ps-vita', 'Sony', 8, 2011),
('Xbox', 'xbox', 'Microsoft', 6, 2001),
('Xbox 360', 'xbox-360', 'Microsoft', 7, 2005),
('Xbox One', 'xbox-one', 'Microsoft', 8, 2013),
('Xbox Series X/S', 'xbox-series-x-s', 'Microsoft', 9, 2020),
('Sega Genesis', 'sega-genesis', 'Sega', 4, 1988),
('Sega Saturn', 'sega-saturn', 'Sega', 5, 1994),
('Dreamcast', 'dreamcast', 'Sega', 6, 1998),
('Atari 2600', 'atari-2600', 'Atari', 2, 1977),
('PC', 'pc', 'Varios', NULL, NULL),
('Arcade', 'arcade', 'Varios', NULL, NULL),
('Multi-plataforma', 'multi-plataforma', 'Varios', NULL, NULL)
ON DUPLICATE KEY UPDATE name=VALUES(name);
```

✅ **Verifica**: Debe decir "30 filas insertadas"

---

#### Script 6: Insertar 20 GÉNEROS

```sql
INSERT INTO genres (name, slug, description) VALUES
('Acción', 'accion', 'Juegos con énfasis en desafíos físicos y combate'),
('Aventura', 'aventura', 'Juegos centrados en exploración y narrativa'),
('RPG', 'rpg', 'Juegos de rol con desarrollo de personajes'),
('Deportes', 'deportes', 'Simuladores de deportes y competencias'),
('Carreras', 'carreras', 'Juegos de conducción y carreras'),
('Estrategia', 'estrategia', 'Juegos que requieren planificación táctica'),
('Simulación', 'simulacion', 'Simuladores de vida real o sistemas'),
('Plataformas', 'plataformas', 'Juegos de saltos y obstáculos'),
('Puzzle', 'puzzle', 'Juegos de resolución de acertijos'),
('Fighting', 'fighting', 'Juegos de lucha uno contra uno'),
('Shooter', 'shooter', 'Juegos de disparos en primera o tercera persona'),
('Terror', 'terror', 'Juegos de horror y suspenso'),
('Sigilo', 'sigilo', 'Juegos basados en infiltración'),
('Música', 'musica', 'Juegos rítmicos y musicales'),
('Party', 'party', 'Juegos multijugador casual'),
('MMORPG', 'mmorpg', 'Juegos de rol multijugador masivo'),
('Survival', 'survival', 'Juegos de supervivencia'),
('Sandbox', 'sandbox', 'Juegos de mundo abierto y creación'),
('Roguelike', 'roguelike', 'Juegos con muerte permanente y niveles procedurales'),
('Metroidvania', 'metroidvania', 'Juegos de exploración no lineal')
ON DUPLICATE KEY UPDATE name=VALUES(name);
```

✅ **Verifica**: Debe decir "20 filas insertadas"

---

#### Script 7: Asignar Consolas y Géneros a Productos Existentes

⚠️ **IMPORTANTE**: Este script asigna automáticamente basándose en nombres de productos.

Si tus productos tienen nombres exactos como:
- Kingdom Hearts II
- Super Mario 64
- The Legend of Zelda: Ocarina of Time
- Metal Gear Solid
- Chrono Trigger

Ejecuta esto:

```sql
-- Asignar consolas
UPDATE products SET console_id = (SELECT id FROM consoles WHERE name = 'PlayStation 2' LIMIT 1)
WHERE name LIKE '%Kingdom Hearts%' AND is_active = TRUE;

UPDATE products SET console_id = (SELECT id FROM consoles WHERE name = 'Nintendo 64' LIMIT 1)
WHERE name LIKE '%Super Mario 64%' AND is_active = TRUE;

UPDATE products SET console_id = (SELECT id FROM consoles WHERE name = 'Nintendo 64' LIMIT 1)
WHERE name LIKE '%Zelda%' AND name LIKE '%Ocarina%' AND is_active = TRUE;

UPDATE products SET console_id = (SELECT id FROM consoles WHERE name = 'PlayStation' LIMIT 1)
WHERE name LIKE '%Metal Gear Solid%' AND is_active = TRUE;

UPDATE products SET console_id = (SELECT id FROM consoles WHERE name = 'SNES' LIMIT 1)
WHERE name LIKE '%Chrono Trigger%' AND is_active = TRUE;

-- Asignar géneros a Kingdom Hearts II
INSERT INTO product_genres (product_id, genre_id)
SELECT p.id, g.id 
FROM products p, genres g
WHERE p.name LIKE '%Kingdom Hearts%' 
AND g.slug IN ('accion', 'aventura', 'rpg')
AND p.is_active = TRUE
AND NOT EXISTS (
    SELECT 1 FROM product_genres pg 
    WHERE pg.product_id = p.id AND pg.genre_id = g.id
);

-- Asignar géneros a Super Mario 64
INSERT INTO product_genres (product_id, genre_id)
SELECT p.id, g.id 
FROM products p, genres g
WHERE p.name LIKE '%Super Mario 64%' 
AND g.slug IN ('plataformas', 'accion', 'aventura')
AND p.is_active = TRUE
AND NOT EXISTS (
    SELECT 1 FROM product_genres pg 
    WHERE pg.product_id = p.id AND pg.genre_id = g.id
);

-- Asignar géneros a Zelda Ocarina
INSERT INTO product_genres (product_id, genre_id)
SELECT p.id, g.id 
FROM products p, genres g
WHERE p.name LIKE '%Zelda%' AND p.name LIKE '%Ocarina%'
AND g.slug IN ('aventura', 'accion', 'rpg')
AND p.is_active = TRUE
AND NOT EXISTS (
    SELECT 1 FROM product_genres pg 
    WHERE pg.product_id = p.id AND pg.genre_id = g.id
);

-- Asignar géneros a Metal Gear Solid
INSERT INTO product_genres (product_id, genre_id)
SELECT p.id, g.id 
FROM products p, genres g
WHERE p.name LIKE '%Metal Gear Solid%'
AND g.slug IN ('accion', 'sigilo')
AND p.is_active = TRUE
AND NOT EXISTS (
    SELECT 1 FROM product_genres pg 
    WHERE pg.product_id = p.id AND pg.genre_id = g.id
);

-- Asignar géneros a Chrono Trigger
INSERT INTO product_genres (product_id, genre_id)
SELECT p.id, g.id 
FROM products p, genres g
WHERE p.name LIKE '%Chrono Trigger%'
AND g.slug IN ('rpg', 'aventura')
AND p.is_active = TRUE
AND NOT EXISTS (
    SELECT 1 FROM product_genres pg 
    WHERE pg.product_id = p.id AND pg.genre_id = g.id
);
```

✅ **Verifica**: Si dice "0 filas afectadas", puede ser normal (significa que los productos ya tienen los datos o los nombres no coinciden exactamente)

---

## 🧪 PARTE 3: VERIFICAR QUE TODO FUNCIONA

### Verificación en Base de Datos:

Ejecuta esta query para ver todos los productos con sus consolas y géneros:

```sql
SELECT 
    p.id,
    p.name as producto,
    co.name as consola,
    GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ') as generos
FROM products p
LEFT JOIN consoles co ON p.console_id = co.id
LEFT JOIN product_genres pg ON p.id = pg.product_id
LEFT JOIN genres g ON pg.genre_id = g.id
WHERE p.is_active = TRUE
GROUP BY p.id, p.name, co.name
ORDER BY p.id;
```

**Debes ver algo como:**

| id | producto | consola | generos |
|----|----------|---------|---------|
| 2 | Kingdom Hearts II | PlayStation 2 | Acción, Aventura, RPG |
| 3 | Super Mario 64 | Nintendo 64 | Acción, Aventura, Plataformas |
| 4 | The Legend of Zelda: Ocarina of Time | Nintendo 64 | Acción, Aventura, RPG |
| 5 | Metal Gear Solid | PlayStation | Acción, Sigilo |
| 6 | Chrono Trigger | SNES | Aventura, RPG |

---

### Verificación en el Sitio Web:

1. Abre: **https://teal-fish-507993.hostingersite.com/productos.php**

2. **Debes ver:**
   - ✅ Filtro de **CONSOLAS** con opciones (Nintendo 64, SNES, PlayStation, PlayStation 2)
   - ✅ Filtro de **GÉNEROS** con opciones (Acción, Aventura, RPG, Plataformas, Sigilo)
   - ✅ Los 5 productos mostrándose correctamente

3. **Prueba los filtros:**
   - Marca **PlayStation 2** → Solo debe aparecer Kingdom Hearts II
   - Marca **RPG** → Deben aparecer: Kingdom Hearts II, Zelda Ocarina, Chrono Trigger
   - Marca **Nintendo 64** + **Acción** → Deben aparecer: Super Mario 64, Zelda Ocarina

---

## ❓ TROUBLESHOOTING

### Problema: "No aparecen los filtros de Consolas o Géneros"

**Solución:**
1. Verifica que subiste `smart_filters_v2.php` correctamente
2. Verifica que `productos.php` incluye la línea: `require_once 'includes/smart_filters_v2.php';`

---

### Problema: "Al filtrar no muestra productos"

**Solución:**
Ejecuta esta query para ver si los productos tienen console_id asignado:

```sql
SELECT id, name, console_id FROM products WHERE is_active = TRUE;
```

Si `console_id` es `NULL`, debes asignarlos manualmente:

```sql
-- Ver IDs de consolas disponibles
SELECT id, name FROM consoles;

-- Asignar manualmente (ajusta los IDs según tu caso)
UPDATE products SET console_id = 14 WHERE id = 2;  -- Kingdom Hearts → PS2
UPDATE products SET console_id = 3 WHERE id = 3;   -- Mario 64 → N64
-- etc.
```

---

### Problema: "Error 500 en el sitio"

**Solución:**
1. Verifica que subiste los 3 archivos PHP correctamente
2. Revisa el archivo de logs de PHP en Hostinger:
   - File Manager → `public_html/error_log`

---

## 🎉 ¡LISTO!

Una vez que hayas completado estos pasos, tu sistema de filtros estará funcionando en producción.

**URLs de prueba:**
- Filtrar por Nintendo 64: `https://teal-fish-507993.hostingersite.com/productos.php?consoles=3`
- Filtrar por RPG: `https://teal-fish-507993.hostingersite.com/productos.php?genres=3`
- Filtrar por Nintendo 64 + Acción: `https://teal-fish-507993.hostingersite.com/productos.php?consoles=3&genres=1`

---

**¿Necesitas ayuda?** Envíame capturas de pantalla de:
- La página productos.php
- Los resultados de las queries de verificación
- Cualquier mensaje de error que veas
