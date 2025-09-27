<?php
session_start();
require_once '../inc/auth.php';
require_once '../../config/database.php';

// Asegurar que el token CSRF esté disponible
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json');

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role_level'] < 60) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Manejar método HTTP override para compatibilidad con servidores restrictivos
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['_method'])) {
            $method = strtoupper($input['_method']);
        }
    }
    
    switch ($method) {
        case 'GET':
            handleGet();
            break;
            
        case 'POST':
            handlePost();
            break;
            
        case 'PUT':
            handlePut();
            break;
            
        case 'DELETE':
            handleDelete();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleGet() {
    global $pdo;
    
    if (isset($_GET['id'])) {
        // Obtener producto específico
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, b.name as brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            return;
        }
        
        // Obtener imágenes
        $images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order");
        $images_stmt->execute([$_GET['id']]);
        $product['images'] = $images_stmt->fetchAll();
        
        // Obtener tags
        $tags_stmt = $pdo->prepare("SELECT tag FROM product_tags WHERE product_id = ?");
        $tags_stmt->execute([$_GET['id']]);
        $product['tags'] = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'data' => $product]);
        
    } else {
        // Listar productos con filtros
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, max(10, intval($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = ['1=1'];
        $params = [];
        
        // Filtros
        if (!empty($_GET['search'])) {
            $where_conditions[] = "(p.title LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($_GET['category_id'])) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $_GET['category_id'];
        }
        
        if (!empty($_GET['brand_id'])) {
            $where_conditions[] = "p.brand_id = ?";
            $params[] = $_GET['brand_id'];
        }
        
        if (!empty($_GET['status'])) {
            $where_conditions[] = "p.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (isset($_GET['is_featured'])) {
            $where_conditions[] = "p.is_featured = ?";
            $params[] = $_GET['is_featured'] ? 1 : 0;
        }
        
        if (isset($_GET['low_stock'])) {
            $where_conditions[] = "p.stock <= 10";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Obtener total
        $count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Obtener productos
        $query = "
            SELECT p.*, c.name as category_name, b.name as brand_name,
                   (SELECT filename FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE $where_clause
            ORDER BY p.created_at DESC
            LIMIT $per_page OFFSET $offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $products,
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page)
            ]
        ]);
    }
}

function handlePost() {
    global $pdo;
    
    if (!hasPermission('products', 'create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para crear productos']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    // Validar campos requeridos
    $required = ['title', 'description', 'price', 'stock', 'category_id', 'status'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generar SKU si no se proporciona
        $sku = !empty($input['sku']) ? $input['sku'] : generateSKU($input['title']);
        
        // Verificar SKU único
        $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $sku_check->execute([$sku]);
        if ($sku_check->fetch()) {
            throw new Exception('El SKU ya existe');
        }
        
        // Insertar producto
        $stmt = $pdo->prepare("
            INSERT INTO products (title, description, short_description, sku, price, sale_price, 
                                stock, category_id, brand_id, weight, dimensions, status, is_featured,
                                meta_title, meta_description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['title'],
            $input['description'],
            $input['short_description'] ?? '',
            $sku,
            $input['price'],
            $input['sale_price'] ?? null,
            $input['stock'],
            $input['category_id'],
            $input['brand_id'] ?? null,
            $input['weight'] ?? null,
            $input['dimensions'] ?? '',
            $input['status'],
            !empty($input['is_featured']) ? 1 : 0,
            $input['meta_title'] ?? '',
            $input['meta_description'] ?? ''
        ]);
        
        $product_id = $pdo->lastInsertId();
        
        // Insertar tags si existen
        if (!empty($input['tags']) && is_array($input['tags'])) {
            $tag_stmt = $pdo->prepare("INSERT INTO product_tags (product_id, tag) VALUES (?, ?)");
            foreach ($input['tags'] as $tag) {
                $tag_stmt->execute([$product_id, trim($tag)]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Producto creado correctamente',
            'data' => ['id' => $product_id]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handlePut() {
    global $pdo;
    
    if (!hasPermission('products', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para actualizar productos']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verificar que el producto existe
        $check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $check_stmt->execute([$input['id']]);
        if (!$check_stmt->fetch()) {
            throw new Exception('Producto no encontrado');
        }
        
        // Construir query de actualización dinámicamente
        $allowed_fields = [
            'title', 'description', 'short_description', 'sku', 'price', 'sale_price',
            'stock', 'category_id', 'brand_id', 'weight', 'dimensions', 'status',
            'is_featured', 'meta_title', 'meta_description'
        ];
        
        $update_fields = [];
        $params = [];
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $input)) {
                $update_fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (!empty($update_fields)) {
            $update_fields[] = "updated_at = NOW()";
            $params[] = $input['id'];
            
            $sql = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        
        // Actualizar tags si se proporcionan
        if (array_key_exists('tags', $input)) {
            // Eliminar tags existentes
            $pdo->prepare("DELETE FROM product_tags WHERE product_id = ?")->execute([$input['id']]);
            
            // Insertar nuevos tags
            if (!empty($input['tags']) && is_array($input['tags'])) {
                $tag_stmt = $pdo->prepare("INSERT INTO product_tags (product_id, tag) VALUES (?, ?)");
                foreach ($input['tags'] as $tag) {
                    $tag_stmt->execute([$input['id'], trim($tag)]);
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleDelete() {
    global $pdo, $user;
    
    // Debug: Log the request details
    error_log("DELETE request received - User ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role Level: " . ($_SESSION['user_role_level'] ?? 'none'));
    error_log("Session data: " . json_encode($_SESSION));
    error_log("User data: " . json_encode($user ?? null));
    
    // Verificar permisos primero
    $hasPermissions = hasPermission('products', 'delete');
    error_log("hasPermission result: " . ($hasPermissions ? 'true' : 'false'));
    
    if (!$hasPermissions) {
        error_log("Permission denied for user ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role Level: " . ($_SESSION['user_role_level'] ?? 'none'));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para eliminar productos', 'debug' => 'permissions_failed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Request input: " . json_encode($input));
    error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'none'));
    error_log("Received CSRF token: " . ($input['csrf_token'] ?? 'none'));

    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        error_log("CSRF token verification failed. Provided: " . ($input['csrf_token'] ?? 'none') . ", Session: " . ($_SESSION['csrf_token'] ?? 'none'));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido', 'debug' => 'csrf_failed']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Eliminar uno o múltiples productos
        if (!empty($input['id'])) {
            // Eliminar un producto
            $product_ids = [$input['id']];
        } elseif (!empty($input['ids']) && is_array($input['ids'])) {
            // Eliminar múltiples productos
            $product_ids = $input['ids'];
        } else {
            throw new Exception('ID(s) de producto requerido(s)');
        }
        
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        // Obtener imágenes para eliminar archivos
        $images_stmt = $pdo->prepare("SELECT filename FROM product_images WHERE product_id IN ($placeholders)");
        $images_stmt->execute($product_ids);
        $images = $images_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Eliminar registros de la base de datos
        $tables_to_clean = [
            'product_tags' => 'product_id',
            'product_images' => 'product_id', 
            'cart_items' => 'product_id',
            'wishlist_items' => 'product_id',
            'order_items' => 'product_id',
            'products' => 'id'
        ];
        
        foreach ($tables_to_clean as $table => $column) {
            try {
                $delete_stmt = $pdo->prepare("DELETE FROM $table WHERE $column IN ($placeholders)");
                $delete_stmt->execute($product_ids);
                error_log("Deleted from table $table: " . $delete_stmt->rowCount() . " rows");
            } catch (PDOException $e) {
                error_log("Warning: Could not delete from table $table: " . $e->getMessage());
                
                // Si es order_items, puede que la columna se llame diferente
                if ($table === 'order_items') {
                    try {
                        $alt_delete_stmt = $pdo->prepare("DELETE FROM order_items WHERE item_id IN ($placeholders) AND item_type = 'product'");
                        $alt_delete_stmt->execute($product_ids);
                        error_log("Successfully deleted from order_items using item_id column");
                    } catch (PDOException $e2) {
                        error_log("Also failed with alternative column: " . $e2->getMessage());
                    }
                }
            }
        }
        
        // Eliminar archivos de imágenes
        foreach ($images as $filename) {
            $file_path = "../../uploads/products/$filename";
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $pdo->commit();
        
        $count = count($product_ids);
        echo json_encode([
            'success' => true, 
            'message' => "Se " . ($count === 1 ? 'eliminó' : 'eliminaron') . " $count producto" . ($count === 1 ? '' : 's') . " correctamente"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function generateSKU($title) {
    $sku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 8));
    return $sku . '-' . rand(1000, 9999);
}
?>