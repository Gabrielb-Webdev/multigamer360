<?php
/**
 * =====================================================
 * MULTIGAMER360 - PÁGINA DE CHECKOUT
 * =====================================================
 * 
 * Descripción: Proceso de finalización de compra
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-16
 * 
 * Funcionalidades:
 * - Finalización del proceso de compra
 * - Validación de carrito con productos
 * - Información de envío y facturación
 * - Métodos de pago disponibles
 * - Resumen de la orden
 * - Aplicación de cupones de descuento
 * - Generación de orden de compra
 */

// =====================================================
// CONFIGURACIÓN INICIAL
// =====================================================

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependencias necesarias
require_once 'config/database.php';
require_once 'includes/cart_manager.php';
require_once 'includes/auth.php';

// =====================================================
// OBTENER INFORMACIÓN DEL USUARIO
// =====================================================
$user = null;
$user_data = [
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'phone' => '',
    'postal_code' => ''
];

if (isLoggedIn()) {
    $user = getCurrentUser();
    
    if ($user) {
        $user_data = [
            'firstName' => $user['first_name'] ?? '',
            'lastName' => $user['last_name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'postal_code' => $user['postal_code'] ?? ''
        ];
    }
}

// =====================================================
// VALIDACIONES PREVIAS
// =====================================================

// Verificar si hay productos en el carrito
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: carrito.php');
    exit();
}

// Verificar si se tiene información de envío (debe venir del carrito)
if (!isset($_SESSION['shipping_method'])) {
    // Si no hay método de envío, redirigir al carrito
    header('Location: carrito.php');
    exit();
}

// =====================================================
// INICIALIZAR CART MANAGER
// =====================================================
$cartManager = new CartManager($pdo);

// =====================================================
// OBTENER PRODUCTOS DEL CARRITO DESDE LA BD
// =====================================================
$cart_items = [];
$cart_total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price_pesos as price, p.image_url,
               COALESCE(
                   (SELECT pi.image_url 
                    FROM product_images pi 
                    WHERE pi.product_id = p.id 
                    AND pi.is_primary = 1
                    LIMIT 1),
                   p.image_url
               ) as primary_image
        FROM products p
        WHERE p.id IN ($placeholders)
    ");
    
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $cart_total += $subtotal;
        
        // Procesar la ruta de la imagen
        $image_url = $product['primary_image'] ?? $product['image_url'];
        
        // Si la imagen no tiene una ruta completa, agregar la ruta de uploads
        if ($image_url && !preg_match('/^(http|https|\/)/i', $image_url)) {
            $image_url = 'uploads/products/' . $image_url;
        }
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $image_url,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

// Obtener información de envío seleccionada
$shipping_method = $_SESSION['shipping_method'] ?? '';
$shipping_cost = $_SESSION['shipping_cost'] ?? 0;
$shipping_name = $_SESSION['shipping_name'] ?? 'No especificado';
$postal_code = $_SESSION['postal_code'] ?? '';

$total_with_shipping = $cart_total + $shipping_cost;

// =====================================================
// INCLUIR HEADER
// =====================================================
require_once 'includes/header.php';
?>

<style>
.checkout-page {
    background-color: var(--bg-dark);
    min-height: 100vh;
    color: var(--text-light);
}

.checkout-container {
    padding: 2rem 0;
}

.checkout-header {
    text-align: center;
    margin-bottom: 2rem;
}

.checkout-header h1 {
    color: var(--accent-red);
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.checkout-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 2rem;
}

.order-summary {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(108, 117, 125, 0.1));
    border: 1px solid var(--accent-red);
}

.order-summary h3 {
    color: var(--accent-red);
    border-bottom: 2px solid var(--accent-red);
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.product-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.product-item:last-child {
    border-bottom: none;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 1rem;
}

.product-details {
    flex: 1;
}

.product-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.product-quantity {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.product-price {
    font-weight: 700;
    color: var(--accent-red);
}

.summary-totals {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--accent-red);
}

.total-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.total-line.final {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--accent-red);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 0.5rem;
    margin-top: 1rem;
}

.shipping-section h4 {
    color: var(--accent-red);
    margin-bottom: 1rem;
}

.shipping-option {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.shipping-option:hover {
    background: rgba(220, 53, 69, 0.1);
    border-color: var(--accent-red);
}

.shipping-option.selected {
    background: rgba(220, 53, 69, 0.2);
    border-color: var(--accent-red);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.change-shipping {
    margin-left: 1rem;
}

.btn-outline-secondary {
    border-color: rgba(255, 255, 255, 0.3);
    color: var(--text-light);
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
}

.btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--accent-red);
    color: var(--accent-red);
}

.payment-details {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.payment-details h5 {
    color: var(--accent-red);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

.payment-method-option {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.payment-method-option:hover {
    background: rgba(220, 53, 69, 0.1);
    border-color: var(--accent-red);
    transform: translateY(-1px);
}

.payment-method-option.selected {
    background: rgba(220, 53, 69, 0.2);
    border-color: var(--accent-red);
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.3);
}

.payment-method-info {
    display: flex;
    flex-direction: column;
    margin-left: 0.75rem;
    flex: 1;
}

.payment-method-title {
    font-weight: 600;
    color: var(--text-light);
    margin-bottom: 0.25rem;
}

.payment-method-icons {
    display: flex;
    gap: 0.5rem;
    font-size: 1.2rem;
}

.payment-method-option input[type="radio"] {
    margin-right: 0;
}

.payment-confirmation {
    margin-bottom: 1rem;
}

.card-form {
    background: rgba(18, 18, 18, 0.95);
    border: 1px solid #444;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.card-form h5 {
    color: #fff;
    margin-bottom: 1.5rem;
    font-weight: 600;
}

.card-form .form-group {
    margin-bottom: 1rem;
}

.card-form .form-label {
    color: #fff;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.card-form .form-control {
    background-color: #2c2c2c;
    border: 1px solid #444;
    color: #fff;
    border-radius: 6px;
    padding: 0.75rem;
}

.card-form .form-control:focus {
    background-color: #2c2c2c;
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    color: #fff;
}

.card-form .form-control::placeholder {
    color: #aaa;
}

.card-logos {
    display: flex;
    gap: 0.5rem;
    font-size: 1.5rem;
}

.security-info .alert {
    background-color: rgba(13, 202, 240, 0.1);
    border: 1px solid rgba(13, 202, 240, 0.3);
    color: #0dcaf0;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .payment-method-option {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .payment-method-info {
        margin-left: 0;
    }
    
    .payment-method-icons {
        align-self: flex-end;
    }
}

.shipping-option input[type="radio"] {
    margin-right: 0.75rem;
}

.shipping-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.shipping-price {
    color: var(--accent-red);
    font-weight: 700;
}

.payment-section {
    margin-top: 2rem;
}

.payment-section h4 {
    color: var(--accent-red);
    margin-bottom: 1rem;
}

.payment-option {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option:hover {
    background: rgba(220, 53, 69, 0.1);
    border-color: var(--accent-red);
}

.payment-option.selected {
    background: rgba(220, 53, 69, 0.2);
    border-color: var(--accent-red);
}

.payment-option input[type="radio"] {
    margin-right: 0.75rem;
}

.customer-info {
    margin-top: 2rem;
}

.customer-info h4 {
    color: var(--accent-red);
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    color: var(--text-light);
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    color: var(--text-light);
    width: 100%;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--accent-red);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.btn-checkout {
    background: linear-gradient(135deg, var(--accent-red), #b02a37);
    border: none;
    color: white;
    padding: 1rem 3rem;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 2rem;
}

.btn-checkout:hover {
    background: linear-gradient(135deg, #b02a37, var(--accent-red));
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

.btn-back {
    background: transparent;
    border: 2px solid var(--accent-red);
    color: var(--accent-red);
    padding: 0.75rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
    margin-bottom: 2rem;
}

.btn-back:hover {
    background: var(--accent-red);
    color: white;
    transform: translateY(-2px);
}

.hidden {
    display: none !important;
}

@media (max-width: 768px) {
    .checkout-container {
        padding: 1rem 0;
    }
    
    .checkout-card {
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .checkout-header h1 {
        font-size: 2rem;
    }
}
</style>

<main class="checkout-page">
    <div class="container checkout-container">
        <div class="checkout-header">
            <a href="carrito.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver al Carrito
            </a>
            <h1>Finalizar Compra</h1>
        </div>

        <div class="row">
            <!-- Resumen del Pedido -->
            <div class="col-lg-5">
                <div class="checkout-card order-summary">
                    <h3><i class="fas fa-shopping-bag"></i> Resumen del Pedido</h3>
                    
                    <div class="products-list">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="product-item">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                <div class="product-details">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-quantity">Cantidad: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="product-price">$<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="total-line">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cart_total, 0, ',', '.'); ?></span>
                        </div>
                        <div class="total-line">
                            <span>Envío (<?php echo htmlspecialchars($shipping_name); ?>):</span>
                            <span>
                                <?php if ($shipping_cost == 0): ?>
                                    Gratis
                                <?php else: ?>
                                    $<?php echo number_format($shipping_cost, 0, ',', '.'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!empty($postal_code)): ?>
                        <div class="total-line">
                            <span>Código Postal:</span>
                            <span><?php echo htmlspecialchars($postal_code); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="total-line final">
                            <span>Total:</span>
                            <span>$<?php echo number_format($total_with_shipping, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Checkout -->
            <div class="col-lg-7">
                <div class="checkout-card">
                    <form id="checkout-form" method="POST" action="process_checkout.php">
                        <!-- Información de Envío Seleccionada -->
                        <div class="shipping-section">
                            <h4><i class="fas fa-truck"></i> Método de Envío Seleccionado</h4>
                            
                            <div class="shipping-option selected">
                                <input type="hidden" name="shippingMethod" value="<?php echo $shipping_method; ?>">
                                <div>
                                    <div class="shipping-title"><?php echo htmlspecialchars($shipping_name); ?></div>
                                    <div class="shipping-price">
                                        <?php if ($shipping_cost == 0): ?>
                                            Gratis
                                        <?php else: ?>
                                            $<?php echo number_format($shipping_cost, 0, ',', '.'); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($postal_code)): ?>
                                        <small class="text-muted">CP: <?php echo htmlspecialchars($postal_code); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="change-shipping">
                                    <a href="carrito.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Cambiar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div class="payment-section">
                            <h4><i class="fas fa-credit-card"></i> Método de Pago</h4>
                            
                            <!-- Selector de método de pago (inicial) -->
                            <div id="payment-method-selector">
                                <div id="payment-local" class="payment-option">
                                    <input type="radio" name="paymentMethod" id="local" value="local">
                                    <div>
                                        <div class="shipping-title" id="payment-local-title">
                                            <?php if ($shipping_method == '2207'): ?>
                                                Pagar en Punto de Retiro
                                            <?php else: ?>
                                                Pagar en el Local
                                            <?php endif; ?>
                                        </div>
                                        <small id="payment-local-description">
                                            <?php if ($shipping_method == '2207'): ?>
                                                Efectivo o tarjeta en el punto de retiro
                                            <?php else: ?>
                                                Efectivo o tarjeta en el momento del retiro
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>

                                <div id="payment-online" class="payment-option">
                                    <input type="radio" name="paymentMethod" id="online" value="online">
                                    <div>
                                        <div class="shipping-title">Pago Online</div>
                                        <small>Tarjeta de crédito/débito o transferencia</small>
                                    </div>
                                </div>

                                <div id="payment-cod" class="payment-option">
                                    <input type="radio" name="paymentMethod" id="cod" value="cod">
                                    <div>
                                        <div class="shipping-title">Contra Entrega</div>
                                        <small>Pagar al momento de recibir el producto</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirmación de método de pago seleccionado -->
                            <div id="payment-method-confirmed" style="display: none;">
                                <div class="shipping-option selected">
                                    <input type="hidden" name="confirmedPaymentMethod" id="confirmedPaymentMethod">
                                    <div>
                                        <div class="shipping-title" id="confirmed-payment-title"></div>
                                        <small id="confirmed-payment-description"></small>
                                    </div>
                                    <div class="change-shipping">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changePaymentMethod()">
                                            <i class="fas fa-edit"></i> Cambiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de Pago Online (desplegable) -->
                        <div id="online-payment-options" class="payment-details" style="display: none;">
                            <h5><i class="fas fa-credit-card"></i> Selecciona tu método de pago online</h5>
                            
                            <div class="payment-method-option" onclick="selectOnlinePayment('credit-card')">
                                <input type="radio" name="onlinePaymentMethod" id="credit-card" value="credit-card">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-credit-card me-2"></i>Tarjeta de Crédito
                                    </div>
                                    <small class="text-muted">Visa, Mastercard, American Express</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fab fa-cc-visa text-primary"></i>
                                    <i class="fab fa-cc-mastercard text-warning"></i>
                                    <i class="fab fa-cc-amex text-info"></i>
                                </div>
                            </div>

                            <div class="payment-method-option" onclick="selectOnlinePayment('debit-card')">
                                <input type="radio" name="onlinePaymentMethod" id="debit-card" value="debit-card">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-credit-card me-2"></i>Tarjeta de Débito
                                    </div>
                                    <small class="text-muted">Débito Visa, Mastercard</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fas fa-university text-success"></i>
                                </div>
                            </div>

                            <div class="payment-method-option" onclick="selectOnlinePayment('bank-transfer')">
                                <input type="radio" name="onlinePaymentMethod" id="bank-transfer" value="bank-transfer">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-university me-2"></i>Transferencia Bancaria
                                    </div>
                                    <small class="text-muted">CBU o Alias bancario</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fas fa-money-bill-transfer text-primary"></i>
                                </div>
                            </div>

                            <div class="payment-method-option" onclick="selectOnlinePayment('digital-wallet')">
                                <input type="radio" name="onlinePaymentMethod" id="digital-wallet" value="digital-wallet">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-mobile-alt me-2"></i>Billetera Digital
                                    </div>
                                    <small class="text-muted">Mercado Pago, Ualá, Brubank</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fas fa-wallet text-success"></i>
                                </div>
                            </div>

                            <div class="payment-method-option" onclick="selectOnlinePayment('crypto')">
                                <input type="radio" name="onlinePaymentMethod" id="crypto" value="crypto">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fab fa-bitcoin me-2"></i>Criptomonedas
                                    </div>
                                    <small class="text-muted">Bitcoin, USDT, Ethereum</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fab fa-bitcoin text-warning"></i>
                                    <i class="fab fa-ethereum text-info"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmación de Tarjeta de Crédito -->
                        <div id="credit-card-confirmed" class="payment-confirmation" style="display: none;">
                            <div class="bg-dark border border-success rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-success fw-bold">
                                            <i class="fas fa-credit-card me-2"></i>Tarjeta de Crédito seleccionada
                                        </div>
                                        <div class="text-muted small">Visa, Mastercard, American Express</div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" onclick="changeCardType()">
                                        <i class="fas fa-edit"></i> CAMBIAR
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmación de Tarjeta de Débito -->
                        <div id="debit-card-confirmed" class="payment-confirmation" style="display: none;">
                            <div class="bg-dark border border-success rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-success fw-bold">
                                            <i class="fas fa-credit-card me-2"></i>Tarjeta de Débito seleccionada
                                        </div>
                                        <div class="text-muted small">Débito Visa, Mastercard</div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" onclick="changeCardType()">
                                        <i class="fas fa-edit"></i> CAMBIAR
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Campos de Tarjeta -->
                        <div id="card-details" class="card-form" style="display: none;">
                            <h5><i class="fas fa-credit-card"></i> Datos de la Tarjeta</h5>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label" for="cardNumber">Número de Tarjeta *</label>
                                        <input type="text" class="form-control" id="cardNumber" name="cardNumber" 
                                               placeholder="1234 5678 9012 3456" maxlength="19" 
                                               oninput="formatCardNumber(this)">
                                        <div class="card-logos mt-2">
                                            <i class="fab fa-cc-visa text-primary"></i>
                                            <i class="fab fa-cc-mastercard text-warning"></i>
                                            <i class="fab fa-cc-amex text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="cardName">Nombre en la Tarjeta *</label>
                                        <input type="text" class="form-control" id="cardName" name="cardName" 
                                               placeholder="JUAN PEREZ" style="text-transform: uppercase;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="cardCVV">CVV *</label>
                                        <input type="text" class="form-control" id="cardCVV" name="cardCVV" 
                                               placeholder="123" maxlength="4" oninput="formatCVV(this)">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="cardMonth">Mes de Vencimiento *</label>
                                        <select class="form-control" id="cardMonth" name="cardMonth">
                                            <option value="">Seleccionar mes</option>
                                            <option value="01">01 - Enero</option>
                                            <option value="02">02 - Febrero</option>
                                            <option value="03">03 - Marzo</option>
                                            <option value="04">04 - Abril</option>
                                            <option value="05">05 - Mayo</option>
                                            <option value="06">06 - Junio</option>
                                            <option value="07">07 - Julio</option>
                                            <option value="08">08 - Agosto</option>
                                            <option value="09">09 - Septiembre</option>
                                            <option value="10">10 - Octubre</option>
                                            <option value="11">11 - Noviembre</option>
                                            <option value="12">12 - Diciembre</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="cardYear">Año de Vencimiento *</label>
                                        <select class="form-control" id="cardYear" name="cardYear">
                                            <option value="">Seleccionar año</option>
                                            <?php for($year = date('Y'); $year <= date('Y') + 20; $year++): ?>
                                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label" for="cardInstallments">Cuotas *</label>
                                        <select class="form-control" id="cardInstallments" name="cardInstallments">
                                            <option value="1">1 cuota sin interés</option>
                                            <option value="3">3 cuotas sin interés</option>
                                            <option value="6">6 cuotas sin interés</option>
                                            <option value="12">12 cuotas con interés</option>
                                            <option value="18">18 cuotas con interés</option>
                                            <option value="24">24 cuotas con interés</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="security-info mt-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <strong>Pago Seguro:</strong> Tus datos están protegidos con encriptación SSL de 256 bits.
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de Pago Local (desplegable) -->
                        <div id="local-payment-options" class="payment-details" style="display: none;">
                            <h5><i class="fas fa-store"></i> Opciones de pago en el local</h5>
                            
                            <div class="payment-method-option" onclick="selectLocalPayment('cash')">
                                <input type="radio" name="localPaymentMethod" id="cash" value="cash">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-money-bill me-2"></i>Efectivo
                                    </div>
                                    <small class="text-muted">Pesos argentinos</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fas fa-dollar-sign text-success"></i>
                                </div>
                            </div>

                            <div class="payment-method-option" onclick="selectLocalPayment('card-local')">
                                <input type="radio" name="localPaymentMethod" id="card-local" value="card-local">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-credit-card me-2"></i>Tarjeta (Crédito/Débito)
                                    </div>
                                    <small class="text-muted">Con POS en el local</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fas fa-credit-card text-primary"></i>
                                </div>
                            </div>

                            <div class="payment-method-option" onclick="selectLocalPayment('usd-cash')">
                                <input type="radio" name="localPaymentMethod" id="usd-cash" value="usd-cash">
                                <div class="payment-method-info">
                                    <div class="payment-method-title">
                                        <i class="fas fa-dollar-sign me-2"></i>Dólares (USD)
                                    </div>
                                    <small class="text-muted">Efectivo en dólares</small>
                                </div>
                                <div class="payment-method-icons">
                                    <i class="fas fa-dollar-sign text-warning"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Cliente -->
                        <div class="customer-info">
                            <h4><i class="fas fa-user"></i> Información del Cliente</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="firstName">Nombre *</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user_data['firstName']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="lastName">Apellido *</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user_data['lastName']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="email">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="phone">Teléfono *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div id="delivery-address" class="hidden">
                                <div class="form-group">
                                    <label class="form-label" for="address">Dirección *</label>
                                    <input type="text" class="form-control" id="address" name="address">
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="city">Ciudad *</label>
                                            <input type="text" class="form-control" id="city" name="city">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="province">Provincia *</label>
                                            <input type="text" class="form-control" id="province" name="province">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="zipCode">Código Postal *</label>
                                            <input type="text" class="form-control" id="zipCode" name="zipCode" value="<?php echo htmlspecialchars($user_data['postal_code']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-checkout">
                            <i class="fas fa-lock"></i> Finalizar Compra
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let selectedPayment = null;

// Determinar métodos de pago disponibles basados en el método de envío
const shippingMethod = '<?php echo $shipping_method; ?>';
const isPickup = (shippingMethod === '0' || shippingMethod === '2207'); // multigamer360 o puntoRetiro

function initializePaymentOptions() {
    console.log('Initializing payment options...');
    const paymentLocal = document.getElementById('payment-local');
    const paymentOnline = document.getElementById('payment-online');
    const paymentCod = document.getElementById('payment-cod');
    const deliveryAddress = document.getElementById('delivery-address');
    
    console.log('Elements found:', {
        paymentLocal: paymentLocal,
        paymentOnline: paymentOnline,
        paymentCod: paymentCod,
        deliveryAddress: deliveryAddress
    });
    
    console.log('Shipping method:', shippingMethod, 'isPickup:', isPickup);
    
    if (isPickup) {
        // RETIRAR POR - Mostrar "pagar en el local" y pago online
        paymentLocal.style.display = 'block';
        paymentOnline.style.display = 'block';
        paymentCod.style.display = 'none';
        if (deliveryAddress) deliveryAddress.style.display = 'none';
        
        console.log('Pickup mode: showing local and online payment');
        
        // Remover required de campos de dirección
        if (document.getElementById('address')) {
            document.getElementById('address').removeAttribute('required');
            document.getElementById('city').removeAttribute('required');
            document.getElementById('province').removeAttribute('required');
            document.getElementById('zipCode').removeAttribute('required');
        }
    } else {
        // ENVÍO A DOMICILIO - Mostrar "contra entrega" y pago online
        paymentLocal.style.display = 'none';
        paymentOnline.style.display = 'block';
        paymentCod.style.display = 'block';
        
        // NO mostrar ni hacer required los campos de dirección aquí
        // Los campos de dirección se mostrarán cuando se seleccione el método de pago
        if (deliveryAddress) {
            deliveryAddress.style.display = 'none';
        }
        
        console.log('Delivery mode: showing online and COD payment');
    }
}

function selectPayment(paymentId) {
    console.log('selectPayment called with:', paymentId);
    
    // Obtener información del método seleccionado
    const paymentElement = document.getElementById(paymentId);
    console.log('Payment element found:', paymentElement);
    
    if (!paymentElement) {
        console.error('Payment element not found for ID:', paymentId);
        return;
    }
    
    const paymentOption = paymentElement.closest('.payment-option');
    if (!paymentOption) {
        console.error('Payment option container not found');
        return;
    }
    
    const paymentTitle = paymentOption.querySelector('.shipping-title').textContent;
    const paymentDescription = paymentOption.querySelector('small').textContent;
    
    console.log('Payment info:', { paymentTitle, paymentDescription });
    
    // Marcar el radio button correspondiente
    paymentElement.checked = true;
    
    // Ocultar selector y mostrar confirmación
    document.getElementById('payment-method-selector').style.display = 'none';
    document.getElementById('payment-method-confirmed').style.display = 'block';
    
    // Actualizar información en la confirmación
    document.getElementById('confirmed-payment-title').textContent = paymentTitle;
    document.getElementById('confirmed-payment-description').textContent = paymentDescription;
    document.getElementById('confirmedPaymentMethod').value = paymentId;
    
    selectedPayment = paymentId;
    
    console.log('Selected payment set to:', selectedPayment);
    
    // Mostrar campos de dirección y hacerlos required solo si es envío a domicilio
    const deliveryAddress = document.getElementById('delivery-address');
    if (!isPickup && (paymentId === 'online' || paymentId === 'cod')) {
        if (deliveryAddress) {
            // PRIMERO mostrar el contenedor
            deliveryAddress.classList.remove('hidden');
            deliveryAddress.style.display = 'block';
            
            // LUEGO hacer los campos required (después de un micro delay para que el DOM se actualice)
            setTimeout(() => {
                const addressField = document.getElementById('address');
                const cityField = document.getElementById('city');
                const provinceField = document.getElementById('province');
                const zipCodeField = document.getElementById('zipCode');
                
                if (addressField) addressField.setAttribute('required', 'required');
                if (cityField) cityField.setAttribute('required', 'required');
                if (provinceField) provinceField.setAttribute('required', 'required');
                if (zipCodeField) zipCodeField.setAttribute('required', 'required');
                
                console.log('Address fields shown and set as required');
            }, 10);
        }
    } else {
        // Si NO es envío a domicilio, remover el required
        if (deliveryAddress) {
            deliveryAddress.classList.add('hidden');
            deliveryAddress.style.display = 'none';
            
            const addressField = document.getElementById('address');
            const cityField = document.getElementById('city');
            const provinceField = document.getElementById('province');
            const zipCodeField = document.getElementById('zipCode');
            
            if (addressField) addressField.removeAttribute('required');
            if (cityField) cityField.removeAttribute('required');
            if (provinceField) provinceField.removeAttribute('required');
            if (zipCodeField) zipCodeField.removeAttribute('required');
        }
    }
    
    // Mostrar opciones específicas según el método
    hideAllPaymentDetails();
    
    if (paymentId === 'online') {
        document.getElementById('online-payment-options').style.display = 'block';
        console.log('Showing online payment options');
    } else if (paymentId === 'local') {
        document.getElementById('local-payment-options').style.display = 'block';
        console.log('Showing local payment options');
    }
}

function changePaymentMethod() {
    // Mostrar selector y ocultar confirmación
    document.getElementById('payment-method-selector').style.display = 'block';
    document.getElementById('payment-method-confirmed').style.display = 'none';
    
    // Limpiar selecciones
    selectedPayment = null;
    document.getElementById('confirmedPaymentMethod').value = '';
    
    // Ocultar campos de dirección y remover required
    const deliveryAddress = document.getElementById('delivery-address');
    if (deliveryAddress && !isPickup) {
        deliveryAddress.style.display = 'none';
        document.getElementById('address').removeAttribute('required');
        document.getElementById('city').removeAttribute('required');
        document.getElementById('province').removeAttribute('required');
        document.getElementById('zipCode').removeAttribute('required');
    }
    
    // Ocultar todas las opciones de pago detalladas
    hideAllPaymentDetails();
    
    // Ocultar confirmaciones de tarjetas (solo cuando se cambia de método)
    document.getElementById('credit-card-confirmed').style.display = 'none';
    document.getElementById('debit-card-confirmed').style.display = 'none';
    
    // Ocultar campos de tarjeta
    document.getElementById('card-details').style.display = 'none';
    
    // Limpiar selecciones de opciones específicas
    clearAllPaymentSelections();
    
    // Limpiar selecciones de los métodos principales
    document.querySelectorAll('input[name="paymentMethod"]').forEach(input => {
        input.checked = false;
    });
}

function hideAllPaymentDetails() {
    document.getElementById('online-payment-options').style.display = 'none';
    document.getElementById('local-payment-options').style.display = 'none';
}

function clearAllPaymentSelections() {
    // Limpiar selecciones online
    document.querySelectorAll('input[name="onlinePaymentMethod"]').forEach(input => {
        input.checked = false;
    });
    document.querySelectorAll('#online-payment-options .payment-method-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Limpiar selecciones locales
    document.querySelectorAll('input[name="localPaymentMethod"]').forEach(input => {
        input.checked = false;
    });
    document.querySelectorAll('#local-payment-options .payment-method-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Limpiar campos de tarjeta
    if (document.getElementById('cardNumber')) {
        document.getElementById('cardNumber').value = '';
        document.getElementById('cardName').value = '';
        document.getElementById('cardCVV').value = '';
        document.getElementById('cardMonth').value = '';
        document.getElementById('cardYear').value = '';
        document.getElementById('cardInstallments').value = '1';
    }
}

function selectOnlinePayment(methodId) {
    // Si es tarjeta de crédito o débito, mostrar confirmación y campos
    if (methodId === 'credit-card' || methodId === 'debit-card') {
        // Ocultar opciones de pago online
        document.getElementById('online-payment-options').style.display = 'none';
        
        // Mostrar confirmación correspondiente
        if (methodId === 'credit-card') {
            document.getElementById('credit-card-confirmed').style.display = 'block';
            document.getElementById('debit-card-confirmed').style.display = 'none';
        } else {
            document.getElementById('debit-card-confirmed').style.display = 'block';
            document.getElementById('credit-card-confirmed').style.display = 'none';
        }
        
        // Mostrar campos de tarjeta
        document.getElementById('card-details').style.display = 'block';
        
        // Marcar la opción seleccionada
        document.getElementById(methodId).checked = true;
        
    } else {
        // Para otros métodos de pago (transferencia, billetera digital, etc.)
        // Remover selección anterior
        document.querySelectorAll('#online-payment-options .payment-method-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Seleccionar nueva opción
        document.getElementById(methodId).checked = true;
        document.getElementById(methodId).closest('.payment-method-option').classList.add('selected');
        
        // Ocultar confirmaciones y campos de tarjeta
        document.getElementById('credit-card-confirmed').style.display = 'none';
        document.getElementById('debit-card-confirmed').style.display = 'none';
        document.getElementById('card-details').style.display = 'none';
    }
}

function selectLocalPayment(methodId) {
    // Remover selección anterior
    document.querySelectorAll('#local-payment-options .payment-method-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Seleccionar nueva opción
    document.getElementById(methodId).checked = true;
    document.getElementById(methodId).closest('.payment-method-option').classList.add('selected');
}

// Función para cambiar el tipo de tarjeta
function changeCardType() {
    // Ocultar confirmaciones
    document.getElementById('credit-card-confirmed').style.display = 'none';
    document.getElementById('debit-card-confirmed').style.display = 'none';
    
    // Ocultar campos de tarjeta
    document.getElementById('card-details').style.display = 'none';
    
    // Mostrar opciones de pago online nuevamente
    document.getElementById('online-payment-options').style.display = 'block';
    
    // Limpiar selecciones
    document.querySelectorAll('input[name="onlinePaymentMethod"]').forEach(input => {
        input.checked = false;
    });
    document.querySelectorAll('#online-payment-options .payment-method-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Limpiar campos de tarjeta
    document.getElementById('cardNumber').value = '';
    document.getElementById('cardName').value = '';
    document.getElementById('cardCVV').value = '';
    document.getElementById('cardMonth').value = '';
    document.getElementById('cardYear').value = '';
    document.getElementById('cardInstallments').value = '1';
}

// Función para formatear número de tarjeta
function formatCardNumber(input) {
    // Remover todos los espacios y caracteres no numéricos
    let value = input.value.replace(/\D/g, '');
    
    // Limitar a 16 dígitos
    value = value.substring(0, 16);
    
    // Agregar espacios cada 4 dígitos
    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    
    // Actualizar el valor del input
    input.value = value;
}

// Función para formatear CVV
function formatCVV(input) {
    // Solo permitir números
    input.value = input.value.replace(/\D/g, '');
}

// Validación del formulario
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    if (!selectedPayment) {
        e.preventDefault();
        alert('Por favor selecciona un método de pago');
        return;
    }
    
    // Si seleccionó pago online, verificar que haya elegido un método específico
    if (selectedPayment === 'online') {
        const selectedOnlineMethod = document.querySelector('input[name="onlinePaymentMethod"]:checked');
        if (!selectedOnlineMethod) {
            e.preventDefault();
            alert('Por favor selecciona un método de pago online específico');
            return;
        }
    }
    
    // Si seleccionó pago local, verificar que haya elegido un método específico
    if (selectedPayment === 'local') {
        const selectedLocalMethod = document.querySelector('input[name="localPaymentMethod"]:checked');
        if (!selectedLocalMethod) {
            e.preventDefault();
            alert('Por favor selecciona cómo vas a pagar en el local');
            return;
        }
    }
});

// Inicializar cuando carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing payment options...');
    initializePaymentOptions();
    console.log('Payment options initialized');
    
    // Agregar event listeners para los métodos de pago
    const paymentLocal = document.getElementById('payment-local');
    const paymentOnline = document.getElementById('payment-online');
    const paymentCod = document.getElementById('payment-cod');
    
    if (paymentLocal) {
        paymentLocal.addEventListener('click', function() {
            console.log('Local payment clicked');
            selectPayment('local');
        });
    }
    
    if (paymentOnline) {
        paymentOnline.addEventListener('click', function() {
            console.log('Online payment clicked');
            selectPayment('online');
        });
    }
    
    if (paymentCod) {
        paymentCod.addEventListener('click', function() {
            console.log('COD payment clicked');
            selectPayment('cod');
        });
    }
    
    console.log('Event listeners added');
});
</script>

<?php require_once 'includes/footer.php'; ?>
