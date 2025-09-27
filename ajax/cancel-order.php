<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';
initSecureSession();

// Verificar que el usuario esté logueado
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

// Incluir base de datos
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit;
}

try {
    // Verificar que el pedido pertenece al usuario y puede ser cancelado
    $stmt = $pdo->prepare("
        SELECT id, status, payment_status 
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    
    // Verificar que el pedido puede ser cancelado
    if (!in_array($order['status'], ['pending', 'processing'])) {
        echo json_encode(['success' => false, 'message' => 'Este pedido no puede ser cancelado']);
        exit;
    }
    
    // Actualizar estado del pedido
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    
    // TODO: Aquí se podría agregar lógica para:
    // - Restaurar stock de productos
    // - Procesar reembolso si ya se pagó
    // - Enviar email de confirmación de cancelación
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido cancelado exitosamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error al cancelar pedido: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>