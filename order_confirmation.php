<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión a base de datos
require_once 'config/database.php';

$order = null;

// Verificar si hay una orden completada en sesión
if (isset($_SESSION['completed_order'])) {
    $order = $_SESSION['completed_order'];
} elseif (isset($_GET['order_id'])) {
    // Si hay order_id en URL, cargar desde base de datos
    $order_id = $_GET['order_id'];

    try {
        // Obtener orden
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
        $stmt->execute([$order_id]);
        $order_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order_data) {
            // Obtener items de la orden con imagen principal desde product_images
            $stmt = $pdo->prepare("
                SELECT oi.*, pi.image_url 
                FROM order_items oi 
                LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_data['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Reconstruir estructura de orden
            $order = [
                'order_id' => $order_data['order_number'],
                'date' => $order_data['created_at'],
                'customer' => [
                    'first_name' => $order_data['customer_first_name'],
                    'last_name' => $order_data['customer_last_name'],
                    'email' => $order_data['customer_email'],
                    'phone' => $order_data['customer_phone'],
                    'address' => $order_data['shipping_address'],
                    'city' => $order_data['shipping_city'],
                    'province' => $order_data['shipping_province'],
                    'zip_code' => $order_data['shipping_postal_code']
                ],
                'shipping' => [
                    'name' => $order_data['shipping_method'],
                    'cost' => $order_data['shipping_cost']
                ],
                'payment' => [
                    'method' => strtolower(str_replace(' ', '', $order_data['payment_method'])),
                    'name' => $order_data['payment_method']
                ],
                'items' => array_map(function ($item) {
                    // Construir ruta completa de la imagen
                    $image_path = 'uploads/products/default.jpg'; // Default
                    if (!empty($item['image_url'])) {
                        // Si ya tiene la ruta completa, usarla
                        if (strpos($item['image_url'], 'uploads/products/') === 0) {
                            $image_path = $item['image_url'];
                        } else {
                            // Si solo es el nombre del archivo, agregar la ruta
                            $image_path = 'uploads/products/' . $item['image_url'];
                        }
                    }
                    
                    return [
                        'id' => $item['product_id'],
                        'name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['subtotal'],
                        'image' => $image_path
                    ];
                }, $items),
                'totals' => [
                    'subtotal' => $order_data['subtotal'],
                    'coupon_discount' => $order_data['discount_amount'],
                    'shipping' => $order_data['shipping_cost'],
                    'total' => $order_data['total_amount']
                ],
                'coupon' => $order_data['discount_amount'] > 0 ? [
                    'code' => 'DESCUENTO',
                    'discount_amount' => $order_data['discount_amount']
                ] : null
            ];
        }
    } catch (PDOException $e) {
        error_log("Error al cargar orden: " . $e->getMessage());
    }
}

// Si no hay orden disponible, redirigir
if (!$order) {
    header('Location: index.php');
    exit();
}

require_once 'includes/header.php';
?>

<style>
    /* Order Confirmation Styles - Version 3.4 */
    /* Updated: 2025-10-28 - Fixed equal height cards and content overflow */

    .confirmation-page {
        background-color: var(--bg-dark);
        min-height: 100vh;
        color: var(--text-light);
        padding: 2rem 0;
    }

    .confirmation-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .success-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .success-icon {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 1rem;
    }

    .success-title {
        color: var(--accent-red);
        font-size: 2.5rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .success-subtitle {
        font-size: 1.2rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }

    .order-id {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--accent-red);
    }

    .confirmation-card {
        background: var(--card-bg);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 2rem;
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .confirmation-card:hover {
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 8px 32px rgba(220, 53, 69, 0.15);
    }
    
    /* Asegurar que las cards tengan la misma altura */
    .row > [class*='col-'] {
        display: flex;
    }

    .section-title {
        color: var(--accent-red);
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 1.25rem;
        margin-top: 0;
        border-bottom: 2px solid var(--accent-red);
        padding-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-title i {
        font-size: 1.2rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 1rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .info-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .info-label {
        font-weight: 600;
        color: var(--text-light);
        min-width: 120px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-value {
        color: var(--text-muted);
        text-align: right;
        flex: 1;
        margin-left: 1rem;
        line-height: 1.6;
        word-wrap: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }

    .products-list {
        margin-bottom: 0;
        padding: 0;
    }

    .product-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
    }

    .product-item:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
    }

    .product-item:last-child {
        margin-bottom: 0;
    }

    .product-info {
        flex: 1;
        padding: 1.5rem;
        margin-bottom: 15px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 8px;
        position: relative;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .product-info::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(
            to right,
            rgba(0, 0, 0, 0.85) 0%,
            rgba(0, 0, 0, 0.75) 50%,
            rgba(0, 0, 0, 0.4) 100%
        );
        border-radius: 8px;
        z-index: 0;
    }

    .product-name {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: #fff;
        line-height: 1.4;
        position: relative;
        z-index: 1;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
    }

    .product-details {
        color: #fff;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }

    .product-quantity {
        display: inline-flex;
        align-items: center;
        background: rgba(220, 53, 69, 0.9);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .product-unit-price {
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    }

    .product-total {
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--accent-red);
        white-space: nowrap;
        text-align: right;
        min-width: 120px;
    }

    .total-section {
        background: rgba(220, 53, 69, 0.08);
        border: 1px solid var(--accent-red);
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        font-size: 1.05rem;
    }

    .total-row.discount-row {
        color: #28a745;
        font-weight: 600;
    }

    .total-row.discount-row span:last-child {
        color: #28a745;
    }

    .total-row.final {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--accent-red);
        border-top: 2px solid var(--accent-red);
        padding-top: 1rem;
        margin-top: 1rem;
        margin-bottom: 0;
    }

    .free-shipping {
        color: #28a745;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        background: rgba(40, 167, 69, 0.15);
        border-radius: 20px;
    }

    .products-list {
        margin-bottom: 0;
    }

    .action-buttons {
        text-align: center;
        margin-top: 3rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--accent-red), #b02a37);
        border: none;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin: 0 1rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #b02a37, var(--accent-red));
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        color: white;
        text-decoration: none;
    }

    .btn-secondary {
        background: transparent;
        border: 2px solid var(--accent-red);
        color: var(--accent-red);
        padding: 1rem 2rem;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin: 0 1rem;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: var(--accent-red);
        color: white;
        transform: translateY(-2px);
        text-decoration: none;
    }

    .payment-instructions {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid #ffc107;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
    }

    .payment-instructions h5 {
        color: #ffc107;
        margin-bottom: 1rem;
    }

    .payment-instructions ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .payment-instructions li {
        margin-bottom: 0.5rem;
    }

    @media (max-width: 768px) {
        .confirmation-page {
            padding: 1rem 0;
        }
        
        /* Resetear flexbox en mobile */
        .row > [class*='col-'] {
            display: block;
        }

        .confirmation-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            height: auto;
        }

        .success-title {
            font-size: 1.8rem;
        }

        .success-icon {
            font-size: 3rem;
        }

        .section-title {
            font-size: 1.2rem;
        }

        .product-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
        }

        .product-info {
            padding: 1.25rem;
            width: 100%;
            margin-bottom: 10px;
        }

        .product-info::before {
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.85) 0%,
                rgba(0, 0, 0, 0.75) 70%,
                rgba(0, 0, 0, 0.6) 100%
            );
        }

        .product-total {
            width: 100%;
            text-align: left;
            font-size: 1.3rem;
            padding-left: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 0.75rem;
        }

        .product-details {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-label {
            min-width: auto;
        }

        .info-value {
            text-align: left;
            margin-left: 0;
        }

        .total-row {
            font-size: 1rem;
        }

        .total-row.final {
            font-size: 1.3rem;
        }

        .action-buttons {
            margin-top: 2rem;
        }

        .btn-primary,
        .btn-secondary {
            display: block;
            margin: 1rem auto;
            text-align: center;
            width: 100%;
            max-width: 300px;
        }

        .payment-instructions {
            padding: 1.25rem;
        }

        .payment-instructions h5 {
            font-size: 1.1rem;
        }
    }

    /* End of Order Confirmation Styles v3.1 */
</style>

<main class="confirmation-page">
    <div class="container confirmation-container">
        <!-- Header de Éxito -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">¡Pedido Confirmado!</h1>
            <p class="success-subtitle">Tu pedido ha sido procesado exitosamente</p>
            <p class="order-id">ID de Orden: <?php echo htmlspecialchars($order['order_id']); ?></p>
        </div>

        <div class="row">
            <!-- Información del Cliente -->
            <div class="col-md-6">
                <div class="confirmation-card">
                    <h3 class="section-title"><i class="fas fa-user"></i> Información del Cliente</h3>

                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span
                            class="info-value"><?php echo htmlspecialchars($order['customer']['first_name'] . ' ' . $order['customer']['last_name']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['customer']['email']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['customer']['phone']); ?></span>
                    </div>

                    <?php if (!empty($order['customer']['address'])): ?>
                        <div class="info-row">
                            <span class="info-label">Dirección:</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($order['customer']['address']); ?><br>
                                <?php echo htmlspecialchars($order['customer']['city'] . ', ' . $order['customer']['province']); ?><br>
                                CP: <?php echo htmlspecialchars($order['customer']['zip_code']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información de Envío y Pago -->
            <div class="col-md-6">
                <div class="confirmation-card">
                    <h3 class="section-title"><i class="fas fa-shipping-fast"></i> Envío y Pago</h3>

                    <div class="info-row">
                        <span class="info-label">Método de Envío:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['shipping']['name']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Costo de Envío:</span>
                        <span class="info-value">
                            <?php if ($order['shipping']['cost'] == 0): ?>
                                Gratis
                            <?php else: ?>
                                $<?php echo number_format($order['shipping']['cost'], 0, ',', '.'); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Método de Pago:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['payment']['name']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Fecha de Pedido:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos Ordenados -->
        <div class="confirmation-card">
            <h3 class="section-title"><i class="fas fa-shopping-bag"></i> Productos Ordenados</h3>

            <div class="products-list">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="product-info" style="background-image: url('<?php echo htmlspecialchars($item['image']); ?>');">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-details">
                            <span class="product-quantity">
                                <i class="fas fa-times"></i> <?php echo $item['quantity']; ?>
                            </span>
                            <span class="product-unit-price">
                                Precio unitario: $<?php echo number_format($item['price'], 0, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($order['totals']['subtotal'], 0, ',', '.'); ?></span>
                </div>

                <?php if (isset($order['coupon']) && $order['coupon']): ?>
                    <div class="total-row discount-row">
                        <span>
                            <i class="fas fa-tag"></i> Descuento
                            (<?php echo htmlspecialchars($order['coupon']['code']); ?>):
                        </span>
                        <span>-$<?php echo number_format($order['totals']['coupon_discount'], 0, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>

                <div class="total-row">
                    <span>Envío (<?php echo htmlspecialchars($order['shipping']['name']); ?>):</span>
                    <span>
                        <?php if ($order['totals']['shipping'] == 0): ?>
                            <span class="free-shipping">Gratis</span>
                        <?php else: ?>
                            $<?php echo number_format($order['totals']['shipping'], 0, ',', '.'); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="total-row final">
                    <span>Total:</span>
                    <span>$<?php echo number_format($order['totals']['total'], 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Instrucciones de Pago -->
        <?php if ($order['payment']['method'] === 'local'): ?>
            <div class="payment-instructions">
                <h5><i class="fas fa-info-circle"></i> Instrucciones para Pago en Local</h5>
                <ul>
                    <li>Podrás pagar en efectivo o con tarjeta al momento del retiro</li>
                    <li>Te contactaremos para coordinar la fecha y hora de retiro</li>
                    <li>Dirección: [Aquí va la dirección de MultiGamer360]</li>
                    <li>Horarios de atención: Lunes a Viernes 9:00 - 18:00, Sábados 9:00 - 13:00</li>
                </ul>
            </div>
        <?php elseif ($order['payment']['method'] === 'cod'): ?>
            <div class="payment-instructions">
                <h5><i class="fas fa-info-circle"></i> Instrucciones para Contra Entrega</h5>
                <ul>
                    <li>Podrás pagar en efectivo al momento de recibir el pedido</li>
                    <li>Te contactaremos para coordinar la entrega</li>
                    <li>Ten el monto exacto preparado para facilitar la entrega</li>
                    <li>El repartidor llevará cambio limitado</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="payment-instructions">
                <h5><i class="fas fa-info-circle"></i> Instrucciones para Pago Online</h5>
                <ul>
                    <li>Te enviaremos un enlace de pago a tu email</li>
                    <li>Puedes pagar con tarjeta de crédito/débito o transferencia bancaria</li>
                    <li>Una vez confirmado el pago, procesaremos tu pedido</li>
                    <li>Recibirás confirmación de pago por email</li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="index.php" class="btn-primary">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
            <a href="productos.php" class="btn-secondary">
                <i class="fas fa-shopping-cart"></i> Seguir Comprando
            </a>
        </div>
    </div>
</main>

<?php
// Limpiar la orden de la sesión solo si se accedió desde el proceso de compra (no desde URL)
// Esto permite que se pueda recargar la página sin perder el acceso
if (isset($_SESSION['completed_order']) && !isset($_GET['order_id'])) {
    unset($_SESSION['completed_order']);
}
require_once 'includes/footer.php';
?>