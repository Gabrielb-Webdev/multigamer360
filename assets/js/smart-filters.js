/**
 * =====================================================
 * SISTEMA DE FILTROS INTELIGENTES - JAVASCRIPT
 * =====================================================
 * 
 * Maneja la lógica de filtros compatibles y el estado del botón "Aplicar"
 * Solo permite aplicar filtros cuando hay cambios pendientes
 */

class SmartFilterSystem {
    constructor() {
        this.initialState = null;
        this.currentState = null;
        this.init();
    }
    
    init() {
        // Capturar estado inicial de la página
        this.initialState = this.captureCurrentState();
        this.currentState = JSON.parse(JSON.stringify(this.initialState));
        
        // Detectar cambios en checkboxes de filtros
        document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.onFilterChange());
        });
        
        // Detectar cambios en inputs de precio
        const minPrice = document.getElementById('price-min');
        const maxPrice = document.getElementById('price-max');
        
        if (minPrice) {
            minPrice.addEventListener('input', () => this.onFilterChange());
        }
        if (maxPrice) {
            maxPrice.addEventListener('input', () => this.onFilterChange());
        }
        
        // Inicializar estado del botón
        this.updateApplyButton();
        
        console.log('✅ SmartFilterSystem inicializado');
    }
    
    /**
     * Capturar estado actual de los filtros
     */
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
    
    /**
     * Obtener valores seleccionados de un grupo de checkboxes
     */
    getSelectedValues(selector) {
        return Array.from(document.querySelectorAll(`${selector}:checked`))
            .map(cb => cb.value);
    }
    
    /**
     * Manejador cuando cambia algún filtro
     */
    onFilterChange() {
        this.currentState = this.captureCurrentState();
        this.updateApplyButton();
        
        // Log para debug
        console.log('🔄 Filtros cambiados:', this.currentState);
    }
    
    /**
     * Verificar si hay cambios pendientes
     */
    hasChanges() {
        return JSON.stringify(this.initialState) !== JSON.stringify(this.currentState);
    }
    
    /**
     * Contar cambios totales
     */
    countChanges() {
        let count = 0;
        
        count += this.currentState.categories.length;
        count += this.currentState.brands.length;
        count += this.currentState.consoles.length;
        count += this.currentState.genres.length;
        
        if (this.currentState.minPrice || this.currentState.maxPrice) {
            count += 1;
        }
        
        return count;
    }
    
    /**
     * Actualizar estado del botón "Aplicar Filtros"
     */
    updateApplyButton() {
        const applyBtn = document.getElementById('apply-filters-btn');
        const filterCount = document.getElementById('filter-count');
        const hasChanges = this.hasChanges();
        
        if (applyBtn) {
            // Habilitar/deshabilitar según cambios
            applyBtn.disabled = !hasChanges;
            
            // Cambiar clase visual
            if (hasChanges) {
                applyBtn.classList.remove('disabled');
                applyBtn.classList.add('active');
            } else {
                applyBtn.classList.add('disabled');
                applyBtn.classList.remove('active');
            }
            
            // Actualizar contador de filtros
            if (filterCount) {
                const count = this.countChanges();
                if (count > 0) {
                    filterCount.textContent = count;
                    filterCount.style.display = 'inline';
                } else {
                    filterCount.style.display = 'none';
                }
            }
        }
    }
    
    /**
     * Aplicar filtros (construir URL y redirigir)
     */
    applyFilters() {
        if (!this.hasChanges()) {
            console.log('⚠️ No hay cambios pendientes');
            return;
        }
        
        console.log('✅ Aplicando filtros:', this.currentState);
        
        // Construir nueva URL
        const url = new URL(window.location.origin + window.location.pathname);
        
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
        
        // Preservar búsqueda si existe
        const searchParam = new URLSearchParams(window.location.search).get('search');
        if (searchParam) {
            url.searchParams.set('search', searchParam);
        }
        
        // Redirigir
        window.location.href = url.toString();
    }
    
    /**
     * Limpiar todos los filtros
     */
    clearFilters() {
        console.log('🧹 Limpiando filtros');
        
        // Desmarcar todos los checkboxes
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        
        // Limpiar inputs de precio
        const minPrice = document.getElementById('price-min');
        const maxPrice = document.getElementById('price-max');
        if (minPrice) minPrice.value = '';
        if (maxPrice) maxPrice.value = '';
        
        // Redirigir a página limpia (preservar búsqueda si existe)
        const url = new URL(window.location.origin + window.location.pathname);
        const searchParam = new URLSearchParams(window.location.search).get('search');
        if (searchParam) {
            url.searchParams.set('search', searchParam);
        }
        
        window.location.href = url.toString();
    }
}

// =====================================================
// INICIALIZACIÓN Y FUNCIONES GLOBALES
// =====================================================

let smartFilterSystem;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    smartFilterSystem = new SmartFilterSystem();
});

/**
 * Función global para aplicar filtros (llamada desde onclick)
 */
function applyAllFilters() {
    if (smartFilterSystem) {
        smartFilterSystem.applyFilters();
    } else {
        console.error('❌ SmartFilterSystem no inicializado');
    }
}

/**
 * Función global para limpiar filtros (llamada desde onclick)
 */
function clearAllFilters() {
    if (smartFilterSystem) {
        smartFilterSystem.clearFilters();
    } else {
        console.error('❌ SmartFilterSystem no inicializado');
    }
}

/**
 * Función para cambiar ordenamiento
 */
function changeSort() {
    const sortSelect = document.getElementById('sort-products');
    if (sortSelect) {
        const url = new URL(window.location);
        url.searchParams.set('order', sortSelect.value);
        window.location.href = url.toString();
    }
}

/**
 * Función para aplicar filtro de precio
 */
function applyPriceFilter() {
    if (smartFilterSystem) {
        smartFilterSystem.applyFilters();
    }
}

console.log('📦 smart-filters.js cargado correctamente');
