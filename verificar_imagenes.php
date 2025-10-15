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
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.image_url, pi.image_url as primary_image, pi.is_primary
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_active = 1
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Productos y sus rutas de imagen:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th>ID</th><th>Nombre</th><th>image_url (products)</th><th>primary_image (product_images)</th><th>Ruta Usada</th><th>¿Existe?</th><th>Preview</th>";
    echo "</tr>";
    
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    
    foreach ($products as $p) {
        // Priorizar primary_image de la tabla product_images
        $image_filename = !empty($p['primary_image']) ? $p['primary_image'] : 
                         (!empty($p['image_url']) ? $p['image_url'] : '');
        
        $source = !empty($p['primary_image']) ? 'product_images' : 
                 (!empty($p['image_url']) ? 'products' : 'ninguna');
        
        // Posibles rutas
        $paths_to_check = [
            'uploads/products/' . $image_filename,
            'assets/images/products/' . $image_filename,
            'admin/uploads/products/' . $image_filename,
            $image_filename // Ruta directa
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
        echo "<td><code>" . htmlspecialchars($p['image_url'] ?? 'NULL') . "</code></td>";
        echo "<td><code>" . htmlspecialchars($p['primary_image'] ?? 'NULL') . "</code><br><small>Fuente: <strong>$source</strong></small></td>";
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
    
    // Verificar tabla product_images
    echo "<hr>";
    echo "<h2>📸 Tabla product_images:</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_images");
        $total = $stmt->fetchColumn();
        echo "<p><strong>Total de imágenes en product_images:</strong> $total</p>";
        
        if ($total > 0) {
            $stmt = $pdo->query("
                SELECT pi.*, p.name as product_name 
                FROM product_images pi
                LEFT JOIN products p ON pi.product_id = p.id
                ORDER BY pi.product_id, pi.display_order
                LIMIT 20
            ");
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr style='background: #6c757d; color: white;'>";
            echo "<th>ID</th><th>Product ID</th><th>Producto</th><th>image_url</th><th>is_primary</th><th>display_order</th>";
            echo "</tr>";
            
            foreach ($images as $img) {
                $primary_style = $img['is_primary'] ? 'background: #fff3cd;' : '';
                echo "<tr style='$primary_style'>";
                echo "<td>{$img['id']}</td>";
                echo "<td>{$img['product_id']}</td>";
                echo "<td>{$img['product_name']}</td>";
                echo "<td><code>{$img['image_url']}</code></td>";
                echo "<td>" . ($img['is_primary'] ? '⭐ SÍ' : 'No') . "</td>";
                echo "<td>{$img['display_order']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No hay imágenes en la tabla product_images</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
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
