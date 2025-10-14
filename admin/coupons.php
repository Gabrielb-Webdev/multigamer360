<?php
// Activar reporte de errores para debug (REMOVER en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla
ini_set('log_errors', 1); // Guardar en log
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require_once 'inc/auth.php';

// $userManager ya está disponible desde auth.php

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO coupons (code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, per_user_limit, start_date, end_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                strtoupper($_POST['code']),
                $_POST['name'],
                $_POST['description'],
                $_POST['type'],
                $_POST['value'],
                $_POST['minimum_amount'] ?: 0,
                $_POST['maximum_discount'] ?: null,
                $_POST['usage_limit'] ?: null,
                $_POST['per_user_limit'] ?: 1,
                $_POST['start_date'],
                $_POST['end_date'] ?: null,
                isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $success_msg = "Cupón creado exitosamente";
        } catch (PDOException $e) {
            $error_msg = "Error al crear cupón: " . $e->getMessage();
        }
    } elseif ($action === 'toggle_status') {
        $coupon_id = $_POST['coupon_id'];
        $stmt = $pdo->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$coupon_id]);
        $success_msg = "Estado del cupón actualizado";
    } elseif ($action === 'delete') {
        $coupon_id = $_POST['coupon_id'];
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        $success_msg = "Cupón eliminado exitosamente";
    }
}

// Obtener cupones
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

$sql = "SELECT *, 
        CASE 
            WHEN end_date IS NOT NULL AND end_date < NOW() THEN 'expired'
            WHEN start_date > NOW() THEN 'scheduled'
            WHEN is_active = 1 THEN 'active'
            ELSE 'inactive'
        END as status_text
        FROM coupons WHERE 1=1";

$params = [];
if ($search) {
    $sql .= " AND (code LIKE ? OR name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    if ($status === 'active') {
        $sql .= " AND is_active = 1 AND (end_date IS NULL OR end_date > NOW()) AND start_date <= NOW()";
    } elseif ($status === 'inactive') {
        $sql .= " AND is_active = 0";
    } elseif ($status === 'expired') {
        $sql .= " AND end_date IS NOT NULL AND end_date < NOW()";
    }
}
if ($type) {
    $sql .= " AND type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error en coupons.php: " . $e->getMessage());
    $coupons = [];
    $error_msg = "Error al cargar los cupones. Por favor, contacta al administrador.";
}

$page_title = "Gestión de Cupones";
require_once 'inc/header.php';
?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'inc/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-ticket-alt me-2"></i>Gestión de Cupones</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                        <i class="fas fa-plus me-1"></i>Crear Cupón
                    </button>
                </div>

                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Código o nombre del cupón">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="status">
                                    <option value="">Todos</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activos</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                                    <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expirados</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="type">
                                    <option value="">Todos</option>
                                    <option value="percentage" <?php echo $type === 'percentage' ? 'selected' : ''; ?>>Porcentaje</option>
                                    <option value="fixed" <?php echo $type === 'fixed' ? 'selected' : ''; ?>>Monto Fijo</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de cupones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Cupones (<?php echo count($coupons); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Usos</th>
                                        <th>Estado</th>
                                        <th>Vigencia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <tr>
                                            <td>
                                                <code class="bg-light p-1 rounded"><?php echo $coupon['code']; ?></code>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($coupon['name']); ?></strong>
                                                <?php if ($coupon['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($coupon['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($coupon['type'] === 'percentage'): ?>
                                                    <span class="badge bg-info">Porcentaje</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Monto Fijo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($coupon['type'] === 'percentage'): ?>
                                                    <strong><?php echo $coupon['value']; ?>%</strong>
                                                    <?php if ($coupon['maximum_discount']): ?>
                                                        <br><small class="text-muted">Máx: $<?php echo number_format($coupon['maximum_discount'], 2); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <strong>$<?php echo number_format($coupon['value'], 2); ?></strong>
                                                <?php endif; ?>
                                                <?php if ($coupon['minimum_amount'] > 0): ?>
                                                    <br><small class="text-muted">Mín: $<?php echo number_format($coupon['minimum_amount'], 2); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $coupon['used_count']; ?>
                                                <?php if ($coupon['usage_limit']): ?>
                                                    / <?php echo $coupon['usage_limit']; ?>
                                                    <?php 
                                                    $usage_percent = ($coupon['used_count'] / $coupon['usage_limit']) * 100;
                                                    $progress_class = $usage_percent > 80 ? 'bg-danger' : ($usage_percent > 60 ? 'bg-warning' : 'bg-success');
                                                    ?>
                                                    <div class="progress mt-1" style="height: 4px;">
                                                        <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $usage_percent; ?>%"></div>
                                                    </div>
                                                <?php else: ?>
                                                    <small class="text-muted">/ Ilimitado</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                $status_text = '';
                                                
                                                switch ($coupon['status_text']) {
                                                    case 'active':
                                                        $status_class = 'bg-success';
                                                        $status_icon = 'fas fa-check-circle';
                                                        $status_text = 'Activo';
                                                        break;
                                                    case 'inactive':
                                                        $status_class = 'bg-secondary';
                                                        $status_icon = 'fas fa-pause-circle';
                                                        $status_text = 'Inactivo';
                                                        break;
                                                    case 'expired':
                                                        $status_class = 'bg-danger';
                                                        $status_icon = 'fas fa-times-circle';
                                                        $status_text = 'Expirado';
                                                        break;
                                                    case 'scheduled':
                                                        $status_class = 'bg-warning';
                                                        $status_icon = 'fas fa-clock';
                                                        $status_text = 'Programado';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <i class="<?php echo $status_icon; ?> me-1"></i><?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Inicio:</strong> <?php echo date('d/m/Y', strtotime($coupon['start_date'])); ?><br>
                                                    <strong>Fin:</strong> 
                                                    <?php echo $coupon['end_date'] ? date('d/m/Y', strtotime($coupon['end_date'])) : 'Sin límite'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewCouponDetails(<?php echo $coupon['id']; ?>)" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="editCoupon(<?php echo $coupon['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Cambiar estado">
                                                            <i class="fas fa-toggle-<?php echo $coupon['is_active'] ? 'on' : 'off'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCoupon(<?php echo $coupon['id']; ?>, '<?php echo htmlspecialchars($coupon['code']); ?>')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($coupons)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No se encontraron cupones</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear cupón -->
    <div class="modal fade" id="createCouponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Cupón</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Código del Cupón *</label>
                                    <input type="text" class="form-control" name="code" required maxlength="50" style="text-transform: uppercase;">
                                    <div class="form-text">Solo letras, números y guiones. Se convertirá automáticamente a mayúsculas.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Cupón *</label>
                                    <input type="text" class="form-control" name="name" required maxlength="255">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Descuento *</label>
                                    <select class="form-select" name="type" required onchange="toggleDiscountFields()">
                                        <option value="percentage">Porcentaje</option>
                                        <option value="fixed">Monto Fijo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Valor del Descuento *</label>
                                    <input type="number" class="form-control" name="value" required min="0" step="0.01">
                                    <div class="form-text" id="valueHelp">Porcentaje de descuento (ej: 10 para 10%)</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Descuento Máximo</label>
                                    <input type="number" class="form-control" name="maximum_discount" min="0" step="0.01" id="maxDiscountField">
                                    <div class="form-text">Solo para cupones de porcentaje</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Monto Mínimo</label>
                                    <input type="number" class="form-control" name="minimum_amount" min="0" step="0.01" value="0">
                                    <div class="form-text">Monto mínimo de compra requerido</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Límite de Usos</label>
                                    <input type="number" class="form-control" name="usage_limit" min="1">
                                    <div class="form-text">Vacío = ilimitado</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Usos por Usuario</label>
                                    <input type="number" class="form-control" name="per_user_limit" min="1" value="1">
                                    <div class="form-text">Máximo usos por usuario</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Inicio *</label>
                                    <input type="datetime-local" class="form-control" name="start_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Fin</label>
                                    <input type="datetime-local" class="form-control" name="end_date">
                                    <div class="form-text">Vacío = sin fecha de expiración</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" checked>
                            <label class="form-check-label">
                                Activar cupón inmediatamente
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Cupón</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleDiscountFields() {
            const type = document.querySelector('select[name="type"]').value;
            const maxDiscountField = document.getElementById('maxDiscountField');
            const valueHelp = document.getElementById('valueHelp');
            
            if (type === 'percentage') {
                maxDiscountField.style.display = 'block';
                valueHelp.textContent = 'Porcentaje de descuento (ej: 10 para 10%)';
            } else {
                maxDiscountField.style.display = 'none';
                valueHelp.textContent = 'Monto fijo de descuento en pesos';
            }
        }
        
        function deleteCoupon(id, code) {
            if (confirm(`¿Estás seguro de que quieres eliminar el cupón "${code}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="coupon_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewCouponDetails(id) {
            // Implementar modal de detalles
            alert('Función de ver detalles en desarrollo');
        }
        
        function editCoupon(id) {
            // Implementar modal de edición
            alert('Función de editar en desarrollo');
        }
        
        // Inicializar campos al cargar
        document.addEventListener('DOMContentLoaded', function() {
            toggleDiscountFields();
        });
    </script>

<?php require_once 'inc/footer.php'; ?>