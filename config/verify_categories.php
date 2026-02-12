<?php
require_once 'database.php';

try {
    // Verificar categorías
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM categories');
    $count = $stmt->fetch()['total'];
    echo "Total de categorías: " . $count . PHP_EOL;
    
    // Mostrar categorías principales
    $stmt = $pdo->query('SELECT name, slug, icon_class FROM categories WHERE parent_id IS NULL LIMIT 5');
    echo "Categorías principales:" . PHP_EOL;
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . " (" . $row['slug'] . ") - Icono: " . $row['icon_class'] . PHP_EOL;
    }
    
    // Verificar marcas
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM brands');
    $count = $stmt->fetch()['total'];
    echo PHP_EOL . "Total de marcas: " . $count . PHP_EOL;
    
    // Mostrar marcas
    $stmt = $pdo->query('SELECT name, slug FROM brands LIMIT 5');
    echo "Marcas principales:" . PHP_EOL;
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . " (" . $row['slug'] . ")" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>