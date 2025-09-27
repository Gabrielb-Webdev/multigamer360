<?php
$page_title = 'Gestión de Usuarios';
$page_actions = '<a href="user_edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Usuario</a>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('users', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver usuarios';
    header('Location: index.php');
    exit;
}

// Parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$registration_date = $_GET['registration_date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($role) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role;
}

if ($status) {
    $where_conditions[] = "u.is_active = ?";
    $params[] = $status === 'active' ? 1 : 0;
}

if ($registration_date) {
    $where_conditions[] = "DATE(u.created_at) = ?";
    $params[] = $registration_date;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de usuarios
    $count_query = "SELECT COUNT(*) as total FROM users u WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetch()['total'];
    
    // Obtener usuarios
    $query = "
        SELECT u.*,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as orders_count,
               (SELECT SUM(o.total_amount) FROM orders o WHERE o.user_id = u.id AND o.status = 'completed') as total_spent,
               (SELECT MAX(o.created_at) FROM orders o WHERE o.user_id = u.id) as last_order_date
        FROM users u
        WHERE $where_clause
        ORDER BY u.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    $total_pages = ceil($total_users / $per_page);
    
    // Estadísticas rápidas
    $stats_query = "
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
            COUNT(CASE WHEN role = 'admin' OR role = 'superadmin' THEN 1 END) as admin_users,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
            COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_30_days
        FROM users u 
        WHERE $where_clause
    ";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch();
    
    // Obtener roles únicos
    $roles_stmt = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL ORDER BY role");
    $available_roles = $roles_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar usuarios: ' . $e->getMessage();
    $users = [];
    $total_users = 0;
    $total_pages = 1;
    $stats = [
        'total_users' => 0,
        'active_users' => 0,
        'admin_users' => 0,
        'new_today' => 0,
        'active_30_days' => 0
    ];
    $available_roles = [];
}
?>

<!-- Estadísticas rápidas -->
<div class="row mb-4">
    <div class="col-md-2-4">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Total Usuarios</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_users']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2-4">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Activos</h6>
                        <h4 class="mb-0 text-success"><?php echo number_format($stats['active_users']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2-4">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Administradores</h6>
                        <h4 class="mb-0 text-warning"><?php echo number_format($stats['admin_users']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-shield fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2-4">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Nuevos Hoy</h6>
                        <h4 class="mb-0 text-info"><?php echo number_format($stats['new_today']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-plus fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2-4">
        <div class="card border-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Activos 30d</h6>
                        <h4 class="mb-0 text-secondary"><?php echo number_format($stats['active_30_days']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x text-secondary"></i>
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
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Nombre, email, usuario...">
            </div>
            
            <div class="col-md-2">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role">
                    <option value="">Todos</option>
                    <?php foreach ($available_roles as $r): ?>
                        <option value="<?php echo $r; ?>" 
                                <?php echo $role === $r ? 'selected' : ''; ?>>
                            <?php echo ucfirst($r); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="registration_date" class="form-label">Fecha de Registro</label>
                <input type="date" class="form-control" id="registration_date" name="registration_date" 
                       value="<?php echo htmlspecialchars($registration_date); ?>">
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de usuarios -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-users me-2"></i>
            Usuarios (<?php echo number_format($total_users); ?> total<?php echo $total_users !== 1 ? 'es' : ''; ?>)
        </span>
        
        <?php if (hasPermission('users', 'delete')): ?>
        <div class="bulk-actions" style="display: none;">
            <span class="me-2">
                <span class="selected-count">0</span> seleccionado(s)
            </span>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkActivate()">
                    <i class="fas fa-check"></i> Activar
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="bulkDeactivate()">
                    <i class="fas fa-ban"></i> Desactivar
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron usuarios</h5>
                <p class="text-muted">
                    <?php if ($search || $role || $status || $registration_date): ?>
                        Intenta ajustar los filtros de búsqueda.
                    <?php else: ?>
                        Los usuarios aparecerán aquí cuando se registren.
                    <?php endif; ?>
                </p>
                <?php if (hasPermission('users', 'create')): ?>
                    <a href="user_edit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Usuario
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php if (hasPermission('users', 'delete')): ?>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <?php endif; ?>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Pedidos</th>
                            <th>Total Gastado</th>
                            <th>Último Acceso</th>
                            <th>Estado</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <?php if (hasPermission('users', 'delete')): ?>
                            <td>
                                <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>"
                                       <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <?php if ($user['avatar']): ?>
                                        <img src="../uploads/avatars/<?php echo $user['avatar']; ?>" 
                                             alt="<?php echo htmlspecialchars($user['username']); ?>"
                                             class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if ($user['first_name'] || $user['last_name']): ?>
                                            <br><small class="text-muted">
                                                <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                            <br><small class="badge bg-info">Tú</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                    <?php if (!$user['email_verified']): ?>
                                        <br><small class="text-warning">
                                            <i class="fas fa-exclamation-triangle"></i> No verificado
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $role_colors = [
                                    'superadmin' => 'bg-danger',
                                    'admin' => 'bg-warning text-dark',
                                    'moderator' => 'bg-info',
                                    'customer' => 'bg-success',
                                    'user' => 'bg-secondary'
                                ];
                                $role_color = $role_colors[$user['role']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $role_color; ?>">
                                    <?php echo ucfirst($user['role'] ?? 'user'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['orders_count'] > 0): ?>
                                    <span class="badge bg-primary"><?php echo $user['orders_count']; ?></span>
                                    <?php if ($user['last_order_date']): ?>
                                        <br><small class="text-muted">
                                            Último: <?php echo date('d/m/Y', strtotime($user['last_order_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin pedidos</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['total_spent'] > 0): ?>
                                    <strong class="text-success">$<?php echo number_format($user['total_spent']); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">$0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <div>
                                        <?php 
                                        $last_login = strtotime($user['last_login']);
                                        $diff = time() - $last_login;
                                        if ($diff < 3600) {
                                            echo '<span class="text-success">Hace ' . ceil($diff / 60) . ' min</span>';
                                        } elseif ($diff < 86400) {
                                            echo '<span class="text-info">Hace ' . ceil($diff / 3600) . ' h</span>';
                                        } else {
                                            echo date('d/m/Y', $last_login);
                                        }
                                        ?>
                                        <br><small class="text-muted"><?php echo date('H:i', $last_login); ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Nunca</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="user_view.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-info"
                                       data-bs-toggle="tooltip" title="Ver perfil">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('users', 'update')): ?>
                                    <a href="user_edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('users', 'update') && $user['id'] !== $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)"
                                            data-bs-toggle="tooltip" title="<?php echo $user['is_active'] ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('users', 'delete') && $user['id'] !== $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteUser(<?php echo $user['id']; ?>)"
                                            data-bs-toggle="tooltip" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de usuarios" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.col-md-2-4 {
    flex: 0 0 auto;
    width: 20%;
}

@media (max-width: 768px) {
    .col-md-2-4 {
        width: 50%;
        margin-bottom: 1rem;
    }
}
</style>

<script>
// Configurar selección múltiple
TableManager.setupBulkActions('.table');

// Alternar estado de usuario
function toggleUserStatus(userId, activate) {
    const action = activate ? 'activar' : 'desactivar';
    Utils.confirm(`¿Está seguro de que desea ${action} este usuario?`, function() {
        fetch('api/users.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                id: userId, 
                is_active: activate,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast(`Usuario ${action} correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || `Error al ${action} usuario`, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Eliminar usuario
function deleteUser(userId) {
    Utils.confirm('¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.', function() {
        fetch('api/users.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                id: userId,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast('Usuario eliminado correctamente', 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al eliminar usuario', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Activar usuarios seleccionados
function bulkActivate() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked:not([disabled])');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos un usuario', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Activar ${ids.length} usuario(s)?`, function() {
        fetch('api/users.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                ids: ids, 
                is_active: true,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast(`${ids.length} usuario(s) activado(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al activar usuarios', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Desactivar usuarios seleccionados
function bulkDeactivate() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked:not([disabled])');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos un usuario', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Desactivar ${ids.length} usuario(s)?`, function() {
        fetch('api/users.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                ids: ids, 
                is_active: false,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast(`${ids.length} usuario(s) desactivado(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al desactivar usuarios', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Eliminar usuarios seleccionados
function bulkDelete() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked:not([disabled])');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos un usuario', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Está seguro de que desea eliminar ${ids.length} usuario(s)? Esta acción no se puede deshacer.`, function() {
        fetch('api/users.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                ids: ids,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast(`${ids.length} usuario(s) eliminado(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al eliminar usuarios', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}
</script>

<?php require_once 'inc/footer.php'; ?>