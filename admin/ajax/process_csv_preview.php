<?php
/**
 * Procesar CSV - Versión optimizada
 */

// Configuración de errores
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar cualquier output previo
if (ob_get_level()) ob_end_clean();

// Headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Función para enviar respuesta JSON
function sendResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        sendResponse([
            'success' => false,
            'message' => 'Error del servidor: ' . $error['message'],
            'line' => $error['line']
        ]);
    }
});

// Cargar base de datos
try {
    require_once '../../config/database_production.php';
    if (!isset($pdo)) {
        throw new Exception('Conexión PDO no disponible');
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
}

// Si solo se solicitan los dropdowns (para recargar)
if (isset($_GET['reload_dropdowns'])) {
    try {
        $categorias = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
        $marcas = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
        $consolas = $pdo->query("SELECT id, name FROM consoles WHERE is_active = 1 ORDER BY name")->fetchAll();
        $generos = $pdo->query("SELECT id, name FROM genres WHERE is_active = 1 ORDER BY name")->fetchAll();
        
        sendResponse([
            'success' => true,
            'dropdowns' => [
                'categories' => $categorias,
                'brands' => $marcas,
                'consoles' => $consolas,
                'genres' => $generos
            ]
        ]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Verificar que se recibió el archivo
if (!isset($_FILES['csv_file'])) {
    sendResponse(['success' => false, 'message' => 'No se recibió ningún archivo']);
}

$file = $_FILES['csv_file'];

// Verificar errores de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'Archivo muy grande (límite del servidor)',
        UPLOAD_ERR_FORM_SIZE => 'Archivo muy grande (límite del formulario)',
        UPLOAD_ERR_PARTIAL => 'Archivo subido parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
        UPLOAD_ERR_EXTENSION => 'Extensión bloqueada'
    ];
    sendResponse([
        'success' => false,
        'message' => $errors[$file['error']] ?? 'Error desconocido: ' . $file['error']
    ]);
}

// Función para procesar CSV
function parseCSV($filePath) {
    $data = [];
    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 10000, ',')) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

// Función para procesar Excel (.xlsx) - SIMPLIFICADA
function parseXLSX($filePath) {
    if (!class_exists('ZipArchive')) {
        return false;
    }
    
    $zip = new ZipArchive;
    if ($zip->open($filePath) !== TRUE) {
        return false;
    }
    
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    
    if (!$sheetData) return false;
    
    $strings = [];
    if ($sharedStrings) {
        $xml = @simplexml_load_string($sharedStrings);
        if ($xml) {
            foreach ($xml->si as $si) {
                $strings[] = (string)$si->t;
            }
        }
    }
    
    $xml = @simplexml_load_string($sheetData);
    if (!$xml) return false;
    
    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $value = (string)$cell->v;
            if (isset($cell['t']) && (string)$cell['t'] === 's') {
                $value = $strings[(int)$value] ?? '';
            }
            $rowData[] = $value;
        }
        $rows[] = $rowData;
    }
    
    return $rows;
}

// ==================== PROCESAMIENTO DEL ARCHIVO ====================

$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Verificar que el archivo existe
if (!file_exists($fileTmp) || !is_readable($fileTmp)) {
    sendResponse(['success' => false, 'message' => 'No se puede leer el archivo temporal']);
}

// Procesar según extensión
$rows = [];

try {
    if ($fileExt === 'xlsx') {
        $rows = parseXLSX($fileTmp);
        if ($rows === false) {
            sendResponse(['success' => false, 'message' => 'Error al leer XLSX. Usa CSV para mejor compatibilidad']);
        }
    } elseif ($fileExt === 'csv') {
        $rows = parseCSV($fileTmp);
    } else {
        sendResponse(['success' => false, 'message' => 'Formato no soportado. Usa CSV o XLSX']);
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Error al procesar archivo: ' . $e->getMessage()]);
}

if (empty($rows) || count($rows) < 2) {
    sendResponse([
        'success' => false,
        'message' => 'Archivo vacío o sin datos suficientes. Debe tener al menos 2 filas (encabezados + datos)'
    ]);
}

// Obtener datos de la base de datos
try {
    $categorias = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    $marcas = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
    $consolas = $pdo->query("SELECT id, name FROM consoles ORDER BY name")->fetchAll();
    $generos = $pdo->query("SELECT id, name FROM genres ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    sendResponse(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
}

// Procesar headers
$headers = array_map(function($h) {
    $h = trim($h);
    $h = str_replace([' ', '-'], '_', $h);
    $h = strtolower($h);
    $h = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
    return $h;
}, $rows[0]);

// Procesar productos (saltar fila de encabezados)
$products = [];
$startRow = 1; // Empezar desde fila 1 (después de headers)

// Si hay más de 2 filas y la fila 1 parece ser instrucciones, saltar también
if (count($rows) > 2) {
    $firstDataRow = $rows[1];
    $hasData = false;
    foreach ($firstDataRow as $cell) {
        if (!empty($cell) && is_numeric($cell)) {
            $hasData = true;
            break;
        }
    }
    if (!$hasData) {
        $startRow = 2; // Saltar fila de instrucciones
    }
}

for ($i = $startRow; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    // Saltar filas vacías
    if (empty(array_filter($row))) continue;
    
    // Crear producto con headers
    $product = [];
    foreach ($headers as $index => $header) {
        $product[$header] = isset($row[$index]) ? trim($row[$index]) : '';
    }
    
    // Mapeo flexible de campos
    $title = $product['nombre_producto'] ?? $product['nombre'] ?? $product['titulo'] ?? '';
    $price_cop = $product['precio_pesos'] ?? $product['precio'] ?? $product['precio_cop'] ?? '';
    $price_usd = $product['precio_dolares'] ?? $product['precio_usd'] ?? $product['preciousd'] ?? '';
    $stock = $product['stock'] ?? $product['cantidad'] ?? 0;
    $console_name = $product['consola'] ?? $product['plataforma'] ?? '';
    $tipo = $product['tipo_producto'] ?? $product['tipo'] ?? 'juego';
    $estado = $product['estado'] ?? $product['status'] ?? 'activo';
    
    // Validar datos mínimos
    if (empty($title) || empty($price_cop)) continue;
    
    // Mapear tipo de producto
    $product_type = 'game';
    $tipo_lower = strtolower($tipo);
    if (strpos($tipo_lower, 'consola') !== false) $product_type = 'console';
    elseif (strpos($tipo_lower, 'accesorio') !== false) $product_type = 'accessory';
    elseif (strpos($tipo_lower, 'juego') !== false || strpos($tipo_lower, 'game') !== false) $product_type = 'game';
    
    // Mapear estado
    $status = 1;
    $estado_lower = strtolower($estado);
    if (strpos($estado_lower, 'inactivo') !== false || strpos($estado_lower, 'agotado') !== false) {
        $status = 0;
    }
    
    // Buscar consola por nombre (coincidencia exacta case-insensitive)
    $console_id = '';
    if (!empty($console_name)) {
        foreach ($consolas as $console) {
            if (strcasecmp($console['name'], $console_name) === 0) {
                $console_id = $console['id'];
                break;
            }
        }
    }
    
    // Producto mapeado
    $mappedProduct = [
        'title' => $title,
        'product_type' => $product_type,
        'console_name' => $console_name,
        'console_id' => $console_id,
        'status' => $status,
        'stock' => (int)$stock,
        'price_cop' => $price_cop,
        'price_usd' => $price_usd,
        'sku' => '',
        'description' => '',
        'category_id' => '',
        'brand_id' => '',
        'genres' => [],
        'is_featured' => 0,
        'is_new' => 0,
        'on_sale' => 0,
        'meta_title' => '',
        'meta_description' => '',
        'images' => []
    ];
    
    $products[] = $mappedProduct;
}

if (empty($products)) {
    sendResponse([
        'success' => false,
        'message' => 'No se encontraron productos válidos. Verifica que el archivo tenga columnas: nombre_producto, precio_pesos',
        'headers' => $headers
    ]);
}

// Respuesta exitosa
sendResponse([
    'success' => true,
    'products' => $products,
    'dropdowns' => [
        'categories' => $categorias,
        'brands' => $marcas,
        'consoles' => $consolas,
        'genres' => $generos
    ],
    'message' => count($products) . ' productos cargados para revisión'
]);
