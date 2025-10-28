<?php
// Script para verificar si los logs están funcionando
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificación de Errores PHP</h1>";

echo "<h2>Configuración actual:</h2>";
echo "<pre>";
echo "error_reporting: " . error_reporting() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "</pre>";

echo "<h2>Directorio de logs:</h2>";
$log_dir = __DIR__ . '/logs';
if (is_dir($log_dir)) {
    echo "<p>Directorio existe: $log_dir</p>";
    echo "<pre>";
    $files = scandir($log_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filepath = $log_dir . '/' . $file;
            echo "$file (tamaño: " . filesize($filepath) . " bytes)\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>Directorio no existe: $log_dir</p>";
}

echo "<h2>Último error en log PHP (si existe):</h2>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    echo "<pre>";
    $lines = file($error_log_path);
    echo implode('', array_slice($lines, -20)); // Últimas 20 líneas
    echo "</pre>";
} else {
    echo "<p>No se encontró archivo de log PHP</p>";
}

// Test de error_log
error_log("TEST: Este es un mensaje de prueba - " . date('Y-m-d H:i:s'));
echo "<p>Se envió un mensaje de prueba al log</p>";
?>
