<?php
/**
 * CREAR NUEVO PRODUCTO
 * Formulario exclusivo para la creación de nuevos productos
 * 
 * Version: 2.18 - Fix [object Object] en plataformas
 * Fecha: 13 Feb 2026
 * Cambios: 
 *  - Corregido: Convertir objetos de plataforma a strings antes de mostrar
 *  - Mejora: Manejo robusto de plataformas (objetos o strings)
 *  - Fix: Ya no muestra [object Object] en tarjetas de juegos
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
            'price' => 'Precio',
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
        
        // Generar SKU si no se proporciona (ahora con verificación automática de unicidad)
        $sku = !empty($_POST['sku']) ? $_POST['sku'] : generateSKU($_POST['name'], $pdo);
        
        // Verificar SKU único (solo si el usuario lo proporcionó manualmente)
        if (!empty($_POST['sku'])) {
            $sku_check = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
            $sku_check->execute([$sku]);
            if ($sku_check->fetch()) {
                throw new Exception('El SKU ya existe. Por favor, use otro SKU o déjelo vacío para generar uno automáticamente.');
            }
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
            'product_type' => $_POST['product_type'] ?? 'game',
            'price_pesos' => floatval($_POST['price']),
            'price_dollars' => !empty($_POST['price_dollars']) ? floatval($_POST['price_dollars']) : 0.00,
            'is_on_sale' => isset($_POST['is_on_sale']) ? 1 : 0,
            'discount_percentage_ars' => !empty($_POST['discount_percentage_ars']) ? floatval($_POST['discount_percentage_ars']) : 0.00,
            'discount_percentage_usd' => !empty($_POST['discount_percentage_usd']) ? floatval($_POST['discount_percentage_usd']) : 0.00,
            'stock_quantity' => intval($_POST['stock_quantity']),
            'category_id' => intval($_POST['category_id']),
            'brand_id' => intval($_POST['brand_id']),
            'console_id' => intval($_POST['console_id']),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_new' => isset($_POST['is_new']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'is_active' => ($_POST['status'] ?? 'active') === 'active' ? 1 : 0,
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
            $main_image_filename = null;
            
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
                        
                        if ($first_image) {
                            $main_image_filename = $new_filename;
                            $first_image = false;
                        }
                        
                        $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)")
                            ->execute([$product_id, $new_filename, $is_primary, $display_order]);
                    }
                }
            }
            
            // Actualizar main_image en products con la primera imagen subida
            if ($main_image_filename) {
                $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?")
                    ->execute([$main_image_filename, $product_id]);
            }
        }
        
        $pdo->commit();
        
        // NO redirigir - quedarse en la misma página
        $_SESSION['product_created'] = true;
        $_SESSION['product_id'] = $product_id;
        $_SESSION['product_name'] = $_POST['name'];
        
        // NO usar header redirect - la página continuará cargando normalmente
        // y el modal se mostrará automáticamente
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
}

// Funciones auxiliares
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
        <!-- Botón para auto-completar -->
        <div class="text-center mb-4">
            <button type="button" class="btn btn-success btn-lg" id="autoCompleteBtn" style="min-width: 300px;">
                <i class="fas fa-magic me-2"></i>Auto-Rellenar Información del Juego
            </button>
            <div><small class="text-muted">Busca automáticamente descripción, géneros, plataforma, etc.</small></div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="product-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <!-- Version: 2.16 - BASE DE DATOS EXPANDIDA:
                1. Ahora incluye 200+ juegos verificados (antes 100+)
                2. 65+ franquicias distintas de videojuegos
                3. Series agregadas: Pokemon, Dragon Ball, Naruto, Witcher, Tomb Raider, Batman Arkham
                4. Juegos online/esports: Valorant, League of Legends, Dota 2, Counter-Strike
                5. Juegos deportes: NBA 2K, Madden NFL, WWE 2K
                6. Juegos AAA recientes: Starfield, Cyberpunk 2077, Dying Light 2
                7. Modal verifica plataformas ANTES de mostrar resultados
                8. Badge "✓ Verificado" para juegos de base de datos conocida
                Base de datos: 200+ juegos | Fuentes: Wikipedia, IGN, MobyGames
            -->
            
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
                                <div class="col-md-4 mb-3">
                                    <label for="product_type" class="form-label">Tipo de Producto *</label>
                                    <select class="form-select" id="product_type" name="product_type" required>
                                        <option value="game" selected>Juego</option>
                                        <option value="console">Consola</option>
                                        <option value="accessory">Accesorio</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" 
                                           maxlength="100" placeholder="Se generará automáticamente">
                                    <div class="form-text">Dejar vacío para auto-generar</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Estado *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" selected>Activo</option>
                                        <option value="inactive">Inactivo</option>
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
                                           value="" placeholder="se-generara-automaticamente"
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
                                          rows="6" required></textarea>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                <label class="form-check-label" for="is_featured">
                                    Producto Destacado
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_new" name="is_new" value="1" checked>
                                <label class="form-check-label" for="is_new">
                                    ⭐ Novedad (aparece en "Novedades" del home)
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
                                <label class="form-label fw-bold">
                                    <i class="fas fa-images me-2"></i>Imágenes del Producto
                                </label>
                                <div class="upload-area border border-2 border-dashed rounded-3 p-4 text-center" id="imageUploadArea" style="cursor: pointer; transition: all 0.3s;">
                                    <input type="file" class="d-none" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                                    <div class="upload-icon mb-3">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="mb-2">Arrastra imágenes aquí o haz clic para seleccionar</h5>
                                    <p class="text-muted mb-0">
                                        <small><i class="fas fa-info-circle me-1"></i>Formatos: JPG, PNG, WebP • Máximo 5MB por imagen</small>
                                    </p>
                                </div>
                            </div>
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
                                <label for="price" class="form-label">Precio (ARS) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           min="0" step="0.01" required>
                                    <span class="input-group-text">ARS</span>
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
                            
                            <!-- Oferta / Descuento -->
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_on_sale" name="is_on_sale" value="1">
                                    <label class="form-check-label" for="is_on_sale">
                                        🔥 <strong>En Oferta</strong>
                                    </label>
                                </div>
                            </div>
                            
                            <div id="discount-section" style="display: none;">
                                <div class="mb-3">
                                    <label for="discount_percentage_ars" class="form-label">
                                        <i class="fas fa-percent me-1"></i>Descuento (ARS) %
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="discount_percentage_ars" 
                                               name="discount_percentage_ars" min="0" max="100" step="0.01" value="0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted" id="discount-preview-ars">Sin descuento</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_percentage_usd" class="form-label">
                                        <i class="fas fa-percent me-1"></i>Descuento (USD) %
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="discount_percentage_usd" 
                                               name="discount_percentage_usd" min="0" max="100" step="0.01" value="0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted" id="discount-preview-usd">Sin descuento</small>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Cantidad en Stock *</label>
                                <input type="number" class="form-control form-control-lg" id="stock_quantity" name="stock_quantity" 
                                       value="0" min="0" required>
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
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" id="addCategoryBtn" title="Agregar nueva categoría">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marca *</label>
                                <div class="input-group">
                                    <select class="form-select" id="brand_id" name="brand_id" required>
                                        <option value="">Seleccionar marca</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>">
                                                <?php echo htmlspecialchars($brand['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" id="addBrandBtn" title="Agregar nueva marca">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="console_id" class="form-label">Consola / Plataforma *</label>
                                <div class="input-group">
                                    <select class="form-select" id="console_id" name="console_id" required>
                                        <option value="">Seleccionar consola</option>
                                        <?php foreach ($consoles as $console): ?>
                                            <option value="<?php echo $console['id']; ?>">
                                                <?php echo htmlspecialchars($console['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" id="addConsoleBtn" title="Agregar nueva consola">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Géneros</span>
                                    <button class="btn btn-sm btn-outline-success" type="button" id="addGenreBtn" title="Agregar nuevo género">
                                        <i class="fas fa-plus"></i> Agregar género
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

<!-- Modales para Agregar Nuevos Elementos -->
<!-- Modal: Agregar Categoría -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Categoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newCategoryName" class="form-label">Nombre de la Categoría *</label>
                    <input type="text" class="form-control" id="newCategoryName" placeholder="Ej: Ediciones Especiales" required>
                </div>
                <div id="addCategoryResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveCategoryBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Marca -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Marca</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newBrandName" class="form-label">Nombre de la Marca *</label>
                    <input type="text" class="form-control" id="newBrandName" placeholder="Ej: PlayStation Studios" required>
                </div>
                <div id="addBrandResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveBrandBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Consola -->
<div class="modal fade" id="addConsoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Consola</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newConsoleName" class="form-label">Nombre de la Consola *</label>
                    <input type="text" class="form-control" id="newConsoleName" placeholder="Ej: PlayStation 1" required>
                </div>
                <div id="addConsoleResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveConsoleBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Género -->
<div class="modal fade" id="addGenreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Género</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newGenreName" class="form-label">Nombre del Género *</label>
                    <input type="text" class="form-control" id="newGenreName" placeholder="Ej: Survival Horror" required>
                </div>
                <div id="addGenreResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="saveGenreBtn">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Búsqueda de Juegos -->
<div class="modal fade" id="gameSearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-search me-2"></i>Buscar Juego</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg" id="gameSearchInput" readonly 
                           placeholder="Buscando..." value="">
                </div>
                <div id="gameSearchResults" class="row g-3">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <p class="text-muted">Buscando información del juego...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SortableJS para Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
/* Estilos para drag & drop de imágenes */
.upload-area {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.upload-area:hover {
    background-color: #e9ecef;
    border-color: #0d6efd !important;
}

.upload-area.border-primary {
    background-color: #cfe2ff !important;
}

.upload-icon {
    transition: transform 0.3s ease;
}

.upload-area:hover .upload-icon i {
    transform: translateY(-5px);
}

/* Estilos para modal de búsqueda de juegos */
.game-result-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    min-height: 200px;
}

.game-result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    border-color: #0d6efd;
}

.game-result-card img {
    min-height: 120px;
    max-height: 120px;
    display: block;
}

.game-result-card .card-body {
    padding: 0.75rem;
}

.game-result-card .card-title {
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1.2;
    max-height: 2.4em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Estilos para sortable de imágenes */
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
        if (!preview) return;
        
        preview.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            if (dragInfo) dragInfo.style.display = 'none';
            return;
        }
        
        // Mostrar info de drag & drop
        if (dragInfo) dragInfo.style.display = 'block';
        
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
        if (!preview) return;
        
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
        if (!preview) return;
        
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
    if (form && imagesInput) {
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
    const autoGenerateSeoBtn = document.getElementById('auto-generate-seo');
    if (autoGenerateSeoBtn) {
        autoGenerateSeoBtn.addEventListener('click', function() {
        const nameEl = document.getElementById('name');
        const descriptionEl = document.getElementById('description');
        const name = nameEl ? nameEl.value : '';
        const description = descriptionEl ? descriptionEl.value : '';
        
        if (!name) {
            alert('Por favor ingrese el nombre del producto primero');
            return;
        }
        
        let metaTitle = name;
        if (metaTitle.length > 60) {
            metaTitle = metaTitle.substring(0, 57) + '...';
        }
        const metaTitleEl = document.getElementById('meta_title');
        if (metaTitleEl) {
            metaTitleEl.value = metaTitle;
        }
        
        let metaDesc = description || name;
        metaDesc = metaDesc.replace(/<[^>]*>/g, '').trim();
        if (metaDesc.length > 160) {
            metaDesc = metaDesc.substring(0, 157) + '...';
        }
        const metaDescEl = document.getElementById('meta_description');
        if (metaDescEl) {
            metaDescEl.value = metaDesc;
        }
        
        updateSEOCounters();
        });
    }
    
    // Contadores SEO
    function updateSEOCounters() {
        const metaTitleEl = document.getElementById('meta_title');
        const metaDescEl = document.getElementById('meta_description');
        const titleCounter = document.getElementById('meta-title-counter');
        const descCounter = document.getElementById('meta-desc-counter');
        if (!metaTitleEl || !metaDescEl || !titleCounter || !descCounter) {
            return;
        }
        
        const metaTitle = metaTitleEl.value;
        const metaDesc = metaDescEl.value;
        
        titleCounter.textContent = `(${metaTitle.length}/60)`;
        descCounter.textContent = `(${metaDesc.length}/160)`;
    }
    
    const metaTitleEl = document.getElementById('meta_title');
    if (metaTitleEl) {
        metaTitleEl.addEventListener('input', updateSEOCounters);
    }
    const metaDescEl = document.getElementById('meta_description');
    if (metaDescEl) {
        metaDescEl.addEventListener('input', updateSEOCounters);
    }
    
    // Toggle descuento
    const isOnSaleEl = document.getElementById('is_on_sale');
    if (isOnSaleEl) {
        isOnSaleEl.addEventListener('change', function() {
            const discountSection = document.getElementById('discount-section');
            if (discountSection) {
                discountSection.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    // Vista previa descuento
    function updateDiscountPreview() {
        // Descuento ARS
        const priceArsEl = document.getElementById('price');
        const discountArsEl = document.getElementById('discount_percentage_ars');
        const previewArsEl = document.getElementById('discount-preview-ars');
        
        if (priceArsEl && discountArsEl && previewArsEl) {
            const price = parseFloat(priceArsEl.value) || 0;
            const discount = parseFloat(discountArsEl.value) || 0;
            
            if (discount > 0 && price > 0) {
                const finalPrice = price - (price * discount / 100);
                const formatter = new Intl.NumberFormat('es-AR', {
                    style: 'currency',
                    currency: 'ARS',
                    minimumFractionDigits: 2
                });
                
                previewArsEl.innerHTML = `
                    <span class="text-danger"><del>${formatter.format(price)}</del></span> → 
                    <span class="text-success fw-bold">${formatter.format(finalPrice)}</span> 
                    <span class="badge bg-success">-${discount}%</span>
                `;
            } else {
                previewArsEl.textContent = 'Sin descuento';
            }
        }
        
        // Descuento USD
        const priceUsdEl = document.getElementById('price_dollars');
        const discountUsdEl = document.getElementById('discount_percentage_usd');
        const previewUsdEl = document.getElementById('discount-preview-usd');
        
        if (priceUsdEl && discountUsdEl && previewUsdEl) {
            const price = parseFloat(priceUsdEl.value) || 0;
            const discount = parseFloat(discountUsdEl.value) || 0;
            
            if (discount > 0 && price > 0) {
                const finalPrice = price - (price * discount / 100);
                const formatter = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2
                });
                
                previewUsdEl.innerHTML = `
                    <span class="text-danger"><del>${formatter.format(price)}</del></span> → 
                    <span class="text-success fw-bold">${formatter.format(finalPrice)}</span> 
                    <span class="badge bg-success">-${discount}%</span>
                `;
            } else {
                previewUsdEl.textContent = 'Sin descuento';
            }
        }
    }
    
    // Event listeners para precios y descuentos
    const priceEl = document.getElementById('price');
    if (priceEl) {
        priceEl.addEventListener('input', updateDiscountPreview);
    }
    
    const priceUsdEl = document.getElementById('price_dollars');
    if (priceUsdEl) {
        priceUsdEl.addEventListener('input', updateDiscountPreview);
    }
    
    const discountArsEl = document.getElementById('discount_percentage_ars');
    if (discountArsEl) {
        discountArsEl.addEventListener('input', updateDiscountPreview);
    }
    
    const discountUsdEl = document.getElementById('discount_percentage_usd');
    if (discountUsdEl) {
        discountUsdEl.addEventListener('input', updateDiscountPreview);
    }
    
    // Alerta de stock
    const stockQuantityEl = document.getElementById('stock_quantity');
    if (stockQuantityEl) {
        stockQuantityEl.addEventListener('input', function() {
        const stock = parseInt(this.value) || 0;
        const alert = document.getElementById('stock-alert');
        if (!alert) {
            return;
        }
        
        if (stock === 0) {
            alert.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Sin stock</span>';
        } else if (stock < 5) {
            alert.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Stock bajo</span>';
        } else {
            alert.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Stock disponible</span>';
        }
        });
    }
    
    // Auto-generar SKU en tiempo real mientras escribe
    // Versión 2.1 - SKU con formato: PREFIX(6)-TIMESTAMP(5)-RANDOM(4)
    let skuManuallyEdited = false; // Bandera para saber si el usuario editó el SKU
    
    const nameEl = document.getElementById('name');
    if (nameEl) {
        nameEl.addEventListener('input', function() {
        const skuField = document.getElementById('sku');
        if (!skuField) {
            return;
        }
        
        // Solo regenerar si el SKU no ha sido editado manualmente
        if (!skuManuallyEdited && this.value) {
            // Limpiar y tomar hasta 6 caracteres
            let prefix = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 6);
            
            // Rellenar si es muy corto
            if (prefix.length < 3) {
                prefix = prefix.padEnd(3, 'X');
            }
            
            // Generar sufijo con timestamp + random más robusto
            // Formato: PREFIJO-TIMESTAMP(5)-RANDOM(4)
            const timestamp = String(Math.floor(Date.now() / 1000)).slice(-5); // Últimos 5 dígitos del timestamp
            const random = Math.floor(Math.random() * 9000 + 1000); // 4 dígitos random (1000-9999)
            
            skuField.value = prefix + '-' + timestamp + random;
        } else if (!skuManuallyEdited && !this.value) {
            // Si borra el nombre, limpiar el SKU también
            skuField.value = '';
        }
        });
    }
    
    // Detectar cuando el usuario edita manualmente el SKU
    const skuEl = document.getElementById('sku');
    if (skuEl) {
        skuEl.addEventListener('input', function() {
            skuManuallyEdited = true;
        });
    }
    
    // Si el usuario borra completamente el SKU, volver a habilitar generación automática
    if (skuEl) {
        skuEl.addEventListener('input', function() {
            if (this.value === '') {
                skuManuallyEdited = false;
            }
        });
    }
    
    // ==========================================
    // BOTONES DE AGREGAR CATEGORÍA, MARCA, CONSOLA Y GÉNERO
    // ==========================================
    
    // Agregar Categoría
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
            modal.show();
        });
    }
    
    const saveCategoryBtn = document.getElementById('saveCategoryBtn');
    if (saveCategoryBtn) {
        saveCategoryBtn.addEventListener('click', function() {
        const name = document.getElementById('newCategoryName').value.trim();
        if (!name) {
            alert('Por favor ingrese un nombre para la categoría');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        
        fetch('ajax/save_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('category_id');
                const option = new Option(name, data.id, true, true);
                select.add(option);
                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                document.getElementById('newCategoryName').value = '';
            } else {
                alert(data.message || 'Error al agregar categoría');
            }
        })
        .catch(e => alert('Error: ' + e.message))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
        });
        });
    }
    
    // Agregar Marca
    const addBrandBtn = document.getElementById('addBrandBtn');
    if (addBrandBtn) {
        addBrandBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('addBrandModal'));
            modal.show();
        });
    }
    
    const saveBrandBtn = document.getElementById('saveBrandBtn');
    if (saveBrandBtn) {
        saveBrandBtn.addEventListener('click', function() {
        const name = document.getElementById('newBrandName').value.trim();
        if (!name) {
            alert('Por favor ingrese un nombre para la marca');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        
        fetch('ajax/save_brand.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('brand_id');
                const option = new Option(name, data.id, true, true);
                select.add(option);
                bootstrap.Modal.getInstance(document.getElementById('addBrandModal')).hide();
                document.getElementById('newBrandName').value = '';
            } else {
                alert(data.message || 'Error al agregar marca');
            }
        })
        .catch(e => alert('Error: ' + e.message))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
        });
        });
    }
    
    // Agregar Consola
    const addConsoleBtn = document.getElementById('addConsoleBtn');
    if (addConsoleBtn) {
        addConsoleBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('addConsoleModal'));
            modal.show();
        });
    }
    
    const saveConsoleBtn = document.getElementById('saveConsoleBtn');
    if (saveConsoleBtn) {
        saveConsoleBtn.addEventListener('click', function() {
        const name = document.getElementById('newConsoleName').value.trim();
        if (!name) {
            alert('Por favor ingrese un nombre para la consola');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        
        fetch('ajax/save_console.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('console_id');
                const option = new Option(name, data.id, true, true);
                select.add(option);
                bootstrap.Modal.getInstance(document.getElementById('addConsoleModal')).hide();
                document.getElementById('newConsoleName').value = '';
            } else {
                alert(data.message || 'Error al agregar consola');
            }
        })
        .catch(e => alert('Error: ' + e.message))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
        });
        });
    }
    
    // Agregar Género
    const addGenreBtn = document.getElementById('addGenreBtn');
    if (addGenreBtn) {
        addGenreBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('addGenreModal'));
            modal.show();
        });
    }
    
    const saveGenreBtn = document.getElementById('saveGenreBtn');
    if (saveGenreBtn) {
        saveGenreBtn.addEventListener('click', function() {
        const name = document.getElementById('newGenreName').value.trim();
        if (!name) {
            alert('Por favor ingrese un nombre para el género');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        
        fetch('ajax/save_genre.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Agregar el nuevo género a la lista
                const genreContainer = document.querySelector('.border.rounded');
                const newGenreHTML = `
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   name="genres[]" value="${data.id}" 
                                   id="genre_${data.id}" checked>
                            <label class="form-check-label" for="genre_${data.id}">
                                ${name}
                            </label>
                        </div>
                    </div>
                `;
                const row = genreContainer.querySelector('.row');
                if (row) {
                    row.insertAdjacentHTML('beforeend', newGenreHTML);
                }
                bootstrap.Modal.getInstance(document.getElementById('addGenreModal')).hide();
                document.getElementById('newGenreName').value = '';
            } else {
                alert(data.message || 'Error al agregar género');
            }
        })
        .catch(e => alert('Error: ' + e.message))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-2"></i>Guardar';
        });
        });
    }

    // ==========================================
    // BOTÓN DE AUTO-RELLENAR CON BÚSQUEDA MULTI-FUENTE
    // Version: 2.15 - Verificación de plataformas antes de mostrar
    // ==========================================

    // Función para mostrar los resultados de búsqueda con plataformas verificadas
    async function displayGameResults(games, container, modal) {
        container.innerHTML = '<div class="col-12 text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">🔍 Verificando plataformas correctas para cada juego...</p></div>';

        const safeGames = Array.isArray(games) ? games : [];

        // Obtener plataformas correctas para todos los juegos
        const gamesWithCorrectPlatforms = await Promise.all(
            safeGames.map(async (game) => {
                try {
                    const gameName = game && game.name ? game.name : 'Juego sin nombre';

                    // Consultar plataformas correctas de la base de datos
                    const platformsResponse = await fetch(`ajax/get_game_platforms.php?game_name=${encodeURIComponent(gameName)}`);
                    const platformsResult = await platformsResponse.json();

                    let correctPlatforms = [];
                    let platformSource = 'RAWG';

                    if (platformsResult.success && platformsResult.platforms) {
                        // Asegurar que sean strings (extraer .name si son objetos)
                        correctPlatforms = platformsResult.platforms.map(p => 
                            typeof p === 'string' ? p : (p.name || p)
                        );
                        platformSource = platformsResult.source;
                        console.log(`✅ Plataformas verificadas para "${gameName}":`, correctPlatforms, `(Fuente: ${platformSource})`);
                    } else {
                        // Fallback a RAWG si no hay en base de datos
                        correctPlatforms = Array.isArray(game && game.platforms)
                            ? game.platforms
                                .map(p => (p && p.platform && p.platform.name) ? p.platform.name : null)
                                .filter(Boolean)
                            : [];
                        console.log(`⚠️ Usando RAWG para "${gameName}":`, correctPlatforms);
                    }

                    return {
                        ...game,
                        correctPlatforms: correctPlatforms,
                        platformSource: platformSource
                    };
                } catch (error) {
                    console.error('Error obteniendo plataformas para', game.name, error);
                    // En caso de error, usar las de RAWG
                    return {
                        ...game,
                        correctPlatforms: Array.isArray(game && game.platforms)
                            ? game.platforms.map(p => p.platform?.name).filter(Boolean)
                            : [],
                        platformSource: 'RAWG (error)'
                    };
                }
            })
        );

        // Limpiar contenedor y mostrar resultados
        container.innerHTML = '';

        gamesWithCorrectPlatforms.forEach(game => {
            try {
                const safeName = game && game.name ? game.name : 'Juego sin nombre';

                // USAR LAS PLATAFORMAS CORRECTAS - Asegurar que sean strings
                const platformStrings = game.correctPlatforms && game.correctPlatforms.length > 0
                    ? game.correctPlatforms.map(p => typeof p === 'string' ? p : (p.name || String(p)))
                    : [];
                
                const platformsDisplay = platformStrings.length > 0
                    ? platformStrings.slice(0, 3).join(', ') + (platformStrings.length > 3 ? ', ...' : '')
                    : 'N/A';

                const platformCount = game.correctPlatforms ? game.correctPlatforms.length : 0;
                const platformBadge = game.platformSource === 'KNOWN_DATABASE'
                    ? '<span class="badge bg-success" style="font-size: 0.6rem;">✓ Verificado</span>'
                    : '';

                const imageUrl = (game && typeof game.background_image === 'string' && game.background_image.trim() !== '')
                    ? game.background_image
                    : 'https://via.placeholder.com/200x150?text=Sin+Imagen';
                const released = (game && game.released) ? new Date(game.released).getFullYear() : 'N/A';
                const rating = (game && typeof game.rating === 'number') ? game.rating.toFixed(1) : 'N/A';

            const col = document.createElement('div');
            col.className = 'col-12 col-sm-6 col-md-4 col-lg-3 mb-3';

            col.innerHTML = `
                <div class="card game-result-card h-100 shadow-sm" style="cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease;">
                    <div style="position: relative; overflow: hidden; height: 120px; background: #f0f0f0;">
                        <img src="${imageUrl}"
                             class="img-fluid"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             alt="${safeName}"
                             loading="lazy"
                             onerror="this.src='https://via.placeholder.com/200x150?text=No+Image'">
                        ${platformBadge ? `<div style="position: absolute; top: 5px; right: 5px;">${platformBadge}</div>` : ''}
                    </div>
                    <div class="card-body p-2" style="flex-grow: 1; display: flex; flex-direction: column;">
                        <h6 class="card-title mb-1" style="font-size: 0.85rem; font-weight: 600; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            ${safeName}
                        </h6>
                        <div class="mt-auto" style="font-size: 0.75rem;">
                            <p class="card-text mb-1" style="color: #666;">
                                <i class="fas fa-gamepad me-1" style="color: #007bff;"></i><strong>${platformCount > 1 ? platformCount + ' plataformas' : platformsDisplay}</strong>
                            </p>
                            ${platformCount > 1 ? `<p class="card-text mb-1" style="color: #999; font-size: 0.7rem;">${platformsDisplay}</p>` : ''}
                            <p class="card-text mb-1" style="color: #666;">
                                <i class="fas fa-calendar me-1" style="color: #28a745;"></i>${released}
                            </p>
                            <p class="card-text" style="color: #ffc107;">
                                <i class="fas fa-star me-1"></i>${rating}
                            </p>
                        </div>
                    </div>
                </div>
            `;

            col.querySelector('.game-result-card').addEventListener('click', function() {
                fillGameData(game, modal);
            });

            col.querySelector('.game-result-card').addEventListener('mouseenter', function() {
                this.style.borderColor = '#007bff';
                this.style.boxShadow = '0 4px 8px rgba(0,123,255,0.3)';
                this.style.transform = 'translateY(-3px)';
            });

            col.querySelector('.game-result-card').addEventListener('mouseleave', function() {
                this.style.borderColor = 'transparent';
                this.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.075)';
                this.style.transform = 'translateY(0)';
            });
            
            container.appendChild(col);
            } catch (e) {
                console.warn('Error renderizando juego:', e, game);
            }
        });
    }
    
    // Función para rellenar los datos del juego seleccionado
    async function fillGameData(game, modal) {
        try {
            // Mostrar mensaje de carga
            const resultsDiv = document.getElementById('gameSearchResults');
            resultsDiv.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-success mb-3"></i>
                    <p class="text-muted">Obteniendo plataformas disponibles para "${game.name}"...</p>
                </div>
            `;

            // PASO 1: Obtener las plataformas disponibles para este juego
            console.log('📋 Obteniendo plataformas para:', game.name);

            const platformsResponse = await fetch(`ajax/get_game_platforms.php?game_name=${encodeURIComponent(game.name)}`);
            const platformsResult = await platformsResponse.json();

            if (!platformsResult.success) {
                throw new Error(platformsResult.error || 'Error al obtener plataformas');
            }

            const availablePlatforms = platformsResult.platforms;
            console.log('🎮 Plataformas disponibles:', availablePlatforms);
            console.log('📍 Fuente de datos:', platformsResult.source);

            // PASO 2: Si hay múltiples plataformas, mostrar opciones separadas
            if (availablePlatforms.length > 1) {
                console.log('⚠️ Múltiples plataformas detectadas, mostrando opciones...');

                resultsDiv.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>${game.name} - Seleccionar Plataforma
                            </h5>
                            <p class="mb-0">Este juego está disponible en <strong>${availablePlatforms.length} plataformas diferentes</strong>.</p>
                            <p class="mb-0 mt-2">Selecciona la plataforma específica para la cual deseas crear el producto. Cada plataforma tendrá su propia consola asignada.</p>
                        </div>
                    </div>
                `;

                // Crear una card por cada plataforma
                availablePlatforms.forEach((platform, index) => {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-sm-6 col-lg-4 mb-3';

                    // Generar ícono según la plataforma
                    let icon = 'fas fa-gamepad';
                    const platformLower = platform.toLowerCase();
                    if (platformLower.includes('pc')) icon = 'fas fa-laptop';
                    else if (platformLower.includes('ps') || platformLower.includes('playstation')) icon = 'fab fa-playstation';
                    else if (platformLower.includes('xbox')) icon = 'fab fa-xbox';
                    else if (platformLower.includes('switch') || platformLower.includes('nintendo')) icon = 'fas fa-gamepad';
                    else if (platformLower.includes('mobile') || platformLower.includes('ios') || platformLower.includes('android')) icon = 'fas fa-mobile-alt';

                    col.innerHTML = `
                        <div class="card platform-option-card h-100 shadow-sm border-0" 
                             style="cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-center text-white">
                                <div class="mb-3">
                                    <i class="${icon} fa-2x" style="opacity: 0.9;"></i>
                                </div>
                                <h6 class="card-title mb-2" style="font-size: 1.1rem; font-weight: 600;">${platform}</h6>
                                <p class="card-text small mb-2" style="opacity: 0.95;">Opción ${index + 1} de ${availablePlatforms.length}</p>
                                <p class="text-white-50 small mb-0 mt-2" style="font-size: 0.85rem;">Clic para continuar →</p>
                            </div>
                        </div>
                    `;

                    // Evento de clic para seleccionar esta plataforma
                    col.querySelector('.platform-option-card').addEventListener('click', async function() {
                        console.log(`✅ Usuario seleccionó: ${game.name} - ${platform}`);
                        await loadGameDataForPlatform(game.name, platform, modal, resultsDiv);
                    });

                    // Hover effects mejorados
                    col.querySelector('.platform-option-card').addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-8px)';
                        this.style.boxShadow = '0 12px 24px rgba(102, 126, 234, 0.4)';
                    });

                    col.querySelector('.platform-option-card').addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                    });

                    resultsDiv.appendChild(col);
                });

            } else {
                // Solo una plataforma, proceder directamente
                console.log('✅ Una sola plataforma, cargando datos...');
                await loadGameDataForPlatform(game.name, availablePlatforms[0], modal, resultsDiv);
            }

        } catch (error) {
            console.error('Error obteniendo plataformas del juego:', error);

            const resultsDiv = document.getElementById('gameSearchResults');
            resultsDiv.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                    <p class="text-muted"><strong>Error al obtener plataformas</strong></p>
                    <p class="small text-muted">${error.message}</p>
                    <button class="btn btn-primary mt-3" data-bs-dismiss="modal">Cerrar</button>
                </div>
            `;
        }
    }

    // Función para cargar los datos del juego para una plataforma específica
    async function loadGameDataForPlatform(gameName, platform, modal, resultsDiv) {
        try {
            resultsDiv.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-success mb-3"></i>
                    <p class="text-muted">Cargando información completa de "${gameName}" para ${platform}...</p>
                </div>
            `;

            // USAR AUTOCOMPLETE_GAME_INFO.PHP que tiene toda la lógica de traducción y mapeo al español
            console.log(`📥 Obteniendo información en español para: ${gameName} - ${platform}`);

            const url = `ajax/autocomplete_game_info.php?game_name=${encodeURIComponent(gameName)}&platform=${encodeURIComponent(platform)}`;
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Error al obtener información del juego');
            }

            // Usar los datos ya traducidos y mapeados al español
            const gameDetails = result.data;

            console.log('✅ Datos recibidos de autocomplete_game_info.php:', gameDetails);

            // Descargar y subir imágenes si están disponibles
            if (gameDetails.images && gameDetails.images.length > 0) {
                console.log('📸 Descargando', gameDetails.images.length, 'imágenes del juego...');

                for (const imageUrl of gameDetails.images) {
                    try {
                        // Crear FormData para subir la imagen
                        const formData = new FormData();
                        formData.append('action', 'download_game_image');
                        formData.append('image_url', imageUrl);
                        formData.append('game_name', gameDetails.title);

                        // Enviar solicitud para descargar y subir imagen
                        const imageResponse = await fetch('ajax/upload_game_image.php', {
                            method: 'POST',
                            body: formData
                        });

                        const imageResult = await imageResponse.json();

                        if (imageResult.success && imageResult.file_path) {
                            console.log('✅ Imagen descargada exitosamente:', imageResult.file_path);
                        }
                    } catch (imageError) {
                        console.error('Error descargando imagen:', imageError);
                    }
                }
            }

            // Rellenar nombre
            const nameEl = document.getElementById('name');
            if (nameEl && gameDetails.title) {
                nameEl.value = gameDetails.title;
            }

            // Rellenar descripción (YA ESTÁ EN ESPAÑOL)
            const descriptionEl = document.getElementById('description');
            if (descriptionEl && gameDetails.description) {
                descriptionEl.value = gameDetails.description;
            }

            // Rellenar géneros (YA ESTÁN EN ESPAÑOL)
            if (gameDetails.genres && gameDetails.genres.length > 0) {
                console.log('🎮 Géneros en español:', gameDetails.genres);

                // Los géneros ya están en español, buscar checkboxes que coincidan
                const genreCheckboxes = document.querySelectorAll('input[name="genres[]"]');
                genreCheckboxes.forEach(checkbox => {
                    const label = checkbox.nextElementSibling.textContent.toLowerCase().trim();
                    const isMatch = gameDetails.genres.some(genre =>
                        genre.toLowerCase() === label || label.includes(genre.toLowerCase())
                    );
                    if (isMatch) {
                        checkbox.checked = true;
                        console.log('✓ Género seleccionado:', label);
                    }
                });
            }

            // Rellenar plataforma/consola basado en la plataforma seleccionada
            console.log('🎮 Plataforma seleccionada:', platform);

            const consoleSelect = document.getElementById('console_id');
            if (consoleSelect && platform) {
                const platformLower = platform.toLowerCase();

                // Buscar coincidencia en el select
                let found = false;
                Array.from(consoleSelect.options).forEach(option => {
                    const optionText = option.text.toLowerCase();
                    if (optionText.includes(platformLower) || platformLower.includes(optionText)) {
                        option.selected = true;
                        found = true;
                        console.log('✓ Consola seleccionada:', option.text);
                    }
                });

                if (!found && gameDetails.platforms && gameDetails.platforms.length > 0) {
                    // Si no encontró coincidencia exacta, intentar con la primera plataforma disponible
                    const firstPlatform = gameDetails.platforms[0].toLowerCase();
                    Array.from(consoleSelect.options).forEach(option => {
                        const optionText = option.text.toLowerCase();
                        if (optionText.includes(firstPlatform) || firstPlatform.includes(optionText)) {
                            option.selected = true;
                            console.log('✓ Consola (fallback) seleccionada:', option.text);
                        }
                    });
                }
            }

            // Rellenar marca/brand (YA ESTÁ EN ESPAÑOL)
            if (gameDetails.brand) {
                console.log('🏢 Marca:', gameDetails.brand);

                const brandSelect = document.getElementById('brand_id');
                if (brandSelect) {
                    const brandLower = gameDetails.brand.toLowerCase();

                    // Buscar coincidencia exacta o parcial
                    Array.from(brandSelect.options).forEach(option => {
                        const optionText = option.text.toLowerCase();
                        if (optionText.includes(brandLower) || brandLower.includes(optionText)) {
                            option.selected = true;
                            console.log('✓ Marca seleccionada:', option.text);
                        }
                    });
                }
            }

            // Rellenar categoría (YA ESTÁ EN ESPAÑOL)
            if (gameDetails.category) {
                console.log('📁 Categoría:', gameDetails.category);

                const categorySelect = document.getElementById('category_id');
                if (categorySelect) {
                    const categoryLower = gameDetails.category.toLowerCase();

                    // Buscar coincidencia
                    Array.from(categorySelect.options).forEach(option => {
                        const optionText = option.text.toLowerCase();
                        if (optionText.includes(categoryLower) || categoryLower.includes(optionText)) {
                            option.selected = true;
                            console.log('✓ Categoría seleccionada:', option.text);
                        }
                    });
                }
            }

            // Rellenar meta título y descripción SEO
            const metaTitleEl = document.getElementById('meta_title');
            if (metaTitleEl && gameDetails.title) {
                metaTitleEl.value = gameDetails.title;
            }

            const metaDescEl = document.getElementById('meta_description');
            if (metaDescEl && gameDetails.short_description) {
                metaDescEl.value = gameDetails.short_description;
            }

            // Actualizar contadores SEO
            updateSEOCounters();

            // Cerrar modal
            modal.hide();

            // Mostrar notificación de éxito
            const successMessage = result.message || `✅ Información de "${gameDetails.title}" cargada exitosamente!`;
            const platformInfo = platform ? `\n🎮 Plataforma seleccionada: ${platform}` : '';
            alert(successMessage + platformInfo + `\n\n✅ Datos rellenados en ESPAÑOL:\n- Descripción\n- Géneros\n- Plataforma/Consola\n- Marca/Publisher\n- Meta datos SEO\n- Imágenes del producto`);

        } catch (error) {
            console.error('Error cargando datos del juego:', error);

            resultsDiv.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                    <p class="text-muted"><strong>Error al cargar datos del juego</strong></p>
                    <p class="small text-muted">${error.message}</p>
                    <button class="btn btn-primary mt-3" data-bs-dismiss="modal">Cerrar</button>
                </div>
            `;
        }
    }

    // Configurar el botón de auto-rellenar
    const autoCompleteBtn = document.getElementById('autoCompleteBtn');
    if (autoCompleteBtn) {
        autoCompleteBtn.addEventListener('click', async function() {
            const title = document.getElementById('name').value.trim();
            
            if (!title) {
                alert('Por favor ingrese primero el nombre del producto');
                document.getElementById('name').focus();
                return;
            }
            
            // Abrir modal y buscar juegos
            const modal = new bootstrap.Modal(document.getElementById('gameSearchModal'));
            const searchInput = document.getElementById('gameSearchInput');
            const resultsDiv = document.getElementById('gameSearchResults');
            
            searchInput.value = title;
            modal.show();
            
            // Buscar juegos en RAWG API
            try {
                resultsDiv.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <p class="text-muted">Buscando "${title}"...</p>
                    </div>
                `;
                
                console.log('Buscando:', title);
                
                // Intentar con el endpoint multi-fuente primero
                let response = await fetch(`ajax/search_game_multi.php?action=search&query=${encodeURIComponent(title)}`);
                
                // Si falla, intentar con el endpoint principal
                if (!response.ok) {
                    console.warn('Multi-source failed, trying RAWG directly...');
                    response = await fetch(`ajax/search_game_rawg.php?action=search&query=${encodeURIComponent(title)}`);
                }
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Response error:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Resultados:', result);
                console.log('📊 Total de resultados recibidos de la API:', result.data?.results?.length || 0);
                console.log('📍 Fuente de datos:', result.source);

                if (!result.success) {
                    throw new Error(result.error || 'Error desconocido');
                }
                
                const data = result.data;
                
                // Mostrar fuente en consola
                if (result.source) {
                    console.log('Fuente de datos:', result.source);
                    console.log('Resultados encontrados:', data.results.length);
                }
                
                if (data.results && data.results.length > 0) {
                    await displayGameResults(data.results, resultsDiv, modal);
                } else {
                    // Sin resultados
                    if (result.source === 'RAWG') {
                        resultsDiv.innerHTML = `
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <p class="text-muted">No se encontraron resultados para "${title}"</p>
                                <p class="small text-muted">en la base de datos de RAWG</p>
                                <p class="small text-muted mt-3">Intente con:</p>
                                <ul class="small text-muted list-unstyled">
                                    <li>• Otro nombre más conocido</li>
                                    <li>• Solo el nombre sin la versión (ej: "Zelda" en lugar de "Zelda BOTW")</li>
                                    <li>• El nombre en inglés</li>
                                </ul>
                                <button class="btn btn-outline-secondary mt-3" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        `;
                    } else if (result.source === 'MOCK') {
                        // Si es MOCK, mostrar la opción mock para rellenar
                        await displayGameResults(data.results, resultsDiv, modal);
                    }
                }
            } catch (error) {
                console.error('Error buscando juegos:', error);
                resultsDiv.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                        <p class="text-muted"><strong>Error al buscar</strong></p>
                        <p class="small text-muted">${error.message}</p>
                        <p class="small text-muted mt-3">Puede:</p>
                        <ul class="small text-muted list-unstyled">
                            <li>✓ Rellenar los datos manualmente</li>
                            <li>✓ Intentar más tarde</li>
                            <li>✓ Contactar soporte si persiste</li>
                        </ul>
                        <button class="btn btn-primary mt-3" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                `;
            }
        });
    }
    
    // Mejorar área de drag & drop de imágenes
    const imageUploadArea = document.getElementById('imageUploadArea');
    const imageInput = document.getElementById('images');
    
    if (imageUploadArea && imageInput) {
        // Click en el área abre el selector de archivos
        imageUploadArea.addEventListener('click', function() {
            imageInput.click();
        });
        
        // Drag & drop
        imageUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('border-primary', 'bg-light');
        });
        
        imageUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-primary', 'bg-light');
        });
        
        imageUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-primary', 'bg-light');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                // Asignar archivos al input
                imageInput.files = files;
                // Disparar evento change
                imageInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
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
                <button type="button" class="btn btn-success" id="createAnotherBtn">
                    <i class="fas fa-plus me-2"></i>Crear Otro Producto
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
// Verificar si se creó un producto
$showModal = false;
$modalProductId = 0;
$modalProductName = '';

// Opción 1: Verificar SESSION y limpiar inmediatamente
if (!empty($_SESSION['product_created'])) {
    $showModal = true;
    $modalProductId = isset($_SESSION['product_id']) ? intval($_SESSION['product_id']) : 0;
    $modalProductName = isset($_SESSION['product_name']) ? $_SESSION['product_name'] : '';
    
    // Limpiar la sesión INMEDIATAMENTE después de leer los valores
    unset($_SESSION['product_created']);
    unset($_SESSION['product_id']);
    unset($_SESSION['product_name']);
    unset($_SESSION['product_slug']);
}
// Opción 2: Verificar GET como respaldo
elseif (!empty($_GET['success']) && $_GET['success'] == '1' && !empty($_GET['pid'])) {
    $showModal = true;
    $modalProductId = intval($_GET['pid']);
    $modalProductName = !empty($_GET['pname']) ? urldecode($_GET['pname']) : 'Producto';
}

// Si hay que mostrar el modal
if ($showModal):
    $modalProductNameJS = json_encode($modalProductName, JSON_HEX_QUOT | JSON_HEX_TAG);
?>
<script>
(function() {
    function initModal() {
        if (typeof bootstrap === 'undefined') {
            setTimeout(initModal, 50);
            return;
        }
        
        try {
            const nameEl = document.getElementById('productNameDisplay');
            const idEl = document.getElementById('productIdDisplay');
            const editBtn = document.getElementById('editProductBtn');
            const modalEl = document.getElementById('successModal');
            
            if (nameEl && idEl && editBtn && modalEl) {
                nameEl.textContent = <?php echo $modalProductNameJS; ?>;
                idEl.textContent = '<?php echo $modalProductId; ?>';
                editBtn.href = 'product_edit.php?id=<?php echo $modalProductId; ?>';
                
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                
                // Prevenir reenvío del formulario al recargar (PRG pattern)
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                
                // Limpiar sesión via AJAX después de mostrar
                fetch('clear_product_session.php')
                    .then(r => r.json())
                    .then(data => console.log('✅ Session cleaned:', data))
                    .catch(e => console.error('❌ Error cleaning session:', e));
                
                // Limpiar URL cuando se cierre el modal
                modalEl.addEventListener('hidden.bs.modal', function() {
                    if (window.history.replaceState) {
                        window.history.replaceState({}, document.title, 'product_create.php');
                    }
                });
                
                // Manejar botón "Crear Otro Producto"
                const createAnotherBtn = document.getElementById('createAnotherBtn');
                if (createAnotherBtn) {
                    createAnotherBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Cerrar el modal primero
                        modal.hide();
                        
                        // Esperar a que el modal se cierre completamente
                        modalEl.addEventListener('hidden.bs.modal', function onHidden() {
                            // Remover el listener para evitar múltiples ejecuciones
                            modalEl.removeEventListener('hidden.bs.modal', onHidden);
                            
                            // Limpiar la sesión
                            fetch('clear_product_session.php')
                                .then(r => r.json())
                                .then(data => {
                                    console.log('✅ Session cleared:', data);
                                    // Redirigir a página limpia
                                    window.location.href = 'product_create.php';
                                })
                                .catch(e => {
                                    console.error('❌ Error cleaning session:', e);
                                    // Aún así recargar
                                    window.location.href = 'product_create.php';
                                });
                        });
                    });
                }
            }
        } catch(e) {
            console.error('❌ Error al mostrar modal:', e);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModal);
    } else {
        initModal();
    }
})();
</script>
<?php endif; ?>

<?php require_once 'inc/footer.php'; ?>
