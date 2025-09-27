<?php
$page_title = 'Gestión de Pedidos';
$page_actions = '<button class="btn btn-outline-primary" onclick="exportOrders()"><i class="fas fa-download"></i> Exportar</button>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('orders', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver pedidos';
    header('Location: index.php');
    exit;
}

// Parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = "(o.id LIKE ? OR o.order_number LIKE ? OR o.customer_email LIKE ? OR o.customer_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status;
}

if ($payment_status) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_status;
}

if ($date_from) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de pedidos
    $count_query = "SELECT COUNT(*) as total FROM orders o WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_orders = $count_stmt->fetch()['total'];
    
    // Obtener pedidos
    $query = "
        SELECT o.*, u.username, u.email as user_email,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE $where_clause
        ORDER BY o.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    $total_pages = ceil($total_orders / $per_page);
    
    // Estadísticas rápidas para el período filtrado
    $stats_query = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
        FROM orders o 
        WHERE $where_clause
    ";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar pedidos: ' . $e->getMessage();
    $orders = [];
    $total_orders = 0;
    $total_pages = 1;
    $stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0,
        'pending_orders' => 0,
        'completed_orders' => 0
    ];
}
?>

<!-- Estadísticas rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Total Pedidos</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Ingresos Totales</h6>
                        <h4 class="mb-0 text-success">$<?php echo number_format($stats['total_revenue']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Valor Promedio</h6>
                        <h4 class="mb-0 text-info">$<?php echo number_format($stats['avg_order_value']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Pendientes</h6>
                        <h4 class="mb-0 text-warning"><?php echo number_format($stats['pending_orders']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="ID, número, email, cliente...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Procesando</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completado</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="payment_status" class="form-label">Pago</label>
                <select class="form-select" id="payment_status" name="payment_status">
                    <option value="">Todos</option>
                    <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                    <option value="failed" <?php echo $payment_status === 'failed' ? 'selected' : ''; ?>>Fallido</option>
                    <option value="refunded" <?php echo $payment_status === 'refunded' ? 'selected' : ''; ?>>Reembolsado</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Desde</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Hasta</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de pedidos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-list me-2"></i>
            Pedidos (<?php echo number_format($total_orders); ?> total<?php echo $total_orders !== 1 ? 'es' : ''; ?>)
        </span>
        
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshOrders()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            
            <?php if (hasPermission('orders', 'update')): ?>
            <div class="bulk-actions" style="display: none;">
                <span class="me-2">
                    <span class="selected-count">0</span> seleccionado(s)
                </span>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                            data-bs-toggle="dropdown">
                        Cambiar Estado
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateStatus('processing')">Procesando</a></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateStatus('shipped')">Enviado</a></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateStatus('delivered')">Entregado</a></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateStatus('completed')">Completado</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateStatus('cancelled')">Cancelar</a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron pedidos</h5>
                <p class="text-muted">
                    <?php if ($search || $status || $payment_status || $date_from || $date_to): ?>
                        Intenta ajustar los filtros de búsqueda.
                    <?php else: ?>
                        Los pedidos aparecerán aquí cuando los clientes realicen compras.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php if (hasPermission('orders', 'update')): ?>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <?php endif; ?>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Fecha</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <?php if (hasPermission('orders', 'update')): ?>
                            <td>
                                <input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>">
                            </td>
                            <?php endif; ?>
                            <td>
                                <div>
                                    <strong>#<?php echo $order['order_number']; ?></strong>
                                    <br><small class="text-muted">ID: <?php echo $order['id']; ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                    <?php if ($order['username']): ?>
                                        <br><small class="badge bg-info">Usuario: <?php echo htmlspecialchars($order['username']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $order['items_count']; ?> productos</span>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($order['total_amount']); ?></strong>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                    <br><small class="text-muted">+ $<?php echo number_format($order['shipping_cost']); ?> envío</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_config = [
                                    'pending' => ['class' => 'bg-warning text-dark', 'text' => 'Pendiente'],
                                    'processing' => ['class' => 'bg-info', 'text' => 'Procesando'],
                                    'shipped' => ['class' => 'bg-primary', 'text' => 'Enviado'],
                                    'delivered' => ['class' => 'bg-success', 'text' => 'Entregado'],
                                    'cancelled' => ['class' => 'bg-danger', 'text' => 'Cancelado'],
                                    'completed' => ['class' => 'bg-success', 'text' => 'Completado']
                                ];
                                $status_info = $status_config[$order['status']] ?? ['class' => 'bg-secondary', 'text' => ucfirst($order['status'])];
                                ?>
                                <span class="badge <?php echo $status_info['class']; ?>">
                                    <?php echo $status_info['text']; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $payment_config = [
                                    'pending' => ['class' => 'bg-warning text-dark', 'text' => 'Pendiente'],
                                    'paid' => ['class' => 'bg-success', 'text' => 'Pagado'],
                                    'failed' => ['class' => 'bg-danger', 'text' => 'Fallido'],
                                    'refunded' => ['class' => 'bg-secondary', 'text' => 'Reembolsado']
                                ];
                                $payment_info = $payment_config[$order['payment_status']] ?? ['class' => 'bg-secondary', 'text' => ucfirst($order['payment_status'])];
                                ?>
                                <span class="badge <?php echo $payment_info['class']; ?>">
                                    <?php echo $payment_info['text']; ?>
                                </span>
                            </td>
                            <td>
                                <div>
                                    <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="order_view.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('orders', 'update')): ?>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')">
                                                <i class="fas fa-cog me-2"></i>Marcar como Procesando
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">
                                                <i class="fas fa-shipping-fast me-2"></i>Marcar como Enviado
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                                                <i class="fas fa-check-circle me-2"></i>Marcar como Entregado
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')">
                                                <i class="fas fa-times me-2"></i>Cancelar Pedido
                                            </a></li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="printOrder(<?php echo $order['id']; ?>)"
                                            data-bs-toggle="tooltip" title="Imprimir">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de pedidos" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Configurar selección múltiple
TableManager.setupBulkActions('.table');

// Actualizar estado de pedido individual
function updateOrderStatus(orderId, status) {
    const statusNames = {
        'processing': 'Procesando',
        'shipped': 'Enviado', 
        'delivered': 'Entregado',
        'cancelled': 'Cancelado',
        'completed': 'Completado'
    };
    
    Utils.confirm(`¿Cambiar estado del pedido a "${statusNames[status]}"?`, function() {
        fetch('api/orders.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                id: orderId, 
                status: status,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast('Estado actualizado correctamente', 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al actualizar estado', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Actualizar estado de múltiples pedidos
function bulkUpdateStatus(status) {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos un pedido', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    const statusNames = {
        'processing': 'Procesando',
        'shipped': 'Enviado', 
        'delivered': 'Entregado',
        'cancelled': 'Cancelado',
        'completed': 'Completado'
    };
    
    Utils.confirm(`¿Cambiar estado de ${ids.length} pedido(s) a "${statusNames[status]}"?`, function() {
        fetch('api/orders.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                ids: ids, 
                status: status,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast(`${ids.length} pedido(s) actualizado(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al actualizar pedidos', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Actualizar lista
function refreshOrders() {
    location.reload();
}

// Exportar pedidos
function exportOrders() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('api/orders-export.php?' + params.toString(), '_blank');
}

// Imprimir pedido
function printOrder(orderId) {
    window.open(`order_print.php?id=${orderId}`, '_blank', 'width=800,height=600');
}

// Auto-refresh cada 30 segundos si hay pedidos pendientes
if (<?php echo $stats['pending_orders']; ?> > 0) {
    setInterval(function() {
        // Solo refresh automático si no hay filtros activos
        if (!window.location.search || window.location.search === '?') {
            const notification = document.createElement('div');
            notification.className = 'toast-container position-fixed top-0 end-0 p-3';
            notification.innerHTML = `
                <div class="toast" role="alert">
                    <div class="toast-header">
                        <i class="fas fa-sync-alt text-primary me-2"></i>
                        <strong class="me-auto">Actualizando</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        Verificando nuevos pedidos...
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            const toast = new bootstrap.Toast(notification.querySelector('.toast'));
            toast.show();
            
            setTimeout(() => {
                refreshOrders();
            }, 2000);
        }
    }, 30000);
}
</script>

<?php require_once 'inc/footer.php'; ?>