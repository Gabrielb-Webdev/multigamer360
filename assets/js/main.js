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

// Los carousels se inicializan automáticamente al final del archivo

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
        
        // Los carousels horizontales se inicializan automáticamente en su propio IIFE al final del archivo
        
        // Si estamos en la página de productos
        if (document.querySelector('.products-grid')) {
            initProductsPage();
        }
        
        // Inicializar botones de agregar al carrito en carousels
        initCarouselCartButtons();
        
        console.log('✅ Aplicación inicializada correctamente');
    } catch (error) {
        console.error('❌ Error al inicializar la aplicación:', error);
    }
});

// ================================
// FUNCIONALIDAD DE CARRITO EN CAROUSELS
// ================================
function initCarouselCartButtons() {
    // Usar delegación de eventos para manejar botones dinámicos
    document.body.addEventListener('click', function(e) {
        const cartBtn = e.target.closest('.add-to-cart-btn');
        if (!cartBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        // Obtener datos del producto
        const productId = cartBtn.dataset.productId;
        const productName = cartBtn.dataset.productName;
        const productPrice = cartBtn.dataset.productPrice;
        const productImage = cartBtn.dataset.productImage;
        
        if (!productId) {
            console.error('ID de producto no encontrado');
            return;
        }
        
        // Deshabilitar botón temporalmente
        cartBtn.disabled = true;
        const originalHTML = cartBtn.innerHTML;
        cartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Enviar petición al servidor
        fetch('ajax/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Producto agregado al carrito exitosamente');
                // Mostrar éxito
                cartBtn.innerHTML = '<i class="fas fa-check"></i>';
                const isOutlineBtn = cartBtn.classList.contains('btn-outline-light');
                
                if (isOutlineBtn) {
                    cartBtn.style.backgroundColor = 'white';
                    cartBtn.style.color = '#28a745';
                    cartBtn.style.borderColor = '#28a745';
                } else {
                    cartBtn.style.backgroundColor = '#28a745';
                    cartBtn.style.color = 'white';
                }
                
                // Actualizar contador del carrito
                updateCartCount();
                
                // Restaurar botón después de 1.5 segundos
                setTimeout(() => {
                    cartBtn.innerHTML = originalHTML;
                    cartBtn.style.backgroundColor = '';
                    cartBtn.style.color = '';
                    cartBtn.style.borderColor = '';
                    cartBtn.disabled = false;
                }, 1500);
                
                // Mostrar notificación (si existe sistema de notificaciones)
                if (typeof showNotification === 'function') {
                    showNotification('Producto agregado al carrito', 'success');
                }
            } else {
                throw new Error(data.message || 'Error al agregar al carrito');
            }
        })
        .catch(error => {
            console.error('❌ Error al agregar al carrito:', error);
            cartBtn.innerHTML = '<i class="fas fa-times"></i>';
            
            const isOutlineBtn = cartBtn.classList.contains('btn-outline-light');
            if (isOutlineBtn) {
                cartBtn.style.backgroundColor = 'white';
                cartBtn.style.color = '#dc3545';
                cartBtn.style.borderColor = '#dc3545';
            } else {
                cartBtn.style.backgroundColor = '#dc3545';
                cartBtn.style.color = 'white';
            }
            
            setTimeout(() => {
                cartBtn.innerHTML = originalHTML;
                cartBtn.style.backgroundColor = '';
                cartBtn.style.color = '';
                cartBtn.style.borderColor = '';
                cartBtn.disabled = false;
            }, 1500);
            
            if (typeof showNotification === 'function') {
                showNotification('Error al agregar al carrito', 'error');
            }
        });
    });
}

// Función para actualizar el contador del carrito
function updateCartCount() {
    fetch('ajax/get-cart-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    cartBadge.textContent = data.count;
                    
                    // Animación del badge
                    cartBadge.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        cartBadge.style.transform = 'scale(1)';
                    }, 200);
                }
            }
        })
        .catch(error => console.error('Error al actualizar contador:', error));
}

// Horizontal Carousel Functionality - Encapsulado para evitar conflictos
// ================================
// HORIZONTAL CAROUSEL - Sistema de carruseles mejorado
// ================================
(function() {
    'use strict';
    
    // Si ya existe, no redefinir
    if (window.HorizontalCarousel) {
        console.log('HorizontalCarousel ya definido');
        return;
    }
    
    class HorizontalCarousel {
        constructor(carouselId, trackId, prevId, nextId) {
            console.log(`Inicializando carousel: ${carouselId}`);
            
            this.carousel = document.getElementById(carouselId);
            this.track = document.getElementById(trackId);
            this.prevBtn = document.getElementById(prevId);
            this.nextBtn = document.getElementById(nextId);
            
            // Verificación detallada
            if (!this.carousel) {
                console.error(`❌ Elemento con ID '${carouselId}' no encontrado`);
                return;
            }
            if (!this.track) {
                console.error(`❌ Track con ID '${trackId}' no encontrado`);
                return;
            }
            if (!this.prevBtn) {
                console.error(`❌ Botón prev con ID '${prevId}' no encontrado`);
                return;
            }
            if (!this.nextBtn) {
                console.error(`❌ Botón next con ID '${nextId}' no encontrado`);
                return;
            }
            
            this.originalSlides = Array.from(this.track.querySelectorAll('.product-slide'));
            this.totalSlides = this.originalSlides.length;
            this.currentIndex = 0;
            this.isAnimating = false;
            
            if (this.totalSlides === 0) {
                console.error(`❌ No hay slides en ${carouselId}`);
                return;
            }
            
            console.log(`✅ Carousel ${carouselId} inicializado con ${this.totalSlides} slides`);
            
            this.updateSlidesPerView();
            this.setupInfiniteLoop();
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
        
        setupInfiniteLoop() {
            // Clonar los primeros y últimos elementos para crear el efecto infinito
            // Solo si tenemos suficientes slides
            if (this.totalSlides <= this.slidesPerView) {
                // Si no hay suficientes slides, duplicar todos
                this.originalSlides.forEach(slide => {
                    const clone = slide.cloneNode(true);
                    clone.classList.add('cloned');
                    this.track.appendChild(clone);
                });
            }
            
            // Clonar los primeros slides y agregarlos al final
            const clonesNeeded = Math.min(this.slidesPerView, this.totalSlides);
            for (let i = 0; i < clonesNeeded; i++) {
                const clone = this.originalSlides[i].cloneNode(true);
                clone.classList.add('cloned');
                this.track.appendChild(clone);
            }
            
            // Clonar los últimos slides y agregarlos al principio
            for (let i = this.totalSlides - clonesNeeded; i < this.totalSlides; i++) {
                const clone = this.originalSlides[i].cloneNode(true);
                clone.classList.add('cloned');
                this.track.insertBefore(clone, this.track.firstChild);
            }
            
            // Actualizar referencia de slides
            this.slides = Array.from(this.track.querySelectorAll('.product-slide'));
            
            // Posicionar en el primer slide real (después de los clones iniciales)
            const slideWidth = this.originalSlides[0].offsetWidth;
            const gap = parseInt(getComputedStyle(this.track).gap) || 20;
            const initialOffset = -(slideWidth + gap) * clonesNeeded;
            this.track.style.transform = `translateX(${initialOffset}px)`;
            this.track.style.transition = 'none';
        }
        
        init() {
            // Event listeners
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigate('prev');
            });
            
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigate('next');
            });
            
            // Resize handler
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    const oldSlidesPerView = this.slidesPerView;
                    this.updateSlidesPerView();
                    
                    // Si cambió la cantidad de slides visibles, reinicializar
                    if (oldSlidesPerView !== this.slidesPerView) {
                        this.resetCarousel();
                    }
                }, 250);
            });
        }
        
        resetCarousel() {
            // Limpiar clones
            const clones = this.track.querySelectorAll('.cloned');
            clones.forEach(clone => clone.remove());
            
            // Reiniciar
            this.currentIndex = 0;
            this.setupInfiniteLoop();
        }
        
        navigate(direction) {
            if (this.isAnimating || this.totalSlides === 0) return;
            
            this.isAnimating = true;
            
            const slideWidth = this.slides[0].offsetWidth;
            const gap = parseInt(getComputedStyle(this.track).gap) || 20;
            const moveDistance = slideWidth + gap;
            
            if (direction === 'next') {
                // Mover hacia la izquierda (siguiente producto)
                this.currentIndex++;
                this.animateTransition(-moveDistance, direction);
            } else {
                // Mover hacia la derecha (producto anterior)
                this.currentIndex--;
                this.animateTransition(moveDistance, direction);
            }
        }
        
        animateTransition(distance, direction) {
            // Obtener la posición actual
            const currentTransform = this.track.style.transform || 'translateX(0px)';
            const currentX = parseFloat(currentTransform.match(/-?\d+\.?\d*/)?.[0] || 0);
            
            // Aplicar transición suave
            this.track.style.transition = 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            this.track.style.transform = `translateX(${currentX + distance}px)`;
            
            // Después de la animación, verificar si necesitamos resetear la posición
            setTimeout(() => {
                const slideWidth = this.slides[0].offsetWidth;
                const gap = parseInt(getComputedStyle(this.track).gap) || 20;
                const clonesNeeded = Math.min(this.slidesPerView, this.totalSlides);
                
                // Si llegamos al final (después del último clon)
                if (direction === 'next' && this.currentIndex >= this.totalSlides + clonesNeeded) {
                    this.track.style.transition = 'none';
                    this.currentIndex = clonesNeeded;
                    const resetPosition = -(slideWidth + gap) * this.currentIndex;
                    this.track.style.transform = `translateX(${resetPosition}px)`;
                    
                    // Forzar reflow
                    this.track.offsetHeight;
                }
                // Si llegamos al inicio (antes del primer clon)
                else if (direction === 'prev' && this.currentIndex < 0) {
                    this.track.style.transition = 'none';
                    this.currentIndex = this.totalSlides - 1;
                    const resetPosition = -(slideWidth + gap) * (this.currentIndex + clonesNeeded);
                    this.track.style.transform = `translateX(${resetPosition}px)`;
                    
                    // Forzar reflow
                    this.track.offsetHeight;
                }
                
                this.isAnimating = false;
            }, 600);
        }
    }

    // Función para inicializar los carousels
    function initCarousels() {
        console.log('🎠 Iniciando carousels...');
        
        // Carousel de novedades
        const productCarousel = document.getElementById('productCarousel');
        if (productCarousel) {
            console.log('📦 Inicializando carousel de novedades...');
            new HorizontalCarousel(
                'productCarousel',
                'productCarouselTrack',
                'productCarouselPrev',
                'productCarouselNext'
            );
        } else {
            console.warn('⚠️ Carousel de novedades no encontrado en esta página');
        }

        // Carousel de productos destacados
        const featuredCarousel = document.getElementById('featuredCarousel');
        if (featuredCarousel) {
            console.log('⭐ Inicializando carousel de productos destacados...');
            new HorizontalCarousel(
                'featuredCarousel',
                'featuredCarouselTrack',
                'featuredCarouselPrev',
                'featuredCarouselNext'
            );
        } else {
            console.warn('⚠️ Carousel de destacados no encontrado en esta página');
        }
        
        console.log('✅ Inicialización de carousels completada');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarousels);
    } else {
        // DOM ya está listo, inicializar inmediatamente
        initCarousels();
    }

    // Hacer la clase disponible globalmente
    window.HorizontalCarousel = HorizontalCarousel;

})(); // Fin de IIFE para HorizontalCarousel