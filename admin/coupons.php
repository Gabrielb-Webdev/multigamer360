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
$page_actions = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCouponModal"><i class="fas fa-plus me-2"></i>Crear Cupón</button>';
require_once 'inc/header.php';
?>

<style>
    /* Estilos específicos para la página de cupones */
    .coupon-code {
        font-family: 'Courier New', monospace;
        background-color: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
        color: #e83e8c;
        border: 1px solid #dee2e6;
    }
    
    .coupon-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }
    
    .coupon-table td {
        vertical-align: middle;
    }
    
    .progress {
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .btn-group-actions {
        display: flex;
        gap: 4px;
    }
    
    .btn-group-actions .btn {
        padding: 4px 8px;
        font-size: 0.875rem;
    }
    
    .badge {
        padding: 6px 12px;
        font-weight: 500;
    }
</style>

                <!-- Tarjetas de estadísticas -->
                <div class="row mb-4">
                    <?php
                    $totalCoupons = count($coupons);
                    $activeCoupons = count(array_filter($coupons, function($c) { return $c['is_active'] == 1; }));
                    $expiredCoupons = count(array_filter($coupons, function($c) { return $c['end_date'] && strtotime($c['end_date']) < time(); }));
                    ?>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Cupones
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalCoupons; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Cupones Activos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activeCoupons; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Cupones Expirados
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $expiredCoupons; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Tasa Activos
                                        </div>
                                        <div class="row no-gutters align-items-center">
                                            <div class="col-auto">
                                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                    <?php echo $totalCoupons > 0 ? round(($activeCoupons / $totalCoupons) * 100) : 0; ?>%
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="progress progress-sm mr-2">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: <?php echo $totalCoupons > 0 ? round(($activeCoupons / $totalCoupons) * 100) : 0; ?>%" 
                                                         aria-valuenow="<?php echo $totalCoupons > 0 ? round(($activeCoupons / $totalCoupons) * 100) : 0; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Lista de Cupones 
                            <span class="badge bg-primary ms-2"><?php echo count($coupons); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover coupon-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Código</th>
                                        <th>Nombre</th>
                                        <th style="width: 100px;">Tipo</th>
                                        <th style="width: 120px;">Valor</th>
                                        <th style="width: 100px;">Usos</th>
                                        <th style="width: 100px;">Estado</th>
                                        <th style="width: 140px;">Vigencia</th>
                                        <th style="width: 180px;" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <tr>
                                            <td>
                                                <span class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong class="d-block"><?php echo htmlspecialchars($coupon['name']); ?></strong>
                                                    <?php if ($coupon['description']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars(substr($coupon['description'], 0, 50)); ?><?php echo strlen($coupon['description']) > 50 ? '...' : ''; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($coupon['type'] === 'percentage'): ?>
                                                    <span class="badge bg-info text-white">
                                                        <i class="fas fa-percent me-1"></i>Porcentaje
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-dollar-sign me-1"></i>Monto Fijo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php if ($coupon['type'] === 'percentage'): ?>
                                                        <strong class="text-info"><?php echo number_format($coupon['value'], 0); ?>%</strong>
                                                        <?php if ($coupon['maximum_discount']): ?>
                                                            <br><small class="text-muted">Máx: $<?php echo number_format($coupon['maximum_discount'], 2); ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <strong class="text-success">$<?php echo number_format($coupon['value'], 2); ?></strong>
                                                    <?php endif; ?>
                                                    <?php if ($coupon['minimum_amount'] > 0): ?>
                                                        <br><small class="text-muted">Mín: $<?php echo number_format($coupon['minimum_amount'], 2); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo $coupon['used_count']; ?></strong>
                                                    <?php if ($coupon['usage_limit']): ?>
                                                        <span class="text-muted">/ <?php echo $coupon['usage_limit']; ?></span>
                                                        <?php 
                                                        $usage_percent = ($coupon['usage_limit'] > 0) ? ($coupon['used_count'] / $coupon['usage_limit']) * 100 : 0;
                                                        $progress_class = $usage_percent > 80 ? 'bg-danger' : ($usage_percent > 60 ? 'bg-warning' : 'bg-success');
                                                        ?>
                                                        <div class="progress mt-1">
                                                            <div class="progress-bar <?php echo $progress_class; ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?php echo $usage_percent; ?>%"
                                                                 aria-valuenow="<?php echo $usage_percent; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <br><small class="text-muted">Ilimitado</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                $status_text = '';
                                                
                                                switch ($coupon['status_text']) {
                                                    case 'active':
                                                        $status_class = 'success';
                                                        $status_icon = 'check-circle';
                                                        $status_text = 'Activo';
                                                        break;
                                                    case 'inactive':
                                                        $status_class = 'secondary';
                                                        $status_icon = 'pause-circle';
                                                        $status_text = 'Inactivo';
                                                        break;
                                                    case 'expired':
                                                        $status_class = 'danger';
                                                        $status_icon = 'times-circle';
                                                        $status_text = 'Expirado';
                                                        break;
                                                    case 'scheduled':
                                                        $status_class = 'warning';
                                                        $status_icon = 'clock';
                                                        $status_text = 'Programado';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?> d-inline-flex align-items-center">
                                                    <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.875rem;">
                                                    <div>
                                                        <i class="fas fa-play text-success me-1"></i>
                                                        <strong>Inicio:</strong> 
                                                        <span class="text-muted"><?php echo date('d/m/Y', strtotime($coupon['start_date'])); ?></span>
                                                    </div>
                                                    <div class="mt-1">
                                                        <i class="fas fa-stop text-danger me-1"></i>
                                                        <strong>Fin:</strong> 
                                                        <span class="text-muted">
                                                            <?php echo $coupon['end_date'] ? date('d/m/Y', strtotime($coupon['end_date'])) : 'Sin límite'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group-actions">
                                                    <button class="btn btn-sm btn-info" 
                                                            onclick="viewCouponDetails(<?php echo $coupon['id']; ?>)" 
                                                            title="Ver detalles"
                                                            data-bs-toggle="tooltip">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" 
                                                            onclick="editCoupon(<?php echo $coupon['id']; ?>)" 
                                                            title="Editar"
                                                            data-bs-toggle="tooltip">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Cambiar el estado de este cupón?');">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                        <button type="submit" 
                                                                class="btn btn-sm <?php echo $coupon['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                                title="<?php echo $coupon['is_active'] ? 'Desactivar' : 'Activar'; ?>"
                                                                data-bs-toggle="tooltip">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteCoupon(<?php echo $coupon['id']; ?>, '<?php echo htmlspecialchars($coupon['code'], ENT_QUOTES); ?>')" 
                                                            title="Eliminar"
                                                            data-bs-toggle="tooltip">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($coupons)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="py-4">
                                                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3 d-block"></i>
                                                    <h5 class="text-muted">No se encontraron cupones</h5>
                                                    <p class="text-muted">Crea tu primer cupón haciendo clic en el botón "Crear Cupón"</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
            
            // Inicializar tooltips de Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Animación suave para las tarjetas
            document.querySelectorAll('.card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>

<?php require_once 'inc/footer.php'; ?>