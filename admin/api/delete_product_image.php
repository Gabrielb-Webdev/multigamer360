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

$data = json_decode(file_get_contents('php://input'), true);
$image_id = intval($data['image_id'] ?? 0);

if (!$image_id) {
    echo json_encode(['success' => false, 'message' => 'ID de imagen inválido']);
    exit;
}

try {
    // Obtener información de la imagen
    $stmt = $pdo->prepare("SELECT image_url, product_id FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        exit;
    }
    
    // Eliminar archivo físico
    $file_path = '../../uploads/products/' . $image['image_url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Eliminar de la base de datos
    $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
    $delete_stmt->execute([$image_id]);
    
    echo json_encode(['success' => true, 'message' => 'Imagen eliminada correctamente']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar imagen: ' . $e->getMessage()]);
}
