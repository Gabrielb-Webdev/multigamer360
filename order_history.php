<?php
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
            o.payment_status,
            o.total_amount as total,
            o.created_at,
            o.shipped_at,
            o.delivered_at,
            o.tracking_number,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
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
                <div class="order-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_orders; ?></span>
                        <span class="stat-label">Pedidos Totales</span>
                    </div>
                    <?php if ($total_orders > 0): ?>
                    <div class="stat-item ms-4">
                        <span class="stat-number">$<?php echo number_format($total_spent, 2); ?></span>
                        <span class="stat-label">Total Gastado</span>
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
                            <th>Pedido #</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Acciones</th>
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
                            <td>
                                <div class="order-number">
                                    <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-truck"></i> <?php echo htmlspecialchars($order['tracking_number']); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="order-date">
                                    <strong><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></strong>
                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($order['status']); ?> status-badge">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                                <?php if ($order['payment_status'] !== 'paid'): ?>
                                <br><small class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Pago pendiente
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="item-count">
                                    <?php echo $order['item_count']; ?> 
                                    <?php echo $order['item_count'] == 1 ? 'producto' : 'productos'; ?>
                                </span>
                            </td>
                            <td>
                                <strong class="order-total">$<?php echo number_format($order['total'], 2); ?></strong>
                            </td>
                            <td>
                                <div class="order-actions">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewOrderDetails(<?php echo $order['id']; ?>)"
                                            title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
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

/* Encabezado */
.order-history-header {
    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
    border-radius: 15px;
    padding: 30px;
    border: 2px solid #8B0000;
    box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3), 
                0 0 20px rgba(139, 0, 0, 0.1);
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
    0% { 
        transform: scale(1); 
        color: #8B0000;
    }
    50% { 
        transform: scale(1.05); 
        color: #DC143C;
    }
    100% { 
        transform: scale(1);
        color: #8B0000;
    }
}

.order-subtitle {
    color: #cccccc;
    font-size: 1.1rem;
    margin: 0;
}

.order-stats {
    display: flex;
    justify-content: flex-end;
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

.stat-label {
    display: block;
    color: #cccccc;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Sección de pedidos */
.orders-section {
    background: linear-gradient(145deg, #1a1a1a, #000000);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(139, 0, 0, 0.2),
                0 4px 15px rgba(0, 0, 0, 0.3);
    border: 2px solid #8B0000;
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

/* Tabla */
.orders-table-container {
    padding: 0;
}

.orders-table {
    margin: 0;
    background: transparent;
}

.orders-table thead th {
    background: #000000;
    color: white;
    border: none;
    font-weight: 600;
    padding: 20px 30px;
    border-bottom: 3px solid #8B0000;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.orders-table tbody td {
    border: none;
    padding: 20px 30px;
    border-bottom: 1px solid #333;
    vertical-align: middle;
    color: white;
}

.orders-table .order-row:hover {
    background: rgba(139, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.order-number strong {
    color: #8B0000;
    font-size: 1.1rem;
    font-weight: 700;
}

.order-date strong {
    color: white;
}

.status-badge {
    font-size: 0.85rem;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

.item-count {
    color: #cccccc;
    font-size: 0.9rem;
}

.order-total {
    color: #8B0000;
    font-size: 1.2rem;
    font-weight: 700;
}

.order-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.order-actions .btn {
    border-radius: 8px;
    transition: all 0.3s ease;
    padding: 8px 12px;
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
    border-color: #8B0000;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(139, 0, 0, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .order-title {
        font-size: 2rem;
    }
    
    .order-stats {
        justify-content: center;
        margin-top: 20px;
    }
    
    .orders-table-header .row {
        text-align: center;
    }
    
    .table-filters {
        margin-top: 15px;
    }
    
    .empty-actions .btn {
        display: block;
        margin: 10px 0;
        width: 100%;
    }
    
    .empty-actions .btn.ms-3 {
        margin-left: 0 !important;
    }
    
    .orders-table thead th,
    .orders-table tbody td {
        padding: 15px 10px;
        font-size: 0.9rem;
    }
    
    .order-actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .order-actions .btn {
        width: 100%;
        margin: 0;
    }
}

/* Animaciones mejoradas */
.order-history-header {
    animation: slideInDown 0.8s ease forwards;
}

.orders-section {
    animation: slideInUp 0.8s ease 0.3s forwards;
    opacity: 0;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Efectos hover adicionales */
.order-row {
    transition: all 0.3s ease;
    position: relative;
}

.order-row::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: linear-gradient(90deg, #8B0000, transparent);
    transition: width 0.3s ease;
    z-index: 1;
}

.order-row:hover::before {
    width: 4px;
}

/* Mejorar filtros */
.table-filters .form-select option {
    background: #1a1a1a;
/* Mejorar filtros */
.table-filters .form-select option {
    background: #1a1a1a;
    color: white;
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
    // Por ahora mostrar alert, después se puede crear una página de detalles
    showNotification('Redirigiendo a detalles del pedido #' + orderId, 'info');
    // TODO: Implementar página de detalles
    // window.location.href = 'order-details.php?id=' + orderId;
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