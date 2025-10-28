<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay una orden completada
if (!isset($_SESSION['completed_order'])) {
    header('Location: index.php');
    exit();
}

$order = $_SESSION['completed_order'];
require_once 'includes/header.php';
?>

<style>
:root {
    --bg-dark: #1a1a1a;
    --card-bg: #2d2d2d;
    --text-light: #ffffff;
    --text-muted: #b0b0b0;
    --accent-red: #dc3545;
}

.confirmation-page {
    background-color: #1a1a1a;
    min-height: 100vh;
    color: #ffffff;
    padding: 2rem 0;
}

.confirmation-container {
    max-width: 1200px;
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
    color: #dc3545;
    font-size: 2.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.success-subtitle {
    font-size: 1.2rem;
    color: #b0b0b0;
    margin-bottom: 0.5rem;
}

.order-id {
    font-size: 1.1rem;
    font-weight: 600;
    color: #dc3545;
}

.confirmation-card {
    background: #2d2d2d;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 2rem;
    height: 100%;
}

.section-title {
    color: #dc3545;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #dc3545;
    padding-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #ffffff;
    min-width: 120px;
}

.info-value {
    color: #b0b0b0;
    text-align: right;
    flex: 1;
    margin-left: 1rem;
}

.product-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.product-item:last-child {
    border-bottom: none;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #ffffff;
}

.product-details {
    color: #b0b0b0;
    font-size: 0.9rem;
}

.product-total {
    font-weight: 700;
    color: #dc3545;
    font-size: 1.1rem;
    margin-left: 1rem;
}

.total-section {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid #dc3545;
    border-radius: 10px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.total-row.final {
    font-size: 1.3rem;
    font-weight: 700;
    color: #dc3545;
    border-top: 2px solid #dc3545;
    padding-top: 1rem;
    margin-top: 1rem;
    margin-bottom: 0;
}

.action-buttons {
    text-align: center;
    margin-top: 3rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #dc3545, #b02a37);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #b02a37, #dc3545);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: transparent;
    border: 2px solid #dc3545;
    color: #dc3545;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #dc3545;
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
    
    .confirmation-card {
        padding: 1.5rem;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .action-buttons {
        margin-top: 2rem;
    }
    
    .btn-primary,
    .btn-secondary {
        display: block;
        margin: 1rem auto;
        text-align: center;
    }
}
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
                        <span class="info-value"><?php echo htmlspecialchars($order['customer']['first_name'] . ' ' . $order['customer']['last_name']); ?></span>
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
            
            <?php foreach ($order['items'] as $item): ?>
            <div class="product-item">
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="product-details">
                        Cantidad: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 0, ',', '.'); ?>
                    </div>
                </div>
                <div class="product-total">
                    $<?php echo number_format($item['total'], 0, ',', '.'); ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($order['totals']['subtotal'], 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span>Envío:</span>
                    <span>
                        <?php if ($order['totals']['shipping'] == 0): ?>
                            Gratis
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
// Limpiar la orden de la sesión después de mostrarla
unset($_SESSION['completed_order']);
require_once 'includes/footer.php'; 
?>