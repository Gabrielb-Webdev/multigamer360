<?php
/**
 * CREAR NUEVO PRODUCTO
 * Formulario exclusivo para la creación de nuevos productos
 */

$product_id = null;
$is_edit = false;
$page_title = 'Nuevo Producto';

require_once 'inc/header.php';

// Verificar permisos de creación
if (!hasPermission('products', 'create')) {
    $_SESSION['error'] = 'No tiene permisos para crear productos';
    header('Location: products.php');
    exit;
}

// No hay producto a cargar (nuevo)
$product = null;

// Cargar datos auxiliares
try {
    // Categorías
    $categories_stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $categories_stmt->fetchAll();
    
    // Marcas
    $brands_stmt = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
    $brands = $brands_stmt->fetchAll();
    
    // Consolas
    $consoles_stmt = $pdo->query("SELECT id, name FROM consoles WHERE is_active = 1 ORDER BY name");
    $consoles = $consoles_stmt->fetchAll();
    
    // Géneros
    $genres_stmt = $pdo->query("SELECT id, name FROM genres WHERE is_active = 1 ORDER BY name");
    $genres = $genres_stmt->fetchAll();
    
    // No hay géneros ni imágenes seleccionadas (nuevo producto)
    $selected_genres = [];
    $product_images = [];
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar datos: ' . $e->getMessage();
    $categories = [];
    $brands = [];
    $consoles = [];
    $genres = [];
    $selected_genres = [];
    $product_images = [];
}

// Procesar formulario de CREACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de seguridad inválido');
        }
        
        // Validar datos requeridos
        $required_fields = [
            'name' => 'Nombre del producto',
            'description' => 'Descripción',
            'price_pesos' => 'Precio en pesos',
            'stock_quantity' => 'Cantidad en stock',
            'category_id' => 'Categoría',
            'brand_id' => 'Marca',
            'console_id' => 'Consola/Plataforma'
        ];
        
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field]) && $_POST[$field] !== '0') {
                throw new Exception("El campo '{$label}' es obligatorio");
            }
        }
        
        // Generar SKU si no se proporciona
        $sku = !empty($_POST['sku']) ? $_POST['sku'] : generateSKU($_POST['name']);
        
        // Verificar SKU único
        $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $sku_check->execute([$sku]);
        if ($sku_check->fetch()) {
            throw new Exception('El SKU ya existe');
        }
        
        // Generar slug
        $slug = generateSlug($_POST['name']);
        
        $pdo->beginTransaction();
        
        // Datos del producto
        $product_data = [
            'name' => trim($_POST['name']),
            'slug' => $slug,
            'description' => trim($_POST['description']),
            'sku' => $sku,
            'price_pesos' => floatval($_POST['price_pesos']),
            'price_dollars' => !empty($_POST['price_dollars']) ? floatval($_POST['price_dollars']) : null,
            'is_on_sale' => isset($_POST['is_on_sale']) ? 1 : 0,
            'discount_percentage' => !empty($_POST['discount_percentage']) ? floatval($_POST['discount_percentage']) : 0.00,
            'stock_quantity' => intval($_POST['stock_quantity']),
            'category_id' => intval($_POST['category_id']),
            'brand_id' => intval($_POST['brand_id']),
            'console_id' => intval($_POST['console_id']),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // CREAR PRODUCTO NUEVO
        $columns = implode(', ', array_keys($product_data));
        $placeholders = ':' . implode(', :', array_keys($product_data));
        
        $sql = "INSERT INTO products ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($product_data);
        
        $product_id = $pdo->lastInsertId();
        
        // Guardar géneros
        if (!empty($_POST['genres'])) {
            $genres_to_insert = is_array($_POST['genres']) ? $_POST['genres'] : [];
            foreach ($genres_to_insert as $genre_id) {
                $pdo->prepare("INSERT INTO product_genres (product_id, genre_id) VALUES (?, ?)")
                    ->execute([$product_id, intval($genre_id)]);
            }
        }
        
        // Procesar imágenes
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $display_order = 0;
            $first_image = true;
            
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$key];
                    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_filename = uniqid('prod_') . '.' . $file_extension;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $display_order++;
                        // La primera imagen es la principal
                        $is_primary = $first_image ? 1 : 0;
                        $first_image = false;
                        
                        $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)")
                            ->execute([$product_id, $new_filename, $is_primary, $display_order]);
                    }
                }
            }
        }
        
        $pdo->commit();
        
        // Redirigir con parámetros de éxito
        $product_name_encoded = urlencode($_POST['name']);
        header('Location: product_create.php?success=1&product_id=' . $product_id . '&product_name=' . $product_name_encoded);
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
}

// Funciones auxiliares
function generateSKU($title) {
    $sku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 8));
    return $sku . '-' . rand(1000, 9999);
}

function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'producto-' . time() : $text;
}
?>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Creando nuevo producto.</strong> Complete la información básica y guarde. Luego podrá agregar imágenes adicionales desde la página de edición.
        </div>
        
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
                                <label for="name" class="form-label">Nombre del Producto *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                                       required maxlength="255">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" 
                                           maxlength="100">
                                    <div class="form-text">Se generará automáticamente si se deja vacío</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Estado *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" selected>Activo</option>
                                        <option value="inactive">Inactivo</option>
                                        <option value="out_of_stock">Agotado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="6" required></textarea>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                <label class="form-check-label" for="is_featured">
                                    Producto Destacado
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Visible en la tienda
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Imágenes (solo subida inicial) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images me-2"></i>
                                Imágenes del Producto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Consejo:</strong> Puede subir las imágenes ahora o agregarlas después desde la página de edición, donde tendrá más opciones de gestión.
                            </div>
                            
                            <div class="mb-3">
                                <label for="images" class="form-label">Agregar Imágenes (Opcional)</label>
                                <input type="file" class="form-control" id="images" name="images[]"
                                       multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Formatos: JPG, PNG, WebP. Máximo 5MB por imagen.
                                </div>
                            </div>
                            
                            <div class="alert alert-info" id="drag-drop-info" style="display: none;">
                                <i class="fas fa-hand-rock"></i> <strong>Arrastra las imágenes</strong> para cambiar el orden. La primera imagen será la portada.
                            </div>
                            
                            <!-- Vista previa con drag & drop -->
                            <div id="image-preview" class="row g-3"></div>
                        </div>
                    </div>
                    
                    <!-- SEO -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-search me-2"></i>
                                SEO - Optimización para Buscadores
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="auto-generate-seo">
                                <i class="fas fa-magic"></i> Auto-generar
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_title" class="form-label">
                                    Meta Título
                                    <span class="text-muted small" id="meta-title-counter">(0/60)</span>
                                </label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" maxlength="60">
                                <div class="form-text">
                                    <i class="fas fa-lightbulb"></i> Recomendado: 50-60 caracteres.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">
                                    Meta Descripción
                                    <span class="text-muted small" id="meta-desc-counter">(0/160)</span>
                                </label>
                                <textarea class="form-control" id="meta_description" name="meta_description" 
                                          rows="3" maxlength="160"></textarea>
                                <div class="form-text">
                                    <i class="fas fa-lightbulb"></i> Recomendado: 150-160 caracteres.
                                </div>
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
                                <label for="price_pesos" class="form-label">Precio en Pesos (COP) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price_pesos" name="price_pesos" 
                                           min="0" step="0.01" required>
                                    <span class="input-group-text">COP</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_dollars" class="form-label">Precio en Dólares (USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price_dollars" name="price_dollars" 
                                           min="0" step="0.01">
                                    <span class="input-group-text">USD</span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_on_sale" name="is_on_sale" value="1">
                                    <label class="form-check-label fw-bold" for="is_on_sale">
                                        <i class="fas fa-percentage text-danger"></i> Producto en Oferta
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="discount-section" style="display: none;">
                                <label for="discount_percentage" class="form-label">
                                    Porcentaje de Descuento
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                           value="0" min="0" max="100" step="0.01">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div id="discount-preview" class="mt-2"></div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Cantidad en Stock *</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       value="0" min="0" required>
                                <div id="stock-alert" class="form-text"></div>
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
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marca *</label>
                                <select class="form-select" id="brand_id" name="brand_id" required>
                                    <option value="">Seleccionar marca</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>">
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="console_id" class="form-label">Consola / Plataforma *</label>
                                <select class="form-select" id="console_id" name="console_id" required>
                                    <option value="">Seleccionar consola</option>
                                    <?php foreach ($consoles as $console): ?>
                                        <option value="<?php echo $console['id']; ?>">
                                            <?php echo htmlspecialchars($console['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Géneros</label>
                                <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                    <?php if (empty($genres)): ?>
                                        <p class="text-muted">No hay géneros disponibles</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($genres as $genre): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="genres[]" value="<?php echo $genre['id']; ?>" 
                                                           id="genre_<?php echo $genre['id']; ?>">
                                                    <label class="form-check-label" for="genre_<?php echo $genre['id']; ?>">
                                                        <?php echo htmlspecialchars($genre['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    Crear Producto
                                </button>
                                
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- SortableJS para Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
/* Estilos para drag & drop */
.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}

.sortable-chosen {
    transform: scale(1.05);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}

.sortable-drag {
    opacity: 0.8;
}

.sortable-image-item {
    transition: transform 0.2s;
}

.sortable-image-item:hover {
    transform: translateY(-5px);
}
</style>

<script>
// JavaScript para creación con Drag & Drop
document.addEventListener('DOMContentLoaded', function() {
    // Array para acumular archivos seleccionados
    let selectedFiles = [];
    let sortableInstance = null;
    
    // Vista previa de imágenes (acumulativa con drag & drop)
    const imagesInput = document.getElementById('images');
    const preview = document.getElementById('image-preview');
    const dragInfo = document.getElementById('drag-drop-info');
    
    if (imagesInput) {
        imagesInput.addEventListener('change', function(e) {
            // Agregar nuevos archivos al array existente
            const newFiles = Array.from(this.files);
            newFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                }
            });
            
            // Regenerar vista previa
            renderImagePreview();
            
            console.log('Total de imágenes seleccionadas:', selectedFiles.length);
        });
    }
    
    function renderImagePreview() {
        preview.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            dragInfo.style.display = 'none';
            return;
        }
        
        // Mostrar info de drag & drop
        dragInfo.style.display = 'block';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3 sortable-image-item';
                col.dataset.index = index;
                col.innerHTML = `
                    <div class="card border-success h-100" style="cursor: move;">
                        <div class="card-header bg-success text-white py-1 d-flex justify-content-between align-items-center">
                            <small><i class="fas fa-grip-vertical"></i> #${index + 1}</small>
                            <button type="button" class="btn btn-sm btn-close btn-close-white" 
                                    onclick="removePreviewImage(${index})" aria-label="Eliminar"></button>
                        </div>
                        <img src="${e.target.result}" class="card-img-top" 
                             style="height: 150px; object-fit: cover;" alt="Vista previa">
                        <div class="card-body p-2 text-center">
                            ${index === 0 ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>' : '<span class="badge bg-secondary">Extra</span>'}
                        </div>
                    </div>
                `;
                preview.appendChild(col);
                
                // Inicializar SortableJS después de agregar todas las imágenes
                if (index === selectedFiles.length - 1) {
                    initSortable();
                }
            };
            reader.readAsDataURL(file);
        });
    }
    
    function initSortable() {
        // Destruir instancia anterior si existe
        if (sortableInstance) {
            sortableInstance.destroy();
        }
        
        // Crear nueva instancia de Sortable
        sortableInstance = new Sortable(preview, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.card', // Toda la card es arrastrable
            onEnd: function(evt) {
                // Reordenar el array de archivos según el nuevo orden
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                
                // Mover elemento en el array
                const movedFile = selectedFiles.splice(oldIndex, 1)[0];
                selectedFiles.splice(newIndex, 0, movedFile);
                
                console.log('Imagen movida de posición', oldIndex, '→', newIndex);
                
                // Actualizar números y badges
                updateImageNumbers();
            }
        });
    }
    
    function updateImageNumbers() {
        const items = preview.querySelectorAll('.sortable-image-item');
        items.forEach((item, index) => {
            // Actualizar número
            const numberSpan = item.querySelector('.card-header small');
            numberSpan.innerHTML = `<i class="fas fa-grip-vertical"></i> #${index + 1}`;
            
            // Actualizar badge (portada solo para el primero)
            const badgeDiv = item.querySelector('.card-body');
            if (index === 0) {
                badgeDiv.innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>';
            } else {
                badgeDiv.innerHTML = '<span class="badge bg-secondary">Extra</span>';
            }
            
            // Actualizar data-index
            item.dataset.index = index;
            
            // Actualizar onclick del botón eliminar
            const closeBtn = item.querySelector('.btn-close');
            closeBtn.setAttribute('onclick', `removePreviewImage(${index})`);
        });
    }
    
    // Función global para eliminar imagen de vista previa
    window.removePreviewImage = function(index) {
        selectedFiles.splice(index, 1);
        renderImagePreview();
        console.log('Imágenes restantes:', selectedFiles.length);
    };
    
    // Antes de enviar el formulario, actualizar el input con todos los archivos en el orden correcto
    const form = document.getElementById('product-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Crear un DataTransfer para actualizar el input con todos los archivos acumulados
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            imagesInput.files = dt.files;
            
            console.log('Enviando formulario con', selectedFiles.length, 'imágenes en orden:', 
                        selectedFiles.map((f, i) => `#${i+1}: ${f.name}`));
        });
    }
    
    // Auto-generar SEO
    document.getElementById('auto-generate-seo').addEventListener('click', function() {
        const name = document.getElementById('name').value;
        const description = document.getElementById('description').value;
        
        if (!name) {
            alert('Por favor ingrese el nombre del producto primero');
            return;
        }
        
        let metaTitle = name;
        if (metaTitle.length > 60) {
            metaTitle = metaTitle.substring(0, 57) + '...';
        }
        document.getElementById('meta_title').value = metaTitle;
        
        let metaDesc = description || name;
        metaDesc = metaDesc.replace(/<[^>]*>/g, '').trim();
        if (metaDesc.length > 160) {
            metaDesc = metaDesc.substring(0, 157) + '...';
        }
        document.getElementById('meta_description').value = metaDesc;
        
        updateSEOCounters();
    });
    
    // Contadores SEO
    function updateSEOCounters() {
        const metaTitle = document.getElementById('meta_title').value;
        const metaDesc = document.getElementById('meta_description').value;
        
        document.getElementById('meta-title-counter').textContent = `(${metaTitle.length}/60)`;
        document.getElementById('meta-desc-counter').textContent = `(${metaDesc.length}/160)`;
    }
    
    document.getElementById('meta_title').addEventListener('input', updateSEOCounters);
    document.getElementById('meta_description').addEventListener('input', updateSEOCounters);
    
    // Toggle descuento
    document.getElementById('is_on_sale').addEventListener('change', function() {
        document.getElementById('discount-section').style.display = this.checked ? 'block' : 'none';
    });
    
    // Vista previa descuento
    function updateDiscountPreview() {
        const price = parseFloat(document.getElementById('price_pesos').value) || 0;
        const discount = parseFloat(document.getElementById('discount_percentage').value) || 0;
        const preview = document.getElementById('discount-preview');
        
        if (discount > 0 && price > 0) {
            const finalPrice = price - (price * discount / 100);
            const formatter = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            });
            
            preview.innerHTML = `
                <div class="alert alert-success mb-0">
                    <small><del>${formatter.format(price)}</del></small><br>
                    <strong class="fs-5">${formatter.format(finalPrice)}</strong>
                </div>
            `;
        } else {
            preview.innerHTML = '';
        }
    }
    
    document.getElementById('price_pesos').addEventListener('input', updateDiscountPreview);
    document.getElementById('discount_percentage').addEventListener('input', updateDiscountPreview);
    
    // Alerta de stock
    document.getElementById('stock_quantity').addEventListener('input', function() {
        const stock = parseInt(this.value) || 0;
        const alert = document.getElementById('stock-alert');
        
        if (stock === 0) {
            alert.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Sin stock</span>';
        } else if (stock < 5) {
            alert.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Stock bajo</span>';
        } else {
            alert.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Stock disponible</span>';
        }
    });
    
    // Auto-generar SKU
    document.getElementById('name').addEventListener('blur', function() {
        const skuField = document.getElementById('sku');
        if (!skuField.value && this.value) {
            const sku = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 8);
            skuField.value = sku + '-' + Math.floor(Math.random() * 9000 + 1000);
        }
    });
});
</script>

<!-- Modal de Éxito -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>¡Producto Creado Exitosamente!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h4 class="mb-3">Producto guardado correctamente</h4>
                <p class="text-muted mb-0">
                    <strong id="productNameDisplay"></strong>
                </p>
                <p class="text-muted small">ID: <span id="productIdDisplay"></span></p>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" id="editProductBtn" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Editar Producto
                </a>
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-2"></i>Ver Lista de Productos
                </a>
                <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="window.location.reload()">
                    <i class="fas fa-plus me-2"></i>Crear Otro Producto
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['product_id'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Llenar datos del modal
    document.getElementById('productNameDisplay').textContent = '<?php echo htmlspecialchars(urldecode($_GET['product_name'] ?? '')); ?>';
    document.getElementById('productIdDisplay').textContent = '<?php echo intval($_GET['product_id']); ?>';
    document.getElementById('editProductBtn').href = 'product_edit.php?id=<?php echo intval($_GET['product_id']); ?>';
    
    // Mostrar modal inmediatamente
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    successModal.show();
    
    // Limpiar URL después de mostrar el modal (quitar parámetros)
    successModal._element.addEventListener('hidden.bs.modal', function() {
        // Limpiar URL sin recargar la página
        window.history.replaceState({}, document.title, 'product_create.php');
    });
});
</script>
<?php endif; ?>

<?php require_once 'inc/footer.php'; ?>
