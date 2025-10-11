<?php
$page_title = 'Gestión de Productos';
$page_actions = '<a href="product_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Producto</a>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('products', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver productos';
    header('Location: index.php');
    exit;
}

// Parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$low_stock = isset($_GET['low_stock']) ? true : false;
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = "(p.title LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
}

if ($brand) {
    $where_conditions[] = "p.brand_id = ?";
    $params[] = $brand;
}

if ($status) {
    if ($status === 'active') {
        $where_conditions[] = "p.is_active = ?";
        $params[] = 1;
    } elseif ($status === 'inactive') {
        $where_conditions[] = "p.is_active = ?";
        $params[] = 0;
    }
    // 'draft' se ignora por ahora ya que no hay columna específica
}

if ($low_stock) {
    $where_conditions[] = "p.stock_quantity <= 10";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de productos
    $count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    
    // Obtener productos
    $query = "
        SELECT p.*, c.name as category_name, b.name as brand_name,
               pi.image_url as main_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE $where_clause
        ORDER BY p.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Obtener categorías para filtros
    $categories_stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $categories_stmt->fetchAll();
    
    // Obtener marcas para filtros
    $brands_stmt = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
    $brands = $brands_stmt->fetchAll();
    
    $total_pages = ceil($total_products / $per_page);
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar productos: ' . $e->getMessage();
    $products = [];
    $categories = [];
    $brands = [];
    $total_products = 0;
    $total_pages = 1;
}
?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Título, SKU o descripción...">
            </div>
            
            <div class="col-md-2">
                <label for="category" class="form-label">Categoría</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="brand" class="form-label">Marca</label>
                <select class="form-select" id="brand" name="brand">
                    <option value="">Todas</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo $b['id']; ?>" 
                                <?php echo $brand == $b['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['name']); ?>
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
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="low_stock" class="form-label">Stock Bajo</label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="low_stock" name="low_stock" 
                           value="1" <?php echo $low_stock ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="low_stock">
                        ≤ 10 unidades
                    </label>
                </div>
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resultados -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-box me-2"></i>
            Productos (<?php echo number_format($total_products); ?> total<?php echo $total_products !== 1 ? 'es' : ''; ?>)
        </span>
        
        <?php if (hasPermission('products', 'delete')): ?>
        <div class="bulk-actions" style="display: none;">
            <span class="me-2">
                <span class="selected-count">0</span> seleccionado(s)
            </span>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-tasks me-2"></i>Acciones en masa
                </button>
                <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Cambiar estado a:</h6></li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="bulkChangeStatus('active'); return false;">
                            <i class="fas fa-check-circle text-success me-2"></i>Activo
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="bulkChangeStatus('inactive'); return false;">
                            <i class="fas fa-times-circle text-secondary me-2"></i>Inactivo
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="bulkChangeStatus('draft'); return false;">
                            <i class="fas fa-file-alt text-warning me-2"></i>Borrador
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" onclick="bulkDelete(); return false;">
                            <i class="fas fa-trash me-2"></i>Eliminar productos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron productos</h5>
                <p class="text-muted">
                    <?php if ($search || $category || $brand || $status || $low_stock): ?>
                        Intenta ajustar los filtros de búsqueda.
                    <?php else: ?>
                        Comienza agregando tu primer producto.
                    <?php endif; ?>
                </p>
                <?php if (hasPermission('products', 'create')): ?>
                    <a href="product_create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php if (hasPermission('products', 'delete')): ?>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <?php endif; ?>
                            <th width="80">Imagen</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <?php if (hasPermission('products', 'delete')): ?>
                            <td>
                                <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>">
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php if ($product['main_image']): ?>
                                    <img src="../uploads/products/<?php echo $product['main_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px; border-radius: 0.375rem;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if (strlen($product['description']) > 100): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($product['sku']); ?></code>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Sin categoría'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($product['brand_name'] ?? 'Sin marca'); ?>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($product['price'], 0); ?></strong>
                            </td>
                            <td>
                                <?php
                                $stock_class = $product['stock_quantity'] <= 10 ? 'text-danger' : ($product['stock_quantity'] <= 20 ? 'text-warning' : 'text-success');
                                ?>
                                <span class="<?php echo $stock_class; ?>">
                                    <?php echo number_format($product['stock_quantity']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $status_class = $product['is_active'] ? 'bg-success' : 'bg-secondary';
                                $status_text = $product['is_active'] ? 'Activo' : 'Inactivo';
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="../product-details.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline-info" target="_blank"
                                       data-bs-toggle="tooltip" title="Ver en sitio">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('products', 'update')): ?>
                                    <a href="product_edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('products', 'delete')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteProduct(<?php echo $product['id']; ?>)"
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
            <nav aria-label="Paginación de productos" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Primera página -->
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
                    
                    <!-- Páginas -->
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
                    
                    <!-- Última página -->
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

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Está seguro de que desea eliminar este producto?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Esta acción no se puede deshacer.</strong>
                    <br>Se eliminarán:
                    <ul class="mb-0 mt-2">
                        <li>El producto y toda su información</li>
                        <li>Todas las imágenes asociadas</li>
                        <li>Referencias en carritos y wishlists</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Eliminar Producto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Cambio de Estado -->
<div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-labelledby="bulkStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="bulkStatusHeader">
                <h5 class="modal-title" id="bulkStatusModalLabel">
                    <i class="fas fa-edit me-2"></i>Cambiar Estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-edit fa-3x mb-3" id="bulkStatusIcon"></i>
                    <p class="mb-0" id="bulkStatusMessage">¿Está seguro de que desea cambiar el estado?</p>
                </div>
                <div class="bg-light border rounded p-3">
                    <p class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Se cambiarán <strong id="bulkStatusCount">0</strong> producto(s) al estado: <strong id="bulkStatusName"></strong>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="confirmBulkStatusBtn">
                    <i class="fas fa-check me-2"></i>Confirmar Cambio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación en Masa -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="bulkDeleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación en Masa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5>¿Está seguro de que desea eliminar <strong id="bulkDeleteCount">0</strong> producto(s)?</h5>
                </div>
                <div class="bg-danger bg-opacity-10 border border-danger rounded p-3">
                    <h6 class="text-danger mb-2">
                        <i class="fas fa-exclamation-circle me-2"></i>¡ADVERTENCIA!
                    </h6>
                    <p class="mb-2"><strong>Esta acción no se puede deshacer.</strong></p>
                    <p class="mb-1">Para cada producto se eliminarán:</p>
                    <ul class="mb-0 small">
                        <li>El producto y toda su información</li>
                        <li>Todas las imágenes asociadas</li>
                        <li>Referencias en carritos y wishlists</li>
                        <li>Todas las reseñas y valoraciones</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Eliminar Todo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF Token disponible globalmente
const AdminPanel = {
    csrfToken: '<?php echo $csrf_token ?? ($_SESSION['csrf_token'] ?? ''); ?>'
};

// Debug: Mostrar información del usuario actual
console.log('User info:', {
    userId: '<?php echo $_SESSION['user_id'] ?? 'none'; ?>',
    userRole: '<?php echo $_SESSION['user_role'] ?? 'none'; ?>',
    userRoleLevel: '<?php echo $_SESSION['user_role_level'] ?? 'none'; ?>',
    csrfToken: AdminPanel.csrfToken
});

// Debug: Verificar que el token CSRF no esté vacío
if (!AdminPanel.csrfToken) {
    console.error('CSRF Token is empty or undefined!');
} else {
    console.log('CSRF Token loaded successfully:', AdminPanel.csrfToken);
}

// Función debounce local
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Configurar búsqueda en tiempo real
document.getElementById('search').addEventListener('input', 
    debounce(function(e) {
        const form = e.target.closest('form');
        form.submit();
    }, 1000)
);

// Configurar selección múltiple para eliminar
document.getElementById('select-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    
    updateBulkActionVisibility();
});

// Actualizar visibilidad de acciones en masa
function updateBulkActionVisibility() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    const selectedCount = document.querySelector('.selected-count');
    
    if (bulkActions) {
        if (selected.length > 0) {
            bulkActions.style.display = 'flex';
            if (selectedCount) {
                selectedCount.textContent = selected.length;
            }
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

// Configurar event listeners para checkboxes
document.querySelectorAll('tbody input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActionVisibility);
});

// Variable para almacenar el ID del producto a eliminar
let productToDelete = null;
let deleteModal = null;

// Inicializar modal al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    // Configurar botón de confirmación del modal
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (productToDelete !== null) {
            executeDelete(productToDelete);
            deleteModal.hide();
            productToDelete = null;
        }
    });
});

// Función para mostrar el modal de confirmación
function deleteProduct(id) {
    productToDelete = id;
    deleteModal.show();
}

// Función que ejecuta la eliminación real
function executeDelete(id) {
    // Mostrar indicador de carga
    const loadingHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalHTML = confirmBtn.innerHTML;
    confirmBtn.innerHTML = loadingHTML;
    confirmBtn.disabled = true;
    
    // Obtener token CSRF
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        csrfToken = AdminPanel.csrfToken;
    }
    if (!csrfToken && window.csrfToken) {
        csrfToken = window.csrfToken;
    }
    
    console.log('=== DELETE PRODUCT DEBUG ===');
    console.log('Product ID:', id);
    console.log('CSRF Token:', csrfToken);
    
    if (!csrfToken) {
        alert('Error: No se pudo obtener el token CSRF');
        confirmBtn.innerHTML = originalHTML;
        confirmBtn.disabled = false;
        return;
    }
    
    // Usar POST con FormData
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', csrfToken);
    
    fetch('api/delete_product.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.text().then(text => {
                console.log('Error response:', text);
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        confirmBtn.innerHTML = originalHTML;
        confirmBtn.disabled = false;
        
        if (data.success) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alert.style.cssText = 'top: 80px; right: 20px; z-index: 9999;';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${data.message || 'Producto eliminado correctamente'}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.message || 'Error al eliminar producto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        confirmBtn.innerHTML = originalHTML;
        confirmBtn.disabled = false;
        alert('Error de conexión: ' + error.message);
    });
}

// Eliminar productos seleccionados
function bulkChangeStatus(newStatus) {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        alert('Seleccione al menos un producto');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    // Configurar el modal según el estado
    const statusConfig = {
        'active': {
            name: 'Activo',
            color: 'success',
            icon: 'check-circle'
        },
        'inactive': {
            name: 'Inactivo',
            color: 'secondary',
            icon: 'times-circle'
        },
        'draft': {
            name: 'Borrador',
            color: 'warning',
            icon: 'file-alt'
        }
    };
    
    const config = statusConfig[newStatus];
    
    // Actualizar el modal
    const modal = document.getElementById('bulkStatusModal');
    const header = modal.querySelector('#bulkStatusHeader');
    const icon = modal.querySelector('#bulkStatusIcon');
    const message = modal.querySelector('#bulkStatusMessage');
    const count = modal.querySelector('#bulkStatusCount');
    const statusName = modal.querySelector('#bulkStatusName');
    const confirmBtn = modal.querySelector('#confirmBulkStatusBtn');
    const closeBtn = header.querySelector('.btn-close');
    
    // Cambiar colores del header y botón close
    header.className = `modal-header bg-${config.color} text-white`;
    if (config.color === 'warning') {
        closeBtn.className = 'btn-close'; // Negro para fondo amarillo
    } else {
        closeBtn.className = 'btn-close btn-close-white';
    }
    
    // Actualizar ícono grande
    icon.className = `fas fa-${config.icon} fa-3x mb-3 text-${config.color}`;
    
    // Actualizar contenido
    count.textContent = ids.length;
    statusName.textContent = config.name;
    message.innerHTML = `¿Está seguro de que desea cambiar el estado de <strong>${ids.length}</strong> producto(s) a <strong>${config.name}</strong>?`;
    
    // Configurar el botón de confirmación
    confirmBtn.className = `btn btn-${config.color}`;
    confirmBtn.onclick = function() {
        executeBulkStatusChange(ids, newStatus, config.name);
    };
    
    // Mostrar el modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function executeBulkStatusChange(ids, newStatus, statusName) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkStatusModal'));
    const confirmBtn = document.getElementById('confirmBulkStatusBtn');
    const originalBtnText = confirmBtn.innerHTML;
    
    // Deshabilitar el botón y mostrar estado de carga
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || AdminPanel.csrfToken;
    const formData = new FormData();
    formData.append('product_ids', ids.join(','));
    formData.append('status', newStatus);
    formData.append('csrf_token', csrfToken);
    
    console.log('Bulk status change:', {
        ids: ids,
        status: newStatus,
        csrf: csrfToken
    });
    
    fetch('api/bulk_update_status.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Success:', data);
        
        if (data.success) {
            // Cerrar modal
            modal.hide();
            
            // Mostrar notificación de éxito
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            // Recargar la página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cambiar el estado: ' + error.message);
        
        // Restaurar el botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnText;
    });
}

function bulkDelete() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        alert('Seleccione al menos un producto');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    
    // Actualizar el modal con la cantidad de productos
    const count = document.getElementById('bulkDeleteCount');
    count.textContent = ids.length;
    
    // Configurar el botón de confirmación
    const confirmBtn = document.getElementById('confirmBulkDeleteBtn');
    confirmBtn.onclick = function() {
        executeBulkDelete(ids);
    };
    
    // Mostrar el modal
    const modal = document.getElementById('bulkDeleteModal');
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function executeBulkDelete(ids) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
    const confirmBtn = document.getElementById('confirmBulkDeleteBtn');
    const originalBtnText = confirmBtn.innerHTML;
    
    // Deshabilitar el botón y mostrar estado de carga
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Eliminando...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || AdminPanel.csrfToken;
    
    // Usar POST con FormData para mejor manejo de sesión
    const formData = new FormData();
    formData.append('ids', ids.join(','));
    formData.append('csrf_token', csrfToken);
    
    console.log('Bulk delete:', {
        ids: ids,
        csrf: csrfToken
    });
    
    fetch('api/delete_product.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Bulk delete response status:', response.status);
        
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }).catch(() => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Success:', data);
        
        if (data.success) {
            // Cerrar modal
            modal.hide();
            
            // Mostrar notificación de éxito
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${ids.length} producto(s) eliminado(s) correctamente
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            // Recargar la página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar productos: ' + error.message);
        
        // Restaurar el botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnText;
    });
}
</script>

<?php require_once 'inc/footer.php'; ?>