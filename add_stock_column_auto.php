<?php
require_once 'config/database.php';

echo "<h1>Agregar Columna Stock a Products</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; }</style>";

try {
    // Verificar si la columna ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ La columna 'stock' ya existe en la tabla products</p>";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Tipo: " . htmlspecialchars($row['Type']) . "</p>";
        echo "<p>Default: " . htmlspecialchars($row['Default'] ?? 'NULL') . "</p>";
    } else {
        echo "<p>➡️ Agregando columna 'stock' a la tabla products...</p>";
        
        // Agregar columna stock
        $pdo->exec("
            ALTER TABLE `products` 
            ADD COLUMN `stock` INT(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad disponible en inventario' 
            AFTER `price_pesos`
        ");
        
        echo "<p class='success'>✅ Columna 'stock' agregada exitosamente</p>";
        
        // Establecer stock inicial de 10 para todos los productos
        echo "<p>➡️ Estableciendo stock inicial de 10 unidades para todos los productos...</p>";
        $stmt = $pdo->exec("UPDATE `products` SET `stock` = 10 WHERE `stock` = 0");
        echo "<p class='success'>✅ Stock inicial establecido para $stmt productos</p>";
    }
    
    // Mostrar productos con su stock
    echo "<h2>Productos y su stock actual:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th></tr>";
    
    $stmt = $pdo->query("SELECT id, name, price_pesos, stock FROM products LIMIT 20");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stock_class = $row['stock'] > 0 ? 'success' : 'error';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>$" . number_format($row['price_pesos'], 0, ',', '.') . "</td>";
        echo "<td class='$stock_class'><strong>" . $row['stock'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ ¡Listo!</h2>";
    echo "<p>Ahora puedes volver a intentar hacer el pedido.</p>";
    echo "<p><a href='carrito.php' style='display: inline-block; background: #dc262b; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>IR AL CARRITO</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
