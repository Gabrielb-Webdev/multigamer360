<?php
/**
 * Script AJAX para obtener etiquetas de una categoría específica
 */

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acceso denegado</div>';
    exit();
}

$category_id = (int)($_GET['category_id'] ?? 0);

if (!$category_id) {
    echo '<div class="alert alert-warning">ID de categoría no válido</div>';
    exit();
}

try {
    // Obtener etiquetas de la categoría
    $sql = "
        SELECT pt.*, 
               COUNT(DISTINCT p.id) as product_count,
               tc.display_name as category_name
        FROM product_tags pt
        LEFT JOIN products p ON JSON_CONTAINS(p.tags, JSON_QUOTE(pt.tag))
        LEFT JOIN tag_categories tc ON pt.category_id = tc.id
        WHERE pt.category_id = ?
        GROUP BY pt.id
        ORDER BY pt.display_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category_id]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todas las categorías para el selector de movimiento
    $categories_sql = "SELECT id, display_name FROM tag_categories WHERE is_active = 1 ORDER BY filter_order";
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tags)) {
        echo '<div class="alert alert-info">Esta categoría no tiene etiquetas asignadas.</div>';
        exit();
    }
    
    $category_name = $tags[0]['category_name'] ?? 'Desconocida';
    
    ?>
    <div class="category-tags-management">
        <p class="text-muted mb-3">
            Gestionar etiquetas de la categoría <strong><?php echo htmlspecialchars($category_name); ?></strong>
        </p>
        
        <div class="table-responsive">
            <table class="table table-dark table-sm">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        <th>Nombre Mostrado</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tags as $tag): ?>
                    <tr>
                        <td>
                            <code class="text-warning"><?php echo htmlspecialchars($tag['tag']); ?></code>
                        </td>
                        <td>
                            <span class="text-white"><?php echo htmlspecialchars($tag['display_name']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $tag['product_count']; ?> productos</span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $tag['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $tag['is_active'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="editTag(<?php echo htmlspecialchars(json_encode($tag)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-sm" 
                                        onclick="moveTag('<?php echo htmlspecialchars($tag['tag']); ?>', '<?php echo htmlspecialchars($tag['display_name']); ?>')">
                                    <i class="fas fa-arrows-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formularios modales inline -->
    <div id="editTagForm" style="display: none;">
        <hr class="border-secondary">
        <h6 class="text-white">Editar Etiqueta</h6>
        <form method="POST" action="manage_filter_categories.php">
            <input type="hidden" name="action" value="update_tag">
            <input type="hidden" name="tag_id" id="editTagId">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label text-white">Nombre mostrado:</label>
                        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" 
                               name="display_name" id="editTagDisplayName" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editTagActive">
                            <label class="form-check-label text-white">Activa</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm">Guardar</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit()">Cancelar</button>
            </div>
        </form>
    </div>

    <div id="moveTagForm" style="display: none;">
        <hr class="border-secondary">
        <h6 class="text-white">Mover Etiqueta</h6>
        <form method="POST" action="manage_filter_categories.php">
            <input type="hidden" name="action" value="move_tag">
            <input type="hidden" name="tag" id="moveTagName">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-white">Nueva categoría:</label>
                        <select class="form-select form-select-sm bg-dark text-white border-secondary" 
                                name="new_category_id" required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['display_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-white">Nombre mostrado:</label>
                        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" 
                               name="display_name" id="moveTagDisplayName" required>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning btn-sm">Mover Etiqueta</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelMove()">Cancelar</button>
            </div>
        </form>
    </div>

    <script>
    function editTag(tag) {
        // Ocultar formulario de movimiento si está visible
        document.getElementById('moveTagForm').style.display = 'none';
        
        // Llenar formulario de edición
        document.getElementById('editTagId').value = tag.id;
        document.getElementById('editTagDisplayName').value = tag.display_name;
        document.getElementById('editTagActive').checked = tag.is_active == 1;
        
        // Mostrar formulario
        document.getElementById('editTagForm').style.display = 'block';
    }

    function moveTag(tagName, displayName) {
        // Ocultar formulario de edición si está visible
        document.getElementById('editTagForm').style.display = 'none';
        
        // Llenar formulario de movimiento
        document.getElementById('moveTagName').value = tagName;
        document.getElementById('moveTagDisplayName').value = displayName;
        
        // Mostrar formulario
        document.getElementById('moveTagForm').style.display = 'block';
    }

    function cancelEdit() {
        document.getElementById('editTagForm').style.display = 'none';
    }

    function cancelMove() {
        document.getElementById('moveTagForm').style.display = 'none';
    }
    </script>

    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar etiquetas: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>