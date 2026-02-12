<?php
/**
 * Product Details Page - MultiGamer360
 * Last Updated: 2025-10-17 23:30
 * Version: 3.0 - Dark Theme + Database Integration
 * Based on working demo version with dark theme styling
 */

// Activar reporte de errores temporalmente para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración de base de datos primero
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/product_manager.php';

// Crear instancia del manager de productos
$productManager = new ProductManager($pdo);

include 'includes/header.php';

// Obtener parámetros del producto (prioridad a slug para SEO)
$product_slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Obtener producto desde la base de datos
if ($product_slug) {
    // Buscar por slug (mejor para SEO)
    $current_product = $productManager->getProductBySlug($product_slug);
} elseif ($product_id) {
    // Fallback: buscar por ID
    $current_product = $productManager->getProductById($product_id);
} else {
    // Sin parámetros válidos
    header('Location: productos.php');
    exit;
}

// Si no se encuentra el producto, redirigir a productos
if (!$current_product) {
    header('Location: productos.php');
    exit;
}

// Obtener el ID del producto para las consultas siguientes
$product_id = $current_product['id'];

// Obtener todas las imágenes del producto
$product_images = $productManager->getProductImages($product_id);

// Determinar imagen principal (prioridad: primary_image > image_url)
$main_image = null;
if (!empty($current_product['primary_image'])) {
    // Usar imagen principal de product_images (is_primary = 1)
    $main_image = $current_product['primary_image'];
} elseif (!empty($current_product['image_url'])) {
    // Fallback a image_url de la tabla products
    $main_image = $current_product['image_url'];
}

// Asegurar que tengamos los valores necesarios con valores por defecto
$current_product['stock_quantity'] = $current_product['stock_quantity'] ?? 0;
$current_product['price_pesos'] = $current_product['price_pesos'] ?? 0;
$current_product['discount_percentage'] = $current_product['discount_percentage'] ?? 0;
$current_product['is_new'] = $current_product['is_new'] ?? 0;
$current_product['is_featured'] = $current_product['is_featured'] ?? 0;
$current_product['is_on_sale'] = $current_product['is_on_sale'] ?? 0;

// Obtener wishlist del usuario si está logueado
$isInWishlist = false;
$wishlist_debug = [
    'logged_in' => isLoggedIn(),
    'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A',
    'product_id' => $product_id,
    'count' => 0,
    'result' => false,
    'sql_executed' => false
];

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['total'];
        $isInWishlist = $count > 0;
        
        $wishlist_debug['count'] = $count;
        $wishlist_debug['result'] = $isInWishlist;
        $wishlist_debug['sql_executed'] = true;
        
        error_log("=== WISHLIST CHECK ===");
        error_log("User: " . $_SESSION['user_id'] . " | Product: " . $product_id . " | Count: " . $count . " | In Wishlist: " . ($isInWishlist ? 'YES' : 'NO'));
        
    } catch (Exception $e) {
        error_log("ERROR WISHLIST: " . $e->getMessage());
        $wishlist_debug['error'] = $e->getMessage();
    }
}

// DEBUG: Imprimir información visible en HTML
echo "<!-- ========================================= -->";
echo "<!-- WISHLIST DEBUG INFO (Product ID: {$product_id}) -->";
echo "<!-- ========================================= -->";
echo "<!-- Logged In: " . ($wishlist_debug['logged_in'] ? 'YES' : 'NO') . " -->";
echo "<!-- User ID: " . $wishlist_debug['user_id'] . " -->";
echo "<!-- Product ID: " . $wishlist_debug['product_id'] . " -->";
echo "<!-- SQL Executed: " . ($wishlist_debug['sql_executed'] ? 'YES' : 'NO') . " -->";
echo "<!-- Records Found: " . $wishlist_debug['count'] . " -->";
echo "<!-- isInWishlist: " . ($wishlist_debug['result'] ? 'TRUE' : 'FALSE') . " -->";
echo "<!-- ========================================= -->";

// ============================================================
// OBTENER PRODUCTOS SIMILARES DE LA BASE DE DATOS (BASADO EN GÉNEROS)
// ============================================================
$similar_products = [];
try {
    // ESTRATEGIA: Buscar productos que compartan géneros con el producto actual
    // Primero intentar productos con géneros en común, luego productos aleatorios
    
    // Query 1: Obtener productos que compartan géneros con el producto actual
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, p.name, p.price_pesos, p.image_url, p.stock_quantity, p.slug,
               c.name as console_name,
               COALESCE(
                   (SELECT pi.image_url 
                    FROM product_images pi 
                    WHERE pi.product_id = p.id 
                    AND pi.is_primary = 1
                    LIMIT 1),
                   p.image_url
               ) as primary_image,
               COUNT(DISTINCT pg2.genre_id) as shared_genres
        FROM products p
        LEFT JOIN consoles c ON p.console_id = c.id
        INNER JOIN product_genres pg2 ON p.id = pg2.product_id
        WHERE pg2.genre_id IN (
            SELECT pg.genre_id 
            FROM product_genres pg 
            WHERE pg.product_id = :product_id
        )
        AND p.id != :product_id
        AND p.is_active = 1
        GROUP BY p.id, p.name, p.price_pesos, p.image_url, p.stock_quantity, p.slug, c.name
        ORDER BY shared_genres DESC, RAND()
        LIMIT 4
    ");
    $stmt->execute([
        'product_id' => $product_id
    ]);
    $similar_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("✓ Productos con géneros similares: " . count($similar_products));
    
    // Query 2: Si no hay suficientes, completar con productos de la misma categoría
    if (count($similar_products) < 4) {
        $needed = 4 - count($similar_products);
        $category_id = $current_product['category_id'] ?? null;
        
        // Obtener IDs a excluir
        $exclude_ids = [$product_id];
        foreach ($similar_products as $prod) {
            $exclude_ids[] = $prod['id'];
        }
        
        // Crear placeholders para NOT IN
        $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
        
        if (!empty($category_id)) {
            $sql = "
                SELECT p.id, p.name, p.price_pesos, p.image_url, p.stock_quantity, p.slug,
                       c.name as console_name,
                       COALESCE(
                           (SELECT pi.image_url 
                            FROM product_images pi 
                            WHERE pi.product_id = p.id 
                            AND pi.is_primary = 1
                            LIMIT 1),
                           p.image_url
                       ) as primary_image
                FROM products p
                LEFT JOIN consoles c ON p.console_id = c.id
                WHERE p.category_id = ?
                AND p.id NOT IN ($placeholders)
                AND p.is_active = 1
                ORDER BY RAND()
                LIMIT $needed
            ";
            
            $params = array_merge([$category_id], $exclude_ids);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $additional = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("✓ Productos de misma categoría: " . count($additional));
            $similar_products = array_merge($similar_products, $additional);
        }
    }
    
    // Query 3: Si aún no hay suficientes, completar con productos aleatorios
    if (count($similar_products) < 4) {
        $needed = 4 - count($similar_products);
        
        // Obtener IDs a excluir
        $exclude_ids = [$product_id];
        foreach ($similar_products as $prod) {
            $exclude_ids[] = $prod['id'];
        }
        
        // Crear placeholders para NOT IN
        $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
        
        $sql = "
            SELECT p.id, p.name, p.price_pesos, p.image_url, p.stock_quantity, p.slug,
                   c.name as console_name,
                   COALESCE(
                       (SELECT pi.image_url 
                        FROM product_images pi 
                        WHERE pi.product_id = p.id 
                        AND pi.is_primary = 1
                        LIMIT 1),
                       p.image_url
                   ) as primary_image
            FROM products p
            LEFT JOIN consoles c ON p.console_id = c.id
            WHERE p.id NOT IN ($placeholders)
            AND p.is_active = 1
            ORDER BY RAND()
            LIMIT $needed
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($exclude_ids);
        $additional = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("✓ Productos aleatorios adicionales: " . count($additional));
        
        $similar_products = array_merge($similar_products, $additional);
    }
    
    error_log("✓ TOTAL productos similares: " . count($similar_products));
    
} catch (Exception $e) {
    error_log("❌ ERROR al obtener productos similares: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $similar_products = [];
}

// DEBUG: Mostrar información visible en HTML
echo "<!-- ========================================= -->";
echo "<!-- SIMILAR PRODUCTS DEBUG -->";
echo "<!-- Total productos similares: " . count($similar_products) . " -->";
echo "<!-- Category ID: " . ($current_product['category_id'] ?? 'N/A') . " -->";
echo "<!-- Product ID actual: " . $product_id . " -->";
if (!empty($similar_products)) {
    echo "<!-- Productos encontrados: ";
    foreach ($similar_products as $sp) {
        echo $sp['id'] . " (" . $sp['name'] . "), ";
    }
    echo " -->";
}
echo "<!-- ========================================= -->";

// Función para obtener ruta de imagen
function getImagePath($image_name)
{
    if (empty($image_name)) {
        return '/assets/images/products/product1.jpg';
    }

    // Verificar diferentes rutas posibles
    $paths = [
        'uploads/products/' . $image_name => '/uploads/products/' . $image_name,
        'assets/images/products/' . $image_name => '/assets/images/products/' . $image_name,
        $image_name => '/' . $image_name // Si ya viene con ruta completa
    ];

    foreach ($paths as $file_path => $url_path) {
        if (file_exists($file_path)) {
            return $url_path;
        }
    }

    // Si no existe, usar la ruta con uploads como default (absoluta)
    return '/uploads/products/' . $image_name;
}

// Preparar array de todas las imágenes para la galería
$all_images = [];

// 1. Imagen principal
if ($main_image) {
    $all_images[] = [
        'path' => getImagePath($main_image),
        'alt' => 'Imagen principal',
        'is_main' => true
    ];
}

// 2. Imágenes adicionales
if (!empty($product_images)) {
    foreach ($product_images as $img) {
        // Evitar duplicar la principal
        if ($img['is_primary'] == 1 && $img['image_url'] == $main_image) {
            continue;
        }
        $all_images[] = [
            'path' => getImagePath($img['image_url']),
            'alt' => $img['alt_text'] ?? 'Imagen adicional',
            'is_main' => false
        ];
    }
}
?>

<!-- Dark Theme Stylesheet -->
<link rel="stylesheet" href="/assets/css/product-details-dark.css?v=3.5">

<div class="container-fluid product-details-container">

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/index.php">INICIO</a></li>
        <li class="breadcrumb-item"><a href="/productos.php">PRODUCTOS</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($current_product['name']); ?>
        </li>
    </ol>
</nav>
    <div class="row product-detail-row">
        <!-- Product Images Section -->
        <div class="col-md-6">
            <div class="product-image-section">
                <!-- Thumbnail Images - Now on the left -->
                <div class="product-thumbnails">
                    <?php
                    $total_images = count($all_images);
                    $max_visible_thumbnails = 5;
                    
                    foreach ($all_images as $index => $img) {
                        // Si es una de las primeras 4, mostrar normal
                        if ($index < $max_visible_thumbnails - 1) {
                            $activeClass = ($index === 0) ? 'active' : '';
                            ?>
                            <div class="thumbnail-item <?php echo $activeClass; ?>"
                                onclick="changeMainImage('<?php echo htmlspecialchars($img['path']); ?>');">
                                <img src="<?php echo htmlspecialchars($img['path']); ?>" 
                                     alt="<?php echo htmlspecialchars($img['alt']); ?>" 
                                     class="img-fluid"
                                     onerror="this.src='assets/images/products/product1.jpg'">
                            </div>
                            <?php
                        } 
                        // Si es la 5ta imagen
                        elseif ($index === $max_visible_thumbnails - 1) {
                            $hidden_count = $total_images - $max_visible_thumbnails;
                            
                            if ($hidden_count > 0) {
                                // Mostrar con overlay "+N"
                                ?>
                                <div class="thumbnail-item more-images-container" onclick="openGalleryModal(<?php echo $index; ?>)">
                                    <img src="<?php echo htmlspecialchars($img['path']); ?>" 
                                         alt="<?php echo htmlspecialchars($img['alt']); ?>" 
                                         class="img-fluid"
                                         onerror="this.src='assets/images/products/product1.jpg'">
                                    <div class="more-images-overlay">
                                        <span>+<?php echo $hidden_count + 1; // +1 porque incluye esta misma imagen en el conteo mental del usuario "ver más" ?></span>
                                    </div>
                                </div>
                                <?php
                            } else {
                                // Es la última imagen exacta (total 5), mostrar normal
                                ?>
                                <div class="thumbnail-item"
                                    onclick="changeMainImage('<?php echo htmlspecialchars($img['path']); ?>');">
                                    <img src="<?php echo htmlspecialchars($img['path']); ?>" 
                                         alt="<?php echo htmlspecialchars($img['alt']); ?>" 
                                         class="img-fluid"
                                         onerror="this.src='assets/images/products/product1.jpg'">
                                </div>
                                <?php
                            }
                        }
                        // Las demás no se renderizan en la lista de thumbnails pero estarán en el modal
                    }
                    ?>
                </div>
                
                <!-- Main Product Image - Now on the right -->
                <div class="main-product-image">
                    <?php
                    $product_image = getImagePath($main_image);
                    ?>
                    <img src="<?php echo htmlspecialchars($product_image); ?>"
                        alt="<?php echo htmlspecialchars($current_product['name']); ?>" class="img-fluid main-image"
                        id="mainProductImage" onerror="this.src='assets/images/products/product1.jpg'">

                    <!-- ============================================================ -->
                    <!-- WISHLIST BUTTON - ÚLTIMA ACTUALIZACIÓN: 2025-10-18 04:55    -->
                    <!-- ============================================================ -->
                    <!-- 
                        🔍 WISHLIST DEBUG:
                        - User Logged In: <?php echo isLoggedIn() ? 'YES ✅' : 'NO ❌'; ?>
                        - User ID: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A'; ?>
                        - Product ID: <?php echo $product_id; ?>
                        - SQL Count: <?php echo $wishlist_debug['count']; ?>
                        - isInWishlist VARIABLE: <?php echo $isInWishlist ? 'TRUE ✅' : 'FALSE ❌'; ?>
                        - RENDERING: <?php echo $isInWishlist ? 'BOTÓN ACTIVO (ROJO)' : 'BOTÓN INACTIVO (BLANCO)'; ?>
                    -->
                    <!-- ============================================================ -->
                    
                    <?php if ($isInWishlist): ?>
                        <!-- ✅ PRODUCTO EN WISHLIST - BOTÓN ACTIVO -->
                        <button class="favorite-btn-detail btn-wishlist active"
                            data-product-id="<?php echo $current_product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($current_product['name']); ?>"
                            title="Quitar de wishlist">
                            <i class="fas fa-heart"></i>
                        </button>
                    <?php else: ?>
                        <!-- ❌ PRODUCTO NO EN WISHLIST - BOTÓN INACTIVO -->
                        <button class="favorite-btn-detail btn-wishlist"
                            data-product-id="<?php echo $current_product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($current_product['name']); ?>"
                            title="Agregar a wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Info Section -->
        <div class="col-md-6">
            <div class="product-info-section">
                <h1 class="product-title"><?php echo htmlspecialchars($current_product['name']); ?></h1>

            <!-- Product badges -->
            <div class="product-badges">
                <?php if (!empty($current_product['is_new']) && $current_product['is_new']): ?>
                    <span class="badge bg-success">NUEVO</span>
                <?php endif; ?>
                <?php if (!empty($current_product['is_featured']) && $current_product['is_featured']): ?>
                    <span class="badge bg-warning text-dark">DESTACADO</span>
                <?php endif; ?>
                <?php
                $stock = $current_product['stock_quantity'] ?? 0;
                if ($stock <= 0): ?>
                    <span class="badge bg-danger">SIN STOCK</span>
                <?php endif; ?>
            </div>

            <!-- Description at the top -->
            <div class="product-description-top">
                <h3 class="section-title">Descripción</h3>
                <?php if (!empty($current_product['long_description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($current_product['long_description'])); ?></p>
                <?php elseif (!empty($current_product['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($current_product['description'])); ?></p>
                <?php else: ?>
                    <p>Producto de alta calidad disponible en MultiGamer360. ¡No te lo pierdas!</p>
                <?php endif; ?>
            </div>

            <!-- Stock information -->
            <div class="stock-info-detail">
                <?php if ($stock > 0): ?>
                    <span class="stock-available">
                        <i class="fas fa-check-circle text-success"></i>
                        <?php echo $stock; ?> unidades disponibles
                    </span>
                <?php else: ?>
                    <span class="stock-unavailable">
                        <i class="fas fa-times-circle text-danger"></i>
                        Sin stock disponible
                    </span>
                <?php endif; ?>
            </div>

            <!-- Price and Quantity Selector Combined -->
            <div class="price-quantity-container">
                <?php 
                // Obtener precio y stock directamente de la base de datos
                $product_price = $current_product['price_pesos'] ?? $current_product['price'];
                $stock = $current_product['stock_quantity'] ?? 0;
                ?>
                
                <div class="product-pricing">
                    <div class="price-cash">
                        <span class="price-value">$<?php echo number_format($product_price, 0, ',', '.'); ?></span>
                        <span class="price-label">EN EFECTIVO</span>
                    </div>
                </div>

                <?php if ($stock > 0): ?>
                    <div class="quantity-selector">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $stock; ?>"
                            id="product-quantity" data-max-stock="<?php echo $stock; ?>">
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <button class="btn-add-to-cart btn-cart-detail" 
                            data-product-id="<?php echo $current_product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($current_product['name']); ?>"
                            data-product-price="<?php echo $product_price; ?>"
                            data-product-image="<?php echo htmlspecialchars($main_image); ?>">
                        <span class="cart-icon"><i class="fas fa-shopping-cart"></i></span> AGREGAR AL CARRITO
                    </button>
                <?php else: ?>
                    <button class="btn-add-to-cart" disabled>
                        <i class="fas fa-times-circle"></i> SIN STOCK
                    </button>
                <?php endif; ?>
            </div>

            <div class="payment-options">
                <div class="discount-info">
                    <i class="fas fa-credit-card"></i>
                    <span>En 4 cuotas sin interes
                        <strong>$<?php echo number_format($product_price / 4, 0, ',', '.'); ?></strong></span>
                </div>
                <div class="cash-discount">
                    <i class="fas fa-percentage"></i>
                    <span>10% de descuento <small>pagando en efectivo</small></span>
                </div>
            </div>

            <div class="payment-methods">
                <a href="#" class="payment-link" id="openPaymentModal">VER MEDIOS DE PAGO</a>
            </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal de Medios de Pago -->
<div class="modal fade" id="paymentMethodsModal" tabindex="-1" aria-labelledby="paymentMethodsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-danger">
                <h5 class="modal-title" id="paymentMethodsModalLabel">
                    <i class="fas fa-credit-card text-danger"></i> Medios de Pago Disponibles
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="payment-method-item">
                    <h6 class="text-danger"><i class="fas fa-money-bill-wave"></i> Efectivo</h6>
                    <p>Aprovecha un <strong>10% de descuento</strong> pagando en efectivo. Puedes coordinar el pago al momento de la entrega.</p>
                </div>
                
                <div class="payment-method-item">
                    <h6 class="text-danger"><i class="fas fa-credit-card"></i> Tarjetas de Crédito</h6>
                    <p>Aceptamos todas las tarjetas de crédito principales:</p>
                    <ul>
                        <li>Visa</li>
                        <li>Mastercard</li>
                        <li>American Express</li>
                        <li>Naranja</li>
                    </ul>
                    <p><strong>Hasta 4 cuotas sin interés</strong> en compras superiores a $50.000</p>
                </div>
                
                <div class="payment-method-item">
                    <h6 class="text-danger"><i class="fas fa-money-check-alt"></i> Tarjetas de Débito</h6>
                    <p>Pago inmediato con tarjeta de débito de cualquier banco.</p>
                </div>
                
                <div class="payment-method-item">
                    <h6 class="text-danger"><i class="fas fa-university"></i> Transferencia Bancaria</h6>
                    <p>Realiza una transferencia directa a nuestra cuenta. El pedido se procesa una vez confirmado el pago.</p>
                </div>
                
                <div class="payment-method-item">
                    <h6 class="text-danger"><i class="fab fa-mercadopago"></i> Mercado Pago</h6>
                    <p>Paga con tu cuenta de Mercado Pago de forma segura y rápida.</p>
                </div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Similar Products Section -->
<?php if (!empty($similar_products)): ?>
<div class="container-fluid mt-5 mb-5" style="background: #1a1a1a; padding: 40px 0;">
    <section class="similar-products-section">
        <h2 class="similar-products-title">PRODUCTOS SIMILARES</h2>
        <div class="similar-products-carousel-wrapper">
            <button class="carousel-nav carousel-prev" aria-label="Anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="similar-products-carousel">
                <?php 
                // Obtener wishlist del usuario
                $userWishlist = [];
                if (isLoggedIn()) {
                    try {
                        $stmt = $pdo->prepare("SELECT product_id FROM user_favorites WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userWishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (Exception $e) {
                        error_log("Error getting wishlist: " . $e->getMessage());
                    }
                }
                
                // Obtener productos en carrito
                $productsInCart = [];
                if (!empty($_SESSION['cart'])) {
                    $productsInCart = array_keys($_SESSION['cart']);
                }
                
                foreach ($similar_products as $product): 
                    // ===== COPIAR EXACTO DE PRODUCTOS.PHP =====
                    // Obtener la imagen principal desde la tabla product_images
                    $image_filename = !empty($product['primary_image']) ? $product['primary_image'] : 
                                     (!empty($product['image_url']) ? $product['image_url'] : 'product1.jpg');
                    
                    // Construir rutas posibles
                    $possible_paths = [
                        'uploads/products/' . $image_filename,
                        'assets/images/products/' . $image_filename,
                        'admin/uploads/products/' . $image_filename
                    ];
                    
                    // Buscar la ruta correcta (usar $_SERVER['DOCUMENT_ROOT'] para rutas absolutas)
                    $product_image = 'assets/images/products/product1.jpg'; // Imagen por defecto
                    $doc_root = $_SERVER['DOCUMENT_ROOT'];
                    
                    foreach ($possible_paths as $path) {
                        // Verificar si el archivo existe en el servidor
                        $full_path = $doc_root . '/' . $path;
                        if (file_exists($full_path)) {
                            $product_image = $path;
                            break;
                        }
                    }
                    
                    // Si no se encontró, intentar con la ruta directa
                    if ($product_image === 'assets/images/products/product1.jpg' && !empty($image_filename)) {
                        // Asumir que image_url ya tiene la ruta completa
                        if (strpos($image_filename, '/') !== false || strpos($image_filename, 'http') === 0) {
                            $product_image = $image_filename;
                        }
                    }
                    
                    // IMPORTANTE: Convertir a ruta absoluta para que funcione en /producto/slug
                    if (strpos($product_image, 'http') !== 0 && $product_image[0] !== '/') {
                        $product_image = '/' . $product_image;
                    }
                ?>
                    <!-- =====================================================
                         NUEVA CARD DE PRODUCTO (CON IMAGEN COMPLETA)
                         ===================================================== -->
                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                        
                        <!-- Botón de wishlist siempre visible -->
                        <?php 
                        $isInWishlist = in_array($product['id'], $userWishlist);
                        $heartClass = $isInWishlist ? 'fas fa-heart' : 'far fa-heart';
                        $btnClass = $isInWishlist ? 'btn-wishlist-fixed active' : 'btn-wishlist-fixed';
                        ?>
                        <button class="<?php echo $btnClass; ?>" 
                                data-product-id="<?php echo $product['id']; ?>"
                                title="<?php echo $isInWishlist ? 'Quitar de wishlist' : 'Agregar a wishlist'; ?>">
                            <i class="<?php echo $heartClass; ?>"></i>
                        </button>
                        
                        <!-- Botón de vista rápida siempre visible -->
                        <a href="<?php echo getProductUrl($product); ?>" 
                           class="btn-view-fixed"
                           title="Ver detalles del producto">
                            <i class="far fa-eye"></i>
                        </a>
                        
                        <!-- Imagen de fondo que ocupa toda la card -->
                        <?php
                        // DEBUG: Mostrar ruta de imagen en comentario HTML
                        echo "<!-- DEBUG IMG: filename='" . htmlspecialchars($image_filename) . "' | path='" . htmlspecialchars($product_image) . "' -->";
                        ?>
                        <div class="product-image-background" 
                             style="background-image: url('<?php echo htmlspecialchars($product_image); ?>');"
                             data-fallback="/assets/images/products/product1.jpg">
                        </div>
                        
                        <!-- Overlay con información del producto -->
                        <div class="product-overlay">
                            <div class="product-info-overlay">
                                <!-- 1. Nombre del producto -->
                                <?php 
                                $clean_name = $product['name'];
                                // Remover referencias a consola del nombre si existen
                                $console_patterns = [' - PS2', ' - PlayStation', ' - Nintendo 64', ' - N64', ' - Super Nintendo', ' - SNES', ' - Xbox', ' - GameCube', ' - Dreamcast', ' - PSP', ' - PS3', ' - PS4', ' - PS5', ' - Switch'];
                                foreach ($console_patterns as $pattern) {
                                    $clean_name = str_ireplace($pattern, '', $clean_name);
                                }
                                ?>
                                <h5 class="product-title"><?php echo htmlspecialchars($clean_name); ?></h5>
                                
                                <!-- 2. Consola -->
                                <div class="product-console">
                                    <?php 
                                    // Usar console_name del JOIN con la tabla consoles
                                    $console_text = !empty($product['console_name']) ? $product['console_name'] : 
                                                  (!empty($product['console']) ? $product['console'] : 'PC');
                                    ?>
                                    <span class="console-name">
                                        <?php echo htmlspecialchars($console_text); ?>
                                    </span>
                                </div>
                                
                                <!-- 3. Precio en pesos -->
                                <div class="product-price-simple">
                                    $<?php echo number_format($product['price_pesos'] ?? $product['price'], 0, ',', '.'); ?>
                                </div>
                                
                                <!-- 4. Botón de agregar al carrito -->
                                <div class="product-actions">
                                    <?php if (($product['stock_quantity'] ?? 0) > 0): ?>
                                        <?php 
                                        // Verificar si el producto ya está en el carrito
                                        $isInCart = in_array($product['id'], $productsInCart);
                                        $btnClass = $isInCart ? 'btn-add-to-cart-modern in-cart' : 'btn-add-to-cart-modern';
                                        $btnText = $isInCart ? 'EN EL CARRITO' : 'AGREGAR AL CARRITO';
                                        ?>
                                        <button class="<?php echo $btnClass; ?>" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-in-cart="<?php echo $isInCart ? 'true' : 'false'; ?>">
                                            <?php if ($isInCart): ?>
                                                <!-- Producto en carrito: solo mostrar checks -->
                                                <i class="fas fa-shopping-cart cart-icon"></i>
                                                <i class="fas fa-check success-check"></i>
                                            <?php else: ?>
                                                <!-- Producto no en carrito: mostrar carrito normal -->
                                                <i class="fas fa-shopping-cart cart-icon"></i>
                                                <div class="loading-spinner"></div>
                                                <i class="fas fa-check success-check"></i>
                                                <span class="btn-text"><?php echo $btnText; ?></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-no-stock" disabled>
                                            <i class="fas fa-ban"></i>
                                            <span>Sin stock</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-nav carousel-next" aria-label="Siguiente">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>
</div>
<?php endif; ?>

<!-- Image Gallery Modal - Amazon Style Layout -->
<div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title ps-3">Imágenes del producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="container-fluid h-100">
                    <div class="row h-100">
                        <!-- Main Image Area (Left/Center) -->
                        <div class="col-md-10 h-100 d-flex align-items-center justify-content-center bg-black position-relative">
                            <img id="galleryMainImage" src="" class="img-fluid" style="max-height: 90vh; max-width: 100%; object-fit: contain;" alt="Imagen principal">
                            
                            <!-- Navigation Arrows -->
                            <button class="gallery-nav prev" onclick="navigateGallery(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="gallery-nav next" onclick="navigateGallery(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <!-- Thumbnails Sidebar (Right) -->
                        <div class="col-md-2 h-100 bg-dark border-start border-secondary overflow-auto py-3">
                            <div class="d-flex flex-column gap-3 px-2">
                                <?php foreach ($all_images as $index => $img): ?>
                                    <div class="gallery-thumbnail-wrapper <?php echo $index === 0 ? 'active' : ''; ?>" 
                                         onclick="setGalleryImage(<?php echo $index; ?>)"
                                         data-index="<?php echo $index; ?>"
                                         data-src="<?php echo htmlspecialchars($img['path']); ?>">
                                        <img src="<?php echo htmlspecialchars($img['path']); ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?php echo htmlspecialchars($img['alt']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentGalleryIndex = 0;
    const galleryImages = <?php echo json_encode(array_column($all_images, 'path')); ?>;
    
    function openGalleryModal(index) {
        const modalElement = document.getElementById('imageGalleryModal');
        const modal = new bootstrap.Modal(modalElement);
        
        setGalleryImage(index);
        modal.show();
    }
    
    function setGalleryImage(index) {
        if (index < 0) index = galleryImages.length - 1;
        if (index >= galleryImages.length) index = 0;
        
        currentGalleryIndex = index;
        
        // Update Main Image
        const mainImage = document.getElementById('galleryMainImage');
        mainImage.src = galleryImages[index];
        
        // Update Thumbnails Active State
        document.querySelectorAll('.gallery-thumbnail-wrapper').forEach(el => {
            el.classList.remove('active');
            if (parseInt(el.dataset.index) === index) {
                el.classList.add('active');
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
    
    function navigateGallery(direction) {
        setGalleryImage(currentGalleryIndex + direction);
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('imageGalleryModal');
        if (modal && modal.classList.contains('show')) {
            if (e.key === 'ArrowLeft') navigateGallery(-1);
            if (e.key === 'ArrowRight') navigateGallery(1);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

<!-- Product Details JavaScript - Version 2.3 - LOADED AFTER BOOTSTRAP -->
<script src="/assets/js/product-details.js?v=2.3"></script>

<script>
    // Función para cambiar la imagen principal al hacer clic en miniaturas
    function changeMainImage(imageSrc) {
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage) {
            mainImage.src = imageSrc;

            // Actualizar clase active en miniaturas
            document.querySelectorAll('.thumbnail-item').forEach(thumb => {
                thumb.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
    }

    // Funcionalidad inline para los botones de cantidad (cargado después de Bootstrap)
    document.addEventListener('DOMContentLoaded', function () {
        // ===== DEBUG: VERIFICAR ESTADO INICIAL DEL BOTÓN WISHLIST =====
        const wishlistBtnInitial = document.querySelector('.btn-wishlist');
        if (wishlistBtnInitial) {
            const hasActiveClass = wishlistBtnInitial.classList.contains('active');
            const iconElement = wishlistBtnInitial.querySelector('i');
            const iconClass = iconElement?.className || 'NO ICON';
            const productId = wishlistBtnInitial.getAttribute('data-product-id');
            const allClasses = wishlistBtnInitial.className;
            
            console.log('===========================================');
            console.log('🔍 DEBUG WISHLIST - Estado al cargar página');
            console.log('===========================================');
            console.log('Producto ID:', productId);
            console.log('Tiene clase "active":', hasActiveClass ? '✅ SÍ' : '❌ NO');
            console.log('Todas las clases del botón:', allClasses);
            console.log('Clase del ícono:', iconClass);
            console.log('¿Es corazón lleno (fas)?:', iconClass.includes('fas') ? '✅ SÍ' : '❌ NO');
            console.log('¿Es corazón vacío (far)?:', iconClass.includes('far') ? '✅ SÍ' : '❌ NO');
            console.log('===========================================');
        } else {
            console.error('❌ ERROR: No se encontró el botón .btn-wishlist');
        }
        
        // ===== MODAL DE MEDIOS DE PAGO =====
        const paymentLink = document.getElementById('openPaymentModal');
        if (paymentLink) {
            paymentLink.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('paymentMethodsModal'));
                modal.show();
            });
        }

        // ===== BOTÓN AGREGAR AL CARRITO =====
        const addToCartBtn = document.querySelector('.btn-add-to-cart');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                const productPrice = this.getAttribute('data-product-price');
                const productImage = this.getAttribute('data-product-image');
                const quantity = parseInt(document.getElementById('product-quantity')?.value || 1);
                
                // Verificar si existe el sistema de carrito
                if (window.advancedCartSystem) {
                    // Usar el sistema avanzado
                    window.advancedCartSystem.addToCartWithQuantity(this, productId, quantity);
                } else {
                    // Fallback: agregar directamente
                    const product = {
                        id: productId,
                        name: productName,
                        price: productPrice,
                        image: productImage,
                        quantity: quantity
                    };
                    
                    // Obtener carrito del localStorage
                    let cart = JSON.parse(localStorage.getItem('cart')) || [];
                    
                    // Verificar si ya existe
                    const existingItemIndex = cart.findIndex(item => item.id === productId);
                    
                    if (existingItemIndex > -1) {
                        cart[existingItemIndex].quantity += quantity;
                    } else {
                        cart.push(product);
                    }
                    
                    localStorage.setItem('cart', JSON.stringify(cart));
                    
                    // Notificar
                    alert('Producto agregado al carrito');
                    location.reload();
                }
            });
        }
    });
</script>