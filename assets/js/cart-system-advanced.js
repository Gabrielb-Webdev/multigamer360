// Sistema de carrito avanzado con stock, animaciones y hover effects
class AdvancedCartSystem {
    constructor() {
        this.isLoading = false;
        this.cartData = {
            count: 0,
            total: 0,
            items: {}
        };
        this.init();
    }

    init() {
        this.loadCartState();
        this.initializeEventListeners();
        this.startPeriodicSync();
        this.initializeHoverEffects();
    }

    // Cargar estado inicial del carrito
    async loadCartState() {
        try {
            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/get-cart-count.php`);
            const data = await response.json();
            
            if (data.success) {
                this.updateCartData(data);
                this.updateUI();
                this.updateButtonStates();
            }
        } catch (error) {
            console.error('Error cargando estado del carrito:', error);
        }
    }

    // Inicializar event listeners
    initializeEventListeners() {
        // Botones de agregar al carrito
        document.addEventListener('click', (e) => {
            if (e.target && e.target.matches && e.target.matches('[data-action="add-to-cart"], [data-action="add-to-cart"] *')) {
                e.preventDefault();
                const button = e.target.closest && e.target.closest('[data-action="add-to-cart"]');
                if (button && !button.disabled) {
                    const productId = button.getAttribute('data-product-id');
                    if (productId) {
                        this.addToCart(button, productId);
                    }
                }
            }

            // Botones de eliminar del carrito (cuando están en estado success)
            if (e.target && e.target.matches && e.target.matches('[data-action="remove-from-cart"], [data-action="remove-from-cart"] *')) {
                e.preventDefault();
                const button = e.target.closest && e.target.closest('[data-action="remove-from-cart"]');
                if (button) {
                    const productId = button.getAttribute('data-product-id');
                    if (productId) {
                        this.removeFromCart(productId, button);
                    }
                }
            }
        });

        // Listener para cambios de página - DESHABILITADO porque causa errores al redirigir
        // El carrito ya se sincroniza automáticamente al cargar páginas
        /*
        window.addEventListener('beforeunload', () => {
            this.syncCartState();
        });
        */

        // Listener para visibilidad de página
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadCartState();
            }
        });
    }

    // Inicializar efectos hover
    initializeHoverEffects() {
        document.addEventListener('mouseenter', (e) => {
            const button = e.target && e.target.closest && e.target.closest('[data-action="add-to-cart"]');
            if (button && button.classList.contains('success')) {
                this.showRemoveHover(button);
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            const button = e.target && e.target.closest && e.target.closest('[data-action="add-to-cart"]');
            if (button && button.classList.contains('success')) {
                this.hideRemoveHover(button);
            }
        }, true);
    }

    // Mostrar hover de eliminar
    showRemoveHover(button) {
        if (button.classList.contains('loading')) return;
        
        button.style.backgroundColor = '#dc3545';
        button.style.borderColor = '#dc3545';
        button.setAttribute('data-action', 'remove-from-cart');
        
        const successIcon = button.querySelector('.success-icon');
        const removeIcon = button.querySelector('.remove-icon');
        
        if (successIcon) successIcon.classList.add('d-none');
        if (removeIcon) {
            removeIcon.classList.remove('d-none');
        } else {
            // Crear icono de eliminar si no existe
            const newRemoveIcon = document.createElement('span');
            newRemoveIcon.className = 'remove-icon';
            newRemoveIcon.innerHTML = '<i class="fas fa-trash"></i>';
            button.appendChild(newRemoveIcon);
        }
    }

    // Ocultar hover de eliminar
    hideRemoveHover(button) {
        if (button.classList.contains('loading')) return;
        
        button.style.backgroundColor = '#28a745';
        button.style.borderColor = '#28a745';
        button.setAttribute('data-action', 'add-to-cart');
        
        const successIcon = button.querySelector('.success-icon');
        const removeIcon = button.querySelector('.remove-icon');
        
        if (removeIcon) removeIcon.classList.add('d-none');
        if (successIcon) successIcon.classList.remove('d-none');
    }

    // Sincronización periódica
    startPeriodicSync() {
        setInterval(() => {
            if (!this.isLoading) {
                this.syncCartState();
            }
        }, 15000); // Cada 15 segundos
    }

    // Sincronizar estado del carrito
    async syncCartState() {
        try {
            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/get-cart-count.php`);
            const data = await response.json();
            
            if (data.success) {
                const hasChanges = 
                    this.cartData.count !== data.cart_count ||
                    this.cartData.total !== data.cart_total;
                
                if (hasChanges) {
                    this.updateCartData(data);
                    this.updateUI();
                    this.updateButtonStates();
                }
            }
        } catch (error) {
            console.error('Error sincronizando carrito:', error);
        }
    }

    // Agregar producto al carrito
    async addToCart(button, productId) {
        if (this.isLoading || button.disabled) {
            return;
        }

        this.isLoading = true;
        this.setButtonLoading(button);

        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('action', 'add');

            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/add-to-cart.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartData(data);
                // this.setButtonSuccess(button); // Desactivado a pedido
                
                // Verificar stock restante para bloquear o restaurar botón
                if (data.stock_info && data.stock_info.remaining_stock <= 0) {
                    // Si no hay stock, bloquear botón
                    button.disabled = true;
                    button.classList.remove('loading');
                    button.innerHTML = '<i class="fas fa-times-circle"></i> SIN STOCK';
                    button.style.backgroundColor = '#6c757d';
                    button.style.borderColor = '#6c757d';
                } else {
                    // Si hay stock, restaurar botón al estado original
                    this.setButtonDefault(button);
                }

                this.updateUI();
                // this.showNotification('Producto agregado al carrito', 'success'); // Desactivado a pedido
                
                // Actualizar otros botones del mismo producto
                this.updateProductButtons(productId, true);

                /* NOTIFICACIONES DESACTIVADAS
                // Mostrar información de stock si está disponible
                if (data.stock_info) {
                    const remaining = data.stock_info.remaining_stock;
                    if (remaining <= 5 && remaining > 0) {
                        this.showNotification(`Quedan ${remaining} unidades disponibles`, 'warning');
                    } else if (remaining === 0) {
                        this.showNotification('Última unidad agregada', 'info');
                    }
                }
                */
            } else {
                this.setButtonError(button);
                this.showNotification(data.message || 'Error al agregar al carrito', 'error');
                
                // Si es un error de stock, mostrar información adicional
                if (data.stock_available !== undefined) {
                    if (data.stock_available > 0) {
                        this.showNotification(`Máximo ${data.stock_available} unidades disponibles`, 'warning');
                    }
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.setButtonError(button);
            this.showNotification('Error de conexión', 'error');
        } finally {
            this.isLoading = false;
        }
    }

    // Remover producto del carrito
    async removeFromCart(productId, button = null) {
        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        
        if (button) {
            this.setButtonLoading(button);
        }

        try {
            const formData = new FormData();
            formData.append('product_id', productId);

            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/remove-from-cart.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartData(data);
                this.updateUI();
                this.updateProductButtons(productId, false);
                this.showNotification('Producto eliminado del carrito', 'success');
            } else {
                this.showNotification('Error al eliminar producto: ' + data.message, 'error');
                if (button) {
                    this.setButtonError(button);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error de conexión', 'error');
            if (button) {
                this.setButtonError(button);
            }
        } finally {
            this.isLoading = false;
        }
    }

    // Actualizar datos del carrito
    updateCartData(data) {
        this.cartData = {
            count: data.cart_count || 0,
            total: parseFloat(data.cart_total || 0),
            items: data.cart || {}
        };
    }

    // Actualizar UI del carrito
    updateUI() {
        // Actualizar contador principal
        const cartDisplay = document.getElementById('cart-display');
        if (cartDisplay) {
            if (this.cartData.count > 0) {
                cartDisplay.textContent = `${this.cartData.count} - $${Math.round(this.cartData.total).toLocaleString('en-US')}`;
            } else {
                cartDisplay.textContent = '$0';
            }
            
            // Animación suave
            cartDisplay.style.transition = 'transform 0.2s ease';
            cartDisplay.style.transform = 'scale(1.1)';
            setTimeout(() => {
                cartDisplay.style.transform = 'scale(1)';
            }, 200);
        }

        // Actualizar otros contadores
        // IMPORTANTE: No usar .badge genérico porque sobrescribe la wishlist y otros badges
        const cartCounts = document.querySelectorAll('.cart-count, #cart-count');
        cartCounts.forEach(element => {
            element.textContent = this.cartData.count;
            
            // Animación del badge si tiene la clase badge
            if (element.classList.contains('badge')) {
                element.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                }, 150);
            }
        });
    }

    // Actualizar estados de todos los botones
    async updateButtonStates() {
        const buttons = document.querySelectorAll('[data-action="add-to-cart"], [data-action="remove-from-cart"]');
        
        for (const button of buttons) {
            const productId = button.getAttribute('data-product-id');
            if (productId) {
                try {
                    const response = await fetch(`ajax/check-cart-item.php?product_id=${productId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        if (data.inCart || data.in_cart) {
                            this.setButtonSuccess(button);
                        } else {
                            this.setButtonDefault(button);
                        }
                    }
                } catch (error) {
                    console.error(`Error verificando producto ${productId}:`, error);
                }
            }
        }
    }

    // Actualizar botones de un producto específico
    updateProductButtons(productId, inCart) {
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
        buttons.forEach(button => {
            if (button.hasAttribute('data-action')) {
                if (inCart) {
                    this.setButtonSuccess(button);
                } else {
                    this.setButtonDefault(button);
                }
            }
        });
    }

    // Estados de botones
    setButtonLoading(button) {
        button.classList.remove('success', 'error');
        button.classList.add('loading');
        button.disabled = true;
        button.setAttribute('data-action', 'add-to-cart');
        
        // Mostrar spinner circular
        this.updateButtonIcons(button, 'loading');
        
        // Limpiar efectos de hover y asegurar estilo
        button.style.backgroundColor = '#6c757d';
        button.style.borderColor = '#6c757d';
        button.style.color = '#ffffff';
    }

    setButtonSuccess(button) {
        button.classList.remove('loading', 'error');
        button.classList.add('success');
        button.disabled = false;
        button.setAttribute('data-action', 'add-to-cart');
        button.style.backgroundColor = '#28a745';
        button.style.borderColor = '#28a745';
        button.style.color = '#ffffff';
        
        this.updateButtonIcons(button, 'success');
    }

    setButtonDefault(button) {
        button.classList.remove('loading', 'success', 'error');
        button.disabled = false;
        button.setAttribute('data-action', 'add-to-cart');
        button.style.backgroundColor = '';
        button.style.borderColor = '';
        button.style.color = '';
        
        this.updateButtonIcons(button, 'default');
    }

    setButtonError(button) {
        this.setButtonDefault(button);
        button.classList.add('error');
        button.style.backgroundColor = '#dc3545';
        button.style.borderColor = '#dc3545';
        
        // Volver al estado normal después de 3 segundos
        setTimeout(() => {
            if (button.classList.contains('error')) {
                this.setButtonDefault(button);
            }
        }, 3000);
    }

    // Actualizar iconos del botón
    updateButtonIcons(button, state) {
        const cartIcon = button.querySelector('.cart-icon');
        const loadingIcon = button.querySelector('.loading-icon');
        const successIcon = button.querySelector('.success-icon');
        const removeIcon = button.querySelector('.remove-icon');

        // Ocultar todos los iconos
        [cartIcon, loadingIcon, successIcon, removeIcon].forEach(icon => {
            if (icon) {
                icon.classList.add('d-none');
            }
        });

        // Mostrar el icono correspondiente
        switch (state) {
            case 'loading':
                if (loadingIcon) {
                    loadingIcon.classList.remove('d-none');
                } else {
                    // Crear spinner si no existe
                    const newLoadingIcon = document.createElement('span');
                    newLoadingIcon.className = 'loading-icon me-2';
                    newLoadingIcon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    button.prepend(newLoadingIcon);
                }
                break;
            case 'success':
                if (successIcon) {
                    successIcon.classList.remove('d-none');
                } else {
                    // Crear check si no existe
                    const newSuccessIcon = document.createElement('span');
                    newSuccessIcon.className = 'success-icon me-2';
                    newSuccessIcon.innerHTML = '<i class="fas fa-check"></i>';
                    button.prepend(newSuccessIcon);
                }
                break;
            default:
                if (cartIcon) {
                    cartIcon.classList.remove('d-none');
                } else {
                    // Crear icono de carrito si no existe
                    const newCartIcon = document.createElement('span');
                    newCartIcon.className = 'cart-icon me-2';
                    newCartIcon.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                    button.prepend(newCartIcon);
                }
        }
    }

    // Mostrar notificación
    showNotification(message, type = 'success') {
        // Remover notificaciones existentes
        const existingNotifications = document.querySelectorAll('.cart-notification');
        existingNotifications.forEach(n => {
            n.style.opacity = '0';
            n.style.transform = 'translateX(100%)';
            setTimeout(() => n.remove(), 300);
        });

        const notification = document.createElement('div');
        notification.className = `cart-notification cart-notification-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas ${icons[type] || icons.success}" style="font-size: 20px; color: ${colors[type]}"></i>
                <span style="font-weight: 500; font-size: 14px;">${message}</span>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1a1a1a;
            color: #ffffff;
            padding: 16px 24px;
            border-radius: 8px;
            border-left: 4px solid ${colors[type]};
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            z-index: 9999;
            transform: translateX(100%);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            min-width: 300px;
            max-width: 400px;
            display: flex;
            align-items: center;
        `;

        document.body.appendChild(notification);

        // Animar entrada
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });

        // Remover después de 4 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.transform = 'translateX(120%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 4000);
    }



    // Método para limpiar carrito (para testing)
    async clearCart() {
        try {
            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/clear-cart.php`, {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.success) {
                this.updateCartData(data);
                this.updateUI();
                this.updateButtonStates();
                this.showNotification('Carrito limpiado correctamente', 'success');
            } else {
                this.showNotification('Error limpiando carrito: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error de conexión', 'error');
        }
    }

    // Agregar producto al carrito con cantidad específica (para product-details)
    async addToCartWithQuantity(button, productId, quantity = 1) {
        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        this.setButtonLoading(button);

        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('action', 'add');

            const baseUrl = window.SITE_URL || '';
            const response = await fetch(`${baseUrl}/ajax/add-to-cart.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartData(data);
                this.updateUI();
                this.updateProductButtons(productId, true);

                // Verificar stock restante para bloquear o restaurar botón
                if (data.stock_remaining !== undefined && data.stock_remaining <= 0) {
                    // Si no hay stock, bloquear botón
                    button.disabled = true;
                    button.classList.remove('loading');
                    button.innerHTML = '<i class="fas fa-times-circle"></i> SIN STOCK';
                    button.style.backgroundColor = '#6c757d';
                    button.style.borderColor = '#6c757d';
                } else {
                    // Si hay stock, restaurar botón al estado original
                    this.setButtonDefault(button);
                }

                // NOTIFICACIONES DESACTIVADAS A PEDIDO DEL USUARIO
                /*
                // Mensaje específico para cantidad
                if (quantity > 1) {
                    this.showNotification(`${quantity} productos agregados al carrito`, 'success');
                } else {
                    this.showNotification('Producto agregado al carrito', 'success');
                }

                // Información adicional de stock
                if (data.stock_remaining !== undefined) {
                    if (data.stock_remaining <= 0) {
                        this.showNotification('¡Última unidad agregada!', 'info');
                    } else if (data.stock_remaining <= 5) {
                        this.showNotification(`Quedan ${data.stock_remaining} unidades`, 'warning');
                    }
                }
                */
            } else {
                this.setButtonError(button);
                this.showNotification(data.message || 'Error al agregar al carrito', 'error');

                // Información de stock disponible
                if (data.stock_available !== undefined && data.stock_available > 0) {
                    this.showNotification(`Máximo ${data.stock_available} unidades disponibles`, 'warning');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.setButtonError(button);
            this.showNotification('Error de conexión', 'error');
        } finally {
            this.isLoading = false;
        }
    }
}

// Inicializar sistema de carrito cuando el DOM esté listo
let advancedCartSystem;
document.addEventListener('DOMContentLoaded', function() {
    advancedCartSystem = new AdvancedCartSystem();
    
    // Hacer disponible globalmente para debugging y uso externo
    window.cartSystem = advancedCartSystem;
    window.advancedCartSystem = advancedCartSystem;
});

// Funciones globales para compatibilidad
function addToCartWithAnimation(button, productId) {
    if (advancedCartSystem) {
        advancedCartSystem.addToCart(button, productId);
    }
}

function addToCartWithQuantity(button, productId, quantity) {
    if (advancedCartSystem) {
        advancedCartSystem.addToCartWithQuantity(button, productId, quantity);
    }
}

function updateCartCount(data) {
    if (advancedCartSystem) {
        advancedCartSystem.updateCartData(data);
        advancedCartSystem.updateUI();
    }
}