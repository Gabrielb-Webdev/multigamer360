// JavaScript para la página de detalles del producto

document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad para las imágenes en miniatura
    initProductThumbnails();
    
    // Funcionalidad para el selector de cantidad
    initQuantitySelector();
    
    // Funcionalidad para el calculador de envío
    initShippingCalculator();
});

// Funcionalidad para cambiar la imagen principal al hacer clic en las miniaturas
function initProductThumbnails() {
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const mainImage = document.querySelector('.main-image');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Remover clase active de todas las miniaturas
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            
            // Agregar clase active a la miniatura clickeada
            this.classList.add('active');
            
            // Cambiar la imagen principal
            const newImageSrc = this.querySelector('img').src;
            if (mainImage) {
                mainImage.src = newImageSrc;
            }
        });
    });
}

// Funcionalidad para los botones + y - del selector de cantidad
function initQuantitySelector() {
    const quantityInput = document.querySelector('.quantity-input');
    const minusBtn = document.querySelector('.quantity-btn.minus');
    const plusBtn = document.querySelector('.quantity-btn.plus');
    
    if (minusBtn && quantityInput) {
        minusBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    }
    
    if (plusBtn && quantityInput) {
        plusBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
        });
    }
    
    // Validar que solo se ingresen números en el input
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            }
        });
    }
}

// Funcionalidad para el calculador de envío
function initShippingCalculator() {
    const calculateBtn = document.querySelector('.btn-calculate');
    const postalCodeInput = document.querySelector('.shipping-form input');
    
    if (calculateBtn && postalCodeInput) {
        calculateBtn.addEventListener('click', function() {
            const postalCode = postalCodeInput.value.trim();
            
            if (!postalCode) {
                alert('Por favor, ingresa tu código postal');
                return;
            }
            
            // Simular cálculo de envío
            // Aquí puedes agregar la lógica real de cálculo de envío
            setTimeout(() => {
                alert(`Envío calculado para código postal: ${postalCode}\nCosto de envío: $5.000`);
            }, 500);
        });
        
        // Permitir calcular con Enter
        postalCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                calculateBtn.click();
            }
        });
    }
}

// Función para agregar al carrito (puedes expandir esta funcionalidad)
function addToCart() {
    const quantity = document.querySelector('.quantity-input').value;
    const productName = document.querySelector('.product-title').textContent;
    
    alert(`Agregado al carrito: ${productName} (Cantidad: ${quantity})`);
    
    // Aquí puedes agregar la lógica real para agregar al carrito
    // Por ejemplo, enviar una petición AJAX al servidor
}

// Event listener para el botón "Agregar al carrito"
document.addEventListener('DOMContentLoaded', function() {
    const addToCartBtn = document.querySelector('.add-to-cart-detail');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', addToCart);
    }
});
