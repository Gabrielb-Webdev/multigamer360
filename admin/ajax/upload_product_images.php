<?php
/**
 * Subir imágenes de producto durante la revisión
 */

require_once '../../config/database_production.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['images'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron imágenes']);
    exit;
}

$uploadDir = '../../uploads/products/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadedImages = [];
$errors = [];

$files = $_FILES['images'];
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
    $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
    
    // Validar errores
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "Error al subir {$fileName}";
        continue;
    }
    
    // Validar tamaño (máximo 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        $errors[] = "{$fileName} es muy grande (máximo 5MB)";
        continue;
    }
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "{$fileName} no es un tipo de imagen válido";
        continue;
    }
    
    // Generar nombre único
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = 'product_' . time() . '_' . $i . '.' . $extension;
    $uploadPath = $uploadDir . $newFileName;
    
    // Mover archivo
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        $uploadedImages[] = 'uploads/products/' . $newFileName;
    } else {
        $errors[] = "Error al guardar {$fileName}";
    }
}

if (!empty($uploadedImages)) {
    echo json_encode([
        'success' => true,
        'images' => $uploadedImages,
        'errors' => $errors,
        'message' => count($uploadedImages) . ' imágenes subidas correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'errors' => $errors,
        'message' => 'No se pudieron subir las imágenes'
    ]);
}
