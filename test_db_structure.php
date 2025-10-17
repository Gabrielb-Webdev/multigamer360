<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Estructura de la tabla Products</h1>";

// Describir la tabla
$stmt = $pdo->query("DESCRIBE products");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Columnas de la tabla 'products':</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Mostrar un producto de ejemplo
echo "<h2>Producto ID=1 (raw query):</h2>";
$stmt = $pdo->query("SELECT * FROM products WHERE id = 1");
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    echo "<pre>";
    print_r($product);
    echo "</pre>";
} else {
    echo "No se encontró producto con ID=1<br>";
    
    // Mostrar primer producto disponible
    $stmt = $pdo->query("SELECT * FROM products LIMIT 1");
    $first = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($first) {
        echo "<h3>Primer producto en la tabla:</h3>";
        echo "<pre>";
        print_r($first);
        echo "</pre>";
    }
}

// Verificar is_active
echo "<h2>Valores de is_active:</h2>";
$stmt = $pdo->query("SELECT id, name, is_active FROM products LIMIT 10");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>is_active</th><th>Tipo</th></tr>";
foreach ($products as $p) {
    $type = gettype($p['is_active']);
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['name']}</td>";
    echo "<td>{$p['is_active']}</td>";
    echo "<td>{$type}</td>";
    echo "</tr>";
}
echo "</table>";
?>
