/**
 * Mobile Menu Handler
 * Este script maneja el menú hamburguesa lateral
 * IMPORTANTE: Se carga DESPUÉS de Bootstrap en el footer
 */

document.addEventListener('DOMContentLoaded', function() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    let overlay = null;
    let bsCollapse = null;
    
    // Inicializar Bootstrap Collapse (Bootstrap ya está cargado aquí)
    if (navbarCollapse && typeof bootstrap !== 'undefined') {
        bsCollapse = new bootstrap.Collapse(navbarCollapse, {
            toggle: false
        });
    } else {
        console.warn('Bootstrap no está disponible o navbar-collapse no existe');
        return;
    }
    
    // Crear overlay dinámicamente
    function createOverlay() {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'menu-overlay';
            document.body.appendChild(overlay);
            
            // Cerrar menú al hacer clic en el overlay
            overlay.addEventListener('click', closeMenu);
        }
    }
    
    // Función para cerrar el menú
    function closeMenu() {
        if (bsCollapse && navbarCollapse.classList.contains('show')) {
            bsCollapse.hide();
        }
    }
    
    // Función para abrir el menú
    function openMenu() {
        if (bsCollapse && !navbarCollapse.classList.contains('show')) {
            bsCollapse.show();
        }
    }
    
    // Manejar clic en el botón hamburguesa
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (navbarCollapse.classList.contains('show')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    }
    
    // Cerrar menú al hacer clic en el botón X real
    const closeButton = document.querySelector('.mobile-close-btn');
    if (closeButton) {
        closeButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMenu();
        });
    }
    
    // Cerrar menú con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navbarCollapse && navbarCollapse.classList.contains('show')) {
            closeMenu();
        }
    });
    
    // Manejar apertura del menú
    if (navbarCollapse) {
        navbarCollapse.addEventListener('show.bs.collapse', function() {
            createOverlay();
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                if (overlay) overlay.classList.add('show');
            }, 100);
        });
        
        // Manejar cierre del menú
        navbarCollapse.addEventListener('hide.bs.collapse', function() {
            document.body.style.overflow = '';
            if (overlay) {
                overlay.classList.remove('show');
                setTimeout(() => {
                    if (overlay && overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                        overlay = null;
                    }
                }, 300);
            }
        });
    }
});
