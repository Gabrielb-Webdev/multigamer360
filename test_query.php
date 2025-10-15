<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/session_config.php';
require 'config/database.php';
require 'includes/product_manager.php';

$pm = new ProductManager($pdo);
$products = $pm->getProducts([]);

echo "Productos encontrados: " . count($products) . "\n";

if (!empty($products)) {
    echo "\nPrimeros productos:\n";
    foreach (array_slice($products, 0, 3) as $p) {
        echo "- {$p['name']} (ID: {$p['id']})\n";
    }
}
