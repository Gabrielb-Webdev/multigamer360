<?php
/**
 * Script de diagnóstico para productos similares
 */

session_start();
require_once 'config/database.php';

echo "<h1>Diagnóstico de Productos Similares</h1>";
echo "<pre>";

// Contar productos activos totales
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$total = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total productos activos: " . $total['total'] . "\n\n";

// Obtener información del producto actual (Kingdom Hearts 3)
$stmt = $pdo->prepare("SELECT id, name, category_id, slug, is_active FROM products WHERE slug = ?");
$stmt->execute(['kingdom-hearts-3']);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if ($current) {
    echo "Producto actual:\n";
    echo "  ID: " . $current['id'] . "\n";
    echo "  Nombre: " . $current['name'] . "\n";
    echo "  Category ID: " . ($current['category_id'] ?? 'NULL') . "\n";
    echo "  Slug: " . $current['slug'] . "\n";
    echo "  Activo: " . ($current['is_active'] ? 'SI' : 'NO') . "\n\n";
    
    // Buscar productos de la misma categoría
    if (!empty($current['category_id'])) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.category_id, p.slug, p.is_active, p.stock_quantity
            FROM products p
            WHERE p.category_id = ? 
            AND p.id != ? 
            AND p.is_active = 1
            LIMIT 10
        ");
        $stmt->execute([$current['category_id'], $current['id']]);
        $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Productos de la misma categoría (" . $current['category_id'] . "):\n";
        echo "  Total encontrados: " . count($similar) . "\n";
        foreach ($similar as $prod) {
            echo "  - ID: {$prod['id']} | {$prod['name']} | Stock: {$prod['stock_quantity']}\n";
        }
    } else {
        echo "⚠️ El producto NO TIENE category_id asignado!\n";
    }
    
    echo "\n";
    
    // Buscar cualquier producto activo (excluyendo el actual)
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.category_id, p.is_active
        FROM products p
        WHERE p.id != ? 
        AND p.is_active = 1
        LIMIT 5
    ");
    $stmt->execute([$current['id']]);
    $any_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Otros productos activos (cualquier categoría):\n";
    echo "  Total encontrados: " . count($any_products) . "\n";
    foreach ($any_products as $prod) {
        echo "  - ID: {$prod['id']} | {$prod['name']} | Cat: {$prod['category_id']}\n";
    }
    
} else {
    echo "⚠️ No se encontró el producto Kingdom Hearts 3\n";
}

echo "\n";

// Listar todas las categorías
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Categorías disponibles:\n";
foreach ($categories as $cat) {
    echo "  - ID: {$cat['id']} | {$cat['name']}\n";
}

echo "</pre>";
?>
