<?php
// Determinar si es creación o edición
$product_id = $_GET['id'] ?? null;
$is_edit = !empty($product_id);
$page_title = $is_edit ? 'Editar Producto' : 'Nuevo Producto';

require_once 'inc/header.php';

// Verificar permisos
$required_permission = $is_edit ? 'update' : 'create';
if (!hasPermission('products', $required_permission)) {
    $_SESSION['error'] = 'No tiene permisos para ' . ($is_edit ? 'editar' : 'crear') . ' productos';
    header('Location: products.php');
    exit;
}

// Cargar producto si es edición
$product = null;
if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $_SESSION['error'] = 'Producto no encontrado';
            header('Location: products.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error al cargar producto: ' . $e->getMessage();
        header('Location: products.php');
        exit;
    }
}

// Cargar datos auxiliares
try {
    // Categorías
    $categories_stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $categories_stmt->fetchAll();
    
    // Marcas
    $brands_stmt = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
    $brands = $brands_stmt->fetchAll();
    
    // Tags
    $tags_stmt = $pdo->query("SELECT DISTINCT tag FROM product_tags ORDER BY tag");
    $available_tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Imágenes del producto (si es edición)
    $product_images = [];
    if ($is_edit) {
        $images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC");
        $images_stmt->execute([$product_id]);
        $product_images = $images_stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar datos: ' . $e->getMessage();
    $categories = [];
    $brands = [];
    $available_tags = [];
    $product_images = [];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de seguridad inválido');
        }
        
        // Validar datos requeridos
        $required_fields = ['title', 'description', 'price', 'stock', 'category_id', 'status'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }
        
        // Generar SKU si no se proporciona
        $sku = !empty($_POST['sku']) ? $_POST['sku'] : generateSKU($_POST['title']);
        
        // Verificar SKU único
        $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $sku_check->execute([$sku, $product_id ?? 0]);
        if ($sku_check->fetch()) {
            throw new Exception('El SKU ya existe');
        }
        
        $pdo->beginTransaction();
        
        // Datos del producto
        $product_data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'sku' => $sku,
            'price' => floatval($_POST['price']),
            'sale_price' => !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null,
            'stock' => intval($_POST['stock']),
            'category_id' => intval($_POST['category_id']),
            'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
            'dimensions' => trim($_POST['dimensions'] ?? ''),
            'status' => $_POST['status'],
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($is_edit) {
            // Actualizar producto
            $set_clauses = [];
            foreach ($product_data as $key => $value) {
                $set_clauses[] = "$key = :$key";
            }
            
            $sql = "UPDATE products SET " . implode(', ', $set_clauses) . " WHERE id = :id";
            $product_data['id'] = $product_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($product_data);
            
        } else {
            // Crear producto
            $product_data['created_at'] = date('Y-m-d H:i:s');
            
            $columns = implode(', ', array_keys($product_data));
            $placeholders = ':' . implode(', :', array_keys($product_data));
            
            $sql = "INSERT INTO products ($columns) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($product_data);
            
            $product_id = $pdo->lastInsertId();
        }
        
        // Guardar tags
        if (!empty($_POST['tags'])) {
            // Eliminar tags existentes
            $pdo->prepare("DELETE FROM product_tags WHERE product_id = ?")->execute([$product_id]);
            
            // Insertar nuevos tags
            $tags = array_filter(array_map('trim', explode(',', $_POST['tags'])));
            foreach ($tags as $tag) {
                $pdo->prepare("INSERT INTO product_tags (product_id, tag) VALUES (?, ?)")
                    ->execute([$product_id, $tag]);
            }
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = $is_edit ? 'Producto actualizado correctamente' : 'Producto creado correctamente';
        header('Location: products.php');
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
}

// Función para generar SKU
function generateSKU($title) {
    $sku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 8));
    return $sku . '-' . rand(1000, 9999);
}
?>

<div class="row">
    <div class="col-12">
        <form method="POST" enctype="multipart/form-data" id="product-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
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
                            <div class="mb-3">
                                <label for="title" class="form-label">Título *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($product['title'] ?? ''); ?>" 
                                       required maxlength="255">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" 
                                           maxlength="50">
                                    <div class="form-text">Se generará automáticamente si se deja vacío</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Estado *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="draft" <?php echo ($product['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                                        <option value="active" <?php echo ($product['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="short_description" class="form-label">Descripción Corta</label>
                                <textarea class="form-control" id="short_description" name="short_description" 
                                          rows="2" maxlength="500"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                                <div class="form-text">Máximo 500 caracteres</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="6" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags" 
                                       value="<?php 
                                       if ($is_edit) {
                                           $tags_stmt = $pdo->prepare("SELECT tag FROM product_tags WHERE product_id = ?");
                                           $tags_stmt->execute([$product_id]);
                                           $current_tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
                                           echo htmlspecialchars(implode(', ', $current_tags));
                                       }
                                       ?>"
                                       placeholder="gaming, pc, accesorios...">
                                <div class="form-text">Separe los tags con comas</div>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                       value="1" <?php echo (!empty($product['is_featured'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    Producto Destacado
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Imágenes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images me-2"></i>
                                Imágenes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="images" class="form-label">Subir Imágenes</label>
                                <input type="file" class="form-control" id="images" name="images[]" 
                                       multiple accept="image/*">
                                <div class="form-text">
                                    Seleccione múltiples imágenes. Formatos: JPG, PNG, WebP. Máximo 5MB por imagen.
                                </div>
                            </div>
                            
                            <!-- Vista previa de imágenes nuevas -->
                            <div id="image-preview" class="row g-3 mb-3"></div>
                            
                            <!-- Imágenes existentes -->
                            <?php if (!empty($product_images)): ?>
                            <div class="current-images">
                                <h6>Imágenes Actuales</h6>
                                <div class="row g-3" id="current-images">
                                    <?php foreach ($product_images as $image): ?>
                                    <div class="col-md-3" data-image-id="<?php echo $image['id']; ?>">
                                        <div class="card">
                                            <img src="../uploads/products/<?php echo $image['filename']; ?>" 
                                                 class="card-img-top" style="height: 150px; object-fit: cover;"
                                                 alt="<?php echo htmlspecialchars($image['alt_text']); ?>">
                                            <div class="card-body p-2">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" 
                                                           name="main_image" value="<?php echo $image['id']; ?>"
                                                           <?php echo $image['is_main'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small">
                                                        Imagen Principal
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                                                        onclick="removeImage(<?php echo $image['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- SEO -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-search me-2"></i>
                                SEO
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_title" class="form-label">Meta Título</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                       value="<?php echo htmlspecialchars($product['meta_title'] ?? ''); ?>" 
                                       maxlength="60">
                                <div class="form-text">Recomendado: 50-60 caracteres</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Descripción</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" 
                                          rows="3" maxlength="160"><?php echo htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
                                <div class="form-text">Recomendado: 150-160 caracteres</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel lateral -->
                <div class="col-lg-4">
                    <!-- Precios e inventario -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-dollar-sign me-2"></i>
                                Precios e Inventario
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="price" class="form-label">Precio Regular *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo $product['price'] ?? ''; ?>" 
                                           min="0" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sale_price" class="form-label">Precio de Oferta</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="sale_price" name="sale_price" 
                                           value="<?php echo $product['sale_price'] ?? ''; ?>" 
                                           min="0" step="0.01">
                                </div>
                                <div class="form-text">Dejar vacío si no hay oferta</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock *</label>
                                <input type="number" class="form-control" id="stock" name="stock" 
                                       value="<?php echo $product['stock'] ?? '0'; ?>" 
                                       min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categorización -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags me-2"></i>
                                Categorización
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Categoría *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marca</label>
                                <select class="form-select" id="brand_id" name="brand_id">
                                    <option value="">Sin marca</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>" 
                                                <?php echo ($product['brand_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Atributos físicos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-weight me-2"></i>
                                Atributos Físicos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="weight" class="form-label">Peso (kg)</label>
                                <input type="number" class="form-control" id="weight" name="weight" 
                                       value="<?php echo $product['weight'] ?? ''; ?>" 
                                       min="0" step="0.01">
                            </div>
                            
                            <div class="mb-3">
                                <label for="dimensions" class="form-label">Dimensiones</label>
                                <input type="text" class="form-control" id="dimensions" name="dimensions" 
                                       value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>" 
                                       placeholder="20x15x10 cm">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $is_edit ? 'Actualizar' : 'Crear'; ?> Producto
                                </button>
                                
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                                
                                <?php if ($is_edit): ?>
                                <a href="../product.php?id=<?php echo $product_id; ?>" 
                                   class="btn btn-outline-info" target="_blank">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver en Sitio
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Vista previa de imágenes
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    Array.from(e.target.files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3';
                col.innerHTML = `
                    <div class="card">
                        <img src="${e.target.result}" class="card-img-top" 
                             style="height: 150px; object-fit: cover;" alt="Preview">
                        <div class="card-body p-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="new_main_image" 
                                       value="${index}" ${index === 0 ? 'checked' : ''}>
                                <label class="form-check-label small">Principal</label>
                            </div>
                        </div>
                    </div>
                `;
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        }
    });
});

// Generar SKU automáticamente
document.getElementById('title').addEventListener('blur', function() {
    const skuField = document.getElementById('sku');
    if (!skuField.value && this.value) {
        const sku = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 8);
        skuField.value = sku + '-' + Math.floor(Math.random() * 9000 + 1000);
    }
});

// Validación del formulario
document.getElementById('product-form').addEventListener('submit', function(e) {
    const price = parseFloat(document.getElementById('price').value);
    const salePrice = parseFloat(document.getElementById('sale_price').value);
    
    if (salePrice && salePrice >= price) {
        e.preventDefault();
        Utils.showToast('El precio de oferta debe ser menor al precio regular', 'danger');
        return false;
    }
});

// Función para eliminar imagen existente
function removeImage(imageId) {
    Utils.confirm('¿Está seguro de que desea eliminar esta imagen?', function() {
        fetch('api/product-images.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': AdminPanel.csrfToken
            },
            body: JSON.stringify({ image_id: imageId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-image-id="${imageId}"]`).remove();
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

// Autocompletar tags
$('#tags').autocomplete({
    source: <?php echo json_encode($available_tags); ?>,
    minLength: 2,
    multiple: true,
    multipleSeparator: ", "
});
</script>

<?php require_once 'inc/footer.php'; ?>