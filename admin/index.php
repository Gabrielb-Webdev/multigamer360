<?php
/**
 * =====================================================
 * DASHBOARD PRINCIPAL UNIFICADO
 * =====================================================
 * 
 * Dashboard completo con todas las métricas importantes:
 * - Ventas y órdenes del día
 * - Inventario y stock
 * - Alertas operativas
 * - Top productos de la semana
 * 
 * Versión: 3.0 - Dashboard Unificado
 * Fecha: 2025-10-14
 */

$page_title = 'Dashboard Principal';
require_once 'inc/header.php';

try {
    // ========================================
    // MÉTRICAS DEL DÍA - VENTAS
    // ========================================
    
    // Ventas de hoy
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(AVG(total_amount), 0) as avg_order_value
        FROM orders 
        WHERE DATE(created_at) = CURDATE()
        AND status != 'cancelled'
    ");
    $today_sales = $stmt->fetch();
    
    // Ventas de ayer (para comparación)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales
        FROM orders 
        WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        AND status != 'cancelled'
    ");
    $yesterday_sales = $stmt->fetch();
    
    // Calcular variación
    $sales_change = 0;
    $orders_change = 0;
    if ($yesterday_sales['total_sales'] > 0) {
        $sales_change = (($today_sales['total_sales'] - $yesterday_sales['total_sales']) / $yesterday_sales['total_sales']) * 100;
    }
    if ($yesterday_sales['total_orders'] > 0) {
        $orders_change = (($today_sales['total_orders'] - $yesterday_sales['total_orders']) / $yesterday_sales['total_orders']) * 100;
    }
    
    // ========================================
    // MÉTRICAS DE INVENTARIO
    // ========================================
    
    // Total productos
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM products 
        WHERE is_active = 1
    ");
    $total_products = $stmt->fetchColumn();
    
    // Stock total
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(stock_quantity), 0) as total
        FROM products 
        WHERE is_active = 1
    ");
    $stock_total = $stmt->fetchColumn();
    
    // Valor del inventario
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(stock_quantity * price_pesos), 0) as inventory_value
        FROM products 
        WHERE is_active = 1
    ");
    $inventory_value = $stmt->fetchColumn();
    
    // Stock bajo (≤ 10 unidades)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM products 
        WHERE stock_quantity <= 10 
        AND stock_quantity > 0
        AND is_active = 1
    ");
    $low_stock_count = $stmt->fetchColumn();
    
    // Productos agotados
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM products 
        WHERE stock_quantity = 0
        AND is_active = 1
    ");
    $out_stock_count = $stmt->fetchColumn();
    
    // ========================================
    // ALERTAS IMPORTANTES
    // ========================================
    
    // Órdenes pendientes
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM orders 
        WHERE status = 'pending'
    ");
    $pending_orders = $stmt->fetchColumn();
    
    // Órdenes para enviar
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM orders 
        WHERE status = 'processing'
    ");
    $processing_orders = $stmt->fetchColumn();
    
    // ========================================
    // TOP 10 PRODUCTOS DE LA SEMANA
    // ========================================
    
    $stmt = $pdo->query("
        SELECT 
            p.name,
            p.sku,
            COUNT(oi.id) as times_sold,
            SUM(oi.quantity) as units_sold,
            SUM(oi.total_price) as revenue
        FROM order_items oi
        INNER JOIN products p ON oi.product_id = p.id
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND o.status != 'cancelled'
        GROUP BY p.id, p.name, p.sku
        ORDER BY revenue DESC
        LIMIT 10
    ");
    $top_products = $stmt->fetchAll();
    
    // ========================================
    // RESUMEN DE LA SEMANA
    // ========================================
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(AVG(total_amount), 0) as avg_order
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND status != 'cancelled'
    ");
    $week_summary = $stmt->fetch();
    
    // Nuevos usuarios esta semana
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $new_users = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar datos: ' . $e->getMessage();
    $today_sales = ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0];
    $yesterday_sales = ['total_orders' => 0, 'total_sales' => 0];
    $total_products = 0;
    $stock_total = 0;
    $inventory_value = 0;
    $low_stock_count = 0;
    $out_stock_count = 0;
    $pending_orders = 0;
    $processing_orders = 0;
    $top_products = [];
    $week_summary = ['total_orders' => 0, 'total_sales' => 0, 'avg_order' => 0];
    $new_users = 0;
}
?>

<style>
    .metric-card {
        border-radius: 12px;
        padding: 1.5rem;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 0.5rem 0;
    }
    
    .metric-label {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    .metric-change {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(255,255,255,0.8);
    }
    
    .metric-change i {
        margin-right: 3px;
    }
    
    .alert-card {
        border-left: 4px solid;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        background: white;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    
    .alert-card:hover {
        transform: translateX(5px);
    }
    
    .alert-card.warning {
        border-left-color: #ffc107;
        background: #fff9e6;
    }
    
    .alert-card.danger {
        border-left-color: #dc3545;
        background: #ffe6e6;
    }
    
    .alert-card.info {
        border-left-color: #17a2b8;
        background: #e6f7ff;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #333;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .product-table {
        font-size: 0.9rem;
    }
    
    .product-table th {
        background: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-top: none;
    }
    
    .product-table tbody tr {
        transition: background-color 0.2s;
    }
    
    .product-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge-rank {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        font-weight: 700;
    }
    
    .section-divider {
        margin: 2rem 0 1rem 0;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
</style>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-tachometer-alt text-primary"></i> Dashboard Principal</h2>
        <p class="text-muted mb-0">Resumen completo en tiempo real • <?php echo date('d/m/Y H:i'); ?></p>
    </div>
</div>

<!-- Métricas de Ventas -->
<div class="section-divider">
    <h5 class="text-muted mb-0"><i class="fas fa-shopping-cart"></i> Ventas del Día</h5>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card metric-card bg-primary text-white">
            <div class="metric-label">Ventas Hoy</div>
            <div class="metric-value">
                $<?php echo number_format($today_sales['total_sales'] ?? 0, 0); ?>
            </div>
            <?php if (isset($sales_change) && $sales_change != 0): ?>
            <div class="metric-change">
                <i class="fas fa-arrow-<?php echo $sales_change > 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($sales_change, 1)); ?>% vs ayer
            </div>
            <?php else: ?>
            <div class="metric-change opacity-50">
                <i class="fas fa-minus"></i> Sin cambios
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card bg-success text-white">
            <div class="metric-label">Órdenes Hoy</div>
            <div class="metric-value">
                <?php echo number_format($today_sales['total_orders'] ?? 0); ?>
            </div>
            <?php if (isset($orders_change) && $orders_change != 0): ?>
            <div class="metric-change">
                <i class="fas fa-arrow-<?php echo $orders_change > 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($orders_change, 1)); ?>% vs ayer
            </div>
            <?php else: ?>
            <div class="metric-change opacity-50">
                <i class="fas fa-minus"></i> Sin cambios
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card bg-info text-white">
            <div class="metric-label">Ticket Promedio</div>
            <div class="metric-value">
                $<?php echo number_format($today_sales['avg_order_value'] ?? 0, 0); ?>
            </div>
            <div class="small opacity-75">Por orden hoy</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card bg-warning text-white">
            <div class="metric-label">Nuevos Clientes</div>
            <div class="metric-value">
                <?php echo number_format($new_users ?? 0); ?>
            </div>
            <div class="small opacity-75">Últimos 7 días</div>
        </div>
    </div>
</div>

<!-- Métricas de Inventario -->
<div class="section-divider">
    <h5 class="text-muted mb-0"><i class="fas fa-boxes"></i> Estado del Inventario</h5>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card metric-card bg-dark text-white">
            <div class="metric-label">Total Productos</div>
            <div class="metric-value">
                <?php echo number_format($total_products ?? 0); ?>
            </div>
            <div class="small opacity-75">Activos</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="metric-label text-white">Stock Total</div>
            <div class="metric-value text-white">
                <?php echo number_format($stock_total ?? 0); ?>
            </div>
            <div class="small text-white opacity-75">Unidades</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="metric-label text-white">Valor Inventario</div>
            <div class="metric-value text-white">
                $<?php echo number_format($inventory_value ?? 0, 0); ?>
            </div>
            <div class="small text-white opacity-75">Total en stock</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="metric-label text-white">Stock Bajo</div>
            <div class="metric-value text-white">
                <?php echo number_format($low_stock_count ?? 0); ?>
            </div>
            <div class="small text-white opacity-75">≤10 unidades</div>
        </div>
    </div>
</div>

<!-- Resumen de la Semana -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-calendar-week text-primary"></i> Resumen de la Semana (Últimos 7 días)</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border-end">
                            <div class="text-muted small mb-2">VENTAS TOTALES</div>
                            <h3 class="text-primary mb-0">$<?php echo number_format($week_summary['total_sales'] ?? 0, 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <div class="text-muted small mb-2">ÓRDENES</div>
                            <h3 class="text-success mb-0"><?php echo number_format($week_summary['total_orders'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <div class="text-muted small mb-2">TICKET PROMEDIO</div>
                            <h3 class="text-info mb-0">$<?php echo number_format($week_summary['avg_order'] ?? 0, 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-2">NUEVOS CLIENTES</div>
                        <h3 class="text-warning mb-0"><?php echo number_format($new_users ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas y Top Productos -->
<div class="row">
    <!-- Alertas -->
    <div class="col-md-6 mb-4">
        <div class="section-title">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            Alertas Importantes
        </div>
        
        <?php if ($pending_orders > 0): ?>
        <div class="alert-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-clock text-info me-2"></i>
                    <strong><?php echo $pending_orders; ?></strong> órdenes pendientes
                </div>
                <a href="orders.php?status=pending" class="btn btn-sm btn-info">Ver</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($processing_orders > 0): ?>
        <div class="alert-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-shipping-fast text-info me-2"></i>
                    <strong><?php echo $processing_orders; ?></strong> órdenes por enviar
                </div>
                <a href="orders.php?status=processing" class="btn btn-sm btn-info">Ver</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($low_stock_count > 0): ?>
        <div class="alert-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    <strong><?php echo $low_stock_count; ?></strong> productos con stock bajo
                </div>
                <a href="products.php?stock_status=low" class="btn btn-sm btn-warning">Ver</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($out_stock_count > 0): ?>
        <div class="alert-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    <strong><?php echo $out_stock_count; ?></strong> productos agotados
                </div>
                <a href="products.php?stock_status=out" class="btn btn-sm btn-danger">Ver</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($pending_orders == 0 && $processing_orders == 0 && $low_stock_count == 0 && $out_stock_count == 0): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> ¡No hay alertas pendientes! Todo está en orden.
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Top 10 Productos -->
    <div class="col-md-6 mb-4">
        <div class="section-title">
            <i class="fas fa-star text-warning"></i>
            Top 10 Productos (Última Semana)
        </div>
        
        <?php if (!empty($top_products)): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover product-table mb-0">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th class="text-center">Veces Vendido</th>
                                <th class="text-center">Unidades</th>
                                <th class="text-end">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $index => $product): ?>
                            <tr>
                                <td>
                                    <?php if ($index < 3): ?>
                                        <span class="badge badge-rank bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'info'); ?>">
                                            #<?php echo $index + 1; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted fw-bold">#<?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                </td>
                                <td>
                                    <code class="small"><?php echo htmlspecialchars($product['sku']); ?></code>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo $product['times_sold']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?php echo $product['units_sold']; ?></span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">$<?php echo number_format($product['revenue'], 0); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay datos de ventas en los últimos 7 días.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh cada 5 minutos
setTimeout(function() {
    location.reload();
}, 300000);

// Mostrar última actualización
console.log('Dashboard actualizado: <?php echo date("Y-m-d H:i:s"); ?>');
</script>

<?php require_once 'inc/footer.php'; ?>
