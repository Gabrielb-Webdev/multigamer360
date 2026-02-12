<?php
// Verificación completa del proceso CSV
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

$checks = [];

// 1. Verificar conexión a base de datos
try {
    require_once '../../config/database_production.php';
    $checks['database'] = 'OK';
    
    // Probar query
    $test = $pdo->query("SELECT 1")->fetch();
    $checks['database_query'] = 'OK';
} catch (Exception $e) {
    $checks['database'] = 'ERROR: ' . $e->getMessage();
}

// 2. Verificar extensiones PHP
$checks['ZipArchive'] = class_exists('ZipArchive') ? 'OK' : 'NO DISPONIBLE';
$checks['DOMDocument'] = class_exists('DOMDocument') ? 'OK' : 'NO DISPONIBLE';
$checks['simplexml'] = function_exists('simplexml_load_string') ? 'OK' : 'NO DISPONIBLE';

// 3. Verificar permisos de upload
$checks['upload_max_filesize'] = ini_get('upload_max_filesize');
$checks['post_max_size'] = ini_get('post_max_size');
$checks['max_execution_time'] = ini_get('max_execution_time');

// 4. Verificar si se puede escribir en directorio temporal
$checks['temp_dir'] = sys_get_temp_dir();
$checks['temp_writable'] = is_writable(sys_get_temp_dir()) ? 'SI' : 'NO';

ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Sistema verificado',
    'checks' => $checks,
    'php_version' => PHP_VERSION
]);
exit;
