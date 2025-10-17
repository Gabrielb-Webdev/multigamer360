<?php
/**
 * Verificar configuración de PHP y errores
 */

header('Content-Type: application/json');

$info = [
    'php_version' => phpversion(),
    'error_reporting' => error_reporting(),
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors'),
    'error_log' => ini_get('error_log'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'output_buffering' => ini_get('output_buffering'),
    'ob_get_level' => ob_get_level(),
    'database_exists' => file_exists('../config/database.php'),
    'product_manager_exists' => file_exists('../includes/ProductManager.php'),
];

// Test de conexión a base de datos
try {
    require_once '../config/database.php';
    $info['database_connection'] = isset($pdo) ? 'OK' : 'FAILED';
    
    if (isset($pdo)) {
        $info['database_test'] = 'Conectado correctamente';
        
        // Verificar tablas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $info['tables_count'] = count($tables);
        $info['has_products'] = in_array('products', $tables);
        $info['has_brands'] = in_array('brands', $tables);
        $info['has_consoles'] = in_array('consoles', $tables);
        $info['has_genres'] = in_array('genres', $tables);
    }
} catch (Exception $e) {
    $info['database_error'] = $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT);
?>
