<?php
$page_title = 'Dashboard';
require_once 'inc/header.php';

// Obtener estadísticas básicas
try {
    // Productos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $total_products = $stmt->fetch()['total'];
    
    // Productos con stock bajo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock <= 10 AND status = 'active'");
    $low_stock_products = $stmt->fetch()['total'];
    
    // Órdenes pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetch()['total'];
    
    // Usuarios registrados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $total_users = $stmt->fetch()['total'];
    
    // Ventas del mes actual
    $stmt = $pdo->query("
        SELECT COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue 
        FROM orders 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND status IN ('completed', 'processing')
    ");
    $monthly_stats = $stmt->fetch();
    
    // Últimas órdenes
    $stmt = $pdo->query("
        SELECT o.id, o.total, o.status, o.created_at, 
               CONCAT(u.first_name, ' ', u.last_name) as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_products = 0;
    $low_stock_products = 0;
    $pending_orders = 0;
    $total_users = 0;
    $monthly_stats = ['orders' => 0, 'revenue' => 0];
    $recent_orders = [];
}
?>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stats-number"><?php echo number_format($total_products); ?></div>
                        <div class="stats-label">Productos Activos</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="products.php" class="text-white text-decoration-none">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stats-number"><?php echo number_format($pending_orders); ?></div>
                        <div class="stats-label">Órdenes Pendientes</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="orders.php?status=pending" class="text-white text-decoration-none">
                    Ver todas <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stats-number">$<?php echo number_format($monthly_stats['revenue'], 0); ?></div>
                        <div class="stats-label">Ventas del Mes</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="reports.php" class="text-white text-decoration-none">
                    Ver reportes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stats-number"><?php echo number_format($total_users); ?></div>
                        <div class="stats-label">Usuarios Registrados</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="users.php" class="text-white text-decoration-none">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Alertas -->
    <?php if ($low_stock_products > 0): ?>
    <div class="col-12 mb-4">
        <div class="alert alert-warning border-left-warning" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atención:</strong> Hay <?php echo $low_stock_products; ?> productos con stock bajo (≤10 unidades).
            <a href="products.php?low_stock=1" class="alert-link">Ver productos</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Últimas Órdenes -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>Últimas Órdenes
                </h5>
                <a href="orders.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                    <p class="text-muted text-center py-4">No hay órdenes recientes</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Invitado'); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = match($order['status']) {
                                            'completed' => 'bg-success',
                                            'processing' => 'bg-info',
                                            'pending' => 'bg-warning',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $status_text = match($order['status']) {
                                            'completed' => 'Completado',
                                            'processing' => 'Procesando',
                                            'pending' => 'Pendiente',
                                            'cancelled' => 'Cancelado',
                                            default => ucfirst($order['status'])
                                        };
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_view.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Accesos Rápidos -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (hasPermission('products', 'create')): ?>
                    <a href="product_edit.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Agregar Producto
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('users', 'read')): ?>
                    <a href="users.php" class="btn btn-outline-success">
                        <i class="fas fa-user-plus me-2"></i>Gestionar Usuarios
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('coupons', 'create')): ?>
                    <a href="coupons.php" class="btn btn-outline-warning">
                        <i class="fas fa-percentage me-2"></i>Crear Cupón
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('reports', 'read')): ?>
                    <a href="reports.php" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar me-2"></i>Ver Reportes
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('settings', 'read')): ?>
                    <a href="settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>Configuración
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Información del Sistema -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Información del Sistema
                </h5>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <div class="mb-2">
                        <strong>Usuario:</strong> <?php echo htmlspecialchars($current_user['full_name']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Rol:</strong> <?php echo htmlspecialchars($current_user['role_name']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Último acceso:</strong> <?php echo date('d/m/Y H:i'); ?>
                    </div>
                    <div>
                        <strong>Versión:</strong> v1.0.0
                    </div>
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>