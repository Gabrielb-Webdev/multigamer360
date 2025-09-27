<?php
/**
 * Panel de administración para gestión de categorías de filtros dinámicos
 * Solo accesible para administradores
 */

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/product_manager.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$productManager = new ProductManager($pdo);

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_category':
            $name = strtolower(trim($_POST['name']));
            $display_name = trim($_POST['display_name']);
            $description = trim($_POST['description']);
            $icon = trim($_POST['icon']);
            $filter_order = (int)$_POST['filter_order'];
            
            if ($name && $display_name) {
                $sql = "INSERT INTO tag_categories (name, display_name, description, icon, filter_order) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $display_name, $description, $icon, $filter_order]);
                $success_message = "Categoría '$display_name' creada correctamente.";
            }
            break;
            
        case 'update_category':
            $id = (int)$_POST['category_id'];
            $display_name = trim($_POST['display_name']);
            $description = trim($_POST['description']);
            $icon = trim($_POST['icon']);
            $filter_order = (int)$_POST['filter_order'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id && $display_name) {
                $sql = "UPDATE tag_categories SET display_name = ?, description = ?, icon = ?, filter_order = ?, is_active = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$display_name, $description, $icon, $filter_order, $is_active, $id]);
                $success_message = "Categoría actualizada correctamente.";
            }
            break;
            
        case 'move_tag':
            $tag = trim($_POST['tag']);
            $new_category_id = (int)$_POST['new_category_id'];
            $display_name = trim($_POST['display_name']);
            
            if ($tag && $new_category_id) {
                $sql = "UPDATE product_tags SET category_id = ?, display_name = ? WHERE tag = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$new_category_id, $display_name, $tag]);
                $success_message = "Etiqueta '$tag' movida correctamente.";
            }
            break;
            
        case 'update_tag':
            $tag_id = (int)$_POST['tag_id'];
            $display_name = trim($_POST['display_name']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($tag_id && $display_name) {
                $sql = "UPDATE product_tags SET display_name = ?, is_active = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$display_name, $is_active, $tag_id]);
                $success_message = "Etiqueta actualizada correctamente.";
            }
            break;
    }
}

// Obtener datos para mostrar
$categories = $productManager->getFilterCategories();
$organized_filters = $productManager->getOrganizedFilters();

include '../includes/header.php';
?>

<div class="container my-5">
    <div class="admin-panel">
        <div class="row">
            <div class="col-12">
                <div class="admin-header mb-4">
                    <h1 class="admin-title">
                        <i class="fas fa-filter text-danger"></i>
                        Gestión de Filtros Dinámicos
                    </h1>
                    <p class="admin-subtitle">
                        Administrar categorías de filtros y organización de etiquetas
                    </p>
                    <div class="admin-actions">
                        <a href="manage_tags_prices.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-tags"></i> Gestión de Etiquetas
                        </a>
                        <button class="btn btn-primary" onclick="openCategoryModal()">
                            <i class="fas fa-plus"></i> Nueva Categoría
                        </button>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Estadísticas generales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo count($categories); ?></div>
                                <div class="stat-label">Categorías</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-tags"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php 
                                    $total_tags = 0;
                                    foreach ($organized_filters as $filter) {
                                        $total_tags += count($filter['tags']);
                                    }
                                    echo $total_tags;
                                ?></div>
                                <div class="stat-label">Etiquetas Activas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-eye"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo count($organized_filters); ?></div>
                                <div class="stat-label">Filtros Visibles</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-box"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php 
                                    $total_products = 0;
                                    foreach ($organized_filters as $filter) {
                                        foreach ($filter['tags'] as $tag) {
                                            $total_products += $tag['product_count'];
                                        }
                                    }
                                    echo $total_products;
                                ?></div>
                                <div class="stat-label">Productos Filtrados</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestión de Categorías -->
                <div class="categories-management mb-5">
                    <h3>Categorías de Filtros</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Nombre</th>
                                    <th>Icono</th>
                                    <th>Etiquetas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTable">
                                <?php foreach ($categories as $category): ?>
                                <tr data-category-id="<?php echo $category['id']; ?>">
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $category['filter_order']; ?></span>
                                    </td>
                                    <td>
                                        <div class="category-info">
                                            <strong><?php echo htmlspecialchars($category['display_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($category['name']); ?></small>
                                            <?php if ($category['description']): ?>
                                                <br><small class="text-info"><?php echo htmlspecialchars($category['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="<?php echo $category['icon']; ?> text-danger"></i>
                                    </td>
                                    <td>
                                        <?php 
                                        $category_tags = array_filter($organized_filters, function($f) use ($category) {
                                            return $f['category']['id'] == $category['id'];
                                        });
                                        $tag_count = $category_tags ? count(reset($category_tags)['tags']) : 0;
                                        ?>
                                        <span class="badge bg-info"><?php echo $tag_count; ?> etiquetas</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $category['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewCategoryTags(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['display_name']); ?>')">
                                            <i class="fas fa-tags"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vista de Filtros Actuales -->
                <div class="current-filters">
                    <h3>Vista Previa de Filtros (Como se ven en la tienda)</h3>
                    <div class="filters-preview">
                        <?php foreach ($organized_filters as $filterGroup): ?>
                        <div class="filter-preview-section">
                            <h5 class="filter-preview-title">
                                <i class="<?php echo $filterGroup['category']['icon']; ?>"></i>
                                <?php echo htmlspecialchars($filterGroup['category']['display_name']); ?>
                            </h5>
                            <div class="filter-preview-tags">
                                <?php foreach ($filterGroup['tags'] as $tag): ?>
                                <span class="filter-preview-tag">
                                    <?php echo htmlspecialchars($tag['display_name']); ?>
                                    <small>(<?php echo $tag['product_count']; ?>)</small>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar categoría -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="categoryModalTitle">Nueva Categoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category" id="categoryAction">
                    <input type="hidden" name="category_id" id="categoryId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_name" class="form-label text-white">Nombre interno:</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" 
                                       id="category_name" name="name" placeholder="ej: consolas" required>
                                <small class="text-muted">Solo minúsculas, sin espacios</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_display_name" class="form-label text-white">Nombre mostrado:</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" 
                                       id="category_display_name" name="display_name" placeholder="ej: Consolas" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_description" class="form-label text-white">Descripción:</label>
                        <textarea class="form-control bg-dark text-white border-secondary" 
                                  id="category_description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="category_icon" class="form-label text-white">Icono (Font Awesome):</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" 
                                       id="category_icon" name="icon" placeholder="ej: fas fa-gamepad">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_order" class="form-label text-white">Orden:</label>
                                <input type="number" class="form-control bg-dark text-white border-secondary" 
                                       id="category_order" name="filter_order" value="1" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="activeCheckboxDiv" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="category_active" name="is_active" checked>
                            <label class="form-check-label text-white" for="category_active">
                                Categoría activa
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="categorySubmitBtn">Crear Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver etiquetas de categoría -->
<div class="modal fade" id="categoryTagsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="categoryTagsModalTitle">Etiquetas de Categoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="categoryTagsContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos heredados del panel anterior */
.admin-panel {
    background: linear-gradient(145deg, #1a1a1a, #000000);
    border-radius: 15px;
    padding: 30px;
    border: 2px solid #8B0000;
    box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3);
}

.admin-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.admin-subtitle {
    color: #cccccc;
    font-size: 1.1rem;
    margin: 0;
}

.admin-actions {
    margin-top: 20px;
}

.stat-card {
    background: linear-gradient(45deg, #8B0000, #DC143C);
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    color: white;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 10px;
    opacity: 0.8;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.categories-management h3,
.current-filters h3 {
    color: white;
    border-bottom: 2px solid #8B0000;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.table-dark {
    --bs-table-bg: transparent;
}

.table-dark td, .table-dark th {
    border-color: #333;
}

/* Vista previa de filtros */
.filters-preview {
    background: #2c2c2c;
    border-radius: 10px;
    padding: 20px;
    border: 1px solid #444;
}

.filter-preview-section {
    margin-bottom: 20px;
    border-bottom: 1px solid #444;
    padding-bottom: 15px;
}

.filter-preview-section:last-child {
    border-bottom: none;
}

.filter-preview-title {
    color: white;
    font-size: 1.1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-preview-title i {
    color: #8B0000;
}

.filter-preview-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-preview-tag {
    background: #444;
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
    border: 1px solid #666;
}

.filter-preview-tag small {
    color: #8B0000;
    font-weight: bold;
}
</style>

<script>
function openCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'Nueva Categoría';
    document.getElementById('categoryAction').value = 'add_category';
    document.getElementById('categorySubmitBtn').textContent = 'Crear Categoría';
    document.getElementById('activeCheckboxDiv').style.display = 'none';
    document.getElementById('categoryForm').reset();
    
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function editCategory(category) {
    document.getElementById('categoryModalTitle').textContent = 'Editar Categoría';
    document.getElementById('categoryAction').value = 'update_category';
    document.getElementById('categorySubmitBtn').textContent = 'Actualizar Categoría';
    document.getElementById('activeCheckboxDiv').style.display = 'block';
    
    document.getElementById('categoryId').value = category.id;
    document.getElementById('category_name').value = category.name;
    document.getElementById('category_name').readOnly = true;
    document.getElementById('category_display_name').value = category.display_name;
    document.getElementById('category_description').value = category.description || '';
    document.getElementById('category_icon').value = category.icon || '';
    document.getElementById('category_order').value = category.filter_order;
    document.getElementById('category_active').checked = category.is_active == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function viewCategoryTags(categoryId, categoryName) {
    document.getElementById('categoryTagsModalTitle').textContent = `Etiquetas de ${categoryName}`;
    
    // Cargar etiquetas de la categoría
    fetch(`get_category_tags.php?category_id=${categoryId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('categoryTagsContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('categoryTagsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar las etiquetas');
        });
}
</script>

<?php include '../includes/footer.php'; ?>