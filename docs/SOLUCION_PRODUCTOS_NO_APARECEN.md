# SOLUCIÓN: Productos no se muestran en productos.php

## 🔍 Problema Detectado

La página `productos.php` en Hostinger no muestra productos aunque existen en la base de datos.

## 🛠️ Cambios Realizados

### 1. **Corrección en `includes/product_manager.php`**

#### a) Método `getProducts()` - Línea 54
- **Antes:** `WHERE p.is_active = TRUE`
- **Después:** `WHERE p.is_active = 1`
- **Razón:** MySQL puede interpretar `TRUE` de forma diferente según la configuración. Usar `1` es más confiable.

#### b) Método `countProducts()` - Líneas 268-343
- **Antes:** 
  - `WHERE p.is_active = TRUE`
  - Filtro de consolas: `p.console IN ($placeholders)` (campo texto antiguo)
  - Filtro de géneros: `p.genre LIKE ?` (búsqueda en texto)
  
- **Después:**
  - `WHERE p.is_active = 1`
  - Filtro de consolas: `p.console_id IN ($placeholders)` (FK a tabla consoles)
  - Filtro de géneros: Usa `EXISTS` con `product_genres` (relación N:N)

### 2. **Corrección en `includes/smart_filters_v2.php`**

Cambiado `TRUE` por `1` en todos los métodos:
- `getAvailableCategories()`
- `getAvailableBrands()`
- `getAvailableConsoles()`
- `getAvailableGenres()`
- `getAvailablePriceRange()`

### 3. **Mejoras en `productos.php`**

Agregado debug mejorado con `error_log()` para registrar:
- Filtros aplicados
- Cantidad de productos obtenidos
- Total count de productos

## 📋 Pasos para Solucionar

### PASO 1: Subir Archivos Corregidos a Hostinger

Debes subir estos archivos modificados a Hostinger (vía FTP o File Manager):

```
includes/product_manager.php
includes/smart_filters_v2.php
productos.php
diagnostico_productos.php (nuevo - para debug)
```

### PASO 2: Ejecutar Diagnóstico

Accede a: `https://teal-fish-507993.hostingersite.com/diagnostico_productos.php`

Este script te mostrará:
- ✅ Si la conexión a DB funciona
- 📊 Estructura de la tabla `products`
- 📦 Cantidad de productos activos
- 🎮 Lista de productos con sus datos
- 🔍 Test del query principal
- 💡 Recomendaciones específicas

### PASO 3: Verificar Resultados

El diagnóstico te dirá exactamente cuál es el problema:

#### **Escenario A: No hay productos en DB**
**Síntoma:** "Total productos activos: 0"  
**Solución:** Agregar productos desde el panel admin

#### **Escenario B: Productos existen pero no se muestran**
**Posibles causas:**
1. **Campo `console_id` es NULL**: Si los productos no tienen consola asignada y el JOIN falla
2. **Tabla `consoles` no existe**: Si la estructura nueva no se migró
3. **`category_id` o `brand_id` no válidos**: Referencias a IDs que no existen

### PASO 4: Soluciones Según Diagnóstico

#### Si el problema es `console_id = NULL`:

**Opción 1 - Asignar consolas a productos existentes:**
```sql
-- Ver productos sin consola
SELECT id, name, console_id FROM products WHERE console_id IS NULL;

-- Actualizar productos (ejemplo: asignar PS2 = id 8)
UPDATE products SET console_id = 8 WHERE id IN (1, 2, 3);
```

**Opción 2 - Modificar query para soportar NULL:**
En `product_manager.php`, línea ~54, cambiar:
```php
LEFT JOIN consoles co ON p.console_id = co.id
```

Por:
```php
LEFT JOIN consoles co ON (p.console_id = co.id OR p.console_id IS NULL)
```

#### Si la tabla `consoles` no existe:

Crear la tabla:
```sql
CREATE TABLE IF NOT EXISTS consoles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    manufacturer VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar consolas básicas
INSERT INTO consoles (name, slug, manufacturer) VALUES
('PlayStation 2', 'playstation-2', 'Sony'),
('Nintendo 64', 'nintendo-64', 'Nintendo'),
('Super Nintendo', 'super-nintendo', 'Nintendo'),
('Xbox', 'xbox', 'Microsoft'),
('GameCube', 'gamecube', 'Nintendo');
```

#### Si faltan categorías o marcas:

Ver referencias:
```sql
-- Productos con categoría inválida
SELECT p.id, p.name, p.category_id 
FROM products p 
LEFT JOIN categories c ON p.category_id = c.id 
WHERE c.id IS NULL;

-- Productos con marca inválida  
SELECT p.id, p.name, p.brand_id 
FROM products p 
LEFT JOIN brands b ON p.brand_id = b.id 
WHERE b.id IS NULL;
```

## 🎯 Verificación Final

Después de aplicar las correcciones:

1. Accede a `productos.php`
2. Abre Consola del Navegador (F12)
3. Busca los mensajes de debug:
   ```
   📸 Encontradas X imágenes de fondo
   ```
   - Si X > 0: ✅ **PROBLEMA RESUELTO**
   - Si X = 0: Ver logs de PHP o ejecutar diagnóstico nuevamente

## 📝 Archivos Afectados

| Archivo | Cambio | Criticidad |
|---------|--------|------------|
| `includes/product_manager.php` | Corrección de queries y filtros | 🔴 CRÍTICO |
| `includes/smart_filters_v2.php` | Cambio TRUE → 1 | 🔴 CRÍTICO |
| `productos.php` | Mejora de debug | 🟡 IMPORTANTE |
| `diagnostico_productos.php` | Nuevo archivo de diagnóstico | 🟢 HERRAMIENTA |

## 🚨 Importante

- **HACER BACKUP** de los archivos antes de subir los cambios
- **PROBAR EN LOCAL** primero (si tienes XAMPP configurado)
- **REVISAR LOGS** de PHP en Hostinger después de subir cambios

## 📞 Próximos Pasos

1. Sube archivos a Hostinger
2. Ejecuta `diagnostico_productos.php`
3. Comparte el resultado conmigo
4. Aplicaremos la solución específica según el diagnóstico

---

**Fecha de corrección:** <?php echo date('Y-m-d'); ?>  
**Archivos modificados:** 3 archivos principales + 1 diagnóstico
