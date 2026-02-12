# 📋 GUÍA DE USO: SISTEMA DE FILTROS INTELIGENTES

## 🎯 ¿Qué es el Sistema de Filtros Inteligentes?

Es un sistema de filtrado avanzado que muestra **solo las opciones compatibles** basadas en los productos que realmente existen en tu base de datos.

### ✨ Características Principales

1. **Filtros Dinámicos**: Se generan automáticamente desde los productos
2. **Interdependientes**: Al seleccionar "Accesorios", solo muestra marcas que tengan accesorios
3. **Multi-selección**: Puedes seleccionar múltiples categorías, marcas, consolas o géneros
4. **Botón Inteligente**: "Aplicar Filtros" solo se habilita cuando hay cambios pendientes
5. **Contador Visual**: Badge que muestra cuántos filtros están activos

---

## 📂 Estructura del Sistema

```
multigamer360/
├── includes/
│   ├── smart_filters.php          ← Lógica PHP de filtros compatibles
│   └── product_manager.php        ← Extendido para múltiples filtros
├── assets/js/
│   └── smart-filters.js           ← JavaScript para detección de cambios
├── productos.php                  ← Página principal actualizada
└── docs/
    ├── SMART_FILTERS_PLAN.md      ← Documentación técnica completa
    └── SMART_FILTERS_USAGE.md     ← Esta guía
```

---

## 🔧 Cómo Funciona

### Paso 1: Inicialización (productos.php)

```php
// Se crea instancia de SmartFilters
require_once 'includes/smart_filters.php';
$smartFilters = new SmartFilters($pdo);

// Se leen filtros de la URL
$appliedFilters = [];
if (!empty($_GET['categories'])) {
    $appliedFilters['categories'] = array_map('intval', explode(',', $_GET['categories']));
}
// ... similar para brands, consoles, genres

// Se obtienen filtros disponibles
$availableFilters = $smartFilters->getAllAvailableFilters($appliedFilters);
```

### Paso 2: Renderizado HTML

Los filtros se muestran en este orden:

1. **Categorías** - Videojuegos, Accesorios, Consolas, etc.
2. **Precio** - Rango mínimo/máximo
3. **Marcas** - Nintendo, Sony, Sega, etc.
4. **Consolas** - NES, SNES, PlayStation, etc.
5. **Géneros** - Acción, RPG, Aventura, etc.

### Paso 3: Selección del Usuario

Cuando el usuario marca checkboxes:
- El JavaScript captura los cambios
- Compara con el estado inicial
- Habilita el botón "Aplicar Filtros"
- Muestra contador de filtros activos

### Paso 4: Aplicación

Al hacer clic en "Aplicar Filtros":
- Se construye URL con parámetros CSV: `?categories=1,5&brands=2,7&consoles=NES,SNES`
- Se redirige a la misma página
- PHP procesa filtros y muestra productos compatibles

---

## 📝 Ejemplos de URL

### Ejemplo 1: Solo categoría Videojuegos
```
productos.php?categories=1
```

### Ejemplo 2: Accesorios de Nintendo
```
productos.php?categories=2&brands=3
```

### Ejemplo 3: Juegos de NES y SNES, género Acción
```
productos.php?categories=1&consoles=NES,SNES&genres=Acción
```

### Ejemplo 4: Filtro complejo con precio
```
productos.php?categories=1,2&brands=3,7&consoles=NES&min_price=500&max_price=5000
```

---

## 🎨 Cómo Agregar Productos que Aparezcan en Filtros

### Para que un producto aparezca en los filtros:

1. **Categoría**: Asigna `category_id` (1=Videojuegos, 2=Accesorios, etc.)
2. **Marca**: Asigna `brand_id` (enlace a tabla `brands`)
3. **Consola**: Llena campo `console` (ej: "Nintendo NES", "Sony PlayStation")
4. **Género**: Llena campo `genre` (ej: "Acción/Aventura", "RPG")

### Ejemplo de INSERT:

```sql
INSERT INTO products (name, category_id, brand_id, console, genre, price, stock)
VALUES (
    'Super Mario Bros 3',
    1,                    -- category_id: Videojuegos
    3,                    -- brand_id: Nintendo
    'Nintendo NES',       -- console
    'Plataformas/Acción', -- genre (separado por /)
    4500.00,
    10
);
```

Automáticamente:
- ✅ Aparecerá en filtro "Categorías" → Videojuegos
- ✅ Aparecerá en filtro "Marcas" → Nintendo
- ✅ Aparecerá en filtro "Consolas" → Nintendo NES
- ✅ Aparecerá en filtros "Géneros" → Plataformas y Acción

---

## 🧪 Pruebas del Sistema

### Prueba 1: Interdependencia Básica
1. Ir a `productos.php`
2. Seleccionar categoría "Accesorios"
3. Hacer clic en "Aplicar Filtros"
4. **Verificar**: Solo aparecen marcas que tienen accesorios

### Prueba 2: Multi-selección
1. Seleccionar categorías "Videojuegos" y "Accesorios"
2. Hacer clic en "Aplicar Filtros"
3. **Verificar**: URL muestra `?categories=1,2`
4. **Verificar**: Aparecen marcas de ambas categorías

### Prueba 3: Limpiar Filtros
1. Con filtros aplicados, hacer clic en "Limpiar filtros"
2. **Verificar**: Se desmarcan todos los checkboxes
3. **Verificar**: URL vuelve a `productos.php` limpia

### Prueba 4: Botón Deshabilitado
1. Cargar `productos.php` sin filtros
2. **Verificar**: Botón "Aplicar Filtros" está deshabilitado
3. Marcar un checkbox
4. **Verificar**: Botón se habilita automáticamente

---

## 🐛 Debugging

### Ver estado de filtros en consola

El JavaScript muestra logs detallados:

```javascript
console.log('🔄 Filtros cambiados:', this.currentState);
console.log('📊 Estado filtros:', { filtersChanged, totalFilters });
```

### Ver SQL generado

En `smart_filters.php`:

```php
// Descomentar para debug
// echo $sql;
// print_r($params);
```

### Ver filtros disponibles

En `productos.php`:

```php
// Después de $availableFilters = ...
echo '<pre>';
print_r($availableFilters);
echo '</pre>';
```

---

## 🚀 Despliegue a Producción

Ya está pusheado al repositorio. Para forzar actualización en Hostinger:

```
https://multigamer360.com/deploy.php?secret=multigamer360_deploy_2025
```

O espera a que el webhook automático actualice (puede tardar unos minutos).

---

## ❓ FAQ

### ¿Por qué no aparece una marca en los filtros?

**R**: Esa marca no tiene productos activos (`is_active = TRUE`) en la base de datos.

### ¿Puedo agregar más tipos de filtros?

**R**: Sí, edita `smart_filters.php` y agrega un método nuevo como `getAvailableConditions()`.

### ¿El sistema funciona con campos JSON?

**R**: No directamente. Los filtros actuales usan campos estándar (console VARCHAR, genre VARCHAR).

### ¿Cómo cambio el orden de los filtros?

**R**: Edita `productos.php` líneas 220-380 y reordena las secciones HTML.

---

## 📞 Soporte

Para más información técnica, consulta:
- **Documentación completa**: `docs/SMART_FILTERS_PLAN.md`
- **Código fuente**: `includes/smart_filters.php`
- **JavaScript**: `assets/js/smart-filters.js`

---

**Última actualización**: Enero 2025  
**Versión**: 1.0  
**Autor**: Sistema de filtros inteligentes MultiGamer360
