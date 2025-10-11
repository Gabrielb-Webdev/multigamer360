<?php
// Test de datos del dashboard
header('Content-Type: text/html; charset=utf-8');
require_once '../config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Dashboard</title></head><body>";
echo "<h1>Test de Datos del Dashboard</h1>";

try {
    // Verificar conexión
    echo "<h2>✅ Conexión a Base de Datos: OK</h2>";
    echo "Base de datos conectada correctamente<br><br>";
    
    // Verificar productos
    echo "<h2>📦 Productos</h2>";
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
        FROM products");
    $products = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Total</th><th>Activos</th><th>Inactivos</th><th>Borradores</th></tr>";
    echo "<tr>";
    echo "<td>" . $products['total'] . "</td>";
    echo "<td style='background: lightgreen'>" . $products['active'] . "</td>";
    echo "<td>" . $products['inactive'] . "</td>";
    echo "<td>" . $products['draft'] . "</td>";
    echo "</tr></table><br>";
    
    // Verificar primeros productos
    $stmt = $pdo->query("SELECT id, name, status, stock, price FROM products ORDER BY id LIMIT 5");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Primeros 5 productos:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Status</th><th>Stock</th><th>Precio</th></tr>";
    foreach ($all_products as $p) {
        $color = $p['status'] == 'active' ? 'lightgreen' : ($p['status'] == 'draft' ? 'lightyellow' : 'lightgray');
        echo "<tr style='background: $color'>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . $p['name'] . "</td>";
        echo "<td><strong>" . $p['status'] . "</strong></td>";
        echo "<td>" . $p['stock'] . "</td>";
        echo "<td>$" . $p['price'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Verificar usuarios
    echo "<h2>👥 Usuarios</h2>";
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        FROM users");
    $users = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Total</th><th>Activos</th><th>Inactivos</th></tr>";
    echo "<tr>";
    echo "<td>" . $users['total'] . "</td>";
    echo "<td style='background: lightgreen'>" . $users['active'] . "</td>";
    echo "<td>" . $users['inactive'] . "</td>";
    echo "</tr></table><br>";
    
    // Verificar usuarios específicos
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, role, is_active FROM users LIMIT 5");
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Primeros 5 usuarios:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Activo</th></tr>";
    foreach ($all_users as $u) {
        $color = $u['is_active'] == 1 ? 'lightgreen' : 'lightgray';
        echo "<tr style='background: $color'>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td>" . $u['email'] . "</td>";
        echo "<td>" . $u['first_name'] . " " . $u['last_name'] . "</td>";
        echo "<td><strong>" . $u['role'] . "</strong></td>";
        echo "<td>" . ($u['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Verificar tabla orders
    echo "<h2>🛒 Órdenes</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    $table_exists = $stmt->fetch();
    if ($table_exists) {
        echo "✅ Tabla orders existe<br>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
        $orders = $stmt->fetch();
        echo "<strong>Total órdenes: " . $orders['total'] . "</strong><br>";
        
        if ($orders['total'] > 0) {
            $stmt = $pdo->query("SELECT id, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
            $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Últimas 5 órdenes:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Total</th><th>Estado</th><th>Fecha</th></tr>";
            foreach ($recent_orders as $o) {
                echo "<tr>";
                echo "<td>" . $o['id'] . "</td>";
                echo "<td>$" . $o['total'] . "</td>";
                echo "<td>" . $o['status'] . "</td>";
                echo "<td>" . $o['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "⚠️ <strong>Tabla orders NO existe</strong><br>";
        echo "<em>El dashboard mostrará 0 órdenes hasta que se cree esta tabla</em><br>";
    }
    
    echo "<br><hr><br>";
    echo "<h2>📊 Resumen para Dashboard:</h2>";
    echo "<ul style='font-size: 18px;'>";
    echo "<li><strong>Productos Activos:</strong> " . $products['active'] . "</li>";
    echo "<li><strong>Usuarios Registrados:</strong> " . $users['active'] . "</li>";
    echo "<li><strong>Órdenes Pendientes:</strong> " . (isset($orders) ? $orders['total'] : 0) . "</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 20px; border: 2px solid red;'>";
    echo "<h2>❌ Error de Base de Datos</h2>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode();
    echo "</div>";
}

echo "</body></html>";
?>
