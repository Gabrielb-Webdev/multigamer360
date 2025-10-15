<?php
/**
 * TEST RÁPIDO - Verificar que ProductManager funciona correctamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session_config.php';
require_once 'config/database.php';
require_once 'includes/product_manager.php';

echo "<h1>✅ Test de ProductManager - Verificación de Corrección</h1>";
echo "<hr>";

try {
    $productManager = new ProductManager($pdo);
    
    // Test 1: Sin filtros
    echo "<h2>Test 1: getProducts() sin filtros</h2>";
    $products = $productManager->getProducts();
    echo "✅ Productos obtenidos: <strong>" . count($products) . "</strong><br>";
    
    if (count($products) > 0) {
        echo "<p style='color: green; font-size: 18px;'>🎉 ¡ÉXITO! ProductManager funciona correctamente</p>";
        
        echo "<h3>Productos encontrados:</h3>";
        echo "<ul>";
        foreach ($products as $p) {
            echo "<li>{$p['name']} - \${$p['price_pesos']} - Stock: {$p['stock_quantity']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No se encontraron productos</p>";
    }
    
    // Test 2: Con filtros de paginación
    echo "<hr>";
    echo "<h2>Test 2: getProducts() con paginación</h2>";
    $filters = [
        'limit' => 20,
        'offset' => 0,
        'order_by' => 'p.created_at DESC'
    ];
    
    $products2 = $productManager->getProducts($filters);
    $count = $productManager->countProducts($filters);
    
    echo "✅ Productos obtenidos: <strong>" . count($products2) . "</strong><br>";
    echo "✅ Total count: <strong>" . $count . "</strong><br>";
    
    if (count($products2) > 0) {
        echo "<p style='color: green; font-size: 18px;'>🎉 ¡PERFECTO! La paginación funciona correctamente</p>";
    }
    
    // Test 3: Comparación
    echo "<hr>";
    echo "<h2>Test 3: Comparación Final</h2>";
    
    if (count($products) === count($products2) && count($products2) === $count) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ TODAS LAS PRUEBAS PASADAS</h3>";
        echo "<p><strong>ProductManager está funcionando correctamente.</strong></p>";
        echo "<p>Los productos deberían aparecer ahora en <code>productos.php</code></p>";
        echo "</div>";
        
        echo "<br>";
        echo "<a href='productos.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px; display: inline-block;'>🎮 Ver Productos</a>";
    } else {
        echo "<p style='color: red;'>⚠️ Hay inconsistencias en los resultados</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ ERROR</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='color: #888; text-align: center;'>Test ejecutado: " . date('Y-m-d H:i:s') . "</p>";
?>
