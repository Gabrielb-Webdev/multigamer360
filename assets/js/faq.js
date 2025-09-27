// ===== FAQ INTERACTIVE FUNCTIONALITY - MULTIGAMER360 =====

document.addEventListener('DOMContentLoaded', function() {
    
    // === ELEMENTOS DEL DOM ===
    const searchInput = document.getElementById('faqSearch');
    const clearSearch = document.getElementById('clearSearch');
    const searchSuggestions = document.querySelectorAll('.suggestion-tag');
    const categoryTabs = document.querySelectorAll('.category-tab');
    const faqItems = document.querySelectorAll('.faq-item');
    const faqCategories = document.querySelectorAll('.faq-category');
    const noResults = document.getElementById('noResults');
    const popularQuestions = document.querySelectorAll('.popular-question');

    let currentCategory = 'all';
    let currentSearch = '';

    // === FUNCIONES DE BÚSQUEDA ===
    function performSearch(searchTerm) {
        currentSearch = searchTerm.toLowerCase().trim();
        
        // Mostrar/ocultar botón de limpiar
        if (currentSearch) {
            clearSearch.style.display = 'block';
        } else {
            clearSearch.style.display = 'none';
        }

        filterContent();
    }

    function filterContent() {
        let visibleCount = 0;

        // Filtrar por categoría
        faqCategories.forEach(category => {
            const categoryName = category.getAttribute('data-category');
            
            if (currentCategory === 'all' || currentCategory === categoryName) {
                category.style.display = 'block';
                
                // Filtrar items dentro de la categoría
                const items = category.querySelectorAll('.faq-item');
                let categoryVisibleCount = 0;
                
                items.forEach(item => {
                    const question = item.querySelector('h4').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                    const keywords = item.getAttribute('data-keywords') || '';
                    const searchableText = `${question} ${answer} ${keywords}`.toLowerCase();
                    
                    if (!currentSearch || searchableText.includes(currentSearch)) {
                        item.style.display = 'block';
                        categoryVisibleCount++;
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Ocultar categoría si no tiene items visibles
                if (categoryVisibleCount === 0 && currentSearch) {
                    category.style.display = 'none';
                }
            } else {
                category.style.display = 'none';
            }
        });

        // Mostrar mensaje de "no resultados"
        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    }

    // === EVENT LISTENERS DE BÚSQUEDA ===
    searchInput.addEventListener('input', function() {
        performSearch(this.value);
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch(this.value);
        }
    });

    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        currentSearch = '';
        this.style.display = 'none';
        filterContent();
        searchInput.focus();
    });

    // === SUGERENCIAS DE BÚSQUEDA ===
    searchSuggestions.forEach(suggestion => {
        suggestion.addEventListener('click', function() {
            const searchTerm = this.getAttribute('data-search');
            searchInput.value = searchTerm;
            performSearch(searchTerm);
            
            // Scroll suave al contenido
            document.querySelector('.faq-content-section').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // === PREGUNTAS POPULARES ===
    popularQuestions.forEach(question => {
        question.addEventListener('click', function(e) {
            e.preventDefault();
            const searchTerm = this.getAttribute('data-target');
            searchInput.value = searchTerm;
            performSearch(searchTerm);
            
            // Scroll al contenido
            document.querySelector('.faq-content-section').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // === TABS DE CATEGORÍAS ===
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remover clase activa de todos los tabs
            categoryTabs.forEach(t => t.classList.remove('active'));
            
            // Agregar clase activa al tab clickeado
            this.classList.add('active');
            
            // Cambiar categoría actual
            currentCategory = this.getAttribute('data-category');
            
            // Filtrar contenido
            filterContent();
            
            // Scroll suave y controlado al contenido
            setTimeout(() => {
                const contentSection = document.querySelector('.faq-content-section');
                if (contentSection) {
                    const headerHeight = 160; // Altura ajustada del header
                    const rect = contentSection.getBoundingClientRect();
                    
                    if (rect.top < headerHeight) {
                        window.scrollTo({
                            top: window.scrollY + rect.top - headerHeight - 10,
                            behavior: 'smooth'
                        });
                    }
                }
            }, 100);
        });
    });

    // === ACORDEÓN FAQ ===
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Toggle del item actual
            if (isActive) {
                item.classList.remove('active');
            } else {
                item.classList.add('active');
                
                // Scroll suave y controlado al item abierto (solo si está fuera de vista)
                setTimeout(() => {
                    const rect = item.getBoundingClientRect();
                    const headerHeight = 160; // Altura ajustada del header con navbar
                    
                    // Solo hacer scroll si el item está parcialmente oculto
                    if (rect.top < headerHeight) {
                        window.scrollTo({
                            top: window.scrollY + rect.top - headerHeight - 20,
                            behavior: 'smooth'
                        });
                    }
                }, 300);
            }
        });
    });

    // === FILTRO AUTOMÁTICO DESDE URL ===
    function handleURLParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search');
        const categoryParam = urlParams.get('category');
        
        if (searchParam) {
            searchInput.value = searchParam;
            performSearch(searchParam);
        }
        
        if (categoryParam) {
            const targetTab = document.querySelector(`[data-category="${categoryParam}"]`);
            if (targetTab) {
                categoryTabs.forEach(tab => tab.classList.remove('active'));
                targetTab.classList.add('active');
                currentCategory = categoryParam;
                filterContent();
            }
        }
    }

    // === FUNCIONES DE UTILIDAD ===
    function highlightSearchTerms() {
        if (!currentSearch) return;
        
        faqItems.forEach(item => {
            if (item.style.display !== 'none') {
                const question = item.querySelector('h4');
                const answer = item.querySelector('.faq-answer');
                
                // Quitar highlights anteriores
                question.innerHTML = question.textContent;
                answer.innerHTML = answer.textContent;
                
                // Agregar nuevos highlights
                const regex = new RegExp(`(${currentSearch})`, 'gi');
                question.innerHTML = question.textContent.replace(regex, '<mark>$1</mark>');
                answer.innerHTML = answer.textContent.replace(regex, '<mark>$1</mark>');
            }
        });
    }

    // === FUNCIONALIDAD DE SCROLL SUAVE ===
    function addSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // === ANALYTICS TRACKING (opcional) ===
    function trackFAQInteraction(action, question) {
        // Aquí puedes agregar tracking de Google Analytics o similar
        console.log(`FAQ ${action}: ${question}`);
        
        // Ejemplo para Google Analytics
        // if (typeof gtag !== 'undefined') {
        //     gtag('event', 'faq_interaction', {
        //         action: action,
        //         question: question
        //     });
        // }
    }

    // === INICIALIZACIÓN ===
    function init() {
        handleURLParams();
        addSmoothScrolling();
        
        // Agregar event listeners para tracking
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', function() {
                const questionText = this.querySelector('h4').textContent;
                trackFAQInteraction('question_opened', questionText);
            });
        });

        // Auto-focus en el campo de búsqueda (solo en desktop)
        if (window.innerWidth > 768) {
            setTimeout(() => {
                searchInput.focus();
            }, 500);
        }
    }

    // === FUNCIONES AUXILIARES ===
    
    // Debounce para optimizar la búsqueda
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Optimizar búsqueda con debounce
    const debouncedSearch = debounce((searchTerm) => {
        performSearch(searchTerm);
    }, 300);

    // Reemplazar el event listener directo con la versión optimizada
    searchInput.removeEventListener('input', function() {
        performSearch(this.value);
    });
    
    searchInput.addEventListener('input', function() {
        debouncedSearch(this.value);
    });

    // === EJECUTAR INICIALIZACIÓN ===
    init();

    // === RESPONSIVE HANDLING ===
    window.addEventListener('resize', function() {
        // Ajustar comportamiento en diferentes tamaños de pantalla
        if (window.innerWidth <= 768) {
            // Comportamiento móvil
            categoryTabs.forEach(tab => {
                tab.style.minWidth = 'auto';
            });
        }
    });

    // === ACCESSIBILITY IMPROVEMENTS ===
    
    // Manejo de teclado para los tabs
    categoryTabs.forEach((tab, index) => {
        tab.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                const nextTab = categoryTabs[index + 1] || categoryTabs[0];
                nextTab.focus();
                nextTab.click();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                const prevTab = categoryTabs[index - 1] || categoryTabs[categoryTabs.length - 1];
                prevTab.focus();
                prevTab.click();
            }
        });
    });

    // Manejo de teclado para los FAQ items
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.setAttribute('tabindex', '0');
        question.setAttribute('role', 'button');
        question.setAttribute('aria-expanded', 'false');
        
        question.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
                
                // Actualizar aria-expanded
                const isActive = item.classList.contains('active');
                this.setAttribute('aria-expanded', isActive);
            }
        });
    });

    // === STORAGE DE PREFERENCIAS ===
    
    // Guardar categoría preferida
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            localStorage.setItem('faq_preferred_category', category);
        });
    });

    // Cargar categoría preferida (si no hay parámetros URL)
    if (!new URLSearchParams(window.location.search).get('category')) {
        const preferredCategory = localStorage.getItem('faq_preferred_category');
        if (preferredCategory && preferredCategory !== 'all') {
            const targetTab = document.querySelector(`[data-category="${preferredCategory}"]`);
            if (targetTab) {
                setTimeout(() => {
                    targetTab.click();
                }, 100);
            }
        }
    }
});

// === FUNCIONES GLOBALES ===

// Función para abrir FAQ específico desde enlaces externos
function openFAQ(questionText) {
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('h4').textContent;
        if (question.toLowerCase().includes(questionText.toLowerCase())) {
            item.classList.add('active');
            item.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            return;
        }
    });
}

// Función para buscar desde enlaces externos
function searchFAQ(searchTerm) {
    const searchInput = document.getElementById('faqSearch');
    if (searchInput) {
        searchInput.value = searchTerm;
        searchInput.dispatchEvent(new Event('input'));
        
        // Scroll al área de resultados
        document.querySelector('.faq-content-section').scrollIntoView({
            behavior: 'smooth'
        });
    }
}
