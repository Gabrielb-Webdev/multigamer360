// Sistema de Validación de Stock del Carrito v2.6
// Bandera para evitar validaciones duplicadas
let isValidating = false;
let validationShown = false;

// Validar stock del carrito al cargar la página
function validateCartStock() {
    // Evitar validaciones duplicadas
    if (isValidating || validationShown) {
        console.log('Validación ya en proceso o ya mostrada');
        return;
    }
    
    isValidating = true;
    console.log('Iniciando validación de stock...');
    
    fetch('ajax/validate_cart_stock.php')
        .then(response => response.json())
        .then(data => {
            isValidating = false;
            console.log('Respuesta de validación:', data);
            
            if (data.success && data.has_issues) {
                // Productos con problemas de stock
                let outOfStock = [];
                let stockLimited = [];
                
                data.items.forEach(item => {
                    if (item.status === 'out_of_stock') {
                        outOfStock.push(item);
                    } else if (item.status === 'stock_limited') {
                        stockLimited.push(item);
                    }
                });
                
                console.log('Productos agotados:', outOfStock);
                console.log('Productos con stock limitado:', stockLimited);
                
                // Mostrar modal solo si hay productos afectados
                if (outOfStock.length > 0 || stockLimited.length > 0) {
                    validationShown = true;
                    showStockIssuesModal(outOfStock, stockLimited);
                } else {
                    console.log('No hay productos afectados');
                }
            } else {
                console.log('Sin problemas de stock');
            }
        })
        .catch(error => {
            isValidating = false;
            console.error('Error validando stock del carrito:', error);
        });
}

// Mostrar modal con productos afectados
function showStockIssuesModal(outOfStock, stockLimited) {
    console.log('Mostrando modal de cambios en carrito');
    
    // Crear modal si no existe
    let modal = document.getElementById('stockIssuesModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'stockIssuesModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-hidden', 'true');
        document.body.appendChild(modal);
    }
    
    // Construir contenido del modal
    let modalContent = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-danger">
                <div class="modal-header border-danger">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning"></i> 
                        Cambios en tu Carrito
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="confirmStockChanges()"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Algunos productos en tu carrito han cambiado debido a actualizaciones de stock:</p>
    `;
    
    // Productos agotados
    if (outOfStock.length > 0) {
        modalContent += `
            <div class="alert alert-danger mb-3">
                <h6 class="alert-heading">
                    <i class="fas fa-times-circle"></i> 
                    Productos Agotados (serán eliminados)
                </h6>
                <ul class="mb-0">
        `;
        outOfStock.forEach(item => {
            modalContent += `<li><strong>${item.name}</strong> - Ya no disponible</li>`;
        });
        modalContent += `</ul></div>`;
    }
    
    // Productos con stock limitado
    if (stockLimited.length > 0) {
        modalContent += `
            <div class="alert alert-warning mb-3">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle"></i> 
                    Cantidad Ajustada
                </h6>
                <ul class="mb-0">
        `;
        stockLimited.forEach(item => {
            modalContent += `
                <li>
                    <strong>${item.name}</strong><br>
                    <small>Cantidad ajustada: ${item.requested_quantity} → ${item.adjusted_quantity} unidades (stock disponible)</small>
                </li>
            `;
        });
        modalContent += `</ul></div>`;
    }
    
    modalContent += `
                    <p class="text-info mt-3">
                        <i class="fas fa-lightbulb"></i> 
                        Otros usuarios pueden haber comprado estos productos mientras estaban en tu carrito.
                    </p>
                </div>
                <div class="modal-footer border-danger">
                    <button type="button" class="btn btn-danger" onclick="confirmStockChanges()">
                        <i class="fas fa-check"></i> Entendido
                    </button>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = modalContent;
    
    // Mostrar el modal
    const bsModal = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: false
    });
    bsModal.show();
}

// Confirmar y recargar página
function confirmStockChanges() {
    console.log('Usuario confirmó cambios, recargando página...');
    
    // Quitar el foco del botón primero (soluciona el error aria-hidden)
    if (document.activeElement) {
        document.activeElement.blur();
    }
    
    // Forzar recarga inmediata
    window.location.reload();
}

// Validar al cargar la página del carrito
if (window.location.pathname.includes('carrito.php')) {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, validando stock del carrito...');
        validateCartStock();
    });
}

// Validar periódicamente cada 30 segundos si está en el carrito
if (window.location.pathname.includes('carrito.php')) {
    setInterval(function() {
        if (!validationShown) {
            console.log('Validación periódica (30s)...');
            validateCartStock();
        }
    }, 30000);
}
