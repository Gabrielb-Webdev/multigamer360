<?php
require_once '../inc/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar permisos
if (!hasPermission('products', 'update')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

if (empty($_FILES['images']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No se enviaron imágenes']);
    exit;
}

try {
    $upload_dir = '../../uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Obtener el orden máximo actual
    $order_stmt = $pdo->prepare("SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = ?");
    $order_stmt->execute([$product_id]);
    $current_max_order = $order_stmt->fetchColumn() ?? 0;
    
    // Verificar si ya existe una imagen principal
    $has_primary_stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
    $has_primary_stmt->execute([$product_id]);
    $has_primary = $has_primary_stmt->fetchColumn() > 0;
    
    $uploaded_count = 0;
    $errors = [];
    
    foreach ($_FILES['images']['name'] as $key => $name) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            $errors[] = "Error al subir: $name";
            continue;
        }
        
        // Validar tamaño (5MB máximo)
        if ($_FILES['images']['size'][$key] > 5 * 1024 * 1024) {
            $errors[] = "Archivo muy grande: $name (máximo 5MB)";
            continue;
        }
        
        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = $_FILES['images']['type'][$key];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Tipo no permitido: $name (solo JPG, PNG, WebP)";
            continue;
        }
        
        $tmp_name = $_FILES['images']['tmp_name'][$key];
        $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $new_filename = uniqid('prod_') . '.' . $file_extension;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($tmp_name, $destination)) {
            $current_max_order++;
            
            // Solo marcar como principal si es la primera imagen del producto
            $is_primary = (!$has_primary && $key == 0) ? 1 : 0;
            if ($is_primary) {
                $has_primary = true; // Marcar que ya hay una principal
            }
            
            $insert_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$product_id, $new_filename, $is_primary, $current_max_order]);
            
            $uploaded_count++;
        } else {
            $errors[] = "Error al mover archivo: $name";
        }
    }
    
    if ($uploaded_count > 0) {
        $message = "$uploaded_count imagen(es) subida(s) correctamente";
        if (!empty($errors)) {
            $message .= ". Errores: " . implode(", ", $errors);
        }
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'uploaded' => $uploaded_count,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo subir ninguna imagen',
            'errors' => $errors
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
