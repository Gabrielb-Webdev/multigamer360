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
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                <i class="fas fa-trash"></i> Eliminar
            </button>
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

// Eliminar producto individual
function deleteProduct(id) {
    if (confirm('¿Está seguro de que desea eliminar este producto?')) {
        // Obtener token CSRF con múltiples métodos de fallback
        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            csrfToken = AdminPanel.csrfToken;
        }
        if (!csrfToken && window.csrf_token) {
            csrfToken = window.csrf_token;
        }
        
        console.log('=== DELETE PRODUCT DEBUG ===');
        console.log('CSRF Token being sent:', csrfToken);
        console.log('Product ID:', id);
        console.log('AdminPanel object:', AdminPanel);
        
        if (!csrfToken) {
            alert('Error: No se pudo obtener el token CSRF');
            return;
        }
        
        // Usar GET con parámetros URL para evitar problemas con métodos HTTP bloqueados
        const deleteUrl = `api/delete_product.php?id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrfToken)}`;
        
        console.log('Delete URL:', deleteUrl);
        
        fetch(deleteUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            if (!response.ok) {
                return response.text().then(text => {
                    console.log('Error response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                alert('Producto eliminado correctamente');
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar producto');
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión: ' + error.message);
        });
    }
}

// Eliminar productos seleccionados
function bulkDelete() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        alert('Seleccione al menos un producto');
        return;
    }
    
    const ids = Array.from(selected).map(cb => cb.value);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || AdminPanel.csrfToken;
    
    if (confirm(`¿Está seguro de que desea eliminar ${ids.length} producto(s)?`)) {
        // Usar GET con parámetros URL para eliminación en lote
        const idsString = ids.join(',');
        const deleteUrl = `api/delete_product.php?ids=${encodeURIComponent(idsString)}&csrf_token=${encodeURIComponent(csrfToken)}`;
        
        console.log('Bulk delete URL:', deleteUrl);
        
        fetch(deleteUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            console.log('Bulk delete response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.log('Bulk delete error response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(`${ids.length} producto(s) eliminado(s) correctamente`);
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar productos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión: ' + error.message);
        });
    }
}
</script>

<?php require_once 'inc/footer.php'; ?>