<?php
/**
 * =====================================================
 * SETUP REPORTES Y ANALÍTICAS - MULTIGAMER360
 * =====================================================
 * 
 * Descripción: Script para configurar las tablas de reportes y analíticas
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-20
 * 
 * Funcionalidades:
 * - Crear tablas para analíticas de ventas
 * - Configurar sistema de reportes automatizados
 * - Tablas para métricas de rendimiento
 * - Sistema de seguimiento de KPIs
 */

require_once '../config/database.php';

echo "<h2>🔧 Configurando Sistema de Reportes y Analíticas</h2>";

try {
    // 1. Tabla para métricas diarias
    $sql_daily_metrics = "
    CREATE TABLE IF NOT EXISTS daily_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        total_sales DECIMAL(10,2) DEFAULT 0,
        total_orders INT DEFAULT 0,
        total_visitors INT DEFAULT 0,
        total_users_registered INT DEFAULT 0,
        total_products_sold INT DEFAULT 0,
        avg_order_value DECIMAL(10,2) DEFAULT 0,
        conversion_rate DECIMAL(5,2) DEFAULT 0,
        cart_abandonment_rate DECIMAL(5,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_date (date),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_daily_metrics);
    echo "<div class='alert alert-success'>✅ Tabla 'daily_metrics' creada exitosamente</div>";

    // 2. Tabla para reportes de productos
    $sql_product_analytics = "
    CREATE TABLE IF NOT EXISTS product_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        date DATE NOT NULL,
        views INT DEFAULT 0,
        cart_additions INT DEFAULT 0,
        purchases INT DEFAULT 0,
        revenue DECIMAL(10,2) DEFAULT 0,
        profit_margin DECIMAL(5,2) DEFAULT 0,
        stock_level INT DEFAULT 0,
        rating_avg DECIMAL(3,2) DEFAULT 0,
        reviews_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_date (product_id, date),
        INDEX idx_date (date),
        INDEX idx_product_id (product_id),
        INDEX idx_views (views),
        INDEX idx_purchases (purchases)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_product_analytics);
    echo "<div class='alert alert-success'>✅ Tabla 'product_analytics' creada exitosamente</div>";

    // 3. Tabla para analíticas de usuarios
    $sql_user_analytics = "
    CREATE TABLE IF NOT EXISTS user_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        session_id VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        page_views INT DEFAULT 0,
        time_on_site INT DEFAULT 0,
        bounce_rate DECIMAL(5,2) DEFAULT 0,
        pages_per_session DECIMAL(5,2) DEFAULT 0,
        device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
        browser VARCHAR(50) DEFAULT NULL,
        operating_system VARCHAR(50) DEFAULT NULL,
        referrer_source VARCHAR(255) DEFAULT NULL,
        conversion_value DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        INDEX idx_date (date),
        INDEX idx_device_type (device_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_user_analytics);
    echo "<div class='alert alert-success'>✅ Tabla 'user_analytics' creada exitosamente</div>";

    // 4. Tabla para reportes financieros
    $sql_financial_reports = "
    CREATE TABLE IF NOT EXISTS financial_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        total_revenue DECIMAL(12,2) DEFAULT 0,
        total_cost DECIMAL(12,2) DEFAULT 0,
        gross_profit DECIMAL(12,2) DEFAULT 0,
        net_profit DECIMAL(12,2) DEFAULT 0,
        profit_margin DECIMAL(5,2) DEFAULT 0,
        total_orders INT DEFAULT 0,
        avg_order_value DECIMAL(10,2) DEFAULT 0,
        total_customers INT DEFAULT 0,
        new_customers INT DEFAULT 0,
        returning_customers INT DEFAULT 0,
        refunds_total DECIMAL(10,2) DEFAULT 0,
        tax_total DECIMAL(10,2) DEFAULT 0,
        shipping_revenue DECIMAL(10,2) DEFAULT 0,
        discount_total DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_period (report_type, period_start, period_end),
        INDEX idx_report_type (report_type),
        INDEX idx_period_start (period_start),
        INDEX idx_period_end (period_end)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_financial_reports);
    echo "<div class='alert alert-success'>✅ Tabla 'financial_reports' creada exitosamente</div>";

    // 5. Tabla para inventario y alertas
    $sql_inventory_analytics = "
    CREATE TABLE IF NOT EXISTS inventory_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        date DATE NOT NULL,
        stock_level INT DEFAULT 0,
        stock_movement INT DEFAULT 0,
        stock_value DECIMAL(10,2) DEFAULT 0,
        reorder_point INT DEFAULT 0,
        lead_time_days INT DEFAULT 0,
        turnover_rate DECIMAL(5,2) DEFAULT 0,
        days_of_supply INT DEFAULT 0,
        out_of_stock_days INT DEFAULT 0,
        slow_moving_flag BOOLEAN DEFAULT FALSE,
        dead_stock_flag BOOLEAN DEFAULT FALSE,
        abc_classification ENUM('A', 'B', 'C') DEFAULT 'C',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_date (product_id, date),
        INDEX idx_product_id (product_id),
        INDEX idx_date (date),
        INDEX idx_stock_level (stock_level),
        INDEX idx_abc_classification (abc_classification)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_inventory_analytics);
    echo "<div class='alert alert-success'>✅ Tabla 'inventory_analytics' creada exitosamente</div>";

    // 6. Tabla para KPIs del dashboard
    $sql_dashboard_kpis = "
    CREATE TABLE IF NOT EXISTS dashboard_kpis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kpi_name VARCHAR(100) NOT NULL,
        kpi_value DECIMAL(15,4) NOT NULL,
        kpi_target DECIMAL(15,4) DEFAULT NULL,
        kpi_unit VARCHAR(20) DEFAULT NULL,
        period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        category ENUM('sales', 'marketing', 'inventory', 'customer', 'financial') NOT NULL,
        description TEXT,
        trend ENUM('up', 'down', 'stable') DEFAULT 'stable',
        variance_percent DECIMAL(5,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_kpi_period (kpi_name, period_start, period_end),
        INDEX idx_kpi_name (kpi_name),
        INDEX idx_period_start (period_start),
        INDEX idx_category (category),
        INDEX idx_trend (trend)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_dashboard_kpis);
    echo "<div class='alert alert-success'>✅ Tabla 'dashboard_kpis' creada exitosamente</div>";

    // Insertar datos de ejemplo
    echo "<h3>📊 Insertando datos de ejemplo...</h3>";

    // Métricas diarias de ejemplo (últimos 30 días)
    $insert_daily_metrics = "
    INSERT IGNORE INTO daily_metrics (date, total_sales, total_orders, total_visitors, total_users_registered, total_products_sold, avg_order_value, conversion_rate, cart_abandonment_rate) VALUES
    (DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1250.50, 25, 450, 5, 38, 50.02, 5.56, 68.25),
    (DATE_SUB(CURDATE(), INTERVAL 29 DAY), 980.75, 18, 380, 3, 28, 54.49, 4.74, 71.20),
    (DATE_SUB(CURDATE(), INTERVAL 28 DAY), 1650.25, 35, 520, 8, 52, 47.15, 6.73, 65.80),
    (DATE_SUB(CURDATE(), INTERVAL 27 DAY), 2100.80, 42, 680, 12, 68, 50.02, 6.18, 62.45),
    (DATE_SUB(CURDATE(), INTERVAL 26 DAY), 1850.90, 38, 590, 7, 58, 48.71, 6.44, 64.20),
    (DATE_SUB(CURDATE(), INTERVAL 25 DAY), 2450.50, 48, 750, 15, 78, 51.05, 6.40, 61.85),
    (DATE_SUB(CURDATE(), INTERVAL 24 DAY), 1720.30, 32, 480, 6, 48, 53.76, 6.67, 63.90),
    (DATE_SUB(CURDATE(), INTERVAL 23 DAY), 2850.75, 55, 820, 18, 89, 51.83, 6.71, 60.25),
    (DATE_SUB(CURDATE(), INTERVAL 22 DAY), 1980.40, 40, 650, 9, 62, 49.51, 6.15, 65.75),
    (DATE_SUB(CURDATE(), INTERVAL 21 DAY), 2250.80, 45, 710, 11, 71, 50.02, 6.34, 63.45),
    (DATE_SUB(CURDATE(), INTERVAL 20 DAY), 3150.25, 62, 950, 22, 98, 50.81, 6.53, 59.80),
    (DATE_SUB(CURDATE(), INTERVAL 19 DAY), 2680.50, 52, 780, 14, 82, 51.55, 6.67, 61.25),
    (DATE_SUB(CURDATE(), INTERVAL 18 DAY), 1850.75, 36, 580, 8, 56, 51.41, 6.21, 64.85),
    (DATE_SUB(CURDATE(), INTERVAL 17 DAY), 2950.90, 58, 890, 19, 92, 50.88, 6.52, 60.45),
    (DATE_SUB(CURDATE(), INTERVAL 16 DAY), 2150.40, 43, 670, 10, 68, 50.01, 6.42, 62.90),
    (DATE_SUB(CURDATE(), INTERVAL 15 DAY), 3450.80, 68, 1050, 25, 108, 50.75, 6.48, 58.95),
    (DATE_SUB(CURDATE(), INTERVAL 14 DAY), 2850.25, 56, 820, 16, 88, 50.90, 6.83, 60.75),
    (DATE_SUB(CURDATE(), INTERVAL 13 DAY), 2250.50, 44, 690, 12, 70, 51.15, 6.38, 63.20),
    (DATE_SUB(CURDATE(), INTERVAL 12 DAY), 3750.75, 74, 1150, 28, 118, 50.69, 6.43, 57.85),
    (DATE_SUB(CURDATE(), INTERVAL 11 DAY), 2680.90, 53, 810, 15, 84, 50.58, 6.54, 61.45),
    (DATE_SUB(CURDATE(), INTERVAL 10 DAY), 1950.40, 38, 620, 9, 60, 51.33, 6.13, 64.75),
    (DATE_SUB(CURDATE(), INTERVAL 9 DAY), 4150.80, 82, 1250, 32, 132, 50.62, 6.56, 56.20),
    (DATE_SUB(CURDATE(), INTERVAL 8 DAY), 3250.25, 64, 980, 21, 102, 50.79, 6.53, 59.45),
    (DATE_SUB(CURDATE(), INTERVAL 7 DAY), 2850.50, 56, 850, 18, 89, 50.90, 6.59, 60.85),
    (DATE_SUB(CURDATE(), INTERVAL 6 DAY), 3850.75, 76, 1180, 29, 122, 50.67, 6.44, 57.95),
    (DATE_SUB(CURDATE(), INTERVAL 5 DAY), 2950.90, 58, 890, 19, 92, 50.88, 6.52, 60.45),
    (DATE_SUB(CURDATE(), INTERVAL 4 DAY), 3450.40, 68, 1050, 25, 108, 50.74, 6.48, 58.95),
    (DATE_SUB(CURDATE(), INTERVAL 3 DAY), 4250.80, 84, 1300, 34, 135, 50.61, 6.46, 55.80),
    (DATE_SUB(CURDATE(), INTERVAL 2 DAY), 3750.25, 74, 1150, 28, 118, 50.69, 6.43, 57.85),
    (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2850.50, 56, 850, 18, 89, 50.90, 6.59, 60.85);
    ";

    $pdo->exec($insert_daily_metrics);
    echo "<div class='alert alert-info'>📈 Métricas diarias de ejemplo insertadas</div>";

    // KPIs de ejemplo
    $insert_kpis = "
    INSERT IGNORE INTO dashboard_kpis (kpi_name, kpi_value, kpi_target, kpi_unit, period_type, period_start, period_end, category, description, trend, variance_percent) VALUES
    ('Revenue Growth Rate', 15.75, 20.00, '%', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'sales', 'Tasa de crecimiento de ingresos mensual', 'up', 5.25),
    ('Customer Acquisition Cost', 35.50, 30.00, 'USD', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'marketing', 'Costo promedio de adquisición de clientes', 'up', -18.33),
    ('Customer Lifetime Value', 285.75, 300.00, 'USD', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'customer', 'Valor de vida promedio del cliente', 'down', -4.75),
    ('Inventory Turnover', 8.25, 10.00, 'times', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'inventory', 'Rotación de inventario mensual', 'stable', -17.50),
    ('Conversion Rate', 6.45, 7.00, '%', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'sales', 'Tasa de conversión promedio', 'up', 2.15),
    ('Average Order Value', 50.85, 55.00, 'USD', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'sales', 'Valor promedio de pedido', 'stable', -7.54),
    ('Cart Abandonment Rate', 62.50, 65.00, '%', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'sales', 'Tasa de abandono de carrito', 'down', 3.85),
    ('Gross Profit Margin', 45.80, 50.00, '%', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'financial', 'Margen de ganancia bruta', 'stable', -8.40),
    ('Return Rate', 3.25, 5.00, '%', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'customer', 'Tasa de devoluciones', 'down', 35.00),
    ('Customer Satisfaction Score', 4.25, 4.50, 'stars', 'monthly', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)), 'customer', 'Puntuación promedio de satisfacción', 'stable', -5.56);
    ";

    $pdo->exec($insert_kpis);
    echo "<div class='alert alert-info'>🎯 KPIs de ejemplo insertados</div>";

    echo "<div class='alert alert-success mt-4'><h4>🎉 Sistema de Reportes y Analíticas configurado correctamente!</h4>";
    echo "<p><strong>Componentes creados:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Métricas diarias automatizadas</li>";
    echo "<li>✅ Analíticas de productos</li>";
    echo "<li>✅ Seguimiento de usuarios</li>";
    echo "<li>✅ Reportes financieros</li>";
    echo "<li>✅ Analíticas de inventario</li>";
    echo "<li>✅ Dashboard de KPIs</li>";
    echo "</ul></div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error al configurar sistema de reportes: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Reportes - MultiGamer360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <!-- El contenido PHP se muestra arriba -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>