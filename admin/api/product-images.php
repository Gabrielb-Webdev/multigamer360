<?php
session_start();
require_once '../inc/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role_level'] < 60) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            handleUpload();
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

function handleUpload() {
    global $pdo;
    
    if (!hasPermission('products', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para subir imágenes']);
        return;
    }
    
    // Verificar CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    $product_id = $_POST['product_id'] ?? null;
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
        return;
    }
    
    // Verificar que el producto existe
    $product_check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $product_check->execute([$product_id]);
    if (!$product_check->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        return;
    }
    
    if (empty($_FILES['images'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se seleccionaron imágenes']);
        return;
    }
    
    $upload_dir = '../../uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $uploaded_images = [];
    $errors = [];
    
    try {
        $pdo->beginTransaction();
        
        // Determinar si hay imágenes existentes
        $existing_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
        $existing_count_stmt->execute([$product_id]);
        $existing_count = $existing_count_stmt->fetchColumn();
        
        $is_first_image = $existing_count === 0;
        
        // Procesar cada archivo
        $files = $_FILES['images'];
        $file_count = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $file_count; $i++) {
            $file_name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $file_tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $file_size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $file_error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            $file_type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
            
            // Validaciones
            if ($file_error !== UPLOAD_ERR_OK) {
                $errors[] = "Error al subir $file_name: " . getUploadError($file_error);
                continue;
            }
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "$file_name: Tipo de archivo no permitido";
                continue;
            }
            
            if ($file_size > $max_size) {
                $errors[] = "$file_name: Archivo muy grande (máximo 5MB)";
                continue;
            }
            
            // Verificar que es realmente una imagen
            $image_info = getimagesize($file_tmp);
            if (!$image_info) {
                $errors[] = "$file_name: No es una imagen válida";
                continue;
            }
            
            // Generar nombre único
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = $product_id . '_' . time() . '_' . $i . '.' . strtolower($extension);
            $upload_path = $upload_dir . $new_filename;
            
            // Mover archivo
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $errors[] = "$file_name: Error al guardar archivo";
                continue;
            }
            
            // Redimensionar imagen si es necesario
            try {
                resizeImage($upload_path, 800, 600);
                createThumbnail($upload_path, $upload_dir . 'thumb_' . $new_filename, 300, 300);
            } catch (Exception $e) {
                // Si falla el redimensionamiento, continuar sin error
                error_log("Error al redimensionar imagen: " . $e->getMessage());
            }
            
            // Guardar en base de datos
            $is_main = $is_first_image && $i === 0 ? 1 : 0;
            $sort_order = $existing_count + $i + 1;
            
            $stmt = $pdo->prepare("
                INSERT INTO product_images (product_id, filename, alt_text, is_main, sort_order, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $product_id,
                $new_filename,
                pathinfo($file_name, PATHINFO_FILENAME),
                $is_main,
                $sort_order
            ]);
            
            $uploaded_images[] = [
                'id' => $pdo->lastInsertId(),
                'filename' => $new_filename,
                'alt_text' => pathinfo($file_name, PATHINFO_FILENAME),
                'is_main' => $is_main,
                'url' => '../uploads/products/' . $new_filename
            ];
        }
        
        $pdo->commit();
        
        $response = ['success' => true, 'images' => $uploaded_images];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        
        if (empty($uploaded_images)) {
            $response['success'] = false;
            $response['message'] = 'No se pudo subir ninguna imagen';
            $response['errors'] = $errors;
        } else {
            $response['message'] = count($uploaded_images) . ' imagen(es) subida(s) correctamente';
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Limpiar archivos subidos en caso de error
        foreach ($uploaded_images as $image) {
            $file_path = $upload_dir . $image['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $thumb_path = $upload_dir . 'thumb_' . $image['filename'];
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            }
        }
        
        throw $e;
    }
}

function handleDelete() {
    global $pdo;
    
    if (!hasPermission('products', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para eliminar imágenes']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    $image_id = $input['image_id'] ?? null;
    if (!$image_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de imagen requerido']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Obtener información de la imagen
        $stmt = $pdo->prepare("SELECT filename, product_id, is_main FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();
        
        if (!$image) {
            throw new Exception('Imagen no encontrada');
        }
        
        // No permitir eliminar la imagen principal si hay más imágenes
        if ($image['is_main']) {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
            $count_stmt->execute([$image['product_id']]);
            $total_images = $count_stmt->fetchColumn();
            
            if ($total_images > 1) {
                throw new Exception('No se puede eliminar la imagen principal. Primero establezca otra imagen como principal.');
            }
        }
        
        // Eliminar registro de la base de datos
        $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $delete_stmt->execute([$image_id]);
        
        // Eliminar archivos físicos
        $upload_dir = '../../uploads/products/';
        $main_file = $upload_dir . $image['filename'];
        $thumb_file = $upload_dir . 'thumb_' . $image['filename'];
        
        if (file_exists($main_file)) {
            unlink($main_file);
        }
        if (file_exists($thumb_file)) {
            unlink($thumb_file);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada correctamente']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function resizeImage($source, $max_width, $max_height) {
    $image_info = getimagesize($source);
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($max_width / $width, $max_height / $height);
    if ($ratio >= 1) return; // No redimensionar si ya es más pequeña
    
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Crear imagen fuente
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $src_image = imagecreatefromwebp($source);
            break;
        case IMAGETYPE_GIF:
            $src_image = imagecreatefromgif($source);
            break;
        default:
            return;
    }
    
    // Crear imagen destino
    $dst_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preservar transparencia para PNG y GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dst_image, false);
        imagesavealpha($dst_image, true);
    }
    
    // Redimensionar
    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Guardar imagen redimensionada
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dst_image, $source, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($dst_image, $source, 8);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dst_image, $source, 85);
            break;
        case IMAGETYPE_GIF:
            imagegif($dst_image, $source);
            break;
    }
    
    imagedestroy($src_image);
    imagedestroy($dst_image);
}

function createThumbnail($source, $destination, $width, $height) {
    $image_info = getimagesize($source);
    $src_width = $image_info[0];
    $src_height = $image_info[1];
    $type = $image_info[2];
    
    // Crear imagen fuente
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $src_image = imagecreatefromwebp($source);
            break;
        case IMAGETYPE_GIF:
            $src_image = imagecreatefromgif($source);
            break;
        default:
            return;
    }
    
    // Calcular dimensiones para crop centrado
    $src_ratio = $src_width / $src_height;
    $dst_ratio = $width / $height;
    
    if ($src_ratio > $dst_ratio) {
        // Imagen más ancha
        $new_height = $src_height;
        $new_width = $src_height * $dst_ratio;
        $src_x = ($src_width - $new_width) / 2;
        $src_y = 0;
    } else {
        // Imagen más alta
        $new_width = $src_width;
        $new_height = $src_width / $dst_ratio;
        $src_x = 0;
        $src_y = ($src_height - $new_height) / 2;
    }
    
    // Crear thumbnail
    $dst_image = imagecreatetruecolor($width, $height);
    
    // Preservar transparencia
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dst_image, false);
        imagesavealpha($dst_image, true);
    }
    
    imagecopyresampled($dst_image, $src_image, 0, 0, $src_x, $src_y, $width, $height, $new_width, $new_height);
    
    // Guardar thumbnail
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dst_image, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($dst_image, $destination, 8);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dst_image, $destination, 85);
            break;
        case IMAGETYPE_GIF:
            imagegif($dst_image, $destination);
            break;
    }
    
    imagedestroy($src_image);
    imagedestroy($dst_image);
}

function getUploadError($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo es demasiado grande (límite del servidor)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo es demasiado grande (límite del formulario)';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta directorio temporal';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir archivo';
        case UPLOAD_ERR_EXTENSION:
            return 'Extensión bloqueada';
        default:
            return 'Error desconocido';
    }
}
?>