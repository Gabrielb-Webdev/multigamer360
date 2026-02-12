<?php
/**
 * Script para corregir productos con slug vacío
 * Ejecutar una sola vez desde el navegador: /admin/fix_empty_slugs.php
 */

require_once '../config/database_production.php';

echo "<h2>Corrigiendo productos con slug vacío...</h2>";

try {
    // Buscar productos con slug vacío o NULL
    $stmt = $pdo->query("SELECT id, name, slug FROM products WHERE slug = '' OR slug IS NULL");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Se encontraron " . count($products) . " productos con slug vacío.</p>";
    
    if (count($products) > 0) {
        echo "<ul>";
        
        foreach ($products as $product) {
            $productId = $product['id'];
            $productName = $product['name'];
            
            // Generar slug desde el nombre
            $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productName)));
            $baseSlug = trim($baseSlug, '-');
            
            // Si sigue vacío, usar el ID como fallback
            if (empty($baseSlug)) {
                $baseSlug = 'producto-' . $productId;
            }
            
            // Verificar si el slug existe y hacerlo único
            $slug = $baseSlug;
            $counter = 1;
            while (true) {
                $checkStmt = $pdo->prepare("SELECT id FROM products WHERE slug = :slug AND id != :product_id LIMIT 1");
                $checkStmt->execute([':slug' => $slug, ':product_id' => $productId]);
                if (!$checkStmt->fetch()) {
                    break; // Slug único encontrado
                }
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            // Actualizar el producto con el nuevo slug
            $updateStmt = $pdo->prepare("UPDATE products SET slug = :slug WHERE id = :id");
            $updateStmt->execute([':slug' => $slug, ':id' => $productId]);
            
            echo "<li>Producto ID {$productId} - <strong>{$productName}</strong> → Nuevo slug: <code>{$slug}</code></li>";
        }
        
        echo "</ul>";
        echo "<p style='color: green; font-weight: bold;'>✓ Todos los slugs han sido corregidos.</p>";
    } else {
        echo "<p style='color: green;'>✓ No hay productos con slug vacío.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='products.php'>← Volver a Productos</a></p>";
