<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificación de Archivos del Servidor</h1>";
echo "<p>Directorio actual: " . getcwd() . "</p>";
echo "<p>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Lista de archivos críticos que deben existir
$critical_files = [
    // Configuración
    'config/database.php',
    'config/user_manager.php',
    'config/session_config.php',

    // Includes
    'includes/auth.php',
    'includes/functions.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/product_manager.php',
    'includes/cart_manager.php',
    'includes/order_manager.php',

    // Assets CSS
    'assets/css/style.css',
    'assets/css/cart-button-modern.css',

    // Assets JS
    'assets/js/main.js',
    'assets/js/cart-system-advanced.js',

    // Imágenes
    'assets/images/logo.png',
    'assets/images/Pre-header.png',
];

echo "<h2>Archivos Críticos</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Archivo</th><th>Estado</th><th>Ruta Completa</th></tr>";

$missing_files = [];
$found_files = [];

foreach ($critical_files as $file) {
    $exists = file_exists($file);
    $status = $exists ? "✓ EXISTE" : "✗ FALTA";
    $color = $exists ? "green" : "red";
    $full_path = realpath($file) ?: getcwd() . '/' . $file;

    echo "<tr>";
    echo "<td>$file</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "<td style='font-size: 10px;'>$full_path</td>";
    echo "</tr>";

    if (!$exists) {
        $missing_files[] = $file;
    } else {
        $found_files[] = $file;
    }
}

echo "</table>";

echo "<h2>Resumen</h2>";
echo "<p style='color: green;'><strong>Archivos encontrados:</strong> " . count($found_files) . "</p>";
echo "<p style='color: red;'><strong>Archivos faltantes:</strong> " . count($missing_files) . "</p>";

if (count($missing_files) > 0) {
    echo "<h3 style='color: red;'>⚠️ ARCHIVOS QUE FALTAN:</h3>";
    echo "<ul>";
    foreach ($missing_files as $file) {
        echo "<li><code>$file</code></li>";
    }
    echo "</ul>";
    echo "<p><strong>ACCIÓN REQUERIDA:</strong> Debes subir estos archivos al servidor de Hostinger.</p>";
}

// Verificar directorios
echo "<h2>Directorios</h2>";
$directories = ['config', 'includes', 'assets', 'assets/css', 'assets/js', 'assets/images', 'uploads', 'uploads/products'];
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Directorio</th><th>Estado</th><th>Permisos</th></tr>";

foreach ($directories as $dir) {
    $exists = is_dir($dir);
    $status = $exists ? "✓ EXISTE" : "✗ FALTA";
    $color = $exists ? "green" : "red";
    $perms = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';

    echo "<tr>";
    echo "<td>$dir</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "<td>$perms</td>";
    echo "</tr>";
}

echo "</table>";

// Listar lo que SÍ existe en el directorio actual
echo "<h2>Archivos PHP en directorio raíz</h2>";
$root_files = glob('*.php');
echo "<ul>";
foreach ($root_files as $file) {
    echo "<li>$file</li>";
}
echo "</ul>";
?>
