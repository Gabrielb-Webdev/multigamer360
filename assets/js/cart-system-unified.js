// Sistema de carrito unificado
class UnifiedCartSystem {
    constructor() {
        this.isLoading = false;
        this.initializeEventListeners();
    }

    // Inicializar event listeners de manera unificada
    initializeEventListeners() {
        // Un solo listener para todos los botones de carrito
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action="add-to-cart"], .btn-cart, .add-to-cart');
            if (button && !button.disabled) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = button.getAttribute('data-product-id');
                if (productId) {
                    this.handleCartAction(button, productId);
                }
            }
        });
    }

    // Manejar acción del carrito (agregar/quitar)
    async handleCartAction(button, productId) {
        if (this.isLoading) return;
        
        const inCart = button.classList.contains('in-cart');
        if (inCart) {
            await this.removeFromCart(button, productId);
        } else {
            await this.addToCart(button, productId);
        }
    }

    // Agregar al carrito
    async addToCart(button, productId) {
        if (this.isLoading || button.disabled) return;

        this.isLoading = true;
        this.setButtonLoading(button);

        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('action', 'add');

            const response = await fetch('ajax/add-to-cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartData(data);
                this.setButtonSuccess(button);
                this.updateUI();
                this.showNotification('Producto agregado al carrito', 'success');
                this.updateAllButtonsForProduct(productId, true);
            } else {
                this.setButtonError(button);
                this.showNotification(data.message || 'Error al agregar al carrito', 'error');
            }
        } catch (error) {
            console.error('Error al agregar al carrito:', error);
            this.setButtonError(button);
            this.showNotification('Error de conexión', 'error');
        } finally {
            this.isLoading = false;
        }
    }

    // Quitar del carrito
    async removeFromCart(button, productId) {
        if (this.isLoading || button.disabled) return;

        this.isLoading = true;
        this.setButtonLoading(button);

        try {
            const response = await fetch('ajax/remove-from-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartData(data);
                this.setButtonDefault(button);
                this.updateUI();
                this.showNotification('Producto eliminado del carrito', 'success');
                this.updateAllButtonsForProduct(productId, false);
            } else {
                this.setButtonError(button);
                this.showNotification(data.message || 'Error al eliminar del carrito', 'error');
            }
        } catch (error) {
            console.error('Error al eliminar del carrito:', error);
            this.setButtonError(button);
            this.showNotification('Error de conexión', 'error');
        } finally {
            this.isLoading = false;
        }
    }

    // Actualizar estado visual del botón
    setButtonLoading(button) {
        button.disabled = true;
        const loadingIcon = button.querySelector('.loading-icon');
        const cartIcon = button.querySelector('.cart-icon');
        if (loadingIcon) loadingIcon.style.display = 'inline-flex';
        if (cartIcon) cartIcon.style.display = 'none';
    }

    setButtonSuccess(button) {
        button.disabled = false;
        button.classList.add('in-cart');
        const successIcon = button.querySelector('.success-icon');
        const loadingIcon = button.querySelector('.loading-icon');
        const cartIcon = button.querySelector('.cart-icon');
        
        if (successIcon) successIcon.style.display = 'inline-flex';
        if (loadingIcon) loadingIcon.style.display = 'none';
        if (cartIcon) cartIcon.style.display = 'none';
    }

    setButtonDefault(button) {
        button.disabled = false;
        button.classList.remove('in-cart');
        const cartIcon = button.querySelector('.cart-icon');
        const loadingIcon = button.querySelector('.loading-icon');
        const successIcon = button.querySelector('.success-icon');
        
        if (cartIcon) cartIcon.style.display = 'inline-flex';
        if (loadingIcon) loadingIcon.style.display = 'none';
        if (successIcon) successIcon.style.display = 'none';
    }

    setButtonError(button) {
        button.disabled = false;
        const cartIcon = button.querySelector('.cart-icon');
        const loadingIcon = button.querySelector('.loading-icon');
        if (cartIcon) cartIcon.style.display = 'inline-flex';
        if (loadingIcon) loadingIcon.style.display = 'none';
    }

    // Actualizar datos del carrito
    updateCartData(data) {
        if (data.cart_count !== undefined) {
            this.updateCartCount(data.cart_count);
        }
        if (data.cart_total !== undefined) {
            this.updateCartTotal(data.cart_total);
        }
    }

    // Actualizar contador del carrito
    updateCartCount(count) {
        const cartCountElement = document.querySelector('#cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            if (count > 0) {
                cartCountElement.style.display = 'inline-flex';
            } else {
                cartCountElement.style.display = 'none';
            }
        }
    }

    // Actualizar total del carrito
    updateCartTotal(total) {
        const cartTotalElement = document.querySelector('#cart-total');
        if (cartTotalElement) {
            cartTotalElement.textContent = total;
        }
    }

    // Actualizar interfaz general
    updateUI() {
        this.updateCartDisplay();
    }

    // Actualizar visualización del carrito
    updateCartDisplay() {
        const cartCount = document.querySelector('#cart-count');
        if (cartCount && cartCount.textContent === '0') {
            cartCount.style.display = 'none';
        }
    }

    // Actualizar todos los botones del mismo producto
    updateAllButtonsForProduct(productId, inCart) {
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
        buttons.forEach(button => {
            if (inCart) {
                this.setButtonSuccess(button);
            } else {
                this.setButtonDefault(button);
            }
        });
    }

    // Mostrar notificación
    showNotification(message, type = 'success') {
        // Implementar según el sistema de notificaciones existente
        const event = new CustomEvent('show-notification', {
            detail: { message, type }
        });
        document.dispatchEvent(event);
    }
}

// Inicializar el sistema unificado cuando el DOM esté listo
let unifiedCartSystem;
document.addEventListener('DOMContentLoaded', () => {
    unifiedCartSystem = new UnifiedCartSystem();
    window.cartSystem = unifiedCartSystem; // Para compatibilidad
});