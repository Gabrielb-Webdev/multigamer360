/**
 * productos-filtros.js - Manejo de filtros y productos
 * Sistema de filtrado mejorado para MultiGamer360
 */

// Estado global de filtros
const filterState = {
    pendingFilters: {
        categories: [],
        brands: [],
        tags: [],
        consoles: [],
        genres: [],
        minPrice: '',
        maxPrice: ''
    },
    filtersChanged: false
};

// Variables globales para compatibilidad con código existente
let pendingFilters = {
    categories: [],
    brands: [],
    tags: [],
    consoles: [],
    genres: [],
    minPrice: '',
    maxPrice: ''
};
let filtersChanged = false;

// Sincronizar con el estado interno
function syncGlobalState() {
    pendingFilters = filterState.pendingFilters;
    filtersChanged = filterState.filtersChanged;
    
    // Exponer globalmente
    window.pendingFilters = pendingFilters;
    window.filtersChanged = filtersChanged;
}

// ===================================================
// MANEJO DE FILTROS
// ===================================================

function updateFilter(filterType, value, checked) {
    try {
        const filters = filterState.pendingFilters;
        let targetArray;

        switch(filterType) {
            case 'category':
                targetArray = filters.categories;
                break;
            case 'brand':
                targetArray = filters.brands;
                break;
            case 'tag':
                targetArray = filters.tags;
                break;
            case 'console':
                targetArray = filters.consoles;
                break;
            case 'genre':
                targetArray = filters.genres;
                break;
            default:
                console.error('❌ Tipo de filtro no válido:', filterType);
                return;
        }

        if (checked && !targetArray.includes(value)) {
            targetArray.push(value);
        } else if (!checked) {
            const index = targetArray.indexOf(value);
            if (index > -1) targetArray.splice(index, 1);
        }

        filterState.filtersChanged = true;
        syncGlobalState(); // Sincronizar estado global
        updateFilterButtons();
        console.log('📝 Filtro actualizado:', filterType, value, checked);
    } catch (error) {
        console.error('❌ Error en updateFilter:', error);
    }
}

function updateTagFilter(tag, checked) {
    try {
        // Auto-expandir el filtro dinámico correspondiente
        if (checked) {
            const tagCheckbox = document.querySelector(`input[value="${tag}"]`);
            if (tagCheckbox) {
                const categoryAttribute = tagCheckbox.getAttribute('data-category');
                if (categoryAttribute) {
                    const dynamicFilterId = document.querySelector(`[id^="dynamic-filter-"]`)?.id;
                    if (dynamicFilterId) {
                        expandFilter(dynamicFilterId);
                    }
                }
            }
        }

        updateFilter('tag', tag, checked);
    } catch (error) {
        console.error('❌ Error en updateTagFilter:', error);
    }
}

function applyPriceFilter() {
    try {
        const minPriceInput = document.getElementById('price-min');
        const maxPriceInput = document.getElementById('price-max');
        
        if (!minPriceInput || !maxPriceInput) {
            console.error('❌ No se encontraron los campos de precio');
            return;
        }

        filterState.pendingFilters.minPrice = minPriceInput.value;
        filterState.pendingFilters.maxPrice = maxPriceInput.value;
        filterState.filtersChanged = true;
        syncGlobalState(); // Sincronizar estado global
        updateFilterButtons();
    } catch (error) {
        console.error('❌ Error en applyPriceFilter:', error);
    }
}

// ===================================================
// APLICACIÓN Y LIMPIEZA DE FILTROS
// ===================================================

function applyAllFilters() {
    try {
        const url = new URL(window.location);
        const filters = filterState.pendingFilters;

        // Limpiar parámetros existentes
        ['category', 'brand', 'tags[]', 'tags', 'min_price', 'max_price', 'page'].forEach(param => {
            url.searchParams.delete(param);
        });

        // Aplicar filtros
        if (filters.categories.length > 0) {
            url.searchParams.set('category', filters.categories[0]);
        }
        
        if (filters.brands.length > 0) {
            url.searchParams.set('brand', filters.brands[0]);
        }
        
        if (filters.tags.length > 0) {
            filters.tags.forEach(tag => {
                url.searchParams.append('tags[]', tag);
            });
        }
        
        if (filters.minPrice) {
            url.searchParams.set('min_price', filters.minPrice);
        }
        
        if (filters.maxPrice) {
            url.searchParams.set('max_price', filters.maxPrice);
        }

        console.log('🔄 Aplicando filtros:', filters);
        window.location.href = url.toString();
    } catch (error) {
        console.error('❌ Error en applyAllFilters:', error);
    }
}

function clearAllFilters() {
    try {
        // Desmarcar todos los checkboxes
        ['category-filter', 'brand-filter', 'tag-filter', 'console-filter', 'genre-filter'].forEach(className => {
            document.querySelectorAll(`.${className}`).forEach(cb => cb.checked = false);
        });

        // Limpiar campos de precio
        const minPriceInput = document.getElementById('price-min');
        const maxPriceInput = document.getElementById('price-max');
        if (minPriceInput) minPriceInput.value = '';
        if (maxPriceInput) maxPriceInput.value = '';

        // Resetear estado de filtros
        filterState.pendingFilters = {
            categories: [],
            brands: [],
            tags: [],
            consoles: [],
            genres: [],
            minPrice: '',
            maxPrice: ''
        };
        filterState.filtersChanged = false;
        syncGlobalState(); // Sincronizar estado global
        updateFilterButtons();

        // Redirigir a la página sin filtros PERO manteniendo la categoría (consola)
        const url = new URL(window.location);
        const search = url.searchParams.get('search');
        const category = url.searchParams.get('category'); // Preservar categoría
        url.search = '';
        if (category) {
            url.searchParams.set('category', category); // Mantener categoría
        }
        if (search) {
            url.searchParams.set('search', search);
        }
        window.location.href = url.toString();
    } catch (error) {
        console.error('❌ Error en clearAllFilters:', error);
    }
}

// ===================================================
// INTERFAZ DE USUARIO
// ===================================================

function updateFilterButtons() {
    const applyBtn = document.getElementById('apply-filters-btn');
    const clearBtn = document.getElementById('clear-filters-btn');
    const filterCount = document.getElementById('filter-count');

    if (applyBtn && clearBtn) {
        const filters = filterState.pendingFilters;
        const totalFilters = 
            filters.categories.length + 
            filters.brands.length + 
            filters.tags.length + 
            filters.consoles.length +
            filters.genres.length +
            (filters.minPrice ? 1 : 0) + 
            (filters.maxPrice ? 1 : 0);

        // Mostrar botón "Aplicar Filtros" si hay cambios pendientes
        if (filterState.filtersChanged) {
            applyBtn.style.display = 'block';
            if (filterCount && totalFilters > 0) {
                filterCount.textContent = totalFilters;
                filterCount.style.display = 'inline';
            } else if (filterCount) {
                filterCount.style.display = 'none';
            }
        } else {
            applyBtn.style.display = 'none';
            if (filterCount) {
                filterCount.style.display = 'none';
            }
        }
        
        // Mostrar botón "Limpiar Filtros" si hay filtros activos
        if (totalFilters > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
        }
    }
}

// ===================================================
// FUNCIONES ADICIONALES
// ===================================================

// Función para expandir filtros dinámicos (requerida por updateTagFilter)
function expandFilter(filterId) {
    try {
        const filterElement = document.getElementById(filterId);
        if (filterElement) {
            // Buscar el elemento colapsible
            const collapseElement = filterElement.querySelector('.collapse');
            if (collapseElement && !collapseElement.classList.contains('show')) {
                collapseElement.classList.add('show');
                console.log('📖 Filtro expandido:', filterId);
            }
        }
    } catch (error) {
        console.error('❌ Error expandiendo filtro:', error);
    }
}

// Función para cambiar ordenamiento
function changeSort() {
    try {
        const sortSelect = document.getElementById('sort-products');
        if (sortSelect) {
            const url = new URL(window.location);
            url.searchParams.set('order', sortSelect.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
    } catch (error) {
        console.error('❌ Error en changeSort:', error);
    }
}

// Función para limpiar filtros (compatibilidad)
function clearFilters() {
    clearAllFilters();
}

// ===================================================
// INICIALIZACIÓN
// ===================================================

// Inicializar el sistema de filtros cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('🔧 Inicializando sistema de filtros...');
    
    // Sincronizar estado global
    syncGlobalState();
    
    // Exponer funciones globalmente
    window.updateFilter = updateFilter;
    window.updateTagFilter = updateTagFilter;
    window.applyAllFilters = applyAllFilters;
    window.clearAllFilters = clearAllFilters;
    window.clearFilters = clearFilters;
    window.applyPriceFilter = applyPriceFilter;
    window.changeSort = changeSort;
    window.expandFilter = expandFilter;
    window.updateFilterButtons = updateFilterButtons;
    
    // Actualizar interfaz
    updateFilterButtons();
    
    console.log('✅ Sistema de filtros inicializado');
});

// Observer para mantener sincronizado el estado
setInterval(() => {
    if (window.pendingFilters !== pendingFilters || window.filtersChanged !== filtersChanged) {
        syncGlobalState();
    }
}, 100);