<?php
/**
 * Script de verificación de estructura de uploads
 * Ejecutar desde: https://teal-fish-507993.hostingersite.com/check_uploads.php
 */

echo "<h2>Verificación de estructura de uploads</h2>";

// 1. Verificar DOCUMENT_ROOT
echo "<h3>1. Document Root</h3>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current script: " . __FILE__ . "<br>";
echo "Directory: " . __DIR__ . "<br><br>";

// 2. Verificar carpeta uploads
echo "<h3>2. Carpeta uploads/</h3>";
$uploads_path = __DIR__ . '/uploads';
echo "Path: $uploads_path<br>";
echo "Existe: " . (file_exists($uploads_path) ? "✅ SÍ" : "❌ NO") . "<br>";
echo "Es carpeta: " . (is_dir($uploads_path) ? "✅ SÍ" : "❌ NO") . "<br>";
echo "Permisos: " . (file_exists($uploads_path) ? substr(sprintf('%o', fileperms($uploads_path)), -4) : "N/A") . "<br><br>";

// 3. Verificar carpeta uploads/products
echo "<h3>3. Carpeta uploads/products/</h3>";
$products_path = __DIR__ . '/uploads/products';
echo "Path: $products_path<br>";
echo "Existe: " . (file_exists($products_path) ? "✅ SÍ" : "❌ NO") . "<br>";
echo "Es carpeta: " . (is_dir($products_path) ? "✅ SÍ" : "❌ NO") . "<br>";
echo "Permisos: " . (file_exists($products_path) ? substr(sprintf('%o', fileperms($products_path)), -4) : "N/A") . "<br><br>";

// 4. Listar archivos en uploads/products si existe
if (is_dir($products_path)) {
    echo "<h3>4. Archivos en uploads/products/</h3>";
    $files = scandir($products_path);
    echo "Total archivos: " . count($files) . "<br>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $products_path . '/' . $file;
            $size = filesize($file_path);
            echo "<li>$file (" . round($size / 1024, 2) . " KB)</li>";
        }
    }
    echo "</ul><br>";
} else {
    echo "<h3>4. Archivos en uploads/products/</h3>";
    echo "⚠️ La carpeta no existe<br><br>";
}

// 5. Verificar consulta a BD para ver qué imágenes espera
echo "<h3>5. Imágenes en base de datos</h3>";
try {
    require_once 'config/database.php';
    $stmt = $pdo->query("SELECT id, product_id, image_url, is_primary FROM product_images ORDER BY product_id LIMIT 20");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total registros en product_images: " . count($images) . "<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Image URL</th><th>Primary</th><th>Existe</th></tr>";
    foreach ($images as $img) {
        $file_exists = file_exists($products_path . '/' . $img['image_url']);
        echo "<tr>";
        echo "<td>" . $img['id'] . "</td>";
        echo "<td>" . $img['product_id'] . "</td>";
        echo "<td>" . $img['image_url'] . "</td>";
        echo "<td>" . ($img['is_primary'] ? '✅' : '') . "</td>";
        echo "<td>" . ($file_exists ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error al consultar BD: " . $e->getMessage();
}

echo "<hr>";
echo "<p><small>Ejecutado: " . date('Y-m-d H:i:s') . "</small></p>";
?>
