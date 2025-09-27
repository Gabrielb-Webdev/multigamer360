<?php
$page_title = 'Gestión de Categorías';
$page_actions = '<a href="category_edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Categoría</a>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('categories', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver categorías';
    header('Location: index.php');
    exit;
}

// Parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$parent_id = $_GET['parent_id'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ? OR c.slug LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status !== '') {
    $where_conditions[] = "c.is_active = ?";
    $params[] = $status;
}

if ($parent_id !== '') {
    if ($parent_id === '0') {
        $where_conditions[] = "c.parent_id IS NULL";
    } else {
        $where_conditions[] = "c.parent_id = ?";
        $params[] = $parent_id;
    }
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de categorías
    $count_query = "SELECT COUNT(*) as total FROM categories c WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_categories = $count_stmt->fetch()['total'];
    
    // Obtener categorías con información de padre e hijos
    $query = "
        SELECT c.*, 
               p.name as parent_name,
               (SELECT COUNT(*) FROM categories child WHERE child.parent_id = c.id) as children_count,
               (SELECT COUNT(*) FROM products prod WHERE prod.category_id = c.id) as products_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        WHERE $where_clause
        ORDER BY c.sort_order ASC, c.name ASC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Obtener categorías padre para filtros
    $parent_categories = $pdo->query("
        SELECT id, name FROM categories 
        WHERE parent_id IS NULL AND is_active = 1 
        ORDER BY name
    ")->fetchAll();
    
    $total_pages = ceil($total_categories / $per_page);
    
    // Estadísticas
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_categories,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_categories,
            COUNT(CASE WHEN parent_id IS NULL THEN 1 END) as parent_categories,
            COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as child_categories
        FROM categories
    ")->fetch();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar categorías: ' . $e->getMessage();
    $categories = [];
    $parent_categories = [];
    $total_categories = 0;
    $total_pages = 1;
    $stats = ['total_categories' => 0, 'active_categories' => 0, 'parent_categories' => 0, 'child_categories' => 0];
}
?>

<!-- Estadísticas rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Total Categorías</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_categories']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tags fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Activas</h6>
                        <h4 class="mb-0 text-success"><?php echo number_format($stats['active_categories']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Principales</h6>
                        <h4 class="mb-0 text-info"><?php echo number_format($stats['parent_categories']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-sitemap fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Subcategorías</h6>
                        <h4 class="mb-0 text-warning"><?php echo number_format($stats['child_categories']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-layer-group fa-2x text-warning"></i>
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
                       placeholder="Nombre, descripción o slug...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="parent_id" class="form-label">Categoría Padre</label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="">Todas</option>
                    <option value="0" <?php echo $parent_id === '0' ? 'selected' : ''; ?>>Solo principales</option>
                    <?php foreach ($parent_categories as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>" 
                                <?php echo $parent_id == $parent['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($parent['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de categorías -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-tags me-2"></i>
            Categorías (<?php echo number_format($total_categories); ?> total<?php echo $total_categories !== 1 ? 'es' : ''; ?>)
        </span>
        
        <?php if (hasPermission('categories', 'delete')): ?>
        <div class="bulk-actions" style="display: none;">
            <span class="me-2">
                <span class="selected-count">0</span> seleccionada(s)
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
        <?php if (empty($categories)): ?>
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron categorías</h5>
                <p class="text-muted">
                    <?php if ($search || $status !== '' || $parent_id !== ''): ?>
                        Intenta ajustar los filtros de búsqueda.
                    <?php else: ?>
                        Comienza agregando tu primera categoría.
                    <?php endif; ?>
                </p>
                <?php if (hasPermission('categories', 'create')): ?>
                    <a href="category_edit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Categoría
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php if (hasPermission('categories', 'delete')): ?>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <?php endif; ?>
                            <th width="80">Imagen</th>
                            <th>Categoría</th>
                            <th>Slug</th>
                            <th>Padre</th>
                            <th>Productos</th>
                            <th>Subcategorías</th>
                            <th>Estado</th>
                            <th>Orden</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <?php if (hasPermission('categories', 'delete')): ?>
                            <td>
                                <input type="checkbox" name="category_ids[]" value="<?php echo $category['id']; ?>">
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php if ($category['image']): ?>
                                    <img src="../uploads/categories/<?php echo $category['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($category['name']); ?>"
                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px; border-radius: 0.375rem;">
                                        <i class="fas fa-tag text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <?php if ($category['description'] && strlen($category['description']) > 100): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars(substr($category['description'], 0, 100)) . '...'; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($category['slug']); ?></code>
                            </td>
                            <td>
                                <?php if ($category['parent_name']): ?>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($category['parent_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Categoría principal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($category['products_count'] > 0): ?>
                                    <span class="badge bg-primary"><?php echo $category['products_count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($category['children_count'] > 0): ?>
                                    <span class="badge bg-info"><?php echo $category['children_count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($category['is_active']): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted"><?php echo $category['sort_order']; ?></span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="../productos.php?categoria=<?php echo $category['slug']; ?>" 
                                       class="btn btn-sm btn-outline-info" target="_blank"
                                       data-bs-toggle="tooltip" title="Ver en sitio">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('categories', 'update')): ?>
                                    <a href="category_edit.php?id=<?php echo $category['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('categories', 'delete') && $category['products_count'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCategory(<?php echo $category['id']; ?>)"
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
            <nav aria-label="Paginación de categorías" class="mt-4">
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

<script>
// Configurar selección múltiple
TableManager.setupBulkActions('.table');

// Eliminar categoría
function deleteCategory(categoryId) {
    Utils.confirm('¿Está seguro de que desea eliminar esta categoría?', function() {
        fetch('api/categories.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                id: categoryId,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast('Categoría eliminada correctamente', 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al eliminar categoría', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Activar categorías seleccionadas
function bulkActivate() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos una categoría', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Activar ${ids.length} categoría(s)?`, function() {
        fetch('api/categories.php', {
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
                Utils.showToast(`${ids.length} categoría(s) activada(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al activar categorías', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Desactivar categorías seleccionadas
function bulkDeactivate() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos una categoría', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Desactivar ${ids.length} categoría(s)?`, function() {
        fetch('api/categories.php', {
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
                Utils.showToast(`${ids.length} categoría(s) desactivada(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al desactivar categorías', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Eliminar categorías seleccionadas
function bulkDelete() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos una categoría', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Está seguro de que desea eliminar ${ids.length} categoría(s)?`, function() {
        fetch('api/categories.php', {
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
                Utils.showToast(`${ids.length} categoría(s) eliminada(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al eliminar categorías', 'danger');
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