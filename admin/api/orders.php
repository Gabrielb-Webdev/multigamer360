<?php
/**
 * =====================================================
 * MULTIGAMER360 ADMIN - API PEDIDOS
 * =====================================================
 * Version: 1.0.2
 * Fecha última modificación: 08 Mar 2026
 *
 * Descripción: API REST para gestión de pedidos (admin)
 * Autor: MultiGamer360 Development Team
 *
 * Changelog v1.0.2 (08 Mar 2026):
 * - Fix CRÍTICO: Auth checkeaba $_SESSION['user_role_level'] (no existe) → 401 siempre
 * - Corregido a $_SESSION['is_admin'] que es lo que guarda auth.php
 *
 * Changelog v1.0.1 (08 Mar 2026):
 * - Fix: PUT fallaba porque allowed_fields incluía columnas inexistentes (payment_status, shipping_cost, discount_amount)
 * - Fix: INSERT en order_status_history y order_notes lanzaba excepción si las tablas no existen — ahora en try/catch
 * - Fix: Notificación por email también en try/catch para no bloquear la actualización
 */
session_start();
require_once '../inc/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
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

    $new_status = $input['status'] ?? null;
    if (!$new_status) {
        echo json_encode(['success' => false, 'message' => 'Estado requerido']);
        return;
    }

    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido: ' . $new_status]);
        return;
    }

    // Actualizar un pedido individual
    if (!empty($input['id'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $input['id']]);
        $affected = $stmt->rowCount();

        // Verificar el estado real en la BD después del UPDATE
        $check = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $check->execute([$input['id']]);
        $actual_status = $check->fetchColumn();

        if ($actual_status === $new_status) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente', 'status' => $actual_status]);
        } else {
            error_log("Order status update failed - id={$input['id']}, requested=$new_status, actual=$actual_status, affected=$affected");
            echo json_encode(['success' => false, 'message' => "No se pudo cambiar el estado. BD tiene: $actual_status"]);
        }
        return;
    }

    // Actualizar múltiples pedidos
    if (!empty($input['ids']) && is_array($input['ids'])) {
        $placeholders = str_repeat('?,', count($input['ids']) - 1) . '?';
        $params = array_merge([$new_status], $input['ids']);
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id IN ($placeholders)");
        $stmt->execute($params);
        $count = count($input['ids']);
        echo json_encode(['success' => true, 'message' => "$count pedido(s) actualizado(s) correctamente"]);
        return;
    }

    echo json_encode(['success' => false, 'message' => 'ID de pedido requerido']);
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