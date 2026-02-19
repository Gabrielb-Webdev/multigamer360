<?php
/**
 * Funciones auxiliares para enviar notificaciones a usuarios
 */

require_once __DIR__ . '/../config/database.php';

class NotificationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Enviar notificación a un usuario específico
     */
    public function sendToUser($user_id, $type, $title, $message, $related_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notifications (user_id, type, title, message, related_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([$user_id, $type, $title, $message, $related_id]);
        } catch (PDOException $e) {
            error_log("Error enviando notificación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificación a todos los usuarios activos
     */
    public function sendToAllUsers($type, $title, $message, $related_id = null) {
        try {
            // Obtener todos los usuarios activos
            $stmt = $this->pdo->query("SELECT id FROM users WHERE is_active = 1");
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($users)) {
                return 0;
            }
            
            // Preparar la consulta de inserción
            $placeholders = [];
            $values = [];
            
            foreach ($users as $user_id) {
                $placeholders[] = "(?, ?, ?, ?, ?)";
                $values[] = $user_id;
                $values[] = $type;
                $values[] = $title;
                $values[] = $message;
                $values[] = $related_id;
            }
            
            $sql = "INSERT INTO user_notifications (user_id, type, title, message, related_id) VALUES " 
                 . implode(', ', $placeholders);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            
            return count($users);
        } catch (PDOException $e) {
            error_log("Error enviando notificaciones masivas: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Notificar sobre un nuevo cupón
     */
    public function notifyNewCoupon($coupon_id, $coupon_code, $coupon_name, $coupon_value, $coupon_type, $notification_type) {
        $discount_text = $coupon_type === 'percentage' 
            ? $coupon_value . '% de descuento' 
            : '$' . number_format($coupon_value, 2) . ' de descuento';
            
        $title = "¡Nuevo cupón disponible!";
        $message = "Tenés un nuevo cupón '{$coupon_code}' - {$coupon_name}: {$discount_text}. ¡No te lo pierdas!";
        
        if ($notification_type === 'all_users') {
            return $this->sendToAllUsers('coupon', $title, $message, $coupon_id);
        }
        
        return 0;
    }
    
    /**
     * Obtener contador de notificaciones no leídas
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error obteniendo contador de notificaciones: " . $e->getMessage());
            return 0;
        }
    }
}

// Crear instancia global
$notificationManager = new NotificationManager($pdo);
?>
