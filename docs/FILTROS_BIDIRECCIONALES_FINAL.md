# 🎯 LÓGICA DE FILTROS BIDIRECCIONALES - IMPLEMENTACIÓN FINAL

## 📋 Reglas de Negocio

### **CATEGORÍAS y GÉNEROS:**
1. **Videojuegos** → Pueden tener múltiples géneros (Acción, RPG, Aventura, etc.)
2. **Consolas** (hardware físico) → SOLO tienen género invisible `_CONSOLA_` (ID 999)
3. **Accesorios** → NO tienen géneros relevantes

### **Principio Fundamental:**
> "Las consolas NO son de acción, el filtro consola es solo para la consola no para los juegos, es como si tuviera un auricular de acción o accesorio de RPG, no tiene sentido"

---

## 🔄 Comportamiento de Filtros (Bidireccional)

### **Escenario 1: Filtrar por GÉNERO (ej: "Acción")**
- ✅ Sistema **FUERZA** categoría `Videojuegos`
- ✅ Solo muestra videojuegos de ese género
- ✅ Actualiza filtros de Marcas y Consolas disponibles
- ❌ Excluye automáticamente categoría `Consolas`

**Código implementado:**
```php
// Si hay filtro de géneros, FORZAR que solo se muestren videojuegos
if (!empty($filters['genres']) && $videojuegosCategoryId) {
    $filters['categories'] = [$videojuegosCategoryId];
}
```

---

### **Escenario 2: Filtrar por CATEGORÍA "Consolas"**
- ❌ Todos los géneros visibles muestran **0 productos**
- ✅ Los géneros se **deshabilitan visualmente** (opacity 0.4, cursor not-allowed)
- ✅ Solo muestra productos de hardware (PS5, Xbox Series X, etc.)
- ⚠️ El género `_CONSOLA_` existe pero es **invisible** en el frontend

**Código implementado:**
```php
// Si está filtrando categoría "Consolas", todos los géneros tienen 0 productos
$isFilteringConsolesCategory = !empty($filters['categories']) && 
                               !($videojuegosCategoryId && in_array($videojuegosCategoryId, $filters['categories']));

if ($isFilteringConsolesCategory) {
    $genres[] = ['id' => $g['id'], 'name' => $g['name'], 'product_count' => 0];
}
```

---

### **Escenario 3: Filtrar por CONSOLA "PlayStation 5"**
- ✅ Muestra:
  - Consola física PS5 (categoría Consolas, género _CONSOLA_)
  - Videojuegos para PS5 (categoría Videojuegos, géneros variados)
- ✅ Actualiza géneros disponibles (solo los que tienen juegos de PS5)
- ✅ Actualiza marcas disponibles (Sony, etc.)

---

### **Escenario 4: Filtrar por MARCA "Sony"**
- ✅ Muestra tanto consolas Sony como videojuegos Sony
- ✅ Si selecciono también "Acción", filtra videojuegos Sony de Acción
- ✅ Si selecciono categoría "Consolas", solo muestra consolas Sony (géneros en 0)

---

## 🧮 Lógica de Conteo (Cada filtro excluye su propia categoría)

### **Contar MARCAS:**
```php
// Aplicar: Categorías + Consolas + Géneros
// Excluir: Marcas
foreach ($brands as $brand) {
    COUNT productos WHERE brand_id = $brand 
    AND [filtros de categorías]
    AND [filtros de consolas]
    AND [filtros de géneros SI shouldApplyGenres]
}
```

### **Contar CONSOLAS:**
```php
// Aplicar: Categorías + Marcas + Géneros
// Excluir: Consolas
foreach ($consoles as $console) {
    COUNT productos WHERE console_id = $console
    AND [filtros de categorías]
    AND [filtros de marcas]
    AND [filtros de géneros SI shouldApplyGenres]
}
```

### **Contar GÉNEROS:**
```php
// REGLA ESPECIAL: Solo contar Videojuegos
foreach ($genres as $genre) {
    if (filtro categoría Consolas activo) {
        return 0; // Géneros NO aplican a consolas
    }
    
    COUNT productos WHERE category_id = Videojuegos
    AND genre_id = $genre
    AND [filtros de marcas]
    AND [filtros de consolas]
}
```

---

## 📂 Estructura de Base de Datos

### **Tablas principales:**
- `products`: id, name, category_id, brand_id, console_id, is_active
- `product_genres`: product_id, genre_id (relación muchos-a-muchos)
- `genres`: id, name, slug (incluye ID 999 "_CONSOLA_" invisible)
- `categories`: 1=Videojuegos, 3=Consolas

### **Ejemplo de producto CONSOLA PS5:**
```sql
-- products table
id: 123
name: "PlayStation 5"
category_id: 3 (Consolas)
brand_id: 1 (Sony)
console_id: 1 (PlayStation 5)

-- product_genres table
product_id: 123
genre_id: 999 (_CONSOLA_)
```

### **Ejemplo de producto JUEGO God of War:**
```sql
-- products table
id: 456
name: "God of War Ragnarök"
category_id: 1 (Videojuegos)
brand_id: 1 (Sony)
console_id: 1 (PlayStation 5)

-- product_genres table (puede tener múltiples)
product_id: 456, genre_id: 2 (Acción)
product_id: 456, genre_id: 5 (Aventura)
```

---

## 🎨 Frontend (JavaScript)

### **Función principal: `updateFilterCounts(filters)`**
```javascript
// 1. Habilitar todos los checkboxes
document.querySelectorAll('.genre-filter').forEach(cb => {
    cb.disabled = false;
    cb.parentElement.style.opacity = '1';
});

// 2. Deshabilitar los que tienen product_count = 0
filters.genres.forEach(genre => {
    if (genre.product_count === 0) {
        checkbox.disabled = true;
        checkbox.parentElement.style.opacity = '0.4';
        checkbox.parentElement.title = 'No hay productos disponibles';
    }
});
```

### **Detección de categoría Consolas:**
```javascript
const allGenresDisabled = filters.genres && 
                          filters.genres.every(g => g.product_count === 0);

if (allGenresDisabled) {
    console.log('⚠️ CATEGORÍA CONSOLAS ACTIVA');
}
```

---

## ✅ Casos de Prueba

### **TEST 1: Seleccionar "Acción"**
- ✅ Solo muestra videojuegos de Acción
- ✅ Actualiza consolas disponibles (solo las que tienen juegos de Acción)
- ✅ Actualiza marcas disponibles
- ✅ Categoría Consolas se deshabilita automáticamente

### **TEST 2: Seleccionar "Consolas"**
- ✅ Todos los géneros muestran (0)
- ✅ Todos los géneros se deshabilitan visualmente
- ✅ Solo muestra hardware físico

### **TEST 3: Seleccionar "PlayStation 5"**
- ✅ Muestra consola PS5 + juegos PS5
- ✅ Géneros se actualizan (solo los de juegos PS5)

### **TEST 4: Deseleccionar todo**
- ✅ Todos los filtros vuelven a sus valores originales
- ✅ Todos los checkboxes se habilitan

---

## 📁 Archivos Modificados

1. **`ajax/get-dynamic-filters.php`**
   - Variable `$shouldApplyGenres` (lógica de exclusión)
   - Forzar categoría Videojuegos cuando se filtran géneros
   - Contar géneros con 0 si categoría Consolas activa

2. **`productos.php`**
   - Función `updateFilterCounts()` con detección de consolas
   - Console.log para debug visual

3. **`test_filter_logic.php`** (nuevo)
   - Script de prueba para validar la lógica

---

## 🚀 Cómo Probar

1. Abrir: `https://teal-fish-507993.hostingersite.com/test_filter_logic.php`
2. Verificar distribución de productos por categoría
3. Verificar que categoría Videojuegos tiene el ID correcto
4. Confirmar que género _CONSOLA_ (ID 999) existe

5. Ir a productos: `https://teal-fish-507993.hostingersite.com/productos.php`
6. Abrir consola del navegador (F12)
7. Probar los 4 escenarios descritos arriba

---

## 🐛 Debugging

### **Backend (PHP):**
```bash
# Ver logs de errores
tail -f logs/error.log

# O verificar error_log de PHP
tail -f /var/log/php_errors.log
```

### **Frontend (JavaScript):**
```javascript
// Console logs incluidos:
console.log('🔄 Actualizando contadores de filtros:', filters);
console.log('⚠️ CATEGORÍA CONSOLAS ACTIVA: Todos los géneros deshabilitados');
```

### **SQL directo:**
```sql
-- Ver productos con género _CONSOLA_
SELECT p.*, pg.genre_id 
FROM products p 
INNER JOIN product_genres pg ON p.id = pg.product_id 
WHERE pg.genre_id = 999;

-- Ver videojuegos con múltiples géneros
SELECT p.name, GROUP_CONCAT(g.name) as genres
FROM products p 
INNER JOIN product_genres pg ON p.id = pg.product_id 
INNER JOIN genres g ON pg.genre_id = g.id 
WHERE p.category_id = 1 
GROUP BY p.id;
```

---

## ✨ Principios Aplicados

1. ✅ **Bidireccionalidad**: Filtros se afectan mutuamente
2. ✅ **Exclusión lógica**: Cada filtro excluye su propia categoría al contar
3. ✅ **Coherencia semántica**: Consolas no tienen géneros de juegos
4. ✅ **Feedback visual**: Opacity + cursor para indicar disponibilidad
5. ✅ **Forzado de contexto**: Género fuerza categoría Videojuegos
6. ✅ **Invalidación de contexto**: Categoría Consolas invalida géneros

---

**Fecha de implementación:** 2025-10-17  
**Estado:** ✅ IMPLEMENTADO y LISTO PARA PRUEBAS
