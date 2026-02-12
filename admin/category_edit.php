<?php
$category_id = intval($_GET['id'] ?? 0);
$is_edit = $category_id > 0;

$page_title = $is_edit ? 'Editar Categoría' : 'Nueva Categoría';

require_once 'inc/header.php';

// Verificar permisos
$required_permission = $is_edit ? 'update' : 'create';
if (!hasPermission('categories', $required_permission)) {
    $_SESSION['error'] = 'No tiene permisos para ' . ($is_edit ? 'editar' : 'crear') . ' categorías';
    header('Location: categories.php');
    exit;
}

$category = null;
$parent_categories = [];

try {
    // Obtener categorías padre disponibles
    if ($is_edit) {
        $parent_query = "SELECT id, name FROM categories WHERE parent_id IS NULL AND id != ? AND is_active = 1 ORDER BY name";
        $parent_stmt = $pdo->prepare($parent_query);
        $parent_stmt->execute([$category_id]);
        $parent_categories = $parent_stmt->fetchAll();
    } else {
        $parent_categories = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name")->fetchAll();
    }
    
    // Si es edición, obtener datos de la categoría
    if ($is_edit) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch();
        
        if (!$category) {
            $_SESSION['error'] = 'Categoría no encontrada';
            header('Location: categories.php');
            exit;
        }
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar datos: ' . $e->getMessage();
    header('Location: categories.php');
    exit;
}

// Valores por defecto
$category = $category ?: [
    'name' => '',
    'slug' => '',
    'description' => '',
    'parent_id' => null,
    'image' => '',
    'banner_image' => '',
    'icon_class' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'is_active' => 1,
    'is_featured' => 0,
    'sort_order' => 0
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="categories.php"><i class="fas fa-tags"></i> Categorías</a>
            </li>
            <li class="breadcrumb-item active">
                <?php echo $is_edit ? 'Editar' : 'Nueva'; ?>
            </li>
        </ol>
    </nav>
    
    <div>
        <a href="categories.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<form id="categoryForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
    <?php endif; ?>
    
    <div class="row">
        <!-- Información básica -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información Básica
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Categoría <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                <div class="form-text">Nombre descriptivo de la categoría</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="slug" class="form-label">URL Amigable (Slug) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="slug" name="slug" 
                                       value="<?php echo htmlspecialchars($category['slug']); ?>" required>
                                <div class="form-text">URL amigable (se genera automáticamente)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                                  placeholder="Descripción detallada de la categoría..."><?php echo htmlspecialchars($category['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">Categoría Padre</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">Sin categoría padre (principal)</option>
                                    <?php foreach ($parent_categories as $parent): ?>
                                        <option value="<?php echo $parent['id']; ?>" 
                                                <?php echo $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($parent['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Dejar vacío para categoría principal</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Orden</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="<?php echo $category['sort_order']; ?>" min="0">
                                <div class="form-text">Orden de visualización</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="icon_class" class="form-label">Icono</label>
                                <input type="text" class="form-control" id="icon_class" name="icon_class" 
                                       value="<?php echo htmlspecialchars($category['icon_class']); ?>"
                                       placeholder="fas fa-gamepad">
                                <div class="form-text">Clase de Font Awesome</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SEO -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i>
                        Optimización SEO
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="meta_title" class="form-label">Meta Título</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" 
                               value="<?php echo htmlspecialchars($category['meta_title']); ?>"
                               maxlength="60">
                        <div class="form-text">
                            <span id="meta_title_count">0</span>/60 caracteres - 
                            Se usa el nombre si está vacío
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="meta_description" class="form-label">Meta Descripción</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" 
                                  rows="3" maxlength="160"
                                  placeholder="Descripción breve para motores de búsqueda..."><?php echo htmlspecialchars($category['meta_description']); ?></textarea>
                        <div class="form-text">
                            <span id="meta_description_count">0</span>/160 caracteres
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="meta_keywords" class="form-label">Palabras Clave</label>
                        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                               value="<?php echo htmlspecialchars($category['meta_keywords']); ?>"
                               placeholder="palabra1, palabra2, palabra3...">
                        <div class="form-text">Separadas por comas</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel lateral -->
        <div class="col-lg-4">
            <!-- Estado y configuración -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Configuración
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   value="1" <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Categoría Activa
                            </label>
                        </div>
                        <div class="form-text">Solo categorías activas son visibles en el sitio</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                   value="1" <?php echo $category['is_featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                Categoría Destacada
                            </label>
                        </div>
                        <div class="form-text">Aparece en secciones especiales</div>
                    </div>
                </div>
            </div>
            
            <!-- Imagen principal -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-image me-2"></i>
                        Imagen Principal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">JPG, PNG o WebP. Máximo 2MB. Recomendado: 400x400px</div>
                    </div>
                    
                    <?php if ($category['image']): ?>
                    <div class="current-image mb-3">
                        <label class="form-label">Imagen Actual</label>
                        <div class="text-center">
                            <img src="../uploads/categories/<?php echo $category['image']; ?>" 
                                 alt="Imagen actual" class="img-thumbnail" 
                                 style="max-width: 200px; max-height: 200px;">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="removeCurrentImage('image')">
                                    <i class="fas fa-trash"></i> Eliminar Imagen
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Banner -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-panorama me-2"></i>
                        Banner de Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                        <div class="form-text">JPG, PNG o WebP. Máximo 2MB. Recomendado: 1200x300px</div>
                    </div>
                    
                    <?php if ($category['banner_image']): ?>
                    <div class="current-banner mb-3">
                        <label class="form-label">Banner Actual</label>
                        <div class="text-center">
                            <img src="../uploads/categories/<?php echo $category['banner_image']; ?>" 
                                 alt="Banner actual" class="img-thumbnail" 
                                 style="max-width: 100%; max-height: 150px;">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="removeCurrentImage('banner_image')">
                                    <i class="fas fa-trash"></i> Eliminar Banner
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $is_edit ? 'Actualizar' : 'Crear'; ?> Categoría
                        </button>
                        
                        <?php if ($is_edit): ?>
                        <a href="../productos.php?categoria=<?php echo $category['slug']; ?>" 
                           class="btn btn-outline-info" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            Ver en Sitio
                        </a>
                        <?php endif; ?>
                        
                        <a href="categories.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Generar slug automáticamente
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim('-');
    
    document.getElementById('slug').value = slug;
});

// Contadores de caracteres para SEO
function setupCharCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    
    function updateCount() {
        const count = input.value.length;
        counter.textContent = count;
        counter.className = count > maxLength ? 'text-danger' : count > maxLength * 0.8 ? 'text-warning' : '';
    }
    
    input.addEventListener('input', updateCount);
    updateCount();
}

setupCharCounter('meta_title', 'meta_title_count', 60);
setupCharCounter('meta_description', 'meta_description_count', 160);

// Eliminar imagen actual
function removeCurrentImage(type) {
    Utils.confirm('¿Está seguro de que desea eliminar esta imagen?', function() {
        const formData = new FormData();
        formData.append('action', 'remove_image');
        formData.append('type', type);
        formData.append('id', <?php echo $category_id; ?>);
        formData.append('csrf_token', AdminPanel.csrfToken);
        
        fetch('api/categories.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar la sección de imagen actual
                const currentSection = type === 'image' ? 
                    document.querySelector('.current-image') : 
                    document.querySelector('.current-banner');
                
                if (currentSection) {
                    currentSection.remove();
                }
                
                Utils.showToast('Imagen eliminada correctamente', 'success');
            } else {
                Utils.showToast(data.message || 'Error al eliminar imagen', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('Error de conexión', 'danger');
        });
    });
}

// Enviar formulario
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
    
    // Mostrar indicador de carga
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;
    
    fetch('api/categories.php', {
        method: isEdit ? 'PUT' : 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Utils.showToast(
                isEdit ? 'Categoría actualizada correctamente' : 'Categoría creada correctamente', 
                'success'
            );
            
            // Redirigir después de un breve retraso
            setTimeout(() => {
                window.location.href = isEdit ? 'categories.php' : `category_edit.php?id=${data.category_id}`;
            }, 1500);
        } else {
            Utils.showToast(data.message || 'Error al guardar categoría', 'danger');
            
            // Restaurar botón
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Mostrar errores de validación si existen
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        
                        // Buscar o crear elemento de error
                        let errorDiv = input.parentNode.querySelector('.invalid-feedback');
                        if (!errorDiv) {
                            errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            input.parentNode.appendChild(errorDiv);
                        }
                        errorDiv.textContent = data.errors[field];
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Utils.showToast('Error de conexión', 'danger');
        
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Limpiar errores de validación al escribir
document.querySelectorAll('input, textarea, select').forEach(input => {
    input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
        const errorDiv = this.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    });
});
</script>

<?php require_once 'inc/footer.php'; ?>