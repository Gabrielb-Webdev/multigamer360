<?php
/**
 * =====================================================
 * MULTIGAMER360 - HISTORIAL DE PEDIDOS
 * =====================================================
 * Version: 1.0.2
 * Fecha última modificación: 08 Mar 2026
 *
 * Descripción: Historial de pedidos del usuario logueado
 * Autor: MultiGamer360 Development Team
 *
 * Changelog v1.0.2 (08 Mar 2026):
 * - Nueva columna de miniaturas de productos: 1 imagen full, 2 = mitad/mitad, 3 = featured+2, 4 = 2x2 grid
 * - GROUP_CONCAT para obtener hasta 4 imágenes por pedido vía product_images
 *
 * Changelog v1.0.1 (08 Mar 2026):
 * - Fix CRÍTICO: SELECT usaba columnas inexistentes (payment_status, shipped_at, delivered_at, tracking_number)
 * - Query simplificada a columnas reales: id, order_number, status, payment_type, total_amount, notes, created_at
 * - Eliminadas referencias a payment_status y tracking_number en el HTML de la tabla
 */
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir sistema de autenticación
require_once 'includes/auth.php';
require_once 'config/database.php';

// Verificar que el usuario esté logueado
requireLogin();

$user = getCurrentUser();
$user_id = $_SESSION['user_id'];

// Obtener pedidos del usuario
$orders = [];
$total_orders = 0;
$total_spent = 0;

try {
    // Consulta para obtener todos los pedidos del usuario
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.payment_type,
            o.total_amount as total,
            o.notes,
            o.created_at,
            COUNT(DISTINCT oi.id) as item_count,
            GROUP_CONCAT(DISTINCT pi.image_url ORDER BY oi.id ASC SEPARATOR '|') as product_imgs
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_primary = 1
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $total_orders = count($orders);
    $total_spent = array_sum(array_column($orders, 'total'));
    
} catch (Exception $e) {
    error_log("Error al obtener pedidos: " . $e->getMessage());
    $orders = [];
}

// Obtener tipo de cambio ARS -> USD
$exchange_rate = 1200;
try {
    $stmt_rate = $pdo->query("SELECT exchange_rate FROM currency_settings WHERE currency_code = 'USD' LIMIT 1");
    $rate_row = $stmt_rate ? $stmt_rate->fetch(PDO::FETCH_ASSOC) : null;
    if ($rate_row && $rate_row['exchange_rate'] > 0) {
        $exchange_rate = (float)$rate_row['exchange_rate'];
    }
} catch (Exception $e) { /* tabla puede no existir, usar default */ }

// Función para obtener el color del estado
function getStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

// Función para obtener el texto del estado
function getStatusText($status) {
    switch($status) {
        case 'pending': return 'Pendiente';
        case 'processing': return 'Procesando';
        case 'shipped': return 'Enviado';
        case 'delivered': return 'Entregado';
        case 'cancelled': return 'Cancelado';
        default: return 'Desconocido';
    }
}

// Función para renderizar miniaturas de productos del pedido (máx 4)
function renderOrderThumbnails($product_imgs) {
    if (empty($product_imgs)) {
        return '<div class="order-thumb-placeholder"><i class="fas fa-gamepad"></i></div>';
    }
    $imgs = array_values(array_filter(array_slice(explode('|', $product_imgs), 0, 4)));
    $count = count($imgs);
    if ($count === 0) {
        return '<div class="order-thumb-placeholder"><i class="fas fa-gamepad"></i></div>';
    }
    $html = '<div class="order-thumbnails count-' . $count . '">';
    foreach ($imgs as $img) {
        $safe = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
        $html .= '<div class="thumb-item"><img src="uploads/products/' . $safe . '" alt="Producto" loading="lazy"></div>';
    }
    $html .= '</div>';
    return $html;
}

include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Encabezado mejorado -->
    <div class="order-history-header mb-5">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="order-title">
                    <i class="fas fa-history text-primary pulse-icon"></i>
                    Historial de Pedidos
                </h1>
                <p class="order-subtitle">
                    Revisa todos tus pedidos y su estado actual
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="order-stats flex-wrap">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_orders; ?></span>
                        <span class="stat-label">Pedidos</span>
                    </div>
                    <?php if ($total_orders > 0): ?>
                    <div class="stat-item ms-3">
                        <span class="stat-number stat-number-sm">$<?php echo number_format($total_spent, 0, ',', '.'); ?></span>
                        <span class="stat-label">Total ARS</span>
                    </div>
                    <div class="stat-item ms-3">
                        <span class="stat-number stat-number-sm">U$S <?php echo number_format($total_spent / $exchange_rate, 0, ',', '.'); ?></span>
                        <span class="stat-label">Total USD</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Área de pedidos -->
    <div class="orders-section">
        <!-- Encabezado de la tabla -->
        <div class="orders-table-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="table-title">Mis Pedidos</h4>
                </div>
                <div class="col-md-6 text-end">
                    <div class="table-filters">
                        <select class="form-select" id="statusFilter" onchange="filterOrders()">
                            <option value="">Todos los estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="processing">Procesando</option>
                            <option value="shipped">Enviado</option>
                            <option value="delivered">Entregado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de pedidos -->
        <div class="orders-table-container">
            <div class="table-responsive">
                <table class="table orders-table">
                    <thead>
                        <tr>
                            <th class="col-img">IMG</th>
                            <th class="col-pedido">Pedido #</th>
                            <th class="col-fecha">Fecha</th>
                            <th class="col-estado">Estado</th>
                            <th class="col-total">Total</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php if (empty($orders)): ?>
                        <!-- Estado vacío -->
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <h4 class="empty-title">Aún no tienes pedidos</h4>
                                    <p class="empty-description">
                                        Una vez que realices tu primera compra, verás todos tus pedidos aquí.
                                    </p>
                                    <div class="empty-actions">
                                        <a href="productos.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-shopping-bag me-2"></i>
                                            Explorar Productos
                                        </a>
                                        <a href="carrito.php" class="btn btn-outline-primary btn-lg ms-3">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Ver Carrito
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <!-- Lista de pedidos -->
                        <?php foreach ($orders as $order): ?>
                        <tr class="order-row" data-status="<?php echo $order['status']; ?>">
                            <td class="col-img">
                                <?php echo renderOrderThumbnails($order['product_imgs'] ?? ''); ?>
                            </td>
                            <td class="col-pedido">
                                <div class="order-number">
                                    <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </div>
                            </td>
                            <td class="col-fecha">
                                <div class="order-date">
                                    <strong><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></strong>
                                    <br><small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                </div>
                            </td>
                            <td class="col-estado">
                                <span class="badge bg-<?php echo getStatusColor($order['status']); ?> status-badge">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                            </td>
                            <td class="col-total">
                                <?php
                                    $order_notes_parsed = json_decode($order['notes'] ?? '{}', true) ?: [];
                                    $order_currency = $order_notes_parsed['currency'] ?? 'ARS';
                                    if ($order_currency === 'USD') {
                                        $display_total = 'U$S ' . number_format($order['total'] / $exchange_rate, 0, ',', '.');
                                        $alt_total = '$' . number_format($order['total'], 0, ',', '.') . ' ARS';
                                    } else {
                                        $display_total = '$' . number_format($order['total'], 0, ',', '.');
                                        $alt_total = 'U$S ' . number_format($order['total'] / $exchange_rate, 0, ',', '.') . ' USD';
                                    }
                                ?>
                                <strong class="order-total"><?php echo $display_total; ?></strong>
                                <br><small class="order-total-alt"><?php echo $alt_total; ?></small>
                            </td>
                            <td class="col-acciones">
                                <div class="order-actions">
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order['status'] === 'delivered'): ?>
                                    <button class="btn btn-sm btn-outline-success ms-1" 
                                            onclick="reorderItems(<?php echo $order['id']; ?>)"
                                            title="Volver a pedir">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                                    <button class="btn btn-sm btn-outline-danger ms-1" 
                                            onclick="cancelOrder(<?php echo $order['id']; ?>)"
                                            title="Cancelar pedido">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* ===================================================== */
/* ESTILOS PARA HISTORIAL DE PEDIDOS */
/* ===================================================== */

/* Encabezado general */
.order-history-header {
    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
    border-radius: 15px;
    padding: 30px;
    border: 2px solid #8B0000;
    box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3), 0 0 20px rgba(139, 0, 0, 0.1);
    animation: slideInDown 0.8s ease forwards;
}

.order-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.pulse-icon {
    animation: pulse 2s infinite;
    color: #8B0000;
}

@keyframes pulse {
    0%   { transform: scale(1);    color: #8B0000; }
    50%  { transform: scale(1.05); color: #DC143C; }
    100% { transform: scale(1);    color: #8B0000; }
}

.order-subtitle {
    color: #cccccc;
    font-size: 1.1rem;
    margin: 0;
}

.order-stats {
    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 4px 0;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: #8B0000;
    line-height: 1;
}

.stat-number-sm {
    font-size: 1.3rem !important;
}

.stat-label {
    display: block;
    color: #cccccc;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Contenedor principal de pedidos */
.orders-section {
    background: linear-gradient(145deg, #1a1a1a, #000000);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(139, 0, 0, 0.2), 0 4px 15px rgba(0, 0, 0, 0.3);
    border: 2px solid #8B0000;
    animation: slideInUp 0.8s ease 0.3s forwards;
    opacity: 0;
}

.orders-table-header {
    background: linear-gradient(45deg, #8B0000, #DC143C);
    padding: 25px 30px;
    border-bottom: 2px solid #8B0000;
}

.table-title {
    color: white;
    font-weight: 600;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.table-filters .form-select {
    background: #1a1a1a;
    border: 2px solid #8B0000;
    color: white;
    border-radius: 8px;
}

.table-filters .form-select:focus {
    border-color: #DC143C;
    box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
    background: #000;
}

.table-filters .form-select option {
    background: #1a1a1a;
    color: white;
}

/* ── TABLA ── */
.orders-table-container {
    padding: 0;
}

.orders-table {
    margin: 0;
    background: transparent;
    width: 100%;
    table-layout: auto;
    border-collapse: collapse;
}

/* Anchos de columna */
.orders-table th.col-img,
.orders-table td.col-img     { width: 90px;  min-width: 90px;  max-width: 90px; }
.orders-table th.col-pedido,
.orders-table td.col-pedido  { width: auto; }
.orders-table th.col-fecha,
.orders-table td.col-fecha   { width: 130px; min-width: 110px; }
.orders-table th.col-estado,
.orders-table td.col-estado  { width: 150px; min-width: 130px; }
.orders-table th.col-total,
.orders-table td.col-total   { width: 160px; min-width: 140px; }
.orders-table th.col-acciones,
.orders-table td.col-acciones{ width: 130px; min-width: 110px; }

/* Celda de cabecera */
.orders-table thead th {
    background: #000000;
    color: white;
    border: none;
    font-weight: 600;
    padding: 16px 15px;
    border-bottom: 3px solid #8B0000;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

/* Celda de dato — mismo padding horizontal que el th */
.orders-table tbody td {
    border: none;
    padding: 16px 15px;
    border-bottom: 1px solid #2a2a2a;
    vertical-align: middle;
    color: white !important;
    background: #0d0d0d;
}

/* Fila hover */
.order-row {
    transition: background 0.3s ease;
    position: relative;
    background: #0d0d0d !important;
}

.order-row:hover td {
    background: #1a0505 !important;
}

/* Textos internos */
.order-number strong {
    color: #DC143C !important;
    font-size: 1.05rem;
    font-weight: 700;
}

.order-date strong {
    color: #ffffff !important;
    font-size: 0.95rem;
}

.order-date small {
    color: #aaaaaa !important;
}

/* Badges de estado */
.status-badge {
    font-size: 0.8rem;
    padding: 7px 14px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.status-badge.bg-warning {
    background: linear-gradient(45deg, #FF8C00, #FFA500) !important;
    color: #000 !important;
}

.status-badge.bg-info {
    background: linear-gradient(45deg, #8B0000, #DC143C) !important;
    color: white !important;
}

.status-badge.bg-primary {
    background: linear-gradient(45deg, #0066CC, #007BFF) !important;
}

.status-badge.bg-success {
    background: linear-gradient(45deg, #006400, #28a745) !important;
}

.status-badge.bg-danger {
    background: linear-gradient(45deg, #8B0000, #DC143C) !important;
}

/* Total */
.order-total {
    color: #DC143C;
    font-size: 1.05rem;
    font-weight: 700;
    display: block;
}

.order-total-alt {
    color: #888888;
    font-size: 0.8rem;
    font-weight: 400;
}

/* Acciones */
.order-actions {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: nowrap;
}

.order-actions .btn {
    border-radius: 8px;
    transition: all 0.3s ease;
    padding: 7px 11px;
    border-width: 2px;
}

.order-actions .btn-outline-primary {
    border-color: #8B0000;
    color: #8B0000;
    background: transparent;
}

.order-actions .btn-outline-primary:hover {
    background: #8B0000;
    border-color: #8B0000;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
}

.order-actions .btn-outline-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.order-actions .btn-outline-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Miniaturas */
.order-thumbnails {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    display: grid;
    gap: 2px;
    background: #1a1a1a;
    border: 1px solid #333;
}

.order-thumbnails.count-1 { grid-template-columns: 1fr;      grid-template-rows: 1fr; }
.order-thumbnails.count-2 { grid-template-columns: 1fr 1fr;  grid-template-rows: 1fr; }
.order-thumbnails.count-3 { grid-template-columns: 1fr 1fr;  grid-template-rows: 1fr 1fr; }
.order-thumbnails.count-3 .thumb-item:first-child { grid-column: 1 / -1; }
.order-thumbnails.count-4 { grid-template-columns: 1fr 1fr;  grid-template-rows: 1fr 1fr; }

.thumb-item {
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #111;
}

.thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.order-thumb-placeholder {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    background: #1a1a1a;
    border: 1px solid #333;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #444;
    font-size: 1.4rem;
}

/* Estado vacío */
.empty-state {
    text-align: center;
    padding: 80px 30px;
    color: white;
}

.empty-icon {
    font-size: 5rem;
    color: #8B0000;
    margin-bottom: 30px;
    opacity: 0.7;
}

.empty-title {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.empty-description {
    color: #cccccc;
    font-size: 1.1rem;
    margin-bottom: 40px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.empty-actions .btn {
    padding: 15px 35px;
    font-weight: 700;
    border-radius: 10px;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.empty-actions .btn-primary {
    background: linear-gradient(45deg, #8B0000, #DC143C);
    border: none;
    box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
}

.empty-actions .btn-primary:hover {
    background: linear-gradient(45deg, #DC143C, #8B0000);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(139, 0, 0, 0.4);
}

.empty-actions .btn-outline-primary {
    border: 2px solid #8B0000;
    color: #8B0000;
    background: transparent;
}

.empty-actions .btn-outline-primary:hover {
    background: #8B0000;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(139, 0, 0, 0.3);
}

/* Animaciones */
@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-40px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes slideInUp {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .order-title { font-size: 1.8rem; }

    .order-stats { justify-content: center; margin-top: 20px; }

    .orders-table-header .row { text-align: center; }

    .table-filters { margin-top: 15px; }

    .orders-table thead th,
    .orders-table tbody td { padding: 12px 8px; font-size: 0.85rem; }

    .orders-table th.col-img,
    .orders-table td.col-img { width: 60px; min-width: 60px; max-width: 60px; }

    .order-thumbnails,
    .order-thumb-placeholder { width: 50px; height: 50px; }

    .order-actions { flex-direction: column; gap: 4px; }

    .order-actions .btn { width: 100%; margin: 0; }

    .empty-actions .btn { display: block; margin: 10px 0; width: 100%; }
    .empty-actions .btn.ms-3 { margin-left: 0 !important; }
}
</style>

<script>
// Función para filtrar pedidos por estado
function filterOrders() {
    const filter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.order-row');
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (filter === '' || status === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Función para ver detalles del pedido
function viewOrderDetails(orderId) {
    window.location.href = 'order_detail.php?id=' + orderId;
}

// Función para volver a pedir
function reorderItems(orderId) {
    if (confirm('¿Quieres agregar todos los productos de este pedido al carrito?')) {
        showNotification('Agregando productos al carrito...', 'info');
        
        // Simular llamada AJAX para volver a pedir
        fetch('ajax/reorder.php', {
            method: 'POST',
            body: new URLSearchParams({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Productos agregados al carrito exitosamente', 'success');
                // Actualizar contador del carrito si existe
                if (typeof syncCartInstantly === 'function') {
                    syncCartInstantly();
                }
            } else {
                showNotification(data.message || 'Error al agregar productos', 'error');
            }
        })
        .catch(error => {
            showNotification('Error de conexión', 'error');
        });
    }
}

// Función para cancelar pedido
function cancelOrder(orderId) {
    if (confirm('¿Estás seguro de que quieres cancelar este pedido?')) {
        showNotification('Cancelando pedido...', 'info');
        
        // Simular llamada AJAX para cancelar
        fetch('ajax/cancel-order.php', {
            method: 'POST',
            body: new URLSearchParams({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Pedido cancelado exitosamente', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Error al cancelar pedido', 'error');
            }
        })
        .catch(error => {
            showNotification('Error de conexión', 'error');
        });
    }
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `order-notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                      type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        ${message}
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'linear-gradient(45deg, #006400, #28a745)' : 
                     type === 'error' ? 'linear-gradient(45deg, #8B0000, #DC143C)' : 
                     'linear-gradient(45deg, #8B0000, #DC143C)'};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        border: 2px solid ${type === 'success' ? '#28a745' : '#8B0000'};
        box-shadow: 0 8px 25px rgba(${type === 'success' ? '40, 167, 69' : '139, 0, 0'}, 0.4);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Estilos para las animaciones de notificación
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(notificationStyles);
</script>

<?php include 'includes/footer.php'; ?>