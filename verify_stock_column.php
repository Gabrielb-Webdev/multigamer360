<?php
require_once 'config/database.php';

echo "<h2>Verificación de Columna Stock</h2>";

try {
    // Verificar estructura de la tabla products
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Columnas en tabla 'products':</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    
    $stock_exists = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'stock') {
            $stock_exists = true;
        }
    }
    echo "</table>";
    
    if ($stock_exists) {
        echo "<p style='color: green; font-weight: bold;'>✅ La columna 'stock' EXISTE</p>";
        
        // Mostrar algunos productos con su stock
        echo "<h3>Productos y su stock actual:</h3>";
        $stmt = $pdo->query("SELECT id, name, stock FROM products LIMIT 10");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Stock</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td><strong>" . ($product['stock'] ?? 'NULL') . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ La columna 'stock' NO EXISTE</p>";
        echo "<p>Necesitas ejecutar el script para agregar la columna stock.</p>";
    }
    
    // Verificar última orden
    echo "<h3>Última orden procesada:</h3>";
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1");
    $last_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_order) {
        echo "<pre>";
        print_r($last_order);
        echo "</pre>";
        
        echo "<h4>Items de esta orden:</h4>";
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$last_order['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Product ID</th><th>Nombre</th><th>Cantidad</th><th>Precio</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>{$item['product_id']}</td>";
            echo "<td>{$item['product_name']}</td>";
            echo "<td><strong>{$item['quantity']}</strong></td>";
            echo "<td>\${$item['price']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
