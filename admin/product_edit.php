<?php
/**
 * EDITAR PRODUCTO EXISTENTE
 * Este archivo es SOLO para editar productos existentes (requiere ID)
 * Para crear nuevos productos, usar product_create.php
 * 
 * Version: 4.1.1 - Formato de precios en tiempo real CRÍTICO FIX
 * Fecha: 06 Feb 2026
 * Cambios:
 *  - CRÍTICO: Arreglado formato de precios en tiempo real (DOMContentLoaded)
 *  - Agregado: Campos separados discount_percentage_ars y discount_percentage_usd
 *  - Mejora: Interfaz mejorada para descuentos específicos por moneda
 *  - Actualizado: Formulario de edición para mostrar descuentos por moneda
 *  - Mejora: Cálculo automático de posición del cursor durante formato
 */

// Incluir autenticación PRIMERO (sin HTML, pero con sesión y DB)
require_once 'inc/auth.php';

// Verificar que se proporcionó un ID
$product_id = $_GET['id'] ?? null;

if (empty($product_id)) {
    $_SESSION['error'] = 'ID de producto no proporcionado';
    header('Location: products.php');
    exit;
}

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
        
        // Generar SKU si no se proporciona (incluye verificación de unicidad)
        $sku = !empty($_POST['sku']) ? $_POST['sku'] : generateSKU($_POST['name'], $pdo, $product_id);
        
        // Si el usuario proporcionó un SKU manualmente, verificar que sea único
        if (!empty($_POST['sku'])) {
            $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $sku_check->execute([$sku, $product_id]);
            if ($sku_check->fetch()) {
                throw new Exception('El SKU ya existe');
            }
        }
        
        // Generar slug
        $slug = generateSlug($_POST['name']);
        
        $pdo->beginTransaction();
        
        // Datos del producto (sin decimales innecesarios)
        $product_data = [
            'name' => trim($_POST['name']),
            'slug' => $slug,
            'description' => trim($_POST['description']),
            'sku' => $sku,
            'price_pesos' => intval($_POST['price_pesos']), // Sin decimales
            'price_dollars' => !empty($_POST['price_dollars']) ? intval($_POST['price_dollars']) : null, // Sin decimales
            'is_on_sale' => isset($_POST['is_on_sale']) ? 1 : 0,
            'discount_percentage' => !empty($_POST['discount_percentage']) ? intval($_POST['discount_percentage']) : 0, // Sin decimales
            'discount_percentage_ars' => !empty($_POST['discount_percentage_ars']) ? intval($_POST['discount_percentage_ars']) : 0, // Sin decimales
            'discount_percentage_usd' => !empty($_POST['discount_percentage_usd']) ? intval($_POST['discount_percentage_usd']) : 0, // Sin decimales
            'stock_quantity' => intval($_POST['stock_quantity']),
            'category_id' => intval($_POST['category_id']),
            'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'console_id' => !empty($_POST['console_id']) ? intval($_POST['console_id']) : null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_new' => isset($_POST['is_new']) ? 1 : 0,
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
        
        // AUTO-LIMPIEZA: Mantener solo los 10 productos destacados más recientes
        if (isset($_POST['is_featured']) && $_POST['is_featured']) {
            // Obtener IDs de productos destacados (ordenados por más recientes)
            $featured_stmt = $pdo->query("
                SELECT id FROM products 
                WHERE is_featured = 1 AND is_active = 1
                ORDER BY updated_at DESC, created_at DESC
            ");
            $featured_ids = $featured_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Si hay más de 10, desmarcar los más viejos
            if (count($featured_ids) > 10) {
                $ids_to_keep = array_slice($featured_ids, 0, 10);
                $ids_to_remove = array_diff($featured_ids, $ids_to_keep);
                
                if (!empty($ids_to_remove)) {
                    $placeholders = str_repeat('?,', count($ids_to_remove) - 1) . '?';
                    $pdo->prepare("UPDATE products SET is_featured = 0 WHERE id IN ($placeholders)")
                        ->execute($ids_to_remove);
                }
            }
        }
        
        // AUTO-LIMPIEZA: Mantener solo las 10 novedades más recientes
        if (isset($_POST['is_new']) && $_POST['is_new']) {
            // Obtener IDs de novedades (ordenados por más recientes)
            $new_stmt = $pdo->query("
                SELECT id FROM products 
                WHERE is_new = 1 AND is_active = 1
                ORDER BY updated_at DESC, created_at DESC
            ");
            $new_ids = $new_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Si hay más de 10, desmarcar las más viejas
            if (count($new_ids) > 10) {
                $ids_to_keep = array_slice($new_ids, 0, 10);
                $ids_to_remove = array_diff($new_ids, $ids_to_keep);
                
                if (!empty($ids_to_remove)) {
                    $placeholders = str_repeat('?,', count($ids_to_remove) - 1) . '?';
                    $pdo->prepare("UPDATE products SET is_new = 0 WHERE id IN ($placeholders)")
                        ->execute($ids_to_remove);
                }
            }
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
            
            $main_image_filename = null;
            
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
                            $main_image_filename = $new_filename; // Guardar para actualizar main_image
                        }
                        
                        $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)")
                            ->execute([$product_id, $new_filename, $is_primary, $current_max_order]);
                    }
                }
            }
            
            // Actualizar main_image en products si se subió una nueva imagen principal
            if ($main_image_filename) {
                $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?")
                    ->execute([$main_image_filename, $product_id]);
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
        
        // IMPORTANTE: SIEMPRE sincronizar main_image con la imagen principal de product_images
        // Esto asegura que products.main_image siempre tenga la imagen correcta
        $sync_main_image = $pdo->prepare("
            SELECT image_url 
            FROM product_images 
            WHERE product_id = ? AND is_primary = 1 
            LIMIT 1
        ");
        $sync_main_image->execute([$product_id]);
        $current_primary = $sync_main_image->fetchColumn();
        
        // Si no hay imagen principal marcada, usar la primera disponible
        if (!$current_primary) {
            $first_image = $pdo->prepare("
                SELECT image_url 
                FROM product_images 
                WHERE product_id = ? 
                ORDER BY display_order ASC 
                LIMIT 1
            ");
            $first_image->execute([$product_id]);
            $current_primary = $first_image->fetchColumn();
        }
        
        // Actualizar main_image en products con la imagen correcta
        if ($current_primary) {
            $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?")
                ->execute([$current_primary, $product_id]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = 'Producto actualizado correctamente';
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
function generateSKU($title, $pdo = null, $exclude_id = null) {
    // Extraer caracteres alfanuméricos del título
    $clean = preg_replace('/[^a-zA-Z0-9]/', '', $title);
    
    // Tomar hasta 6 caracteres del título (prefijo corto)
    $prefix = strtoupper(substr($clean, 0, 6));
    
    // Si el prefijo es muy corto, rellenar
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    
    // Generar sufijo aleatorio más robusto
    // Formato: PREFIJO-TIMESTAMP(5)-RANDOM(4)
    $timestamp = substr(time(), -5); // Últimos 5 dígitos del timestamp
    $random = rand(1000, 9999); // 4 dígitos random
    
    $sku = $prefix . '-' . $timestamp . $random;
    
    // Si tenemos acceso a PDO, verificar que sea único
    if ($pdo !== null) {
        $attempts = 0;
        $max_attempts = 20;
        
        while ($attempts < $max_attempts) {
            // Preparar query excluyendo el producto actual si estamos editando
            if ($exclude_id !== null) {
                $check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
                $check->execute([$sku, $exclude_id]);
            } else {
                $check = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
                $check->execute([$sku]);
            }
            
            if ($check->rowCount() === 0) {
                // SKU único encontrado
                break;
            }
            
            // SKU duplicado, generar uno nuevo con más aleatoriedad
            $random = rand(1000, 9999);
            $sku = $prefix . '-' . $timestamp . $random;
            $attempts++;
        }
        
        // Si después de muchos intentos sigue duplicado, usar microsegundos
        if ($attempts >= $max_attempts) {
            $micro = substr(str_replace('.', '', microtime(true)), -6);
            $sku = $prefix . '-' . $micro;
        }
    }
    
    return $sku;
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

// AHORA SÍ incluir el header (con HTML)
$is_edit = true;
$page_title = 'Editar Producto';
require_once 'inc/header.php';
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
                            
                            <!-- Campo de Slug (URL amigable) -->
                            <div class="mb-3">
                                <label for="slug_preview" class="form-label">
                                    <i class="fas fa-link me-2"></i>URL del Producto (Slug)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">/producto/</span>
                                    <input type="text" class="form-control" id="slug_preview" 
                                           value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" 
                                           readonly style="background-color: #f8f9fa;">
                                    <button class="btn btn-outline-secondary" type="button" id="regenerate_slug" title="Regenerar desde el título">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Se genera automáticamente desde el nombre del producto. 
                                    Ejemplo: "Kingdom Hearts 3" → "kingdom-hearts-3"
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
                                <input class="form-check-input" type="checkbox" id="is_new" name="is_new" 
                                       value="1" <?php echo (!empty($product['is_new'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_new">
                                    ⭐ Novedad (aparece en "Novedades" del home)
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
                                <strong>Consejo:</strong> Arrastra las imágenes para cambiar el orden. La primera imagen será la portada del producto.
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
                                <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                       value="<?php echo htmlspecialchars($product['meta_title'] ?? ''); ?>" 
                                       maxlength="60">
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
                                          rows="3" maxlength="160"><?php echo htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
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
                                <label for="price_pesos" class="form-label">
                                    Precio en Pesos (ARS) *
                                    <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" 
                                       title="Precio en pesos colombianos"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control price-input" id="price_pesos" name="price_pesos" 
                                           value="<?php echo number_format($product['price_pesos'] ?? 0, 0, '', '.'); ?>" 
                                           required data-raw-value="<?php echo $product['price_pesos'] ?? '0'; ?>">
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
                                    <input type="text" class="form-control price-input" id="price_dollars" name="price_dollars" 
                                           value="<?php echo number_format($product['price_dollars'] ?? 0, 0, '', '.'); ?>" 
                                           data-raw-value="<?php echo $product['price_dollars'] ?? '0'; ?>">
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
                                <div class="mb-3">
                                    <label for="discount_percentage_ars" class="form-label">
                                        <i class="fas fa-percent me-1"></i>Descuento (ARS) %
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control discount-input" id="discount_percentage_ars" name="discount_percentage_ars" 
                                               value="<?php echo intval($product['discount_percentage_ars'] ?? 0); ?>" 
                                               data-raw-value="<?php echo intval($product['discount_percentage_ars'] ?? 0); ?>">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted" id="discount-preview-ars">Sin descuento</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_percentage_usd" class="form-label">
                                        <i class="fas fa-percent me-1"></i>Descuento (USD) %
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control discount-input" id="discount_percentage_usd" name="discount_percentage_usd" 
                                               value="<?php echo intval($product['discount_percentage_usd'] ?? 0); ?>" 
                                               data-raw-value="<?php echo intval($product['discount_percentage_usd'] ?? 0); ?>">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted" id="discount-preview-usd">Sin descuento</small>
                                </div>
                            </div>
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
                                
                                <a href="../<?php echo getProductUrl($product); ?>" 
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

/* Estilos para Drag & Drop con SortableJS */
.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}

.sortable-chosen {
    transform: scale(1.05);
    box-shadow: 0 0 20px rgba(0,0,0,0.2);
}

.sortable-drag {
    opacity: 0.8;
}

.sortable-image-item {
    transition: transform 0.2s ease;
}

.sortable-image-item:hover {
    transform: translateY(-5px);
}
</style>

<!-- SortableJS para Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
/**
 * SISTEMA DE IMÁGENES CON DRAG & DROP
 * Versión: 4.0.0 - Igual que product_create.php
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sistema de imágenes con Drag & Drop cargado');
    
// ============================================
// GENERACIÓN AUTOMÁTICA DE SLUG
// ============================================

// Función para generar slug desde el nombre
function generateSlugFromName(name) {
    return name
        .toLowerCase()                          // Convertir a minúsculas
        .normalize('NFD')                       // Normalizar caracteres especiales
        .replace(/[\u0300-\u036f]/g, '')       // Eliminar diacríticos (acentos)
        .replace(/[^a-z0-9\s-]/g, '')          // Eliminar caracteres especiales
        .trim()                                 // Eliminar espacios al inicio y final
        .replace(/\s+/g, '-')                  // Reemplazar espacios con guiones
        .replace(/-+/g, '-');                  // Reemplazar múltiples guiones con uno solo
}

// Actualizar slug cuando cambia el nombre del producto
const nameInput = document.getElementById('name');
const slugPreview = document.getElementById('slug_preview');

if (nameInput && slugPreview) {
    nameInput.addEventListener('input', function() {
        const slug = generateSlugFromName(this.value);
        slugPreview.value = slug;
    });
}

// Botón para regenerar slug manualmente
const regenerateSlugBtn = document.getElementById('regenerate_slug');
if (regenerateSlugBtn && nameInput && slugPreview) {
    regenerateSlugBtn.addEventListener('click', function() {
        const slug = generateSlugFromName(nameInput.value);
        slugPreview.value = slug;
        
        // Feedback visual
        this.innerHTML = '<i class="fas fa-check"></i>';
        this.classList.add('btn-success');
        this.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-sync-alt"></i>';
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-secondary');
        }, 1500);
    });
}
    
    // Array para acumular archivos seleccionados (nuevos + existentes)
    let selectedFiles = [];
    let existingImages = [];
    let sortableInstance = null;
    
    // Cargar imágenes existentes del producto
    <?php
    $existing_images_json = [];
    if (!empty($product_images)) {
        foreach ($product_images as $index => $image) {
            $existing_images_json[] = [
                'id' => $image['id'],
                'url' => '../uploads/products/' . htmlspecialchars($image['image_url'] ?? $image['filename'] ?? ''),
                'is_existing' => true,
                'order' => $index
            ];
        }
    }
    ?>
    existingImages = <?php echo json_encode($existing_images_json); ?>;
    
    // Vista previa de imágenes (drag & drop)
    const imagesInput = document.getElementById('images');
    const preview = document.getElementById('image-preview');
    const dragInfo = document.getElementById('drag-drop-info');
    
    // Renderizar imágenes existentes al cargar
    renderAllImages();
    
    if (imagesInput) {
        imagesInput.addEventListener('change', function(e) {
            // Agregar nuevos archivos al array
            const newFiles = Array.from(this.files);
            newFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                }
            });
            
            // Regenerar vista previa
            renderAllImages();
            
            console.log('Total de imágenes:', existingImages.length + selectedFiles.length);
        });
    }
    
    function renderAllImages() {
        preview.innerHTML = '';
        
        const totalImages = existingImages.length + selectedFiles.length;
        
        if (totalImages === 0) {
            dragInfo.style.display = 'none';
            return;
        }
        
        // Mostrar info de drag & drop
        dragInfo.style.display = 'block';
        
        let currentIndex = 0;
        
        // Renderizar imágenes existentes primero
        existingImages.forEach((img, index) => {
            const col = document.createElement('div');
            col.className = 'col-md-3 sortable-image-item';
            col.dataset.index = currentIndex;
            col.dataset.imageId = img.id;
            col.dataset.isExisting = 'true';
            col.innerHTML = `
                <div class="card border-primary h-100" style="cursor: move;">
                    <div class="card-header bg-primary text-white py-1 d-flex justify-content-between align-items-center">
                        <small><i class="fas fa-grip-vertical"></i> #${currentIndex + 1}</small>
                        <button type="button" class="btn btn-sm btn-close btn-close-white" 
                                onclick="removeExistingImage(${img.id})" aria-label="Eliminar"></button>
                    </div>
                    <img src="${img.url}" class="card-img-top" 
                         style="height: 150px; object-fit: cover;" alt="Imagen existente">
                    <div class="card-body p-2 text-center">
                        ${currentIndex === 0 ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>' : '<span class="badge bg-info">Existente</span>'}
                    </div>
                </div>
            `;
            preview.appendChild(col);
            currentIndex++;
        });
        
        // Renderizar imágenes nuevas
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3 sortable-image-item';
                col.dataset.index = currentIndex;
                col.dataset.isExisting = 'false';
                col.innerHTML = `
                    <div class="card border-success h-100" style="cursor: move;">
                        <div class="card-header bg-success text-white py-1 d-flex justify-content-between align-items-center">
                            <small><i class="fas fa-grip-vertical"></i> #${currentIndex + 1}</small>
                            <button type="button" class="btn btn-sm btn-close btn-close-white" 
                                    onclick="removeNewImage(${index})" aria-label="Eliminar"></button>
                        </div>
                        <img src="${e.target.result}" class="card-img-top" 
                             style="height: 150px; object-fit: cover;" alt="Nueva imagen">
                        <div class="card-body p-2 text-center">
                            ${currentIndex === 0 ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>' : '<span class="badge bg-secondary">Nueva</span>'}
                        </div>
                    </div>
                `;
                preview.appendChild(col);
                currentIndex++;
                
                // Inicializar Sortable después de agregar la última imagen
                if (currentIndex === totalImages) {
                    initSortable();
                }
            };
            reader.readAsDataURL(file);
        });
        
        // Si solo hay imágenes existentes, inicializar Sortable inmediatamente
        if (selectedFiles.length === 0 && existingImages.length > 0) {
            initSortable();
        }
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
            handle: '.card',
            onEnd: function(evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                
                // Reordenar arrays combinados
                const allItems = [...existingImages, ...selectedFiles];
                const movedItem = allItems.splice(oldIndex, 1)[0];
                allItems.splice(newIndex, 0, movedItem);
                
                // Separar de nuevo en existentes y nuevas
                existingImages = allItems.filter(item => item.is_existing);
                selectedFiles = allItems.filter(item => !item.is_existing);
                
                console.log('Imagen movida:', oldIndex, '→', newIndex);
                updateImageNumbers();
                
                // Guardar nuevo orden de existentes en servidor
                saveImageOrder();
            }
        });
    }
    
    function updateImageNumbers() {
        const items = preview.querySelectorAll('.sortable-image-item');
        items.forEach((item, index) => {
            // Actualizar número
            const numberSpan = item.querySelector('.card-header small');
            if (numberSpan) {
                numberSpan.innerHTML = `<i class="fas fa-grip-vertical"></i> #${index + 1}`;
            }
            
            // Actualizar badge (portada solo para el primero)
            const badgeDiv = item.querySelector('.card-body');
            if (badgeDiv) {
                const isExisting = item.dataset.isExisting === 'true';
                if (index === 0) {
                    badgeDiv.innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>';
                } else {
                    badgeDiv.innerHTML = isExisting ? '<span class="badge bg-info">Existente</span>' : '<span class="badge bg-secondary">Nueva</span>';
                }
            }
            
            // Actualizar data-index
            item.dataset.index = index;
        });
    }
    
    function saveImageOrder() {
        const items = preview.querySelectorAll('.sortable-image-item[data-is-existing="true"]');
        const imageOrder = [];
        
        items.forEach((item, index) => {
            imageOrder.push({
                id: item.dataset.imageId,
                order: index + 1
            });
        });
        
        if (imageOrder.length === 0) return;
        
        console.log('💾 Guardando orden de imágenes existentes');
        
        fetch('api/update_image_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
                console.error('❌ Error:', data.message);
            }
        })
        .catch(error => console.error('❌ Error de red:', error));
    }
    
    // Eliminar imagen existente
    window.removeExistingImage = function(imageId) {
        if (!confirm('¿Eliminar esta imagen? Esta acción no se puede deshacer.')) return;
        
        fetch('api/delete_product_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image_id: imageId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remover del array local
                existingImages = existingImages.filter(img => img.id !== imageId);
                renderAllImages();
                console.log('✅ Imagen eliminada');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    };
    
    // Eliminar imagen nueva (no subida aún)
    window.removeNewImage = function(index) {
        selectedFiles.splice(index, 1);
        renderAllImages();
        console.log('Nueva imagen removida. Restantes:', selectedFiles.length);
    };
    
    // Antes de enviar el formulario, actualizar el input con archivos en orden
    const form = document.getElementById('product-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Crear DataTransfer con archivos en el orden correcto
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            imagesInput.files = dt.files;
            
            console.log('Enviando', selectedFiles.length, 'imágenes nuevas');
        });
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
// Auto-generar SKU cuando sale del campo nombre (blur)
// Versión 2.1 - SKU con formato: PREFIX(6)-TIMESTAMP(5)-RANDOM(4)
if (nameInputForSku) {
    nameInputForSku.addEventListener('blur', function() {
    const skuField = document.getElementById('sku');
    if (!skuField.value && this.value) {
        // Limpiar y tomar hasta 6 caracteres
        let prefix = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 6);
        
        // Rellenar si es muy corto
        if (prefix.length < 3) {
            prefix = prefix.padEnd(3, 'X');
        }
        
        // Generar sufijo con timestamp + random
        // Formato: PREFIJO-TIMESTAMP(5)-RANDOM(4)
        const timestamp = String(Math.floor(Date.now() / 1000)).slice(-5); // Últimos 5 dígitos
        const random = Math.floor(Math.random() * 9000 + 1000); // 4 dígitos random (1000-9999)
        
        skuField.value = prefix + '-' + timestamp + random;
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
        
        // Permitir que el formulario se envíe normalmente
        console.log('✅ Formulario válido, enviando...');
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

// ============================================
// SEO - CONTADORES Y AUTO-GENERAR
// ============================================

// Actualizar contadores de SEO
function updateSEOCounters() {
    const metaTitle = document.getElementById('meta_title').value;
    const metaDesc = document.getElementById('meta_description').value;
    
    document.getElementById('meta-title-counter').textContent = `(${metaTitle.length}/60)`;
    document.getElementById('meta-desc-counter').textContent = `(${metaDesc.length}/160)`;
}

// Auto-generar meta tags desde el nombre y descripción
document.getElementById('auto-generate-seo').addEventListener('click', function() {
    const productName = document.getElementById('name').value.trim();
    const description = document.getElementById('description').value.trim();
    
    if (!productName) {
        alert('Primero ingrese el nombre del producto');
        document.getElementById('name').focus();
        return;
    }
    
    // Generar meta title (máximo 60 caracteres)
    let metaTitle = productName;
    if (metaTitle.length > 60) {
        metaTitle = metaTitle.substring(0, 57) + '...';
    }
    
    // Generar meta description (máximo 160 caracteres)
    let metaDesc = description;
    if (metaDesc.length > 160) {
        metaDesc = metaDesc.substring(0, 157) + '...';
    }
    
    document.getElementById('meta_title').value = metaTitle;
    document.getElementById('meta_description').value = metaDesc;
    
    updateSEOCounters();
    
    // Feedback visual
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Generado';
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
    }, 2000);
});

// Actualizar contadores en tiempo real
document.getElementById('meta_title').addEventListener('input', updateSEOCounters);
document.getElementById('meta_description').addEventListener('input', updateSEOCounters);

// Inicializar contadores al cargar la página
updateSEOCounters();

// Inicializar tooltips de Bootstrap
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

}); // Fin de DOMContentLoaded

// ==========================================
// FORMATO DE PRECIOS CON SEPARADOR DE MILES
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Función para formatear número con puntos de miles
    function formatNumberWithThousands(value) {
        // Remover todo excepto dígitos
        const num = value.replace(/\D/g, '');
        
        // Si está vacío, retornar vacío
        if (!num) return '';
        
        // Formatear con puntos de miles
        return parseInt(num, 10).toLocaleString('es-AR').replace(/,/g, '.');
    }
    
    // Función para obtener el valor sin formato
    function getRawValue(formattedValue) {
        return formattedValue.replace(/\./g, '');
    }
    
    // Función para calcular nueva posición del cursor
    function getNewCursorPosition(oldValue, newValue, oldPosition) {
        const oldDots = (oldValue.substring(0, oldPosition).match(/\./g) || []).length;
        const rawPosition = oldPosition - oldDots;
        const newDots = (newValue.substring(0, rawPosition + Math.floor((newValue.length - rawPosition) / 4)).match(/\./g) || []).length;
        return rawPosition + newDots;
    }
    
    // Manejar campos de precio
    document.querySelectorAll('.price-input').forEach(input => {
        // Formatear valor inicial si existe
        if (input.value) {
            input.value = formatNumberWithThousands(input.value);
        }
        
        // Formatear en tiempo real mientras se escribe
        input.addEventListener('input', function(e) {
            const oldValue = this.value;
            const cursorPosition = this.selectionStart;
            
            // Remover formato y obtener solo números
            const rawValue = getRawValue(this.value);
            this.setAttribute('data-raw-value', rawValue);
            
            // Aplicar formato
            const formatted = formatNumberWithThousands(rawValue);
            
            // Solo actualizar si cambió
            if (this.value !== formatted) {
                this.value = formatted;
                
                // Calcular nueva posición del cursor
                const dotsBeforeCursor = (oldValue.substring(0, cursorPosition).match(/\./g) || []).length;
                const digitsBeforeCursor = cursorPosition - dotsBeforeCursor;
                const formattedBeforeCursor = formatted.substring(0, digitsBeforeCursor + Math.floor((formatted.length - digitsBeforeCursor) / 4));
                const newCursorPosition = formattedBeforeCursor.length;
                
                // Ajustar cursor
                this.setSelectionRange(newCursorPosition, newCursorPosition);
            }
        });
        
        // Al perder el foco, asegurar formato correcto
        input.addEventListener('blur', function() {
            const rawValue = getRawValue(this.value);
            this.setAttribute('data-raw-value', rawValue);
            if (rawValue) {
                this.value = formatNumberWithThousands(rawValue);
            }
        });
        
        // Al hacer focus, seleccionar todo para facilitar edición
        input.addEventListener('focus', function() {
            // Opcional: descomentar para seleccionar todo al hacer click
            // this.select();
        });
    });
    
    // Manejar campos de descuento (solo enteros, sin decimales)
    document.querySelectorAll('.discount-input').forEach(input => {
        input.addEventListener('input', function(e) {
            // Solo permitir números enteros hasta 100
            let value = this.value.replace(/\D/g, ''); // Remover todo excepto dígitos
            
            if (value) {
                value = parseInt(value, 10);
                if (value > 100) value = 100; // Máximo 100%
            }
            
            this.value = value || '';
            this.setAttribute('data-raw-value', value || '0');
            
            // Actualizar preview de descuento si existe
            const previewId = this.id.replace('discount_percentage', 'discount-preview');
            const previewEl = document.getElementById(previewId);
            if (previewEl) {
                if (value && value > 0) {
                    const priceId = this.id.includes('ars') ? 'price' : 'price_dollars';
                    const priceEl = document.getElementById(priceId);
                    if (priceEl) {
                        const priceValue = parseInt(getRawValue(priceEl.value) || '0');
                        const discountedPrice = priceValue * (1 - (value / 100));
                        previewEl.textContent = `Precio con descuento: $${formatNumberWithThousands(String(Math.round(discountedPrice)))}`;
                        previewEl.classList.add('text-success');
                    }
                } else {
                    previewEl.textContent = 'Sin descuento';
                    previewEl.classList.remove('text-success');
                }
            }
        });
    });
    
    // Al enviar el formulario, convertir valores formateados a números
    const form = document.getElementById('edit-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Convertir campos de precio a valores sin formato
            document.querySelectorAll('.price-input').forEach(input => {
                const rawValue = getRawValue(input.value);
                input.value = rawValue || '0';
            });
            
            // Asegurar que descuentos sean enteros
            document.querySelectorAll('.discount-input').forEach(input => {
                const rawValue = input.value.replace(/\D/g, '');
                input.value = rawValue || '0';
            });
        });
    }
    
    console.log('✅ Price formatting system initialized - v1.1 (Edit Mode)');
});
</script>

<?php require_once 'inc/footer.php'; ?>
