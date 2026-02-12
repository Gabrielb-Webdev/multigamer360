<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Reparar Directorios Faltantes</h1>";

// Crear directorio uploads/products si no existe
$uploads_dir = 'uploads';
$products_dir = 'uploads/products';

echo "<h2>Creando directorios necesarios...</h2>";

// Crear uploads si no existe
if (!is_dir($uploads_dir)) {
    if (mkdir($uploads_dir, 0755, true)) {
        echo "<p style='color: green;'>✓ Directorio '$uploads_dir' creado exitosamente</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al crear '$uploads_dir'</p>";
    }
} else {
    echo "<p style='color: blue;'>✓ Directorio '$uploads_dir' ya existe</p>";
}

// Crear uploads/products si no existe
if (!is_dir($products_dir)) {
    if (mkdir($products_dir, 0755, true)) {
        echo "<p style='color: green;'>✓ Directorio '$products_dir' creado exitosamente</p>";
        chmod($products_dir, 0755);
        echo "<p style='color: green;'>✓ Permisos configurados (0755)</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al crear '$products_dir'</p>";
    }
} else {
    echo "<p style='color: blue;'>✓ Directorio '$products_dir' ya existe</p>";
    // Verificar y corregir permisos
    $perms = substr(sprintf('%o', fileperms($products_dir)), -4);
    echo "<p>Permisos actuales: $perms</p>";

    if ($perms != '0755' && $perms != '0777') {
        chmod($products_dir, 0755);
        echo "<p style='color: green;'>✓ Permisos corregidos a 0755</p>";
    }
}

echo "<h2>Verificación Final</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Directorio</th><th>Existe</th><th>Permisos</th><th>Escribible</th></tr>";

$dirs_to_check = ['uploads', 'uploads/products'];
foreach ($dirs_to_check as $dir) {
    $exists = is_dir($dir);
    $perms = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
    $writable = $exists && is_writable($dir) ? 'Sí' : 'No';
    $color = $exists ? 'green' : 'red';

    echo "<tr>";
    echo "<td>$dir</td>";
    echo "<td style='color: $color;'>" . ($exists ? 'Sí' : 'No') . "</td>";
    echo "<td>$perms</td>";
    echo "<td style='color: " . ($writable == 'Sí' ? 'green' : 'red') . "'>$writable</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>✅ Proceso Completado</h2>";
echo "<p><strong>Siguiente paso:</strong> Sube el archivo <code>config/database.php</code> al servidor.</p>";
echo "<p>Luego visita: <a href='check-files.php'>check-files.php</a> para verificar.</p>";
?>
