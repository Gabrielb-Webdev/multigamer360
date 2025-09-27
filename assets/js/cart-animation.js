// Funciones para animaciones del carrito
function addToCartWithAnimation(button, productId, productName = '') {
    // Verificar si el elemento es válido
    if (!button) {
        console.error('Botón no encontrado');
        return;
    }

    // Obtener los elementos del botón
    const loadingIcon = button.querySelector('.loading-icon');
    const successIcon = button.querySelector('.success-icon');
    const cartIcon = button.querySelector('.cart-icon');
    const removeIcon = button.querySelector('.remove-icon');
    const buttonText = button.querySelector('.button-text');

    // Estado inicial - ocultar todo excepto loading
    if (loadingIcon) loadingIcon.style.display = 'inline-flex';
    if (successIcon) successIcon.style.display = 'none';
    if (cartIcon) cartIcon.style.display = 'none';
    if (removeIcon) removeIcon.style.display = 'none';
    if (buttonText) buttonText.style.display = 'none';

    // Agregar clase de carga
    button.classList.add('loading');

    // Hacer la petición AJAX
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=add_more`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cambiar a estado de éxito
            button.classList.remove('loading');
            button.classList.add('success');
            
            if (loadingIcon) loadingIcon.style.display = 'none';
            if (successIcon) successIcon.style.display = 'inline-flex';
            
            // Actualizar contador del carrito
            updateCartCount(data.cart_count, data.cart_total || 0);
            
            // Mostrar notificación
            showCartFeedback(productName || 'Producto agregado al carrito', 'success');
            
            // Actualizar TODOS los botones de este producto en la página DESPUÉS del éxito
            setTimeout(() => {
                updateAllButtonsForProduct(productId, true);
            }, 100);
            
            // Después de 2 segundos, cambiar a estado "en carrito"
            setTimeout(() => {
                button.classList.remove('success');
                button.classList.add('in-cart');
                
                if (successIcon) successIcon.style.display = 'none';
                if (removeIcon) removeIcon.style.display = 'inline-flex';
                if (buttonText) buttonText.style.display = 'inline-block';
                
                // Refrescar contador desde servidor para estar seguro
                refreshCartCountFromServer();
            }, 2000);
        } else {
            // Error - volver al estado normal inmediatamente
            resetButtonToNormal(button);
            showCartFeedback(data.message || 'Error al agregar producto', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resetButtonToNormal(button);
        showCartFeedback('Error de conexión', 'error');
    });
}

// Función para eliminar del carrito
function removeFromCartWithAnimation(button, productId, productName = '') {
    if (!button) {
        console.error('Botón no encontrado');
        return;
    }

    // Obtener los elementos del botón
    const loadingIcon = button.querySelector('.loading-icon');
    const successIcon = button.querySelector('.success-icon');
    const cartIcon = button.querySelector('.cart-icon');
    const removeIcon = button.querySelector('.remove-icon');
    const buttonText = button.querySelector('.button-text');

    // Estado de carga
    if (loadingIcon) loadingIcon.style.display = 'inline-flex';
    if (successIcon) successIcon.style.display = 'none';
    if (cartIcon) cartIcon.style.display = 'none';
    if (removeIcon) removeIcon.style.display = 'none';
    if (buttonText) buttonText.style.display = 'none';

    button.classList.add('loading');

    // Hacer la petición AJAX
    fetch('ajax/remove-from-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=clear`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contador del carrito
            updateCartCount(data.cart_count, data.cart_total || 0);
            
            // Mostrar notificación
            showCartFeedback(productName ? `${productName} eliminado del carrito` : 'Producto eliminado del carrito', 'success');
            
            // Actualizar TODOS los botones de este producto en la página DESPUÉS del éxito
            setTimeout(() => {
                updateAllButtonsForProduct(productId, false);
            }, 100);
            
            // Cambiar a estado normal
            button.classList.remove('loading', 'in-cart');
            resetButtonToNormal(button);
            
            // Refrescar contador desde servidor para estar seguro
            setTimeout(() => {
                refreshCartCountFromServer();
            }, 500);
        } else {
            // Error - volver al estado normal
            resetButtonToNormal(button);
            showCartFeedback(data.message || 'Error al eliminar producto', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resetButtonToNormal(button);
        showCartFeedback('Error de conexión', 'error');
    });
}

// Función para resetear botón a estado normal
function resetButtonToNormal(button) {
    const loadingIcon = button.querySelector('.loading-icon');
    const successIcon = button.querySelector('.success-icon');
    const cartIcon = button.querySelector('.cart-icon');
    const removeIcon = button.querySelector('.remove-icon');
    const buttonText = button.querySelector('.button-text');
    
    button.classList.remove('loading', 'success', 'in-cart');
    
    if (loadingIcon) loadingIcon.style.display = 'none';
    if (successIcon) successIcon.style.display = 'none';
    if (removeIcon) removeIcon.style.display = 'none';
    if (cartIcon) cartIcon.style.display = 'inline-flex';
    if (buttonText) buttonText.style.display = 'inline-block';
}

// Función para actualizar el evento del botón
function updateButtonEvent(button, productId, productName) {
    // Verificar el estado actual en el carrito
    checkCartStatus(productId).then(data => {
        if (data.in_cart) {
            // Producto está en carrito - configurar para eliminar
            button.classList.add('in-cart');
            const cartIcon = button.querySelector('.cart-icon');
            const removeIcon = button.querySelector('.remove-icon');
            
            if (cartIcon) cartIcon.style.display = 'none';
            if (removeIcon) removeIcon.style.display = 'inline-flex';
            
            // Remover listeners anteriores y agregar nuevo
            button.replaceWith(button.cloneNode(true));
            const newButton = button.parentNode.querySelector('.btn-cart[data-product-id="' + productId + '"]');
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                removeFromCartWithAnimation(this, productId, productName);
            });
        } else {
            // Producto no está en carrito - configurar para agregar
            button.classList.remove('in-cart');
            resetButtonToNormal(button);
            
            // Remover listeners anteriores y agregar nuevo
            button.replaceWith(button.cloneNode(true));
            const newButton = button.parentNode.querySelector('.btn-cart[data-product-id="' + productId + '"]');
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                addToCartWithAnimation(this, productId, productName);
            });
        }
    });
}

// Función para verificar estado del carrito
function checkCartStatus(productId) {
    return fetch(`ajax/check-cart-item.php?product_id=${productId}`)
        .then(response => response.json())
        .catch(error => {
            console.error('Error verificando estado del carrito:', error);
            return { success: false, in_cart: false };
        });
}

// Funciones para wishlist
function toggleWishlist(button, productId, productName = '') {
    if (!button) {
        console.error('Botón no encontrado');
        return;
    }

    const icon = button.querySelector('i');
    const wasInWishlist = icon.classList.contains('fas');

    // Animación de loading
    button.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';

    fetch('ajax/toggle-wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=toggle`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar icono según el estado
            if (data.in_wishlist) {
                icon.className = 'fas fa-heart';
                button.style.color = '#dc3545';
                showCartFeedback(data.message, 'success');
            } else {
                icon.className = 'far fa-heart';
                button.style.color = '';
                showCartFeedback(data.message, 'success');
            }
            
            // Actualizar contador de wishlist
            updateWishlistCount(data.wishlist_count);
        } else {
            // Error - revertir estado
            icon.className = wasInWishlist ? 'fas fa-heart' : 'far fa-heart';
            showCartFeedback(data.message || 'Error al actualizar lista de deseos', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Error - revertir estado
        icon.className = wasInWishlist ? 'fas fa-heart' : 'far fa-heart';
        showCartFeedback('Error de conexión', 'error');
    })
    .finally(() => {
        button.disabled = false;
    });
}

// Función para verificar estado de wishlist
function checkWishlistStatus(productId) {
    return fetch(`ajax/check-wishlist-item.php?product_id=${productId}`)
        .then(response => response.json())
        .catch(error => {
            console.error('Error verificando estado de wishlist:', error);
            return { success: false, in_wishlist: false };
        });
}

// Función para actualizar el contador del carrito con animación
function updateCartCount(newCount, totalAmount = 0) {
    console.log('Actualizando contador carrito:', newCount, 'Total:', totalAmount);
    
    // Actualizar el display principal del header
    const cartDisplay = document.getElementById('cart-display');
    if (cartDisplay) {
        const formattedTotal = new Intl.NumberFormat('es-CO').format(totalAmount);
        cartDisplay.textContent = `${newCount} - $${formattedTotal}`;
        console.log('Cart display actualizado:', cartDisplay.textContent);
    } else {
        console.error('Elemento cart-display no encontrado');
    }
    
    // Mantener compatibilidad con badges existentes
    const cartCountElements = document.querySelectorAll('.cart-count, .badge');
    
    cartCountElements.forEach(element => {
        // Animación de cambio
        element.style.transform = 'scale(1.3)';
        element.style.transition = 'transform 0.3s ease';
        
        // Actualizar el número
        element.textContent = newCount;
        
        // Volver al tamaño normal
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 300);
        
        // Mostrar/ocultar según el contador
        if (newCount > 0) {
            element.style.display = 'inline-block';
        } else {
            element.style.display = 'none';
        }
    });
    
    // Actualizar cualquier otro elemento que pueda mostrar el total
    const cartTotalElements = document.querySelectorAll('.cart-total-display');
    cartTotalElements.forEach(element => {
        const formattedTotal = new Intl.NumberFormat('es-CO').format(totalAmount);
        element.textContent = `$${formattedTotal}`;
    });
}

// Función para refrescar el contador del carrito desde el servidor
function refreshCartCountFromServer() {
    console.log('Refrescando contador desde servidor...');
    
    fetch('ajax/get-cart-count.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor para contador:', data);
            if (data.success) {
                updateCartCount(data.cart_count, data.cart_total || 0);
                console.log('Contador refrescado:', data.cart_count, 'Total:', data.cart_total);
            } else {
                console.error('Error en respuesta del servidor:', data.message);
            }
        })
        .catch(error => {
            console.error('Error refrescando contador:', error);
        });
}

// Función para actualizar el contador de wishlist con animación
function updateWishlistCount(newCount) {
    const wishlistCountElements = document.querySelectorAll('.wishlist-count');
    
    wishlistCountElements.forEach(element => {
        // Animación de cambio
        element.style.transform = 'scale(1.3)';
        element.style.transition = 'transform 0.3s ease';
        
        // Actualizar el número
        element.textContent = newCount;
        
        // Volver al tamaño normal
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 300);
        
        // Mostrar/ocultar según el contador
        if (newCount > 0) {
            element.style.display = 'inline-block';
        } else {
            element.style.display = 'none';
        }
    });
}

// Función para mostrar notificaciones
function showCartFeedback(message, type = 'success') {
    // Crear o encontrar el contenedor de notificaciones
    let notificationContainer = document.getElementById('notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;
        document.body.appendChild(notificationContainer);
    }

    // Crear la notificación
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 8px;
        max-width: 300px;
    `;

    // Agregar icono según el tipo
    const icon = type === 'success' ? '✅' : '❌';
    notification.innerHTML = `${icon} ${message}`;

    // Agregar al contenedor
    notificationContainer.appendChild(notification);

    // Animar entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);

    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Función para inicializar los eventos del carrito
function initializeCartButtons() {
    // Botones de agregar al carrito
    document.querySelectorAll('.btn-cart').forEach(button => {
        const productId = button.getAttribute('data-product-id');
        const productName = button.getAttribute('data-product-name') || '';
        
        if (!productId) return;
        
        // Verificar estado inicial del producto en el carrito
        checkCartStatus(productId).then(data => {
            updateButtonState(button, data.in_cart, productId, productName);
        }).catch(error => {
            console.error('Error verificando estado del carrito para producto', productId, error);
        });
        
        // Agregar event listener que decide qué hacer según el estado
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.classList.contains('in-cart')) {
                // Está en carrito, eliminar
                removeFromCartWithAnimation(this, productId, productName);
            } else {
                // No está en carrito, agregar
                addToCartWithAnimation(this, productId, productName);
            }
        });
    });

    // Botones de wishlist (los corazones de arriba)
    document.querySelectorAll('.btn-wishlist').forEach(button => {
        const productId = button.getAttribute('data-product-id');
        const productName = button.getAttribute('data-product-name') || '';
        
        // Verificar estado inicial del producto en la wishlist
        checkWishlistStatus(productId).then(data => {
            if (data.in_wishlist) {
                const icon = button.querySelector('i');
                icon.className = 'fas fa-heart';
                button.style.color = '#dc3545';
            }
        });
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            toggleWishlist(this, productId, productName);
        });
    });
}

// Nueva función para actualizar el estado visual del botón
function updateButtonState(button, inCart, productId, productName) {
    const cartIcon = button.querySelector('.cart-icon');
    const removeIcon = button.querySelector('.remove-icon');
    const buttonText = button.querySelector('.button-text');
    
    if (inCart) {
        // Producto está en carrito - mostrar botón de eliminar
        button.classList.add('in-cart');
        button.classList.remove('loading', 'success');
        
        if (cartIcon) cartIcon.style.display = 'none';
        if (removeIcon) removeIcon.style.display = 'inline-flex';
        if (buttonText) buttonText.style.display = 'inline-block';
    } else {
        // Producto no está en carrito - mostrar botón de agregar
        button.classList.remove('in-cart', 'loading', 'success');
        
        if (cartIcon) cartIcon.style.display = 'inline-flex';
        if (removeIcon) removeIcon.style.display = 'none';
        if (buttonText) buttonText.style.display = 'inline-block';
    }
}

// Función para actualizar todos los botones de un producto específico
function updateAllButtonsForProduct(productId, inCart) {
    console.log(`Actualizando botones para producto ${productId}, inCart: ${inCart}`);
    
    const buttons = document.querySelectorAll(`[data-product-id="${productId}"].btn-cart`);
    console.log(`Encontrados ${buttons.length} botones para producto ${productId}`);
    
    buttons.forEach(button => {
        const productName = button.getAttribute('data-product-name') || '';
        updateButtonState(button, inCart, productId, productName);
        
        // Solo re-crear el event listener si no existe o es diferente al estado actual
        const hasCorrectListener = button.hasAttribute('data-listener-updated');
        const currentState = button.classList.contains('in-cart');
        
        if (!hasCorrectListener || currentState !== inCart) {
            // Remover listener anterior si existe
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Marcar que tiene el listener correcto
            newButton.setAttribute('data-listener-updated', 'true');
            
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.classList.contains('in-cart')) {
                    // Está en carrito, eliminar
                    removeFromCartWithAnimation(this, productId, productName);
                } else {
                    // No está en carrito, agregar
                    addToCartWithAnimation(this, productId, productName);
                }
            });
            
            console.log(`Event listener actualizado para botón producto ${productId}`);
        }
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando sistema de carrito...');
    
    // Inicializar botones del carrito
    initializeCartButtons();
    
    // Cargar contador del carrito al iniciar la página - PRIORIDAD ALTA
    fetch('ajax/get-cart-count.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta de get-cart-count:', data);
            if (data.success) {
                updateCartCount(data.cart_count, data.cart_total || 0);
                console.log('Contador de carrito actualizado:', data.cart_count, 'Total:', data.cart_total);
                
                // Si hay productos en el carrito, verificar el estado de los botones en la página
                if (data.cart_count > 0 && data.cart) {
                    Object.keys(data.cart).forEach(productId => {
                        updateAllButtonsForProduct(productId, true);
                    });
                }
            } else {
                console.error('Error en respuesta de carrito:', data.message);
                // Inicializar con 0 si hay error
                updateCartCount(0, 0);
            }
        })
        .catch(error => {
            console.error('Error cargando contador de carrito:', error);
            // Inicializar con 0 si hay error de conexión
            updateCartCount(0, 0);
        });

    // Cargar contador de wishlist al iniciar la página
    fetch('ajax/get-wishlist-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateWishlistCount(data.wishlist_count);
                console.log('Contador de wishlist inicializado:', data.wishlist_count);
            }
        })
        .catch(error => console.error('Error cargando contador de wishlist:', error));
});

// Función global para reinicializar botones (útil para contenido dinámico)
window.reinitializeCartButtons = function() {
    console.log('Reinicializando botones del carrito...');
    initializeCartButtons();
};

// Función global para actualizar estado de un producto específico
window.refreshProductState = function(productId) {
    console.log('Actualizando estado del producto:', productId);
    checkCartStatus(productId).then(data => {
        updateAllButtonsForProduct(productId, data.in_cart);
    }).catch(error => {
        console.error('Error al actualizar estado del producto:', productId, error);
    });
};