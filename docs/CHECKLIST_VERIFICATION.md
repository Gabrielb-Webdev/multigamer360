# 🔍 CHECKLIST DE VERIFICACIÓN - ¿QUÉ FALTA?

## ✅ COMPLETADO (Según capturas)

- [x] Tablas creadas: `consoles`, `genres`, `product_genres`
- [x] Script `update_existing_products.sql` ejecutado
- [x] 5 productos actualizados con consolas y géneros

---

## ⚠️ VERIFICACIONES PENDIENTES

### 1. Verificar Productos en Base de Datos

Ejecuta `config/diagnostic_complete.sql` para ver:

**Lo que DEBE mostrar:**
- ✅ Kingdom Hearts II → console_id = 14 (PlayStation 2) + géneros: Acción, Aventura, RPG
- ✅ Super Mario 64 → console_id = 3 (Nintendo 64) + géneros: Plataformas, Acción, Aventura
- ✅ Zelda Ocarina → console_id = 3 (Nintendo 64) + géneros: Aventura, Acción, RPG
- ✅ Metal Gear Solid → console_id = 13 (PlayStation) + géneros: Acción, Sigilo
- ✅ Chrono Trigger → console_id = 2 (SNES) + géneros: RPG, Aventura

**Si algún producto tiene `console_id = NULL` → PROBLEMA**

---

### 2. Probar Página productos.php

Abre en navegador: `http://localhost/multigamer360/productos.php`

**Lo que DEBE mostrar:**

#### Sección de Filtros (Sidebar Izquierdo):
- [ ] ✅ **CATEGORÍAS**: Videojuegos (5)
- [ ] ✅ **PRECIO**: Rango mínimo/máximo
- [ ] ✅ **MARCAS**: Nintendo, Sony, Konami, Square Enix, Square
- [ ] ✅ **CONSOLAS**: Nintendo 64, SNES, PlayStation, PlayStation 2, Super Nintendo
- [ ] ✅ **GÉNEROS**: Acción, Aventura, RPG, Plataformas, Sigilo

#### Productos Mostrados:
- [ ] ✅ Ver los 5 productos
- [ ] ✅ Cada producto muestra su imagen
- [ ] ✅ Cada producto muestra su precio

---

### 3. Probar Funcionalidad de Filtros

#### Prueba 1: Filtrar por Consola
1. Marca **PlayStation 2**
2. Clic en **"Aplicar Filtros"**
3. **DEBE MOSTRAR**: Solo Kingdom Hearts II
4. **URL debe ser**: `productos.php?consoles=14`

#### Prueba 2: Filtrar por Género
1. Limpiar filtros
2. Marca **RPG**
3. Clic en **"Aplicar Filtros"**
4. **DEBE MOSTRAR**: Kingdom Hearts II, Zelda Ocarina, Chrono Trigger
5. **URL debe ser**: `productos.php?genres=3` (o el ID de RPG)

#### Prueba 3: Filtrar por Marca
1. Limpiar filtros
2. Marca **Nintendo**
3. Clic en **"Aplicar Filtros"**
4. **DEBE MOSTRAR**: Super Mario 64, Zelda Ocarina, Chrono Trigger

#### Prueba 4: Filtros Múltiples
1. Limpiar filtros
2. Marca **Nintendo 64** + **Acción**
3. Clic en **"Aplicar Filtros"**
4. **DEBE MOSTRAR**: Super Mario 64, Zelda Ocarina
5. **URL debe ser**: `productos.php?consoles=3&genres=1`

---

## 🐛 PROBLEMAS COMUNES Y SOLUCIONES

### ❌ Problema 1: "No aparecen filtros de Consolas o Géneros"

**Causa:** Los productos no tienen `console_id` o géneros asignados

**Solución:**
```sql
-- Verificar si productos tienen console_id
SELECT id, name, console_id FROM products WHERE is_active = TRUE;

-- Si console_id es NULL, ejecutar:
-- config/update_existing_products.sql
```

---

### ❌ Problema 2: "Al filtrar no muestra productos"

**Causa:** SmartFilters está usando la versión antigua

**Solución:** Verificar que `productos.php` incluya:
```php
require_once 'includes/smart_filters_v2.php';
```

No debe decir `smart_filters.php` (sin el _v2)

---

### ❌ Problema 3: "Error de sintaxis PHP"

**Causa:** No se hizo git pull

**Solución:**
```bash
cd f:\xampp\htdocs\multigamer360
git pull origin main
```

---

### ❌ Problema 4: "Filtros aparecen vacíos"

**Causa:** Productos no tienen relaciones correctas

**Solución:**
```sql
-- Ver productos sin consola
SELECT id, name FROM products WHERE is_active = TRUE AND console_id IS NULL;

-- Ver productos sin géneros
SELECT p.id, p.name 
FROM products p
LEFT JOIN product_genres pg ON p.id = pg.product_id
WHERE p.is_active = TRUE 
AND pg.genre_id IS NULL;
```

---

## 📊 QUERIES DE VERIFICACIÓN RÁPIDA

### Query 1: Ver todo de un producto
```sql
SELECT 
    p.*,
    c.name as categoria,
    b.name as marca,
    co.name as consola,
    GROUP_CONCAT(g.name) as generos
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN brands b ON p.brand_id = b.id
LEFT JOIN consoles co ON p.console_id = co.id
LEFT JOIN product_genres pg ON p.id = pg.product_id
LEFT JOIN genres g ON pg.genre_id = g.id
WHERE p.id = 2  -- Kingdom Hearts II
GROUP BY p.id;
```

### Query 2: Ver IDs de consolas para URLs
```sql
SELECT id, name FROM consoles 
WHERE name IN ('Nintendo 64', 'SNES', 'Super Nintendo', 'PlayStation', 'PlayStation 2');
```

### Query 3: Ver IDs de géneros para URLs
```sql
SELECT id, name FROM genres 
WHERE name IN ('Acción', 'Aventura', 'RPG', 'Plataformas', 'Sigilo');
```

---

## ✅ CHECKLIST FINAL

### Base de Datos
- [ ] Tabla `consoles` tiene 30 filas
- [ ] Tabla `genres` tiene 20 filas
- [ ] Tabla `product_genres` tiene al menos 10 filas (5 productos x 2 géneros promedio)
- [ ] Todos los productos activos tienen `console_id` NO NULL
- [ ] Todos los productos activos tienen al menos 1 género asignado

### Código PHP
- [ ] `productos.php` incluye `smart_filters_v2.php`
- [ ] `product_manager.php` está actualizado (usa `console_id` y `product_genres`)
- [ ] No hay errores de sintaxis PHP

### Frontend
- [ ] Página `productos.php` carga sin errores
- [ ] Aparecen los 5 filtros: Categorías, Precio, Marcas, Consolas, Géneros
- [ ] Cada filtro tiene opciones con contador (ej: "Nintendo 64 (2)")
- [ ] Botón "Aplicar Filtros" está deshabilitado inicialmente
- [ ] Al marcar checkboxes, el botón se habilita
- [ ] Al hacer clic en "Aplicar Filtros", la URL cambia
- [ ] Los productos se filtran correctamente

---

## 🎯 PRÓXIMOS PASOS

Una vez que verifiques todo esto:

### Si TODO funciona: ✅
1. Hacer deploy a producción
2. Crear formulario de admin para agregar productos fácilmente
3. Agregar más productos manualmente

### Si algo NO funciona: ⚠️
1. Envíame captura de pantalla del error
2. Ejecuta `diagnostic_complete.sql` y envía resultado
3. Copia el error de la consola del navegador (F12)

---

## 📸 CAPTURAS NECESARIAS

Para ayudarte mejor, necesito ver:

1. **Resultado de `diagnostic_complete.sql`** (ejecutar en phpMyAdmin)
2. **Captura de `productos.php`** (página completa)
3. **Consola del navegador** (F12 → pestaña Console)
4. **Captura de filtros** (sidebar con los 5 filtros)

---

¿Qué resultado te da `diagnostic_complete.sql`? 🔍
