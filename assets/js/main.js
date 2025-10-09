// ================================
// MAIN.JS - FUNCIONALIDAD PRINCIPAL
// ================================

// Declaraciones de funciones al inicio para evitar hoisting issues

// Función para cambiar a navbar-only cuando el scroll llega al 5%
function initHeaderHiding() {
    try {
        const header = document.querySelector('.main-header');
        const body = document.body;
        let ticking = false;
        
        if (!header || !body) {
            console.log('initHeaderHiding: Elementos no encontrados');
            return;
        }
        
        function handleScroll() {
            if (!ticking) {
                requestAnimationFrame(() => {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const documentHeight = document.documentElement.scrollHeight;
                    const windowHeight = window.innerHeight;
                    
                    // Calcular el porcentaje de scroll
                    const scrollPercent = (scrollTop / (documentHeight - windowHeight)) * 100;
                    
                    // Si llegamos al 5% o más, cambiar a navbar-only
                    if (scrollPercent >= 5) {
                        header.classList.add('navbar-only');
                        body.classList.add('navbar-only');
                    } else {
                        header.classList.remove('navbar-only');
                        body.classList.remove('navbar-only');
                    }
                    
                    ticking = false;
                });
                ticking = true;
            }
        }
        
        // Agregar event listener para el scroll
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // Verificar posición inicial
        handleScroll();
    } catch (error) {
        console.error('initHeaderHiding: Error:', error);
    }
}

// Función para inicializar carousels horizontales personalizados
function initHorizontalCarousels() {
    try {
        // Verificar que HorizontalCarousel esté disponible
        if (typeof window.HorizontalCarousel === 'undefined') {
            console.warn('HorizontalCarousel no está disponible aún');
            return;
        }
        
        // Inicializar carousel de productos nuevos
        if (document.getElementById('productCarousel')) {
            new window.HorizontalCarousel('productCarousel', 'productTrack', 'productPrev', 'productNext');
        }
        
        // Inicializar carousel de productos destacados si existe
        if (document.getElementById('featuredCarousel')) {
            new window.HorizontalCarousel('featuredCarousel', 'featuredTrack', 'featuredPrev', 'featuredNext');
        }
    } catch (error) {
        console.error('initHorizontalCarousels: Error:', error);
    }
}

// Función para inicializar funcionalidad de página de productos
function initProductsPage() {
    try {
        // Inicializar filtros
        initProductFilters();
        
        // Inicializar acciones de productos
        initProductActions();
        
        // Inicializar ordenamiento
        initProductSorting();
        
        // Inicializar paginación
        initPagination();
    } catch (error) {
        console.error('initProductsPage: Error:', error);
    }
}

// Funciones auxiliares para productos
function initProductFilters() {
    // Filtro de precio
    const priceApplyBtn = document.querySelector('.price-apply');
    if (priceApplyBtn) {
        priceApplyBtn.addEventListener('click', function() {
            applyPriceFilter();
        });
    }
    
    // Limpiar filtros
    const clearFiltersBtn = document.querySelector('.btn-outline-light');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            clearAllFilters();
        });
    }
}

function initProductActions() {
    // Usar delegación de eventos en el contenedor de productos
    const productsContainer = document.querySelector('.products-grid') || document.body;
    
    // Botones de favoritos
    productsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.closest && e.target.closest('.favorite-btn')) {
            e.preventDefault();
            toggleFavorite(e.target.closest('.favorite-btn'));
        }
    });
    
    // Botones de vista rápida
    productsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.closest && e.target.closest('.quick-view-btn')) {
            openQuickView(e.target.closest('.quick-view-btn'));
        }
    });
}

function initProductSorting() {
    const sortSelect = document.getElementById('sort-products');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortProducts(this.value);
        });
    }
}

function initPagination() {
    const pagination = document.getElementById('products-pagination');
    
    if (!pagination) {
        // La paginación solo existe cuando hay múltiples páginas
        return;
    }
    
    // Delegación de eventos para paginación
    pagination.addEventListener('click', function(e) {
        if (e.target.classList.contains('page-link')) {
            e.preventDefault();
            
            const pageItem = e.target.parentElement;
            const page = parseInt(pageItem.getAttribute('data-page'));
            
            if (pageItem.id === 'prev-page') {
                goToPreviousPage();
            } else if (pageItem.id === 'next-page') {
                goToNextPage();
            } else if (page && !isNaN(page)) {
                goToPage(page);
            }
        }
    });
}

// Funciones auxiliares
function toggleFavorite(button) {
    const icon = button.querySelector('i');
    const isActive = icon.classList.contains('fas');
    
    if (isActive) {
        icon.classList.remove('fas');
        icon.classList.add('far');
        button.setAttribute('title', 'Agregar a favoritos');
    } else {
        icon.classList.remove('far');
        icon.classList.add('fas');
        button.setAttribute('title', 'Quitar de favoritos');
    }
}

function openQuickView(button) {
    console.log('Vista rápida para producto:', button.closest('.product-card'));
}

function applyPriceFilter() {
    const minPrice = parseFloat(document.getElementById('min-price').value) || 0;
    const maxPrice = parseFloat(document.getElementById('max-price').value) || Infinity;
    
    const products = document.querySelectorAll('.product-card');
    let visibleCount = 0;
    
    products.forEach(product => {
        const priceText = product.querySelector('.price').textContent;
        const price = parseFloat(priceText.replace(/[^\d.-]/g, ''));
        
        if (price >= minPrice && price <= maxPrice) {
            product.style.display = 'block';
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });
}

function clearAllFilters() {
    // Limpiar checkboxes
    const checkboxes = document.querySelectorAll('.filter-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    // Limpiar precio
    const minPriceEl = document.getElementById('min-price');
    const maxPriceEl = document.getElementById('max-price');
    if (minPriceEl) minPriceEl.value = '';
    if (maxPriceEl) maxPriceEl.value = '';
    
    // Redirigir manteniendo la categoría (consola) si existe
    const url = new URL(window.location);
    const category = url.searchParams.get('category');
    const search = url.searchParams.get('search');
    url.search = '';
    if (category) {
        url.searchParams.set('category', category); // Preservar categoría
    }
    if (search) {
        url.searchParams.set('search', search);
    }
    window.location.href = url.toString();
}

function sortProducts(sortType) {
    const productsContainer = document.querySelector('.products-grid');
    const products = Array.from(productsContainer.querySelectorAll('.product-card'));
    
    products.sort((a, b) => {
        switch (sortType) {
            case 'price-asc':
                return getProductPrice(a) - getProductPrice(b);
            case 'price-desc':
                return getProductPrice(b) - getProductPrice(a);
            case 'name-asc':
                return getProductName(a).localeCompare(getProductName(b));
            case 'name-desc':
                return getProductName(b).localeCompare(getProductName(a));
            default:
                return 0;
        }
    });
    
    // Reorganizar productos en el DOM
    products.forEach(product => productsContainer.appendChild(product));
}

function getProductPrice(productElement) {
    const priceText = productElement.querySelector('.price').textContent;
    return parseFloat(priceText.replace(/[^\d.-]/g, '')) || 0;
}

function getProductName(productElement) {
    return productElement.querySelector('.product-title').textContent.trim();
}

// Variables de paginación - Encapsuladas para evitar conflictos
window.PaginationVars = window.PaginationVars || {
    currentPage: 1,
    itemsPerPage: 12,
    totalPages: 4
};

function goToPage(page) {
    if (page < 1 || page > window.PaginationVars.totalPages) {
        return;
    }
    
    window.PaginationVars.currentPage = page;
    
    // Obtener todos los productos
    const products = document.querySelectorAll('.product-card');
    const startIndex = (window.PaginationVars.currentPage - 1) * window.PaginationVars.itemsPerPage;
    const endIndex = startIndex + window.PaginationVars.itemsPerPage;
    
    // Mostrar/ocultar productos según la página
    products.forEach((product, index) => {
        if (index >= startIndex && index < endIndex) {
            product.style.display = 'block';
            product.classList.remove('hidden-product');
            product.classList.add('visible-product');
        } else {
            product.style.display = 'none';
            product.classList.add('hidden-product');
            product.classList.remove('visible-product');
        }
    });
}

function goToPreviousPage() {
    if (window.PaginationVars.currentPage > 1) {
        goToPage(window.PaginationVars.currentPage - 1);
    }
}

function goToNextPage() {
    if (window.PaginationVars.currentPage < window.PaginationVars.totalPages) {
        goToPage(window.PaginationVars.currentPage + 1);
    }
}

// Función para controles de carousel Bootstrap
function initCarouselControls() {
    try {
        // Obtener todos los carousels Bootstrap
        const carousels = document.querySelectorAll('.carousel');
        
        carousels.forEach(carousel => {
            const prevBtn = carousel.querySelector('.carousel-control-prev');
            const nextBtn = carousel.querySelector('.carousel-control-next');
            
            if (prevBtn && nextBtn) {
                // Agregar event listeners si es necesario
                console.log('Carousel Bootstrap encontrado:', carousel.id);
            }
        });
    } catch (error) {
        console.error('initCarouselControls: Error:', error);
    }
}

// Función para navegación inteligente con scroll
function initSmartNavigation() {
    const header = document.querySelector('.main-header');
    const body = document.body;
    
    if (!header || !body) {
        console.warn('Elementos de header no encontrados para navegación inteligente');
        return;
    }
    
    // Aplicar clases iniciales
    header.classList.add('header-full');
}

// ====================================
// FIN DE DECLARACIONES DE FUNCIONES
// ====================================

// Event Listeners del DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando aplicación...');
    
    try {
        // Navegación inteligente
        initSmartNavigation();
        
        // Header hiding functionality
        initHeaderHiding();
        
        // Funcionalidad para controles de carousel Bootstrap
        initCarouselControls();
        
        // Inicializar carousels horizontales personalizados
        initHorizontalCarousels();
        
        // Si estamos en la página de productos
        if (document.querySelector('.products-grid')) {
            initProductsPage();
        }
        
        console.log('✅ Aplicación inicializada correctamente');
    } catch (error) {
        console.error('❌ Error al inicializar la aplicación:', error);
    }
});

// Horizontal Carousel Functionality - Encapsulado para evitar conflictos
(function() {
    'use strict';
    
    // Si ya existe, no redefinir
    if (window.HorizontalCarousel) {
        console.log('HorizontalCarousel ya existe, evitando redefinición');
        return;
    }
    
    class HorizontalCarousel {
        constructor(carouselId, trackId, prevId, nextId) {
            this.carousel = document.getElementById(carouselId);
            this.track = document.getElementById(trackId);
            this.prevBtn = document.getElementById(prevId);
            this.nextBtn = document.getElementById(nextId);
            
            if (!this.carousel || !this.track || !this.prevBtn || !this.nextBtn) {
                console.warn(`Carousel ${carouselId} no encontrado o elementos faltantes`);
                return;
            }
            
            this.originalSlides = this.track.querySelectorAll('.product-slide');
            this.totalSlides = this.originalSlides.length;
            this.isTransitioning = false;
            
            // Determinar cuántos slides mostrar basado en el ancho de pantalla
            this.updateSlidesPerView();
            
            // Crear slides duplicados para efecto infinito
            this.createInfiniteLoop();
            
            // Empezar en el primer set original (después de los clones iniciales)
            this.currentIndex = this.totalSlides;
            
            this.init();
        }
        
        updateSlidesPerView() {
            const width = window.innerWidth;
            if (width <= 480) {
                this.slidesPerView = 1;
            } else if (width <= 768) {
                this.slidesPerView = 2;
            } else if (width <= 1200) {
                this.slidesPerView = 3;
            } else {
                this.slidesPerView = 4;
            }
        }
        
        createInfiniteLoop() {
            // Clonar slides al final (para navegación hacia la derecha)
            for (let i = 0; i < this.totalSlides; i++) {
                const clone = this.originalSlides[i].cloneNode(true);
                clone.classList.add('cloned');
                this.track.appendChild(clone);
            }
            
            // Clonar slides al inicio (para navegación hacia la izquierda)
            for (let i = this.totalSlides - 1; i >= 0; i--) {
                const clone = this.originalSlides[i].cloneNode(true);
                clone.classList.add('cloned');
                this.track.insertBefore(clone, this.track.firstChild);
            }
            
            // Actualizar la lista de slides después de clonar
            this.allSlides = this.track.querySelectorAll('.product-slide');
            this.totalAllSlides = this.allSlides.length;
        }
        
        init() {
            // Asegurar que el CSS de transición esté aplicado
            this.track.style.transition = 'transform 0.3s ease';
            
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.prevSlide();
            });
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.nextSlide();
            });
            
            // Listener para detectar cuando termina la transición (para loop infinito)
            this.track.addEventListener('transitionend', () => this.handleTransitionEnd());
            
            // Actualizar en redimensionamiento
            window.addEventListener('resize', () => {
                this.updateSlidesPerView();
                this.updateCarousel();
            });
            
            // Configuración inicial
            this.updateCarousel();
        }
        
        prevSlide() {
            if (this.isTransitioning) {
                return;
            }
            
            this.isTransitioning = true;
            this.currentIndex--;
            this.updateCarousel();
        }
        
        nextSlide() {
            if (this.isTransitioning) {
                return;
            }
            
            this.isTransitioning = true;
            this.currentIndex++;
            this.updateCarousel();
        }
        
        updateCarousel() {
            if (!this.allSlides || this.allSlides.length === 0) {
                return;
            }
            
            const slideWidth = this.allSlides[0].offsetWidth;
            const gap = 20; // Gap ajustado para coincidir con CSS
            const translateX = -(this.currentIndex * (slideWidth + gap));
            
            this.track.style.transform = `translateX(${translateX}px)`;
        }
        
        handleTransitionEnd() {
            this.isTransitioning = false;
            
            // Si estamos en los clones del final, saltar al inicio original
            if (this.currentIndex >= this.totalSlides * 2) {
                this.track.style.transition = 'none';
                this.currentIndex = this.totalSlides;
                this.updateCarousel();
                
                // Restaurar transición después de un frame
                requestAnimationFrame(() => {
                    this.track.style.transition = 'transform 0.3s ease';
                });
            }
            
            // Si estamos en los clones del inicio, saltar al final original
            if (this.currentIndex < this.totalSlides) {
                this.track.style.transition = 'none';
                this.currentIndex = this.totalSlides * 2 - 1;
                this.updateCarousel();
                
                // Restaurar transición después de un frame
                requestAnimationFrame(() => {
                    this.track.style.transition = 'transform 0.3s ease';
                });
            }
        }
    }

    // Hacer la clase disponible globalmente
    window.HorizontalCarousel = HorizontalCarousel;

})(); // Fin de IIFE para HorizontalCarousel