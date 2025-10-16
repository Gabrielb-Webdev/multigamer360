<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Debug: Verificando Producto ID 1</h2>";
echo "<pre>";

// 1. Verificar si el producto ID 1 existe
echo "1. BUSCANDO PRODUCTO ID 1 EN LA BASE DE DATOS:\n";
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = 1");
$stmt->execute();
$product = $stmt->fetch();

if ($product) {
    echo "   ✓ PRODUCTO ENCONTRADO:\n";
    foreach ($product as $key => $value) {
        if (!is_numeric($key)) {
            echo "     - $key: " . ($value ?? 'NULL') . "\n";
        }
    }
} else {
    echo "   ✗ PRODUCTO ID 1 NO EXISTE EN LA BD\n";
    
    // Buscar productos disponibles
    echo "\n2. BUSCANDO OTROS PRODUCTOS:\n";
    $stmt = $pdo->query("SELECT id, name, stock_quantity FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "   Productos disponibles:\n";
        foreach ($products as $p) {
            echo "     - ID: {$p['id']} | {$p['name']} | Stock: {$p['stock_quantity']}\n";
        }
    } else {
        echo "   No hay productos en la base de datos\n";
    }
}

// 2. Verificar estructura de la tabla
echo "\n3. ESTRUCTURA DE LA TABLA PRODUCTS:\n";
$stmt = $pdo->query("DESCRIBE products");
$columns = $stmt->fetchAll();
echo "   Columnas:\n";
foreach ($columns as $col) {
    echo "     - {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Default: " . ($col['Default'] ?? 'NULL') . "\n";
}

// 3. Probar la consulta del CartManager
echo "\n4. PROBANDO CONSULTA DEL CARTMANAGER:\n";
$stmt = $pdo->prepare("
    SELECT id, name, price_pesos as price, stock_quantity as stock
    FROM products 
    WHERE id = ?
");
$stmt->execute([1]);
$result = $stmt->fetch();

if ($result) {
    echo "   ✓ CONSULTA EXITOSA:\n";
    foreach ($result as $key => $value) {
        if (!is_numeric($key)) {
            echo "     - $key: " . ($value ?? 'NULL') . "\n";
        }
    }
} else {
    echo "   ✗ LA CONSULTA NO DEVOLVIÓ RESULTADOS\n";
}

echo "\n</pre>";
?>
