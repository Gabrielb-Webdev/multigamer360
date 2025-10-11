<?php
/**
 * Diagnóstico del Dashboard - MultiGamer360
 * Este archivo ayuda a identificar por qué el dashboard no muestra datos
 */

// Configurar para mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; background: white; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico del Dashboard - MultiGamer360</h1>
    <p><strong>Fecha:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <hr>

<?php
// Paso 1: Verificar archivo de configuración
echo "<h2>📁 Paso 1: Verificar Conexión a Base de Datos</h2>";
try {
    require_once '../config/database.php';
    echo "<div class='success'>✅ Archivo de configuración cargado correctamente</div>";
    
    if (isset($pdo)) {
        echo "<div class='success'>✅ Variable \$pdo existe</div>";
        
        // Verificar conexión
        $pdo->query("SELECT 1");
        echo "<div class='success'>✅ Conexión a base de datos funcionando</div>";
        
        // Obtener nombre de la base de datos
        $stmt = $pdo->query("SELECT DATABASE() as dbname");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='info'>ℹ️ Base de datos conectada: <code>{$db_info['dbname']}</code></div>";
        
    } else {
        echo "<div class='error'>❌ Variable \$pdo no existe</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error al conectar: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Paso 2: Verificar tablas
echo "<h2>📊 Paso 2: Verificar Tablas en la Base de Datos</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_tables = ['products', 'users', 'orders'];
    echo "<table>";
    echo "<tr><th>Tabla</th><th>Existe</th><th>Registros</th></tr>";
    
    foreach ($required_tables as $table) {
        $exists = in_array($table, $tables);
        $count = 0;
        
        if ($exists) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['total'];
                echo "<tr><td><strong>$table</strong></td><td style='background: #d4edda;'>✅ SÍ</td><td>$count</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td><strong>$table</strong></td><td style='background: #d4edda;'>✅ SÍ</td><td style='background: #f8d7da;'>Error al contar</td></tr>";
            }
        } else {
            echo "<tr><td><strong>$table</strong></td><td style='background: #f8d7da;'>❌ NO</td><td>-</td></tr>";
        }
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error al verificar tablas: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Paso 3: Verificar PRODUCTOS
echo "<h2>📦 Paso 3: Análisis de Productos</h2>";
try {
    // Contar por estado (is_active)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        FROM products
    ");
    $product_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
    echo "<tr><td>Total</td><td><strong>{$product_stats['total']}</strong></td></tr>";
    echo "<tr style='background: #d4edda;'><td>Activos (is_active=1)</td><td><strong>{$product_stats['active']}</strong></td></tr>";
    echo "<tr><td>Inactivos (is_active=0)</td><td>{$product_stats['inactive']}</td></tr>";
    echo "</table>";
    
    if ($product_stats['total'] > 0) {
        echo "<div class='success'>✅ Hay {$product_stats['total']} productos en la base de datos</div>";
        
        // Mostrar algunos productos
        $stmt = $pdo->query("SELECT id, name, is_active, stock_quantity, price FROM products ORDER BY id DESC LIMIT 5");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Últimos 5 productos:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Activo</th><th>Stock</th><th>Precio</th></tr>";
        foreach ($products as $p) {
            $bg = $p['is_active'] == 1 ? 'background: #d4edda;' : '';
            $active_text = $p['is_active'] == 1 ? '✅ Activo' : '❌ Inactivo';
            echo "<tr style='$bg'>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td><strong>{$active_text}</strong></td>";
            echo "<td>{$p['stock_quantity']}</td>";
            echo "<td>\${$p['price']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No hay productos en la base de datos</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error al consultar productos: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Paso 4: Verificar USUARIOS
echo "<h2>👥 Paso 4: Análisis de Usuarios</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        FROM users
    ");
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
    echo "<tr><td>Total</td><td><strong>{$user_stats['total']}</strong></td></tr>";
    echo "<tr style='background: #d4edda;'><td>Activos</td><td><strong>{$user_stats['active']}</strong></td></tr>";
    echo "<tr><td>Inactivos</td><td>{$user_stats['inactive']}</td></tr>";
    echo "</table>";
    
    if ($user_stats['total'] > 0) {
        echo "<div class='success'>✅ Hay {$user_stats['total']} usuarios en la base de datos</div>";
    } else {
        echo "<div class='warning'>⚠️ No hay usuarios en la base de datos</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error al consultar usuarios: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Paso 5: Probar las consultas exactas del dashboard
echo "<h2>🎯 Paso 5: Probar Consultas Exactas del Dashboard</h2>";

echo "<h3>Consulta 1: Productos Activos</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_products = (int)($result['total'] ?? 0);
    
    echo "<div class='info'>📊 Resultado: <strong>$total_products</strong> productos activos</div>";
    echo "<code>SELECT COUNT(*) as total FROM products WHERE is_active = 1</code>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>Consulta 2: Usuarios Registrados</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_users = (int)($result['total'] ?? 0);
    
    echo "<div class='info'>📊 Resultado: <strong>$total_users</strong> usuarios activos</div>";
    echo "<code>SELECT COUNT(*) as total FROM users WHERE is_active = 1</code>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>Consulta 3: Órdenes Pendientes</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_orders = (int)($result['total'] ?? 0);
    
    echo "<div class='info'>📊 Resultado: <strong>$pending_orders</strong> órdenes pendientes</div>";
    echo "<code>SELECT COUNT(*) as total FROM orders WHERE status = 'pending'</code>";
} catch (Exception $e) {
    echo "<div class='warning'>⚠️ Tabla 'orders' no existe o error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Esto es normal si aún no has creado la tabla de órdenes.</p>";
}

// Paso 6: Resumen Final
echo "<h2>📋 Paso 6: Resumen Final</h2>";

$summary_data = [
    'Productos Activos' => $total_products ?? 0,
    'Usuarios Registrados' => $total_users ?? 0,
    'Órdenes Pendientes' => $pending_orders ?? 0
];

echo "<table>";
echo "<tr><th>Métrica</th><th>Valor</th><th>Estado</th></tr>";
foreach ($summary_data as $label => $value) {
    $status = $value > 0 ? "✅ OK" : "⚠️ Sin datos";
    $bg = $value > 0 ? "background: #d4edda;" : "background: #fff3cd;";
    echo "<tr style='$bg'><td><strong>$label</strong></td><td><strong>$value</strong></td><td>$status</td></tr>";
}
echo "</table>";

// Verificar cache de PHP
echo "<h2>🔄 Paso 7: Verificar Cache</h2>";
if (function_exists('opcache_get_status')) {
    $opcache_status = opcache_get_status();
    if ($opcache_status && $opcache_status['opcache_enabled']) {
        echo "<div class='warning'>⚠️ OPcache está habilitado. Si hiciste cambios recientes en el código, puede estar sirviendo una versión en caché.</div>";
        echo "<p><strong>Solución:</strong> Limpia el cache de OPcache desde el panel de hosting o espera a que expire.</p>";
    } else {
        echo "<div class='success'>✅ OPcache está deshabilitado o no activo</div>";
    }
} else {
    echo "<div class='info'>ℹ️ No se puede verificar el estado de OPcache</div>";
}

echo "<hr>";
echo "<h2>✅ Diagnóstico Completado</h2>";
echo "<p>Si ves datos aquí pero no en el dashboard, el problema puede ser:</p>";
echo "<ol>";
echo "<li>Cache del navegador (presiona Ctrl+Shift+R para refrescar)</li>";
echo "<li>Cache del servidor (OPcache)</li>";
echo "<li>Error en el archivo index.php del dashboard</li>";
echo "<li>Problema con los includes (auth.php o header.php)</li>";
echo "</ol>";

?>
</body>
</html>
