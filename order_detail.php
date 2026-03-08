<?php
/**
 * =====================================================
 * MULTIGAMER360 - DETALLE DE PEDIDO
 * =====================================================
 * Version: 1.0.0
 * Fecha última modificación: 08 Mar 2026
 *
 * Descripción: Página de detalle de un pedido para el usuario logueado
 * Autor: MultiGamer360 Development Team
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: order_history.php');
    exit;
}

$order = null;
$order_items = [];

try {
    // Cargar pedido — verificar que pertenece al usuario logueado
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: order_history.php');
        exit;
    }

    // Cargar items con imagen principal
    $stmt = $pdo->prepare("
        SELECT oi.*,
               p.name as product_title,
               (SELECT filename FROM product_images pi WHERE pi.product_id = oi.product_id AND pi.is_primary = 1 LIMIT 1) as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error al cargar detalle de pedido: " . $e->getMessage());
    header('Location: order_history.php');
    exit;
}

// Parsear notes JSON
$notes = json_decode($order['notes'] ?? '{}', true) ?: [];
$subtotal        = $notes['subtotal']         ?? $order['total_amount'];
$shipping_cost   = $notes['shipping_cost']    ?? 0;
$shipping_name   = $notes['shipping_name']    ?? 'Envío';
$coupon_code     = $notes['coupon_code']      ?? '';
$coupon_discount = $notes['coupon_discount']  ?? 0;
$payment_name    = $notes['payment_name']     ?? ucfirst($order['payment_type'] ?? 'Pago');
$order_currency  = in_array($notes['currency'] ?? '', ['ARS', 'USD']) ? $notes['currency'] : 'ARS';

$payment_map = [
    'presential' => 'Pago en local',
    'online'     => 'Pago online',
    'cod'        => 'Pago contra entrega',
];
if (empty($payment_name) || $payment_name === ucfirst($order['payment_type'] ?? '')) {
    $payment_name = $payment_map[$order['payment_type'] ?? ''] ?? 'Pago';
}

// Tipo de cambio ARS -> USD
$exchange_rate = 1200;
try {
    $stmt_rate = $pdo->query("SELECT exchange_rate FROM currency_settings WHERE currency_code = 'USD' LIMIT 1");
    $rate_row  = $stmt_rate ? $stmt_rate->fetch(PDO::FETCH_ASSOC) : null;
    if ($rate_row && $rate_row['exchange_rate'] > 0) {
        $exchange_rate = (float)$rate_row['exchange_rate'];
    }
} catch (Exception $e) { /* usar default */ }

// Helpers de formato
function fmtARS($n) {
    return '$' . number_format($n, 0, ',', '.');
}
function fmtUSD($n, $exchange_rate) {
    return 'U$S ' . number_format($n / $exchange_rate, 0, ',', '.');
}
function fmtMoney($n, $currency, $exchange_rate) {
    return $currency === 'USD' ? fmtUSD($n, $exchange_rate) : fmtARS($n);
}
function fmtMoneyAlt($n, $currency, $exchange_rate) {
    return $currency === 'USD' ? fmtARS($n) . ' ARS' : fmtUSD($n, $exchange_rate) . ' USD';
}

function getStatusColorDetail($status) {
    switch ($status) {
        case 'pending':    return 'warning';
        case 'processing': return 'info';
        case 'shipped':    return 'primary';
        case 'delivered':  return 'success';
        case 'cancelled':  return 'danger';
        default:           return 'secondary';
    }
}

function getStatusTextDetail($status) {
    switch ($status) {
        case 'pending':    return 'Pendiente';
        case 'processing': return 'Procesando';
        case 'shipped':    return 'Enviado';
        case 'delivered':  return 'Entregado';
        case 'cancelled':  return 'Cancelado';
        default:           return 'Desconocido';
    }
}

include 'includes/header.php';
?>

<div class="container my-5">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb od-breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="order_history.php">Mis Pedidos</a></li>
            <li class="breadcrumb-item active">#<?php echo htmlspecialchars($order['order_number']); ?></li>
        </ol>
    </nav>

    <!-- Encabezado -->
    <div class="od-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="od-title">
                    <i class="fas fa-receipt me-3"></i>
                    Pedido #<?php echo htmlspecialchars($order['order_number']); ?>
                </h1>
                <p class="od-subtitle mb-0">
                    Realizado el <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                    a las <?php echo date('H:i', strtotime($order['created_at'])); ?> hs
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <span class="badge od-status-badge bg-<?php echo getStatusColorDetail($order['status']); ?>">
                    <?php echo getStatusTextDetail($order['status']); ?>
                </span>
                <a href="order_history.php" class="btn btn-outline-secondary ms-3 od-back-btn">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Columna izquierda: productos -->
        <div class="col-lg-8">
            <div class="od-card">
                <div class="od-card-header">
                    <i class="fas fa-box-open me-2"></i> Productos del pedido
                </div>
                <div class="od-card-body">
                    <?php if (empty($order_items)): ?>
                        <p class="text-muted text-center py-4">No se encontraron productos en este pedido.</p>
                    <?php else: ?>
                        <?php foreach ($order_items as $item): ?>
                        <div class="od-item">
                            <div class="od-item-image">
                                <?php if (!empty($item['product_image'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>"
                                         alt="<?php echo htmlspecialchars($item['product_name'] ?? $item['product_title'] ?? ''); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="od-item-img-placeholder"><i class="fas fa-gamepad"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="od-item-info">
                                <div class="od-item-name">
                                    <?php echo htmlspecialchars($item['product_name'] ?? $item['product_title'] ?? 'Producto'); ?>
                                </div>
                                <div class="od-item-meta">
                                    Cantidad: <strong><?php echo (int)$item['quantity']; ?></strong>
                                    &nbsp;·&nbsp;
                                    Precio unit.: <strong><?php echo fmtMoney($item['price'], $order_currency, $exchange_rate); ?></strong>
                                </div>
                            </div>
                            <div class="od-item-total">
                                <?php echo fmtMoney($item['price'] * $item['quantity'], $order_currency, $exchange_rate); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna derecha: resumen y datos -->
        <div class="col-lg-4">

            <!-- Resumen de totales -->
            <div class="od-card mb-4">
                <div class="od-card-header">
                    <i class="fas fa-calculator me-2"></i> Resumen
                </div>
                <div class="od-card-body od-totals">
                    <div class="od-total-row">
                        <span>Subtotal</span>
                        <span><?php echo fmtMoney($subtotal, $order_currency, $exchange_rate); ?></span>
                    </div>
                    <?php if ($shipping_cost > 0): ?>
                    <div class="od-total-row">
                        <span><?php echo htmlspecialchars($shipping_name); ?></span>
                        <span><?php echo fmtMoney($shipping_cost, $order_currency, $exchange_rate); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="od-total-row">
                        <span><?php echo htmlspecialchars($shipping_name); ?></span>
                        <span class="text-success">Gratis</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($coupon_discount > 0): ?>
                    <div class="od-total-row od-discount">
                        <span><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($coupon_code); ?></span>
                        <span>-<?php echo fmtMoney($coupon_discount, $order_currency, $exchange_rate); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="od-total-row od-grand-total">
                        <span>Total</span>
                        <span><?php echo fmtMoney($order['total_amount'], $order_currency, $exchange_rate); ?></span>
                    </div>
                    <div class="od-total-row od-equiv">
                        <span><?php echo $order_currency === 'USD' ? 'Equivalente ARS' : 'Equivalente USD'; ?></span>
                        <span><?php echo fmtMoneyAlt($order['total_amount'], $order_currency, $exchange_rate); ?></span>
                    </div>
                </div>
            </div>

            <!-- Info de pago y envío -->
            <div class="od-card">
                <div class="od-card-header">
                    <i class="fas fa-info-circle me-2"></i> Información
                </div>
                <div class="od-card-body">
                    <div class="od-info-row">
                        <span class="od-info-label"><i class="fas fa-credit-card me-2"></i>Método de pago</span>
                        <span class="od-info-value"><?php echo htmlspecialchars($payment_name); ?></span>
                    </div>
                    <?php if (!empty($order['shipping_address'])): ?>
                    <div class="od-info-row">
                        <span class="od-info-label"><i class="fas fa-map-marker-alt me-2"></i>Dirección de envío</span>
                        <span class="od-info-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="od-info-row">
                        <span class="od-info-label"><i class="fas fa-store me-2"></i>Entrega</span>
                        <span class="od-info-value">Retiro en tienda</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['customer_phone'])): ?>
                    <div class="od-info-row">
                        <span class="od-info-label"><i class="fas fa-phone me-2"></i>Contacto</span>
                        <span class="od-info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* ===================================================== */
/* ESTILOS DETALLE DE PEDIDO */
/* ===================================================== */

.od-breadcrumb {
    background: transparent;
    padding: 0;
}
.od-breadcrumb .breadcrumb-item a {
    color: #8B0000;
    text-decoration: none;
}
.od-breadcrumb .breadcrumb-item.active {
    color: #cccccc;
}
.od-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    color: #555;
}

.od-header {
    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
    border-radius: 15px;
    padding: 30px;
    border: 2px solid #8B0000;
    box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3);
}

.od-title {
    color: white !important;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.od-subtitle {
    color: #cccccc !important;
    font-size: 1rem;
}

.od-status-badge {
    font-size: 1rem;
    padding: 10px 22px;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.od-back-btn {
    border-color: #555;
    color: #ccc !important;
    border-radius: 8px;
}
.od-back-btn:hover {
    background: #333;
    border-color: #777;
    color: white !important;
}

/* Tarjetas */
.od-card {
    background: linear-gradient(145deg, #1a1a1a, #0d0d0d);
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.od-card-header {
    background: linear-gradient(45deg, #8B0000, #DC143C);
    color: white !important;
    font-weight: 600;
    padding: 16px 24px;
    font-size: 1rem;
    letter-spacing: 0.3px;
}

.od-card-body {
    padding: 0;
}

/* Items */
.od-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 24px;
    border-bottom: 1px solid #1f1f1f;
}
.od-item:last-child {
    border-bottom: none;
}

.od-item-image {
    width: 70px;
    height: 70px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    background: #111;
    border: 1px solid #333;
    display: flex;
    align-items: center;
    justify-content: center;
}
.od-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.od-item-img-placeholder {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #444;
    font-size: 1.5rem;
}

.od-item-info {
    flex: 1;
    min-width: 0;
}
.od-item-name {
    color: white !important;
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.od-item-meta {
    color: #aaaaaa !important;
    font-size: 0.87rem;
}
.od-item-meta strong {
    color: #cccccc !important;
}

.od-item-total {
    color: #DC143C !important;
    font-weight: 700;
    font-size: 1.1rem;
    white-space: nowrap;
}

/* Totales */
.od-totals {
    padding: 0 !important;
}
.od-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 24px;
    border-bottom: 1px solid #1f1f1f;
    color: #cccccc !important;
    font-size: 0.95rem;
}
.od-total-row:last-child {
    border-bottom: none;
}
.od-discount {
    color: #28a745 !important;
}
.od-grand-total {
    color: white !important;
    font-weight: 700;
    font-size: 1.15rem;
    background: rgba(139, 0, 0, 0.15);
}
.od-grand-total span:last-child {
    color: #DC143C !important;
    font-size: 1.3rem;
}

.od-equiv {
    border-top: 1px dashed #333 !important;
    background: rgba(255,255,255,0.03) !important;
}
.od-equiv span {
    color: #777777 !important;
    font-size: 0.85rem !important;
    font-weight: 400 !important;
}

/* Info */
.od-info-row {
    display: flex;
    flex-direction: column;
    padding: 14px 24px;
    border-bottom: 1px solid #1f1f1f;
}
.od-info-row:last-child {
    border-bottom: none;
}
.od-info-label {
    color: #888888 !important;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.od-info-value {
    color: white !important;
    font-size: 0.95rem;
    font-weight: 500;
}

.text-success {
    color: #28a745 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .od-title {
        font-size: 1.5rem;
    }
    .od-item {
        padding: 14px 16px;
    }
    .od-total-row, .od-info-row {
        padding: 12px 16px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
