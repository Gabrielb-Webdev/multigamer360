<?php
/**
 * Panel de administración para gestión de etiquetas de productos
 * Solo accesible para administradores
 */

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/product_manager.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$productManager = new ProductManager($pdo);

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_tag':
            $product_id = (int)$_POST['product_id'];
            $tag = trim($_POST['tag']);
            if ($product_id && $tag) {
                $productManager->addTagsToProduct($product_id, [$tag]);
                $success_message = "Etiqueta '$tag' agregada al producto.";
            }
            break;
            
        case 'remove_tag':
            $product_id = (int)$_POST['product_id'];
            $tag = trim($_POST['tag']);
            if ($product_id && $tag) {
                $productManager->removeTagFromProduct($product_id, $tag);
                $success_message = "Etiqueta '$tag' eliminada del producto.";
            }
            break;
            
        case 'update_prices':
            $product_id = (int)$_POST['product_id'];
            $price_pesos = (float)$_POST['price_pesos'];
            $price_usd = !empty($_POST['price_usd']) ? (float)$_POST['price_usd'] : null;
            
            if ($product_id && $price_pesos > 0) {
                $productManager->updateProductPrices($product_id, $price_pesos, $price_usd);
                $success_message = "Precios actualizados correctamente.";
            }
            break;
    }
}

// Obtener productos para gestión
$products = $productManager->getProducts(['limit' => 50]);
$all_tags = $productManager->getAllTags();

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="admin-panel">
        <div class="row">
            <div class="col-12">
                <div class="admin-header mb-4">
                    <h1 class="admin-title">
                        <i class="fas fa-tags text-danger"></i>
                        Gestión de Etiquetas y Precios
                    </h1>
                    <p class="admin-subtitle">
                        Panel para administrar etiquetas internas y precios de productos
                    </p>
                    <div class="mt-3">
                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                        <a href="manage_filter_categories.php" class="btn btn-outline-info me-2">
                            <i class="fas fa-filter"></i> Gestión de Filtros
                        </a>
                        <button class="btn btn-primary" onclick="openProductModal()">
                            <i class="fas fa-plus"></i> Agregar Producto
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

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo count($products); ?></div>
                                <div class="stat-label">Productos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo count($all_tags); ?></div>
                                <div class="stat-label">Etiquetas Únicas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number">ARS</div>
                                <div class="stat-label">Moneda Principal</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number">USD</div>
                                <div class="stat-label">Moneda Secundaria</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Etiquetas disponibles -->
                <div class="tags-section mb-4">
                    <h3>Etiquetas Disponibles</h3>
                    <div class="available-tags">
                        <?php foreach ($all_tags as $tag): ?>
                            <span class="tag-badge" data-tag="<?php echo htmlspecialchars($tag); ?>">
                                <?php echo htmlspecialchars($tag); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Lista de productos -->
                <div class="products-management">
                    <h3>Gestión de Productos</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Etiquetas Actuales</th>
                                    <th>Precio Pesos</th>
                                    <th>Precio USD</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <div class="product-info">
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($product['sku']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="current-tags" id="tags-<?php echo $product['id']; ?>">
                                            <?php 
                                            if ($product['tags']) {
                                                $tags = json_decode($product['tags'], true);
                                                foreach ($tags as $tag): 
                                            ?>
                                                <span class="current-tag">
                                                    <?php echo htmlspecialchars($tag); ?>
                                                    <button class="remove-tag-btn" 
                                                            onclick="removeTag(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($tag); ?>')">
                                                        ×
                                                    </button>
                                                </span>
                                            <?php 
                                                endforeach; 
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price-display">
                                            $<?php echo number_format($product['price_pesos'] ?? 0, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="price-display">
                                            USD $<?php echo number_format($product['price_usd'] ?? 0, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="openTagModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                <i class="fas fa-tag"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="openPriceModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price_pesos'] ?? 0; ?>, <?php echo $product['price_usd'] ?? 0; ?>)">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar etiquetas -->
<div class="modal fade" id="tagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">Agregar Etiqueta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_tag">
                    <input type="hidden" name="product_id" id="tag_product_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-white">Producto:</label>
                        <div id="tag_product_name" class="fw-bold text-info"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_tag" class="form-label text-white">Nueva Etiqueta:</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               id="new_tag" name="tag" placeholder="Ej: videojuego, accion, nintendo">
                    </div>
                    
                    <div class="available-tags-modal">
                        <label class="form-label text-white">Etiquetas sugeridas:</label>
                        <div class="suggested-tags">
                            <?php foreach ($all_tags as $tag): ?>
                                <span class="suggested-tag" onclick="selectSuggestedTag('<?php echo htmlspecialchars($tag); ?>')">
                                    <?php echo htmlspecialchars($tag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar Etiqueta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar precios -->
<div class="modal fade" id="priceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">Editar Precios</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_prices">
                    <input type="hidden" name="product_id" id="price_product_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-white">Producto:</label>
                        <div id="price_product_name" class="fw-bold text-info"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price_pesos" class="form-label text-white">Precio en Pesos (ARS):</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-secondary text-white">$</span>
                                    <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" 
                                           id="price_pesos" name="price_pesos" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price_usd" class="form-label text-white">Precio en USD:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-secondary text-white">USD $</span>
                                    <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" 
                                           id="price_usd" name="price_usd">
                                </div>
                                <small class="text-muted">Dejar vacío para calcular automáticamente</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Actualizar Precios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos para el panel de administración */
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

.available-tags {
    margin-bottom: 20px;
}

.tag-badge, .suggested-tag {
    display: inline-block;
    background: linear-gradient(45deg, #8B0000, #DC143C);
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    margin: 3px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tag-badge:hover, .suggested-tag:hover {
    background: linear-gradient(45deg, #DC143C, #8B0000);
    transform: scale(1.05);
}

.current-tag {
    display: inline-block;
    background: #333;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    margin: 2px;
    font-size: 0.8rem;
    position: relative;
}

.remove-tag-btn {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 12px;
    margin-left: 5px;
    cursor: pointer;
    line-height: 1;
}

.remove-tag-btn:hover {
    background: #c82333;
}

.action-buttons .btn {
    margin: 2px;
}

.price-display {
    font-weight: bold;
    color: #28a745;
}

.tags-section h3,
.products-management h3 {
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
</style>

<script>
function openTagModal(productId, productName) {
    document.getElementById('tag_product_id').value = productId;
    document.getElementById('tag_product_name').textContent = productName;
    document.getElementById('new_tag').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('tagModal'));
    modal.show();
}

function openPriceModal(productId, productName, pricePesos, priceUsd) {
    document.getElementById('price_product_id').value = productId;
    document.getElementById('price_product_name').textContent = productName;
    document.getElementById('price_pesos').value = pricePesos;
    document.getElementById('price_usd').value = priceUsd;
    
    const modal = new bootstrap.Modal(document.getElementById('priceModal'));
    modal.show();
}

function selectSuggestedTag(tag) {
    document.getElementById('new_tag').value = tag;
}

function removeTag(productId, tag) {
    if (confirm(`¿Eliminar la etiqueta "${tag}" de este producto?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_tag">
            <input type="hidden" name="product_id" value="${productId}">
            <input type="hidden" name="tag" value="${tag}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>