<?php
/**
 * Descargar una imagen individual desde URL externa
 * (Wikipedia, Wikimedia, Pexels, Unsplash, etc.)
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener URL de la imagen
$imageUrl = $_POST['image_url'] ?? '';

if (empty($imageUrl)) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó URL de imagen']);
    exit;
}

// Validar URL
if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'URL inválida']);
    exit;
}

$uploadDir = '../../uploads/products/';

// Crear directorio si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    // Descargar imagen
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0 (Wikipedia Compatible)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($imageData === false || $httpCode !== 200) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al descargar imagen',
            'http_code' => $httpCode,
            'error' => $error
        ]);
        exit;
    }
    
    // Verificar que es una imagen válida
    $imageInfo = @getimagesizefromstring($imageData);
    if ($imageInfo === false) {
        echo json_encode(['success' => false, 'message' => 'El archivo no es una imagen válida']);
        exit;
    }
    
    // Determinar extensión según el tipo MIME
    $extension = 'jpg';
    switch ($imageInfo['mime']) {
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/jpeg':
        case 'image/jpg':
            $extension = 'jpg';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
        case 'image/svg+xml':
            $extension = 'svg';
            break;
    }
    
    // Generar nombre único
    $fileName = 'wiki_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Guardar archivo
    if (file_put_contents($filePath, $imageData) === false) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Imagen descargada correctamente',
        'path' => 'uploads/products/' . $fileName,
        'filename' => $fileName,
        'size' => filesize($filePath),
        'mime' => $imageInfo['mime'],
        'width' => $imageInfo[0],
        'height' => $imageInfo[1]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Excepción al descargar imagen: ' . $e->getMessage()
    ]);
}
