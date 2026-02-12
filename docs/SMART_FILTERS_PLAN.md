# 🎯 Sistema de Filtros Inteligentes y Compatibles - Plan de Implementación

## 📋 Requerimientos del Usuario

### Estructura de Filtros Deseada:
1. **Categorías** - Accesorios, Videojuegos, Consolas, etc.
2. **Precio** - Rango mín-máx
3. **Marcas** - Nintendo, Sony, Sega, etc.
4. **Consolas** - PlayStation 4, NES, Xbox One, etc.
5. **Géneros** - Acción, Aventura, RPG, etc. (múltiples por producto)

### Comportamiento Esperado:
- ✅ Filtros se construyen dinámicamente según productos en BD
- ✅ Filtros son **compatibles entre sí** (interdependientes)
- ✅ Si selecciono "Accesorios", solo muestro marcas/consolas/géneros de accesorios
- ✅ Opciones incompatibles se **bloquean/ocultan**
- ✅ Botón "Aplicar Filtros" solo activo cuando hay cambios pendientes
- ❌ Eliminar "Tipo de Producto"

---

## 🏗️ Arquitectura del Sistema

### Archivos Creados/Modificados:

#### 1. `includes/smart_filters.php` ✅ (CREADO)
**Clase SmartFilters**
- `getAvailableCategories($appliedFilters)` - Categorías compatibles
- `getAvailableBrands($appliedFilters)` - Marcas compatibles
- `getAvailableConsoles($appliedFilters)` - Consolas compatibles
- `getAvailableGenres($appliedFilters)` - Géneros compatibles
- `getAvailablePriceRange($appliedFilters)` - Rango de precios
- `getAllAvailableFilters($appliedFilters)` - Todos los filtros de una vez

**Lógica de Compatibilidad:**
```php
// Si ya seleccionaste categoría = "Accesorios"
$appliedFilters = ['categories' => [1]]; // ID de Accesorios

// getAvailableBrands() solo devuelve marcas que tienen productos con category_id = 1
// getAvailableConsoles() solo devuelve consolas de productos con category_id = 1
// etc.
```

#### 2. `productos.php` (MODIFICAR)
**Cambios necesarios:**

**A. Inicialización (líneas ~30-80):**
```php
// ANTES:
require_once 'includes/videogame_filters.php';
$videoGameFilters = new VideoGameFilters($pdo);

// DESPUÉS:
require_once 'includes/smart_filters.php';
$smartFilters = new SmartFilters($pdo);

// Procesar filtros aplicados desde URL
$appliedFilters = [];
if (!empty($_GET['categories'])) {
    $appliedFilters['categories'] = explode(',', $_GET['categories']);
}
if (!empty($_GET['brands'])) {
    $appliedFilters['brands'] = explode(',', $_GET['brands']);
}
// ... etc

// Obtener filtros disponibles (solo compatibles)
$availableFilters = $smartFilters->getAllAvailableFilters($appliedFilters);
```

**B. Sección de Filtros (líneas ~200-400):**
```php
<!-- Filtros por Categoría -->
<div class="filter-section">
    <h5 class="filter-title">
        <i class="fas fa-th-large"></i> Categorías
    </h5>
    <div class="filter-options">
        <?php foreach ($availableFilters['categories'] as $category): ?>
        <div class="filter-option">
            <input type="checkbox" 
                   id="cat_<?php echo $category['id']; ?>" 
                   class="filter-checkbox category-filter"
                   value="<?php echo $category['id']; ?>"
                   data-name="<?php echo htmlspecialchars($category['name']); ?>"
                   <?php echo (in_array($category['id'], $appliedFilters['categories'] ?? [])) ? 'checked' : ''; ?>>
            <label for="cat_<?php echo $category['id']; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
                <span class="filter-count">(<?php echo $category['product_count']; ?>)</span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Filtros por Precio -->
<div class="filter-section">
    <h5 class="filter-title">
        <i class="fas fa-dollar-sign"></i> Precio
    </h5>
    <div class="filter-options">
        <div class="price-range-info">
            <small>Rango: $<?php echo number_format($availableFilters['price_range']['min'], 0); ?> - $<?php echo number_format($availableFilters['price_range']['max'], 0); ?></small>
        </div>
        <div class="price-inputs">
            <input type="number" 
                   id="price-min" 
                   class="price-input"
                   placeholder="Mínimo"
                   min="<?php echo $availableFilters['price_range']['min']; ?>"
                   max="<?php echo $availableFilters['price_range']['max']; ?>"
                   value="<?php echo $_GET['min_price'] ?? ''; ?>">
            <span class="price-separator">-</span>
            <input type="number" 
                   id="price-max" 
                   class="price-input"
                   placeholder="Máximo"
                   min="<?php echo $availableFilters['price_range']['min']; ?>"
                   max="<?php echo $availableFilters['price_range']['max']; ?>"
                   value="<?php echo $_GET['max_price'] ?? ''; ?>">
        </div>
    </div>
</div>

<!-- Filtros por Marca -->
<div class="filter-section">
    <h5 class="filter-title">
        <i class="fas fa-industry"></i> Marcas
    </h5>
    <div class="filter-options">
        <?php foreach ($availableFilters['brands'] as $brand): ?>
        <div class="filter-option">
            <input type="checkbox" 
                   id="brand_<?php echo $brand['id']; ?>" 
                   class="filter-checkbox brand-filter"
                   value="<?php echo $brand['id']; ?>"
                   data-name="<?php echo htmlspecialchars($brand['name']); ?>"
                   <?php echo (in_array($brand['id'], $appliedFilters['brands'] ?? [])) ? 'checked' : ''; ?>>
            <label for="brand_<?php echo $brand['id']; ?>">
                <?php echo htmlspecialchars($brand['name']); ?>
                <span class="filter-count">(<?php echo $brand['product_count']; ?>)</span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Filtros por Consola -->
<div class="filter-section">
    <h5 class="filter-title">
        <i class="fas fa-gamepad"></i> Consolas
    </h5>
    <div class="filter-options">
        <?php foreach ($availableFilters['consoles'] as $console): ?>
        <div class="filter-option">
            <input type="checkbox" 
                   id="console_<?php echo md5($console['slug']); ?>" 
                   class="filter-checkbox console-filter"
                   value="<?php echo htmlspecialchars($console['name']); ?>"
                   data-slug="<?php echo $console['slug']; ?>"
                   <?php echo (in_array($console['name'], $appliedFilters['consoles'] ?? [])) ? 'checked' : ''; ?>>
            <label for="console_<?php echo md5($console['slug']); ?>">
                <?php echo htmlspecialchars($console['name']); ?>
                <span class="filter-count">(<?php echo $console['product_count']; ?>)</span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Filtros por Género -->
<div class="filter-section">
    <h5 class="filter-title">
        <i class="fas fa-tags"></i> Géneros
    </h5>
    <div class="filter-options">
        <?php foreach ($availableFilters['genres'] as $genre): ?>
        <div class="filter-option">
            <input type="checkbox" 
                   id="genre_<?php echo md5($genre['slug']); ?>" 
                   class="filter-checkbox genre-filter"
                   value="<?php echo htmlspecialchars($genre['name']); ?>"
                   data-slug="<?php echo $genre['slug']; ?>"
                   <?php echo (in_array($genre['name'], $appliedFilters['genres'] ?? [])) ? 'checked' : ''; ?>>
            <label for="genre_<?php echo md5($genre['slug']); ?>">
                <?php echo htmlspecialchars($genre['name']); ?>
                <span class="filter-count">(<?php echo $genre['product_count']; ?>)</span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

#### 3. `assets/js/smart-filters.js` (CREAR NUEVO)
**JavaScript para filtros inteligentes:**

```javascript
/**
 * Sistema de Filtros Inteligentes
 * Maneja la compatibilidad entre filtros y el estado del botón "Aplicar"
 */

class SmartFilterSystem {
    constructor() {
        this.initialState = this.captureCurrentState();
        this.currentState = {...this.initialState};
        this.init();
    }
    
    init() {
        // Detectar cambios en checkboxes
        document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.onFilterChange());
        });
        
        // Detectar cambios en inputs de precio
        document.querySelectorAll('.price-input').forEach(input => {
            input.addEventListener('input', () => this.onFilterChange());
        });
        
        // Inicializar estado del botón
        this.updateApplyButton();
    }
    
    captureCurrentState() {
        return {
            categories: this.getSelectedValues('.category-filter'),
            brands: this.getSelectedValues('.brand-filter'),
            consoles: this.getSelectedValues('.console-filter'),
            genres: this.getSelectedValues('.genre-filter'),
            minPrice: document.getElementById('price-min')?.value || '',
            maxPrice: document.getElementById('price-max')?.value || ''
        };
    }
    
    getSelectedValues(selector) {
        return Array.from(document.querySelectorAll(`${selector}:checked`))
            .map(cb => cb.value);
    }
    
    onFilterChange() {
        this.currentState = this.captureCurrentState();
        this.updateApplyButton();
        
        // Aquí podrías agregar lógica AJAX para recargar filtros compatibles
        // sin recargar la página (opcional, más avanzado)
    }
    
    hasChanges() {
        return JSON.stringify(this.initialState) !== JSON.stringify(this.currentState);
    }
    
    updateApplyButton() {
        const applyBtn = document.getElementById('apply-filters-btn');
        const hasChanges = this.hasChanges();
        
        if (applyBtn) {
            applyBtn.disabled = !hasChanges;
            applyBtn.classList.toggle('disabled', !hasChanges);
            
            if (hasChanges) {
                const changeCount = this.countChanges();
                document.getElementById('filter-count').textContent = changeCount;
                document.getElementById('filter-count').style.display = 'inline';
            } else {
                document.getElementById('filter-count').style.display = 'none';
            }
        }
    }
    
    countChanges() {
        let count = 0;
        
        // Contar categorías
        count += this.currentState.categories.length;
        
        // Contar marcas
        count += this.currentState.brands.length;
        
        // Contar consolas
        count += this.currentState.consoles.length;
        
        // Contar géneros
        count += this.currentState.genres.length;
        
        // Contar precio (si está aplicado)
        if (this.currentState.minPrice || this.currentState.maxPrice) {
            count += 1;
        }
        
        return count;
    }
    
    applyFilters() {
        if (!this.hasChanges()) return;
        
        // Construir URL con filtros
        const url = new URL(window.location);
        url.search = ''; // Limpiar parámetros actuales
        
        // Agregar categorías
        if (this.currentState.categories.length > 0) {
            url.searchParams.set('categories', this.currentState.categories.join(','));
        }
        
        // Agregar marcas
        if (this.currentState.brands.length > 0) {
            url.searchParams.set('brands', this.currentState.brands.join(','));
        }
        
        // Agregar consolas
        if (this.currentState.consoles.length > 0) {
            url.searchParams.set('consoles', this.currentState.consoles.join(','));
        }
        
        // Agregar géneros
        if (this.currentState.genres.length > 0) {
            url.searchParams.set('genres', this.currentState.genres.join(','));
        }
        
        // Agregar precio
        if (this.currentState.minPrice) {
            url.searchParams.set('min_price', this.currentState.minPrice);
        }
        if (this.currentState.maxPrice) {
            url.searchParams.set('max_price', this.currentState.maxPrice);
        }
        
        // Redirigir
        window.location.href = url.toString();
    }
    
    clearFilters() {
        // Limpiar todos los filtros
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('price-min').value = '';
        document.getElementById('price-max').value = '';
        
        // Redirigir a página limpia
        window.location.href = 'productos.php';
    }
}

// Inicializar sistema
let smartFilterSystem;
document.addEventListener('DOMContentLoaded', () => {
    smartFilterSystem = new SmartFilterSystem();
});

// Funciones globales para botones
function applyAllFilters() {
    if (smartFilterSystem) {
        smartFilterSystem.applyFilters();
    }
}

function clearAllFilters() {
    if (smartFilterSystem) {
        smartFilterSystem.clearFilters();
    }
}
```

---

## 🔄 Flujo de Funcionamiento

### 1. Carga Inicial
```
Usuario → productos.php
    ↓
Se leen filtros aplicados desde URL
    ↓
SmartFilters calcula filtros disponibles compatibles
    ↓
Se renderiza HTML con filtros
    ↓
JavaScript inicializa SmartFilterSystem
```

### 2. Usuario Selecciona "Accesorios"
```
Usuario marca checkbox "Accesorios"
    ↓
JavaScript detecta cambio
    ↓
Botón "Aplicar Filtros" se activa
    ↓
Usuario hace clic en "Aplicar"
    ↓
Redirección: productos.php?categories=5
    ↓
SmartFilters calcula filtros compatibles con cat=5
    ↓
Solo muestra marcas/consolas/géneros de accesorios
```

### 3. Usuario Agrega Más Filtros
```
Usuario marca "Sony" en marcas
    ↓
Botón "Aplicar" se actualiza (2 filtros pendientes)
    ↓
Usuario hace clic en "Aplicar"
    ↓
Redirección: productos.php?categories=5&brands=3
    ↓
Solo productos que son Accesorios Y de marca Sony
```

---

## ✅ Ventajas del Sistema

1. **Dinámico**: Filtros se construyen según productos en BD
2. **Inteligente**: Solo muestra opciones compatibles
3. **Performante**: Consultas SQL optimizadas
4. **UX Mejorada**: Usuario no ve opciones inválidas
5. **Escalable**: Agregar productos → filtros se actualizan solos

---

## 📝 Tareas Pendientes

- [ ] Modificar `productos.php` completo (es muy largo, requiere reemplazo total)
- [ ] Crear `assets/js/smart-filters.js`
- [ ] Actualizar `product_manager.php` para usar nuevos filtros
- [ ] Modificar consultas SQL para filtrar por múltiples criterios
- [ ] Probar en local
- [ ] Subir a Hostinger

---

**¿Deseas que continúe con la implementación completa?**
Esta es una refactorización grande que tomará tiempo pero dará los resultados que necesitas.
