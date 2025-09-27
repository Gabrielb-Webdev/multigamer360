<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes estar logueado para escribir una reseña']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$title = trim($_POST['title'] ?? '');
$comment = trim($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

// Validaciones
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas']);
    exit;
}

if (empty($title) || strlen($title) < 5) {
    echo json_encode(['success' => false, 'message' => 'El título debe tener al menos 5 caracteres']);
    exit;
}

if (empty($comment) || strlen($comment) < 10) {
    echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 10 caracteres']);
    exit;
}

try {
    // Verificar que el producto existe
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El producto no existe']);
        exit;
    }

    // Verificar si el usuario ya escribió una reseña para este producto
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya has escrito una reseña para este producto']);
        exit;
    }

    // Verificar si es una compra verificada (opcional)
    $is_verified_purchase = false;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
    ");
    $stmt->execute([$user_id, $product_id]);
    $is_verified_purchase = $stmt->fetchColumn() > 0;

    // Insertar la reseña
    $stmt = $pdo->prepare("
        INSERT INTO reviews (product_id, user_id, rating, title, comment, is_verified_purchase, is_approved) 
        VALUES (?, ?, ?, ?, ?, ?, 0)
    ");
    
    $stmt->execute([
        $product_id,
        $user_id,
        $rating,
        $title,
        $comment,
        $is_verified_purchase ? 1 : 0
    ]);

    $review_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true, 
        'message' => 'Tu reseña ha sido enviada y está pendiente de moderación. ¡Gracias por tu opinión!',
        'review_id' => $review_id,
        'is_verified_purchase' => $is_verified_purchase
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    error_log("Error al enviar reseña: " . $e->getMessage());
}
?>
