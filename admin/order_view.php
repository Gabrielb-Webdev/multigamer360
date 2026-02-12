<?php
// Obtener ID del pedido
$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    $_SESSION['error'] = 'ID de pedido requerido';
    header('Location: orders.php');
    exit;
}

$page_title = 'Detalle de Pedido #' . $order_id;

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('orders', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver pedidos';
    header('Location: index.php');
    exit;
}

try {
    // Obtener información del pedido
    $order_stmt = $pdo->prepare("
        SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as username, u.email as user_email, u.phone as user_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch();
    
    if (!$order) {
        $_SESSION['error'] = 'Pedido no encontrado';
        header('Location: orders.php');
        exit;
    }
    
    // Obtener items del pedido
    $items_stmt = $pdo->prepare("
        SELECT oi.*, p.title as product_title, p.sku, p.image_url,
               (SELECT filename FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll();
    
    // Obtener historial de estado
    $history_stmt = $pdo->prepare("
        SELECT * FROM order_status_history 
        WHERE order_id = ? 
        ORDER BY created_at DESC
    ");
    $history_stmt->execute([$order_id]);
    $status_history = $history_stmt->fetchAll();
    
    // Obtener notas
    $notes_stmt = $pdo->prepare("
        SELECT on.*, CONCAT(u.first_name, ' ', u.last_name) as admin_name
        FROM order_notes on
        LEFT JOIN users u ON on.admin_id = u.id
        WHERE on.order_id = ?
        ORDER BY on.created_at DESC
    ");
    $notes_stmt->execute([$order_id]);
    $order_notes = $notes_stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar pedido: ' . $e->getMessage();
    header('Location: orders.php');
    exit;
}

// Procesar actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de seguridad inválido');
        }
        
        if (!hasPermission('orders', 'update')) {
            throw new Exception('Sin permisos para actualizar pedidos');
        }
        
        $new_status = $_POST['status'];
        $note = trim($_POST['note'] ?? '');
        
        $pdo->beginTransaction();
        
        // Actualizar estado del pedido
        $update_stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_status, $order_id]);
        
        // Registrar historial
        $history_stmt = $pdo->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, admin_id, note, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $history_stmt->execute([$order_id, $order['status'], $new_status, $_SESSION['user_id'], $note]);
        
        // Agregar nota si se proporcionó
        if ($note) {
            $note_stmt = $pdo->prepare("
                INSERT INTO order_notes (order_id, admin_id, note, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $note_stmt->execute([$order_id, $_SESSION['user_id'], $note]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = 'Estado del pedido actualizado correctamente';
        header("Location: order_view.php?id=$order_id");
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
}

// Procesar nueva nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de seguridad inválido');
        }
        
        if (!hasPermission('orders', 'update')) {
            throw new Exception('Sin permisos para agregar notas');
        }
        
        $note = trim($_POST['note']);
        if (!$note) {
            throw new Exception('La nota no puede estar vacía');
        }
        
        $note_stmt = $pdo->prepare("
            INSERT INTO order_notes (order_id, admin_id, note, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $note_stmt->execute([$order_id, $_SESSION['user_id'], $note]);
        
        $_SESSION['success'] = 'Nota agregada correctamente';
        header("Location: order_view.php?id=$order_id");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<div class="row">
    <!-- Información principal del pedido -->
    <div class="col-lg-8">
        <!-- Encabezado del pedido -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Pedido #<?php echo $order['order_number']; ?>
                    </h5>
                    
                    <div class="d-flex gap-2">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <button class="btn btn-outline-info" onclick="printOrder()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <?php if ($order['customer_email']): ?>
                        <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información del Cliente</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <?php if ($order['customer_phone']): ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($order['username']): ?>
                        <p class="mb-0">
                            <span class="badge bg-info">Usuario: <?php echo htmlspecialchars($order['username']); ?></span>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Detalles del Pedido</h6>
                        <p class="mb-1"><strong>ID:</strong> <?php echo $order['id']; ?></p>
                        <p class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        <p class="mb-1"><strong>Estado:</strong> 
                            <?php
                            $status_config = [
                                'pending' => ['class' => 'bg-warning text-dark', 'text' => 'Pendiente'],
                                'processing' => ['class' => 'bg-info', 'text' => 'Procesando'],
                                'shipped' => ['class' => 'bg-primary', 'text' => 'Enviado'],
                                'delivered' => ['class' => 'bg-success', 'text' => 'Entregado'],
                                'cancelled' => ['class' => 'bg-danger', 'text' => 'Cancelado'],
                                'completed' => ['class' => 'bg-success', 'text' => 'Completado']
                            ];
                            $status_info = $status_config[$order['status']] ?? ['class' => 'bg-secondary', 'text' => ucfirst($order['status'])];
                            ?>
                            <span class="badge <?php echo $status_info['class']; ?>">
                                <?php echo $status_info['text']; ?>
                            </span>
                        </p>
                        <p class="mb-0"><strong>Pago:</strong> 
                            <?php
                            $payment_config = [
                                'pending' => ['class' => 'bg-warning text-dark', 'text' => 'Pendiente'],
                                'paid' => ['class' => 'bg-success', 'text' => 'Pagado'],
                                'failed' => ['class' => 'bg-danger', 'text' => 'Fallido'],
                                'refunded' => ['class' => 'bg-secondary', 'text' => 'Reembolsado']
                            ];
                            $payment_info = $payment_config[$order['payment_status']] ?? ['class' => 'bg-secondary', 'text' => ucfirst($order['payment_status'])];
                            ?>
                            <span class="badge <?php echo $payment_info['class']; ?>">
                                <?php echo $payment_info['text']; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Productos del pedido -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i>
                    Productos (<?php echo count($order_items); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $subtotal = 0; ?>
                            <?php foreach ($order_items as $item): ?>
                            <?php $item_total = $item['price'] * $item['quantity']; ?>
                            <?php $subtotal += $item_total; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['product_image']): ?>
                                        <img src="../uploads/products/<?php echo $item['product_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                             class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center me-3" 
                                             style="width: 50px; height: 50px; border-radius: 0.375rem;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_title'] ?? 'Producto eliminado'); ?></strong>
                                            <?php if (!$item['product_title']): ?>
                                                <br><small class="text-danger">Este producto ya no existe</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($item['sku']): ?>
                                        <code><?php echo htmlspecialchars($item['sku']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><strong>$<?php echo number_format($item_total); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Subtotal:</th>
                                <th>$<?php echo number_format($subtotal); ?></th>
                            </tr>
                            <?php if ($order['shipping_cost'] > 0): ?>
                            <tr>
                                <th colspan="4" class="text-end">Envío:</th>
                                <th>$<?php echo number_format($order['shipping_cost']); ?></th>
                            </tr>
                            <?php endif; ?>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <tr>
                                <th colspan="4" class="text-end">Descuento:</th>
                                <th class="text-success">-$<?php echo number_format($order['discount_amount']); ?></th>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-active">
                                <th colspan="4" class="text-end">Total:</th>
                                <th class="fs-5">$<?php echo number_format($order['total_amount']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Dirección de envío -->
        <?php if ($order['shipping_address']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Dirección de Envío
                </h5>
            </div>
            <div class="card-body">
                <address class="mb-0">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </address>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Historial de estado -->
        <?php if (!empty($status_history)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Historial de Estado
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($status_history as $history): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">
                                Estado cambiado de "<?php echo ucfirst($history['old_status']); ?>" 
                                a "<?php echo ucfirst($history['new_status']); ?>"
                            </h6>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?>
                            </small>
                            <?php if ($history['note']): ?>
                                <p class="mt-2 mb-0"><?php echo htmlspecialchars($history['note']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Panel lateral -->
    <div class="col-lg-4">
        <!-- Cambiar estado -->
        <?php if (hasPermission('orders', 'update')): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Cambiar Estado
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Procesando</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completado</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="note" class="form-label">Nota (opcional)</label>
                        <textarea class="form-control" id="note" name="note" rows="3" 
                                  placeholder="Agregar nota sobre el cambio de estado..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>
                        Actualizar Estado
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Información de pago -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-credit-card me-2"></i>
                    Información de Pago
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Método:</strong> 
                    <?php echo htmlspecialchars($order['payment_method'] ?? 'No especificado'); ?>
                </p>
                <p class="mb-2">
                    <strong>Estado:</strong> 
                    <span class="badge <?php echo $payment_info['class']; ?>">
                        <?php echo $payment_info['text']; ?>
                    </span>
                </p>
                <?php if ($order['transaction_id']): ?>
                <p class="mb-0">
                    <strong>ID Transacción:</strong><br>
                    <code><?php echo htmlspecialchars($order['transaction_id']); ?></code>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Notas -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-sticky-note me-2"></i>
                    Notas del Pedido
                </h5>
            </div>
            <div class="card-body">
                <!-- Agregar nueva nota -->
                <?php if (hasPermission('orders', 'update')): ?>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_note" value="1">
                    
                    <div class="mb-3">
                        <textarea class="form-control" name="note" rows="3" 
                                  placeholder="Agregar nota..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-plus me-1"></i> Agregar Nota
                    </button>
                </form>
                <hr>
                <?php endif; ?>
                
                <!-- Lista de notas -->
                <?php if (!empty($order_notes)): ?>
                    <div class="notes-list">
                        <?php foreach ($order_notes as $note): ?>
                        <div class="note-item mb-3 p-2 bg-light rounded">
                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($note['admin_name'] ?? 'Usuario eliminado'); ?> - 
                                <?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No hay notas para este pedido</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 0;
    width: 10px;
    height: 10px;
    background: #007bff;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -21px;
    top: 10px;
    bottom: -20px;
    width: 2px;
    background: #dee2e6;
}
</style>

<script>
function printOrder() {
    window.open(`order_print.php?id=<?php echo $order_id; ?>`, '_blank', 'width=800,height=600');
}

// Auto-refresh si el pedido está en estado activo
<?php if (in_array($order['status'], ['pending', 'processing'])): ?>
setInterval(function() {
    // Verificar cambios en el estado del pedido
    fetch(`api/orders.php?id=<?php echo $order_id; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.status !== '<?php echo $order['status']; ?>') {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
}, 30000);
<?php endif; ?>
</script>

<?php require_once 'inc/footer.php'; ?>