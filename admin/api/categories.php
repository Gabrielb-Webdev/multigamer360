<?php
require_once '../config/database.php';
require_once '../config/session_config.php';
require_once 'inc/auth.php';

header('Content-Type: application/json');

// Verificar CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !verifyCSRFToken($_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Error en API de categorías: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleGetRequest() {
    global $pdo;
    
    if (!hasPermission('categories', 'read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para leer categorías']);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Obtener categoría específica
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   p.name as parent_name,
                   (SELECT COUNT(*) FROM categories child WHERE child.parent_id = c.id) as children_count,
                   (SELECT COUNT(*) FROM products prod WHERE prod.category_id = c.id) as products_count
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        
        if ($category) {
            echo json_encode(['success' => true, 'category' => $category]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        }
    } else {
        // Listar categorías con filtros
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $parent_id = $_GET['parent_id'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, max(10, intval($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = ['1=1'];
        $params = [];
        
        if ($search) {
            $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ? OR c.slug LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if ($status !== '') {
            $where_conditions[] = "c.is_active = ?";
            $params[] = $status;
        }
        
        if ($parent_id !== '') {
            if ($parent_id === '0') {
                $where_conditions[] = "c.parent_id IS NULL";
            } else {
                $where_conditions[] = "c.parent_id = ?";
                $params[] = $parent_id;
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Contar total
        $count_query = "SELECT COUNT(*) as total FROM categories c WHERE $where_clause";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Obtener categorías
        $query = "
            SELECT c.*, 
                   p.name as parent_name,
                   (SELECT COUNT(*) FROM categories child WHERE child.parent_id = c.id) as children_count,
                   (SELECT COUNT(*) FROM products prod WHERE prod.category_id = c.id) as products_count
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE $where_clause
            ORDER BY c.sort_order ASC, c.name ASC
            LIMIT $per_page OFFSET $offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'pages' => ceil($total / $per_page)
            ]
        ]);
    }
}

function handlePostRequest() {
    global $pdo;
    
    // Manejar diferentes acciones POST
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'remove_image') {
        handleRemoveImage();
        return;
    }
    
    if (!hasPermission('categories', 'create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para crear categorías']);
        return;
    }
    
    $validation = validateCategoryData($_POST);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validation['errors']]);
        return;
    }
    
    $data = $validation['data'];
    
    // Verificar slug único
    $slug_check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
    $slug_check->execute([$data['slug']]);
    if ($slug_check->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El slug ya existe', 'errors' => ['slug' => 'Ya existe una categoría con este slug']]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Procesar imágenes
        $image_path = '';
        $banner_path = '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_result = uploadCategoryImage($_FILES['image'], 'image');
            if ($image_result['success']) {
                $image_path = $image_result['filename'];
            } else {
                throw new Exception($image_result['message']);
            }
        }
        
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $banner_result = uploadCategoryImage($_FILES['banner_image'], 'banner');
            if ($banner_result['success']) {
                $banner_path = $banner_result['filename'];
            } else {
                throw new Exception($banner_result['message']);
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO categories (
                name, slug, description, parent_id, image, banner_image, 
                icon_class, meta_title, meta_description, meta_keywords,
                is_active, is_featured, sort_order, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['parent_id'],
            $image_path,
            $banner_path,
            $data['icon_class'],
            $data['meta_title'],
            $data['meta_description'],
            $data['meta_keywords'],
            $data['is_active'],
            $data['is_featured'],
            $data['sort_order']
        ]);
        
        $category_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'category_id' => $category_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        
        // Limpiar imágenes subidas si hay error
        if (!empty($image_path)) {
            @unlink("../uploads/categories/$image_path");
        }
        if (!empty($banner_path)) {
            @unlink("../uploads/categories/$banner_path");
        }
        
        error_log("Error al crear categoría: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear categoría: ' . $e->getMessage()]);
    }
}

function handlePutRequest() {
    global $pdo;
    
    // Leer datos PUT
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Manejar operaciones bulk
    if (isset($data['ids']) && is_array($data['ids'])) {
        handleBulkUpdate($data);
        return;
    }
    
    // Parse multipart form data para PUT con archivos
    if (isset($_POST['id'])) {
        $data = $_POST;
        $category_id = intval($data['id']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
        return;
    }
    
    if (!hasPermission('categories', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para actualizar categorías']);
        return;
    }
    
    $validation = validateCategoryData($data);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validation['errors']]);
        return;
    }
    
    $update_data = $validation['data'];
    
    // Verificar que la categoría existe
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $current_category = $stmt->fetch();
    
    if (!$current_category) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        return;
    }
    
    // Verificar slug único (excluyendo la categoría actual)
    $slug_check = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $slug_check->execute([$update_data['slug'], $category_id]);
    if ($slug_check->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El slug ya existe', 'errors' => ['slug' => 'Ya existe una categoría con este slug']]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Procesar nuevas imágenes
        $image_path = $current_category['image'];
        $banner_path = $current_category['banner_image'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_result = uploadCategoryImage($_FILES['image'], 'image');
            if ($image_result['success']) {
                // Eliminar imagen anterior
                if ($current_category['image']) {
                    @unlink("../uploads/categories/" . $current_category['image']);
                }
                $image_path = $image_result['filename'];
            } else {
                throw new Exception($image_result['message']);
            }
        }
        
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $banner_result = uploadCategoryImage($_FILES['banner_image'], 'banner');
            if ($banner_result['success']) {
                // Eliminar banner anterior
                if ($current_category['banner_image']) {
                    @unlink("../uploads/categories/" . $current_category['banner_image']);
                }
                $banner_path = $banner_result['filename'];
            } else {
                throw new Exception($banner_result['message']);
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE categories SET 
                name = ?, slug = ?, description = ?, parent_id = ?, 
                image = ?, banner_image = ?, icon_class = ?,
                meta_title = ?, meta_description = ?, meta_keywords = ?,
                is_active = ?, is_featured = ?, sort_order = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $update_data['name'],
            $update_data['slug'],
            $update_data['description'],
            $update_data['parent_id'],
            $image_path,
            $banner_path,
            $update_data['icon_class'],
            $update_data['meta_title'],
            $update_data['meta_description'],
            $update_data['meta_keywords'],
            $update_data['is_active'],
            $update_data['is_featured'],
            $update_data['sort_order'],
            $category_id
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Categoría actualizada correctamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error al actualizar categoría: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar categoría: ' . $e->getMessage()]);
    }
}

function handleDeleteRequest() {
    global $pdo;
    
    if (!hasPermission('categories', 'delete')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para eliminar categorías']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['ids']) && is_array($data['ids'])) {
        // Eliminar múltiples categorías
        $ids = array_map('intval', $data['ids']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        try {
            // Verificar que no tengan productos
            $check_stmt = $pdo->prepare("
                SELECT c.id, c.name, COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                WHERE c.id IN ($placeholders)
                GROUP BY c.id, c.name
                HAVING product_count > 0
            ");
            $check_stmt->execute($ids);
            $categories_with_products = $check_stmt->fetchAll();
            
            if (!empty($categories_with_products)) {
                $names = array_column($categories_with_products, 'name');
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pueden eliminar categorías con productos: ' . implode(', ', $names)
                ]);
                return;
            }
            
            $pdo->beginTransaction();
            
            // Obtener imágenes para eliminar
            $images_stmt = $pdo->prepare("SELECT image, banner_image FROM categories WHERE id IN ($placeholders)");
            $images_stmt->execute($ids);
            $images = $images_stmt->fetchAll();
            
            // Eliminar categorías
            $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id IN ($placeholders)");
            $delete_stmt->execute($ids);
            
            // Eliminar imágenes físicas
            foreach ($images as $image_row) {
                if ($image_row['image']) {
                    @unlink("../uploads/categories/" . $image_row['image']);
                }
                if ($image_row['banner_image']) {
                    @unlink("../uploads/categories/" . $image_row['banner_image']);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Categorías eliminadas correctamente'
            ]);
            
        } catch (Exception $e) {
            $pdo->rollback();
            error_log("Error al eliminar categorías: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar categorías']);
        }
        
    } else {
        // Eliminar categoría individual
        $category_id = intval($data['id'] ?? 0);
        
        if (!$category_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
            return;
        }
        
        try {
            // Verificar que no tenga productos
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $check_stmt->execute([$category_id]);
            $product_count = $check_stmt->fetch()['count'];
            
            if ($product_count > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "No se puede eliminar la categoría porque tiene $product_count producto(s) asociado(s)"
                ]);
                return;
            }
            
            $pdo->beginTransaction();
            
            // Obtener imágenes antes de eliminar
            $images_stmt = $pdo->prepare("SELECT image, banner_image FROM categories WHERE id = ?");
            $images_stmt->execute([$category_id]);
            $images = $images_stmt->fetch();
            
            // Eliminar categoría
            $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->execute([$category_id]);
            
            if ($delete_stmt->rowCount() === 0) {
                throw new Exception('Categoría no encontrada');
            }
            
            // Eliminar imágenes físicas
            if ($images) {
                if ($images['image']) {
                    @unlink("../uploads/categories/" . $images['image']);
                }
                if ($images['banner_image']) {
                    @unlink("../uploads/categories/" . $images['banner_image']);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Categoría eliminada correctamente'
            ]);
            
        } catch (Exception $e) {
            $pdo->rollback();
            error_log("Error al eliminar categoría: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar categoría']);
        }
    }
}

function handleBulkUpdate($data) {
    global $pdo;
    
    if (!hasPermission('categories', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para actualizar categorías']);
        return;
    }
    
    $ids = array_map('intval', $data['ids']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    try {
        if (isset($data['is_active'])) {
            $stmt = $pdo->prepare("UPDATE categories SET is_active = ?, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$data['is_active']], $ids));
            
            $action = $data['is_active'] ? 'activadas' : 'desactivadas';
            echo json_encode([
                'success' => true,
                'message' => count($ids) . " categoría(s) $action correctamente"
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        error_log("Error en actualización bulk: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar categorías']);
    }
}

function handleRemoveImage() {
    global $pdo;
    
    if (!hasPermission('categories', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para actualizar categorías']);
        return;
    }
    
    $category_id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? '';
    
    if (!$category_id || !in_array($type, ['image', 'banner_image'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    try {
        // Obtener imagen actual
        $stmt = $pdo->prepare("SELECT $type FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $current_image = $stmt->fetchColumn();
        
        if ($current_image) {
            // Actualizar base de datos
            $update_stmt = $pdo->prepare("UPDATE categories SET $type = '', updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$category_id]);
            
            // Eliminar archivo físico
            @unlink("../uploads/categories/$current_image");
            
            echo json_encode([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No hay imagen para eliminar'
            ]);
        }
    } catch (Exception $e) {
        error_log("Error al eliminar imagen: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar imagen']);
    }
}

function validateCategoryData($data) {
    $errors = [];
    $clean_data = [];
    
    // Name (requerido)
    $name = trim($data['name'] ?? '');
    if (empty($name)) {
        $errors['name'] = 'El nombre es requerido';
    } elseif (strlen($name) > 255) {
        $errors['name'] = 'El nombre no puede exceder 255 caracteres';
    } else {
        $clean_data['name'] = $name;
    }
    
    // Slug (requerido)
    $slug = trim($data['slug'] ?? '');
    if (empty($slug)) {
        $errors['slug'] = 'El slug es requerido';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors['slug'] = 'El slug solo puede contener letras minúsculas, números y guiones';
    } elseif (strlen($slug) > 255) {
        $errors['slug'] = 'El slug no puede exceder 255 caracteres';
    } else {
        $clean_data['slug'] = $slug;
    }
    
    // Description (opcional)
    $clean_data['description'] = trim($data['description'] ?? '');
    
    // Parent ID (opcional)
    $parent_id = $data['parent_id'] ?? null;
    $clean_data['parent_id'] = empty($parent_id) ? null : intval($parent_id);
    
    // Icon class (opcional)
    $clean_data['icon_class'] = trim($data['icon_class'] ?? '');
    
    // Meta fields (opcionales)
    $clean_data['meta_title'] = trim($data['meta_title'] ?? '');
    $clean_data['meta_description'] = trim($data['meta_description'] ?? '');
    $clean_data['meta_keywords'] = trim($data['meta_keywords'] ?? '');
    
    // Booleans
    $clean_data['is_active'] = isset($data['is_active']) ? 1 : 0;
    $clean_data['is_featured'] = isset($data['is_featured']) ? 1 : 0;
    
    // Sort order
    $clean_data['sort_order'] = max(0, intval($data['sort_order'] ?? 0));
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $clean_data
    ];
}

function uploadCategoryImage($file, $type) {
    $upload_dir = '../uploads/categories/';
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validar archivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Use JPG, PNG o WebP.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 2MB.'];
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Error al subir el archivo.'];
    }
}
?>