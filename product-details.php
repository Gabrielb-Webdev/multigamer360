<?php 
// Solo iniciar sesión si no está ya iniciada
// Updated: 2025-10-17 - Bootstrap error fixed
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
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Obtener producto desde la base de datos
$current_product = $productManager->getProductById($product_id);

// Si no se encuentra el producto, redirigir a productos
if (!$current_product) {
    header('Location: productos.php');
    exit;
}
?>

<div class="container-fluid product-details-container">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">INICIO</a></li>
            <li class="breadcrumb-item"><a href="productos.php">PRODUCTOS</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($current_product['name']); ?></li>
        </ol>
    </nav>

    <div class="row product-detail-row">
        <!-- Product Images Section -->
        <div class="col-md-6">
            <div class="product-image-section">
                <!-- Main Product Image -->
                <div class="main-product-image">
                    <?php 
                    // Procesar la imagen de manera similar a productos.php
                    $image_filename = !empty($current_product['image_url']) ? $current_product['image_url'] : 'product1.jpg';
                    $assets_path = 'assets/images/products/' . $image_filename;
                    $uploads_path = 'uploads/products/' . $image_filename;
                    
                    // Determinar qué ruta usar (prioridad a assets/images/products/)
                    if (file_exists($assets_path)) {
                        $product_image = $assets_path;
                    } else if (file_exists($uploads_path)) {
                        $product_image = $uploads_path;
                    } else {
                        // Imagen por defecto
                        $product_image = 'assets/images/products/product1.jpg';
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($product_image); ?>" 
                         alt="<?php echo htmlspecialchars($current_product['name']); ?>" 
                         class="img-fluid main-image"
                         onerror="this.src='assets/images/products/product1.jpg'">
                         
                    <!-- Wishlist button overlay -->
                    <button class="favorite-btn-detail btn-wishlist" 
                            data-product-id="<?php echo $current_product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($current_product['name']); ?>">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                
                <!-- Thumbnail Images -->
                <div class="product-thumbnails">
                    <div class="thumbnail-item active">
                        <img src="<?php echo $product_image; ?>" alt="Imagen 1" class="img-fluid">
                    </div>
                    <div class="thumbnail-item">
                        <img src="assets/images/products/product2.jpg" alt="Imagen 2" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Info Section -->
        <div class="col-md-6">
            <div class="product-info-section">
                <h1 class="product-title"><?php echo htmlspecialchars($current_product['name']); ?></h1>
                
                <!-- Product badges -->
                <div class="product-badges">
                    <?php if ($current_product['is_new']): ?>
                        <span class="badge bg-success">NUEVO</span>
                    <?php endif; ?>
                    <?php if ($current_product['is_featured']): ?>
                        <span class="badge bg-warning text-dark">DESTACADO</span>
                    <?php endif; ?>
                    <?php if ($current_product['stock'] <= 0): ?>
                        <span class="badge bg-danger">SIN STOCK</span>
                    <?php elseif ($current_product['stock'] <= 5): ?>
                        <span class="badge bg-orange">POCAS UNIDADES</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($current_product['short_description'])): ?>
                    <p class="product-description"><?php echo htmlspecialchars($current_product['short_description']); ?></p>
                <?php endif; ?>
                
                <!-- Stock information -->
                <div class="stock-info-detail">
                    <?php if ($current_product['stock'] > 0): ?>
                        <span class="stock-available">
                            <i class="fas fa-check-circle text-success"></i> 
                            <?php echo $current_product['stock']; ?> unidades disponibles
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
                        <span>En 4 cuotas sin interes <strong>$<?php echo number_format($regular_price / 4, 0, ',', '.'); ?></strong></span>
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
                    <?php if ($current_product['stock'] > 0): ?>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn minus">-</button>
                            <input type="number" 
                                   class="quantity-input" 
                                   value="1" 
                                   min="1" 
                                   max="<?php echo $current_product['stock']; ?>"
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
    </div>
</div>

<style>
.favorite-btn-detail {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 50px;
    height: 50px;
    border: none;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.favorite-btn-detail:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.favorite-btn-detail.active {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.favorite-btn-detail i {
    font-size: 20px;
    transition: all 0.3s ease;
}

.product-badges {
    margin-bottom: 15px;
}

.product-badges .badge {
    margin-right: 8px;
    padding: 6px 12px;
    font-size: 0.8rem;
}

.stock-info-detail {
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255,255,255,0.1);
    border-radius: 5px;
}

.stock-available, .stock-unavailable {
    font-weight: 500;
}

.btn-cart-detail {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.btn-cart-detail.success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}

.btn-cart-detail:hover.success {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-cart-detail .cart-icon,
.btn-cart-detail .loading-icon,
.btn-cart-detail .success-icon {
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .product-detail-row {
        flex-direction: column;
    }
    
    .product-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .quantity-selector {
        justify-content: center;
    }
}
</style>

<script>
// Funcionalidad para los botones de cantidad
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.querySelector('.quantity-input');
    const minusBtn = document.querySelector('.quantity-btn.minus');
    const plusBtn = document.querySelector('.quantity-btn.plus');
    const maxStock = <?php echo $current_product['stock']; ?>;

    if (minusBtn && plusBtn && quantityInput) {
        minusBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        plusBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
            }
        });

        // Validar input manual
        quantityInput.addEventListener('change', function() {
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

    thumbnails.forEach(function(thumbnail) {
        thumbnail.addEventListener('click', function() {
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
    document.addEventListener('click', function(e) {
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
                    <img src="assets/images/products/retro-consola-2.jpg" alt="Final Fantasy XVI PlayStation" class="img-fluid">
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

<script src="assets/js/product-details.js"></script>

<style>
/* Asegurar z-index correcto para dropdowns en product-details */
.dropdown-menu {
    z-index: 1055 !important;
}
.main-header {
    z-index: 1050 !important;
}
</style>

<?php include 'includes/footer.php'; ?>

