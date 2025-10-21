/**
 * =====================================================
 * SISTEMA DE BOTÓN MODERNO DE CARRITO
 * =====================================================
 * 
 * Descripción: Sistema JavaScript para manejar el botón de agregar al carrito
 * con animaciones de carga y éxito según especificaciones del usuario
 * 
 * Estados del botón:
 * 1. Normal: Fondo blanco, icono carrito + texto "Agregar al carrito"
 * 2. Carga: Fondo gris, spinner de carga (4-5 segundos)
 * 3. En Carrito: Fondo verde, icono carrito + check (permanente hasta quitar del carrito)
 */

// Encapsular en IIFE para evitar conflictos
(function() {
    'use strict';
    
    // Si ya existe, no redefinir
    if (window.ModernCartButton) {
        console.log('ModernCartButton ya existe, evitando redefinición');
        return;
    }
    
    class ModernCartButton {
    constructor() {
        this.isProcessing = false;
        this.loadingTimeout = 3000; // Tiempo de carga cambiado a 3 segundos
        this.productsInCart = new Set(); // Tracking de productos en carrito
        this.init();
    }

    /**
     * Inicializar el sistema de botones
     */
    init() {
        this.attachEventListeners();
        this.checkInitialCartState();
        console.log('ModernCartButton: Sistema inicializado');
    }

    /**
     * Adjuntar event listeners a los botones
     */
    attachEventListeners() {
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.btn-add-to-cart-modern');
            if (button && !this.isProcessing) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = button.getAttribute('data-product-id');
                
                if (productId) {
                    // Si el producto está en el carrito, redirigir al carrito
                    if (this.productsInCart.has(productId)) {
                        console.log('ModernCartButton: Producto en carrito, redirigiendo...');
                        this.redirectToCart();
                    } 
                    // Si no está en el carrito y el botón no está deshabilitado, agregar
                    else if (!button.disabled) {
                        this.handleAddToCart(button, productId);
                    }
                }
            }
        });
    }

    /**
     * Manejar la acción de agregar al carrito
     * @param {HTMLElement} button - El botón clickeado
     * @param {string} productId - ID del producto
     */
    async handleAddToCart(button, productId) {
        if (this.isProcessing) return;

        console.log(`ModernCartButton: Agregando producto ${productId} al carrito`);
        
        // Marcar como procesando
        this.isProcessing = true;
        
        // Cambiar a estado de carga
        this.setButtonLoadingState(button);
        
        try {
            // Simular tiempo mínimo de carga para mostrar la animación
            const [ajaxResult] = await Promise.all([
                this.sendAddToCartRequest(productId),
                this.delay(this.loadingTimeout)
            ]);

            console.log('ModernCartButton: Resultado de la petición:', ajaxResult);

            if (ajaxResult.success) {
                console.log('ModernCartButton: ✅ Producto agregado exitosamente');
                console.log('ModernCartButton: Datos recibidos:', ajaxResult);
                
                // Éxito: cambiar a estado "En Carrito" permanente
                this.setButtonInCartState(button, productId);
                // this.showNotification('Producto agregado al carrito correctamente', 'success');
                
                // Actualizar contador y precio total
                this.updateCartDisplay(ajaxResult.cart_count, ajaxResult.cart_total);
                
                // Marcar producto como en carrito
                this.productsInCart.add(productId);
                this.isProcessing = false;
                
            } else {
                console.log('ModernCartButton: ❌ Error del servidor:', ajaxResult.message);
                // Error: volver al estado normal y mostrar mensaje
                this.resetButtonState(button);
                // this.showNotification(ajaxResult.message || 'Error al agregar el producto', 'error');
                this.isProcessing = false;
            }
            
        } catch (error) {
            console.error('ModernCartButton: Error en la petición AJAX:', error);
            this.resetButtonState(button);
            // this.showNotification('Error de conexión. Intenta nuevamente.', 'error');
            this.isProcessing = false;
        }
    }

    /**
     * Enviar petición AJAX para agregar al carrito
     * @param {string} productId - ID del producto
     * @returns {Promise} - Promesa con la respuesta del servidor
     */
    async sendAddToCartRequest(productId) {
        console.log(`ModernCartButton: Enviando petición AJAX para producto ${productId}`);
        
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        console.log('ModernCartButton: FormData creado:', {
            product_id: productId,
            quantity: 1
        });

        const response = await fetch('/ajax/add-to-cart.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // Incluir cookies de sesión
            headers: {
                'Cache-Control': 'no-cache'
            }
        });

        console.log(`ModernCartButton: Respuesta HTTP ${response.status} ${response.statusText}`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const jsonResult = await response.json();
        console.log('ModernCartButton: Respuesta JSON:', jsonResult);
        
        return jsonResult;
    }

    /**
     * Establecer estado de carga del botón
     * @param {HTMLElement} button - El botón a modificar
     */
    setButtonLoadingState(button) {
        button.classList.add('loading');
        button.disabled = true;
        
        // Ocultar elementos normales
        const cartIcon = button.querySelector('.cart-icon');
        const buttonText = button.querySelector('.btn-text');
        const loadingSpinner = button.querySelector('.loading-spinner');
        
        if (cartIcon) cartIcon.style.display = 'none';
        if (buttonText) buttonText.style.display = 'none';
        if (loadingSpinner) loadingSpinner.style.display = 'inline-block';
        
        console.log('ModernCartButton: Estado de carga activado');
    }

    /**
     * Establecer estado "En Carrito" del botón (permanente hasta quitar del carrito)
     * @param {HTMLElement} button - El botón a modificar
     * @param {string} productId - ID del producto
     */
    setButtonInCartState(button, productId) {
        console.log(`ModernCartButton: Iniciando setButtonInCartState para producto ${productId}`);
        
        button.classList.remove('loading');
        button.classList.add('in-cart');
        button.disabled = false; // Permitir clics para redirección al carrito
        
        console.log(`ModernCartButton: Clases del botón después de cambios: ${button.className}`);
        
        // Mostrar carrito + check según especificación del usuario
        const cartIcon = button.querySelector('.cart-icon');
        const buttonText = button.querySelector('.btn-text');
        const loadingSpinner = button.querySelector('.loading-spinner');
        const successCheck = button.querySelector('.success-check');
        
        console.log('ModernCartButton: Elementos encontrados:', {
            cartIcon: cartIcon ? 'SÍ' : 'NO',
            buttonText: buttonText ? 'SÍ' : 'NO',
            loadingSpinner: loadingSpinner ? 'SÍ' : 'NO',
            successCheck: successCheck ? 'SÍ' : 'NO'
        });
        
        if (cartIcon) {
            cartIcon.style.display = 'inline-block';
            console.log('ModernCartButton: Carrito mostrado');
        }
        if (successCheck) {
            successCheck.style.display = 'inline-block';
            console.log('ModernCartButton: Check mostrado');
        }
        if (buttonText) {
            buttonText.style.display = 'none';
            console.log('ModernCartButton: Texto ocultado');
        }
        if (loadingSpinner) {
            loadingSpinner.style.display = 'none';
            console.log('ModernCartButton: Spinner ocultado');
        }
        
        // Cambiar el cursor para indicar que es clickeable
        button.style.cursor = 'pointer';
        
        // Debug final
        const finalStyle = window.getComputedStyle(button);
        console.log(`ModernCartButton: Color de fondo final: ${finalStyle.backgroundColor}`);
        console.log(`ModernCartButton: Producto ${productId} marcado como en carrito`);
    }

    /**
     * Resetear botón al estado normal
     * @param {HTMLElement} button - El botón a resetear
     */
    resetButtonState(button) {
        button.classList.remove('loading', 'success', 'in-cart');
        button.disabled = false;
        
        // Mostrar elementos normales
        const cartIcon = button.querySelector('.cart-icon');
        const buttonText = button.querySelector('.btn-text');
        const loadingSpinner = button.querySelector('.loading-spinner');
        const successCheck = button.querySelector('.success-check');
        
        if (cartIcon) cartIcon.style.display = 'inline-block';
        if (buttonText) buttonText.style.display = 'inline-block';
        if (loadingSpinner) loadingSpinner.style.display = 'none';
        if (successCheck) successCheck.style.display = 'none';
        
        console.log('ModernCartButton: Estado normal restaurado');
    }

    /**
     * Verificar estado inicial del carrito al cargar la página
     * OPTIMIZADO: Ya no hace peticiones AJAX, lee del atributo data-in-cart
     */
    async checkInitialCartState() {
        const buttons = document.querySelectorAll('.btn-add-to-cart-modern');
        
        console.log(`ModernCartButton: Verificando estado inicial de ${buttons.length} botones`);
        
        for (const button of buttons) {
            const productId = button.getAttribute('data-product-id');
            const isInCart = button.getAttribute('data-in-cart') === 'true';
            
            if (productId && isInCart) {
                // El producto ya está en el carrito según PHP
                // No necesitamos hacer AJAX, simplemente actualizar el estado visual
                this.productsInCart.add(productId);
                
                // El botón ya tiene la clase 'in-cart' desde PHP, pero aseguramos el estado
                if (!button.classList.contains('in-cart')) {
                    button.classList.add('in-cart');
                }
                
                console.log(`ModernCartButton: Producto ${productId} marcado como en carrito (desde PHP)`);
            }
        }
        
        console.log('ModernCartButton: Estado inicial del carrito verificado (sin AJAX)');
    }

    /**
     * Remover producto del estado "en carrito" (llamar cuando se elimine del carrito)
     * @param {string} productId - ID del producto
     */
    removeFromCartState(productId) {
        this.productsInCart.delete(productId);
        
        // Buscar el botón correspondiente y resetear su estado
        const button = document.querySelector(`[data-product-id="${productId}"]`);
        if (button) {
            this.resetButtonState(button);
        }
        
        console.log(`ModernCartButton: Producto ${productId} removido del estado carrito`);
    }

    /**
     * Mostrar notificación al usuario
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de notificación (success, error, info)
     */
    showNotification(message, type = 'info') {
        // Buscar sistemas de notificación existentes
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else if (typeof Swal !== 'undefined') {
            const icon = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
            Swal.fire({
                text: message,
                icon: icon,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            // Fallback: usar alert nativo
            alert(message);
        }
    }

    /**
     * Actualizar display del carrito con contador y precio total
     * @param {number} count - Número de productos
     * @param {number} total - Precio total
     */
    updateCartDisplay(count, total) {
        // Actualizar contadores específicos
        const counters = document.querySelectorAll('.cart-count, [data-cart-count]');
        counters.forEach(counter => {
            counter.textContent = count || '0';
            counter.style.display = count > 0 ? 'inline-block' : 'none';
        });
        
        // Actualizar el display del carrito en el header solo con precio total
        const cartDisplay = document.getElementById('cart-display');
        if (cartDisplay) {
            const formattedTotal = total ? `$${parseFloat(total).toFixed(2)}` : '$0';
            cartDisplay.textContent = formattedTotal; // Solo mostrar el precio
        }
        
        console.log(`ModernCartButton: Display actualizado - ${count || 0} productos, total: $${total || 0}`);
        
        // Forzar una sincronización adicional para asegurar persistencia
        this.forceSyncCart();
    }
    
    /**
     * Forzar sincronización del carrito para mantener consistencia
     */
    forceSyncCart() {
        // Pequeño delay para permitir que el servidor procese completamente
        setTimeout(() => {
            fetch('/ajax/get-cart-count.php', {
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                const cartDisplay = document.getElementById('cart-display');
                if (cartDisplay && data.cart_total !== undefined) {
                    const formattedTotal = data.cart_total > 0 ? `$${parseFloat(data.cart_total).toFixed(2)}` : '$0';
                    cartDisplay.textContent = formattedTotal;
                    console.log('ModernCartButton: Force sync completed -', formattedTotal);
                }
            })
            .catch(error => console.log('Force sync error:', error));
        }, 100); // Muy breve delay para asegurar que el servidor haya procesado
    }

    /**
     * Actualizar contador del carrito en la UI (función legacy mantenida para compatibilidad)
     * @param {number} count - Nuevo contador
     */
    updateCartCounter(count) {
        // Actualizar contadores específicos
        const counters = document.querySelectorAll('.cart-count, [data-cart-count]');
        counters.forEach(counter => {
            counter.textContent = count || '0';
            counter.style.display = count > 0 ? 'inline-block' : 'none';
        });
        
        // Actualizar el display del carrito en el header (solo precio)
        const cartDisplay = document.getElementById('cart-display');
        if (cartDisplay) {
            // Mantener solo el precio, obtenerlo del texto actual si existe
            const currentText = cartDisplay.textContent;
            if (currentText.includes('$')) {
                // Mantener el precio actual
                const parts = currentText.split(' - ');
                if (parts.length === 2) {
                    cartDisplay.textContent = parts[1]; // Solo el precio
                }
            } else {
                cartDisplay.textContent = '$0'; // Default si no hay precio
            }
        }
        
        console.log(`ModernCartButton: Contador del carrito actualizado a ${count}`);
    }

    /**
     * Utility: Crear una promesa que se resuelve después de un delay
     * @param {number} ms - Milisegundos de delay
     * @returns {Promise}
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Redirigir al carrito de compras
     */
    redirectToCart() {
        console.log('ModernCartButton: Redirigiendo al carrito...');
        
        // Verificar si existe una página de carrito
        const cartPages = ['carrito.php', 'cart.php', 'shopping-cart.php'];
        let cartUrl = 'carrito.php'; // URL por defecto
        
        // Intentar determinar la URL correcta del carrito
        const cartLinks = document.querySelectorAll('a[href*="carrito"], a[href*="cart"]');
        if (cartLinks.length > 0) {
            cartUrl = cartLinks[0].getAttribute('href');
        }
        
        // Redirigir con una pequeña animación visual
        // this.showNotification('Redirigiendo al carrito...', 'info');
        
        setTimeout(() => {
            window.location.href = cartUrl;
        }, 500);
    }
}

// =====================================================
// INICIALIZACIÓN AUTOMÁTICA
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('ModernCartButton: DOM cargado, inicializando sistema...');
        window.modernCartButton = new ModernCartButton();
    } catch (error) {
        console.error('ModernCartButton: Error durante la inicialización:', error);
    }
});

// =====================================================
// FUNCIONES GLOBALES PARA INTEGRACIÓN
// =====================================================

/**
 * Función global para marcar un producto como removido del carrito
 * @param {string} productId - ID del producto a remover
 */
window.updateCartButtonState = function(productId, inCart = false) {
    if (window.modernCartButton) {
        if (inCart) {
            // Marcar como en carrito
            const button = document.querySelector(`[data-product-id="${productId}"]`);
            if (button) {
                window.modernCartButton.setButtonInCartState(button, productId);
                window.modernCartButton.productsInCart.add(productId);
            }
        } else {
            // Remover del carrito
            window.modernCartButton.removeFromCartState(productId);
        }
    }
};

// =====================================================
// COMPATIBILIDAD CON SISTEMAS EXISTENTES
// =====================================================

// Si existe el sistema de carrito unificado, integrarse con él
if (typeof window.unifiedCartSystem !== 'undefined') {
    console.log('ModernCartButton: Detectado sistema de carrito unificado, integrando...');
    // Aquí se podría agregar lógica de integración si es necesario
}

    // Hacer la clase disponible globalmente
    window.ModernCartButton = ModernCartButton;

    // NO inicializar automáticamente aquí - dejar que footer.php lo haga
    console.log('ModernCartButton class loaded and available globally');

})(); // Fin de IIFE