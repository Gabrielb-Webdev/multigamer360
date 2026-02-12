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
$product_id = intval($data['product_id'] ?? 0);
$image_id = intval($data['image_id'] ?? 0);

if (!$product_id || !$image_id) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Quitar is_primary de todas las imágenes del producto
    $stmt1 = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $stmt1->execute([$product_id]);
    
    // Marcar la nueva imagen como principal
    $stmt2 = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
    $stmt2->execute([$image_id, $product_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Imagen principal actualizada']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
