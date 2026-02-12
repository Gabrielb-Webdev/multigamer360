<?php
/**
 * =====================================================
 * GESTIÓN DE PRODUCTOS E INVENTARIO
 * =====================================================
 * 
 * Sistema completo de gestión de productos con:
 * - Importación CSV/Excel
 * - Edición en lote
 * - Control de inventario
 * - Imágenes y multimedia
 * 
 * Version: 2.1.1
 * Fecha: 2026-02-06
 * Changelog: Eliminado modal de Auto-Completado Exitoso por solicitud del usuario
 */

$page_title = 'Gestión de Productos e Inventario';
$page_actions = '
    <a href="product_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Producto</a>
    <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#importCsvModal">
        <i class="fas fa-file-excel"></i> Importar Excel
    </button>
    <a href="download_excel_example.php" class="btn btn-info ms-2">
        <i class="fas fa-download"></i> Descargar Ejemplo Excel
    </a>
';

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
$stock_status = $_GET['stock_status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
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

// Filtro por estado de stock
if ($stock_status === 'low') {
    $where_conditions[] = "p.stock_quantity <= 10 AND p.stock_quantity > 0";
} elseif ($stock_status === 'out') {
    $where_conditions[] = "p.stock_quantity = 0";
} elseif ($stock_status === 'good') {
    $where_conditions[] = "p.stock_quantity > 10";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de productos
    $count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    
    // Estadísticas de inventario (usando 10 como nivel mínimo de stock)
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_products,
            SUM(stock_quantity) as total_stock,
            COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 END) as low_stock,
            COUNT(CASE WHEN stock_quantity > 10 THEN 1 END) as good_stock
        FROM products 
        WHERE is_active = 1
    ")->fetch();
    
    // Calcular valores de inventario por separado (en código PHP)
    $inventory_query = $pdo->query("
        SELECT 
            stock_quantity,
            COALESCE(price_pesos, 0) as price_ars,
            COALESCE(price_dollars, 0) as price_usd
        FROM products 
        WHERE is_active = 1
    ");
    
    $inventory_value_ars = 0;
    $inventory_value_usd = 0;
    
    while ($row = $inventory_query->fetch()) {
        $inventory_value_ars += $row['stock_quantity'] * $row['price_ars'];
        $inventory_value_usd += $row['stock_quantity'] * $row['price_usd'];
    }
    
    $stats['inventory_value_ars'] = $inventory_value_ars;
    $stats['inventory_value_usd'] = $inventory_value_usd;
    
    // Obtener productos
    $query = "
        SELECT p.*, c.name as category_name, b.name as brand_name,
               co.name as console_name,
               pi.image_url as main_image,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'Agotado'
                   WHEN p.stock_quantity <= 10 THEN 'Stock Bajo'
                   ELSE 'Stock Normal'
               END as stock_status,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'danger'
                   WHEN p.stock_quantity <= 10 THEN 'warning'
                   ELSE 'success'
               END as stock_color
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN consoles co ON p.console_id = co.id
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
    $stats = ['total_products' => 0, 'total_stock' => 0, 'out_of_stock' => 0, 'low_stock' => 0, 'good_stock' => 0, 'inventory_value' => 0];
}
?>

<!-- Estadísticas de inventario -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Total Productos</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_products']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-boxes fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Stock Total</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_stock']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-warehouse fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Stock Bajo</h6>
                        <h4 class="mb-0 text-warning"><?php echo number_format($stats['low_stock']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Agotado</h6>
                        <h4 class="mb-0 text-danger"><?php echo number_format($stats['out_of_stock']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card border-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title mb-2 opacity-75">Valor Total ARS</h6>
                        <h4 class="mb-0">$<?php echo number_format($stats['inventory_value_ars'], 0); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card border-dark" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title mb-2 opacity-75">Valor Total USD</h6>
                        <h4 class="mb-0">$<?php echo number_format($stats['inventory_value_usd'], 2); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
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
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="stock_status" class="form-label">Estado Stock</label>
                <select class="form-select" id="stock_status" name="stock_status">
                    <option value="">Todos</option>
                    <option value="good" <?php echo $stock_status === 'good' ? 'selected' : ''; ?>>Stock Normal</option>
                    <option value="low" <?php echo $stock_status === 'low' ? 'selected' : ''; ?>>Stock Bajo</option>
                    <option value="out" <?php echo $stock_status === 'out' ? 'selected' : ''; ?>>Agotado</option>
                </select>
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
                            <th>Consola</th>
                            <th>Precio ARS</th>
                            <th>Precio USD</th>
                            <th>Stock</th>
                            <th>Stock Mín.</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
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
                                <?php if (!empty($product['console_name'])): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($product['console_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-primary">$<?php echo number_format($product['price_pesos'] ?? 0, 0); ?></strong>
                            </td>
                            <td>
                                <strong class="text-success">$<?php echo number_format($product['price_dollars'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $product['stock_color']; ?>">
                                    <?php echo number_format($product['stock_quantity']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">10</span>
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
                                    <a href="../<?php echo getProductUrl($product); ?>" 
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

// Función para mostrar modal de ajuste de stock
function showAdjustStockModal(productId, productName, currentStock) {
    document.getElementById('adjust_product_id').value = productId;
    document.getElementById('adjust_product_name').textContent = productName;
    document.getElementById('current_stock').value = currentStock;
    
    // Limpiar formulario
    document.getElementById('adjustStockForm').reset();
    document.getElementById('adjust_product_id').value = productId;
    document.getElementById('current_stock').value = currentStock;
    
    calculateResultingStock();
    
    new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
}

// Calcular stock resultante
function calculateResultingStock() {
    const currentStock = parseInt(document.getElementById('current_stock').value) || 0;
    const adjustmentType = document.getElementById('adjustment_type').value;
    const adjustmentQuantity = parseInt(document.getElementById('adjustment_quantity').value) || 0;
    
    let resultingStock = currentStock;
    
    if (adjustmentType === 'increase') {
        resultingStock = currentStock + adjustmentQuantity;
    } else if (adjustmentType === 'decrease') {
        resultingStock = Math.max(0, currentStock - adjustmentQuantity);
    } else if (adjustmentType === 'set') {
        resultingStock = adjustmentQuantity;
    }
    
    document.getElementById('resulting_stock').textContent = resultingStock;
}

// Event listeners para cálculo automático
document.addEventListener('DOMContentLoaded', function() {
    const adjustTypeElem = document.getElementById('adjustment_type');
    const adjustQtyElem = document.getElementById('adjustment_quantity');
    
    if (adjustTypeElem) {
        adjustTypeElem.addEventListener('change', calculateResultingStock);
    }
    if (adjustQtyElem) {
        adjustQtyElem.addEventListener('input', calculateResultingStock);
    }
    
    // Enviar formulario de ajuste
    const adjustStockForm = document.getElementById('adjustStockForm');
    if (adjustStockForm) {
        adjustStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alert.style.zIndex = '9999';
                    alert.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        Stock ajustado correctamente
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alert);
                    
                    bootstrap.Modal.getInstance(document.getElementById('adjustStockModal')).hide();
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + (data.message || 'Error al ajustar stock'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        });
    }
});
</script>

<!-- Modal Ajustar Stock -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-box me-2"></i>Ajustar Stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustStockForm">
                    <input type="hidden" id="adjust_product_id" name="product_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <div id="adjust_product_name" class="form-control-plaintext fw-bold"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_stock" class="form-label">Stock Actual</label>
                                <input type="number" class="form-control" id="current_stock" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="adjustment_type" class="form-label">Tipo de Ajuste</label>
                                <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="increase">Incrementar (+)</option>
                                    <option value="decrease">Disminuir (-)</option>
                                    <option value="set">Establecer cantidad exacta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adjustment_quantity" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="adjustment_quantity" name="adjustment_quantity" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adjustment_reason" class="form-label">Motivo del Ajuste</label>
                        <select class="form-select" id="adjustment_reason" name="adjustment_reason" required>
                            <option value="">Seleccionar motivo...</option>
                            <option value="purchase">Compra de inventario</option>
                            <option value="sale">Venta</option>
                            <option value="return">Devolución</option>
                            <option value="damage">Producto dañado</option>
                            <option value="theft">Robo/Pérdida</option>
                            <option value="correction">Corrección de inventario</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adjustment_notes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="adjustment_notes" name="adjustment_notes" rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Stock resultante:</strong> <span id="resulting_stock">0</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="adjustStockForm" class="btn btn-warning">
                    <i class="fas fa-check me-2"></i>Aplicar Ajuste
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Subida Excel Simplificado -->
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importCsvModalLabel">
                    <i class="fas fa-file-excel me-2"></i>Subir Excel de Productos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importCsvForm" enctype="multipart/form-data">
                    <!-- Área de Drag & Drop mejorada -->
                    <div class="upload-area" id="uploadArea">
                        <input type="file" class="d-none" id="csvFile" name="csv_file" accept=".xlsx,.xls,.csv" required>
                        
                        <div class="upload-content" id="uploadContent">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                            </div>
                            <h5 class="mb-2">Arrastra tu archivo aquí</h5>
                            <p class="text-muted mb-3">o</p>
                            <button type="button" class="btn btn-outline-primary" id="selectFileBtn">
                                <i class="fas fa-folder-open me-2"></i>Seleccionar Archivo
                            </button>
                            <p class="text-muted small mt-3 mb-0">
                                <i class="fas fa-info-circle me-1"></i>Formatos: .xlsx, .xls, .csv (Máx. 5MB)
                            </p>
                        </div>
                        
                        <div class="file-selected d-none" id="fileSelected">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="file-icon me-3">
                                        <i class="fas fa-file-excel fa-3x text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" id="fileName">archivo.xlsx</h6>
                                        <small class="text-muted" id="fileSize">0 KB</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="removeFileBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress d-none mt-3" id="uploadProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div id="importResult" class="mt-3"></div>
                </form>
                
                <style>
                .upload-area {
                    border: 2px dashed #dee2e6;
                    border-radius: 12px;
                    padding: 40px 20px;
                    text-align: center;
                    transition: all 0.3s ease;
                    background: #f8f9fa;
                    cursor: pointer;
                }
                
                .upload-area:hover {
                    border-color: #0d6efd;
                    background: #e7f1ff;
                }
                
                .upload-area.drag-over {
                    border-color: #0d6efd;
                    background: #cfe2ff;
                    border-style: solid;
                    transform: scale(1.02);
                }
                
                .upload-icon i {
                    transition: transform 0.3s ease;
                }
                
                .upload-area:hover .upload-icon i {
                    transform: translateY(-5px);
                }
                
                .file-selected {
                    padding: 20px;
                    background: #fff;
                    border: 2px solid #198754;
                    border-radius: 12px;
                }
                
                .upload-area:has(.file-selected:not(.d-none)) {
                    cursor: default;
                }
                
                .upload-area:has(.file-selected:not(.d-none)):hover {
                    border-color: #198754;
                    background: #fff;
                    transform: none;
                }
                
                .file-icon {
                    width: 60px;
                    height: 60px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #d1e7dd;
                    border-radius: 8px;
                }
                </style>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="processExcelBtn">
                    <i class="fas fa-upload me-2"></i>Subir y Revisar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Revisión Producto por Producto -->
<div class="modal fade" id="reviewProductModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Revisar Producto <span id="productCounter"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" id="closeReviewModal"></button>
            </div>
            <div class="modal-body" style="overflow-y: auto; background-color: #f8f9fa;">
                <div class="container-fluid">
                    <!-- Información del Excel -->
                    <div class="alert alert-info mb-4">
                        <h6 class="mb-3"><i class="fas fa-file-excel me-2"></i>Datos del Excel:</h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <strong>Nombre:</strong> <span id="csvTitle"></span>
                            </div>
                            <div class="col-md-2">
                                <strong>Tipo:</strong> <span id="csvType" class="badge bg-info"></span>
                            </div>
                            <div class="col-md-2">
                                <strong>Consola:</strong> <span id="csvConsole"></span>
                            </div>
                            <div class="col-md-2">
                                <strong>Stock:</strong> <span id="csvStock"></span>
                            </div>
                            <div class="col-md-2">
                                <strong>Precio COP:</strong> <span id="csvPriceCop"></span>
                            </div>
                            <div class="col-md-1">
                                <strong>USD:</strong> <span id="csvPriceUsd"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón para auto-completar -->
                    <div class="text-center mb-4">
                        <button type="button" class="btn btn-success btn-lg" id="autoCompleteBtn" style="min-width: 300px;">
                            <i class="fas fa-magic me-2"></i>Auto-Rellenar Información del Juego
                        </button>
                        <div><small class="text-muted">Busca automáticamente descripción, géneros, plataforma, etc.</small></div>
                    </div>

                    <form id="productReviewForm">
                        <div class="row">
                            <!-- Columna Izquierda - Información Básica -->
                            <div class="col-lg-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle me-2"></i>Información Básica
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="editTitle" class="form-label">Nombre del Producto *</label>
                                            <input type="text" class="form-control" id="editTitle" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="editProductType" class="form-label">Tipo de Producto *</label>
                                                <select class="form-select" id="editProductType" required>
                                                    <option value="game">Juego</option>
                                                    <option value="console">Consola</option>
                                                    <option value="accessory">Accesorio</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="editSku" class="form-label">SKU</label>
                                                <input type="text" class="form-control" id="editSku" placeholder="Se generará automáticamente">
                                                <div class="form-text">Dejar vacío para auto-generar</div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="editStatus" class="form-label">Estado *</label>
                                                <select class="form-select" id="editStatus" required>
                                                    <option value="1">Activo</option>
                                                    <option value="0">Inactivo</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="editDescription" class="form-label">Descripción *</label>
                                            <textarea class="form-control" id="editDescription" rows="6" required></textarea>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="editFeatured" value="1">
                                            <label class="form-check-label" for="editFeatured">
                                                Producto Destacado
                                            </label>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="editNew" value="1" checked>
                                            <label class="form-check-label" for="editNew">
                                                ⭐ Novedad (aparece en "Novedades" del home)
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="editOnSale" value="1">
                                            <label class="form-check-label" for="editOnSale">
                                                🔥 En Oferta
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Imágenes -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-images me-2"></i>Imágenes del Producto
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-images me-2"></i>Imágenes del Producto
                                            </label>
                                            <div class="upload-area border border-2 border-dashed rounded-3 p-4 text-center" id="imageUploadArea" style="cursor: pointer; transition: all 0.3s;">
                                                <input type="file" class="d-none" id="editImages" multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                                                <div class="upload-icon mb-3">
                                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                                                </div>
                                                <h5 class="mb-2">Arrastra imágenes aquí o haz clic para seleccionar</h5>
                                                <p class="text-muted mb-0">
                                                    <small><i class="fas fa-info-circle me-1"></i>Formatos: JPG, PNG, WebP • Máximo 5MB por imagen</small>
                                                </p>
                                            </div>
                                        </div>
                                        <div id="imagePreview" class="row g-3 mt-2"></div>
                                    </div>
                                </div>

                                <!-- SEO -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-search me-2"></i>SEO - Optimización para Buscadores
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="editMetaTitle" class="form-label">Meta Título</label>
                                            <input type="text" class="form-control" id="editMetaTitle" maxlength="60">
                                            <div class="form-text">
                                                <i class="fas fa-lightbulb"></i> Recomendado: 50-60 caracteres
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="editMetaDescription" class="form-label">Meta Descripción</label>
                                            <textarea class="form-control" id="editMetaDescription" rows="3" maxlength="160"></textarea>
                                            <div class="form-text">
                                                <i class="fas fa-lightbulb"></i> Recomendado: 150-160 caracteres
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha - Precios y Categorización -->
                            <div class="col-lg-4">
                                <!-- Precios e Inventario -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-dollar-sign me-2"></i>Precios e Inventario
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="editPriceCop" class="form-label">Precio en Pesos (COP) *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="editPriceCop" min="0" step="0.01" required>
                                                <span class="input-group-text">COP</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="editPriceUsd" class="form-label">Precio en Dólares (USD)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="editPriceUsd" min="0" step="0.01">
                                                <span class="input-group-text">USD</span>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="mb-3">
                                            <label for="editStock" class="form-label">Cantidad en Stock *</label>
                                            <input type="number" class="form-control form-control-lg" id="editStock" value="0" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Categorización -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-tags me-2"></i>Categorización
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="editCategory" class="form-label">Categoría *</label>
                                            <div class="input-group">
                                                <select class="form-select" id="editCategory" required>
                                                    <option value="">Seleccionar categoría</option>
                                                </select>
                                                <button class="btn btn-outline-success" type="button" id="addCategoryBtn" title="Agregar nueva categoría">
                                                    <i class="fas fa-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="editBrand" class="form-label">Marca *</label>
                                            <div class="input-group">
                                                <select class="form-select" id="editBrand" required>
                                                    <option value="">Seleccionar marca</option>
                                                </select>
                                                <button class="btn btn-outline-success" type="button" id="addBrandBtn" title="Agregar nueva marca">
                                                    <i class="fas fa-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="editConsole" class="form-label">Consola / Plataforma *</label>
                                            <div class="input-group">
                                                <select class="form-select" id="editConsole" required>
                                                    <option value="">Seleccionar consola</option>
                                                </select>
                                                <button class="btn btn-outline-success" type="button" id="addConsoleBtn" title="Agregar nueva consola">
                                                    <i class="fas fa-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label d-flex justify-content-between align-items-center">
                                                <span>Géneros</span>
                                                <button class="btn btn-sm btn-outline-success" type="button" id="addGenreBtn" title="Agregar nuevo género">
                                                    <i class="fas fa-plus"></i> Agregar género
                                                </button>
                                            </label>
                                            <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;" id="editGenres">
                                                <!-- Se llenará dinámicamente con checkboxes -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer bg-white border-top">
                <div class="me-auto">
                    <button type="button" class="btn btn-outline-danger" id="skipProductBtn">
                        <i class="fas fa-times me-2"></i>Saltar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="previousProductBtn" disabled>
                        <i class="fas fa-arrow-left me-2"></i>Anterior
                    </button>
                </div>
                <button type="button" class="btn btn-success btn-lg" id="saveProductBtn">
                    <i class="fas fa-save me-2"></i>Guardar y Siguiente
                </button>
                <button type="button" class="btn btn-primary btn-lg d-none" id="finishImportBtn">
                    <i class="fas fa-check-circle me-2"></i>Finalizar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lightbox para Ver Imágenes -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="lightboxImage" src="" alt="Vista previa" class="img-fluid" style="max-height: 80vh; width: auto;">
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <div id="lightboxCaption" class="text-white"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación: Saltar Producto -->
<div class="modal fade" id="skipProductConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>¿Saltar Producto?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Este producto no se guardará y pasarás al siguiente en la lista.</p>
                <p class="mb-0 mt-2"><strong>¿Estás seguro de que deseas continuar?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="confirmSkipProductBtn">
                    <i class="fas fa-forward me-2"></i>Sí, Saltar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación: Cerrar sin Guardar -->
<div class="modal fade" id="closeWithoutSavingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-circle me-2"></i>¿Cerrar sin Guardar?
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Si cierras ahora, se perderá todo el progreso de la importación.</p>
                <p class="mb-0 mt-2">Los productos no guardados no se añadirán al sistema.</p>
                <p class="mb-0 mt-3"><strong>¿Estás seguro de que deseas salir?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-2"></i>Continuar Editando
                </button>
                <button type="button" class="btn btn-danger" id="confirmCloseWithoutSavingBtn">
                    <i class="fas fa-sign-out-alt me-2"></i>Sí, Salir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modales para Agregar Nuevos Elementos -->
<!-- Modal: Agregar Categoría -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Categoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newCategoryName" class="form-label">Nombre de la Categoría *</label>
                    <input type="text" class="form-control" id="newCategoryName" placeholder="Ej: Ediciones Especiales" required>
                </div>
                <div id="addCategoryResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveCategoryBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Marca -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Marca</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newBrandName" class="form-label">Nombre de la Marca *</label>
                    <input type="text" class="form-control" id="newBrandName" placeholder="Ej: PlayStation Studios" required>
                </div>
                <div id="addBrandResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveBrandBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Consola -->
<div class="modal fade" id="addConsoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Consola</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newConsoleName" class="form-label">Nombre de la Consola *</label>
                    <input type="text" class="form-control" id="newConsoleName" placeholder="Ej: PlayStation 1" required>
                </div>
                <div id="addConsoleResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveConsoleBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Género -->
<div class="modal fade" id="addGenreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Género</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newGenreName" class="form-label">Nombre del Género *</label>
                    <input type="text" class="form-control" id="newGenreName" placeholder="Ej: Battle Royale" required>
                </div>
                <div id="addGenreResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveGenreBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let productsToReview = [];
let currentProductIndex = 0;
let dropdownData = {};
let savedProducts = [];

// Función helper para setear innerHTML de forma segura
function safeSetHTML(elementId, htmlContent) {
    try {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = htmlContent;
            return true;
        } else {
            console.warn('Elemento no encontrado: ' + elementId);
            return false;
        }
    } catch (e) {
        console.error('Error al setear HTML:', e);
        return false;
    }
}

// Funcionalidad de Drag & Drop mejorada
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('csvFile');
const selectFileBtn = document.getElementById('selectFileBtn');
const removeFileBtn = document.getElementById('removeFileBtn');
const uploadContent = document.getElementById('uploadContent');
const fileSelected = document.getElementById('fileSelected');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');

// Prevenir comportamiento por defecto del navegador
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight cuando se arrastra sobre el área
['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.classList.add('drag-over');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.classList.remove('drag-over');
    }, false);
});

// Manejar drop
uploadArea.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(files[0]);
    }
}, false);

// Click en el botón seleccionar
selectFileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.click();
});

// Click en toda el área (solo cuando NO hay archivo seleccionado)
uploadArea.addEventListener('click', (e) => {
    // No abrir si ya hay un archivo seleccionado
    if (!fileSelected.classList.contains('d-none')) {
        return;
    }
    
    // No abrir si se hizo click en el botón de remover o seleccionar
    if (e.target !== removeFileBtn && !removeFileBtn.contains(e.target) &&
        e.target !== selectFileBtn && !selectFileBtn.contains(e.target)) {
        fileInput.click();
    }
});

// Cuando se selecciona un archivo
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

// Remover archivo
removeFileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value = '';
    uploadContent.classList.remove('d-none');
    fileSelected.classList.add('d-none');
    uploadArea.style.border = '2px dashed #dee2e6';
    uploadArea.style.cursor = 'pointer';
});

// Función para manejar archivo seleccionado
function handleFileSelect(file) {
    // Validar tipo de archivo
    const validTypes = ['.xlsx', '.xls', '.csv'];
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    if (!validTypes.includes(fileExtension)) {
        alert('Por favor selecciona un archivo válido (.xlsx, .xls, .csv)');
        fileInput.value = '';
        return;
    }
    
    // Validar tamaño (5MB máximo)
    if (file.size > 5 * 1024 * 1024) {
        alert('El archivo es demasiado grande. Máximo 5MB.');
        fileInput.value = '';
        return;
    }
    
    // Mostrar información del archivo
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    
    uploadContent.classList.add('d-none');
    fileSelected.classList.remove('d-none');
    uploadArea.style.border = '2px solid #198754';
    uploadArea.style.cursor = 'default';
    uploadArea.style.background = '#fff';
}

// Formatear tamaño de archivo
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Paso 1: Procesar Excel
document.getElementById('processExcelBtn').addEventListener('click', function() {
    const fileInput = document.getElementById('csvFile');
    const progressBar = document.getElementById('uploadProgress');
    const resultDiv = document.getElementById('importResult');
    
    if (!fileInput.files[0]) {
        if (resultDiv) resultDiv.innerHTML = '<div class="alert alert-danger">Selecciona un archivo</div>';
        return;
    }
    
    const formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);
    
    progressBar.classList.remove('d-none');
    this.disabled = true;
    
    fetch('ajax/process_csv_preview.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Primero obtener el texto de la respuesta
        return response.text().then(text => {
            console.log('Response text (first 500 chars):', text.substring(0, 500));
            
            // Limpiar cualquier texto antes del JSON
            // Buscar el primer { que indica el inicio del objeto JSON principal
            const jsonStart = text.indexOf('{');
            
            if (jsonStart > 0) {
                console.warn('Se encontró texto antes del JSON, limpiando...');
                text = text.substring(jsonStart);
            }
            
            // También limpiar cualquier texto después del JSON
            // Buscar el último } que cierra el objeto JSON
            const jsonEnd = text.lastIndexOf('}');
            if (jsonEnd > 0 && jsonEnd < text.length - 1) {
                console.warn('Se encontró texto después del JSON, limpiando...');
                text = text.substring(0, jsonEnd + 1);
            }
            
            // Intentar parsear como JSON
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Texto completo de respuesta:', text);
                throw new Error('La respuesta del servidor no es JSON válido. Revisa la consola para más detalles.');
            }
        });
    })
    .then(data => {
        if (data.success) {
            productsToReview = data.products;
            dropdownData = data.dropdowns;
            
            // Cerrar modal de subida
            // Cerrar modal de importación
            const uploadModal = bootstrap.Modal.getInstance(document.getElementById('importCsvModal'));
            if (uploadModal) {
                uploadModal.hide();
            }
            
            // Esperar a que el modal se cierre antes de abrir el siguiente
            setTimeout(() => {
                showReviewModal();
            }, 500);
        } else {
            // Mostrar error en el modal de subida
            let errorHtml = `<div class="alert alert-danger">
                <strong>Error:</strong> ${data.message}`;
            
            if (data.debug) {
                errorHtml += `<hr><small><strong>Debug:</strong><br>`;
                if (data.debug.headers) {
                    errorHtml += `Headers: ${data.debug.headers.join(', ')}<br>`;
                }
                if (data.debug.total_rows) {
                    errorHtml += `Total filas: ${data.debug.total_rows}`;
                }
                errorHtml += `</small>`;
            }
            
            errorHtml += `</div>`;
            if (resultDiv) resultDiv.innerHTML = errorHtml;
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Mostrar error en el modal de subida
        if (resultDiv) resultDiv.innerHTML = `<div class="alert alert-danger">
            <strong>Error:</strong> ${error.message}<br>
            <small>Revisa la consola del navegador para más detalles (F12)</small>
        </div>`;
    })
    .finally(() => {
        progressBar.classList.add('d-none');
        this.disabled = false;
    });
});

// ==================== MODAL DE REVISIÓN DE PRODUCTOS REHECHO ====================

// Función para mostrar el modal de revisión
function showReviewModal() {
    const reviewModalEl = document.getElementById('reviewProductModal');
    if (!reviewModalEl) {
        alert('Error: Modal de revisión no encontrado');
        return;
    }
    
    const reviewModal = new bootstrap.Modal(reviewModalEl, {
        backdrop: 'static',
        keyboard: false
    });
    
    // Cargar primer producto cuando el modal se muestre
    reviewModalEl.addEventListener('shown.bs.modal', function handler() {
        reviewModalEl.removeEventListener('shown.bs.modal', handler);
        setTimeout(() => {
            if (!loadProductReview(0)) {
                alert('Error al cargar el primer producto');
                reviewModal.hide();
            }
        }, 100);
    });
    
    reviewModal.show();
}

// Función para cargar un producto en el modal
function loadProductReview(index) {
    if (index < 0 || index >= productsToReview.length) {
        return false;
    }
    
    currentProductIndex = index;
    const product = productsToReview[index];
    
    try {
        // Limpiar imágenes previas
        window.downloadedGameImages = [];
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview) imagePreview.innerHTML = '';
        
        const imageInput = document.getElementById('editImages');
        if (imageInput) imageInput.value = '';
        
        // Actualizar contador
        const counter = document.getElementById('productCounter');
        if (counter) counter.textContent = `(${index + 1} de ${productsToReview.length})`;
        
        // Datos del CSV
        const elements = {
            csvTitle: product.title,
            csvConsole: product.console_name || '-',
            csvStock: product.stock || '0',
            csvPriceCop: '$' + Number(product.price_cop || 0).toLocaleString(),
            csvPriceUsd: product.price_usd ? '$' + product.price_usd : '-'
        };
        
        for (const [id, value] of Object.entries(elements)) {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        }
        
        // Badge de tipo
        const csvType = document.getElementById('csvType');
        if (csvType) {
            const types = {
                'game': { label: 'Juego', class: 'bg-success' },
                'console': { label: 'Consola', class: 'bg-primary' },
                'accessory': { label: 'Accesorio', class: 'bg-warning' }
            };
            const type = types[product.product_type] || types['game'];
            csvType.textContent = type.label;
            csvType.className = 'badge ' + type.class;
        }
        
        // Llenar formulario
        document.getElementById('editTitle').value = product.title;
        document.getElementById('editProductType').value = product.product_type || 'game';
        document.getElementById('editSku').value = product.sku || '';
        document.getElementById('editStatus').value = product.status;
        document.getElementById('editPriceCop').value = product.price_cop;
        document.getElementById('editPriceUsd').value = product.price_usd || '';
        document.getElementById('editStock').value = product.stock || 0;
        document.getElementById('editDescription').value = product.description || '';
        document.getElementById('editFeatured').checked = product.is_featured == 1;
        document.getElementById('editNew').checked = product.is_new == 1;
        document.getElementById('editOnSale').checked = product.on_sale == 1;
        document.getElementById('editMetaTitle').value = product.meta_title || (product.title + ' - MultiGamer360');
        
        // Generar meta descripción automática si no existe (importante para SEO)
        if (product.meta_description) {
            document.getElementById('editMetaDescription').value = product.meta_description;
        } else if (product.description) {
            // Crear descripción corta optimizada para SEO
            let metaDesc = product.description
                .replace(/<[^>]*>/g, '')
                .replace(/\s+/g, ' ')
                .trim();
            
            if (metaDesc.length > 155) {
                metaDesc = metaDesc.substring(0, 152) + '...';
            } else if (metaDesc.length < 130) {
                metaDesc += ' | Compra en MultiGamer360';
            }
            
            document.getElementById('editMetaDescription').value = metaDesc;
        } else {
            // Generar descripción básica desde el título
            const basicDesc = `Compra ${product.title} en MultiGamer360. Tienda online de videojuegos, consolas y accesorios gaming. Envíos a todo el país.`;
            document.getElementById('editMetaDescription').value = basicDesc;
        }
        document.getElementById('editMetaTitle').value = product.meta_title || '';
        document.getElementById('editMetaDescription').value = product.meta_description || '';
        
        // Llenar dropdowns
        populateDropdown('editCategory', dropdownData.categories, product.category_id);
        populateDropdown('editBrand', dropdownData.brands, product.brand_id);
        populateDropdown('editConsole', dropdownData.consoles, product.console_id);
        populateGenreCheckboxes('editGenres', dropdownData.genres, product.genres);
        
        // Botones de navegación
        document.getElementById('previousProductBtn').disabled = index === 0;
        document.getElementById('saveProductBtn').classList.toggle('d-none', index === productsToReview.length - 1);
        document.getElementById('finishImportBtn').classList.toggle('d-none', index < productsToReview.length - 1);
        
        return true;
    } catch (error) {
        console.error('Error al cargar producto:', error);
        return false;
    }
}

function populateDropdown(selectId, items, selectedValue) {
    const select = document.getElementById(selectId);
    if (!select) {
        console.error(`Elemento ${selectId} no encontrado`);
        return;
    }
    select.innerHTML = '<option value="">Seleccionar...</option>';
    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        if (item.id == selectedValue) option.selected = true;
        select.appendChild(option);
    });
}

// Función para recargar dropdowns desde el servidor
function reloadDropdowns() {
    fetch('ajax/process_csv_preview.php?reload_dropdowns=1')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.dropdowns) {
            // Actualizar la variable global
            dropdownData = data.dropdowns;
            
            // Recargar categorías
            populateDropdown('editCategory', dropdownData.categories, '');
            
            // Recargar marcas
            populateDropdown('editBrand', dropdownData.brands, '');
            
            // Recargar consolas
            populateDropdown('editConsole', dropdownData.consoles, '');
            
            // Recargar géneros (checkboxes)
            populateGenreCheckboxes('editGenres', dropdownData.genres, []);
        }
    })
    .catch(error => {
        console.error('Error al recargar dropdowns:', error);
    });
}

function populateGenreCheckboxes(containerId, items, selectedValues) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Elemento ${containerId} no encontrado`);
        return;
    }
    
    container.innerHTML = '';
    
    if (!items || items.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0">No hay géneros disponibles</p>';
        return;
    }
    
    const row = document.createElement('div');
    row.className = 'row';
    
    items.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-2';
        
        const formCheck = document.createElement('div');
        formCheck.className = 'form-check';
        
        const checkbox = document.createElement('input');
        checkbox.className = 'form-check-input';
        checkbox.type = 'checkbox';
        checkbox.name = 'genres[]';
        checkbox.value = item.id;
        checkbox.id = 'genre_' + item.id;
        if (selectedValues && selectedValues.includes(item.id)) {
            checkbox.checked = true;
        }
        
        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = 'genre_' + item.id;
        label.textContent = item.name;
        
        formCheck.appendChild(checkbox);
        formCheck.appendChild(label);
        col.appendChild(formCheck);
        row.appendChild(col);
    });
    
    container.appendChild(row);
}

// Función para actualizar vista previa de imágenes
function updateImagePreview() {
    const imagePreview = document.getElementById('imagePreview');
    if (!imagePreview) {
        console.warn('Elemento imagePreview no encontrado');
        return;
    }
    
    imagePreview.innerHTML = '';
    
    if (!window.downloadedGameImages || window.downloadedGameImages.length === 0) {
        return;
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${window.downloadedGameImages.length} imagen(es) descargadas desde RAWG
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    imagePreview.appendChild(alertDiv);
    
    window.downloadedGameImages.forEach((imagePath, index) => {
        const imgContainer = document.createElement('div');
        imgContainer.className = 'position-relative d-inline-block me-2 mb-2';
        imgContainer.style.width = '150px';
        imgContainer.style.height = '150px';
        imgContainer.dataset.imageIndex = index;
        
        const img = document.createElement('img');
        img.src = imagePath.startsWith('http') ? imagePath : '../../' + imagePath;
        img.className = 'img-thumbnail';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.cursor = 'pointer';
        img.title = 'Click para ver en grande';
        
        // Click para ver en grande
        img.onclick = function() {
            openLightbox(this.src, 'Imagen RAWG #' + (index + 1));
        };
        
        const badge = document.createElement('span');
        badge.className = 'position-absolute top-0 start-0 badge bg-success';
        badge.textContent = index === 0 ? 'Principal' : `#${index + 1}`;
        badge.style.margin = '5px';
        
        const downloadIcon = document.createElement('span');
        downloadIcon.className = 'position-absolute bottom-0 end-0 badge bg-primary';
        downloadIcon.innerHTML = '<i class="fas fa-download"></i>';
        downloadIcon.style.margin = '5px';
        
        // Botón de eliminar
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
        deleteBtn.style.margin = '5px';
        deleteBtn.style.padding = '2px 6px';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.title = 'Eliminar imagen';
        deleteBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remover del array
            window.downloadedGameImages.splice(index, 1);
            
            // Actualizar vista previa
            updateImagePreview();
        };
        
        imgContainer.appendChild(img);
        imgContainer.appendChild(badge);
        imgContainer.appendChild(downloadIcon);
        imgContainer.appendChild(deleteBtn);
        imagePreview.appendChild(imgContainer);
    });
}

// Función para auto-completar información básica de consolas y accesorios
function autoCompleteBasic(productName, productType, btn) {
    // Intentar obtener información de Wikipedia
    fetch('ajax/autocomplete_console_info.php?product_name=' + encodeURIComponent(productName) + '&product_type=' + productType)
    .then(response => {
        // Verificar si la respuesta es válida
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        // Intentar parsear JSON
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no es JSON válido:', text.substring(0, 200));
                throw new Error('Respuesta inválida del servidor');
            }
        });
    })
    .then(data => {
        if (data.success && data.data) {
            const typeText = productType === 'console' ? 'consola' : 'accesorio';
            const brandSelect = document.getElementById('editBrand');
            const brandName = brandSelect.options[brandSelect.selectedIndex]?.text || '';
            
            // Llenar descripción solo si está vacía
            const descField = document.getElementById('editDescription');
            if ((!descField.value || descField.value.trim() === '') && data.data.description) {
                let description = data.data.description;
                
                // Si viene de Wikipedia y es muy larga, recortar
                if (data.data.source === 'wikipedia' && description.length > 500) {
                    description = description.substring(0, 497) + '...';
                }
                
                // Agregar mención de la tienda
                if (!description.includes('MultiGamer360')) {
                    description += ' | Disponible en MultiGamer360.';
                }
                
                descField.value = description;
            }
            
            // Auto-seleccionar marca basada en especificaciones técnicas
            if ((!brandSelect.value || brandSelect.value === '') && data.data.specs && data.data.specs.fabricante) {
                const fabricante = data.data.specs.fabricante.toLowerCase();
                for (let i = 0; i < brandSelect.options.length; i++) {
                    const optionText = brandSelect.options[i].text.toLowerCase();
                    if (optionText.includes(fabricante) || fabricante.includes(optionText)) {
                        brandSelect.value = brandSelect.options[i].value;
                        console.log('✓ Marca detectada:', brandSelect.options[i].text);
                        break;
                    }
                }
            }
            
            // Generar SKU solo si está vacío
            // Versión 2.1 - SKU con formato: PREFIX(6)-TIMESTAMP(5)-RANDOM(4)
            const skuField = document.getElementById('editSku');
            if (!skuField.value || skuField.value.trim() === '') {
                // Extraer caracteres alfanuméricos del nombre del producto
                const clean = productName.replace(/[^a-zA-Z0-9]/g, '');
                
                // Tomar hasta 6 caracteres del título
                let prefix = clean.substring(0, 6).toUpperCase();
                
                // Si el prefijo es muy corto, rellenar
                if (prefix.length < 3) {
                    prefix = prefix.padEnd(3, 'X');
                }
                
                // Generar sufijo con timestamp + random más robusto
                // Formato: PREFIJO-TIMESTAMP(5)-RANDOM(4)
                const timestamp = Math.floor(Date.now() / 1000).toString().slice(-5);
                const random = Math.floor(Math.random() * 9000) + 1000;
                
                const sku = `${prefix}-${timestamp}${random}`;
                skuField.value = sku;
                console.log('✓ SKU generado:', sku);
            }
            
            // Generar meta título solo si está vacío
            const metaTitleField = document.getElementById('editMetaTitle');
            if (!metaTitleField.value || metaTitleField.value.trim() === '') {
                let metaTitle = productName;
                const selectedBrand = brandSelect.options[brandSelect.selectedIndex]?.text || '';
                if (selectedBrand && selectedBrand !== 'Seleccionar marca' && selectedBrand !== 'Seleccionar...') {
                    metaTitle += ` ${selectedBrand}`;
                }
                metaTitle += ' | MultiGamer360';
                
                if (metaTitle.length > 60) {
                    metaTitle = metaTitle.substring(0, 57) + '...';
                }
                metaTitleField.value = metaTitle;
            }
            
            // Generar meta descripción solo si está vacía
            const metaDescField = document.getElementById('editMetaDescription');
            if (!metaDescField.value || metaDescField.value.trim() === '') {
                const selectedBrand = brandSelect.options[brandSelect.selectedIndex]?.text || '';
                let metaDescription = `${productName} - ${typeText.charAt(0).toUpperCase() + typeText.slice(1)}`;
                if (selectedBrand && selectedBrand !== 'Seleccionar marca' && selectedBrand !== 'Seleccionar...') {
                    metaDescription += ` ${selectedBrand}`;
                }
                
                // Agregar especificaciones clave si existen
                if (data.data.specs) {
                    if (data.data.specs.año_lanzamiento) {
                        metaDescription += ` (${data.data.specs.año_lanzamiento})`;
                    }
                }
                
                metaDescription += ' | Disponible en MultiGamer360 con garantía y envíos a todo Colombia';
                
                if (metaDescription.length > 160) {
                    metaDescription = metaDescription.substring(0, 157) + '...';
                }
                metaDescField.value = metaDescription;
            }
            
            // Seleccionar categoría "Hardware" o "Consolas" si está vacía
            const categorySelect = document.getElementById('editCategory');
            if ((!categorySelect.value || categorySelect.value === '') && productType === 'console') {
                // Buscar categoría Hardware o Consolas
                for (let i = 0; i < categorySelect.options.length; i++) {
                    const optionText = categorySelect.options[i].text.toLowerCase();
                    if (optionText.includes('consola') || 
                        optionText.includes('console') ||
                        optionText.includes('hardware')) {
                        categorySelect.value = categorySelect.options[i].value;
                        console.log('✓ Categoría seleccionada:', categorySelect.options[i].text);
                        break;
                    }
                }
            } else if ((!categorySelect.value || categorySelect.value === '') && productType === 'accessory') {
                // Buscar categoría Accesorios (plural)
                for (let i = 0; i < categorySelect.options.length; i++) {
                    const optionText = categorySelect.options[i].text.toLowerCase();
                    if (optionText.includes('accesorio')) {
                        categorySelect.value = categorySelect.options[i].value;
                        console.log('✓ Categoría seleccionada:', categorySelect.options[i].text);
                        break;
                    }
                }
            }
            
            // Si hay imagen de Wikipedia, agregarla
            let imagePromise = Promise.resolve();
            if (data.data.images && data.data.images.length > 0) {
                console.log('Descargando imágenes:', data.data.images);
                
                // Descargar todas las imágenes disponibles
                const imageDownloadPromises = data.data.images.slice(0, 5).map(imageUrl => {
                    return fetch('ajax/download_image.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'image_url=' + encodeURIComponent(imageUrl)
                    })
                    .then(response => response.json())
                    .then(imageData => {
                        if (imageData.success) {
                            console.log('✓ Imagen descargada:', imageData.path);
                            return imageData.path;
                        }
                        return null;
                    })
                    .catch(error => {
                        console.log('✗ Error al descargar imagen:', error);
                        return null;
                    });
                });
                
                // Esperar a que se descarguen todas las imágenes
                imagePromise = Promise.all(imageDownloadPromises).then(downloadedPaths => {
                    const validPaths = downloadedPaths.filter(path => path !== null);
                    if (validPaths.length > 0) {
                        window.downloadedGameImages = validPaths;
                        updateImagePreview();
                        console.log(`✓ ${validPaths.length} imagen(es) descargada(s)`);
                        return validPaths.length;
                    }
                    return 0;
                });
            } else if (data.data.image) {
                // Fallback: si solo hay una imagen principal
                imagePromise = fetch('ajax/download_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'image_url=' + encodeURIComponent(data.data.image)
                })
                .then(response => response.json())
                .then(imageData => {
                    if (imageData.success) {
                        window.downloadedGameImages = [imageData.path];
                        updateImagePreview();
                        console.log('✓ Imagen descargada:', imageData.path);
                        return 1;
                    }
                    return 0;
                })
                .catch(error => {
                    console.log('Error al descargar imagen:', error);
                    return 0;
                });
            }
            
            // Marcar género "_CONSOLA_" si es consola o accesorio
            if (productType === 'console' || productType === 'accessory') {
                const genreCheckboxes = document.querySelectorAll('input[name="genres[]"]');
                genreCheckboxes.forEach(checkbox => {
                    const label = checkbox.nextElementSibling;
                    if (label && label.textContent.trim() === '_CONSOLA_') {
                        checkbox.checked = true;
                        console.log('✓ Género _CONSOLA_ marcado automáticamente');
                    }
                });
            }
            
            // Mostrar modal de éxito cuando todo esté listo
            imagePromise.then(imageCount => {
                const sourceText = data.data.source === 'wikipedia+specs' ? 'Wikipedia con especificaciones técnicas' : 
                                   data.data.source === 'wikipedia' ? 'Wikipedia' : 
                                   'generación automática';
                
                // Construir contenido del modal
                let successContent = `
                    <div class="alert alert-success border-0 mb-3">
                        <h6 class="alert-heading mb-2">
                            <i class="fas fa-info-circle me-2"></i>Fuente de Información
                        </h6>
                        <p class="mb-0">${sourceText}</p>
                    </div>
                    
                    <h6 class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Campos Completados:</h6>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>SKU único generado</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Descripción del producto generada</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Meta título SEO creado</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Meta descripción SEO creada</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Categoría seleccionada automáticamente</li>`;
                
                if (imageCount > 0) {
                    successContent += `<li class="mb-2"><i class="fas fa-images text-success me-2"></i>${imageCount} imagen(es) descargada(s) exitosamente</li>`;
                } else {
                    successContent += `<li class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i>No se pudieron descargar imágenes</li>`;
                }
                
                if (data.data.specs && Object.keys(data.data.specs).length > 0) {
                    successContent += `<li class="mb-2"><i class="fas fa-microchip text-success me-2"></i>Especificaciones técnicas incluidas en la descripción</li>`;
                }
                
                successContent += `<li class="mb-2"><i class="fas fa-check text-success me-2"></i>Género "_CONSOLA_" marcado</li>`;
                successContent += `</ul>
                    
                    <div class="alert alert-info border-0 mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        <small>Revisa y ajusta la información según sea necesario antes de guardar.</small>
                    </div>`;
                
                // Modal de éxito eliminado por solicitud del usuario
                // Solo mostrar en consola
                console.log('✓ Auto-completado exitoso');
                
                console.log(`✓ Información completada desde ${sourceText}`);
            });
        } else {
            console.log('⚠ No se pudo obtener información adicional');
            // Aún así generar descripción básica
            const descField = document.getElementById('editDescription');
            if (!descField.value || descField.value.trim() === '') {
                const typeText = productType === 'console' ? 'consola' : 'accesorio';
                descField.value = `${productName} es una ${typeText} de alta calidad. Encuentra este producto en MultiGamer360.`;
            }
            
            // Mostrar modal de advertencia
            let warningContent = `
                <div class="alert alert-warning border-0 mb-3">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>Información Parcial
                    </h6>
                    <p class="mb-0">No se pudo obtener información adicional de fuentes externas.</p>
                </div>
                
                <h6 class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Campos Completados:</h6>
                <ul class="list-unstyled mb-3">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Descripción básica generada</li>
                    <li class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i>No se encontró información adicional</li>
                </ul>
                
                <div class="alert alert-info border-0 mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    <small>Completa manualmente los campos restantes antes de guardar.</small>
                </div>`;
            
            // Modal de advertencia eliminado por solicitud del usuario
            console.log('⚠ Auto-completado parcial');
        }
    })
    .catch(error => {
        console.error('Error en auto-completado:', error);
        // Generar descripción básica como fallback
        const descField = document.getElementById('editDescription');
        if (!descField.value || descField.value.trim() === '') {
            const typeText = productType === 'console' ? 'consola' : 'accesorio';
            descField.value = `${productName} es una ${typeText}. Disponible en MultiGamer360.`;
        }
    })
    .finally(() => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic me-2"></i>Auto-Rellenar';
    });
}

// Auto-completar información
const autoCompleteBtn = document.getElementById('autoCompleteBtn');
if (autoCompleteBtn) {
    autoCompleteBtn.addEventListener('click', function() {
    const gameName = document.getElementById('editTitle').value;
    const productType = document.getElementById('editProductType').value;
    
    if (!gameName) {
        alert('Primero ingresa el nombre del producto');
        return;
    }
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Buscando...';
    
    // Si es consola o accesorio, usar auto-completado básico
    if (productType === 'console' || productType === 'accessory') {
        autoCompleteBasic(gameName, productType, btn);
        return;
    }
    
    // Si es juego, usar RAWG API
    fetch('ajax/autocomplete_game_info.php?game_name=' + encodeURIComponent(gameName))
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Mostrar mensaje de error
            let errorMsg = '❌ ' + data.message;
            
            if (data.instructions && data.instructions.length > 0) {
                errorMsg += '\n\n📋 Instrucciones:\n' + data.instructions.join('\n');
            }
            
            if (data.debug) {
                console.error('Debug API:', data.debug);
            }
            
            alert(errorMsg);
            return;
        }
        
        // Paso 1: Crear/buscar datos faltantes en la base de datos
        const missingData = {
            category: data.data.category || null,
            brand: data.data.brand || null,
            console: data.data.main_console || null,
            genres: data.data.genres || []
        };
        
        // Llamar al endpoint que crea/encuentra los datos
        fetch('ajax/create_missing_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(missingData)
        })
        .then(response => response.json())
        .then(createResult => {
            let fieldsCompleted = 0;
            let messages = [];
            
            // Mostrar lo que se creó
            if (createResult.created && createResult.created.length > 0) {
                messages.push('✨ Creado: ' + createResult.created.join(', '));
                
                // Si se crearon elementos, recargar los dropdowns
                reloadDropdowns();
            }
            if (createResult.found && createResult.found.length > 0) {
                messages.push('✓ Encontrado: ' + createResult.found.join(', '));
            }
            
            // Esperar un momento para que se recarguen los dropdowns
            setTimeout(() => {
                // Generar SKU automático solo si está vacío
                const skuField = document.getElementById('editSku');
                if (data.data.title && (!skuField.value || skuField.value === '')) {
                    // Extraer caracteres alfanuméricos
                    const clean = data.data.title.replace(/[^a-zA-Z0-9]/g, '');
                    
                    // Tomar hasta 6 caracteres
                    let prefix = clean.substring(0, 6).toUpperCase();
                    
                    // Rellenar si es muy corto
                    if (prefix.length < 3) {
                        prefix = prefix.padEnd(3, 'X');
                    }
                    
                    // Generar sufijo con timestamp + random
                    const timestamp = Math.floor(Date.now() / 1000).toString().slice(-5);
                    const random = Math.floor(Math.random() * 9000) + 1000;
                    
                    skuField.value = `${prefix}-${timestamp}${random}`;
                    fieldsCompleted++;
                }
                
                // Rellenar descripción solo si está vacía
                const descField = document.getElementById('editDescription');
                if (data.data.description && (!descField.value || descField.value.trim() === '')) {
                    descField.value = data.data.description;
                    fieldsCompleted++;
                }
                
                // Rellenar meta título solo si está vacío
                const metaTitleField = document.getElementById('editMetaTitle');
                if (data.data.title && (!metaTitleField.value || metaTitleField.value.trim() === '')) {
                    metaTitleField.value = data.data.title + ' - MultiGamer360';
                    fieldsCompleted++;
                }
                
                // Rellenar meta descripción solo si está vacía
                const metaDescField = document.getElementById('editMetaDescription');
                if (data.data.description && (!metaDescField.value || metaDescField.value.trim() === '')) {
                    // Crear una descripción corta de 150-160 caracteres para SEO
                    let metaDesc = data.data.description
                        .replace(/<[^>]*>/g, '') // Remover HTML
                        .replace(/\s+/g, ' ')     // Normalizar espacios
                        .trim();
                    
                    // Truncar a 155 caracteres (óptimo para Google)
                    if (metaDesc.length > 155) {
                        metaDesc = metaDesc.substring(0, 152) + '...';
                    }
                    
                    // Agregar nombre del sitio si hay espacio
                    if (metaDesc.length < 130) {
                        metaDesc += ' | MultiGamer360';
                    }
                    
                    metaDescField.value = metaDesc;
                    fieldsCompleted++;
                }
                
                // Seleccionar CATEGORÍA solo si no hay una seleccionada
                const categoryField = document.getElementById('editCategory');
                if (createResult.ids && createResult.ids.category_id && (!categoryField.value || categoryField.value === '')) {
                    categoryField.value = createResult.ids.category_id;
                    fieldsCompleted++;
                }
                
                // Seleccionar MARCA solo si no hay una seleccionada
                const brandField = document.getElementById('editBrand');
                if (createResult.ids && createResult.ids.brand_id && (!brandField.value || brandField.value === '')) {
                    document.getElementById('editBrand').value = createResult.ids.brand_id;
                    fieldsCompleted++;
                }
                
                // Seleccionar CONSOLA solo si no hay una seleccionada
                const consoleField = document.getElementById('editConsole');
                if (createResult.ids && createResult.ids.console_id && (!consoleField.value || consoleField.value === '')) {
                    consoleField.value = createResult.ids.console_id;
                    fieldsCompleted++;
                }
                
                // Seleccionar GÉNEROS solo si no hay ninguno seleccionado
                if (createResult.ids && createResult.ids.genre_ids && createResult.ids.genre_ids.length > 0) {
                    const genreContainer = document.getElementById('editGenres');
                    const checkboxes = genreContainer.querySelectorAll('input[type="checkbox"]');
                    
                    // Verificar si ya hay géneros seleccionados
                    const hasSelectedGenres = Array.from(checkboxes).some(cb => cb.checked);
                    
                    if (!hasSelectedGenres) {
                        // Solo marcar géneros si no hay ninguno seleccionado
                        checkboxes.forEach(checkbox => {
                            if (createResult.ids.genre_ids.includes(parseInt(checkbox.value))) {
                                checkbox.checked = true;
                            }
                        });
                        fieldsCompleted++;
                    }
                }
                
                // Descargar imágenes si hay
                if (data.data.images && data.data.images.length > 0) {
                    fetch('ajax/download_game_images.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ image_urls: data.data.images })
                    })
                    .then(response => response.json())
                    .then(imageResult => {
                        if (imageResult.success && imageResult.images.length > 0) {
                            // Mostrar vista previa de imágenes descargadas
                            const preview = document.getElementById('imagePreview');
                            if (!preview) return;
                            
                            preview.innerHTML = '<div class="alert alert-success mb-2"><i class="fas fa-check-circle me-2"></i>' + 
                                imageResult.images.length + ' imágenes descargadas desde RAWG</div>';
                            
                            imageResult.images.forEach((img, idx) => {
                                const imgDiv = document.createElement('div');
                                imgDiv.className = 'd-inline-block position-relative me-2 mb-2';
                                imgDiv.dataset.imagePath = img.path;
                                imgDiv.style.width = '150px';
                                imgDiv.style.height = '150px';
                                
                                const imgElement = document.createElement('img');
                                imgElement.src = '../' + img.path;
                                imgElement.className = 'img-thumbnail';
                                imgElement.style.width = '100%';
                                imgElement.style.height = '100%';
                                imgElement.style.objectFit = 'cover';
                                imgElement.style.cursor = 'pointer';
                                imgElement.title = 'Click para ver en grande';
                                imgElement.onclick = function() {
                                    openLightbox('../' + img.path, 'Imagen RAWG #' + (idx + 1));
                                };
                                
                                const deleteBtn = document.createElement('button');
                                deleteBtn.type = 'button';
                                deleteBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
                                deleteBtn.style.margin = '5px';
                                deleteBtn.style.padding = '2px 6px';
                                deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                                deleteBtn.title = 'Eliminar imagen';
                                deleteBtn.dataset.imagePath = img.path;
                                
                                imgDiv.appendChild(imgElement);
                                imgDiv.appendChild(deleteBtn);
                                preview.appendChild(imgDiv);
                                
                                // Agregar event listener inmediatamente después de agregar al DOM
                                deleteBtn.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        const imagePath = this.dataset.imagePath;
                                        const parentDiv = this.closest('.d-inline-block');
                                        
                                        // Remover del DOM
                                        if (parentDiv) {
                                            parentDiv.remove();
                                        }
                                        
                                        // Remover del array de imágenes descargadas
                                        if (window.downloadedGameImages) {
                                            window.downloadedGameImages = window.downloadedGameImages.filter(img => img !== imagePath);
                                        }
                                        
                                        // Actualizar mensaje de contador
                                        const previewContainer = document.getElementById('imagePreview');
                                        if (previewContainer && window.downloadedGameImages) {
                                            const alert = previewContainer.querySelector('.alert-success');
                                            if (alert) {
                                                const count = window.downloadedGameImages.length;
                                                if (count === 0) {
                                                    alert.remove();
                                                } else {
                                                    alert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + 
                                                        count + ' imágenes descargadas desde RAWG';
                                                }
                                            }
                                        }
                                    });
                                });
                            
                            // Guardar las rutas de las imágenes para usar al guardar el producto
                            window.downloadedGameImages = imageResult.images.map(img => img.path);
                            
                            fieldsCompleted++;
                            messages.push('📸 Imágenes: ' + imageResult.images.length + ' descargadas');
                        } else if (imageResult.errors && imageResult.errors.length > 0) {
                            console.warn('Errores al descargar imágenes:', imageResult.errors);
                        }
                    })
                    .catch(error => {
                        console.error('Error al descargar imágenes:', error);
                    });
                }
                
                // Mensaje final mejorado con modal Bootstrap
                setTimeout(() => {
                    let messageHTML = '<div class="text-center">';
                    messageHTML += '<i class="fas fa-check-circle text-success" style="font-size: 48px; margin-bottom: 15px;"></i>';
                    messageHTML += '<h5 class="mb-3">¡Información completada exitosamente!</h5>';
                    messageHTML += '<div class="alert alert-info text-start">';
                    messageHTML += '<strong>📊 Campos rellenados: ' + fieldsCompleted + '</strong><br><br>';
                    if (messages.length > 0) {
                        messageHTML += messages.join('<br>');
                    }
                    messageHTML += '</div>';
                    messageHTML += '<p class="text-muted"><small>✨ Descripción generada en español sin límites</small></p>';
                    messageHTML += '</div>';
                    
                    // Modal eliminado por solicitud del usuario
                    // Solo mostrar en consola
                    console.log('✓ Descripción generada exitosamente');
                    
                    /* Código de modal comentado
                    let modalElement = document.getElementById('autoCompleteSuccessModal');
                    if (!modalElement) {
                        modalElement = document.createElement('div');
                        modalElement.className = 'modal fade';
                        modalElement.id = 'autoCompleteSuccessModal';
                        modalElement.innerHTML = `
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" id="autoCompleteSuccessBody"></div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                                            <i class="fas fa-check me-2"></i>Continuar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modalElement);
                    }
                    
                    document.getElementById('autoCompleteSuccessBody').innerHTML = messageHTML;
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    */
                }, 200);
            }, 200);
        })
        .catch(error => {
            alert('Error al crear datos: ' + error.message);
        });
    })
    .catch(error => {
        alert('Error al buscar información: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic me-2"></i>Auto-Rellenar Información del Juego';
    });
    });
}

// Guardar producto
const saveProductBtn = document.getElementById('saveProductBtn');
if (saveProductBtn) {
    saveProductBtn.addEventListener('click', function() {
        saveCurrentProduct();
    });
}

const finishImportBtn = document.getElementById('finishImportBtn');
if (finishImportBtn) {
    finishImportBtn.addEventListener('click', function() {
        saveCurrentProduct(true);
    });
}

function saveCurrentProduct(isLast = false) {
    const form = document.getElementById('productReviewForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const saveBtn = isLast ? document.getElementById('finishImportBtn') : document.getElementById('saveProductBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    
    // Primero subir imágenes si hay
    const imageFiles = document.getElementById('editImages').files;
    
    if (imageFiles.length > 0) {
        const imageFormData = new FormData();
        for (let i = 0; i < imageFiles.length; i++) {
            imageFormData.append('images[]', imageFiles[i]);
        }
        
        fetch('ajax/upload_product_images.php', {
            method: 'POST',
            body: imageFormData
        })
        .then(response => response.json())
        .then(imageData => {
            if (imageData.success) {
                saveProductWithImages(imageData.images, isLast, saveBtn);
            } else {
                alert('Error al subir imágenes: ' + imageData.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>' + (isLast ? 'Finalizar' : 'Guardar y Siguiente');
            }
        })
        .catch(error => {
            alert('Error al subir imágenes: ' + error.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>' + (isLast ? 'Finalizar' : 'Guardar y Siguiente');
        });
    } else {
        saveProductWithImages([], isLast, saveBtn);
    }
}

function saveProductWithImages(images, isLast, saveBtn) {
    // Combinar imágenes subidas manualmente + imágenes descargadas de RAWG
    let allImages = [];
    
    // Agregar imágenes subidas manualmente (vienen con estructura completa)
    if (images && images.length > 0) {
        allImages = [...images];
    }
    
    // Agregar imágenes descargadas de RAWG si existen
    if (window.downloadedGameImages && window.downloadedGameImages.length > 0) {
        console.log('Imágenes descargadas de RAWG:', window.downloadedGameImages);
        // Las imágenes de RAWG vienen como paths simples, convertirlas al formato esperado
        window.downloadedGameImages.forEach(imagePath => {
            allImages.push(imagePath); // Enviar directamente el path
        });
    }
    
    console.log('Total imágenes a guardar:', allImages);
    
    // Recopilar datos del formulario
    const categorySelect = document.getElementById('editCategory');
    const brandSelect = document.getElementById('editBrand');
    const consoleSelect = document.getElementById('editConsole');
    
    const productData = {
        title: document.getElementById('editTitle').value,
        product_type: document.getElementById('editProductType').value,
        sku: document.getElementById('editSku').value,
        status: document.getElementById('editStatus').value,
        price_cop: document.getElementById('editPriceCop').value,
        price_usd: document.getElementById('editPriceUsd').value,
        stock: document.getElementById('editStock').value,
        description: document.getElementById('editDescription').value,
        category_id: categorySelect.value,
        brand_id: brandSelect.value,
        console_id: consoleSelect.value,
        genres: Array.from(document.querySelectorAll('input[name="genres[]"]:checked')).map(cb => cb.value),
        is_featured: document.getElementById('editFeatured').checked ? 1 : 0,
        is_new: document.getElementById('editNew').checked ? 1 : 0,
        on_sale: document.getElementById('editOnSale').checked ? 1 : 0,
        is_active: document.getElementById('editStatus').value,
        meta_title: document.getElementById('editMetaTitle').value,
        meta_description: document.getElementById('editMetaDescription').value,
        images: allImages
    };
    
    // Si no hay ID seleccionado pero hay texto, significa que es nuevo y debe auto-crearse
    // Enviar los nombres para auto-creación cuando corresponda
    if (!productData.category_id && categorySelect.selectedOptions[0]?.text) {
        productData.category_name = categorySelect.selectedOptions[0].text;
    }
    if (!productData.brand_id && brandSelect.selectedOptions[0]?.text) {
        productData.brand_name = brandSelect.selectedOptions[0].text;
    }
    if (!productData.console_id && consoleSelect.selectedOptions[0]?.text) {
        productData.console_name = consoleSelect.selectedOptions[0].text;
    }
    
    // Agregar nombres de géneros marcados para auto-creación
    const genreNames = Array.from(document.querySelectorAll('input[name="genres[]"]:checked'))
        .map(cb => cb.nextElementSibling?.textContent || '')
        .filter(name => name);
    if (genreNames.length > 0) {
        productData.genre_names = genreNames;
    }
    
    // Si tiene datos del CSV original, preservar los nombres
    if (productsToReview[currentProductIndex]) {
        const csvProduct = productsToReview[currentProductIndex];
        if (csvProduct.category_name && !productData.category_id) {
            productData.category_name = csvProduct.category_name;
        }
        if (csvProduct.brand_name && !productData.brand_id) {
            productData.brand_name = csvProduct.brand_name;
        }
        if (csvProduct.console_name && !productData.console_id) {
            productData.console_name = csvProduct.console_name;
        }
        if (csvProduct.genre_names && csvProduct.genre_names.length > 0) {
            productData.genre_names = [...(productData.genre_names || []), ...csvProduct.genre_names];
        }
    }
    
    // Guardar en servidor
    fetch('ajax/save_reviewed_product.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(productData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            savedProducts.push(data.product_id);
            
            if (isLast || currentProductIndex >= productsToReview.length - 1) {
                // Finalizar
                alert(`¡Importación completada! ${savedProducts.length} productos guardados.`);
                window.location.reload();
            } else {
                // Siguiente producto
                const loadSuccess = loadProductReview(currentProductIndex + 1);
                if (!loadSuccess) {
                    alert('Error al cargar siguiente producto. Finalizando importación.');
                    window.location.reload();
                }
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar y Siguiente';
            }
        } else {
            alert('Error al guardar: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>' + (isLast ? 'Finalizar' : 'Guardar y Siguiente');
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>' + (isLast ? 'Finalizar' : 'Guardar y Siguiente');
    });
}

// Navegación
const previousProductBtn = document.getElementById('previousProductBtn');
if (previousProductBtn) {
    previousProductBtn.addEventListener('click', function() {
        if (currentProductIndex > 0) {
            const loadSuccess = loadProductReview(currentProductIndex - 1);
            if (!loadSuccess) {
                alert('Error al cargar el producto anterior');
            }
        }
    });
}

const skipProductBtn = document.getElementById('skipProductBtn');
if (skipProductBtn) {
    skipProductBtn.addEventListener('click', function() {
        // Mostrar modal de confirmación
        const skipModal = new bootstrap.Modal(document.getElementById('skipProductConfirmModal'));
        skipModal.show();
    });
}

// Confirmar saltar producto
const confirmSkipProductBtn = document.getElementById('confirmSkipProductBtn');
if (confirmSkipProductBtn) {
    confirmSkipProductBtn.addEventListener('click', function() {
        // Cerrar modal de confirmación
    const skipModal = bootstrap.Modal.getInstance(document.getElementById('skipProductConfirmModal'));
    if (skipModal) {
        skipModal.hide();
    }
    
    if (currentProductIndex < productsToReview.length - 1) {
        // Ir al siguiente producto
        const loadSuccess = loadProductReview(currentProductIndex + 1);
        if (!loadSuccess) {
            alert('Error al cargar el siguiente producto');
        }
    } else {
        // Era el último producto, cerrar y recargar
        alert('Has completado la revisión de todos los productos.');
        window.location.reload();
    }
    });
}

const closeReviewModal = document.getElementById('closeReviewModal');
if (closeReviewModal) {
    closeReviewModal.addEventListener('click', function() {
        // Mostrar modal de confirmación
        const closeModal = new bootstrap.Modal(document.getElementById('closeWithoutSavingModal'));
        closeModal.show();
    });
}

// Confirmar cerrar sin guardar
const confirmCloseWithoutSavingBtn = document.getElementById('confirmCloseWithoutSavingBtn');
if (confirmCloseWithoutSavingBtn) {
    confirmCloseWithoutSavingBtn.addEventListener('click', function() {
        window.location.reload();
    });
}

// ========== DRAG & DROP Y PREVIEW DE IMÁGENES ==========
const imageUploadArea = document.getElementById('imageUploadArea');
const editImagesInput = document.getElementById('editImages');

if (imageUploadArea && editImagesInput) {
    // Click en el área para abrir selector
    imageUploadArea.addEventListener('click', function() {
        editImagesInput.click();
    });
    
    // Estilos hover
    imageUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#0d6efd';
        this.style.backgroundColor = '#e7f1ff';
    });
    
    imageUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#dee2e6';
        this.style.backgroundColor = 'transparent';
    });
    
    // Drag & drop
    imageUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#dee2e6';
        this.style.backgroundColor = 'transparent';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            editImagesInput.files = files;
            editImagesInput.dispatchEvent(new Event('change'));
        }
    });
}

// Función para abrir lightbox
function openLightbox(imageSrc, caption) {
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxModal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
    
    if (lightboxImage && lightboxCaption) {
        lightboxImage.src = imageSrc;
        lightboxCaption.textContent = caption || '';
        lightboxModal.show();
    }
}

// Preview de imágenes subidas manualmente
if (editImagesInput) {
    editImagesInput.addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        if (!preview) return;
        
        // Limpiar preview anterior de imágenes subidas (pero mantener las de RAWG)
        const uploadedImages = preview.querySelectorAll('[data-uploaded="true"]');
        uploadedImages.forEach(img => img.remove());
        
        // Crear array temporal para almacenar archivos
        if (!window.uploadedImageFiles) {
            window.uploadedImageFiles = [];
        }
        
        Array.from(e.target.files).forEach((file, index) => {
            window.uploadedImageFiles.push(file);
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'position-relative d-inline-block me-2 mb-2';
                imgContainer.style.width = '150px';
                imgContainer.style.height = '150px';
                imgContainer.dataset.uploaded = 'true';
                imgContainer.dataset.fileIndex = window.uploadedImageFiles.length - 1;
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-thumbnail';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.cursor = 'pointer';
                img.title = 'Click para ver en grande';
                
                // Click para ver en grande
                img.onclick = function() {
                    openLightbox(e.target.result, 'Imagen subida: ' + file.name);
                };
                
                const badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-0 badge bg-info';
                badge.textContent = 'Nuevo';
                badge.style.margin = '5px';
                
                // Botón de eliminar
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
                deleteBtn.style.margin = '5px';
                deleteBtn.style.padding = '2px 6px';
                deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                deleteBtn.title = 'Eliminar imagen';
                deleteBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const fileIndex = parseInt(imgContainer.dataset.fileIndex);
                    
                    // Remover del array de archivos
                    window.uploadedImageFiles.splice(fileIndex, 1);
                    
                    // Remover del DOM
                    imgContainer.remove();
                    
                    // Actualizar el input file con los archivos restantes
                    const dataTransfer = new DataTransfer();
                    window.uploadedImageFiles.forEach(f => dataTransfer.items.add(f));
                    editImagesInput.files = dataTransfer.files;
                };
                
                imgContainer.appendChild(img);
                imgContainer.appendChild(badge);
                imgContainer.appendChild(deleteBtn);
                preview.appendChild(imgContainer);
            };
            reader.readAsDataURL(file);
        });
    });
}

// ========== FUNCIONALIDAD DE MODALES AGREGAR ==========
// Función para inicializar los event listeners de los botones de agregar
// Se llama cuando se muestra el modal de revisión para asegurar que los elementos existan
let addButtonsInitialized = false;

function initializeAddButtons() {
    if (addButtonsInitialized) return;
    addButtonsInitialized = true;

const addCategoryBtn = document.getElementById('addCategoryBtn');
if (addCategoryBtn) {
    addCategoryBtn.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        const categoryNameInput = document.getElementById('newCategoryName');
        const categoryResultDiv = document.getElementById('addCategoryResult');
        
        if (categoryNameInput) categoryNameInput.value = '';
        if (categoryResultDiv) categoryResultDiv.innerHTML = '';
        
        modal.show();
    });
}

const saveCategoryBtn = document.getElementById('saveCategoryBtn');
if (saveCategoryBtn) {
    saveCategoryBtn.addEventListener('click', function() {
        const nameInput = document.getElementById('newCategoryName');
        const resultDiv = document.getElementById('addCategoryResult');
        const btn = this;
        
        if (!nameInput || !resultDiv) {
            console.error('Elementos del modal de categoría no encontrados');
            return;
        }
        
        const name = nameInput.value.trim();
        
        if (!name) {
            safeSetHTML('addCategoryResult', '<div class="alert alert-warning">El nombre es requerido</div>');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        fetch('ajax/add_category.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'name=' + encodeURIComponent(name)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                safeSetHTML('addCategoryResult', '<div class="alert alert-success">' + data.message + '</div>');
                
                if (dropdownData.categories) {
                    dropdownData.categories.push({id: data.id, name: data.name});
                }
                
                const select = document.getElementById('editCategory');
                if (select) {
                    const option = document.createElement('option');
                    option.value = data.id;
                    option.textContent = data.name;
                    option.selected = true;
                    select.appendChild(option);
                }
                
                setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'))?.hide(), 1000);
            } else {
                safeSetHTML('addCategoryResult', '<div class="alert alert-danger">' + data.message + '</div>');
            }
        })
        .catch(error => {
            safeSetHTML('addCategoryResult', '<div class="alert alert-danger">Error: ' + error.message + '</div>');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
            }
        });
    });
}

const addBrandBtn = document.getElementById('addBrandBtn');
if (addBrandBtn) {
    addBrandBtn.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('addBrandModal'));
        const brandNameInput = document.getElementById('newBrandName');
        const brandResultDiv = document.getElementById('addBrandResult');
        
        if (brandNameInput) brandNameInput.value = '';
        if (brandResultDiv) brandResultDiv.innerHTML = '';
        
        modal.show();
    });
}

const saveBrandBtn = document.getElementById('saveBrandBtn');
if (saveBrandBtn) {
    saveBrandBtn.addEventListener('click', function() {
        const nameInput = document.getElementById('newBrandName');
        const resultDiv = document.getElementById('addBrandResult');
        const btn = this;
        
        if (!nameInput || !resultDiv) {
            console.error('Elementos del modal de marca no encontrados');
            return;
        }
        
        const name = nameInput.value.trim();
        
        if (!name) {
            safeSetHTML('addBrandResult', '<div class="alert alert-warning">El nombre es requerido</div>');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        fetch('ajax/add_brand.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'name=' + encodeURIComponent(name)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                safeSetHTML('addBrandResult', '<div class="alert alert-success">' + data.message + '</div>');
                
                if (dropdownData.brands) {
                    dropdownData.brands.push({id: data.id, name: data.name});
                }
                
                const select = document.getElementById('editBrand');
                if (select) {
                    const option = document.createElement('option');
                    option.value = data.id;
                    option.textContent = data.name;
                    option.selected = true;
                    select.appendChild(option);
                }
                
                setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('addBrandModal'))?.hide(), 1000);
            } else {
                safeSetHTML('addBrandResult', '<div class="alert alert-danger">' + data.message + '</div>');
            }
        })
        .catch(error => {
            safeSetHTML('addBrandResult', '<div class="alert alert-danger">Error: ' + error.message + '</div>');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
            }
        });
    });
}

const addConsoleBtn = document.getElementById('addConsoleBtn');
if (addConsoleBtn) {
    addConsoleBtn.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('addConsoleModal'));
        const consoleNameInput = document.getElementById('newConsoleName');
        const consoleResultDiv = document.getElementById('addConsoleResult');
        
        if (consoleNameInput) consoleNameInput.value = '';
        if (consoleResultDiv) consoleResultDiv.innerHTML = '';
        
        modal.show();
    });
}

const saveConsoleBtn = document.getElementById('saveConsoleBtn');
if (saveConsoleBtn) {
    saveConsoleBtn.addEventListener('click', function() {
        const nameInput = document.getElementById('newConsoleName');
        const resultDiv = document.getElementById('addConsoleResult');
        const btn = this;
        
        if (!nameInput || !resultDiv) {
            console.error('Elementos del modal no encontrados');
            return;
        }
        
        const name = nameInput.value.trim();
        
        if (!name) {
            safeSetHTML('addConsoleResult', '<div class="alert alert-warning">El nombre es requerido</div>');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        fetch('ajax/add_console.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'name=' + encodeURIComponent(name)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                safeSetHTML('addConsoleResult', '<div class="alert alert-success">' + data.message + '</div>');
                
                if (dropdownData.consoles) {
                    dropdownData.consoles.push({id: data.id, name: data.name});
                }
                
                const select = document.getElementById('editConsole');
                if (select) {
                    const option = document.createElement('option');
                    option.value = data.id;
                    option.textContent = data.name;
                    option.selected = true;
                    select.appendChild(option);
                }
                
                setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('addConsoleModal'))?.hide(), 1000);
            } else {
                safeSetHTML('addConsoleResult', '<div class="alert alert-danger">' + data.message + '</div>');
            }
        })
        .catch(error => {
            safeSetHTML('addConsoleResult', '<div class="alert alert-danger">Error: ' + error.message + '</div>');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
            }
        });
    });
}

const addGenreBtn = document.getElementById('addGenreBtn');
if (addGenreBtn) {
    addGenreBtn.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('addGenreModal'));
        const genreNameInput = document.getElementById('newGenreName');
        const genreResultDiv = document.getElementById('addGenreResult');
        
        if (genreNameInput) genreNameInput.value = '';
        if (genreResultDiv) genreResultDiv.innerHTML = '';
        
        modal.show();
    });
}

const saveGenreBtn = document.getElementById('saveGenreBtn');
if (saveGenreBtn) {
    saveGenreBtn.addEventListener('click', function() {
        const nameInput = document.getElementById('newGenreName');
        const resultDiv = document.getElementById('addGenreResult');
        const btn = this;
        
        if (!nameInput || !resultDiv) {
            console.error('Elementos del modal de género no encontrados');
            return;
        }
        
        const name = nameInput.value.trim();
        
        if (!name) {
            safeSetHTML('addGenreResult', '<div class="alert alert-warning">El nombre es requerido</div>');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        fetch('ajax/add_genre.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'name=' + encodeURIComponent(name)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                safeSetHTML('addGenreResult', '<div class="alert alert-success">' + data.message + '</div>');
                
                if (dropdownData.genres) {
                    dropdownData.genres.push({id: data.id, name: data.name});
                }
                
                populateGenreCheckboxes('editGenres', dropdownData.genres, [data.id]);
                
                setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('addGenreModal'))?.hide(), 1000);
            } else {
                safeSetHTML('addGenreResult', '<div class="alert alert-danger">' + data.message + '</div>');
            }
        })
        .catch(error => {
            safeSetHTML('addGenreResult', '<div class="alert alert-danger">Error: ' + error.message + '</div>');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
            }
        });
    });
}

} // Fin de initializeAddButtons()

// Llamar a la función cuando se muestre el modal de revisión
const reviewModalElement = document.getElementById('reviewProductModal');
if (reviewModalElement) {
    reviewModalElement.addEventListener('shown.bs.modal', function() {
        initializeAddButtons();
    });
}


</script>
<!-- Version: 2.1.1 - Eliminado modal de Auto-Completado Exitoso -->

<?php require_once 'inc/footer.php'; ?>
