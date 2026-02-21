# CAMBIOS NECESARIOS EN CHECKOUT.PHP

## 📝 Instrucciones para actualizar checkout.php

El archivo checkout.php actual tiene una estructura de pagos antigua. Necesitás hacer estos cambios:

---

## 1. REEMPLAZAR SECCIÓN DE MÉTODOS DE PAGO (Líneas 739-900 aprox)

Buscar esta sección:
```html
<!-- Método de Pago -->
<div class="payment-section">
```

Reemplazar TODO ese bloque con esta versión mejorada:

```php
<!-- Método de Pago ARGENTINA -->
<div class="payment-section">
    <h4><i class="fas fa-credit-card"></i> Método de Pago</h4>
    
    <?php
    // Determinar tipo de entrega
    $deliveryType = 'pickup_store'; // Default
    if ($shipping_cost > 0) {
        $deliveryType = 'shipping';
    }
    
    // Obtener métodos disponibles desde configuración
    $availableMethods = $paymentHelper->getAvailablePaymentMethods($deliveryType);
    ?>
    
    <div id="payment-methods-container">
        <?php foreach ($availableMethods as $method): ?>
            <?php
            $methodConfig = json_decode($method['config_json'], true);
            $isPresential = strpos($method['method_key'], 'presential') !== false;
            $isMercadoPago = $method['method_key'] === 'mercadopago_online';
            $isTransfer = $method['method_key'] === 'bank_transfer';
            ?>
            
            <div class="payment-option" data-method="<?php echo $method['method_key']; ?>">
                <label>
                    <input type="radio" name="paymentMethod" value="<?php echo $method['method_key']; ?>" required>
                    <div class="payment-info">
                        <div class="payment-title">
                            <strong><?php echo htmlspecialchars($method['method_name']); ?></strong>
                            
                            <?php if ($isTransfer && !empty($methodConfig['discount_percentage'])): ?>
                                <span class="badge bg-success ms-2">
                                    <?php echo $methodConfig['discount_percentage']; ?>% OFF
                                </span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($methodConfig['description'] ?? ''); ?>
                        </small>
                    </div>
                </label>
            </div>
            
        <?php endforeach; ?>
    </div>
    
    <!-- Detalles de Transferencia Bancaria (se muestra cuando selecciona transfer) -->
    <div id="transfer-details" class="payment-details" style="display: none;">
        <?php $bankAccount = $paymentHelper->getPrimaryBankAccount(); ?>
        <div class="alert alert-info">
            <h5><i class="fas fa-university"></i> Datos para la transferencia:</h5>
            <table class="table table-sm table-borderless text-white">
                <tr>
                    <td><strong>Banco:</strong></td>
                    <td><?php echo $bankAccount['bank_name']; ?></td>
                </tr>
                <tr>
                    <td><strong>CBU:</strong></td>
                    <td class="font-monospace"><?php echo $bankAccount['cbu']; ?></td>
                </tr>
                <tr>
                    <td><strong>Alias:</strong></td>
                    <td class="font-monospace"><?php echo $bankAccount['alias']; ?></td>
                </tr>
                <tr>
                    <td><strong>Titular:</strong></td>
                    <td><?php echo $bankAccount['holder_name']; ?></td>
                </tr>
            </table>
            <p class="mb-0">
                <small>💡 Realizá la transferencia y subí el comprobante antes de 48hs. Te enviaremos los datos por email también.</small>
            </p>
        </div>
    </div>
    
    <!-- Detalles de Pago Presencial (se muestra cuando selecciona presencial) -->
    <div id="presential-details" class="payment-details" style="display: none;">
        <?php $storeInfo = $paymentConfig['presential']['store_info']; ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-store"></i> Retirá y pagá en <?php echo $storeInfo['name']; ?></h5>
            <p>
                📍 <strong>Dirección:</strong> <?php echo $storeInfo['address']; ?><br>
                📞 <strong>Teléfono:</strong> <?php echo $storeInfo['phone']; ?><br>
                🕐 <strong>Horarios:</strong> <?php echo $storeInfo['schedule']['monday']; ?>
            </p>
            <p class="mb-0">
                <strong>Métodos de pago aceptados:</strong>
                Efectivo, Tarjeta débito/crédito, QR Mercado Pago, Transferencia inmediata
            </p>
        </div>
    </div>
</div>

<!-- JavaScript para mostrar/ocultar detalles según método seleccionado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentOptions = document.querySelectorAll('input[name="paymentMethod"]');
    const transferDetails = document.getElementById('transfer-details');
    const presentialDetails = document.getElementById('presential-details');
    
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Ocultar todos los detalles
            transferDetails.style.display = 'none';
            presentialDetails.style.display = 'none';
            
            // Mostrar detalles según método seleccionado
            if (this.value === 'bank_transfer') {
                transferDetails.style.display = 'block';
            } else if (this.value.includes('presential')) {
                presentialDetails.style.display = 'block';
            }
            
            // Actualizar texto del botón de finalizar
            updateCheckoutButton();
        });
    });
    
    function updateCheckoutButton() {
        const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked');
        const checkoutBtn = document.querySelector('.btn-checkout');
        
        if (selectedMethod) {
            checkoutBtn.classList.add('active');
            
            if (selectedMethod.value === 'mercadopago_online') {
                checkoutBtn.innerHTML = '<i class="fas fa-credit-card"></i> PAGAR CON MERCADO PAGO';
            } else if (selectedMethod.value === 'bank_transfer') {
                checkoutBtn.innerHTML = '<i class="fas fa-university"></i> CONTINUAR CON TRANSFERENCIA';
            } else if (selectedMethod.value.includes('presential')) {
                checkoutBtn.innerHTML = '<i class="fas fa-check"></i> GENERAR CÓDIGO DE RESERVA';
            } else {
                checkoutBtn.innerHTML = '<i class="fas fa-check"></i> FINALIZAR COMPRA';
            }
        } else {
            checkoutBtn.classList.remove('active');
            checkoutBtn.innerHTML = '<i class="fas fa-lock"></i> Seleccioná un método de pago';
        }
    }
});
</script>
```

---

## 2. ACTUALIZAR ESTILOS CSS (Agregar estas clases)

Buscar la sección `<style>` y agregar:

```css
.payment-option {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option:hover {
    border-color: var(--accent-red);
    background: rgba(220, 53, 69, 0.1);
}

.payment-option label {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin: 0;
    width: 100%;
}

.payment-option input[type="radio"] {
    margin-right: 15px;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.payment-option input[type="radio"]:checked + .payment-info {
    color: var(--accent-red);
}

.payment-info {
    flex: 1;
}

.payment-title {
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.payment-details {
    margin-top: 20px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
```

---

## 3. PROBARLO

Después de hacer los cambios:

1. Ir a tu checkout
2. Verificar que se muestren las opciones de pago de Argentina
3. Seleccionar cada método y ver que aparezcan los detalles correctos
4. Verificar que el botón cambie de texto según el método

---

**NOTA:** Como el archivo checkout.php es muy largo (1600+ líneas), estos cambios te permitirán integrar el nuevo sistema de pagos argentina sin romper la funcionalidad existente.

Si preferís, puedo crear un archivo checkout_v2.php completamente nuevo con todo integrado.

¿Seguimos con process_checkout.php que es más crítico?
