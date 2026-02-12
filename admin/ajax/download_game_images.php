<?php
/**
 * Descargar imágenes desde URLs externas (RAWG API)
 * y guardarlas en el servidor
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['image_urls']) || !is_array($data['image_urls'])) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionaron URLs de imágenes']);
    exit;
}

$imageUrls = $data['image_urls'];
$uploadDir = '../../uploads/products/';

// Crear directorio si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$downloadedImages = [];
$errors = [];

foreach ($imageUrls as $index => $url) {
    try {
        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "URL inválida: {$url}";
            continue;
        }
        
        // Descargar imagen
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($imageData === false || $httpCode !== 200) {
            $errors[] = "Error al descargar: {$url}";
            continue;
        }
        
        // Verificar que es una imagen válida
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            $errors[] = "Archivo no es una imagen válida: {$url}";
            continue;
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
        }
        
        // Generar nombre único
        $fileName = 'rawg_' . time() . '_' . $index . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Guardar archivo
        if (file_put_contents($filePath, $imageData) !== false) {
            $downloadedImages[] = [
                'filename' => $fileName,
                'path' => 'uploads/products/' . $fileName,
                'size' => filesize($filePath),
                'mime' => $imageInfo['mime'],
                'width' => $imageInfo[0],
                'height' => $imageInfo[1]
            ];
        } else {
            $errors[] = "Error al guardar: {$fileName}";
        }
        
    } catch (Exception $e) {
        $errors[] = "Excepción: " . $e->getMessage();
    }
}

if (empty($downloadedImages)) {
    echo json_encode([
        'success' => false, 
        'message' => 'No se pudo descargar ninguna imagen',
        'errors' => $errors
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => count($downloadedImages) . ' imágenes descargadas correctamente',
    'images' => $downloadedImages,
    'errors' => $errors
]);
