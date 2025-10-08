<?php
// Test de diagnóstico para Hostinger
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico del Sistema</h2>";

// 1. Test de PHP
echo "<h3>1. Versión de PHP</h3>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. Test de conexión a base de datos
echo "<h3>2. Test de Conexión a Base de Datos</h3>";
$host = 'localhost';
$dbname = 'u851317150_mg360_db';
$username = 'u851317150_mg360_user';
$password = 'MultiGamer2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión a la base de datos exitosa<br>";
    
    // Test de tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tablas encontradas: " . count($tables) . "<br>";
    echo "<details><summary>Ver tablas</summary><ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul></details>";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

// 3. Test de archivos requeridos
echo "<h3>3. Test de Archivos Necesarios</h3>";
$required_files = [
    'config/database.php',
    'config/session_config.php',
    'includes/auth.php'
];

foreach($required_files as $file) {
    if(file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO existe<br>";
    }
}

// 4. Test de directorios con permisos
echo "<h3>4. Test de Directorios</h3>";
$directories = ['uploads', 'logs', 'assets'];
foreach($directories as $dir) {
    if(is_dir($dir)) {
        $writable = is_writable($dir) ? '✅ escribible' : '❌ NO escribible';
        echo "$dir: $writable<br>";
    } else {
        echo "$dir: ❌ NO existe<br>";
    }
}

// 5. Test de extensiones PHP
echo "<h3>5. Extensiones PHP Requeridas</h3>";
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'json', 'gd'];
foreach($extensions as $ext) {
    $loaded = extension_loaded($ext) ? '✅' : '❌';
    echo "$loaded $ext<br>";
}

echo "<h3>6. Variables de Entorno</h3>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'No definido') . "<br>";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No definido') . "<br>";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'No definido') . "<br>";

echo "<hr>";
echo "<p>Si todo está ✅, el problema puede estar en index.php o en la configuración de sesiones.</p>";
?>
