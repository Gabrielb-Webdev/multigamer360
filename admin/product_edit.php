<?php
/**
 * EDITAR PRODUCTO EXISTENTE
 * Este archivo es SOLO para editar productos existentes (requiere ID)
 * Para crear nuevos productos, usar product_create.php
 * 
 * Version: 4.3.0 - MEJORA: Layout unificado con product_create.php
 * Fecha: 14 Feb 2026
 * Cambios:
 *  - ✅ MEJORA: Agregado campo "Tipo de Producto" (game/console/accessory)
 *  - ✅ MEJORA: Layout de 3 columnas (4-4-4) igual a product_create.php
 *  - ✅ UX: Formulario ahora tiene apariencia consistente con la página de creación
 *  - ✅ ESTRUCTURA: Mismo orden de campos que product_create.php
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
    
    // IMPORTANTE: Sincronizar status e is_active al cargar
    // Si status está vacío, usar is_active como referencia
    if (empty($product['status']) && isset($product['is_active'])) {
        $product['status'] = ($product['is_active'] == 1) ? 'active' : 'inactive';
    }
    // Si status existe, asegurar que is_active esté sincronizado
    if (!empty($product['status'])) {
        $product['is_active'] = ($product['status'] === 'active') ? 1 : 0;
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
            'is_active' => ($_POST['status'] ?? 'active') === 'active' ? 1 : 0,
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

<!-- CSS específico para product_edit.php -->
<link href="assets/css/product-edit.css?v=1.0" rel="stylesheet">

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
                                        <option value="game" <?php echo ($product['product_type'] ?? 'game') === 'game' ? 'selected' : ''; ?>>Juego</option>
                                        <option value="console" <?php echo ($product['product_type'] ?? '') === 'console' ? 'selected' : ''; ?>>Consola</option>
                                        <option value="accessory" <?php echo ($product['product_type'] ?? '') === 'accessory' ? 'selected' : ''; ?>>Accesorio</option>
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
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i>Estado *
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>
                                            ✓ Activo
                                        </option>
                                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                            ✗ Inactivo
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        <span id="status-indicator" class="badge bg-<?php echo ($product['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ($product['status'] ?? 'active') === 'active' ? '● Producto Activo' : '● Producto Inactivo'; ?>
                                        </span>
                                    </div>
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
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                       value="1" <?php echo (!empty($product['is_featured'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    Producto Destacado
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_new" name="is_new" 
                                       value="1" <?php echo (!empty($product['is_new'])) ? 'checked' : ''; ?>>
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
                                       title="Precio en pesos argentinos"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control price-input" id="price_pesos" name="price_pesos" 
                                           value="<?php echo number_format($product['price_pesos'] ?? 0, 0, '', '.'); ?>" 
                                           required data-raw-value="<?php echo $product['price_pesos'] ?? '0'; ?>">
                                    <span class="input-group-text">ARS</span>
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
                                <label for="stock_quantity" class="form-label">Cantidad en Stock *</label>
                                <input type="number" class="form-control form-control-lg" id="stock_quantity" name="stock_quantity" 
                                       value="<?php echo $product['stock_quantity'] ?? '0'; ?>" 
                                       placeholder="0" min="0" required>
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
                                            <option value="<?php echo $brand['id']; ?>" 
                                                    <?php echo ($product['brand_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
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
                                            <option value="<?php echo $console['id']; ?>" 
                                                    <?php echo ($product['console_id'] ?? '') == $console['id'] ? 'selected' : ''; ?>>
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
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="card shadow-sm border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>Guardar Cambios
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg shadow-sm" id="updateProductBtn">
                                    <i class="fas fa-save me-2"></i>
                                    Actualizar Producto
                                </button>
                                
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver a Productos
                                </a>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Los cambios se aplicarán inmediatamente</small>
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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

<!-- Modal de Éxito: Información Cargada -->
<div class="modal fade" id="successInfoModal" tabindex="-1" aria-labelledby="successInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="successInfoModalLabel">
                    <i class="fas fa-check-circle me-2"></i>¡Información Cargada!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="success-animation mb-3">
                        <i class="fas fa-gamepad fa-4x text-success"></i>
                    </div>
                    <h5 class="text-success fw-bold mb-2" id="successGameTitle">Kingdom Hearts</h5>
                    <p class="text-muted mb-0" id="successPlatformInfo">
                        <i class="fas fa-gamepad me-1"></i>PlayStation 2
                    </p>
                </div>
                
                <div class="alert alert-success border-0 mb-3" role="alert">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-language fa-2x me-3 text-success"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Traducido al Español</h6>
                            <small class="text-muted">Toda la información ha sido traducida automáticamente</small>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-check-double text-success me-2"></i>Datos Rellenados:
                        </h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Descripción completa</strong> <span class="text-muted">(en español)</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Géneros del juego</strong> <span class="text-muted">(traducidos)</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Plataforma/Consola</strong> <span class="text-muted">(asignada)</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Marca/Publisher</strong> <span class="text-muted">(verificado)</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Meta datos SEO</strong> <span class="text-muted">(optimizados)</span>
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Imágenes del producto</strong> <span class="text-muted" id="imageCount">(descargadas)</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Revisa los campos y ajusta los precios antes de guardar
                    </small>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
                    <i class="fas fa-thumbs-up me-2"></i>¡Entendido!
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SortableJS para Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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

    // Array para acumular archivos seleccionados (GLOBAL para acceso desde auto-rellenar)
    window.selectedFiles = [];
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
                    window.selectedFiles.push(file);
                }
            });
            
            // Regenerar vista previa
            window.renderImagePreview();
            
            console.log('Total de imágenes seleccionadas:', window.selectedFiles.length);
        });
    }
    
    // Función global para renderizar preview (accesible desde auto-rellenar)
    window.renderImagePreview = function() {
        if (!preview) return;
        
        preview.innerHTML = '';
        
        if (window.selectedFiles.length === 0) {
            if (dragInfo) dragInfo.style.display = 'none';
            return;
        }
        
        // Mostrar info de drag & drop
        if (dragInfo) dragInfo.style.display = 'block';
        
        window.selectedFiles.forEach((file, index) => {
            // Capturar el índice antes del callback asíncrono
            const imageIndex = index;
            const isFirstImage = index === 0;
            const isLastImage = index === window.selectedFiles.length - 1;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3 sortable-image-item';
                col.dataset.index = imageIndex;
                
                const cardHTML = '<div class="card border-success h-100" style="cursor: move;">' +
                    '<div class="card-header bg-success text-white py-1 d-flex justify-content-between align-items-center">' +
                        '<small><i class="fas fa-grip-vertical"></i> #' + (imageIndex + 1) + '</small>' +
                        '<button type="button" class="btn btn-sm btn-close btn-close-white" ' +
                            'onclick="removePreviewImage(' + imageIndex + ')" aria-label="Eliminar"></button>' +
                    '</div>' +
                    '<img src="' + e.target.result + '" class="card-img-top" ' +
                         'style="height: 150px; object-fit: cover;" alt="Vista previa">' +
                    '<div class="card-body p-2 text-center">' +
                        (isFirstImage ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>' : '<span class="badge bg-secondary">Extra</span>') +
                    '</div>' +
                '</div>';
                
                col.innerHTML = cardHTML;
                preview.appendChild(col);
                
                // Inicializar SortableJS después de agregar todas las imágenes
                if (isLastImage) {
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
                const movedFile = window.selectedFiles.splice(oldIndex, 1)[0];
                window.selectedFiles.splice(newIndex, 0, movedFile);
                
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
        window.selectedFiles.splice(index, 1);
        window.renderImagePreview();
        console.log('Imágenes restantes:', window.selectedFiles.length);
    };
    
    // ============================================================================
    // SUBMIT HANDLER ÚNICO (v2.33) - Procesa: Imágenes + Precios + Descuentos
    // ============================================================================
    const form = document.getElementById('product-form');
    console.log('🎯 FORM ENCONTRADO:', form ? 'SI' : 'NO', form?.id);
    
    if (form) {
        console.log('✅ Registrando submit handler...');
        form.addEventListener('submit', function(e) {
            console.log('');
            console.log('='.repeat(80));
            console.log('🚀 SUBMIT - INICIANDO VALIDACIÓN');
            console.log('='.repeat(80));
            
            // PASO 1: Actualizar imágenes
            if (imagesInput) {
                const dt = new DataTransfer();
                window.selectedFiles.forEach(file => dt.items.add(file));
                imagesInput.files = dt.files;
                console.log('📸 Imágenes actualizadas:', window.selectedFiles.length);
            }
            
            // PASO 2: Limpiar precios
            console.log('');
            console.log('💰 PASO 2: LIMPIANDO PRECIOS...');
            let hasError = false;
            
            document.querySelectorAll('.price-input').forEach(input => {
                // PRIORIDAD 1: data-raw-value (ya está limpio por los event listeners)
                // PRIORIDAD 2: input.value limpiado manualmente
                const dataRaw = input.getAttribute('data-raw-value') || '';
                const origValue = input.value || '';
                
                console.log(`  📋 ${input.id}:`);
                console.log(`     - value visble: "${origValue}"`);
                console.log(`     - data-raw-value: "${dataRaw}"`);
                
                let cleaned = '';
                
                // USAR data-raw-value si existe y no está vacío
                if (dataRaw && dataRaw !== '' && dataRaw !== '0') {
                    cleaned = dataRaw.replace(/[^\d]/g, '');
                    console.log(`     ✅ Usando data-raw-value: "${cleaned}"`);
                } else {
                    // Fallback: limpiar manualmente el valor visible
                    cleaned = origValue.replace(/\./g, '').replace(/,/g, '').replace(/\s/g, '').replace(/[^\d]/g, '');
                    console.log(`     ⚠️ Limpiando value manualmente: "${cleaned}"`);
                }
                
                // Validar price_pesos (campo obligatorio)
                if (input.id === 'price_pesos') {
                    if (!cleaned || cleaned === '' || cleaned === '0') {
                        console.log('');
                        console.error('❌❌❌ ERROR CRÍTICO: price_pesos vacío o es 0!');
                        console.error('   - origValue:', origValue);
                        console.error('   - dataRaw:', dataRaw);
                        console.error('   - cleaned:', cleaned);
                        alert('ERROR: El Precio en ARS es obligatorio y debe ser mayor a 0.');
                        input.focus();
                        input.style.border = '3px solid red';
                        e.preventDefault();
                        hasError = true;
                        return;
                    }
                    console.log(`     ✅✅✅ price_pesos VÁLIDO: "${cleaned}"`);
                }
                
                if (!cleaned) cleaned = '0';
                
                console.log(`     ➡️ ASIGNANDO input.value = "${cleaned}"`);
                input.value = cleaned;
            });
            
            if (hasError) {
                console.error('❌ SUBMIT CANCELADO POR ERROR');
                return false;
            }
            
            // PASO 3: Limpiar descuentos
            console.log('');
            console.log('🎯 PASO 3: LIMPIANDO DESCUENTOS...');
            document.querySelectorAll('.discount-input').forEach(input => {
                const rawValue = input.value.replace(/\D/g, '') || '0';
                console.log(`  ${input.id}: "${input.value}" -> "${rawValue}"`);
                input.value = rawValue;
            });
            
            console.log('');
            console.log('✅✅✅ SUBMIT - ENVIANDO FORMULARIO AL SERVIDOR...');
            console.log('='.repeat(80));
            console.log('');
        });
        console.log('✅ Submit handler registrado correctamente');
    } else {
        console.error('❌ NO SE ENCONTRÓ EL FORMULARIO #product-form');
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
    
    // ========================================================================
    // NOTA: Vista previa de descuentos ahora manejada por initPriceFormatting()
    // El código viejo fue eliminado para evitar conflictos con el nuevo sistema
    // ========================================================================
    
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

            // Variable para almacenar imágenes descargadas
            let downloadedImagesCount = 0;

            // Descargar y subir imágenes si están disponibles
            if (gameDetails.images && gameDetails.images.length > 0) {
                console.log('📸 Descargando', gameDetails.images.length, 'imágenes del juego...');

                const downloadedFiles = [];
                let successCount = 0;

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
                            console.log('✅ Imagen descargada:', imageResult.file_path);
                            
                            // Convertir la imagen descargada a un objeto File
                            // Ajustar ruta relativa: estamos en admin/, necesitamos subir un nivel
                            const imagePath = '../' + imageResult.file_path;
                            console.log('📂 Ruta ajustada:', imagePath);
                            
                            const response = await fetch(imagePath);
                            const blob = await response.blob();
                            const file = new File([blob], imageResult.file_name, { type: imageResult.mime_type || 'image/jpeg' });
                            
                            console.log('📦 File object creado:', file.name, file.size, 'bytes');
                            
                            // Agregar al array de archivos seleccionados
                            downloadedFiles.push(file);
                            successCount++;
                        }
                    } catch (imageError) {
                        console.error('❌ Error descargando imagen:', imageError);
                    }
                }

                if (downloadedFiles.length > 0) {
                    console.log(`✅ ${downloadedFiles.length} imágenes descargadas y convertidas`);
                    
                    console.log('🔍 Estado ANTES de agregar:');
                    console.log('  - window.selectedFiles existe?', typeof window.selectedFiles);
                    console.log('  - window.renderImagePreview existe?', typeof window.renderImagePreview);
                    console.log('  - window.selectedFiles.length:', window.selectedFiles ? window.selectedFiles.length : 'undefined');
                    
                    // Agregar todas las imágenes al array global selectedFiles
                    downloadedFiles.forEach(file => {
                        console.log('  → Agregando:', file.name);
                        window.selectedFiles.push(file);
                    });
                    
                    console.log('🔍 Estado DESPUÉS de agregar:');
                    console.log('  - window.selectedFiles.length:', window.selectedFiles.length);
                    
                    // Regenerar la vista previa para mostrar las imágenes
                    console.log('🎨 Llamando a window.renderImagePreview()...');
                    window.renderImagePreview();
                    
                    // Actualizar contador
                    downloadedImagesCount = successCount;
                    
                    // Mostrar mensaje de éxito
                    console.log(`🎉 ${successCount} imágenes agregadas al producto`);
                }
            }

            // Rellenar nombre
            const nameEl = document.getElementById('name');
            if (nameEl && gameDetails.title) {
                nameEl.value = gameDetails.title;
                console.log('✓ Nombre rellenado:', gameDetails.title);
            }

            // Rellenar descripción (YA ESTÁ EN ESPAÑOL)
            const descriptionEl = document.getElementById('description');
            if (descriptionEl && gameDetails.description) {
                descriptionEl.value = gameDetails.description;
                console.log('✓ Descripción rellenada:', gameDetails.description.substring(0, 50) + '...');
            } else {
                console.warn('⚠️ No se pudo rellenar descripción:', {
                    elementExists: !!descriptionEl,
                    hasDescription: !!gameDetails.description,
                    description: gameDetails.description
                });
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

            // Cerrar modal de búsqueda
            modal.hide();

            // Mostrar modal de éxito elegante
            const successModal = new bootstrap.Modal(document.getElementById('successInfoModal'));
            
            // Actualizar contenido del modal
            document.getElementById('successGameTitle').textContent = gameDetails.title || 'Juego';
            document.getElementById('successPlatformInfo').innerHTML = platform 
                ? `<i class="fas fa-gamepad me-1"></i>${platform}` 
                : '<i class="fas fa-gamepad me-1"></i>Información cargada';
            
            // Actualizar contador de imágenes
            const imageCountEl = document.getElementById('imageCount');
            if (imageCountEl) {
                if (downloadedImagesCount > 0) {
                    imageCountEl.textContent = `(${downloadedImagesCount} ${downloadedImagesCount === 1 ? 'imagen descargada' : 'imágenes descargadas'})`;
                    imageCountEl.className = 'text-success';
                } else {
                    imageCountEl.textContent = '(disponibles)';
                }
            }
            
            // Mostrar el modal de éxito
            successModal.show();

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

<!-- ==========================================
     FORMATO DE PRECIOS CON SEPARADOR DE MILES
     Este script se ejecuta SIEMPRE (no depende de modal)
     ========================================== -->
<script>
(function() {
    'use strict';
    
    function initPriceFormatting() {
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
        
        // Función UNIFICADA para actualizar preview de descuento (se llama desde precio Y descuento)
        function updateDiscountPreview(currency) {
            // currency = 'ars' o 'usd'
            const priceId = currency === 'ars' ? 'price_pesos' : 'price_dollars';
            const discountId = currency === 'ars' ? 'discount_percentage_ars' : 'discount_percentage_usd';
            const previewId = currency === 'ars' ? 'discount-preview-ars' : 'discount-preview-usd';
            
            const priceEl = document.getElementById(priceId);
            const discountEl = document.getElementById(discountId);
            const previewEl = document.getElementById(previewId);
            
            if (!priceEl || !discountEl || !previewEl) return;
            
            // Obtener valores
            const priceRaw = priceEl.getAttribute('data-raw-value') || '0';
            const priceValue = parseInt(priceRaw);
            const discountValue = parseInt(discountEl.value || '0');
            
            console.log(`🔄 updateDiscountPreview(${currency}): precio=${priceValue}, descuento=${discountValue}%`);
            
            // Actualizar preview
            if (discountValue > 0 && priceValue > 0) {
                const discountedPrice = priceValue * (1 - (discountValue / 100));
                previewEl.innerHTML = `
                    <span class="text-danger"><del>$${formatNumberWithThousands(String(priceValue))}</del></span> → 
                    <span class="text-success fw-bold">$${formatNumberWithThousands(String(Math.round(discountedPrice)))}</span> 
                    <span class="badge bg-success">-${discountValue}%</span>
                `;
                previewEl.classList.add('text-success');
            } else {
                previewEl.textContent = 'Sin descuento';
                previewEl.classList.remove('text-success');
            }
        }
        
        // Manejar campos de precio
        document.querySelectorAll('.price-input').forEach(input => {
            // Inicializar data-raw-value desde el principio
            const initialRawValue = getRawValue(input.value || '0');
            input.setAttribute('data-raw-value', initialRawValue);
            
            // Formatear valor inicial si existe
            if (input.value) {
                input.value = formatNumberWithThousands(input.value);
            }
            
            console.log(`📋 Campo inicializado: ${input.id} = "${input.value}" (raw: "${initialRawValue}")`);
            
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
                
                // Actualizar preview de descuento
                const currency = this.id === 'price_pesos' ? 'ars' : 'usd';
                updateDiscountPreview(currency);
            });
            
            // Al perder el foco, asegurar formato correcto
            input.addEventListener('blur', function() {
                const rawValue = getRawValue(this.value) || '0';
                this.setAttribute('data-raw-value', rawValue);
                console.log(`📤 Blur en ${this.id}: data-raw-value = "${rawValue}"`);
                
                if (rawValue && rawValue !== '0') {
                    this.value = formatNumberWithThousands(rawValue);
                } else {
                    this.value = ''; // Dejar vacío visualmente si es 0
                    this.setAttribute('data-raw-value', '0');
                }
                // Actualizar preview de descuento
                const currency = this.id === 'price_pesos' ? 'ars' : 'usd';
                updateDiscountPreview(currency);
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
                
                // Actualizar preview de descuento usando función unificada
                const currency = this.id.includes('ars') ? 'ars' : 'usd';
                updateDiscountPreview(currency);
            });
        });
        
        console.log('✅ Price formatting system initialized - v2.32');
    }
    
    // Ejecutar inmediatamente si el DOM está listo, o esperar al evento
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPriceFormatting);
    } else {
        initPriceFormatting();
    }
})();
</script>

<script>
    // ============================================
    // REGENERAR SLUG
    // ============================================
const regenerateSlugBtn = document.getElementById('regenerate_slug');
const slugPreview = document.getElementById('slug_preview');
const nameInput = document.getElementById('name');

if (regenerateSlugBtn && slugPreview && nameInput) {
    regenerateSlugBtn.addEventListener('click', function() {
        if (!nameInput.value) {
            alert('Primero ingresa el nombre del producto');
            nameInput.focus();
            return;
        }

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

    // ============================================
    // ACTUALIZAR INDICADOR DE ESTADO DINÁMICAMENTE
    // ============================================
const statusSelect = document.getElementById('status');
const statusIndicator = document.getElementById('status-indicator');

if (statusSelect && statusIndicator) {
    statusSelect.addEventListener('change', function() {
        const isActive = this.value === 'active';
        
        // Actualizar badge
        statusIndicator.className = 'badge bg-' + (isActive ? 'success' : 'danger');
        statusIndicator.innerHTML = isActive ? '● Producto Activo' : '● Producto Inactivo';
        
        // Animación sutil
        statusIndicator.style.transform = 'scale(1.1)';
        setTimeout(() => {
            statusIndicator.style.transform = 'scale(1)';
        }, 200);
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
            return;
        }
        
        let currentIndex = 0;
        
        // Renderizar imágenes existentes primero
        existingImages.forEach((img, index) => {
            const col = document.createElement('div');
            col.className = 'col-md-3 sortable-image-item';
            col.dataset.index = currentIndex;
            col.dataset.imageId = img.id;
            col.dataset.isExisting = 'true';
            
            const cardHTML = '<div class="card border-primary h-100" style="cursor: move;">' +
                '<div class="card-header bg-primary text-white py-1 d-flex justify-content-between align-items-center">' +
                    '<small><i class="fas fa-grip-vertical"></i> #' + (currentIndex + 1) + '</small>' +
                    '<button type="button" class="btn btn-sm btn-close btn-close-white" ' +
                        'onclick="removeExistingImage(' + img.id + ')" aria-label="Eliminar"></button>' +
                '</div>' +
                '<img src="' + img.url + '" class="card-img-top" ' +
                     'style="height: 150px; object-fit: cover;" alt="Imagen existente">' +
                '<div class="card-body p-2 text-center">' +
                    (currentIndex === 0 ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>' : '<span class="badge bg-info">Existente</span>') +
                '</div>' +
            '</div>';
            
            col.innerHTML = cardHTML;
            preview.appendChild(col);
            currentIndex++;
        });
        
        // Renderizar imágenes nuevas
        selectedFiles.forEach((file, index) => {
            // Capturar valores antes del callback asíncrono
            const displayIndex = currentIndex;  // Para mostrar el número correcto
            const arrayIndex = index;  // Para eliminar del array correcto
            const isFirstImage = currentIndex === 0;
            
            currentIndex++;  // Incrementar inmediatamente
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3 sortable-image-item';
                col.dataset.index = displayIndex;
                col.dataset.isExisting = 'false';
                
                const cardHTML = '<div class="card border-success h-100" style="cursor: move;">' +
                    '<div class="card-header bg-success text-white py-1 d-flex justify-content-between align-items-center">' +
                        '<small><i class="fas fa-grip-vertical"></i> #' + (displayIndex + 1) + '</small>' +
                        '<button type="button" class="btn btn-sm btn-close btn-close-white" ' +
                            'onclick="removeNewImage(' + arrayIndex + ')" aria-label="Eliminar"></button>' +
                    '</div>' +
                    '<img src="' + e.target.result + '" class="card-img-top" ' +
                         'style="height: 150px; object-fit: cover;" alt="Nueva imagen">' +
                    '<div class="card-body p-2 text-center">' +
                        (isFirstImage ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> PORTADA</span>' : '<span class="badge bg-secondary">Nueva</span>') +
                    '</div>' +
                '</div>';
                
                col.innerHTML = cardHTML;
                preview.appendChild(col);
                
                // Inicializar Sortable después de agregar la última imagen
                if (displayIndex + 1 === totalImages) {
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
            // Limpiar descuentos
            const discountArs = document.getElementById('discount_percentage_ars');
            const discountUsd = document.getElementById('discount_percentage_usd');
            if (discountArs) discountArs.value = '0';
            if (discountUsd) discountUsd.value = '0';
        }
    });
}

// Alerta de stock bajo
function updateStockAlert() {
    const stockInput = document.getElementById('stock_quantity');
    if (!stockInput) return;
    
    const stock = parseInt(stockInput.value) || 0;
    const alert = document.getElementById('stock-alert');
    
    // Validar que el elemento existe
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
}

const stockInput = document.getElementById('stock_quantity');
if (stockInput) {
    stockInput.addEventListener('input', updateStockAlert);
}

// Inicializar solo si el elemento existe
if (document.getElementById('stock-alert')) {
    updateStockAlert();
}

// ============================================
// GENERAR SKU AUTOMÁTICO
// ============================================

const nameInputForSku = document.getElementById('name');
// Auto-generar SKU cuando sale del campo nombre (blur)
// Versión 2.1 - SKU con formato: PREFIX(6)-TIMESTAMP(5)-RANDOM(4)
if (nameInputForSku) {
    nameInputForSku.addEventListener('blur', function() {
    const skuField = document.getElementById('sku');
    if (!skuField) return;
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
        const isOnSale = document.getElementById('is_on_sale');
        const discountArs = document.getElementById('discount_percentage_ars');
        const discountUsd = document.getElementById('discount_percentage_usd');
        
        // Validar porcentajes de descuento si está en oferta
        if (isOnSale && isOnSale.checked) {
            if (discountArs) {
                const discountArsValue = parseFloat(discountArs.value) || 0;
                if (discountArsValue < 0 || discountArsValue > 100) {
                    e.preventDefault();
                    alert('El porcentaje de descuento ARS debe estar entre 0 y 100');
                    discountArs.focus();
                    return false;
                }
            }
            
            if (discountUsd) {
                const discountUsdValue = parseFloat(discountUsd.value) || 0;
                if (discountUsdValue < 0 || discountUsdValue > 100) {
                    e.preventDefault();
                    alert('El porcentaje de descuento USD debe estar entre 0 y 100');
                    discountUsd.focus();
                    return false;
                }
            }
        }
        
        // Validar que tenga al menos una categoría
        const categorySelect = document.getElementById('category_id');
        if (categorySelect && !categorySelect.value) {
            e.preventDefault();
            alert('Debe seleccionar una categoría');
            categorySelect.focus();
            return false;
        }
        
        // Permitir que el formulario se envíe normalmente
        console.log('✅ Formulario válido, enviando...');
    });
}

// ============================================
// BOTONES DE AGREGAR CATEGORÍA, MARCA, CONSOLA Y GÉNERO
// ============================================

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
    const nameInput = document.getElementById('newCategoryName');
    if (!nameInput) return;
    const name = nameInput.value.trim();
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
            if (select) {
                const option = new Option(name, data.id, true, true);
                select.add(option);
            }
            const modalEl = document.getElementById('addCategoryModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            const nameInput = document.getElementById('newCategoryName');
            if (nameInput) nameInput.value = '';
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
    const nameInput = document.getElementById('newBrandName');
    if (!nameInput) return;
    const name = nameInput.value.trim();
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
            if (select) {
                const option = new Option(name, data.id, true, true);
                select.add(option);
            }
            const modalEl = document.getElementById('addBrandModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            const nameInput = document.getElementById('newBrandName');
            if (nameInput) nameInput.value = '';
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
    const nameInput = document.getElementById('newConsoleName');
    if (!nameInput) return;
    const name = nameInput.value.trim();
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
            if (select) {
                const option = new Option(name, data.id, true, true);
                select.add(option);
            }
            const modalEl = document.getElementById('addConsoleModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            const nameInput = document.getElementById('newConsoleName');
            if (nameInput) nameInput.value = '';
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
    const nameInput = document.getElementById('newGenreName');
    if (!nameInput) return;
    const name = nameInput.value.trim();
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
            const modalEl = document.getElementById('addGenreModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            const nameInput = document.getElementById('newGenreName');
            if (nameInput) nameInput.value = '';
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

// ============================================
// SEO - CONTADORES Y AUTO-GENERAR
// ============================================

// Actualizar contadores de SEO
function updateSEOCounters() {
    const metaTitleEl = document.getElementById('meta_title');
    const metaDescEl = document.getElementById('meta_description');
    const titleCounterEl = document.getElementById('meta-title-counter');
    const descCounterEl = document.getElementById('meta-desc-counter');
    
    if (metaTitleEl && titleCounterEl) {
        titleCounterEl.textContent = `(${metaTitleEl.value.length}/60)`;
    }
    
    if (metaDescEl && descCounterEl) {
        descCounterEl.textContent = `(${metaDescEl.value.length}/160)`;
    }
}

// Auto-generar meta tags desde el nombre y descripción
const autoGenerateSeoBtn = document.getElementById('auto-generate-seo');
if (autoGenerateSeoBtn) {
    autoGenerateSeoBtn.addEventListener('click', function() {
        const nameEl = document.getElementById('name');
        const descriptionEl = document.getElementById('description');
        const metaTitleEl = document.getElementById('meta_title');
        const metaDescEl = document.getElementById('meta_description');
        
        if (!nameEl) return;
        
        const productName = nameEl.value.trim();
        const description = descriptionEl ? descriptionEl.value.trim() : '';
        
        if (!productName) {
            alert('Primero ingrese el nombre del producto');
            nameEl.focus();
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
        
        if (metaTitleEl) metaTitleEl.value = metaTitle;
        if (metaDescEl) metaDescEl.value = metaDesc;
        
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
}

// Actualizar contadores en tiempo real
const metaTitleInput = document.getElementById('meta_title');
const metaDescInput = document.getElementById('meta_description');

if (metaTitleInput) {
    metaTitleInput.addEventListener('input', updateSEOCounters);
}
if (metaDescInput) {
    metaDescInput.addEventListener('input', updateSEOCounters);
}

// Inicializar contadores al cargar la página
updateSEOCounters();

// Nota: Los tooltips de Bootstrap se inicializan al final del documento
</script>

<!-- ==========================================
     FORMATO DE PRECIOS CON SEPARADOR DE MILES
     Este script se ejecuta SIEMPRE (no depende de modal)
     ========================================== -->
<script>
(function() {
    'use strict';
    
    function initPriceFormatting() {
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
                        const priceId = this.id.includes('ars') ? 'price_pesos' : 'price_dollars';
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
        const form = document.getElementById('product-form');
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
    }
    
    // Ejecutar inmediatamente si el DOM está listo, o esperar al evento
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPriceFormatting);
    } else {
        initPriceFormatting();
    }

    // ==========================================
    // BOTÓN DE AUTO-RELLENAR CON BÚSQUEDA MULTI-FUENTE
    // Version: 2.15 - Verificación de plataformas antes de mostrar
    // Agregado a product_edit.php para igualar funcionalidad con product_create.php
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
            if (!resultsDiv) return;
            
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
                            <p class="mb-0 mt-2">Selecciona la plataforma específica para la cual deseas actualizar el producto.</p>
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

            // Variable para almacenar imágenes descargadas
            let downloadedImagesCount = 0;

            // Descargar y subir imágenes si están disponibles
            if (gameDetails.images && gameDetails.images.length > 0) {
                console.log('📸 Descargando', gameDetails.images.length, 'imágenes del juego...');

                const downloadedFiles = [];
                let successCount = 0;

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
                            console.log('✅ Imagen descargada:', imageResult.file_path);
                            
                            // Convertir la imagen descargada a un objeto File
                            // Ajustar ruta relativa: estamos en admin/, necesitamos subir un nivel
                            const imagePath = '../' + imageResult.file_path;
                            console.log('📂 Ruta ajustada:', imagePath);
                            
                            const response = await fetch(imagePath);
                            const blob = await response.blob();
                            const file = new File([blob], imageResult.file_name, { type: imageResult.mime_type || 'image/jpeg' });
                            
                            console.log('📦 File object creado:', file.name, file.size, 'bytes');
                            
                            // Agregar al array de archivos seleccionados
                            downloadedFiles.push(file);
                            successCount++;
                        }
                    } catch (imageError) {
                        console.error('❌ Error descargando imagen:', imageError);
                    }
                }

                if (downloadedFiles.length > 0) {
                    console.log(`✅ ${downloadedFiles.length} imágenes descargadas y convertidas`);
                    
                    console.log('🔍 Estado ANTES de agregar:');
                    console.log('  - window.selectedFiles existe?', typeof window.selectedFiles);
                    console.log('  - window.renderImagePreview existe?', typeof window.renderImagePreview);
                    console.log('  - window.selectedFiles.length:', window.selectedFiles ? window.selectedFiles.length : 'undefined');
                    
                    // Agregar todas las imágenes al array global selectedFiles
                    downloadedFiles.forEach(file => {
                        console.log('  → Agregando:', file.name);
                        window.selectedFiles.push(file);
                    });
                    
                    console.log('🔍 Estado DESPUÉS de agregar:');
                    console.log('  - window.selectedFiles.length:', window.selectedFiles.length);
                    
                    // Regenerar la vista previa para mostrar las imágenes
                    console.log('🎨 Llamando a window.renderImagePreview()...');
                    window.renderImagePreview();
                    
                    // Actualizar contador
                    downloadedImagesCount = successCount;
                    
                    // Mostrar mensaje de éxito
                    console.log(`🎉 ${successCount} imágenes agregadas al producto`);
                }
            }

            // Rellenar nombre
            const nameEl = document.getElementById('name');
            if (nameEl && gameDetails.title) {
                nameEl.value = gameDetails.title;
                console.log('✓ Nombre rellenado:', gameDetails.title);
            }

            // Rellenar descripción (YA ESTÁ EN ESPAÑOL)
            const descriptionEl = document.getElementById('description');
            if (descriptionEl && gameDetails.description) {
                descriptionEl.value = gameDetails.description;
                console.log('✓ Descripción rellenada:', gameDetails.description.substring(0, 50) + '...');
            } else {
                console.warn('⚠️ No se pudo rellenar descripción:', {
                    elementExists: !!descriptionEl,
                    hasDescription: !!gameDetails.description,
                    description: gameDetails.description
                });
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

            // Actualizar contadores SEO si la función existe
            if (typeof updateSEOCounters === 'function') {
                updateSEOCounters();
            }

            // Cerrar modal de búsqueda
            modal.hide();

            // Mostrar modal de éxito elegante
            const successModalEl = document.getElementById('successInfoModal');
            if (successModalEl) {
                const successModal = new bootstrap.Modal(successModalEl);
                
                // Actualizar contenido del modal
                const successGameTitleEl = document.getElementById('successGameTitle');
                if (successGameTitleEl) {
                    successGameTitleEl.textContent = gameDetails.title || 'Juego';
                }
                
                const successPlatformInfoEl = document.getElementById('successPlatformInfo');
                if (successPlatformInfoEl) {
                    successPlatformInfoEl.innerHTML = platform 
                        ? `<i class="fas fa-gamepad me-1"></i>${platform}` 
                        : '<i class="fas fa-gamepad me-1"></i>Información cargada';
                }
            
                // Actualizar contador de imágenes
                const imageCountEl = document.getElementById('imageCount');
                if (imageCountEl) {
                    if (downloadedImagesCount > 0) {
                        imageCountEl.textContent = `(${downloadedImagesCount} ${downloadedImagesCount === 1 ? 'imagen descargada' : 'imágenes descargadas'})`;
                        imageCountEl.className = 'text-success';
                    } else {
                        imageCountEl.textContent = '(disponibles)';
                    }
                }
                
                // Mostrar el modal de éxito
                successModal.show();
            }

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
            const nameInput = document.getElementById('name');
            if (!nameInput) return;
            const title = nameInput.value.trim();
            
            if (!title) {
                alert('Por favor ingrese primero el nombre del producto');
                nameInput.focus();
                return;
            }
            
            // Abrir modal y buscar juegos
            const gameSearchModalEl = document.getElementById('gameSearchModal');
            if (!gameSearchModalEl) return;
            
            const searchInput = document.getElementById('gameSearchInput');
            if (!searchInput) return;
            
            const resultsDiv = document.getElementById('gameSearchResults');
            if (!resultsDiv) return;
            
            const modal = new bootstrap.Modal(gameSearchModalEl);
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

<!-- ==========================================
     FORMATO DE PRECIOS CON SEPARADOR DE MILES
     Este script se ejecuta SIEMPRE (no depende de modal)
     ========================================== -->
<script>
(function() {
    'use strict';
    
    function initPriceFormatting() {
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
        function getRawValue( formattedValue) {
            return formattedValue.replace(/\./g, '');
        }
        
        // Función para calcular nueva posición del cursor
        function getNewCursorPosition(oldValue, newValue, oldPosition) {
            const oldDots = (oldValue.substring(0, oldPosition).match(/\./g) || []).length;
            const rawPosition = oldPosition - oldDots;
            const newDots = (newValue.substring(0, rawPosition + Math.floor((newValue.length - rawPosition) / 4)).match(/\./g) || []).length;
            return rawPosition + newDots;
        }
        
        // Función UNIFICADA para actualizar preview de descuento (se llama desde precio Y descuento)
        function updateDiscountPreview(currency) {
            // currency = 'ars' o 'usd'
            const priceId = currency === 'ars' ? 'price_pesos' : 'price_dollars';
            const discountId = currency === 'ars' ? 'discount_percentage_ars' : 'discount_percentage_usd';
            const previewId = currency === 'ars' ? 'discount-preview-ars' : 'discount-preview-usd';
            
            const priceEl = document.getElementById(priceId);
            const discountEl = document.getElementById(discountId);
            const previewEl = document.getElementById(previewId);
            
            if (!priceEl || !discountEl || !previewEl) return;
            
            // Obtener valores
            const priceRaw = priceEl.getAttribute('data-raw-value') || '0';
            const priceValue = parseInt(priceRaw);
            const discountValue = parseInt(discountEl.value || '0');
            
            console.log(`🔄 updateDiscountPreview(${currency}): precio=${priceValue}, descuento=${discountValue}%`);
            
            // Actualizar preview
            if (discountValue > 0 && priceValue > 0) {
                const discountedPrice = priceValue * (1 - (discountValue / 100));
                previewEl.innerHTML = `
                    <span class="text-danger"><del>$${formatNumberWithThousands(String(priceValue))}</del></span> → 
                    <span class="text-success fw-bold">$${formatNumberWithThousands(String(Math.round(discountedPrice)))}</span> 
                    <span class="badge bg-success">-${discountValue}%</span>
                `;
                previewEl.classList.add('text-success');
            } else {
                previewEl.textContent = 'Sin descuento';
                previewEl.classList.remove('text-success');
            }
        }
        
        // Manejar campos de precio
        document.querySelectorAll('.price-input').forEach(input => {
            // Inicializar data-raw-value desde el principio
            const initialRawValue = getRawValue(input.value || '0');
            input.setAttribute('data-raw-value', initialRawValue);
            
            // Formatear valor inicial si existe
            if (input.value) {
                input.value = formatNumberWithThousands(input.value);
            }
            
            console.log(`📋 Campo inicializado: ${input.id} = "${input.value}" (raw: "${initialRawValue}")`);
            
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
                
                // Actualizar preview de descuento
                const currency = this.id === 'price_pesos' ? 'ars' : 'usd';
                updateDiscountPreview(currency);
            });
            
            // Al perder el foco, asegurar formato correcto
            input.addEventListener('blur', function() {
                const rawValue = getRawValue(this.value) || '0';
                this.setAttribute('data-raw-value', rawValue);
                console.log(`📤 Blur en ${this.id}: data-raw-value = "${rawValue}"`);
                
                if (rawValue && rawValue !== '0') {
                    this.value = formatNumberWithThousands(rawValue);
                } else {
                    this.value = ''; // Dejar vacío visualmente si es 0
                    this.setAttribute('data-raw-value', '0');
                }
                // Actualizar preview de descuento
                const currency = this.id === 'price_pesos' ? 'ars' : 'usd';
                updateDiscountPreview(currency);
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
                
                // Actualizar preview de descuento usando función unificada
                const currency = this.id.includes('ars') ? 'ars' : 'usd';
                updateDiscountPreview(currency);
            });
        });
        
        console.log('✅ Price formatting system initialized - v2.32');
    }
    
    // Ejecutar inmediatamente si el DOM está listo, o esperar al evento
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPriceFormatting);
    } else {
        initPriceFormatting();
    }
})();
</script>

<?php require_once 'inc/footer.php'; ?>

<script>
// Inicializar tooltips de Bootstrap después de que la biblioteca esté cargada
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
        console.log('✅ Tooltips de Bootstrap inicializados:', tooltipTriggerList.length);
    }
});
</script>
