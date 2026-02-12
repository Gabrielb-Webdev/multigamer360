<?php
/**
 * Importación masiva de productos desde CSV
 */

session_start();
require_once '../../config/database.php';
require_once '../inc/auth.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasPermission('products', 'create')) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para importar productos']);
    exit;
}

// Verificar que se haya enviado un archivo
if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se ha cargado ningún archivo o hubo un error']);
    exit;
}

$updateExisting = isset($_POST['updateExisting']) && $_POST['updateExisting'] === 'on';

// Obtener información del archivo
$fileName = $_FILES['csvFile']['name'];
$fileTmpPath = $_FILES['csvFile']['tmp_name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Verificar extensión
if (!in_array($fileExtension, ['csv', 'xls', 'xlsx'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de archivo no válido. Use CSV, XLS o XLSX']);
    exit;
}

// Si es Excel, intentar convertirlo a CSV
$csvFile = $fileTmpPath;
if ($fileExtension === 'xls' || $fileExtension === 'xlsx') {
    // Intentar usar una conversión simple leyendo como tabla HTML
    // Excel puede guardar como HTML, que podemos parsear
    $csvFile = sys_get_temp_dir() . '/temp_import_' . uniqid() . '.csv';
    
    // Leer el archivo Excel
    $content = file_get_contents($fileTmpPath);
    
    // Si es un archivo XML de Excel (xlsx es ZIP con XML)
    if ($fileExtension === 'xlsx') {
        // Intentar extraer usando ZIP
        $zip = new ZipArchive();
        if ($zip->open($fileTmpPath) === TRUE) {
            // Buscar el sheet principal
            $sheetData = $zip->getFromName('xl/sharedStrings.xml');
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            
            if ($sheetXml) {
                // Procesar XML a CSV (simplificado)
                $xml = simplexml_load_string($sheetXml);
                $csv_output = fopen($csvFile, 'w');
                
                // Extraer datos de las filas
                foreach ($xml->sheetData->row as $row) {
                    $csvRow = [];
                    foreach ($row->c as $cell) {
                        $value = (string)$cell->v;
                        $csvRow[] = $value;
                    }
                    fputcsv($csv_output, $csvRow);
                }
                fclose($csv_output);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo Excel. Use el formato CSV para mayor compatibilidad.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo abrir el archivo Excel. Intente guardarlo como CSV desde Excel.']);
            exit;
        }
    } else {
        // Para XLS, sugerir convertir a CSV
        echo json_encode(['success' => false, 'message' => 'Archivos .xls no soportados directamente. Por favor, guarde el archivo como .xlsx o .csv desde Excel (Archivo > Guardar como > CSV UTF-8)']);
        exit;
    }
}

// Leer el archivo CSV
$handle = fopen($csvFile, 'r');

if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo CSV']);
    exit;
}

// Estadísticas
$imported = 0;
$updated = 0;
$errors = 0;
$errorDetails = [];

// Leer la primera línea (encabezados)
$headers = fgetcsv($handle, 1000, ',');

if (!$headers) {
    echo json_encode(['success' => false, 'message' => 'El archivo CSV está vacío o mal formado']);
    fclose($handle);
    exit;
}

// Normalizar encabezados (trim y minúsculas)
$headers = array_map(function($h) {
    // Mapear encabezados en español a inglés
    $map = [
        'titulo' => 'title',
        'título' => 'title',
        'nombre' => 'title',
        'descripcion' => 'description',
        'descripción' => 'description',
        'descripcion_corta' => 'short_description',
        'descripción_corta' => 'short_description',
        'precio_pesos' => 'price_pesos',
        'precio_dolares' => 'price_usd',
        'precio_dólares' => 'price_usd',
        'stock' => 'stock_quantity',
        'categoria' => 'category',
        'categoría' => 'category',
        'consola' => 'console',
        'generos' => 'genres',
        'géneros' => 'genres',
        'destacado' => 'is_featured',
        'novedad' => 'is_new',
        'activo' => 'is_active',
        'visible_tienda' => 'is_active',
        'estado' => 'status',
        'en_oferta' => 'on_sale',
        'condicion' => 'condition',
        'condición' => 'condition',
        'etiquetas' => 'tags',
        'meta_titulo' => 'meta_title',
        'meta_título' => 'meta_title',
        'meta_descripcion' => 'meta_description',
        'meta_descripción' => 'meta_description',
        'imagen_principal' => 'primary_image'
    ];
    
    $normalized = strtolower(trim($h));
    return isset($map[$normalized]) ? $map[$normalized] : $normalized;
}, $headers);

// Verificar encabezados requeridos
$requiredHeaders = ['title', 'sku', 'price_pesos', 'stock_quantity'];
foreach ($requiredHeaders as $required) {
    if (!in_array($required, $headers)) {
        echo json_encode(['success' => false, 'message' => "Falta el campo requerido: $required (o su equivalente en español)"]);
        fclose($handle);
        exit;
    }
}

// Cache para búsquedas de categorías, marcas, consolas y géneros
$categoriesCache = [];
$brandsCache = [];
$consolesCache = [];
$genresCache = [];

// Procesar cada línea
$lineNumber = 1;
while (($data = fgetcsv($handle, 1000, ',')) !== false) {
    $lineNumber++;
    
    // Saltar líneas vacías
    if (empty(array_filter($data))) {
        continue;
    }
    
    // Convertir datos en array asociativo
    $row = [];
    foreach ($headers as $index => $header) {
        $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
    }
    
    try {
        // Validar campos requeridos
        if (empty($row['title']) || empty($row['sku'])) {
            throw new Exception("Línea $lineNumber: Título y SKU son obligatorios");
        }
        
        // Verificar si el SKU ya existe
        $checkSku = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $checkSku->execute([$row['sku']]);
        $existingProduct = $checkSku->fetch();
        
        if ($existingProduct && !$updateExisting) {
            throw new Exception("Línea $lineNumber: El SKU '{$row['sku']}' ya existe. Active 'Actualizar productos existentes' para actualizar.");
        }
        
        // Generar slug desde el título
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['title'])));
        
        // Buscar category_id si se proporcionó categoría
        $category_id = null;
        if (!empty($row['category'])) {
            $categoryName = $row['category'];
            if (!isset($categoriesCache[$categoryName])) {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$categoryName]);
                $categoriesCache[$categoryName] = $stmt->fetchColumn();
            }
            $category_id = $categoriesCache[$categoryName];
        }
        
        // Buscar brand_id si se proporcionó marca
        $brand_id = null;
        if (!empty($row['brand'])) {
            $brandName = $row['brand'];
            if (!isset($brandsCache[$brandName])) {
                $stmt = $pdo->prepare("SELECT id FROM brands WHERE name = ?");
                $stmt->execute([$brandName]);
                $brandsCache[$brandName] = $stmt->fetchColumn();
            }
            $brand_id = $brandsCache[$brandName];
        }
        
        // Buscar console_id si se proporcionó consola
        $console_id = null;
        if (!empty($row['console'])) {
            $consoleName = $row['console'];
            if (!isset($consolesCache[$consoleName])) {
                $stmt = $pdo->prepare("SELECT id FROM consoles WHERE name = ?");
                $stmt->execute([$consoleName]);
                $consolesCache[$consoleName] = $stmt->fetchColumn();
            }
            $console_id = $consolesCache[$consoleName];
        }
        
        // Procesar géneros (separados por punto y coma)
        $genreIds = [];
        if (!empty($row['genres'])) {
            $genreNames = array_map('trim', explode(';', $row['genres']));
            foreach ($genreNames as $genreName) {
                if (empty($genreName)) continue;
                
                if (!isset($genresCache[$genreName])) {
                    $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = ?");
                    $stmt->execute([$genreName]);
                    $genresCache[$genreName] = $stmt->fetchColumn();
                }
                
                if ($genresCache[$genreName]) {
                    $genreIds[] = $genresCache[$genreName];
                }
            }
        }
        
        // Procesar condición (mapear español a inglés)
        $condition = 'new';
        if (!empty($row['condition'])) {
            $conditionMap = [
                'nuevo' => 'new',
                'new' => 'new',
                'usado' => 'used',
                'used' => 'used',
                'reacondicionado' => 'refurbished',
                'refurbished' => 'refurbished'
            ];
            $condition = $conditionMap[strtolower($row['condition'])] ?? 'new';
        }
        
        // Procesar estado del producto
        $status = 1; // activo por defecto
        if (!empty($row['status'])) {
            $statusMap = [
                'activo' => 1,
                'active' => 1,
                'inactivo' => 0,
                'inactive' => 0,
                'agotado' => 0,
                'out_of_stock' => 0
            ];
            $status = $statusMap[strtolower($row['status'])] ?? 1;
        }
        
        // Preparar datos del producto
        $productData = [
            'title' => $row['title'],
            'slug' => $slug,
            'sku' => $row['sku'],
            'description' => $row['description'] ?? '',
            'short_description' => $row['short_description'] ?? '',
            'price_pesos' => floatval($row['price_pesos'] ?? 0),
            'price_usd' => floatval($row['price_usd'] ?? 0),
            'price' => floatval($row['price_pesos'] ?? 0), // Mantener compatibilidad
            'stock_quantity' => intval($row['stock_quantity'] ?? 0),
            'category_id' => $category_id,
            'brand_id' => $brand_id,
            'console_id' => $console_id,
            'is_featured' => !empty($row['is_featured']) && strtolower($row['is_featured']) === 'si' ? 1 : 0,
            'is_new' => !empty($row['is_new']) && strtolower($row['is_new']) === 'si' ? 1 : 0,
            'is_active' => $status,
            'on_sale' => !empty($row['on_sale']) && strtolower($row['on_sale']) === 'si' ? 1 : 0,
            'condition_product' => $condition,
            'tags' => $row['tags'] ?? '',
            'meta_title' => $row['meta_title'] ?? '',
            'meta_description' => $row['meta_description'] ?? ''
        ];
        
        if ($existingProduct && $updateExisting) {
            // Actualizar producto existente
            $sql = "UPDATE products SET 
                    title = :title,
                    slug = :slug,
                    description = :description,
                    short_description = :short_description,
                    price_pesos = :price_pesos,
                    price_usd = :price_usd,
                    price = :price,
                    stock_quantity = :stock_quantity,
                    category_id = :category_id,
                    brand_id = :brand_id,
                    console_id = :console_id,
                    is_featured = :is_featured,
                    is_new = :is_new,
                    is_active = :is_active,
                    on_sale = :on_sale,
                    condition_product = :condition_product,
                    tags = :tags,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    updated_at = NOW()
                    WHERE id = :id";
            
            $productData['id'] = $existingProduct['id'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($productData);
            
            $productId = $existingProduct['id'];
            
            // Actualizar géneros si se proporcionaron
            if (!empty($genreIds)) {
                // Eliminar géneros anteriores
                $pdo->prepare("DELETE FROM product_genres WHERE product_id = ?")->execute([$productId]);
                
                // Insertar nuevos géneros
                $genreStmt = $pdo->prepare("INSERT INTO product_genres (product_id, genre_id) VALUES (?, ?)");
                foreach ($genreIds as $genreId) {
                    $genreStmt->execute([$productId, $genreId]);
                }
            }
            
            $updated++;
        } else {
            // Insertar nuevo producto
            $sql = "INSERT INTO products (
                    title, slug, sku, description, short_description,
                    price_pesos, price_usd, price, stock_quantity,
                    category_id, brand_id, console_id,
                    is_featured, is_new, is_active, on_sale, condition_product, tags,
                    meta_title, meta_description,
                    created_at, updated_at
                ) VALUES (
                    :title, :slug, :sku, :description, :short_description,
                    :price_pesos, :price_usd, :price, :stock_quantity,
                    :category_id, :brand_id, :console_id,
                    :is_featured, :is_new, :is_active, :on_sale, :condition_product, :tags,
                    :meta_title, :meta_description,
                    NOW(), NOW()
                )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($productData);
            
            $productId = $pdo->lastInsertId();
            
            // Insertar géneros si se proporcionaron
            if (!empty($genreIds)) {
                $genreStmt = $pdo->prepare("INSERT INTO product_genres (product_id, genre_id) VALUES (?, ?)");
                foreach ($genreIds as $genreId) {
                    $genreStmt->execute([$productId, $genreId]);
                }
            }
            
            $imported++;
        }
        
    } catch (Exception $e) {
        $errors++;
        $errorDetails[] = $e->getMessage();
    }
}

fclose($handle);

// Limpiar archivo temporal si se creó
if ($csvFile !== $fileTmpPath && file_exists($csvFile)) {
    unlink($csvFile);
}

// Respuesta final
echo json_encode([
    'success' => true,
    'imported' => $imported,
    'updated' => $updated,
    'errors' => $errors,
    'error_details' => $errorDetails,
    'message' => "Proceso completado: $imported importados, $updated actualizados, $errors errores"
]);
