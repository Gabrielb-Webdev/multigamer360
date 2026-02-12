<?php
require_once 'database.php';

echo "=== VERIFICANDO SISTEMA DE REPORTES Y ANALÍTICAS ===" . PHP_EOL;

try {
    // Verificar tabla dashboard_kpis
    echo "1. Verificando tabla dashboard_kpis..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE dashboard_kpis");
        echo "   ✓ Tabla dashboard_kpis existe" . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM dashboard_kpis");
        $kpis_count = $stmt->fetch()['total'];
        echo "   ✓ KPIs configurados: " . $kpis_count . PHP_EOL;
        
    } catch (Exception $e) {
        echo "   ❌ Tabla dashboard_kpis no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE dashboard_kpis (
            id INT PRIMARY KEY AUTO_INCREMENT,
            category VARCHAR(50) NOT NULL,
            kpi_name VARCHAR(100) NOT NULL,
            kpi_value DECIMAL(15,2) NOT NULL,
            kpi_target DECIMAL(15,2),
            kpi_unit VARCHAR(20),
            trend ENUM('up', 'down', 'stable') DEFAULT 'stable',
            variance_percent DECIMAL(5,2),
            description TEXT,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla dashboard_kpis creada" . PHP_EOL;
    }
    
    // Verificar tabla daily_metrics
    echo PHP_EOL . "2. Verificando tabla daily_metrics..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE daily_metrics");
        echo "   ✓ Tabla daily_metrics existe" . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM daily_metrics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $recent_metrics = $stmt->fetch()['total'];
        echo "   ✓ Métricas recientes (7 días): " . $recent_metrics . PHP_EOL;
        
    } catch (Exception $e) {
        echo "   ❌ Tabla daily_metrics no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE daily_metrics (
            id INT PRIMARY KEY AUTO_INCREMENT,
            date DATE UNIQUE NOT NULL,
            total_sales DECIMAL(15,2) DEFAULT 0,
            total_orders INT DEFAULT 0,
            total_visitors INT DEFAULT 0,
            unique_visitors INT DEFAULT 0,
            page_views INT DEFAULT 0,
            conversion_rate DECIMAL(5,2) DEFAULT 0,
            avg_order_value DECIMAL(10,2) DEFAULT 0,
            new_customers INT DEFAULT 0,
            returning_customers INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla daily_metrics creada" . PHP_EOL;
    }
    
    // Crear datos de ejemplo si no existen
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM dashboard_kpis");
    $kpis_count = $stmt->fetch()['total'];
    
    if ($kpis_count == 0) {
        echo PHP_EOL . "3. Creando KPIs de ejemplo..." . PHP_EOL;
        
        $sample_kpis = [
            ['ventas', 'Ventas del Mes', 15750.00, 20000.00, 'USD', 'up', 15.2, 'Total de ventas en el mes actual'],
            ['ventas', 'Órdenes del Mes', 87, 100, 'órdenes', 'up', 12.5, 'Número total de órdenes en el mes'],
            ['ventas', 'Ticket Promedio', 181.03, 200.00, 'USD', 'stable', 2.3, 'Valor promedio por orden'],
            ['usuarios', 'Nuevos Clientes', 24, 30, 'usuarios', 'down', -8.1, 'Nuevos clientes registrados'],
            ['productos', 'Productos Vendidos', 145, 150, 'productos', 'up', 18.7, 'Total de productos vendidos'],
            ['inventario', 'Valor Inventario', 45230.00, 50000.00, 'USD', 'stable', -2.1, 'Valor total del inventario']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO dashboard_kpis (category, kpi_name, kpi_value, kpi_target, kpi_unit, trend, variance_percent, description, period_start, period_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), CURDATE())");
        
        foreach ($sample_kpis as $kpi) {
            $stmt->execute($kpi);
            echo "     ✓ KPI creado: " . $kpi[1] . PHP_EOL;
        }
    }
    
    // Crear métricas diarias de ejemplo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM daily_metrics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $recent_metrics = $stmt->fetch()['total'];
    
    if ($recent_metrics == 0) {
        echo PHP_EOL . "4. Creando métricas diarias de ejemplo..." . PHP_EOL;
        
        $stmt = $pdo->prepare("INSERT INTO daily_metrics (date, total_sales, total_orders, total_visitors, unique_visitors, page_views, conversion_rate, avg_order_value, new_customers, returning_customers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $sales = rand(800, 2500);
            $orders = rand(4, 15);
            $visitors = rand(150, 400);
            $unique_visitors = rand(120, 350);
            $page_views = rand(300, 800);
            $conversion_rate = round(($orders / $visitors) * 100, 2);
            $avg_order_value = round($sales / $orders, 2);
            $new_customers = rand(1, 5);
            $returning_customers = $orders - $new_customers;
            
            $stmt->execute([$date, $sales, $orders, $visitors, $unique_visitors, $page_views, $conversion_rate, $avg_order_value, $new_customers, $returning_customers]);
            echo "     ✓ Métricas creadas para: " . $date . " (Ventas: $" . number_format($sales, 2) . ")" . PHP_EOL;
        }
    }
    
    // Mostrar estadísticas generales
    echo PHP_EOL . "=== ESTADÍSTICAS DEL SISTEMA ===" . PHP_EOL;
    
    // Ventas del mes
    $stmt = $pdo->query("SELECT SUM(total_sales) as monthly_sales FROM daily_metrics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $monthly_sales = $stmt->fetch()['monthly_sales'] ?? 0;
    echo "💰 Ventas del mes: $" . number_format($monthly_sales, 2) . PHP_EOL;
    
    // Órdenes del mes
    $stmt = $pdo->query("SELECT SUM(total_orders) as monthly_orders FROM daily_metrics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $monthly_orders = $stmt->fetch()['monthly_orders'] ?? 0;
    echo "📦 Órdenes del mes: " . $monthly_orders . PHP_EOL;
    
    // Conversión promedio
    $stmt = $pdo->query("SELECT AVG(conversion_rate) as avg_conversion FROM daily_metrics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $avg_conversion = $stmt->fetch()['avg_conversion'] ?? 0;
    echo "📈 Conversión promedio (7 días): " . number_format($avg_conversion, 2) . "%" . PHP_EOL;
    
    // Visitantes únicos
    $stmt = $pdo->query("SELECT SUM(unique_visitors) as weekly_visitors FROM daily_metrics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $weekly_visitors = $stmt->fetch()['weekly_visitors'] ?? 0;
    echo "👥 Visitantes únicos (7 días): " . number_format($weekly_visitors) . PHP_EOL;
    
    echo PHP_EOL . "✅ Sistema de reportes y analíticas completado exitosamente!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>