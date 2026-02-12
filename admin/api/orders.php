<?php
session_start();
require_once '../inc/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role_level'] < 60) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet();
            break;
            
        case 'PUT':
            handlePut();
            break;
            
        case 'DELETE':
            handleDelete();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleGet() {
    global $pdo;
    
    if (!hasPermission('orders', 'read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para ver pedidos']);
        return;
    }
    
    if (isset($_GET['id'])) {
        // Obtener pedido específico
        $stmt = $pdo->prepare("
            SELECT o.*, u.username, u.email as user_email, u.phone as user_phone
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $order = $stmt->fetch();
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
            return;
        }
        
        // Obtener items del pedido
        $items_stmt = $pdo->prepare("
            SELECT oi.*, p.title as product_title, p.sku,
                   (SELECT filename FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as product_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $items_stmt->execute([$_GET['id']]);
        $order['items'] = $items_stmt->fetchAll();
        
        // Obtener historial de estado
        $history_stmt = $pdo->prepare("
            SELECT osh.*, u.username as admin_name
            FROM order_status_history osh
            LEFT JOIN users u ON osh.admin_id = u.id
            WHERE osh.order_id = ?
            ORDER BY osh.created_at DESC
        ");
        $history_stmt->execute([$_GET['id']]);
        $order['status_history'] = $history_stmt->fetchAll();
        
        // Obtener notas
        $notes_stmt = $pdo->prepare("
            SELECT on.*, u.username as admin_name
            FROM order_notes on
            LEFT JOIN users u ON on.admin_id = u.id
            WHERE on.order_id = ?
            ORDER BY on.created_at DESC
        ");
        $notes_stmt->execute([$_GET['id']]);
        $order['notes'] = $notes_stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $order]);
        
    } elseif (isset($_GET['stats'])) {
        // Obtener estadísticas
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $stats_query = "
            SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ";
        
        $stmt = $pdo->prepare($stats_query);
        $stmt->execute([$date_from, $date_to]);
        $stats = $stmt->fetch();
        
        // Estadísticas por día (últimos 7 días)
        $daily_stats_query = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(total_amount) as daily_revenue
            FROM orders 
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        $daily_stmt = $pdo->query($daily_stats_query);
        $daily_stats = $daily_stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'daily' => $daily_stats
            ]
        ]);
        
    } else {
        // Listar pedidos con filtros
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, max(10, intval($_GET['per_page'] ?? 25)));
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = ['1=1'];
        $params = [];
        
        // Filtros
        if (!empty($_GET['search'])) {
            $where_conditions[] = "(o.id LIKE ? OR o.order_number LIKE ? OR o.customer_email LIKE ? OR o.customer_name LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($_GET['status'])) {
            $where_conditions[] = "o.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (!empty($_GET['payment_status'])) {
            $where_conditions[] = "o.payment_status = ?";
            $params[] = $_GET['payment_status'];
        }
        
        if (!empty($_GET['date_from'])) {
            $where_conditions[] = "DATE(o.created_at) >= ?";
            $params[] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $where_conditions[] = "DATE(o.created_at) <= ?";
            $params[] = $_GET['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Obtener total
        $count_query = "SELECT COUNT(*) as total FROM orders o WHERE $where_clause";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Obtener pedidos
        $query = "
            SELECT o.*, u.username, u.email as user_email,
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE $where_clause
            ORDER BY o.created_at DESC
            LIMIT $per_page OFFSET $offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $orders,
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page)
            ]
        ]);
    }
}

function handlePut() {
    global $pdo;
    
    if (!hasPermission('orders', 'update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para actualizar pedidos']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        if (!empty($input['id'])) {
            // Actualizar un pedido
            $order_id = $input['id'];
            $order_ids = [$order_id];
        } elseif (!empty($input['ids']) && is_array($input['ids'])) {
            // Actualizar múltiples pedidos
            $order_ids = $input['ids'];
        } else {
            throw new Exception('ID(s) de pedido requerido(s)');
        }
        
        // Campos permitidos para actualización
        $allowed_fields = ['status', 'payment_status', 'shipping_cost', 'discount_amount', 'notes'];
        $update_fields = [];
        $params = [];
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $input)) {
                $update_fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($update_fields)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        $update_fields[] = "updated_at = NOW()";
        
        // Actualizar pedidos
        $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
        $params = array_merge($params, $order_ids);
        
        $sql = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Registrar historial de estado si se cambió el estado
        if (isset($input['status'])) {
            foreach ($order_ids as $order_id) {
                // Obtener estado anterior
                $old_status_stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                $old_status_stmt->execute([$order_id]);
                $old_status = $old_status_stmt->fetchColumn();
                
                if ($old_status && $old_status !== $input['status']) {
                    $history_stmt = $pdo->prepare("
                        INSERT INTO order_status_history (order_id, old_status, new_status, admin_id, note, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $history_stmt->execute([
                        $order_id,
                        $old_status,
                        $input['status'],
                        $_SESSION['user_id'],
                        $input['note'] ?? ''
                    ]);
                }
            }
        }
        
        // Agregar nota si se especifica
        if (!empty($input['note'])) {
            foreach ($order_ids as $order_id) {
                $note_stmt = $pdo->prepare("
                    INSERT INTO order_notes (order_id, admin_id, note, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $note_stmt->execute([$order_id, $_SESSION['user_id'], $input['note']]);
            }
        }
        
        // Enviar notificación por email si se especifica
        if (!empty($input['send_notification']) && isset($input['status'])) {
            foreach ($order_ids as $order_id) {
                sendOrderStatusNotification($order_id, $input['status']);
            }
        }
        
        $pdo->commit();
        
        $count = count($order_ids);
        echo json_encode([
            'success' => true,
            'message' => "Se " . ($count === 1 ? 'actualizó' : 'actualizaron') . " $count pedido" . ($count === 1 ? '' : 's') . " correctamente"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleDelete() {
    global $pdo;
    
    if (!hasPermission('orders', 'delete')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para eliminar pedidos']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Obtener IDs de pedidos a eliminar
        if (!empty($input['id'])) {
            $order_ids = [$input['id']];
        } elseif (!empty($input['ids']) && is_array($input['ids'])) {
            $order_ids = $input['ids'];
        } else {
            throw new Exception('ID(s) de pedido requerido(s)');
        }
        
        $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
        
        // Verificar que los pedidos se pueden eliminar (solo borradores o cancelados)
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE id IN ($placeholders) 
            AND status NOT IN ('cancelled', 'draft')
        ");
        $check_stmt->execute($order_ids);
        $active_orders = $check_stmt->fetchColumn();
        
        if ($active_orders > 0) {
            throw new Exception('Solo se pueden eliminar pedidos cancelados o en borrador');
        }
        
        // Eliminar en orden: notas, historial, items, pedidos
        $tables = [
            'order_notes' => 'order_id',
            'order_status_history' => 'order_id',
            'order_items' => 'order_id',
            'orders' => 'id'
        ];
        
        foreach ($tables as $table => $id_field) {
            $delete_stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field IN ($placeholders)");
            $delete_stmt->execute($order_ids);
        }
        
        $pdo->commit();
        
        $count = count($order_ids);
        echo json_encode([
            'success' => true,
            'message' => "Se " . ($count === 1 ? 'eliminó' : 'eliminaron') . " $count pedido" . ($count === 1 ? '' : 's') . " correctamente"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function sendOrderStatusNotification($order_id, $new_status) {
    global $pdo;
    
    try {
        // Obtener información del pedido y cliente
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) return false;
        
        $customer_email = $order['user_email'] ?: $order['customer_email'];
        if (!$customer_email) return false;
        
        // Configurar mensaje según el estado
        $status_messages = [
            'processing' => [
                'subject' => 'Tu pedido está siendo procesado',
                'message' => 'Hemos comenzado a procesar tu pedido. Te notificaremos cuando esté listo para envío.'
            ],
            'shipped' => [
                'subject' => 'Tu pedido ha sido enviado',
                'message' => 'Tu pedido ha sido enviado y está en camino. Recibirás el producto en los próximos días.'
            ],
            'delivered' => [
                'subject' => 'Tu pedido ha sido entregado',
                'message' => 'Tu pedido ha sido entregado exitosamente. Esperamos que disfrutes tu compra.'
            ],
            'cancelled' => [
                'subject' => 'Tu pedido ha sido cancelado',
                'message' => 'Lamentamos informarte que tu pedido ha sido cancelado. Si tienes dudas, contáctanos.'
            ]
        ];
        
        if (!isset($status_messages[$new_status])) return false;
        
        $status_info = $status_messages[$new_status];
        
        // Aquí iría la lógica de envío de email
        // Por ahora solo registramos en log
        error_log("Notificación enviada: Pedido #{$order['order_number']} - Estado: $new_status - Email: $customer_email");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando notificación: " . $e->getMessage());
        return false;
    }
}
?>