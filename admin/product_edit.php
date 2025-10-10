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
    
    // Consolas
    $consoles_stmt = $pdo->query("SELECT id, name FROM consoles WHERE is_active = 1 ORDER BY name");
    $consoles = $consoles_stmt->fetchAll();
    
    // Géneros
    $genres_stmt = $pdo->query("SELECT id, name FROM genres WHERE is_active = 1 ORDER BY name");
    $genres = $genres_stmt->fetchAll();
    
    // Géneros seleccionados (si es edición)
    $selected_genres = [];
    if ($is_edit) {
        $selected_genres_stmt = $pdo->prepare("SELECT genre_id FROM product_genres WHERE product_id = ?");
        $selected_genres_stmt->execute([$product_id]);
        $selected_genres = $selected_genres_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Imágenes del producto (si es edición)
    $product_images = [];
    if ($is_edit) {
        $images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC");
        $images_stmt->execute([$product_id]);
        $product_images = $images_stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar datos: ' . $e->getMessage();
    $categories = [];
    $brands = [];
    $consoles = [];
    $genres = [];
    $selected_genres = [];
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
        $required_fields = ['name', 'description', 'price_pesos', 'stock_quantity', 'category_id'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field]) && $_POST[$field] !== '0') {
                throw new Exception("El campo {$field} es requerido");
            }
        }
        
        // Generar SKU si no se proporciona
        $sku = !empty($_POST['sku']) ? $_POST['sku'] : generateSKU($_POST['name']);
        
        // Verificar SKU único
        $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $sku_check->execute([$sku, $product_id ?? 0]);
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
            'short_description' => trim($_POST['short_description'] ?? ''),
            'sku' => $sku,
            'price_pesos' => floatval($_POST['price_pesos']),
            'price_dollars' => !empty($_POST['price_dollars']) ? floatval($_POST['price_dollars']) : null,
            'is_on_sale' => isset($_POST['is_on_sale']) ? 1 : 0,
            'discount_percentage' => !empty($_POST['discount_percentage']) ? floatval($_POST['discount_percentage']) : 0.00,
            'stock_quantity' => intval($_POST['stock_quantity']),
            'category_id' => intval($_POST['category_id']),
            'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'console_id' => !empty($_POST['console_id']) ? intval($_POST['console_id']) : null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
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
        
        // Guardar géneros (relación muchos a muchos)
        if (!empty($_POST['genres'])) {
            // Eliminar géneros existentes
            $pdo->prepare("DELETE FROM product_genres WHERE product_id = ?")->execute([$product_id]);
            
            // Insertar nuevos géneros
            $genres_to_insert = is_array($_POST['genres']) ? $_POST['genres'] : [];
            foreach ($genres_to_insert as $genre_id) {
                $pdo->prepare("INSERT INTO product_genres (product_id, genre_id) VALUES (?, ?)")
                    ->execute([$product_id, intval($genre_id)]);
            }
        }
        
        // Procesar imágenes nuevas (SE AGREGAN, NO SE REEMPLAZAN)
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Obtener el orden máximo actual
            $current_max_order = 0;
            $order_stmt = $pdo->prepare("SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = ?");
            $order_stmt->execute([$product_id]);
            $current_max_order = $order_stmt->fetchColumn() ?? 0;
            
            // Verificar si ya existe una imagen principal
            $has_primary_stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
            $has_primary_stmt->execute([$product_id]);
            $has_primary = $has_primary_stmt->fetchColumn() > 0;
            
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$key];
                    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_filename = uniqid('prod_') . '.' . $file_extension;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $current_max_order++;
                        // Solo marcar como principal si es la primera imagen del producto
                        $is_primary = (!$has_primary && $key == 0) ? 1 : 0;
                        if ($is_primary) {
                            $has_primary = true; // Marcar que ya hay una principal
                        }
                        
                        $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)")
                            ->execute([$product_id, $new_filename, $is_primary, $current_max_order]);
                    }
                }
            }
        }
        
        // Actualizar imagen principal si se seleccionó una
        if (!empty($_POST['primary_image_id'])) {
            // Quitar is_primary de todas las imágenes del producto
            $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$product_id]);
            
            // Marcar la seleccionada como principal
            $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?")
                ->execute([intval($_POST['primary_image_id']), $product_id]);
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

// Función para generar slug
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
        <form method="POST" enctype="multipart/form-data" id="product-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="primary_image_id" id="primary_image_id" value="">
            
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
                                        <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                        <option value="out_of_stock" <?php echo ($product['status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Agotado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="6" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                       value="1" <?php echo (!empty($product['is_featured'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    Producto Destacado
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" <?php echo (isset($product['is_active']) ? $product['is_active'] : 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Visible en la tienda
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Imágenes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images me-2"></i>
                                Imágenes del Producto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="images" class="form-label">Agregar Imágenes</label>
                                <input type="file" class="form-control" id="images" 
                                       multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Selecciona una o más imágenes. 
                                    Se agregarán a las existentes. Formatos: JPG, PNG, WebP. Máximo 5MB por imagen.
                                </div>
                            </div>
                            
                            <!-- Vista previa de imágenes nuevas (temporal) -->
                            <div id="new-images-preview" class="row g-3 mb-3"></div>
                            
                            <!-- Botón para subir imágenes pendientes -->
                            <div id="upload-pending-section" style="display: none;" class="mb-3">
                                <button type="button" class="btn btn-success w-100" id="upload-pending-btn" onclick="uploadPendingImagesManually()">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>
                                    Subir <span id="pending-count">0</span> imagen(es) pendiente(s)
                                </button>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> Las imágenes se subirán inmediatamente al hacer clic
                                </small>
                            </div>
                            
                            <!-- Todas las imágenes del producto -->
                            <div id="all-images-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-images me-2"></i>Imágenes del Producto
                                    <span class="badge bg-primary" id="images-count"><?php echo count($product_images); ?></span>
                                </h6>
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-star text-warning me-1"></i> Selecciona la imagen de portada con el radio button
                                    <br>
                                    <i class="fas fa-arrows-alt text-info me-1"></i> Arrastra la imagen (desde cualquier parte) para reordenar
                                </p>
                                <div class="row g-3" id="images-grid">
                                    <?php foreach ($product_images as $index => $image): ?>
                                    <div class="col-md-3 image-item" data-image-id="<?php echo $image['id']; ?>" data-order="<?php echo $image['display_order'] ?? $index; ?>">
                                        <div class="card h-100 position-relative" style="cursor: move;">
                                            <!-- Badge de orden (drag handle) -->
                                            <div class="position-absolute top-0 start-0 p-2" style="z-index: 10;">
                                                <span class="badge bg-dark bg-opacity-75 drag-handle">
                                                    <i class="fas fa-grip-vertical"></i> #<?php echo $index + 1; ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Badge de imagen principal -->
                                            <?php if ($image['is_primary']): ?>
                                            <div class="position-absolute top-0 end-0 p-2" style="z-index: 10;">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-star"></i> Portada
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Imagen -->
                                            <img src="../uploads/products/<?php echo htmlspecialchars($image['image_url'] ?? $image['filename'] ?? ''); ?>" 
                                                 class="card-img-top" style="height: 180px; object-fit: cover;"
                                                 alt="Imagen del producto">
                                            
                                            <!-- Controles -->
                                            <div class="card-body p-2">
                                                <!-- Radio button para portada -->
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input primary-image-radio" 
                                                           type="radio" 
                                                           name="primary_image_radio" 
                                                           id="primary_<?php echo $image['id']; ?>"
                                                           value="<?php echo $image['id']; ?>"
                                                           <?php echo $image['is_primary'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label fw-bold text-warning" for="primary_<?php echo $image['id']; ?>">
                                                        <i class="fas fa-star"></i> Imagen de Portada
                                                    </label>
                                                </div>
                                                
                                                <!-- Botón eliminar -->
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                                                        onclick="deleteProductImage(<?php echo $image['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php if (empty($product_images)): ?>
                            <div class="alert alert-info" id="no-images-alert">
                                <i class="fas fa-info-circle"></i> Este producto aún no tiene imágenes. Agrega al menos una imagen para tu producto.
                            </div>
                            <?php endif; ?>
                            
                            <!-- Imágenes existentes -->
                            <?php if (false && !empty($product_images)): // Oculto, ahora se usa el nuevo sistema ?>
                            <div class="current-images">
                                <h6 class="mb-3">
                                    <i class="fas fa-folder-open me-2"></i>Imágenes Actuales 
                                    <span class="badge bg-secondary"><?php echo count($product_images); ?></span>
                                </h6>
                                <p class="text-muted small">
                                    <i class="fas fa-arrows-alt me-1"></i> Arrastra las imágenes para reordenarlas
                                </p>
                                <div class="row g-3" id="current-images-sortable">
                                    <?php foreach ($product_images as $image): ?>
                                    <div class="col-md-3 sortable-image-item" data-image-id="<?php echo $image['id']; ?>">
                                        <div class="card position-relative" style="cursor: move;">
                                            <div class="position-absolute top-0 start-0 p-2">
                                                <span class="badge bg-dark bg-opacity-75">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </span>
                                            </div>
                                            <?php if ($image['is_primary']): ?>
                                            <div class="position-absolute top-0 end-0 p-2">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-star"></i> Principal
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            <img src="../uploads/products/<?php echo htmlspecialchars($image['image_url'] ?? $image['filename'] ?? ''); ?>" 
                                                 class="card-img-top" style="height: 150px; object-fit: cover;"
                                                 alt="Imagen del producto">
                                            <div class="card-body p-2">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input image-primary-radio" type="radio" 
                                                           name="primary_image" value="<?php echo $image['id']; ?>"
                                                           <?php echo $image['is_primary'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small">
                                                        Marcar como principal
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                                                        onclick="deleteProductImage(<?php echo $image['id']; ?>)">
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
                                <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                       value="<?php echo htmlspecialchars($product['meta_title'] ?? ''); ?>" 
                                       maxlength="60">
                                <div class="form-text">
                                    <i class="fas fa-lightbulb"></i> Recomendado: 50-60 caracteres. 
                                    Este título aparece en los resultados de Google.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">
                                    Meta Descripción
                                    <span class="text-muted small" id="meta-desc-counter">(0/160)</span>
                                </label>
                                <textarea class="form-control" id="meta_description" name="meta_description" 
                                          rows="3" maxlength="160"><?php echo htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-lightbulb"></i> Recomendado: 150-160 caracteres. 
                                    Esta descripción aparece bajo el título en Google.
                                </div>
                            </div>
                            
                            <!-- Vista previa SERP -->
                            <div class="alert alert-info">
                                <strong><i class="fas fa-eye me-2"></i>Vista Previa en Google:</strong>
                                <div class="mt-2 p-3 bg-white rounded">
                                    <div class="text-primary fw-bold" id="serp-preview-title" style="font-size: 20px;">
                                        <?php echo htmlspecialchars($product['meta_title'] ?? $product['name'] ?? 'Título del producto'); ?>
                                    </div>
                                    <div class="text-success small" id="serp-preview-url">
                                        www.multigamer360.com › producto › <?php echo $product['slug'] ?? 'producto'; ?>
                                    </div>
                                    <div class="text-muted" id="serp-preview-desc" style="font-size: 14px;">
                                        <?php echo htmlspecialchars($product['meta_description'] ?? $product['short_description'] ?? 'Descripción del producto'); ?>
                                    </div>
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
                                <label for="price_pesos" class="form-label">
                                    Precio en Pesos (COP) *
                                    <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" 
                                       title="Precio en pesos colombianos"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price_pesos" name="price_pesos" 
                                           value="<?php echo $product['price_pesos'] ?? ''; ?>" 
                                           min="0" step="0.01" required>
                                    <span class="input-group-text">COP</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_dollars" class="form-label">
                                    Precio en Dólares (USD)
                                    <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" 
                                       title="Precio en dólares estadounidenses (opcional)"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price_dollars" name="price_dollars" 
                                           value="<?php echo $product['price_dollars'] ?? ''; ?>" 
                                           min="0" step="0.01">
                                    <span class="input-group-text">USD</span>
                                </div>
                                <div class="form-text">Opcional: Dejar vacío si no aplica</div>
                            </div>
                            
                            <hr>
                            
                            <!-- Sistema de Descuentos por Porcentaje -->
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_on_sale" name="is_on_sale" 
                                           value="1" <?php echo (!empty($product['is_on_sale'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="is_on_sale">
                                        <i class="fas fa-percentage text-danger"></i> Producto en Oferta
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="discount-section" style="display: <?php echo (!empty($product['is_on_sale'])) ? 'block' : 'none'; ?>;">
                                <label for="discount_percentage" class="form-label">
                                    Porcentaje de Descuento
                                    <span class="badge bg-danger">%</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                           value="<?php echo $product['discount_percentage'] ?? '0'; ?>" 
                                           min="0" max="100" step="0.01">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-tag"></i> Ingrese el porcentaje de descuento (0-100%)
                                </div>
                                
                                <!-- Vista Previa del Descuento -->
                                <div id="discount-preview" class="mt-3"></div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">
                                    Cantidad en Stock *
                                    <i class="fas fa-boxes text-muted"></i>
                                </label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       value="<?php echo $product['stock_quantity'] ?? '0'; ?>" 
                                       min="0" required>
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
                                <div class="input-group">
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Seleccionar categoría</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marca</label>
                                <div class="input-group">
                                    <select class="form-select" id="brand_id" name="brand_id">
                                        <option value="">Sin marca</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>" 
                                                    <?php echo ($product['brand_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($brand['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="console_id" class="form-label">Consola / Plataforma</label>
                                <div class="input-group">
                                    <select class="form-select" id="console_id" name="console_id">
                                        <option value="">Sin consola</option>
                                        <?php foreach ($consoles as $console): ?>
                                            <option value="<?php echo $console['id']; ?>" 
                                                    <?php echo ($product['console_id'] ?? '') == $console['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($console['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#addConsoleModal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    Géneros
                                    <button class="btn btn-sm btn-outline-success ms-2" type="button" 
                                            data-bs-toggle="modal" data-bs-target="#addGenreModal">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </label>
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
                                                           id="genre_<?php echo $genre['id']; ?>"
                                                           <?php echo in_array($genre['id'], $selected_genres) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="genre_<?php echo $genre['id']; ?>">
                                                        <?php echo htmlspecialchars($genre['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Puede seleccionar múltiples géneros</div>
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

<!-- MODALES PARA AGREGAR NUEVOS -->

<!-- Modal: Agregar Categoría -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-folder-plus me-2"></i>Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new_category_name" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="new_category_name" required>
                </div>
                <div class="mb-3">
                    <label for="new_category_description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="new_category_description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNewCategory()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Marca -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trademark me-2"></i>Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new_brand_name" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="new_brand_name" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNewBrand()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Consola -->
<div class="modal fade" id="addConsoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gamepad me-2"></i>Nueva Consola</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new_console_name" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="new_console_name" required>
                </div>
                <div class="mb-3">
                    <label for="new_console_manufacturer" class="form-label">Fabricante</label>
                    <input type="text" class="form-control" id="new_console_manufacturer">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNewConsole()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Género -->
<div class="modal fade" id="addGenreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list-ul me-2"></i>Nuevo Género</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new_genre_name" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="new_genre_name" required>
                </div>
                <div class="mb-3">
                    <label for="new_genre_description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="new_genre_description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNewGenre()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para drag & drop de imágenes */
.image-item {
    transition: all 0.3s ease;
}

.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
    border: 2px dashed #0d6efd;
}

.sortable-chosen {
    cursor: grabbing !important;
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(5deg);
}

.image-item .card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.image-item:hover .card {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.drag-handle {
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}
</style>

<!-- Incluir SortableJS para drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Esperar a que el DOM y Bootstrap estén completamente cargados
document.addEventListener('DOMContentLoaded', function() {

// ============================================
// GESTIÓN DE IMÁGENES
// ============================================
// SISTEMA DE IMÁGENES MEJORADO - ACUMULATIVO
// ============================================

// Array para almacenar las imágenes nuevas que se van a subir
let pendingImages = [];
let pendingImageFiles = [];

// Vista previa de imágenes nuevas
const imagesInput = document.getElementById('images');
if (imagesInput) {
    console.log('✓ Input de imágenes encontrado');
    
    imagesInput.addEventListener('change', function(e) {
        const files = Array.from(this.files);
        
        console.log('Archivos seleccionados:', files.length);
        
        if (files.length === 0) return;
        
        // Agregar nuevos archivos al array de pendientes
        files.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                // Validar tamaño (5MB máximo)
                if (file.size > 5 * 1024 * 1024) {
                    alert(`La imagen "${file.name}" excede el tamaño máximo de 5MB`);
                    return;
                }
                
                console.log('Procesando imagen:', file.name);
                pendingImageFiles.push(file);
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageData = {
                        file: file,
                        dataUrl: e.target.result,
                        name: file.name
                    };
                    pendingImages.push(imageData);
                    console.log('Imagen cargada, total pendientes:', pendingImages.length);
                    renderPendingImages();
                };
                reader.readAsDataURL(file);
            }
        });
        
        // NO limpiar el input - esto permite seguir agregando
        // this.value = '';
    });
} else {
    console.error('✗ Input de imágenes NO encontrado');
}

// Renderizar vista previa de imágenes pendientes
function renderPendingImages() {
    const preview = document.getElementById('new-images-preview');
    const uploadSection = document.getElementById('upload-pending-section');
    const pendingCount = document.getElementById('pending-count');
    
    console.log('Renderizando vista previa. Imágenes pendientes:', pendingImages.length);
    
    if (!preview) {
        console.error('✗ Contenedor new-images-preview NO encontrado');
        return;
    }
    
    preview.innerHTML = '';
    
    if (pendingImages.length === 0) {
        if (uploadSection) uploadSection.style.display = 'none';
        console.log('No hay imágenes pendientes');
        return;
    }
    
    // Mostrar botón de subida si hay producto editándose
    if (uploadSection && <?php echo $product_id ?? 0; ?> > 0) {
        uploadSection.style.display = 'block';
        pendingCount.textContent = pendingImages.length;
        console.log('✓ Botón de subida mostrado');
    }
    
    pendingImages.forEach((img, index) => {
        const col = document.createElement('div');
        col.className = 'col-md-3 pending-image-preview';
        col.innerHTML = `
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white py-1 d-flex justify-content-between align-items-center">
                    <small><i class="fas fa-cloud-upload-alt"></i> Nueva #${index + 1}</small>
                    <button type="button" class="btn btn-sm btn-close btn-close-white" 
                            onclick="removePendingImage(${index})" aria-label="Cancelar"></button>
                </div>
                <img src="${img.dataUrl}" class="card-img-top" 
                     style="height: 150px; object-fit: cover;" alt="Vista previa">
                <div class="card-body p-2">
                    <small class="text-muted d-block text-truncate" title="${img.name}">
                        <i class="fas fa-file-image me-1"></i>${img.name}
                    </small>
                </div>
            </div>
        `;
        preview.appendChild(col);
    });
    
    console.log('✓ Vista previa renderizada con', pendingImages.length, 'imágenes');
}

// Eliminar imagen pendiente (antes de subir)
function removePendingImage(index) {
    pendingImages.splice(index, 1);
    pendingImageFiles.splice(index, 1);
    renderPendingImages();
}

// Subir imágenes pendientes mediante AJAX
function uploadPendingImages() {
    if (pendingImageFiles.length === 0) {
        return Promise.resolve();
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_images');
    formData.append('product_id', <?php echo $product_id ?? 0; ?>);
    
    pendingImageFiles.forEach((file, index) => {
        formData.append('images[]', file);
    });
    
    return fetch('api/upload_product_images.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpiar arrays de pendientes
            pendingImages = [];
            pendingImageFiles = [];
            renderPendingImages();
            
            // Recargar la página para mostrar las nuevas imágenes
            location.reload();
        } else {
            throw new Error(data.message || 'Error al subir imágenes');
        }
    });
}

// Subir imágenes manualmente (botón específico)
function uploadPendingImagesManually() {
    if (pendingImageFiles.length === 0) {
        alert('No hay imágenes pendientes para subir');
        return;
    }
    
    const uploadBtn = document.getElementById('upload-pending-btn');
    const originalHTML = uploadBtn.innerHTML;
    
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';
    
    uploadPendingImages()
        .catch(error => {
            alert('Error: ' + error.message);
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = originalHTML;
        });
}

// Configurar Sortable para reordenar imágenes
const imagesGrid = document.getElementById('images-grid');
if (imagesGrid) {
    new Sortable(imagesGrid, {
        animation: 200,
        easing: "cubic-bezier(1, 0, 0, 1)",
        draggable: '.image-item',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            updateImageOrder();
        }
    });
    
    console.log('Sortable inicializado correctamente');
}

// Actualizar orden de imágenes en el servidor
function updateImageOrder() {
    const items = document.querySelectorAll('.image-item');
    const imageOrder = [];
    
    items.forEach((item, index) => {
        imageOrder.push({
            id: item.dataset.imageId,
            order: index + 1
        });
        
        // Actualizar el badge de orden visualmente
        const badge = item.querySelector('.drag-handle');
        if (badge) {
            badge.innerHTML = `<i class="fas fa-grip-vertical"></i> #${index + 1}`;
        }
    });
    
    console.log('Nuevo orden:', imageOrder);
    
    // Enviar al servidor
    fetch('api/update_image_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: <?php echo $product_id ?? 0; ?>,
            order: imageOrder
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✓ Orden actualizado en servidor');
        } else {
            console.error('Error al actualizar orden:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Manejar radio buttons de imagen principal
const primaryRadios = document.querySelectorAll('.primary-image-radio');
primaryRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            const imageId = this.value;
            console.log('Cambiando imagen principal a:', imageId);
            setAsPrimary(imageId);
        }
    });
});

// Marcar imagen como portada/principal
function setAsPrimary(imageId) {
    fetch('api/set_primary_image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: <?php echo $product_id ?? 0; ?>,
            image_id: imageId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar para actualizar la UI
            location.reload();
        } else {
            alert(data.message || 'Error al marcar imagen como portada');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al marcar imagen como portada');
    });
}

// Marcar imagen como principal (usando radio buttons - código legacy)
const imagePrimaryRadios = document.querySelectorAll('.image-primary-radio');
imagePrimaryRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            const imageId = this.value;
            // Actualizar el campo oculto del formulario
            document.getElementById('primary_image_id').value = imageId;
            updatePrimaryImage(imageId);
        }
    });
});

function updatePrimaryImage(imageId) {
    fetch('api/update_primary_image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            product_id: <?php echo $product_id ?? 0; ?>,
            image_id: imageId 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Imagen principal actualizada');
            // Actualizar badges visuales
            document.querySelectorAll('.sortable-image-item .badge.bg-success').forEach(b => b.remove());
            const selectedCard = document.querySelector(`[data-image-id="${imageId}"]`);
            if (selectedCard) {
                const badge = document.createElement('div');
                badge.className = 'position-absolute top-0 end-0 p-2';
                badge.innerHTML = '<span class="badge bg-success"><i class="fas fa-star"></i> Principal</span>';
                selectedCard.querySelector('.card').appendChild(badge);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Eliminar imagen
function deleteProductImage(imageId) {
    if (!confirm('¿Está seguro de eliminar esta imagen? Esta acción no se puede deshacer.')) return;
    
    fetch('api/delete_product_image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ image_id: imageId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Eliminar visualmente
            const imageItem = document.querySelector(`[data-image-id="${imageId}"]`);
            if (imageItem) {
                imageItem.remove();
            }
            
            // Actualizar contador
            const remaining = document.querySelectorAll('.image-item').length;
            const counter = document.getElementById('images-count');
            if (counter) {
                counter.textContent = remaining;
            }
            
            // Actualizar números de orden
            document.querySelectorAll('.image-item').forEach((item, index) => {
                const badge = item.querySelector('.badge');
                if (badge) {
                    badge.innerHTML = `<i class="fas fa-grip-vertical"></i> #${index + 1}`;
                }
            });
            
            // Mostrar alerta si ya no hay imágenes
            if (remaining === 0) {
                const grid = document.getElementById('images-grid');
                grid.innerHTML = '<div class="col-12"><div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay imágenes. Agrega al menos una imagen.</div></div>';
            }
            
            alert('Imagen eliminada correctamente');
        } else {
            alert('Error al eliminar imagen: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al eliminar imagen');
    });
}

// ============================================
// SEO AUTO-GENERACIÓN
// ============================================

// Auto-generar campos SEO
const autoGenerateSeoBtn = document.getElementById('auto-generate-seo');
if (autoGenerateSeoBtn) {
    autoGenerateSeoBtn.addEventListener('click', function() {
    const name = document.getElementById('name').value;
    const description = document.getElementById('description').value;
    
    if (!name) {
        alert('Por favor ingrese el nombre del producto primero');
        return;
    }
    
    // Generar meta título
    let metaTitle = name;
    if (metaTitle.length > 60) {
        metaTitle = metaTitle.substring(0, 57) + '...';
    }
    metaTitle += ' | MultiGamer360';
    document.getElementById('meta_title').value = metaTitle;
    
    // Generar meta descripción
    let metaDesc = description || name;
    metaDesc = metaDesc.replace(/<[^>]*>/g, '').trim(); // Quitar HTML
    if (metaDesc.length > 160) {
        metaDesc = metaDesc.substring(0, 157) + '...';
    }
    document.getElementById('meta_description').value = metaDesc;
    
    updateSEOCounters();
    updateSERPPreview();
    
    alert('Campos SEO generados automáticamente');
});
}

// Contadores de caracteres SEO
function updateSEOCounters() {
    const metaTitleInput = document.getElementById('meta_title');
    const metaDescInput = document.getElementById('meta_description');
    const titleCounter = document.getElementById('meta-title-counter');
    const descCounter = document.getElementById('meta-desc-counter');
    
    // Verificar que los elementos existen
    if (!metaTitleInput || !metaDescInput) {
        console.warn('Campos SEO no encontrados');
        return;
    }
    
    const metaTitle = metaTitleInput.value || '';
    const metaDesc = metaDescInput.value || '';
    
    if (titleCounter) {
        titleCounter.textContent = `(${metaTitle.length}/60)`;
        titleCounter.className = metaTitle.length > 60 ? 'text-danger small' : 'text-muted small';
    }
    
    if (descCounter) {
        descCounter.textContent = `(${metaDesc.length}/160)`;
        descCounter.className = metaDesc.length > 160 ? 'text-danger small' : 'text-muted small';
    }
}

// Actualizar vista previa SERP
function updateSERPPreview() {
    const titleInput = document.getElementById('meta_title');
    const descInput = document.getElementById('meta_description');
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    
    // Verificar que los elementos existen antes de acceder a sus valores
    const title = (titleInput ? titleInput.value : '') || 
                  (nameInput ? nameInput.value : '') || 
                  'Título del producto';
    
    const desc = (descInput ? descInput.value : '') || 
                 (descriptionInput ? descriptionInput.value.substring(0, 160) : '') || 
                 'Descripción del producto';
    
    const previewTitle = document.getElementById('serp-preview-title');
    const previewDesc = document.getElementById('serp-preview-desc');
    
    if (previewTitle) previewTitle.textContent = title;
    if (previewDesc) previewDesc.textContent = desc;
}

const metaTitleInput = document.getElementById('meta_title');
const metaDescInput = document.getElementById('meta_description');
const nameInput = document.getElementById('name');
const descriptionInput = document.getElementById('description');

if (metaTitleInput) {
    metaTitleInput.addEventListener('input', function() {
        updateSEOCounters();
        updateSERPPreview();
    });
}

if (metaDescInput) {
    metaDescInput.addEventListener('input', function() {
        updateSEOCounters();
        updateSERPPreview();
    });
}

if (nameInput) {
    nameInput.addEventListener('input', updateSERPPreview);
}

if (descriptionInput) {
    descriptionInput.addEventListener('input', updateSERPPreview);
}

// Inicializar contadores y vista previa
updateSEOCounters();
updateSERPPreview();

// ============================================
// PRECIOS Y DESCUENTOS POR PORCENTAJE
// ============================================

// Toggle del switch "En Oferta"
const isOnSaleSwitch = document.getElementById('is_on_sale');
const discountSection = document.getElementById('discount-section');

if (isOnSaleSwitch) {
    isOnSaleSwitch.addEventListener('change', function() {
        if (this.checked) {
            discountSection.style.display = 'block';
        } else {
            discountSection.style.display = 'none';
            document.getElementById('discount_percentage').value = '0';
            updateDiscountPreview();
        }
    });
}

// Calcular precio con descuento por porcentaje
function updateDiscountPreview() {
    const pricePesos = parseFloat(document.getElementById('price_pesos').value) || 0;
    const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
    const preview = document.getElementById('discount-preview');
    const isOnSale = document.getElementById('is_on_sale').checked;
    
    if (!isOnSale || discountPercentage === 0 || pricePesos === 0) {
        preview.innerHTML = '';
        return;
    }
    
    // Validar que el porcentaje esté entre 0 y 100
    if (discountPercentage < 0 || discountPercentage > 100) {
        preview.innerHTML = `
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle"></i> El porcentaje debe estar entre 0 y 100
            </div>
        `;
        return;
    }
    
    // Calcular precio final
    const discountAmount = (pricePesos * discountPercentage / 100);
    const finalPrice = pricePesos - discountAmount;
    
    // Formatear números con separador de miles
    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    preview.innerHTML = `
        <div class="alert alert-success mb-0">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-percentage"></i> Descuento: ${discountPercentage}%</strong><br>
                    <small class="text-muted">Ahorro: ${formatter.format(discountAmount)}</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="text-muted small">
                        <del>${formatter.format(pricePesos)}</del>
                    </div>
                    <div class="text-success fs-5 fw-bold">
                        ${formatter.format(finalPrice)}
                    </div>
                </div>
            </div>
        </div>
    `;
}

const pesosInput = document.getElementById('price_pesos');
const discountPercentageInput = document.getElementById('discount_percentage');

if (pesosInput) {
    pesosInput.addEventListener('input', updateDiscountPreview);
}

if (discountPercentageInput) {
    discountPercentageInput.addEventListener('input', updateDiscountPreview);
}

// Alerta de stock bajo
function updateStockAlert() {
    const stock = parseInt(document.getElementById('stock_quantity').value) || 0;
    const alert = document.getElementById('stock-alert');
    
    if (stock === 0) {
        alert.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Sin stock</span>';
    } else if (stock < 5) {
        alert.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Stock bajo</span>';
    } else {
        alert.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Stock disponible</span>';
    }
}

const stockInput = document.getElementById('stock_quantity');
if (stockInput) {
    stockInput.addEventListener('input', updateStockAlert);
}

// Inicializar
updateDiscountPreview();
updateStockAlert();

// ============================================
// GENERAR SKU AUTOMÁTICO
// ============================================

const nameInputForSku = document.getElementById('name');
if (nameInputForSku) {
    nameInputForSku.addEventListener('blur', function() {
    const skuField = document.getElementById('sku');
    if (!skuField.value && this.value) {
        const sku = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 8);
        skuField.value = sku + '-' + Math.floor(Math.random() * 9000 + 1000);
    }
});
}

// ============================================
// VALIDACIÓN DEL FORMULARIO
// ============================================

const productForm = document.getElementById('product-form');
if (productForm) {
    productForm.addEventListener('submit', function(e) {
    const pricePesos = parseFloat(document.getElementById('price_pesos').value) || 0;
    const isOnSale = document.getElementById('is_on_sale').checked;
    const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
    
    // Validar porcentaje de descuento si está en oferta
    if (isOnSale && (discountPercentage < 0 || discountPercentage > 100)) {
        e.preventDefault();
        alert('El porcentaje de descuento debe estar entre 0 y 100');
        document.getElementById('discount_percentage').focus();
        return false;
    }
    
    // Validar que tenga al menos una categoría
    if (!document.getElementById('category_id').value) {
        e.preventDefault();
        alert('Debe seleccionar una categoría');
        document.getElementById('category_id').focus();
        return false;
    }
    
    // Si hay imágenes pendientes, subirlas primero mediante AJAX
    if (pendingImageFiles.length > 0 && <?php echo $product_id ?? 0; ?> > 0) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo imágenes...';
        
        uploadPendingImages()
            .then(() => {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando producto...';
                // Continuar con el envío del formulario
                productForm.submit();
            })
            .catch(error => {
                alert('Error al subir imágenes: ' + error.message);
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        
        return false;
    }
});
}

// ============================================
// FUNCIONES PARA AGREGAR NUEVOS ELEMENTOS
// ============================================

function saveNewCategory() {
    const name = document.getElementById('new_category_name').value.trim();
    const description = document.getElementById('new_category_description').value.trim();
    
    if (!name) {
        alert('El nombre es requerido');
        return;
    }
    
    fetch('api/add_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Agregar opción al select
            const option = new Option(data.category.name, data.category.id, true, true);
            document.getElementById('category_id').add(option);
            
            // Cerrar modal
            const modal = document.getElementById('addCategoryModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
            
            // Limpiar campos
            document.getElementById('new_category_name').value = '';
            document.getElementById('new_category_description').value = '';
            
            alert('Categoría creada correctamente');
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear la categoría'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function saveNewBrand() {
    const name = document.getElementById('new_brand_name').value.trim();
    
    if (!name) {
        alert('El nombre es requerido');
        return;
    }
    
    fetch('api/add_brand.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const option = new Option(data.brand.name, data.brand.id, true, true);
            document.getElementById('brand_id').add(option);
            
            const modal = document.getElementById('addBrandModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
            
            document.getElementById('new_brand_name').value = '';
            alert('Marca creada correctamente');
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear la marca'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function saveNewConsole() {
    const name = document.getElementById('new_console_name').value.trim();
    const manufacturer = document.getElementById('new_console_manufacturer').value.trim();
    
    if (!name) {
        alert('El nombre es requerido');
        return;
    }
    
    fetch('api/add_console.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, manufacturer })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const option = new Option(data.console.name, data.console.id, true, true);
            document.getElementById('console_id').add(option);
            
            const modal = document.getElementById('addConsoleModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
            
            document.getElementById('new_console_name').value = '';
            document.getElementById('new_console_manufacturer').value = '';
            alert('Consola creada correctamente');
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear la consola'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function saveNewGenre() {
    const name = document.getElementById('new_genre_name').value.trim();
    const description = document.getElementById('new_genre_description').value.trim();
    
    if (!name) {
        alert('El nombre es requerido');
        return;
    }
    
    fetch('api/add_genre.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar página para mostrar el nuevo género en los checkboxes
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear el género'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Inicializar tooltips de Bootstrap
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

}); // Fin de DOMContentLoaded
</script>

<?php require_once 'inc/footer.php'; ?>
