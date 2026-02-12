<?php
/**
 * SCRIPT DE DEBUG - VERIFICAR PRODUCTOS DESTACADOS Y NOVEDADES
 * Ejecutar en: https://teal-fish-507993.hostingersite.com/debug_productos.php
 */

require_once 'config/database.php';
require_once 'includes/product_manager.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug - Productos Destacados y Novedades</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #c41e3a; }
        h2 { color: #333; border-bottom: 2px solid #c41e3a; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #c41e3a; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge { padding: 3px 8px; border-radius: 3px; color: white; font-size: 12px; }
        .badge-yes { background: green; }
        .badge-no { background: gray; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug - Productos Destacados y Novedades</h1>
        
        <?php
        try {
            // Verificar columnas en la tabla products
            echo "<h2>1. Verificar Estructura de la Tabla</h2>";
            $columns = $pdo->query("SHOW COLUMNS FROM products LIKE '%is_%'")->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Columna</th><th>Tipo</th><th>Estado</th></tr>";
            
            $has_featured = false;
            $has_new = false;
            $has_active = false;
            
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                
                if ($col['Field'] == 'is_featured') {
                    $has_featured = true;
                    echo "<td><span class='success'>✓ Existe</span></td>";
                } elseif ($col['Field'] == 'is_new') {
                    $has_new = true;
                    echo "<td><span class='success'>✓ Existe</span></td>";
                } elseif ($col['Field'] == 'is_active') {
                    $has_active = true;
                    echo "<td><span class='success'>✓ Existe</span></td>";
                } else {
                    echo "<td><span class='badge badge-yes'>OK</span></td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            if (!$has_featured) {
                echo "<p class='error'>✗ ERROR: Falta columna is_featured</p>";
            }
            if (!$has_new) {
                echo "<p class='error'>✗ ERROR: Falta columna is_new</p>";
            }
            if (!$has_active) {
                echo "<p class='error'>✗ ERROR: Falta columna is_active</p>";
            }
            
            // Verificar productos destacados
            echo "<h2>2. Productos Destacados (is_featured = 1)</h2>";
            $featured = $pdo->query("
                SELECT p.id, p.name, p.is_featured, p.is_new, p.is_active, p.stock_quantity,
                       pi.image_url as primary_image
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_featured = 1
            ")->fetchAll();
            
            if (empty($featured)) {
                echo "<p class='warning'>⚠ No hay productos marcados como destacados (is_featured = 1)</p>";
            } else {
                echo "<p class='success'>✓ Encontrados: " . count($featured) . " productos destacados</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Destacado</th><th>Novedad</th><th>Activo</th><th>Stock</th><th>Imagen</th></tr>";
                foreach ($featured as $p) {
                    echo "<tr>";
                    echo "<td>{$p['id']}</td>";
                    echo "<td>{$p['name']}</td>";
                    echo "<td><span class='badge badge-" . ($p['is_featured'] ? 'yes' : 'no') . "'>" . ($p['is_featured'] ? 'SÍ' : 'NO') . "</span></td>";
                    echo "<td><span class='badge badge-" . ($p['is_new'] ? 'yes' : 'no') . "'>" . ($p['is_new'] ? 'SÍ' : 'NO') . "</span></td>";
                    echo "<td><span class='badge badge-" . ($p['is_active'] ? 'yes' : 'no') . "'>" . ($p['is_active'] ? 'SÍ' : 'NO') . "</span></td>";
                    echo "<td>{$p['stock_quantity']}</td>";
                    echo "<td>" . ($p['primary_image'] ? "✓ {$p['primary_image']}" : "✗ Sin imagen") . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Verificar productos nuevos
            echo "<h2>3. Productos Nuevos/Novedades (is_new = 1)</h2>";
            $new_products = $pdo->query("
                SELECT p.id, p.name, p.is_featured, p.is_new, p.is_active, p.stock_quantity,
                       pi.image_url as primary_image
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_new = 1
            ")->fetchAll();
            
            if (empty($new_products)) {
                echo "<p class='warning'>⚠ No hay productos marcados como novedades (is_new = 1)</p>";
            } else {
                echo "<p class='success'>✓ Encontrados: " . count($new_products) . " productos nuevos</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Destacado</th><th>Novedad</th><th>Activo</th><th>Stock</th><th>Imagen</th></tr>";
                foreach ($new_products as $p) {
                    echo "<tr>";
                    echo "<td>{$p['id']}</td>";
                    echo "<td>{$p['name']}</td>";
                    echo "<td><span class='badge badge-" . ($p['is_featured'] ? 'yes' : 'no') . "'>" . ($p['is_featured'] ? 'SÍ' : 'NO') . "</span></td>";
                    echo "<td><span class='badge badge-" . ($p['is_new'] ? 'yes' : 'no') . "'>" . ($p['is_new'] ? 'SÍ' : 'NO') . "</span></td>";
                    echo "<td><span class='badge badge-" . ($p['is_active'] ? 'yes' : 'no') . "'>" . ($p['is_active'] ? 'SÍ' : 'NO') . "</span></td>";
                    echo "<td>{$p['stock_quantity']}</td>";
                    echo "<td>" . ($p['primary_image'] ? "✓ {$p['primary_image']}" : "✗ Sin imagen") . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Probar ProductManager
            echo "<h2>4. Probar ProductManager</h2>";
            $productManager = new ProductManager($pdo);
            
            echo "<h3>getFeaturedProducts(8):</h3>";
            $featured_test = $productManager->getFeaturedProducts(8);
            echo "<pre>";
            echo "Cantidad devuelta: " . count($featured_test) . "\n";
            if (!empty($featured_test)) {
                foreach ($featured_test as $p) {
                    echo "- ID: {$p['id']}, Nombre: {$p['name']}, Imagen: " . ($p['primary_image'] ?? 'N/A') . "\n";
                }
            } else {
                echo "✗ Array vacío\n";
            }
            echo "</pre>";
            
            echo "<h3>getNewProducts(8):</h3>";
            $new_test = $productManager->getNewProducts(8);
            echo "<pre>";
            echo "Cantidad devuelta: " . count($new_test) . "\n";
            if (!empty($new_test)) {
                foreach ($new_test as $p) {
                    echo "- ID: {$p['id']}, Nombre: {$p['name']}, Imagen: " . ($p['primary_image'] ?? 'N/A') . "\n";
                }
            } else {
                echo "✗ Array vacío\n";
            }
            echo "</pre>";
            
            // Recomendaciones
            echo "<h2>5. Recomendaciones</h2>";
            echo "<ul>";
            
            if (empty($featured) && empty($new_products)) {
                echo "<li class='error'>⚠️ <strong>PROBLEMA:</strong> No hay productos marcados como destacados NI como novedades</li>";
                echo "<li>✅ <strong>SOLUCIÓN:</strong> Ve a phpMyAdmin y ejecuta:</li>";
                echo "<pre>UPDATE products SET is_featured = 1, is_new = 1 WHERE id = 3;</pre>";
            } else {
                if (!empty($featured)) {
                    $inactive_featured = array_filter($featured, function($p) { return !$p['is_active']; });
                    if (!empty($inactive_featured)) {
                        echo "<li class='warning'>⚠️ Hay productos destacados pero inactivos (is_active = 0)</li>";
                    }
                }
                if (!empty($new_products)) {
                    $inactive_new = array_filter($new_products, function($p) { return !$p['is_active']; });
                    if (!empty($inactive_new)) {
                        echo "<li class='warning'>⚠️ Hay productos nuevos pero inactivos (is_active = 0)</li>";
                    }
                }
            }
            
            echo "<li>✅ Asegúrate de que el archivo <code>index.php</code> esté actualizado en el servidor</li>";
            echo "<li>✅ Asegúrate de que el archivo <code>includes/product_manager.php</code> esté actualizado</li>";
            echo "<li>✅ Limpia la caché del navegador (Ctrl+Shift+R)</li>";
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h2>❌ Error</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><a href="index.php" style="color: #c41e3a;">← Volver al home</a></p>
        <p><em>Este archivo debe eliminarse después de la verificación por seguridad.</em></p>
    </div>
</body>
</html>
