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
    
    // Verificar que la imagen pertenece al producto
    $verify_stmt = $pdo->prepare("SELECT id FROM product_images WHERE id = ? AND product_id = ?");
    $verify_stmt->execute([$image_id, $product_id]);
    
    if (!$verify_stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada o no pertenece al producto']);
        exit;
    }
    
    // Quitar is_primary de todas las imágenes del producto
    $remove_stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $remove_stmt->execute([$product_id]);
    
    // Marcar la imagen seleccionada como principal
    $set_stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
    $set_stmt->execute([$image_id, $product_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Imagen marcada como portada correctamente'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
