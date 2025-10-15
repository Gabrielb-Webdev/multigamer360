<?php
require 'config/database.php';

echo "Productos activos: " . $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = TRUE')->fetchColumn() . PHP_EOL;
echo "Todos los productos: " . $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() . PHP_EOL;

// Ver los valores de is_active
$stmt = $pdo->query('SELECT id, name, is_active FROM products LIMIT 5');
echo "\nPrimeros 5 productos:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} - {$row['name']} - Active: " . ($row['is_active'] ? 'TRUE' : 'FALSE') . "\n";
}
