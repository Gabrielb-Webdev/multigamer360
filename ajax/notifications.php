<?php
/**
 * API para gestión de notificaciones de usuario
 * Endpoints:
 * - GET: Obtener notificaciones del usuario
 * - POST: Marcar como leída
 * - DELETE: Eliminar notificación
 */

session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener notificaciones
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $sql = "SELECT n.*, 
                    CASE 
                        WHEN n.type = 'coupon' THEN c.code
                        ELSE NULL
                    END as coupon_code
                    FROM user_notifications n
                    LEFT JOIN coupons c ON n.type = 'coupon' AND n.related_id = c.id
                    WHERE n.user_id = ?";
            
            $params = [$user_id];
            
            if ($unread_only) {
                $sql .= " AND n.is_read = 0";
            }
            
            $sql .= " ORDER BY n.created_at DESC LIMIT ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($params, [$limit]));
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar no leídas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            $unread_count = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            break;
            
        case 'POST':
            // Marcar como leída
            $data = json_decode(file_get_contents('php://input'), true);
            $notification_id = $data['notification_id'] ?? null;
            $mark_all = $data['mark_all_read'] ?? false;
            
            if ($mark_all) {
                $stmt = $pdo->prepare("
                    UPDATE user_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE user_id = ? AND is_read = 0
                ");
                $stmt->execute([$user_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Todas las notificaciones marcadas como leídas',
                    'updated' => $stmt->rowCount()
                ]);
            } elseif ($notification_id) {
                $stmt = $pdo->prepare("
                    UPDATE user_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$notification_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Notificación marcada como leída']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Notificación no encontrada']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            }
            break;
            
        case 'DELETE':
            // Eliminar notificación
            $data = json_decode(file_get_contents('php://input'), true);
            $notification_id = $data['notification_id'] ?? null;
            
            if ($notification_id) {
                $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$notification_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Notificación eliminada']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Notificación no encontrada']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de notificación requerido']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error en notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>
