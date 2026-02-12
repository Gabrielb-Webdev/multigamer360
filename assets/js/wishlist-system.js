// Sistema de lista de deseos - Encapsulado para evitar conflictos
(function() {
    'use strict';
    
    // Si ya existe, no redefinir
    if (window.WishlistSystem) {
        console.log('WishlistSystem ya existe, evitando redefinición');
        return;
    }
    
    class WishlistSystem {
        constructor() {
            this.init();
        }

        init() {
            this.loadWishlistState();
            this.initializeEventListeners();
        }

    // Inicializar event listeners
    initializeEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-wishlist, .btn-wishlist *')) {
                e.preventDefault();
                const button = e.target.closest('.btn-wishlist');
                if (button) {
                    const productId = button.getAttribute('data-product-id');
                    const productName = button.getAttribute('data-product-name');
                    if (productId) {
                        this.toggleWishlist(button, productId, productName);
                    }
                }
            }
        });
    }

    // Cargar estado de la wishlist
    async loadWishlistState() {
        try {
            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/get-wishlist-count.php`);
            
            // Verificar que la respuesta sea OK
            if (!response.ok) {
                console.warn('⚠️ No se pudo cargar el estado de wishlist:', response.status);
                return; // NO actualizar botones si hay error
            }
            
            const data = await response.json();
            
            console.log('📋 Cargando estado de wishlist desde servidor:', data);
            
            if (data.success && Array.isArray(data.items)) {
                console.log('✅ Items en wishlist:', data.items);
                this.updateWishlistButtons(data.items || []);
                this.updateWishlistCount(data.count || 0);
            } else {
                console.warn('⚠️ Respuesta de wishlist no válida, manteniendo estado inicial del servidor');
            }
        } catch (error) {
            console.error('❌ Error cargando estado de wishlist:', error);
            console.warn('⚠️ Manteniendo estado inicial renderizado por PHP');
            // NO actualizar botones si hay error - respetar el estado del servidor
        }
    }

    // Toggle wishlist
    async toggleWishlist(button, productId, productName) {
        if (button.classList.contains('loading')) return;

        // Mostrar loading
        this.setButtonLoading(button, true);

        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('action', 'toggle');

            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/toggle-wishlist.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Actualizar estado del botón
                this.updateButtonState(button, data.in_wishlist);
                
                // Actualizar todos los botones si tenemos los items
                if (data.items) {
                    this.updateWishlistButtons(data.items);
                }
                
                // NO MOSTRAR notificación - DESACTIVADO
                // this.showNotification(data.message, 'success');
                
                // Actualizar contador si existe
                this.updateWishlistCount(data.count);
            } else {
                // NO MOSTRAR notificación - DESACTIVADO
                // this.showNotification(data.message || 'Error al actualizar lista de deseos', 'error');
                console.log(data.message || 'Error al actualizar lista de deseos');
            }
        } catch (error) {
            console.error('Error:', error);
            // NO MOSTRAR notificación - DESACTIVADO
            // this.showNotification('Error de conexión', 'error');
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    // Actualizar estado del botón
    updateButtonState(button, inWishlist) {
        const icon = button.querySelector('i');
        
        if (inWishlist) {
            button.classList.add('active');
            icon.className = 'fas fa-heart';
            button.style.color = '#e74c3c';
        } else {
            button.classList.remove('active');
            icon.className = 'far fa-heart';
            button.style.color = '';
        }
    }

    // Actualizar todos los botones de wishlist
    updateWishlistButtons(wishlistItems) {
        document.querySelectorAll('.btn-wishlist').forEach(button => {
            const productId = parseInt(button.getAttribute('data-product-id'));
            const inWishlist = wishlistItems.includes(productId);
            
            // IMPORTANTE: Solo actualizar si el botón NO tiene ya el estado correcto
            // Esto respeta el estado inicial renderizado por PHP
            const currentlyActive = button.classList.contains('active');
            
            // Si el estado es diferente al esperado, actualizarlo
            if (currentlyActive !== inWishlist) {
                console.log(`🔄 Sincronizando botón de producto ${productId}: ${inWishlist ? 'Agregando' : 'Quitando'} estado active`);
                this.updateButtonState(button, inWishlist);
            } else {
                console.log(`✅ Botón de producto ${productId} ya tiene el estado correcto (active: ${currentlyActive})`);
            }
        });
    }

    // Mostrar/ocultar loading en botón
    setButtonLoading(button, loading) {
        const icon = button.querySelector('i');
        
        if (loading) {
            button.classList.add('loading');
            button.style.opacity = '0.8';
            button.style.pointerEvents = 'none';
            
            // Guardar clase original del icono y cambiar a spinner
            if (icon) {
                if (!button.dataset.originalIcon) {
                    button.dataset.originalIcon = icon.className;
                }
                icon.className = 'fas fa-spinner fa-spin';
            }
        } else {
            button.classList.remove('loading');
            button.style.opacity = '';
            button.style.pointerEvents = '';
            
            // Si el icono sigue siendo un spinner (ej. por error en la petición), restaurar el original
            // Si la petición fue exitosa, updateButtonState ya habrá cambiado el icono
            if (icon && icon.className.includes('fa-spinner') && button.dataset.originalIcon) {
                icon.className = button.dataset.originalIcon;
            }
            
            // Limpiar data
            delete button.dataset.originalIcon;
        }
    }

    // Actualizar contador de wishlist
    updateWishlistCount(count) {
        const counters = document.querySelectorAll('.wishlist-count, [data-wishlist-count]');
        counters.forEach(counter => {
            if (count > 0) {
                counter.textContent = count;
                counter.style.display = 'inline-block';
            } else {
                counter.style.display = 'none';
            }
        });
    }

    // Mostrar notificación
    showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `wishlist-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        // Estilos inline para la notificación
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: type === 'success' ? '#28a745' : '#dc3545',
            color: 'white',
            padding: '12px 20px',
            borderRadius: '6px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            zIndex: '9999',
            transform: 'translateX(400px)',
            transition: 'transform 0.3s ease',
            maxWidth: '300px',
            fontSize: '14px'
        });

        // Añadir al DOM
        document.body.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Animar salida y remover
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Inicializar sistema de wishlist cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    try {
        window.wishlistSystem = new WishlistSystem();
    } catch (error) {
        console.error('WishlistSystem: Error durante la inicialización:', error);
    }
});

// CSS adicional para efectos de wishlist
const wishlistStyles = `
<style>
.btn-wishlist {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-wishlist:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-wishlist.active {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.btn-wishlist.loading {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-wishlist i {
    font-size: 18px;
    transition: all 0.3s ease;
}

.wishlist-notification {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 8px;
}

.notification-content i {
    font-size: 16px;
}

@media (max-width: 768px) {
    .wishlist-notification {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>
`;

// Insertar estilos en el head
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', wishlistStyles);
}

    // Hacer la clase disponible globalmente
    window.WishlistSystem = WishlistSystem;

    // NO inicializar automáticamente aquí - dejar que footer.php lo haga
    console.log('WishlistSystem class loaded and available globally');

})(); // Fin de IIFE