<?php
$page_title = 'Gestión de Marcas';
$page_actions = '<a href="brand_edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Marca</a>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('brands', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver marcas';
    header('Location: index.php');
    exit;
}

// Parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = "(b.name LIKE ? OR b.description LIKE ? OR b.slug LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status !== '') {
    $where_conditions[] = "b.is_active = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de marcas
    $count_query = "SELECT COUNT(*) as total FROM brands b WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_brands = $count_stmt->fetch()['total'];
    
    // Obtener marcas con información de productos
    $query = "
        SELECT b.*, 
               (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) as products_count
        FROM brands b
        WHERE $where_clause
        ORDER BY b.name ASC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $brands = $stmt->fetchAll();
    
    $total_pages = ceil($total_brands / $per_page);
    
    // Estadísticas (sin is_featured por ahora)
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_brands,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_brands,
            AVG((SELECT COUNT(*) FROM products WHERE brand_id = brands.id)) as avg_products_per_brand
        FROM brands
    ")->fetch();
    
    // Agregar campo featured_brands con valor 0
    $stats['featured_brands'] = 0;
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar marcas: ' . $e->getMessage();
    $brands = [];
    $total_brands = 0;
    $total_pages = 1;
    $stats = ['total_brands' => 0, 'active_brands' => 0, 'featured_brands' => 0, 'avg_products_per_brand' => 0];
}
?>

<!-- Estadísticas rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Total Marcas</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_brands']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-copyright fa-2x text-primary"></i>
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
                        <h4 class="mb-0 text-success"><?php echo number_format($stats['active_brands']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
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
                        <h6 class="card-title text-muted mb-2">Destacadas</h6>
                        <h4 class="mb-0 text-warning"><?php echo number_format($stats['featured_brands']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-star fa-2x text-warning"></i>
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
                        <h6 class="card-title text-muted mb-2">Promedio Productos</h6>
                        <h4 class="mb-0 text-info"><?php echo number_format($stats['avg_products_per_brand'], 1); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x text-info"></i>
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
            <div class="col-md-6">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Nombre, descripción o slug...">
            </div>
            
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todas</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Activa</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactiva</option>
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
                    <a href="brands.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de marcas -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-copyright me-2"></i>
            Marcas (<?php echo number_format($total_brands); ?> total<?php echo $total_brands !== 1 ? 'es' : ''; ?>)
        </span>
        
        <?php if (hasPermission('brands', 'delete')): ?>
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
        <?php if (empty($brands)): ?>
            <div class="text-center py-5">
                <i class="fas fa-copyright fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron marcas</h5>
                <p class="text-muted">
                    <?php if ($search || $status !== ''): ?>
                        Intenta ajustar los filtros de búsqueda.
                    <?php else: ?>
                        Comienza agregando tu primera marca.
                    <?php endif; ?>
                </p>
                <?php if (hasPermission('brands', 'create')): ?>
                    <a href="brand_edit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Marca
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php if (hasPermission('brands', 'delete')): ?>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <?php endif; ?>
                            <th width="80">Logo</th>
                            <th>Marca</th>
                            <th>Slug</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th>Destacada</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $brand): ?>
                        <tr>
                            <?php if (hasPermission('brands', 'delete')): ?>
                            <td>
                                <input type="checkbox" name="brand_ids[]" value="<?php echo $brand['id']; ?>">
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php if ($brand['logo']): ?>
                                    <img src="../uploads/brands/<?php echo $brand['logo']; ?>" 
                                         alt="<?php echo htmlspecialchars($brand['name']); ?>"
                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: contain; background: white;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px; border-radius: 0.375rem;">
                                        <i class="fas fa-copyright text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($brand['name']); ?></strong>
                                    <?php if ($brand['description'] && strlen($brand['description']) > 100): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars(substr($brand['description'], 0, 100)) . '...'; ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($brand['website']): ?>
                                        <br><small>
                                            <a href="<?php echo htmlspecialchars($brand['website']); ?>" 
                                               target="_blank" class="text-decoration-none">
                                                <i class="fas fa-external-link-alt"></i> Sitio web
                                            </a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($brand['slug']); ?></code>
                            </td>
                            <td>
                                <?php if ($brand['products_count'] > 0): ?>
                                    <span class="badge bg-primary"><?php echo $brand['products_count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($brand['is_active']): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($brand['is_featured']) && $brand['is_featured']): ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star"></i> Destacada
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="../productos.php?marca=<?php echo $brand['slug']; ?>" 
                                       class="btn btn-sm btn-outline-info" target="_blank"
                                       data-bs-toggle="tooltip" title="Ver productos">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('brands', 'update')): ?>
                                    <a href="brand_edit.php?id=<?php echo $brand['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('brands', 'delete') && $brand['products_count'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteBrand(<?php echo $brand['id']; ?>)"
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
            <nav aria-label="Paginación de marcas" class="mt-4">
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
document.addEventListener('DOMContentLoaded', function() {
    // Configurar selección múltiple
    if (typeof TableManager !== 'undefined') {
        TableManager.setupBulkActions('.table');
    }
});

// Eliminar marca
function deleteBrand(brandId) {
    Utils.confirm('¿Está seguro de que desea eliminar esta marca?', function() {
        fetch('api/brands.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ 
                id: brandId,
                csrf_token: AdminPanel.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast('Marca eliminada correctamente', 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al eliminar marca', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Activar marcas seleccionadas
function bulkActivate() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos una marca', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Activar ${ids.length} marca(s)?`, function() {
        fetch('api/brands.php', {
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
                Utils.showToast(`${ids.length} marca(s) activada(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al activar marcas', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Desactivar marcas seleccionadas
function bulkDeactivate() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos una marca', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Desactivar ${ids.length} marca(s)?`, function() {
        fetch('api/brands.php', {
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
                Utils.showToast(`${ids.length} marca(s) desactivada(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al desactivar marcas', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Eliminar marcas seleccionadas
function bulkDelete() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        Utils.showToast('Seleccione al menos una marca', 'warning');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    Utils.confirm(`¿Está seguro de que desea eliminar ${ids.length} marca(s)?`, function() {
        fetch('api/brands.php', {
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
                Utils.showToast(`${ids.length} marca(s) eliminada(s) correctamente`, 'success');
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error al eliminar marcas', 'danger');
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