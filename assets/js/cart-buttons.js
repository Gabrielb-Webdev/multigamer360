// Sistema de carrito con animaciones
document.addEventListener('DOMContentLoaded', function() {
    initializeCartButtons();
});

function initializeCartButtons() {
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.btn-add-cart');
        if (button && !button.disabled) {
            e.preventDefault();
            handleCartAction(button);
        }
    });
}

async function handleCartAction(button) {
    if (button.classList.contains('loading')) return;
    
    const productId = button.getAttribute('data-product-id');
    if (!productId) return;

    // Activar estado de carga
    button.classList.add('loading');
    button.disabled = true;

    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        const response = await fetch('ajax/add-to-cart.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar el contador del carrito si existe
            updateCartCounter(data.cart_count, data.cart_total);
            
            // Mostrar notificación de éxito
            showNotification('Producto agregado al carrito', 'success');

            // Quitar estado de carga después de un momento
            setTimeout(() => {
                button.classList.remove('loading');
                button.disabled = false;
            }, 1000);
        } else {
            showNotification(data.message || 'Error al agregar al carrito', 'error');
            button.classList.remove('loading');
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
        button.classList.remove('loading');
        button.disabled = false;
    }
}

function updateCartCounter(count = 0, total = 0) {
    const cartDisplay = document.querySelector('#cart-display');
    if (cartDisplay) {
        cartDisplay.textContent = `${count} - $${total}`;
    }
}

function showNotification(message, type = 'success') {
    // Implementar según el sistema de notificaciones existente
    if (window.showCartFeedback) {
        window.showCartFeedback(message, type);
    } else {
        console.log(`${type}: ${message}`);
    }
}