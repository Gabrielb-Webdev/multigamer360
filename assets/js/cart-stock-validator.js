// Validar stock del carrito al cargar la página
function validateCartStock() {
    fetch('ajax/validate_cart_stock.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_issues) {
                // Mostrar alertas sobre problemas de stock
                let messages = [];
                
                data.items.forEach(item => {
                    if (item.status === 'out_of_stock') {
                        messages.push(`❌ "${item.name}" ha sido eliminado del carrito (agotado)`);
                    } else if (item.status === 'stock_limited') {
                        messages.push(`⚠️ "${item.name}": cantidad ajustada a ${item.adjusted_quantity} unidades (stock limitado)`);
                    }
                });
                
                if (messages.length > 0) {
                    alert('⚠️ Cambios en tu carrito:\n\n' + messages.join('\n'));
                    // Recargar la página para mostrar los cambios
                    location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Error validando stock del carrito:', error);
        });
}

// Validar al cargar la página del carrito
if (window.location.pathname.includes('carrito.php')) {
    document.addEventListener('DOMContentLoaded', function() {
        validateCartStock();
    });
}

// Validar periódicamente cada 30 segundos si está en el carrito
if (window.location.pathname.includes('carrito.php')) {
    setInterval(validateCartStock, 30000);
}
