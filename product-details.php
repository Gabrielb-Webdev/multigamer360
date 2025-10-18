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
<link rel="stylesheet" href="assets/css/product-details-dark.css?v=2.1">

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
        <div class="col-md-6">
            <div class="product-image-section">
                <!-- Thumbnail Images - Now on the left -->
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
                
                <!-- Main Product Image - Now on the right -->
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
                        <i class="fas fa-shopping-cart"></i> AGREGAR AL CARRITO
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

<!-- Product Details JavaScript - Version 2.2 - LOADED AFTER BOOTSTRAP -->
<script src="assets/js/product-details.js?v=2.2"></script>

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
        // ===== MODAL DE MEDIOS DE PAGO =====
        const paymentLink = document.getElementById('openPaymentModal');
        if (paymentLink) {
            paymentLink.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('paymentMethodsModal'));
                modal.show();
            });
        }

        // ===== WISHLIST CON ANIMACIÓN =====
        document.addEventListener('click', function(e) {
            const wishlistBtn = e.target.closest('.btn-wishlist');
            if (wishlistBtn) {
                e.preventDefault();
                
                // Añadir clase de loading
                wishlistBtn.classList.add('loading');
                
                const productId = wishlistBtn.getAttribute('data-product-id');
                const productName = wishlistBtn.getAttribute('data-product-name');
                
                // Simular delay para mostrar animación
                setTimeout(function() {
                    if (window.wishlistSystem) {
                        window.wishlistSystem.toggleWishlist(wishlistBtn, productId, productName);
                    }
                    
                    // Remover clase de loading después de completar
                    setTimeout(function() {
                        wishlistBtn.classList.remove('loading');
                    }, 300);
                }, 400);
            }
        });

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
                    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
                    
                    // Verificar si el producto ya existe
                    const existingProductIndex = cart.findIndex(item => item.id == productId);
                    
                    if (existingProductIndex > -1) {
                        cart[existingProductIndex].quantity += quantity;
                    } else {
                        cart.push(product);
                    }
                    
                    // Guardar en localStorage
                    localStorage.setItem('cart', JSON.stringify(cart));
                    
                    // Actualizar contador del carrito
                    if (window.updateCartCount) {
                        window.updateCartCount();
                    }
                    
                    // Mostrar mensaje de éxito (sin toast)
                    console.log(`Producto ${productName} agregado al carrito`);
                    
                    // Actualizar el botón visualmente
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> AGREGADO';
                    this.style.background = '#28a745';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.background = '';
                    }, 2000);
                }
            });
        }

        // ===== QUANTITY SELECTOR =====
        const quantityInput = document.querySelector('.quantity-input');
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        const maxStock = <?php echo isset($current_product['stock_quantity']) ? (int) $current_product['stock_quantity'] : 0; ?>;

        if (minusBtn && plusBtn && quantityInput) {
            // Botón menos
            minusBtn.addEventListener('click', function () {
                const currentValue = parseInt(quantityInput.value) || 1;
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            });

            // Botón más
            plusBtn.addEventListener('click', function () {
                const currentValue = parseInt(quantityInput.value) || 1;
                const max = parseInt(quantityInput.getAttribute('max')) || maxStock;
                if (currentValue < max) {
                    quantityInput.value = currentValue + 1;
                } else {
                    quantityInput.value = max;
                    console.log('Stock máximo alcanzado: ' + max);
                }
            });

            // Validar input manual
            quantityInput.addEventListener('input', function () {
                let value = parseInt(this.value);
                const max = parseInt(this.getAttribute('max')) || maxStock;
                
                if (isNaN(value) || value < 1) {
                    this.value = 1;
                } else if (value > max) {
                    this.value = max;
                }
            });

            quantityInput.addEventListener('blur', function () {
                let value = parseInt(this.value);
                const max = parseInt(this.getAttribute('max')) || maxStock;
                
                if (isNaN(value) || value < 1) {
                    this.value = 1;
                } else if (value > max) {
                    this.value = max;
                }
            });

            quantityInput.addEventListener('change', function () {
                let value = parseInt(this.value);
                const max = parseInt(this.getAttribute('max')) || maxStock;
                
                if (isNaN(value) || value < 1) {
                    this.value = 1;
                } else if (value > max) {
                    this.value = max;
                }
            });
        }

        // ===== THUMBNAIL IMAGES =====
        const thumbnails = document.querySelectorAll('.thumbnail-item');
        const mainImage = document.querySelector('.main-image');

        thumbnails.forEach(function (thumbnail) {
            thumbnail.addEventListener('click', function () {
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const newImageSrc = this.querySelector('img').src;
                mainImage.src = newImageSrc;
            });
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