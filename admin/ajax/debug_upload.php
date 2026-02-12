<?php
// Debug completo del upload
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_csv_errors.log');

// Iniciar log
file_put_contents('/tmp/csv_upload_debug.log', date('Y-m-d H:i:s') . " - Inicio del script\n", FILE_APPEND);

// Limpiar buffer
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

try {
    file_put_contents('/tmp/csv_upload_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents('/tmp/csv_upload_debug.log', "FILES data: " . print_r($_FILES, true) . "\n", FILE_APPEND);
    
    // Verificar si se recibió el archivo
    if (!isset($_FILES['csv_file'])) {
        $response = [
            'success' => false,
            'message' => 'No se recibió ningún archivo',
            'debug' => [
                'post_keys' => array_keys($_POST),
                'files_keys' => array_keys($_FILES),
                'method' => $_SERVER['REQUEST_METHOD']
            ]
        ];
        echo json_encode($response);
        exit;
    }
    
    $file = $_FILES['csv_file'];
    
    file_put_contents('/tmp/csv_upload_debug.log', "Archivo recibido: " . $file['name'] . "\n", FILE_APPEND);
    file_put_contents('/tmp/csv_upload_debug.log', "Tamaño: " . $file['size'] . "\n", FILE_APPEND);
    file_put_contents('/tmp/csv_upload_debug.log', "Error code: " . $file['error'] . "\n", FILE_APPEND);
    
    // Verificar errores de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede escribir en disco',
            UPLOAD_ERR_EXTENSION => 'Extensión PHP detuvo la subida'
        ];
        
        $response = [
            'success' => false,
            'message' => 'Error al subir archivo: ' . ($errors[$file['error']] ?? 'Error desconocido'),
            'error_code' => $file['error']
        ];
        echo json_encode($response);
        exit;
    }
    
    // Verificar que el archivo existe
    if (!file_exists($file['tmp_name'])) {
        $response = [
            'success' => false,
            'message' => 'El archivo temporal no existe',
            'tmp_name' => $file['tmp_name']
        ];
        echo json_encode($response);
        exit;
    }
    
    // Leer primeras líneas del archivo
    $content = file_get_contents($file['tmp_name']);
    $firstChars = substr($content, 0, 200);
    
    file_put_contents('/tmp/csv_upload_debug.log', "Primeros caracteres: " . bin2hex($firstChars) . "\n", FILE_APPEND);
    
    // Intentar procesar
    $response = [
        'success' => true,
        'message' => 'Archivo recibido correctamente',
        'file_info' => [
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'extension' => pathinfo($file['name'], PATHINFO_EXTENSION),
            'first_50_chars' => substr($content, 0, 50)
        ]
    ];
    
    echo json_encode($response);
    file_put_contents('/tmp/csv_upload_debug.log', "Respuesta enviada OK\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents('/tmp/csv_upload_debug.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
exit;
