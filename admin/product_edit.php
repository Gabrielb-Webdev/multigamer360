<?php
/**
 * EDITAR PRODUCTO EXISTENTE
 * Este archivo es SOLO para editar productos existentes (requiere ID)
 * Para crear nuevos productos, usar product_create.php
 */

// Verificar que se proporcionó un ID
$product_id = $_GET['id'] ?? null;

if (empty($product_id)) {
    $_SESSION['error'] = 'ID de producto no proporcionado';
    header('Location: products.php');
    exit;
}

$is_edit = true;
$page_title = 'Editar Producto';

require_once 'inc/header.php';

// Verificar permisos de edición
if (!hasPermission('products', 'update')) {
    $_SESSION['error'] = 'No tiene permisos para editar productos';
    header('Location: products.php');
    exit;
}

// Cargar producto existente
$product = null;
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
    
    // Géneros seleccionados (siempre cargamos para edición)
    $selected_genres = [];
    $selected_genres_stmt = $pdo->prepare("SELECT genre_id FROM product_genres WHERE product_id = ?");
    $selected_genres_stmt->execute([$product_id]);
    $selected_genres = $selected_genres_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Imágenes del producto (siempre cargamos para edición)
    $product_images = [];
    $images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC");
    $images_stmt->execute([$product_id]);
    $product_images = $images_stmt->fetchAll();
    
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
        
        // Verificar SKU único (excluyendo el producto actual)
        $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $sku_check->execute([$sku, $product_id]);
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
            'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'console_id' => !empty($_POST['console_id']) ? intval($_POST['console_id']) : null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // ACTUALIZAR producto existente
        $set_clauses = [];
        foreach ($product_data as $key => $value) {
            $set_clauses[] = "$key = :$key";
        }
        
        $sql = "UPDATE products SET " . implode(', ', $set_clauses) . " WHERE id = :id";
        $product_data['id'] = $product_id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($product_data);
        
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
        
        $_SESSION['success'] = 'Producto actualizado correctamente';
        header('Location: product_edit.php?id=' . $product_id);
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
        <div class="alert alert-primary">
            <i class="fas fa-edit me-2"></i>
            <strong>Editando:</strong> <?php echo htmlspecialchars($product['name']); ?>
        </div>
        
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
                            <div class="alert alert-warning">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Consejo:</strong> Puede agregar, eliminar o reorganizar las imágenes. La primera imagen será la portada del producto.
                            </div>
                            
                            <div class="mb-3">
                                <label for="images" class="form-label">Agregar Imágenes</label>
                                <input type="file" class="form-control" id="images" name="images[]"
                                       multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Formatos: JPG, PNG, WebP. Máximo 5MB por imagen.
                                </div>
                            </div>
                            
                            <div class="alert alert-info" id="drag-drop-info" style="display: none;">
                                <i class="fas fa-hand-rock"></i> <strong>Arrastra las imágenes</strong> para cambiar el orden. La primera imagen será la portada.
                            </div>
                            
                            <!-- Vista previa de imágenes existentes -->
                            <?php if (!empty($product_images)): ?>
                            <h6 class="mb-3">
                                <i class="fas fa-images me-2"></i>Imágenes Actuales
                                <span class="badge bg-primary"><?php echo count($product_images); ?></span>
                            </h6>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-arrows-alt-v text-info me-1"></i> Usa las flechas ↑↓ para cambiar el orden. La primera imagen es la portada.
                            </p>
                            <div class="row g-3 mb-3" id="images-grid">
                                <?php foreach ($product_images as $index => $image): ?>
                                <div class="col-md-3 image-item" data-image-id="<?php echo $image['id']; ?>">
                                    <div class="card h-100 position-relative">
                                        <!-- Badge de orden -->
                                        <div class="position-absolute top-0 start-0 p-2" style="z-index: 10;">
                                            <span class="badge bg-dark bg-opacity-75">#<?php echo $index + 1; ?></span>
                                        </div>
                                        
                                        <?php if ($index === 0): ?>
                                        <div class="position-absolute top-0 end-0 p-2" style="z-index: 10;">
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-star"></i> Portada
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <img src="../uploads/products/<?php echo htmlspecialchars($image['image_url'] ?? $image['filename'] ?? ''); ?>" 
                                             class="card-img-top" style="height: 180px; object-fit: cover;"
                                             alt="Imagen del producto">
                                        
                                        <div class="card-body p-2">
                                            <!-- Botones de reordenamiento -->
                                            <div class="btn-group w-100 mb-2" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="moveImage(<?php echo $image['id']; ?>, 'up')"
                                                        <?php echo $index === 0 ? 'disabled' : ''; ?>
                                                        title="Mover arriba">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="moveImage(<?php echo $image['id']; ?>, 'down')"
                                                        <?php echo $index === count($product_images) - 1 ? 'disabled' : ''; ?>
                                                        title="Mover abajo">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
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
                            <?php else: ?>
                            <div class="alert alert-info mb-3" id="no-images-alert">
                                <i class="fas fa-info-circle"></i> Este producto aún no tiene imágenes. Agrega al menos una imagen para tu producto.
                            </div>
                            <?php endif; ?>
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
                                    Actualizar Producto
                                </button>
                                
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver a Productos
                                </a>
                                
                                <a href="../product.php?id=<?php echo $product_id; ?>" 
                                   class="btn btn-outline-info" target="_blank">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver en Sitio
                                </a>
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
/**
 * ESTILOS PARA SISTEMA DE IMÁGENES
 * Versión: 2.2.0 - Simplificado (sin drag & drop)
 * Última actualización: 2025-01-10
 */

/* Cards de imágenes */
.image-item {
    transition: all 0.3s ease;
}

.image-item .card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.image-item:hover .card {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Botones de reordenamiento */
.image-item .btn-group button {
    flex: 1;
}

.image-item .btn-group button:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* Vista previa de imágenes nuevas */
.pending-image-preview {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<!-- NO necesitamos SortableJS - Sistema simplificado con botones -->

<script>
/**
 * SISTEMA DE IMÁGENES SIMPLIFICADO
 * Versión: 3.0.0
 * Solo funcionalidad básica: eliminar imágenes
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sistema de imágenes simplificado cargado');

// ============================================
// ELIMINAR IMAGEN
// ============================================

window.deleteProductImage = function(imageId) {
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
            // Recargar la página para reflejar los cambios
            location.reload();
        } else {
            alert('Error al eliminar imagen: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al eliminar imagen');
    });
};

// ============================================
// REORDENAR IMÁGENES CON FLECHAS
// ============================================

window.moveImage = function(imageId, direction) {
    const container = document.getElementById('images-grid');
    const items = Array.from(container.querySelectorAll('.image-item'));
    const currentItem = items.find(item => item.dataset.imageId == imageId);
    
    if (!currentItem) {
        console.error('Imagen no encontrada');
        return;
    }
    
    const currentIndex = items.indexOf(currentItem);
    let newIndex;
    
    if (direction === 'up' && currentIndex > 0) {
        newIndex = currentIndex - 1;
    } else if (direction === 'down' && currentIndex < items.length - 1) {
        newIndex = currentIndex + 1;
    } else {
        return; // No se puede mover
    }
    
    console.log(`🔄 Moviendo imagen ${currentIndex + 1} → ${newIndex + 1}`);
    
    // Intercambiar elementos en el DOM
    if (direction === 'up') {
        container.insertBefore(currentItem, items[newIndex]);
    } else {
        container.insertBefore(currentItem, items[newIndex].nextSibling);
    }
    
    // Actualizar badges y botones
    updateImageOrderUI();
    
    // Guardar nuevo orden en servidor
    saveImageOrder();
};

function updateImageOrderUI() {
    const items = document.querySelectorAll('.image-item');
    items.forEach((item, index) => {
        // Actualizar badge de número
        const badge = item.querySelector('.badge.bg-dark');
        if (badge) {
            badge.textContent = `#${index + 1}`;
        }
        
        // Actualizar badge de portada
        const portadaBadge = item.querySelector('.badge.bg-warning');
        if (index === 0) {
            if (!portadaBadge) {
                const newBadge = document.createElement('div');
                newBadge.className = 'position-absolute top-0 end-0 p-2';
                newBadge.style.zIndex = '10';
                newBadge.innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> Portada</span>';
                item.querySelector('.card').appendChild(newBadge);
            }
        } else {
            if (portadaBadge) {
                portadaBadge.parentElement.remove();
            }
        }
        
        // Actualizar botones de flecha
        const upBtn = item.querySelector('button[title="Mover arriba"]');
        const downBtn = item.querySelector('button[title="Mover abajo"]');
        
        if (upBtn) upBtn.disabled = (index === 0);
        if (downBtn) downBtn.disabled = (index === items.length - 1);
    });
    
    console.log('✓ UI actualizada');
}

function saveImageOrder() {
    const items = document.querySelectorAll('.image-item');
    const imageOrder = [];
    
    items.forEach((item, index) => {
        imageOrder.push({
            id: item.dataset.imageId,
            order: index + 1
        });
    });
    
    console.log('💾 Guardando orden:', imageOrder);
    
    fetch('api/update_image_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: <?php echo $product_id; ?>,
            order: imageOrder
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Orden guardado');
        } else {
            console.error('❌ Error al guardar orden:', data.message);
        }
    })
    .catch(error => console.error('❌ Error de red:', error));
}

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
    
    // Si hay imágenes pendientes en modo edición, subirlas primero
    if (pendingImageFiles.length > 0) {
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
