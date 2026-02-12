<?php
/**
 * Test para depurar get-dynamic-filters.php
 */

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log de inicio
file_put_contents(__DIR__ . '/debug.log', "[" . date('Y-m-d H:i:s') . "] Inicio de prueba\n", FILE_APPEND);

header('Content-Type: application/json');

try {
    file_put_contents(__DIR__ . '/debug.log', "[" . date('Y-m-d H:i:s') . "] Cargando database.php\n", FILE_APPEND);
    require_once '../config/database.php';
    
    file_put_contents(__DIR__ . '/debug.log', "[" . date('Y-m-d H:i:s') . "] Cargando ProductManager.php\n", FILE_APPEND);
    require_once '../includes/ProductManager.php';
    
    file_put_contents(__DIR__ . '/debug.log', "[" . date('Y-m-d H:i:s') . "] Todo cargado correctamente\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test exitoso',
        'files_loaded' => true
    ]);
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug.log', "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
