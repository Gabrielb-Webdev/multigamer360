<?php
/**
 * Diagnóstico de productos - verificar qué hay en la BD
 */

require_once 'config/database.php';

echo "<h2>Diagnóstico de Productos</h2>";

// Test 1: Contar todos los productos activos
echo "<h3>Test 1: Total de productos activos</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $total = $stmt->fetchColumn();
    echo "✅ Total de productos activos: <strong>{$total}</strong><br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 2: Ver productos por categoría
echo "<h3>Test 2: Productos por categoría</h3>";
try {
    $stmt = $pdo->query("
        SELECT c.id, c.name, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        GROUP BY c.id, c.name
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Categoría</th><th>Productos</th></tr>";
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td>{$cat['id']}</td>";
        echo "<td>{$cat['name']}</td>";
        echo "<td>{$cat['product_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 3: Ver algunos productos de ejemplo
echo "<h3>Test 3: Primeros 10 productos</h3>";
try {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.price_pesos, c.name as category, p.is_active, p.stock_quantity
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
        LIMIT 10
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Categoría</th><th>Stock</th><th>Activo</th></tr>";
        foreach ($products as $prod) {
            $active_badge = $prod['is_active'] ? '✅' : '❌';
            echo "<tr>";
            echo "<td>{$prod['id']}</td>";
            echo "<td>{$prod['name']}</td>";
            echo "<td>\${$prod['price_pesos']}</td>";
            echo "<td>{$prod['category']}</td>";
            echo "<td>{$prod['stock_quantity']}</td>";
            echo "<td>{$active_badge}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No hay productos en la base de datos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 4: Simular consulta con filtro de categoría 1
echo "<h3>Test 4: Consulta con categoría ID = 1 (como en la URL)</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price_pesos, c.name as category
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND p.category_id = ?
        LIMIT 10
    ");
    $stmt->execute([1]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total encontrados: <strong>" . count($products) . "</strong><br><br>";
    
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Categoría</th></tr>";
        foreach ($products as $prod) {
            echo "<tr>";
            echo "<td>{$prod['id']}</td>";
            echo "<td>{$prod['name']}</td>";
            echo "<td>\${$prod['price_pesos']}</td>";
            echo "<td>{$prod['category']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No hay productos con category_id = 1<br>";
        echo "<p><strong>Posible solución:</strong> Verifica que los productos tengan category_id = 1</p>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><br>";
echo "<a href='productos.php'>Volver a productos</a> | ";
echo "<a href='productos.php?categories=1'>Productos con categoría 1</a>";
?>
