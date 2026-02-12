<?php
/**
 * Guardar producto revisado y completado
 */

require_once '../../config/database_production.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$productData = json_decode(file_get_contents('php://input'), true);

if (!$productData) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos del producto.']);
    exit;
}

try {
    // Validar campos obligatorios básicos
    $required = ['title', 'price_cop'];
    foreach ($required as $field) {
        if (empty($productData[$field])) {
            echo json_encode(['success' => false, 'message' => "El campo {$field} es obligatorio."]);
            exit;
        }
    }
    
    // ==================== AUTO-CREAR CATEGORÍA SI NO EXISTE ====================
    if (!empty($productData['category_name'])) {
        // Buscar si existe
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $productData['category_name']]);
        $category = $stmt->fetch();
        
        if ($category) {
            $productData['category_id'] = $category['id'];
        } else {
            // Crear nueva categoría
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productData['category_name'])));
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, is_active, created_at) VALUES (:name, :slug, 1, NOW())");
            $stmt->execute([
                ':name' => $productData['category_name'],
                ':slug' => $slug
            ]);
            $productData['category_id'] = $pdo->lastInsertId();
            error_log("✓ Categoría creada automáticamente: {$productData['category_name']} (ID: {$productData['category_id']})");
        }
    }
    
    // ==================== AUTO-CREAR MARCA SI NO EXISTE ====================
    if (!empty($productData['brand_name'])) {
        // Buscar si existe
        $stmt = $pdo->prepare("SELECT id FROM brands WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $productData['brand_name']]);
        $brand = $stmt->fetch();
        
        if ($brand) {
            $productData['brand_id'] = $brand['id'];
        } else {
            // Crear nueva marca
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productData['brand_name'])));
            $stmt = $pdo->prepare("INSERT INTO brands (name, slug, is_active, created_at) VALUES (:name, :slug, 1, NOW())");
            $stmt->execute([
                ':name' => $productData['brand_name'],
                ':slug' => $slug
            ]);
            $productData['brand_id'] = $pdo->lastInsertId();
            error_log("✓ Marca creada automáticamente: {$productData['brand_name']} (ID: {$productData['brand_id']})");
        }
    }
    
    // ==================== AUTO-CREAR CONSOLA SI NO EXISTE ====================
    if (!empty($productData['console_name'])) {
        // Buscar si existe
        $stmt = $pdo->prepare("SELECT id FROM consoles WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $productData['console_name']]);
        $console = $stmt->fetch();
        
        if ($console) {
            $productData['console_id'] = $console['id'];
        } else {
            // Crear nueva consola
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productData['console_name'])));
            // Intentar extraer el fabricante del nombre
            $manufacturer = null;
            if (stripos($productData['console_name'], 'playstation') !== false || stripos($productData['console_name'], 'ps') !== false) {
                $manufacturer = 'Sony';
            } elseif (stripos($productData['console_name'], 'xbox') !== false) {
                $manufacturer = 'Microsoft';
            } elseif (stripos($productData['console_name'], 'nintendo') !== false) {
                $manufacturer = 'Nintendo';
            }
            
            $stmt = $pdo->prepare("INSERT INTO consoles (name, slug, manufacturer, is_active, created_at) VALUES (:name, :slug, :manufacturer, 1, NOW())");
            $stmt->execute([
                ':name' => $productData['console_name'],
                ':slug' => $slug,
                ':manufacturer' => $manufacturer
            ]);
            $productData['console_id'] = $pdo->lastInsertId();
            error_log("✓ Consola creada automáticamente: {$productData['console_name']} (ID: {$productData['console_id']})");
        }
    }
    
    // Validar que ahora sí tengamos los IDs necesarios
    if (empty($productData['category_id']) || empty($productData['brand_id']) || empty($productData['console_id'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan categoría, marca o consola.']);
        exit;
    }
    
    // Generar slug único automáticamente desde el título
    $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productData['title'])));
    $baseSlug = trim($baseSlug, '-'); // Eliminar guiones al inicio y final
    
    // Si el slug está vacío, usar timestamp como fallback
    if (empty($baseSlug)) {
        $baseSlug = 'producto-' . time();
    }
    
    // Verificar si el slug existe y hacerlo único
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        if (!$stmt->fetch()) {
            break; // Slug único encontrado
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    // Preparar datos para inserción
    $sql = "INSERT INTO products (
        name, slug, sku, status, description,
        price_pesos, price_dollars, stock_quantity, category_id, brand_id,
        console_id, product_type, is_featured, is_new, is_on_sale, is_active,
        meta_title, meta_description,
        created_at, updated_at
    ) VALUES (
        :name, :slug, :sku, :status, :description,
        :price_pesos, :price_dollars, :stock_quantity, :category_id, :brand_id,
        :console_id, :product_type, :is_featured, :is_new, :is_on_sale, :is_active,
        :meta_title, :meta_description,
        NOW(), NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $productData['title'],
        ':slug' => $slug,
        ':sku' => $productData['sku'] ?: 'AUTO-' . time(),
        ':status' => $productData['status'] ?? 1,
        ':description' => $productData['description'] ?? '',
        ':price_pesos' => $productData['price_cop'],
        ':price_dollars' => $productData['price_usd'] ?? 0,
        ':stock_quantity' => $productData['stock'] ?? 0,
        ':category_id' => $productData['category_id'],
        ':brand_id' => $productData['brand_id'],
        ':console_id' => $productData['console_id'],
        ':product_type' => $productData['product_type'] ?? 'game',
        ':is_featured' => $productData['is_featured'] ?? 0,
        ':is_new' => $productData['is_new'] ?? 0,
        ':is_on_sale' => $productData['on_sale'] ?? 0,
        ':is_active' => $productData['is_active'] ?? 1,
        ':meta_title' => $productData['meta_title'] ?? $productData['title'],
        ':meta_description' => $productData['meta_description'] ?? ''
    ]);
    
    $productId = $pdo->lastInsertId();
    
    // ==================== AUTO-CREAR Y ASOCIAR GÉNEROS ====================
    if (!empty($productData['genres']) && is_array($productData['genres'])) {
        $genreStmt = $pdo->prepare("INSERT INTO product_genres (product_id, genre_id) VALUES (:product_id, :genre_id)");
        foreach ($productData['genres'] as $genreId) {
            $genreStmt->execute([
                ':product_id' => $productId,
                ':genre_id' => $genreId
            ]);
        }
    }
    
    // Auto-crear géneros desde nombres si vienen
    if (!empty($productData['genre_names']) && is_array($productData['genre_names'])) {
        foreach ($productData['genre_names'] as $genreName) {
            // Buscar si existe
            $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => trim($genreName)]);
            $genre = $stmt->fetch();
            
            if ($genre) {
                $genreId = $genre['id'];
            } else {
                // Crear nuevo género
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $genreName)));
                $stmt = $pdo->prepare("INSERT INTO genres (name, slug, is_active, created_at) VALUES (:name, :slug, 1, NOW())");
                $stmt->execute([
                    ':name' => trim($genreName),
                    ':slug' => $slug
                ]);
                $genreId = $pdo->lastInsertId();
                error_log("✓ Género creado automáticamente: {$genreName} (ID: {$genreId})");
            }
            
            // Asociar género al producto
            $genreStmt = $pdo->prepare("INSERT IGNORE INTO product_genres (product_id, genre_id) VALUES (:product_id, :genre_id)");
            $genreStmt->execute([
                ':product_id' => $productId,
                ':genre_id' => $genreId
            ]);
        }
    }
    
    // Procesar imágenes si se subieron
    if (!empty($productData['images']) && is_array($productData['images'])) {
        $imageStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (:product_id, :image_url, :is_primary, :display_order)");
        
        $mainImage = null;
        foreach ($productData['images'] as $index => $imageData) {
            // Puede venir como string, como objeto con 'path', o como array asociativo
            if (is_string($imageData)) {
                $imagePath = $imageData;
            } elseif (is_array($imageData) && isset($imageData['path'])) {
                $imagePath = $imageData['path'];
            } elseif (is_array($imageData) && isset($imageData['url'])) {
                $imagePath = $imageData['url'];
            } else {
                // Fallback: convertir a string
                $imagePath = (string)$imageData;
            }
            
            // Extraer solo el nombre del archivo (sin la ruta completa)
            // Si viene: "uploads/products/rawg_123.jpg" → guardar solo "rawg_123.jpg"
            // Si viene: "http://example.com/image.jpg" → guardar solo "image.jpg"
            $imageFileName = basename($imagePath);
            
            // Log para debug
            error_log("Imagen original: $imagePath");
            error_log("Guardando solo nombre: $imageFileName");
            
            $imageStmt->execute([
                ':product_id' => $productId,
                ':image_url' => $imageFileName,
                ':is_primary' => $index === 0 ? 1 : 0,
                ':display_order' => $index + 1
            ]);
            
            // Guardar la primera imagen como main_image (solo nombre)
            if ($index === 0) {
                $mainImage = $imageFileName;
            }
        }
        
        // Actualizar main_image en la tabla products
        if ($mainImage) {
            error_log("Actualizando main_image para producto $productId: $mainImage");
            $updateMainImage = $pdo->prepare("UPDATE products SET main_image = :main_image WHERE id = :id");
            $updateMainImage->execute([
                ':main_image' => $mainImage,
                ':id' => $productId
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'product_id' => $productId,
        'message' => 'Producto guardado exitosamente.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el producto: ' . $e->getMessage()
    ]);
}
