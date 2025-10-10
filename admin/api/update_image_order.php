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
$images = $data['images'] ?? [];

if (empty($images)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron imágenes']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE product_images SET display_order = ? WHERE id = ?");
    
    foreach ($images as $image) {
        $stmt->execute([$image['order'], $image['id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al actualizar orden: ' . $e->getMessage()]);
}
