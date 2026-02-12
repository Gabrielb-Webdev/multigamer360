<?php
/**
 * Procesar CSV - Versión simplificada con debug
 */

// Limpiar cualquier output
while (ob_get_level()) ob_end_clean();
ob_start();

// Configurar headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Función para enviar respuesta JSON limpia
function sendJSON($data) {
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Método no permitido']);
}

// Verificar archivo
if (!isset($_FILES['csv_file'])) {
    sendJSON(['success' => false, 'message' => 'No se recibió ningún archivo']);
}

$file = $_FILES['csv_file'];

// Verificar errores de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJSON(['success' => false, 'message' => 'Error al subir archivo: código ' . $file['error']]);
}

// Verificar que existe el archivo temporal
if (!file_exists($file['tmp_name'])) {
    sendJSON(['success' => false, 'message' => 'Archivo temporal no encontrado']);
}

// Cargar base de datos
try {
    require_once '../../config/database_production.php';
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}

// Si solo se piden dropdowns
if (isset($_GET['reload_dropdowns'])) {
    try {
        $categorias = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $marcas = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $consolas = $pdo->query("SELECT id, name FROM consoles WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $generos = $pdo->query("SELECT id, name FROM genres WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        
        sendJSON([
            'success' => true,
            'dropdowns' => [
                'categories' => $categorias,
                'brands' => $marcas,
                'consoles' => $consolas,
                'genres' => $generos
            ]
        ]);
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Obtener datos de BD
try {
    $categorias = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $marcas = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $consolas = $pdo->query("SELECT id, name FROM consoles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $generos = $pdo->query("SELECT id, name FROM genres ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
}

// Procesar archivo
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Función simple para leer CSV
function readSimpleCSV($file) {
    $rows = [];
    if (($handle = fopen($file, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 10000, ',')) !== FALSE) {
            $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows;
}

// Por ahora solo soportar CSV
if ($fileExt !== 'csv') {
    sendJSON(['success' => false, 'message' => 'Por favor usa formato CSV. Extensión recibida: ' . $fileExt]);
}

$rows = readSimpleCSV($fileTmp);

if (empty($rows) || count($rows) < 2) {
    sendJSON([
        'success' => false,
        'message' => 'Archivo vacío o sin datos. Filas encontradas: ' . count($rows)
    ]);
}

// Procesar headers
$headers = array_map('trim', $rows[0]);
$headers = array_map('strtolower', $headers);

// Procesar productos
$products = [];
for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    if (empty(array_filter($row))) continue;
    
    // Crear producto básico
    $product = [];
    foreach ($headers as $idx => $header) {
        $product[$header] = isset($row[$idx]) ? trim($row[$idx]) : '';
    }
    
    // Mapeo básico
    $mappedProduct = [
        'title' => $product['nombre_producto'] ?? $product['nombre'] ?? '',
        'product_type' => 'game',
        'console_name' => $product['consola'] ?? '',
        'status' => 1,
        'stock' => $product['stock'] ?? 0,
        'price_cop' => $product['precio_pesos'] ?? $product['precio'] ?? 0,
        'price_usd' => $product['precio_dolares'] ?? $product['precio_usd'] ?? '',
        'sku' => '',
        'description' => '',
        'category_id' => '',
        'brand_id' => '',
        'console_id' => '',
        'genres' => [],
        'is_featured' => 0,
        'is_new' => 0,
        'on_sale' => 0,
        'meta_title' => '',
        'meta_description' => '',
        'images' => []
    ];
    
    if (empty($mappedProduct['title'])) continue;
    
    $products[] = $mappedProduct;
}

if (empty($products)) {
    sendJSON([
        'success' => false,
        'message' => 'No se encontraron productos válidos. Headers: ' . implode(', ', $headers)
    ]);
}

// Respuesta exitosa
sendJSON([
    'success' => true,
    'products' => $products,
    'dropdowns' => [
        'categories' => $categorias,
        'brands' => $marcas,
        'consoles' => $consolas,
        'genres' => $generos
    ],
    'message' => count($products) . ' productos cargados'
]);
