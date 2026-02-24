/**
 * =====================================================
 * SHIPPING CALCULATOR - JavaScript
 * Calcula envíos dinámicos en el carrito
 * =====================================================
 */

let selectedShipping = null;
let shippingOptions = [];

// Inicializar al cargar página
document.addEventListener('DOMContentLoaded', function() {
    initShippingCalculator();
});

function initShippingCalculator() {
    const calculateBtn = document.getElementById('calculateShipping');
    const postalCodeInput = document.getElementById('postalCodeInput');
    const changePostalBtn = document.getElementById('changePostal');
    
    if (calculateBtn) {
        calculateBtn.addEventListener('click', function() {
            const postalCode = postalCodeInput.value.trim();
            if (postalCode) {
                calculateShippingOptions(postalCode);
            } else {
                showError('Por favor ingresa un código postal');
            }
        });
    }
    
    if (changePostalBtn) {
        changePostalBtn.addEventListener('click', function() {
            resetShippingCalculator();
        });
    }
    
    // Enter en input de CP
    if (postalCodeInput) {
        postalCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                calculateBtn.click();
            }
        });
    }
}

/**
 * Calcular opciones de envío
 */
function calculateShippingOptions(postalCode) {
    // Validar formato
    if (!/^\d{4}$/.test(postalCode)) {
        showError('El código postal debe tener 4 dígitos');
        return;
    }
    
    // Mostrar loading
    const calculateBtn = document.getElementById('calculateShipping');
    const originalText = calculateBtn.innerHTML;
    calculateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculando...';
    calculateBtn.disabled = true;
    
    // Obtener peso total del carrito (o usar default)
    const cartWeight = calculateCartWeight();
    const cartTotal = parseFloat(document.querySelector('[data-cart-total]')?.dataset.cartTotal || 10000);
    
    // Hacer request AJAX
    fetch('ajax/calculate-shipping.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `postal_code=${postalCode}&cart_weight=${cartWeight}&cart_total=${cartTotal}`
    })
    .then(response => response.json())
    .then(data => {
        calculateBtn.innerHTML = originalText;
        calculateBtn.disabled = false;
        
        if (data.success) {
            shippingOptions = data.options;
            showShippingOptions(data.options, postalCode);
        } else {
            showError(data.message || 'Error al calcular envío');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        calculateBtn.innerHTML = originalText;
        calculateBtn.disabled = false;
        showError('Error de conexión. Intenta nuevamente.');
    });
}

/**
 * Mostrar opciones de envío calculadas
 */
function showShippingOptions(options, postalCode) {
    // Ocultar formulario de CP y mostrar confirmación
    document.getElementById('postalCodeForm').style.display = 'none';
    document.getElementById('postalConfirmation').style.display = 'block';
    document.getElementById('confirmedCP').textContent = postalCode;
    
    // Mostrar opciones de envío
    const shippingContainer = document.getElementById('shippingOptions');
    const shippingOptionsHTML = document.getElementById('shippingOptionsHTML');
    
    if (!shippingOptionsHTML) {
        console.error('Contenedor de opciones no encontrado');
        return;
    }
    
    // Generar HTML de opciones
    let html = '';
    options.forEach((option, index) => {
        const isFirst = index === 0;
        const estimatedBadge = option.is_estimated ? '<span class="badge bg-warning text-dark ms-2">Estimado</span>' : '';
        
        html += `
            <div class="form-check mb-3 p-3 border border-secondary rounded shipping-option">
                <input class="form-check-input" 
                       type="radio" 
                       name="shippingMethod" 
                       id="${option.id}" 
                       value="${option.price}"
                       data-provider="${option.provider}"
                       data-name="${option.name}"
                       data-days="${option.delivery_days}"
                       onchange="updateShippingSelection(${option.price}, '${option.id}', '${option.name}')"
                       ${isFirst ? 'checked' : ''}>
                <label class="form-check-label text-white w-100" for="${option.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-bold">${option.name}${estimatedBadge}</div>
                            <div class="text-muted small">${option.description}</div>
                            <div class="text-success small">
                                <i class="fas fa-clock me-1"></i>${option.delivery_text}
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <span class="text-danger fw-bold fs-5">${option.price_formatted}</span>
                        </div>
                    </div>
                </label>
            </div>
        `;
    });
    
    shippingOptionsHTML.innerHTML = html;
    shippingContainer.style.display = 'block';
    
    // Seleccionar automáticamente la primera opción (más barata)
    if (options.length > 0) {
        updateShippingSelection(options[0].price, options[0].id, options[0].name);
    }
    
    // Mostrar sección de total
    document.getElementById('totalSection').style.display = 'block';
}

/**
 * Actualizar selección de envío
 */
function updateShippingSelection(cost, methodId, methodName) {
    selectedShipping = {
        cost: parseFloat(cost),
        method: methodId,
        name: methodName
    };
    
    // Guardar en sesión via AJAX
    fetch('ajax/set-shipping.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `shipping_cost=${cost}&shipping_method=${methodId}&shipping_name=${encodeURIComponent(methodName)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartTotals();
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Actualizar totales del carrito
 */
function updateCartTotals() {
    const subtotalElement = document.getElementById('cartSubtotal');
    const shippingElement = document.getElementById('shippingCost');
    const totalElement = document.getElementById('cartTotal');
    
    if (!subtotalElement || !shippingElement || !totalElement) {
        return;
    }
    
    const subtotal = parseFloat(subtotalElement.dataset.value || 0);
    const shippingCost = selectedShipping ? selectedShipping.cost : 0;
    const total = subtotal + shippingCost;
    
    // Actualizar valores
    shippingElement.textContent = formatPrice(shippingCost);
    totalElement.textContent = formatPrice(total);
    
    // Habilitar botón de checkout
    const checkoutBtn = document.querySelector('.btn-checkout');
    if (checkoutBtn && shippingCost > 0) {
        checkoutBtn.disabled = false;
        checkoutBtn.classList.remove('disabled');
    }
}

/**
 * Resetear calculador de envíos
 */
function resetShippingCalculator() {
    document.getElementById('postalCodeForm').style.display = 'block';
    document.getElementById('postalConfirmation').style.display = 'none';
    document.getElementById('shippingOptions').style.display = 'none';
    document.getElementById('totalSection').style.display = 'none';
    document.getElementById('postalCodeInput').value = '';
    selectedShipping = null;
    shippingOptions = [];
}

/**
 * Calcular peso total del carrito
 */
function calculateCartWeight() {
    // Por ahora retornar peso default (1kg)
    // Podrías agregar data-weight a cada producto
    const cartItems = document.querySelectorAll('[data-product-weight]');
    let totalWeight = 0;
    
    cartItems.forEach(item => {
        const weight = parseFloat(item.dataset.productWeight || 1);
        const quantity = parseInt(item.dataset.productQuantity || 1);
        totalWeight += weight * quantity;
    });
    
    return totalWeight > 0 ? totalWeight : 1.0;
}

/**
 * Formatear precio
 */
function formatPrice(price) {
    return '$' + Math.round(price).toLocaleString('es-AR');
}

/**
 * Mostrar error
 */
function showError(message) {
    // Podrías usar un toast/modal, por ahora alert
    alert(message);
}
