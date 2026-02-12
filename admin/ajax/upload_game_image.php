<?php
/**
 * Script para descargar imágenes de juegos desde APIs externas
 * y subirlas al servidor local
 */

session_start();
header('Content-Type: application/json');

// Verificar sesión de administrador
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'download_game_image') {
    $imageUrl = $_POST['image_url'] ?? '';
    $gameName = $_POST['game_name'] ?? 'game';
    
    if (empty($imageUrl)) {
        echo json_encode([
            'success' => false,
            'error' => 'URL de imagen requerida'
        ]);
        exit;
    }
    
    try {
        // Descargar la imagen
        $imageData = @file_get_contents($imageUrl);
        
        if ($imageData === false) {
            throw new Exception('No se pudo descargar la imagen');
        }
        
        // Verificar que es una imagen válida
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Tipo de archivo no válido: ' . $mimeType);
        }
        
        // Determinar extensión
        $extension = match($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        
        // Crear nombre de archivo seguro
        $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $gameName);
        $safeName = strtolower(substr($safeName, 0, 50));
        $fileName = $safeName . '_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Directorio de destino
        $uploadDir = '../../uploads/productos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . $fileName;
        
        // Guardar imagen
        if (file_put_contents($filePath, $imageData) === false) {
            throw new Exception('Error al guardar la imagen');
        }
        
        // Optimizar imagen (reducir tamaño si es muy grande)
        optimizeImage($filePath, $mimeType);
        
        echo json_encode([
            'success' => true,
            'file_path' => 'uploads/productos/' . $fileName,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'size' => filesize($filePath)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

/**
 * Optimizar imagen para reducir tamaño
 */
function optimizeImage($filePath, $mimeType) {
    $maxWidth = 1920;
    $maxHeight = 1080;
    $quality = 85;
    
    try {
        // Obtener dimensiones originales
        list($width, $height) = getimagesize($filePath);
        
        // No optimizar si es pequeña
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Crear imagen desde archivo
        $source = match($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($filePath),
            'image/png' => imagecreatefrompng($filePath),
            'image/webp' => imagecreatefromwebp($filePath),
            default => null
        };
        
        if (!$source) return;
        
        // Crear imagen redimensionada
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Guardar imagen optimizada
        match($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($resized, $filePath, $quality),
            'image/png' => imagepng($resized, $filePath, 9),
            'image/webp' => imagewebp($resized, $filePath, $quality),
            default => null
        };
        
        // Liberar memoria
        imagedestroy($source);
        imagedestroy($resized);
        
    } catch (Exception $e) {
        error_log('Error optimizando imagen: ' . $e->getMessage());
    }
}

echo json_encode([
    'success' => false,
    'error' => 'Acción no válida'
]);
