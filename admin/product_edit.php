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
            'offer_price' => !empty($_POST['offer_price']) ? floatval($_POST['offer_price']) : null,
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
        
        // Procesar imágenes nuevas
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $current_max_order = 0;
            if ($is_edit) {
                $order_stmt = $pdo->prepare("SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = ?");
                $order_stmt->execute([$product_id]);
                $current_max_order = $order_stmt->fetchColumn() ?? 0;
            }
            
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$key];
                    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_filename = uniqid('prod_') . '.' . $file_extension;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $current_max_order++;
                        $is_primary = ($key == 0 && empty($product_images)) ? 1 : 0;
                        
                        $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)")
                            ->execute([$product_id, $new_filename, $is_primary, $current_max_order]);
                    }
                }
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
                                <label for="images" class="form-label">Subir Nuevas Imágenes</label>
                                <input type="file" class="form-control" id="images" name="images[]" 
                                       multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Puede seleccionar múltiples imágenes. 
                                    Formatos: JPG, PNG, WebP. Máximo 5MB por imagen.
                                </div>
                            </div>
                            
                            <!-- Vista previa de imágenes nuevas -->
                            <div id="image-preview" class="row g-3 mb-3"></div>
                            
                            <!-- Imágenes existentes -->
                            <?php if (!empty($product_images)): ?>
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
                            
                            <div class="mb-3">
                                <label for="offer_price" class="form-label">
                                    Precio de Oferta (COP)
                                    <span class="badge bg-danger">OFERTA</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="offer_price" name="offer_price" 
                                           value="<?php echo $product['offer_price'] ?? ''; ?>" 
                                           min="0" step="0.01">
                                    <span class="input-group-text">COP</span>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-tag"></i> Dejar vacío si no hay oferta. 
                                    Debe ser menor al precio regular.
                                </div>
                                <div id="discount-preview" class="mt-2"></div>
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

<!-- Incluir SortableJS para drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// ============================================
// GESTIÓN DE IMÁGENES
// ============================================

// Vista previa de imágenes nuevas
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (this.files.length === 0) return;
    
    Array.from(this.files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            // Validar tamaño (5MB máximo)
            if (file.size > 5 * 1024 * 1024) {
                alert(`La imagen "${file.name}" excede el tamaño máximo de 5MB`);
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3';
                col.innerHTML = `
                    <div class="card border-success">
                        <div class="card-header bg-success text-white py-1">
                            <small><i class="fas fa-file-upload"></i> Nueva</small>
                        </div>
                        <img src="${e.target.result}" class="card-img-top" 
                             style="height: 150px; object-fit: cover;" alt="Preview">
                        <div class="card-body p-2">
                            <small class="text-muted d-block mb-1">${file.name}</small>
                        </div>
                    </div>
                `;
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        }
    });
});

// Configurar Sortable para reordenar imágenes existentes
<?php if (!empty($product_images)): ?>
const sortableImages = document.getElementById('current-images-sortable');
if (sortableImages) {
    new Sortable(sortableImages, {
        animation: 150,
        handle: '.sortable-image-item',
        ghostClass: 'bg-light',
        onEnd: function(evt) {
            updateImageOrder();
        }
    });
}

// Actualizar orden de imágenes en el servidor
function updateImageOrder() {
    const items = document.querySelectorAll('.sortable-image-item');
    const imageOrder = [];
    
    items.forEach((item, index) => {
        imageOrder.push({
            id: item.dataset.imageId,
            order: index + 1
        });
    });
    
    fetch('api/update_image_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ images: imageOrder })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Orden actualizado');
        }
    })
    .catch(error => console.error('Error:', error));
}
<?php endif; ?>

// Marcar imagen como principal
document.querySelectorAll('.image-primary-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            const imageId = this.value;
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
    if (!confirm('¿Está seguro de eliminar esta imagen?')) return;
    
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
            document.querySelector(`[data-image-id="${imageId}"]`).remove();
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
document.getElementById('auto-generate-seo').addEventListener('click', function() {
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

// Contadores de caracteres SEO
function updateSEOCounters() {
    const metaTitle = document.getElementById('meta_title').value;
    const metaDesc = document.getElementById('meta_description').value;
    
    const titleCounter = document.getElementById('meta-title-counter');
    const descCounter = document.getElementById('meta-desc-counter');
    
    titleCounter.textContent = `(${metaTitle.length}/60)`;
    titleCounter.className = metaTitle.length > 60 ? 'text-danger small' : 'text-muted small';
    
    descCounter.textContent = `(${metaDesc.length}/160)`;
    descCounter.className = metaDesc.length > 160 ? 'text-danger small' : 'text-muted small';
}

// Actualizar vista previa SERP
function updateSERPPreview() {
    const title = document.getElementById('meta_title').value || document.getElementById('name').value || 'Título del producto';
    const desc = document.getElementById('meta_description').value || document.getElementById('short_description').value || 'Descripción del producto';
    
    document.getElementById('serp-preview-title').textContent = title;
    document.getElementById('serp-preview-desc').textContent = desc;
}

document.getElementById('meta_title').addEventListener('input', function() {
    updateSEOCounters();
    updateSERPPreview();
});

document.getElementById('meta_description').addEventListener('input', function() {
    updateSEOCounters();
    updateSERPPreview();
});

document.getElementById('name').addEventListener('input', updateSERPPreview);
document.getElementById('short_description').addEventListener('input', updateSERPPreview);

// Inicializar contadores
updateSEOCounters();

// ============================================
// PRECIOS Y DESCUENTOS
// ============================================

// Calcular descuento
function updateDiscountPreview() {
    const pricePesos = parseFloat(document.getElementById('price_pesos').value) || 0;
    const offerPrice = parseFloat(document.getElementById('offer_price').value) || 0;
    const preview = document.getElementById('discount-preview');
    
    if (offerPrice > 0 && offerPrice < pricePesos) {
        const discount = ((pricePesos - offerPrice) / pricePesos * 100).toFixed(0);
        const savings = (pricePesos - offerPrice).toFixed(2);
        preview.innerHTML = `
            <div class="alert alert-success mb-0">
                <strong><i class="fas fa-percent"></i> Descuento: ${discount}%</strong><br>
                <small>Ahorro: $${savings} COP</small>
            </div>
        `;
    } else if (offerPrice >= pricePesos) {
        preview.innerHTML = `
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle"></i> El precio de oferta debe ser menor al precio regular
            </div>
        `;
    } else {
        preview.innerHTML = '';
    }
}

document.getElementById('price_pesos').addEventListener('input', updateDiscountPreview);
document.getElementById('offer_price').addEventListener('input', updateDiscountPreview);

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

document.getElementById('stock_quantity').addEventListener('input', updateStockAlert);

// Inicializar
updateDiscountPreview();
updateStockAlert();

// ============================================
// GENERAR SKU AUTOMÁTICO
// ============================================

document.getElementById('name').addEventListener('blur', function() {
    const skuField = document.getElementById('sku');
    if (!skuField.value && this.value) {
        const sku = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 8);
        skuField.value = sku + '-' + Math.floor(Math.random() * 9000 + 1000);
    }
});

// ============================================
// VALIDACIÓN DEL FORMULARIO
// ============================================

document.getElementById('product-form').addEventListener('submit', function(e) {
    const pricePesos = parseFloat(document.getElementById('price_pesos').value) || 0;
    const offerPrice = parseFloat(document.getElementById('offer_price').value) || 0;
    
    if (offerPrice > 0 && offerPrice >= pricePesos) {
        e.preventDefault();
        alert('El precio de oferta debe ser menor al precio regular');
        document.getElementById('offer_price').focus();
        return false;
    }
    
    // Validar que tenga al menos una categoría
    if (!document.getElementById('category_id').value) {
        e.preventDefault();
        alert('Debe seleccionar una categoría');
        document.getElementById('category_id').focus();
        return false;
    }
});

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
            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
            
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
            bootstrap.Modal.getInstance(document.getElementById('addBrandModal')).hide();
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
            bootstrap.Modal.getInstance(document.getElementById('addConsoleModal')).hide();
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
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<?php require_once 'inc/footer.php'; ?>
