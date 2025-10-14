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
$product_id = $data['product_id'] ?? null;
$images = $data['order'] ?? $data['images'] ?? []; // Soportar ambos formatos

if (empty($images)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron imágenes']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE product_images SET display_order = ?, is_primary = ? WHERE id = ?");
    
    foreach ($images as $index => $image) {
        // La primera imagen (orden 1) es la principal
        $is_primary = ($image['order'] == 1) ? 1 : 0;
        $stmt->execute([$image['order'], $is_primary, $image['id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al actualizar orden: ' . $e->getMessage()]);
}
