<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes estar logueado']);
    exit;
}

$review_id = intval($_POST['review_id'] ?? 0);
$vote_type = $_POST['vote_type'] ?? ''; // 'helpful' or 'not_helpful'
$user_id = $_SESSION['user_id'];

if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Reseña inválida']);
    exit;
}

if (!in_array($vote_type, ['helpful', 'not_helpful'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de voto inválido']);
    exit;
}

try {
    // Verificar que la reseña existe y está aprobada
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND is_approved = 1");
    $stmt->execute([$review_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Reseña no encontrada']);
        exit;
    }

    // Verificar si el usuario ya votó por esta reseña
    $stmt = $pdo->prepare("SELECT vote_type FROM review_votes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$user_id, $review_id]);
    $existing_vote = $stmt->fetch();

    if ($existing_vote) {
        if ($existing_vote['vote_type'] === $vote_type) {
            // Remover voto si es el mismo
            $stmt = $pdo->prepare("DELETE FROM review_votes WHERE user_id = ? AND review_id = ?");
            $stmt->execute([$user_id, $review_id]);
            $action = 'removed';
        } else {
            // Cambiar voto
            $stmt = $pdo->prepare("UPDATE review_votes SET vote_type = ? WHERE user_id = ? AND review_id = ?");
            $stmt->execute([$vote_type, $user_id, $review_id]);
            $action = 'changed';
        }
    } else {
        // Nuevo voto
        $stmt = $pdo->prepare("INSERT INTO review_votes (review_id, user_id, vote_type) VALUES (?, ?, ?)");
        $stmt->execute([$review_id, $user_id, $vote_type]);
        $action = 'added';
    }

    // Actualizar contador de votos útiles en la reseña
    $stmt = $pdo->prepare("
        UPDATE reviews 
        SET helpful_count = (
            SELECT COUNT(*) FROM review_votes 
            WHERE review_id = ? AND vote_type = 'helpful'
        )
        WHERE id = ?
    ");
    $stmt->execute([$review_id, $review_id]);

    // Obtener nuevo conteo
    $stmt = $pdo->prepare("SELECT helpful_count FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);
    $helpful_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'helpful_count' => $helpful_count,
        'message' => $action === 'removed' ? 'Voto removido' : 'Voto registrado'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    error_log("Error al votar reseña: " . $e->getMessage());
}
?>