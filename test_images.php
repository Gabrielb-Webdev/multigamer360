<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test de Imágenes</title></head><body>";
echo "<h1>Verificación de Imágenes y Permisos</h1>";

// Lista de imágenes críticas
$critical_images = [
    'assets/images/logo.png',
    'assets/images/Pre-header.png',
    'assets/images/contacto.jpg',
    'assets/images/icons/billete-de-banco.png',
    'assets/images/icons/enviar.png',
    'assets/images/icons/juego.png',
    'assets/images/footer/logo-footer.png',
    'assets/images/footer/footer.png',
    'assets/images/products/product1.jpg'
];

echo "<h2>Estado de Archivos de Imágenes</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Archivo</th><th>Existe</th><th>Permisos</th><th>Tamaño</th><th>Test</th></tr>";

foreach ($critical_images as $image) {
    echo "<tr>";
    echo "<td>$image</td>";
    
    if (file_exists($image)) {
        echo "<td style='color: green;'>✅ Existe</td>";
        
        // Permisos
        $perms = substr(sprintf('%o', fileperms($image)), -4);
        $readable = is_readable($image) ? '✅' : '❌';
        echo "<td>$perms $readable</td>";
        
        // Tamaño
        $size = filesize($image);
        echo "<td>" . number_format($size) . " bytes</td>";
        
        // Test de imagen
        echo "<td><img src='$image' style='max-width:100px; max-height:50px;' onerror=\"this.parentElement.innerHTML='❌ Error'\" /></td>";
    } else {
        echo "<td style='color: red;'>❌ NO existe</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        echo "<td>❌</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Verificar directorios
echo "<h2>Estado de Directorios</h2>";
$directories = [
    'assets',
    'assets/images',
    'assets/images/icons',
    'assets/images/footer',
    'assets/images/products'
];

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Directorio</th><th>Existe</th><th>Permisos</th><th>Contenido</th></tr>";

foreach ($directories as $dir) {
    echo "<tr>";
    echo "<td>$dir</td>";
    
    if (is_dir($dir)) {
        echo "<td style='color: green;'>✅ Existe</td>";
        
        // Permisos
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $readable = is_readable($dir) ? '✅' : '❌';
        echo "<td>$perms $readable</td>";
        
        // Contenido
        $files = scandir($dir);
        $count = count($files) - 2; // Excluir . y ..
        echo "<td>$count archivos/carpetas</td>";
    } else {
        echo "<td style='color: red;'>❌ NO existe</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<h2>Acciones Recomendadas</h2>";
echo "<ul>";
echo "<li>Si los archivos NO existen: Necesitas subirlos al servidor</li>";
echo "<li>Si los archivos existen pero no se cargan: Verifica permisos (deben ser 644 para archivos, 755 para directorios)</li>";
echo "<li>Si todo está OK aquí pero falla en el sitio: Revisa el .htaccess</li>";
echo "</ul>";

echo "</body></html>";
?>
