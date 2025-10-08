<?php
/**
 * Script para corregir rutas de imágenes en la base de datos
 * Las imágenes deben estar en assets/images/products/ pero están apuntando a la raíz
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Corregir Rutas de Imágenes</title></head><body>";
echo "<h1>Corrección de Rutas de Imágenes en Base de Datos</h1>";

try {
    // 1. Ver el estado actual
    echo "<h2>Estado Actual de Imágenes</h2>";
    $stmt = $pdo->query("SELECT id, name, image FROM products LIMIT 20");
    $products = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Producto</th><th>Ruta Actual</th><th>Estado</th></tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['image']}</td>";
        
        // Verificar si la ruta es incorrecta (empieza con / o sin assets/)
        if (strpos($product['image'], '/') === 0 && strpos($product['image'], '/assets/') !== 0) {
            echo "<td style='color: red;'>❌ Ruta incorrecta</td>";
        } elseif (strpos($product['image'], 'assets/') === false) {
            echo "<td style='color: orange;'>⚠️ Sin prefijo assets/</td>";
        } else {
            echo "<td style='color: green;'>✅ OK</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Corregir rutas
    echo "<h2>Aplicando Correcciones</h2>";
    
    $updates = 0;
    $errors = 0;
    
    // Actualizar imágenes que empiezan con / pero no tienen /assets/
    $stmt = $pdo->prepare("
        UPDATE products 
        SET image = CONCAT('assets/images/products/', SUBSTRING(image, 2))
        WHERE image LIKE '/%' 
        AND image NOT LIKE '/assets/%'
    ");
    
    if ($stmt->execute()) {
        $count = $stmt->rowCount();
        echo "<p>✅ Corregidas $count imágenes con ruta absoluta incorrecta</p>";
        $updates += $count;
    }
    
    // Actualizar imágenes que no tienen prefijo assets/
    $stmt = $pdo->prepare("
        UPDATE products 
        SET image = CONCAT('assets/images/products/', image)
        WHERE image NOT LIKE 'assets/%' 
        AND image NOT LIKE '/%'
        AND image != ''
        AND image IS NOT NULL
    ");
    
    if ($stmt->execute()) {
        $count = $stmt->rowCount();
        echo "<p>✅ Corregidas $count imágenes sin prefijo assets/</p>";
        $updates += $count;
    }
    
    // Normalizar barras duplicadas
    $stmt = $pdo->prepare("
        UPDATE products 
        SET image = REPLACE(image, 'assets//images', 'assets/images')
        WHERE image LIKE '%assets//images%'
    ");
    
    if ($stmt->execute()) {
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "<p>✅ Normalizadas $count rutas con barras duplicadas</p>";
            $updates += $count;
        }
    }
    
    // 3. Ver el estado después de la corrección
    echo "<h2>Estado Después de la Corrección</h2>";
    $stmt = $pdo->query("SELECT id, name, image FROM products LIMIT 20");
    $products = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Producto</th><th>Ruta Corregida</th><th>Existe</th></tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['image']}</td>";
        
        // Verificar si el archivo existe
        if (file_exists($product['image'])) {
            echo "<td style='color: green;'>✅ Existe</td>";
        } else {
            echo "<td style='color: red;'>❌ No existe</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Resumen
    echo "<h2>Resumen</h2>";
    if ($updates > 0) {
        echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px;'>";
        echo "<h3>✅ Correcciones Aplicadas</h3>";
        echo "<p>Se corrigieron <strong>$updates</strong> rutas de imágenes.</p>";
        echo "<p><a href='index.php' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver Sitio</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #d1ecf1; padding: 20px; border-radius: 5px;'>";
        echo "<h3>ℹ️ No se Requirieron Correcciones</h3>";
        echo "<p>Todas las rutas ya son correctas.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
