<?php
/**
 * VERIFICAR IMÁGENES DE PRODUCTOS
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session_config.php';
require_once 'config/database.php';

echo "<h1>🖼️ Verificación de Imágenes de Productos</h1>";
echo "<hr>";

try {
    $stmt = $pdo->query("SELECT id, name, image_url FROM products WHERE is_active = 1");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Productos y sus rutas de imagen:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th>ID</th><th>Nombre</th><th>image_url (DB)</th><th>Ruta Completa</th><th>¿Existe?</th><th>Preview</th>";
    echo "</tr>";
    
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    
    foreach ($products as $p) {
        $image_url = $p['image_url'] ?? '';
        
        // Posibles rutas
        $paths_to_check = [
            'uploads/products/' . $image_url,
            'assets/images/products/' . $image_url,
            'admin/uploads/products/' . $image_url,
            $image_url // Ruta directa
        ];
        
        $found = false;
        $working_path = '';
        
        foreach ($paths_to_check as $path) {
            $full_server_path = $doc_root . '/' . ltrim($path, '/');
            if (file_exists($full_server_path)) {
                $found = true;
                $working_path = $path;
                break;
            }
        }
        
        $status_color = $found ? '#d4edda' : '#f8d7da';
        $status_text = $found ? '✅ Existe' : '❌ No encontrado';
        
        echo "<tr style='background: $status_color;'>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['name']}</td>";
        echo "<td><code>" . htmlspecialchars($image_url) . "</code></td>";
        echo "<td><code>" . htmlspecialchars($working_path ?: 'N/A') . "</code></td>";
        echo "<td><strong>$status_text</strong></td>";
        echo "<td>";
        if ($found) {
            echo "<img src='$working_path' style='max-width: 100px; max-height: 100px;' alt='Preview'>";
        } else {
            echo "⚠️ Sin imagen";
        }
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Información adicional
    echo "<hr>";
    echo "<h2>📁 Información del Sistema de Archivos:</h2>";
    echo "<p><strong>Document Root:</strong> <code>$doc_root</code></p>";
    
    // Verificar carpetas
    $folders_to_check = [
        'uploads/products/',
        'assets/images/products/',
        'admin/uploads/products/'
    ];
    
    echo "<h3>Carpetas disponibles:</h3>";
    echo "<ul>";
    foreach ($folders_to_check as $folder) {
        $full_path = $doc_root . '/' . $folder;
        $exists = is_dir($full_path);
        $status = $exists ? '✅ Existe' : '❌ No existe';
        echo "<li><code>$folder</code> - $status";
        
        if ($exists) {
            $files = scandir($full_path);
            $image_files = array_filter($files, function($f) {
                return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
            });
            echo " (" . count($image_files) . " imágenes encontradas)";
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Recomendaciones
    echo "<hr>";
    echo "<h2>💡 Recomendaciones:</h2>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    
    $all_found = true;
    foreach ($products as $p) {
        $image_url = $p['image_url'] ?? '';
        $test_path = $doc_root . '/uploads/products/' . $image_url;
        if (!file_exists($test_path)) {
            $all_found = false;
            break;
        }
    }
    
    if ($all_found) {
        echo "<p>✅ <strong>Todas las imágenes están disponibles.</strong></p>";
        echo "<p>Las rutas en productos.php están correctas.</p>";
    } else {
        echo "<p>⚠️ <strong>Algunas imágenes no se encuentran.</strong></p>";
        echo "<p><strong>Opciones:</strong></p>";
        echo "<ol>";
        echo "<li>Sube las imágenes faltantes a <code>uploads/products/</code></li>";
        echo "<li>O actualiza el campo <code>image_url</code> en la base de datos con las rutas correctas</li>";
        echo "<li>O usa la imagen por defecto en <code>assets/images/products/product1.jpg</code></li>";
        echo "</ol>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Verificación ejecutada: " . date('Y-m-d H:i:s') . "</p>";
?>
