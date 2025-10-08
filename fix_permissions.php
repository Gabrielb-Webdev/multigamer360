<?php
/**
 * Script para corregir permisos de archivos y directorios
 * Ejecutar una sola vez después del deployment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Corregir Permisos</title></head><body>";
echo "<h1>Corrección de Permisos - MultiGamer360</h1>";

// Directorios que deben ser 755
$directories = [
    'assets',
    'assets/css',
    'assets/js',
    'assets/images',
    'assets/images/icons',
    'assets/images/footer',
    'assets/images/products',
    'assets/images/retro',
    'uploads',
    'uploads/products',
    'uploads/users',
    'logs'
];

echo "<h2>Corrigiendo Permisos de Directorios (755)</h2>";
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (chmod($dir, 0755)) {
            echo "✅ $dir - Permisos actualizados a 755<br>";
        } else {
            echo "❌ $dir - No se pudieron cambiar permisos<br>";
        }
    } else {
        echo "⚠️ $dir - No existe<br>";
    }
}

// Función recursiva para archivos
function fixFilePermissions($directory, &$results) {
    if (!is_dir($directory)) return;
    
    $items = scandir($directory);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $directory . '/' . $item;
        
        if (is_dir($path)) {
            // Directorios: 755
            if (chmod($path, 0755)) {
                $results['dirs_ok']++;
            } else {
                $results['dirs_fail']++;
            }
            fixFilePermissions($path, $results);
        } else {
            // Archivos: 644
            if (chmod($path, 0644)) {
                $results['files_ok']++;
            } else {
                $results['files_fail']++;
            }
        }
    }
}

echo "<h2>Corrigiendo Permisos de Archivos (644)</h2>";
$results = ['files_ok' => 0, 'files_fail' => 0, 'dirs_ok' => 0, 'dirs_fail' => 0];

fixFilePermissions('assets', $results);
fixFilePermissions('uploads', $results);

echo "<p>✅ Archivos corregidos: {$results['files_ok']}</p>";
echo "<p>❌ Archivos fallidos: {$results['files_fail']}</p>";
echo "<p>✅ Directorios corregidos: {$results['dirs_ok']}</p>";
echo "<p>❌ Directorios fallidos: {$results['dirs_fail']}</p>";

echo "<h2>Resumen</h2>";
if ($results['files_fail'] == 0 && $results['dirs_fail'] == 0) {
    echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px;'>";
    echo "<h3>✅ ¡Permisos corregidos exitosamente!</h3>";
    echo "<p>Ahora las imágenes deberían cargar correctamente.</p>";
    echo "<p><a href='index.php' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sitio</a></p>";
    echo "<p><a href='test_images.php' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Verificar Imágenes</a></p>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>⚠️ Algunos permisos no se pudieron cambiar</h3>";
    echo "<p>Es posible que necesites cambiarlos manualmente desde el File Manager de Hostinger.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
