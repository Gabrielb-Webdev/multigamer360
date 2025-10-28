<?php
require_once 'config/database.php';

echo "<h1>Verificación de Estructura de Base de Datos</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #4CAF50; color: white; } .error { background: #ffebee; } .success { background: #e8f5e9; }</style>";

try {
    // Verificar tabla orders
    echo "<h2>Tabla: orders</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ La tabla 'orders' existe</p>";
        
        // Mostrar estructura
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $stmt = $pdo->query("DESCRIBE orders");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ La tabla 'orders' NO existe</p>";
        echo "<p><strong>Acción:</strong> Debes ejecutar el script SQL: <code>create_orders_tables.sql</code></p>";
    }
    
    // Verificar tabla order_items
    echo "<h2>Tabla: order_items</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ La tabla 'order_items' existe</p>";
        
        // Mostrar estructura
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $stmt = $pdo->query("DESCRIBE order_items");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ La tabla 'order_items' NO existe</p>";
        echo "<p><strong>Acción:</strong> Debes ejecutar el script SQL: <code>create_orders_tables.sql</code></p>";
    }
    
    // Verificar tabla products
    echo "<h2>Tabla: products (columna stock)</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ La columna 'stock' existe en products</p>";
        echo "<p>Tipo: " . htmlspecialchars($row['Type']) . "</p>";
    } else {
        echo "<p class='error'>❌ La columna 'stock' NO existe en products</p>";
        echo "<p><strong>Acción:</strong> Debes agregar la columna stock a la tabla products</p>";
    }
    
    // Verificar tabla coupon_usage
    echo "<h2>Tabla: coupon_usage</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'coupon_usage'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ La tabla 'coupon_usage' existe</p>";
        
        // Verificar columnas específicas
        $stmt = $pdo->query("SHOW COLUMNS FROM coupon_usage LIKE 'order_id'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ La columna 'order_id' existe en coupon_usage</p>";
        } else {
            echo "<p class='error'>❌ La columna 'order_id' NO existe en coupon_usage (no crítico)</p>";
        }
    } else {
        echo "<p class='error'>❌ La tabla 'coupon_usage' NO existe (no crítico si no usas cupones)</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
