<?php
$page_title = 'Gestión de Inventario';
$page_actions = '<button class="btn btn-primary" onclick="showAdjustmentModal()"><i class="fas fa-plus"></i> Ajuste de Stock</button>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('inventory', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver inventario';
    header('Location: index.php');
    exit;
}

// Parámetros de filtrado
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query con filtros
$where_conditions = ['p.is_active = 1'];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
}

if ($brand) {
    $where_conditions[] = "p.brand_id = ?";
    $params[] = $brand;
}

// Usar 10 como nivel mínimo de stock (min_stock_level no existe en la BD)
$min_stock_threshold = 10;

if ($stock_status === 'low') {
    $where_conditions[] = "p.stock_quantity <= $min_stock_threshold";
} elseif ($stock_status === 'out') {
    $where_conditions[] = "p.stock_quantity = 0";
} elseif ($stock_status === 'good') {
    $where_conditions[] = "p.stock_quantity > $min_stock_threshold";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Obtener total de productos
    $count_query = "
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE $where_clause
    ";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    
    // Obtener productos con información de inventario
    $query = "
        SELECT p.*, 
               c.name as category_name,
               b.name as brand_name,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'Agotado'
                   WHEN p.stock_quantity <= 10 THEN 'Stock Bajo'
                   ELSE 'Stock Normal'
               END as stock_status,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'danger'
                   WHEN p.stock_quantity <= 10 THEN 'warning'
                   ELSE 'success'
               END as stock_color,
               10 as min_stock_level
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE $where_clause
        ORDER BY 
            CASE 
                WHEN p.stock_quantity = 0 THEN 1
                WHEN p.stock_quantity <= 10 THEN 2
                ELSE 3
            END,
            p.name ASC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    $total_pages = ceil($total_products / $per_page);
    
    // Estadísticas de inventario (usando 10 como nivel mínimo de stock)
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_products,
            SUM(stock_quantity) as total_stock,
            COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 END) as low_stock,
            COUNT(CASE WHEN stock_quantity > 10 THEN 1 END) as good_stock,
            SUM(stock_quantity * COALESCE(price_pesos, 0)) as inventory_value
        FROM products 
        WHERE is_active = 1
    ")->fetch();
    
    // Obtener categorías para filtros
    $categories = $pdo->query("
        SELECT id, name FROM categories 
        WHERE is_active = 1 AND parent_id IS NULL
        ORDER BY name
    ")->fetchAll();
    
    // Obtener marcas para filtros
    $brands = $pdo->query("
        SELECT id, name FROM brands 
        WHERE is_active = 1
        ORDER BY name
    ")->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar inventario: ' . $e->getMessage();
    $products = [];
    $total_products = 0;
    $total_pages = 1;
    $stats = ['total_products' => 0, 'total_stock' => 0, 'out_of_stock' => 0, 'low_stock' => 0, 'good_stock' => 0, 'inventory_value' => 0];
    $categories = [];
    $brands = [];
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
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Stock Normal</h6>
                        <h4 class="mb-0 text-success"><?php echo number_format($stats['good_stock']); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
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
        <div class="card border-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted mb-2">Valor Inventario</h6>
                        <h4 class="mb-0"><?php echo '$' . number_format($stats['inventory_value'], 2); ?></h4>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x text-dark"></i>
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
                       placeholder="Nombre o SKU...">
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
                    <?php foreach ($brands as $br): ?>
                        <option value="<?php echo $br['id']; ?>" 
                                <?php echo $brand == $br['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($br['name']); ?>
                        </option>
                    <?php endforeach; ?>
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
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="inventory.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="button" class="btn btn-success" onclick="exportInventory()">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de inventario -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-warehouse me-2"></i>
            Inventario (<?php echo number_format($total_products); ?> productos)
        </span>
        
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showBulkUpdateModal()">
                <i class="fas fa-edit"></i> Actualización Masiva
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="showLowStockAlert()">
                <i class="fas fa-exclamation-triangle"></i> Alertas
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron productos</h5>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="80">Imagen</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Estado</th>
                            <th>Precio</th>
                            <th>Valor</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
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
                                    <?php if (strlen($product['description']) > 60): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($product['sku']); ?></code>
                            </td>
                            <td>
                                <?php if ($product['category_name']): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin categoría</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['brand_name']): ?>
                                    <?php echo htmlspecialchars($product['brand_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin marca</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fs-6 fw-bold"><?php echo number_format($product['stock_quantity']); ?></span>
                            </td>
                            <td>
                                <span class="text-muted"><?php echo number_format($product['min_stock_level']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $product['stock_color']; ?>">
                                    <?php echo $product['stock_status']; ?>
                                </span>
                            </td>
                            <td>
                                $<?php echo number_format($product['price'], 2); ?>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($product['stock_quantity'] * $product['price'], 2); ?></strong>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="showAdjustStockModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['stock_quantity']; ?>)"
                                            data-bs-toggle="tooltip" title="Ajustar stock">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <a href="product_edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary"
                                       data-bs-toggle="tooltip" title="Editar producto">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="showStockHistory(<?php echo $product['id']; ?>)"
                                            data-bs-toggle="tooltip" title="Historial">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de inventario" class="mt-4">
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

<!-- Modal Ajustar Stock -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar Stock</h5>
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
                <button type="submit" form="adjustStockForm" class="btn btn-primary">Aplicar Ajuste</button>
            </div>
        </div>
    </div>
</div>

<script>
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
document.getElementById('adjustment_type').addEventListener('change', calculateResultingStock);
document.getElementById('adjustment_quantity').addEventListener('input', calculateResultingStock);

// Mostrar modal de ajuste de stock
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

// Enviar formulario de ajuste
document.getElementById('adjustStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Utils.showToast('Stock ajustado correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('adjustStockModal')).hide();
            location.reload();
        } else {
            Utils.showToast(data.message || 'Error al ajustar stock', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Utils.showToast('Error de conexión', 'danger');
    });
});

// Exportar inventario
function exportInventory() {
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'csv');
    window.open('api/inventory.php?' + params.toString(), '_blank');
}

// Mostrar historial de stock
function showStockHistory(productId) {
    // TODO: Implementar modal de historial
    Utils.showToast('Función de historial próximamente', 'info');
}
</script>

<?php require_once 'inc/footer.php'; ?>