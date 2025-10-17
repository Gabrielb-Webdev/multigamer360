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
require_once 'includes/product_manager.php';

// Crear instancia del manager de productos
$productManager = new ProductManager($pdo);

include 'includes/header.php';

// Obtener parámetros del producto
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

// Obtener producto desde la base de datos
$current_product = $productManager->getProductById($product_id);

// Si no se encuentra el producto, redirigir a productos
if (!$current_product) {
    header('Location: productos.php');
    exit;
}

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

// Función para obtener ruta de imagen
function getImagePath($image_name)
{
    if (empty($image_name)) {
        return 'assets/images/products/product1.jpg';
    }

    // Verificar diferentes rutas posibles
    $paths = [
        'uploads/products/' . $image_name,
        'assets/images/products/' . $image_name,
        $image_name // Si ya viene con ruta completa
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    // Si no existe, usar la ruta con uploads como default
    return 'uploads/products/' . $image_name;
}
?>

<!-- Dark Theme Stylesheet -->
<link rel="stylesheet" href="assets/css/product-details-dark.css?v=<?php echo time(); ?>">

<div class="container-fluid product-details-container">

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">INICIO</a></li>
        <li class="breadcrumb-item"><a href="productos.php">PRODUCTOS</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($current_product['name']); ?>
        </li>
    </ol>
</nav>
    <div class="row product-detail-row">
        <!-- Product Images Section -->
        <div class="col-md-5 col-lg-4">
            <div class="product-image-section">
                <!-- Main Product Image -->
                <div class="main-product-image">
                    <?php
                    $product_image = getImagePath($main_image);
                    ?>
                    <img src="<?php echo htmlspecialchars($product_image); ?>"
                        alt="<?php echo htmlspecialchars($current_product['name']); ?>" class="img-fluid main-image"
                        id="mainProductImage" onerror="this.src='assets/images/products/product1.jpg'">

                    <!-- Wishlist button overlay -->
                    <button class="favorite-btn-detail btn-wishlist"
                        data-product-id="<?php echo $current_product['id']; ?>"
                        data-product-name="<?php echo htmlspecialchars($current_product['name']); ?>">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <!-- Thumbnail Images -->
                <div class="product-thumbnails">
                    <?php
                    // Mostrar imagen principal como primera miniatura
                    if ($main_image) {
                        $thumb_path = getImagePath($main_image);
                        ?>
                        <div class="thumbnail-item active"
                            onclick="changeMainImage('<?php echo htmlspecialchars($thumb_path); ?>')">
                            <img src="<?php echo htmlspecialchars($thumb_path); ?>" alt="Imagen principal" class="img-fluid"
                                onerror="this.src='assets/images/products/product1.jpg'">
                        </div>
                    <?php
                    }

                    // Mostrar imágenes adicionales de product_images
                    if (!empty($product_images)) {
                        foreach ($product_images as $img) {
                            // Skip la imagen principal si ya la mostramos
                            if ($img['is_primary'] == 1 && $img['image_url'] == $main_image) {
                                continue;
                            }

                            $img_path = getImagePath($img['image_url']);
                            ?>
                            <div class="thumbnail-item" onclick="changeMainImage('<?php echo htmlspecialchars($img_path); ?>')">
                                <img src="<?php echo htmlspecialchars($img_path); ?>"
                                    alt="<?php echo htmlspecialchars($img['alt_text'] ?? 'Imagen adicional'); ?>"
                                    class="img-fluid" onerror="this.src='assets/images/products/product1.jpg'">
                            </div>
                        <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
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
                <?php elseif ($stock <= 5): ?>
                    <span class="badge bg-orange">POCAS UNIDADES</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($current_product['long_description'])): ?>
                <p class="product-description">
                    <?php echo nl2br(htmlspecialchars(substr($current_product['long_description'], 0, 200))); ?>...</p>
            <?php elseif (!empty($current_product['description'])): ?>
                <p class="product-description">
                    <?php echo nl2br(htmlspecialchars(substr($current_product['description'], 0, 200))); ?>...</p>
            <?php endif; ?>

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

            <div class="product-pricing">
                <?php
                // Usar price_pesos como precio principal
                $product_price = $current_product['price_pesos'] ?? $current_product['price'];

                // Calcular precios
                $cash_price = $product_price * 0.9;
                $regular_price = $product_price;

                if (!empty($current_product['sale_price']) && $current_product['sale_price'] > 0) {
                    $cash_price = $current_product['sale_price'] * 0.9;
                    $regular_price = $current_product['sale_price'];
                }
                ?>
                <div class="price-cash">
                    <span class="price-value">$<?php echo number_format($cash_price, 0, ',', '.'); ?></span>
                    <span class="price-label">En efectivo</span>
                </div>
                <div class="price-card">$<?php echo number_format($regular_price, 0, ',', '.'); ?></div>
            </div>

            <div class="payment-options">
                <div class="discount-info">
                    <i class="fas fa-credit-card"></i>
                    <span>En 4 cuotas sin interes
                        <strong>$<?php echo number_format($regular_price / 4, 0, ',', '.'); ?></strong></span>
                </div>
                <div class="cash-discount">
                    <i class="fas fa-percentage"></i>
                    <span>10% de descuento <small>pagando en efectivo</small></span>
                </div>
            </div>

            <div class="payment-methods">
                <a href="#" class="payment-link">VER MEDIOS DE PAGO</a>
            </div>

            <div class="product-actions">
                <?php
                $stock = $current_product['stock_quantity'] ?? 0;
                if ($stock > 0): ?>
                    <div class="quantity-selector">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $stock; ?>"
                            id="product-quantity">
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>

                    <!-- REMOVED: Conflicting cart button - only modern cart button in productos.php should be active -->
                <?php else: ?>
                    <button class="add-to-cart-detail" disabled>
                        <i class="fas fa-ban"></i>
                        SIN STOCK
                    </button>
                <?php endif; ?>
            </div>

            <div class="shipping-calculator">
                <h5>Calcular envío</h5>
                <div class="shipping-form">
                    <input type="text" class="form-control" placeholder="Tu código postal">
                    <button type="button" class="btn-calculate">CALCULAR</button>
                </div>
                <small class="shipping-note">No sé mi código postal</small>
            </div>

            <div class="product-description">
                <h5>Descripción</h5>
                <?php if (!empty($current_product['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($current_product['description'])); ?></p>
                <?php else: ?>
                    <p>Producto de alta calidad disponible en MultiGamer360. ¡No te lo pierdas!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Product Info Section -->
    <div class="col-md-6">

    </div>
</div>
</div>

</div>
</div>
</div>
</div>

<!-- Similar Products Section -->
<section class="similar-products-section">
    <h2 class="similar-products-title">PRODUCTOS SIMILARES</h2>
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="similar-product-card">
                <img src="assets/images/products/product2.jpg" alt="Final Fantasy XVI PlayStation" class="img-fluid">
                <div class="similar-product-info">
                    <h6>Final Fantasy XVI PlayStation</h6>
                    <div class="similar-price">
                        <span class="similar-price-cash">$30.000</span>
                        <span class="similar-price-label">En efectivo</span>
                    </div>
                    <div class="similar-price-card">$40.000</div>
                    <!-- REMOVED: Conflicting cart button -->
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="similar-product-card">
                <img src="assets/images/products/product3.jpg" alt="Final Fantasy XVI PlayStation" class="img-fluid">
                <div class="similar-product-info">
                    <h6>Final Fantasy XVI PlayStation</h6>
                    <div class="similar-price">
                        <span class="similar-price-cash">$30.000</span>
                        <span class="similar-price-label">En efectivo</span>
                    </div>
                    <div class="similar-price-card">$40.000</div>
                    <!-- REMOVED: Conflicting cart button -->
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="similar-product-card">
                <img src="assets/images/products/product4.jpg" alt="Final Fantasy XVI PlayStation" class="img-fluid">
                <div class="similar-product-info">
                    <h6>Final Fantasy XVI PlayStation</h6>
                    <div class="similar-price">
                        <span class="similar-price-cash">$30.000</span>
                        <span class="similar-price-label">En efectivo</span>
                    </div>
                    <div class="similar-price-card">$40.000</div>
                    <!-- REMOVED: Conflicting cart button -->
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="similar-product-card">
                <img src="assets/images/products/retro-consola-2.jpg" alt="Final Fantasy XVI PlayStation"
                    class="img-fluid">
                <div class="similar-product-info">
                    <h6>Final Fantasy XVI PlayStation</h6>
                    <div class="similar-price">
                        <span class="similar-price-cash">$30.000</span>
                        <span class="similar-price-label">En efectivo</span>
                    </div>
                    <div class="similar-price-card">$40.000</div>
                    <!-- REMOVED: Conflicting cart button -->
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Product Details JavaScript - Version 2.1 - LOADED AFTER BOOTSTRAP -->
<script src="assets/js/product-details.js?v=<?php echo time(); ?>"></script>

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
        const quantityInput = document.querySelector('.quantity-input');
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        const maxStock = <?php echo isset($current_product['stock_quantity']) ? (int) $current_product['stock_quantity'] : 0; ?>;

        if (minusBtn && plusBtn && quantityInput) {
            minusBtn.addEventListener('click', function () {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            });

            plusBtn.addEventListener('click', function () {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                }
            });

            // Validar input manual
            quantityInput.addEventListener('change', function () {
                const value = parseInt(this.value);
                if (value < 1) {
                    this.value = 1;
                } else if (value > maxStock) {
                    this.value = maxStock;
                }
            });
        }

        // Funcionalidad para cambiar imagen principal al hacer clic en miniatura
        const thumbnails = document.querySelectorAll('.thumbnail-item');
        const mainImage = document.querySelector('.main-image');

        thumbnails.forEach(function (thumbnail) {
            thumbnail.addEventListener('click', function () {
                // Remover clase active de todas las miniaturas
                thumbnails.forEach(t => t.classList.remove('active'));

                // Agregar clase active a la miniatura clickeada
                this.classList.add('active');

                // Cambiar imagen principal
                const newImageSrc = this.querySelector('img').src;
                mainImage.src = newImageSrc;
            });
        });

        // Modificar función de agregar al carrito para incluir cantidad
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn-cart-detail, .btn-cart-detail *')) {
                e.preventDefault();
                const button = e.target.closest('.btn-cart-detail');
                if (button && !button.disabled && window.advancedCartSystem) {
                    const productId = button.getAttribute('data-product-id');
                    const quantity = document.getElementById('product-quantity').value;

                    // Usar el sistema de carrito avanzado con cantidad personalizada
                    window.advancedCartSystem.addToCartWithQuantity(button, productId, parseInt(quantity));
                }
            }
        });
    });
</script>

<!-- 
╔══════════════════════════════════════════════════════════════════╗
║  PRODUCT DETAILS PAGE - VERSION 2.2 - FORCE SYNC                ║
║  Last Updated: 2025-10-17 at 21:00                               ║
║  CRITICAL FIX: Bootstrap loads before scripts                    ║
║  SYNC TOKEN: FORCE_UPDATE_2025_10_17_21_00                       ║
╚══════════════════════════════════════════════════════════════════╝
-->