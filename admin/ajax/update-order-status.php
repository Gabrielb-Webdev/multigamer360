<?php
/**
 * AJAX Endpoint para actualizar estado de órdenes
 * Uso: admin/ajax/update-order-status.php
 */

session_start();

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}

require_once '../inc/config.php';
require_once '../inc/functions.php';

// Verificar que el usuario sea administrador
if (!isAdmin()) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Verificar permisos específicos
if (!hasPermission('orders', 'update')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Sin permisos para actualizar órdenes']));
}

header('Content-Type: application/json');

try {
    // Obtener datos del POST
    $order_id = $_POST['order_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    $tracking_number = $_POST['tracking_number'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    if (!$order_id || !$new_status) {
        throw new Exception('Datos incompletos');
    }
    
    // Validar estado
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Estado inválido');
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Obtener orden actual
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Orden no encontrada');
    }
    
    // Preparar datos para actualizar
    $update_fields = ['status = ?', 'updated_at = NOW()'];
    $update_params = [$new_status];
    
    // Si el nuevo estado es 'shipped', actualizar shipped_at
    if ($new_status === 'shipped' && !$order['shipped_at']) {
        $update_fields[] = 'shipped_at = NOW()';
    }
    
    // Si el nuevo estado es 'delivered', actualizar delivered_at
    if ($new_status === 'delivered' && !$order['delivered_at']) {
        $update_fields[] = 'delivered_at = NOW()';
        $update_fields[] = 'completed_at = NOW()';
    }
    
    // Si se proporcionó tracking number
    if ($tracking_number !== null) {
        $update_fields[] = 'tracking_number = ?';
        $update_params[] = $tracking_number;
    }
    
    // Si se proporcionaron notas
    if ($notes !== null) {
        $update_fields[] = 'notes = ?';
        $update_params[] = $notes;
    }
    
    // Agregar order_id al final de los parámetros
    $update_params[] = $order_id;
    
    // Ejecutar actualización
    $update_query = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute($update_params);
    
    // Confirmar transacción
    $pdo->commit();
    
    // Registrar en log
    error_log("Orden #{$order['order_number']} actualizada: {$order['status']} -> {$new_status} por usuario #{$_SESSION['user_id']}");
    
    // Obtener datos actualizados
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $updated_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'order' => $updated_order
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error al actualizar orden: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
