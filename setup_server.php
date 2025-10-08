<?php
// Script para crear el directorio logs si no existe
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Setup del Sistema</title></head><body>";
echo "<h1>Setup del Sistema MultiGamer360</h1>";

// 1. Crear directorio logs/ si no existe
echo "<h2>1. Verificar/Crear Directorio logs/</h2>";
if (!is_dir('logs')) {
    if (mkdir('logs', 0755, true)) {
        echo "<p>✅ Directorio logs/ creado exitosamente</p>";
    } else {
        echo "<p>❌ No se pudo crear el directorio logs/</p>";
    }
} else {
    echo "<p>✅ Directorio logs/ ya existe</p>";
}

// Verificar permisos de logs/
if (is_dir('logs')) {
    if (is_writable('logs')) {
        echo "<p>✅ Directorio logs/ es escribible</p>";
    } else {
        echo "<p>⚠️ Directorio logs/ existe pero NO es escribible. Cambia los permisos a 755 o 777</p>";
    }
}

// 2. Verificar config/database.php
echo "<h2>2. Verificar config/database.php</h2>";
if (file_exists('config/database.php')) {
    echo "<p>✅ config/database.php existe</p>";
    
    // Intentar incluirlo
    try {
        require_once 'config/database.php';
        if (isset($pdo)) {
            echo "<p>✅ Conexión PDO establecida correctamente</p>";
        } else {
            echo "<p>❌ config/database.php existe pero no crea la variable \$pdo</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error al cargar config/database.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config/database.php NO EXISTE</p>";
    echo "<p><strong>ACCIÓN REQUERIDA:</strong> Sube el archivo config/database.php al servidor en la ruta:</p>";
    echo "<pre>/home/u851317150/domains/teal-fish-507993.hostingersite.com/public_html/config/database.php</pre>";
}

// 3. Verificar otros archivos críticos
echo "<h2>3. Verificar Archivos Críticos</h2>";
$critical_files = [
    'index.php',
    'config/session_config.php',
    'includes/auth.php',
    'includes/product_manager.php',
    'config/user_manager.php'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file existe</p>";
    } else {
        echo "<p>❌ $file NO existe</p>";
    }
}

// 4. Test de sesión
echo "<h2>4. Test de Sesión</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Sesión iniciada correctamente</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error al iniciar sesión: " . $e->getMessage() . "</p>";
}

// 5. Resumen
echo "<h2>Resumen</h2>";
$all_ok = is_dir('logs') && file_exists('config/database.php') && file_exists('index.php');

if ($all_ok) {
    echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px;'>";
    echo "<h3>✅ Todo está listo</h3>";
    echo "<p>El sistema debería funcionar correctamente ahora.</p>";
    echo "<p><a href='index.php' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sitio Principal</a></p>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>⚠️ Aún hay problemas por resolver</h3>";
    echo "<p>Revisa los errores marcados arriba y corrígelos.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
