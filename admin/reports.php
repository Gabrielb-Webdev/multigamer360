<?php
/**
 * =====================================================
 * REPORTES Y ANALÍTICAS - PANEL ADMINISTRATIVO
 * =====================================================
 * 
 * Descripción: Panel completo de reportes y analíticas para MultiGamer360
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-20
 * 
 * Funcionalidades:
 * - Dashboard con KPIs principales
 * - Reportes de ventas y financieros
 * - Analíticas de productos e inventario
 * - Métricas de usuarios y conversión
 * - Gráficos interactivos y exportación
 */

require_once 'inc/auth.php';

// $userManager ya está disponible desde auth.php

// Obtener datos para el dashboard
try {
    // KPIs principales del mes actual
    $stmt_kpis = $pdo->prepare("
        SELECT kpi_name, kpi_value, kpi_target, kpi_unit, trend, variance_percent, description
        FROM dashboard_kpis 
        WHERE period_start >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        ORDER BY category, kpi_name
    ");
    $stmt_kpis->execute();
    $kpis = $stmt_kpis->fetchAll(PDO::FETCH_ASSOC);

    // Métricas diarias de los últimos 7 días
    $stmt_daily = $pdo->prepare("
        SELECT date, total_sales, total_orders, total_visitors, conversion_rate
        FROM daily_metrics 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date DESC
    ");
    $stmt_daily->execute();
    $daily_metrics = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

    // Top productos por ventas (último mes)
    $stmt_top_products = $pdo->prepare("
        SELECT p.name, p.id, SUM(pa.purchases) as total_sales, SUM(pa.revenue) as total_revenue
        FROM product_analytics pa
        JOIN products p ON pa.product_id = p.id
        WHERE pa.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY p.id, p.name
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $stmt_top_products->execute();
    $top_products = $stmt_top_products->fetchAll(PDO::FETCH_ASSOC);

    // Resumen financiero del mes
    $stmt_financial = $pdo->prepare("
        SELECT 
            SUM(total_revenue) as monthly_revenue,
            SUM(total_cost) as monthly_cost,
            SUM(gross_profit) as monthly_profit,
            AVG(profit_margin) as avg_margin,
            SUM(total_orders) as monthly_orders,
            AVG(avg_order_value) as avg_order_value
        FROM financial_reports 
        WHERE period_start >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    ");
    $stmt_financial->execute();
    $financial_summary = $stmt_financial->fetch(PDO::FETCH_ASSOC);

    // Estado del inventario
    $stmt_inventory = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN stock_level <= reorder_point THEN 1 END) as low_stock_items,
            COUNT(CASE WHEN stock_level = 0 THEN 1 END) as out_of_stock_items,
            COUNT(CASE WHEN slow_moving_flag = 1 THEN 1 END) as slow_moving_items,
            COUNT(CASE WHEN dead_stock_flag = 1 THEN 1 END) as dead_stock_items,
            AVG(turnover_rate) as avg_turnover_rate
        FROM inventory_analytics ia
        WHERE ia.date = (SELECT MAX(date) FROM inventory_analytics)
    ");
    $stmt_inventory->execute();
    $inventory_status = $stmt_inventory->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}

// Función para formatear números
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

// Función para formatear moneda
function formatCurrency($amount) {
    return '$' . number_format($amount, 2, ',', '.');
}

// Función para obtener icono de tendencia
function getTrendIcon($trend) {
    switch($trend) {
        case 'up': return '<i class="fas fa-arrow-up text-success"></i>';
        case 'down': return '<i class="fas fa-arrow-down text-danger"></i>';
        default: return '<i class="fas fa-minus text-warning"></i>';
    }
}

$page_title = "Reportes y Analíticas";
require_once 'inc/header.php';
?>

<style>
    .dashboard-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-2px);
    }
    .kpi-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        }
        .kpi-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .kpi-trend {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
        .metric-summary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .financial-summary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .inventory-alert {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .nav-pills .nav-link.active {
            background-color: #6c63ff;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .progress-custom {
            height: 20px;
            border-radius: 10px;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        .content-area {
            padding: 20px;
        }
    </style>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-chart-line me-2"></i>Reportes y Analíticas</h1>
            </div>
            
            <!-- Dashboard de Reportes -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tachometer-alt text-primary"></i> Dashboard de Reportes</h2>
                <div>
                    <button class="btn btn-primary me-2" onclick="exportData('pdf')">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                    <button class="btn btn-success" onclick="exportData('excel')">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </button>
                    </div>
                </div>

                <!-- KPIs Principales -->
                <div class="row mb-4">
                    <?php if (!empty($kpis)): ?>
                        <?php foreach (array_slice($kpis, 0, 4) as $kpi): ?>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="small opacity-75"><?= htmlspecialchars($kpi['kpi_name']) ?></div>
                                        <div class="kpi-value">
                                            <?= formatNumber($kpi['kpi_value'], 2) ?><?= htmlspecialchars($kpi['kpi_unit']) ?>
                                        </div>
                                        <div class="kpi-trend">
                                            <?= getTrendIcon($kpi['trend']) ?>
                                            <?= abs($kpi['variance_percent']) ?>%
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Resúmenes Ejecutivos -->
                <div class="row mb-4">
                    <?php if ($financial_summary): ?>
                    <div class="col-md-4">
                        <div class="financial-summary">
                            <h5><i class="fas fa-dollar-sign"></i> Resumen Financiero</h5>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between">
                                    <span>Ingresos del Mes:</span>
                                    <strong><?= formatCurrency($financial_summary['monthly_revenue'] ?? 0) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Ganancia Bruta:</span>
                                    <strong><?= formatCurrency($financial_summary['monthly_profit'] ?? 0) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Margen Promedio:</span>
                                    <strong><?= formatNumber($financial_summary['avg_margin'] ?? 0, 1) ?>%</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Pedidos Totales:</span>
                                    <strong><?= formatNumber($financial_summary['monthly_orders'] ?? 0) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($inventory_status): ?>
                    <div class="col-md-4">
                        <div class="inventory-alert">
                            <h5><i class="fas fa-warehouse"></i> Estado del Inventario</h5>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between">
                                    <span>Stock Bajo:</span>
                                    <strong><?= formatNumber($inventory_status['low_stock_items'] ?? 0) ?> items</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sin Stock:</span>
                                    <strong><?= formatNumber($inventory_status['out_of_stock_items'] ?? 0) ?> items</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Movimiento Lento:</span>
                                    <strong><?= formatNumber($inventory_status['slow_moving_items'] ?? 0) ?> items</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Rotación Promedio:</span>
                                    <strong><?= formatNumber($inventory_status['avg_turnover_rate'] ?? 0, 1) ?>x</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <div class="metric-summary">
                            <h5><i class="fas fa-chart-bar"></i> Métricas Clave</h5>
                            <div class="mt-3">
                                <?php if (!empty($daily_metrics)): ?>
                                    <?php $latest = $daily_metrics[0]; ?>
                                    <div class="d-flex justify-content-between">
                                        <span>Ventas Ayer:</span>
                                        <strong><?= formatCurrency($latest['total_sales']) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Pedidos Ayer:</span>
                                        <strong><?= formatNumber($latest['total_orders']) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Visitantes Ayer:</span>
                                        <strong><?= formatNumber($latest['total_visitors']) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Conversión Ayer:</span>
                                        <strong><?= formatNumber($latest['conversion_rate'], 2) ?>%</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line"></i> Tendencia de Ventas (7 días)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5><i class="fas fa-star"></i> Top Productos</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($top_products)): ?>
                                    <?php foreach (array_slice($top_products, 0, 5) as $index => $product): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <div class="badge bg-primary me-2"><?= $index + 1 ?></div>
                                            <small><?= htmlspecialchars($product['name']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?= formatNumber($product['total_sales']) ?> ventas</small><br>
                                            <strong><?= formatCurrency($product['total_revenue']) ?></strong>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Section -->
            <div id="sales-section" class="content-section d-none">
                <h2><i class="fas fa-chart-bar text-primary"></i> Reportes de Ventas</h2>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card dashboard-card">
                            <div class="card-header d-flex justify-content-between">
                                <h5>Métricas de Ventas Diarias</h5>
                                <div>
                                    <select class="form-select form-select-sm" id="salesPeriod">
                                        <option value="7">Últimos 7 días</option>
                                        <option value="30">Últimos 30 días</option>
                                        <option value="90">Últimos 90 días</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Ventas</th>
                                                <th>Pedidos</th>
                                                <th>Visitantes</th>
                                                <th>Conversión</th>
                                                <th>Valor Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($daily_metrics as $metric): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($metric['date'])) ?></td>
                                                <td><strong><?= formatCurrency($metric['total_sales']) ?></strong></td>
                                                <td><?= formatNumber($metric['total_orders']) ?></td>
                                                <td><?= formatNumber($metric['total_visitors']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $metric['conversion_rate'] > 6 ? 'success' : ($metric['conversion_rate'] > 4 ? 'warning' : 'danger') ?>">
                                                        <?= formatNumber($metric['conversion_rate'], 2) ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $avg_value = $metric['total_orders'] > 0 ? $metric['total_sales'] / $metric['total_orders'] : 0;
                                                    echo formatCurrency($avg_value);
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Section -->
            <div id="financial-section" class="content-section d-none">
                <h2><i class="fas fa-dollar-sign text-primary"></i> Reportes Financieros</h2>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5>Resumen Financiero Mensual</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="financialChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5>Márgenes de Ganancia</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="marginChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div id="products-section" class="content-section d-none">
                <h2><i class="fas fa-box text-primary"></i> Analíticas de Productos</h2>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5>Rendimiento de Productos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Producto</th>
                                                <th>Ventas</th>
                                                <th>Ingresos</th>
                                                <th>Performance</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= formatNumber($product['total_sales']) ?></td>
                                                <td><?= formatCurrency($product['total_revenue']) ?></td>
                                                <td>
                                                    <?php $performance = min(100, ($product['total_revenue'] / 1000) * 100); ?>
                                                    <div class="progress progress-custom">
                                                        <div class="progress-bar bg-gradient" style="width: <?= $performance ?>%"></div>
                                                    </div>
                                                    <small><?= formatNumber($performance, 1) ?>%</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProductDetails(<?= $product['id'] ?>)">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Section -->
            <div id="inventory-section" class="content-section d-none">
                <h2><i class="fas fa-warehouse text-primary"></i> Analíticas de Inventario</h2>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                <h4><?= formatNumber($inventory_status['low_stock_items'] ?? 0) ?></h4>
                                <p class="text-muted">Items con Stock Bajo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <i class="fas fa-times-circle fa-2x text-danger mb-3"></i>
                                <h4><?= formatNumber($inventory_status['out_of_stock_items'] ?? 0) ?></h4>
                                <p class="text-muted">Sin Stock</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <i class="fas fa-turtle fa-2x text-info mb-3"></i>
                                <h4><?= formatNumber($inventory_status['slow_moving_items'] ?? 0) ?></h4>
                                <p class="text-muted">Movimiento Lento</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <i class="fas fa-sync fa-2x text-success mb-3"></i>
                                <h4><?= formatNumber($inventory_status['avg_turnover_rate'] ?? 0, 1) ?>x</h4>
                                <p class="text-muted">Rotación Promedio</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Section -->
            <div id="customers-section" class="content-section d-none">
                <h2><i class="fas fa-users text-primary"></i> Analíticas de Clientes</h2>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5>Métricas de Clientes</h5>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted">Las analíticas de clientes estarán disponibles con más datos de usuarios.</p>
                                <i class="fas fa-users fa-4x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Section -->
            <div id="analytics-section" class="content-section d-none">
                <h2><i class="fas fa-chart-pie text-primary"></i> Analíticas Avanzadas</h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5>Distribución de KPIs</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="kpiChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5>Tendencias de Conversión</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="conversionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Datos para gráficos
const salesData = {
    labels: [<?php echo implode(',', array_map(function($m) { return "'" . date('d/m', strtotime($m['date'])) . "'"; }, array_reverse($daily_metrics))); ?>],
    datasets: [{
        label: 'Ventas Diarias',
        data: [<?php echo implode(',', array_map(function($m) { return $m['total_sales']; }, array_reverse($daily_metrics))); ?>],
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1
    }]
};

const ordersData = {
    labels: [<?php echo implode(',', array_map(function($m) { return "'" . date('d/m', strtotime($m['date'])) . "'"; }, array_reverse($daily_metrics))); ?>],
    datasets: [{
        label: 'Pedidos Diarios',
        data: [<?php echo implode(',', array_map(function($m) { return $m['total_orders']; }, array_reverse($daily_metrics))); ?>],
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        tension: 0.1
    }]
};

// Configuración de gráficos
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true
        }
    }
};

// Navegación entre secciones
document.querySelectorAll('.sidebar .nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const section = this.getAttribute('data-section');
        
        if (section) {
            // Ocultar todas las secciones
            document.querySelectorAll('.content-section').forEach(s => s.classList.add('d-none'));
            
            // Mostrar sección seleccionada
            document.getElementById(section + '-section').classList.remove('d-none');
            
            // Actualizar navegación activa
            document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        }
    });
});

// Inicializar gráficos cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de ventas
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: salesData,
            options: chartOptions
        });
    }
    
    // Gráfico financiero
    const financialCtx = document.getElementById('financialChart');
    if (financialCtx) {
        new Chart(financialCtx, {
            type: 'bar',
            data: {
                labels: ['Ingresos', 'Costos', 'Ganancia'],
                datasets: [{
                    label: 'Financiero',
                    data: [
                        <?= $financial_summary['monthly_revenue'] ?? 0 ?>,
                        <?= $financial_summary['monthly_cost'] ?? 0 ?>,
                        <?= $financial_summary['monthly_profit'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ]
                }]
            },
            options: chartOptions
        });
    }
    
    // Gráfico de márgenes
    const marginCtx = document.getElementById('marginChart');
    if (marginCtx) {
        new Chart(marginCtx, {
            type: 'doughnut',
            data: {
                labels: ['Margen', 'Costo'],
                datasets: [{
                    data: [
                        <?= $financial_summary['avg_margin'] ?? 0 ?>,
                        <?= 100 - ($financial_summary['avg_margin'] ?? 0) ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 205, 86, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // Gráfico de KPIs
    const kpiCtx = document.getElementById('kpiChart');
    if (kpiCtx) {
        new Chart(kpiCtx, {
            type: 'radar',
            data: {
                labels: [<?php echo implode(',', array_map(function($k) { return "'" . $k['kpi_name'] . "'"; }, array_slice($kpis, 0, 6))); ?>],
                datasets: [{
                    label: 'Valores Actuales',
                    data: [<?php echo implode(',', array_map(function($k) { return $k['kpi_value']; }, array_slice($kpis, 0, 6))); ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // Gráfico de conversión
    const conversionCtx = document.getElementById('conversionChart');
    if (conversionCtx) {
        new Chart(conversionCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($m) { return "'" . date('d/m', strtotime($m['date'])) . "'"; }, array_reverse($daily_metrics))); ?>],
                datasets: [{
                    label: 'Tasa de Conversión (%)',
                    data: [<?php echo implode(',', array_map(function($m) { return $m['conversion_rate']; }, array_reverse($daily_metrics))); ?>],
                    borderColor: 'rgb(153, 102, 255)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    tension: 0.1
                }]
            },
            options: chartOptions
        });
    }
});

// Funciones auxiliares
function exportData(format) {
    alert('Función de exportación a ' + format + ' será implementada próximamente.');
}

function viewProductDetails(productId) {
    alert('Redirigiendo a detalles del producto ID: ' + productId);
}

// Actualización en tiempo real (simulada)
setInterval(function() {
    // Aquí se podría implementar actualización automática de datos
}, 300000); // 5 minutos
</script>

<?php require_once 'inc/footer.php'; ?>