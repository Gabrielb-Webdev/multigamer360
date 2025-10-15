<?php
/**
 * FIX RÁPIDO TEMPORAL - PRODUCTOS
 * 
 * Este script actualiza los productos existentes para asegurar que se muestren
 * Ejecutar UNA SOLA VEZ desde el navegador y luego eliminar
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session_config.php';
require_once 'config/database.php';

echo "<h1>🔧 Fix Rápido - Productos</h1>";
echo "<p>Este script corregirá problemas comunes que impiden mostrar productos.</p>";
echo "<hr>";

try {
    // 1. Verificar y activar todos los productos
    echo "<h2>1. Activando productos...</h2>";
    $stmt = $pdo->query("UPDATE products SET is_active = 1 WHERE is_active IS NULL OR is_active = 0");
    $updated = $stmt->rowCount();
    echo "✅ Productos activados: $updated<br>";
    
    // 2. Verificar que tengan stock
    echo "<h2>2. Asegurando stock mínimo...</h2>";
    $stmt = $pdo->query("UPDATE products SET stock_quantity = 10 WHERE stock_quantity IS NULL OR stock_quantity < 0");
    $updated = $stmt->rowCount();
    echo "✅ Productos con stock actualizado: $updated<br>";
    
    // 3. Verificar console_id (puede ser NULL sin problema con LEFT JOIN)
    echo "<h2>3. Verificando console_id...</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE console_id IS NULL");
    $result = $stmt->fetch();
    $nullConsoles = $result['total'];
    echo "ℹ️ Productos sin consola asignada: $nullConsoles (esto es OK con LEFT JOIN)<br>";
    
    // 4. Verificar categorías inválidas
    echo "<h2>4. Verificando categorías...</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE c.id IS NULL
    ");
    $result = $stmt->fetch();
    $invalidCats = $result['total'];
    
    if ($invalidCats > 0) {
        echo "⚠️ Productos con categoría inválida: $invalidCats<br>";
        
        // Intentar asignar a categoría por defecto (ID 1 o crear una)
        $defaultCat = $pdo->query("SELECT id FROM categories ORDER BY id LIMIT 1")->fetchColumn();
        
        if ($defaultCat) {
            $stmt = $pdo->prepare("UPDATE products p 
                                  LEFT JOIN categories c ON p.category_id = c.id 
                                  SET p.category_id = ? 
                                  WHERE c.id IS NULL");
            $stmt->execute([$defaultCat]);
            echo "✅ Productos movidos a categoría $defaultCat<br>";
        }
    } else {
        echo "✅ Todas las categorías son válidas<br>";
    }
    
    // 5. Verificar marcas inválidas
    echo "<h2>5. Verificando marcas...</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE b.id IS NULL
    ");
    $result = $stmt->fetch();
    $invalidBrands = $result['total'];
    
    if ($invalidBrands > 0) {
        echo "⚠️ Productos con marca inválida: $invalidBrands<br>";
        
        // Intentar asignar a marca por defecto (ID 1 o crear una)
        $defaultBrand = $pdo->query("SELECT id FROM brands ORDER BY id LIMIT 1")->fetchColumn();
        
        if ($defaultBrand) {
            $stmt = $pdo->prepare("UPDATE products p 
                                  LEFT JOIN brands b ON p.brand_id = b.id 
                                  SET p.brand_id = ? 
                                  WHERE b.id IS NULL");
            $stmt->execute([$defaultBrand]);
            echo "✅ Productos movidos a marca $defaultBrand<br>";
        }
    } else {
        echo "✅ Todas las marcas son válidas<br>";
    }
    
    // 6. Test final
    echo "<h2>6. Test Final</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN consoles co ON p.console_id = co.id
        WHERE p.is_active = 1
    ");
    $result = $stmt->fetch();
    $visibleProducts = $result['total'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✅ RESULTADO FINAL</h3>";
    echo "<p style='font-size: 20px; margin: 10px 0;'><strong>Productos visibles ahora: $visibleProducts</strong></p>";
    echo "</div>";
    
    if ($visibleProducts > 0) {
        echo "<p style='font-size: 18px; color: green;'>🎉 ¡ÉXITO! Los productos deberían mostrarse ahora en productos.php</p>";
        echo "<p><a href='productos.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ver Productos</a></p>";
    } else {
        echo "<p style='font-size: 18px; color: red;'>⚠️ Aún no hay productos visibles. Ejecuta diagnostico_productos.php para más detalles.</p>";
        echo "<p><a href='diagnostico_productos.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ejecutar Diagnóstico</a></p>";
    }
    
    // Mostrar algunos productos como ejemplo
    if ($visibleProducts > 0) {
        echo "<h2>7. Productos Encontrados (muestra)</h2>";
        $stmt = $pdo->query("
            SELECT p.id, p.name, c.name as category, b.name as brand, p.stock_quantity, p.price_pesos
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.is_active = 1
            LIMIT 10
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #007bff; color: white;'>";
        echo "<th>ID</th><th>Nombre</th><th>Categoría</th><th>Marca</th><th>Stock</th><th>Precio</th>";
        echo "</tr>";
        
        foreach ($products as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>" . ($p['category'] ?? 'Sin categoría') . "</td>";
            echo "<td>" . ($p['brand'] ?? 'Sin marca') . "</td>";
            echo "<td>{$p['stock_quantity']}</td>";
            echo "<td>\${$p['price_pesos']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p style='color: #888;'><strong>IMPORTANTE:</strong> Este archivo debe eliminarse después de usarlo por seguridad.</p>";
    echo "<p style='color: #888;'>Archivo generado: " . date('Y-m-d H:i:s') . "</p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
