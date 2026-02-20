<?php
/**
 * =====================================================
 * MULTIGAMER360 - PÁGINA PRINCIPAL (INDEX)
 * =====================================================
 *
 * Descripción: Página de inicio del sitio web MultiGamer360
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-16
 *
 * Funcionalidades:
 * - Mostrar productos destacados y novedades
 * - Carrusel de categorías principales
 * - Sistema de bienvenida para nuevos usuarios
 * - Integración con carrito y wishlist
 * - Diseño responsivo moderno
 */

// =====================================================
// CONFIGURACIÓN INICIAL
// =====================================================

// Desactivar mostrar errores en producción
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/product_manager.php';
require_once 'config/user_manager.php';

// =====================================================
// INICIALIZACIÓN DE MANAGERS
// =====================================================

// Crear instancias de los managers para manejo de datos
$productManager = new ProductManager($pdo);
$userManager = new UserManager($pdo);

// =====================================================
// PROCESAMIENTO DE DATOS
// =====================================================

// Verificar si el usuario acaba de registrarse para mostrar mensaje de bienvenida
$showWelcomeMessage = isset($_GET['registered']) && $_GET['registered'] == '1';

// Obtener datos principales para la página de inicio
try {
    $featured_products = $productManager->getFeaturedProducts(10);     // Productos destacados (máx 10)
    $new_products = $productManager->getNewProducts(10);               // Productos nuevos (máx 10)
    $categories = $productManager->getCategories();                    // Categorías disponibles
} catch (Exception $e) {
    error_log("Error en index.php: " . $e->getMessage());
    $featured_products = [];
    $new_products = [];
    $categories = [];
}

// =====================================================
// INCLUIR HEADER
// =====================================================
include 'includes/header.php'; 
?>

<?php if ($showWelcomeMessage): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>¡Bienvenido a MultiGamer360!</strong> Tu cuenta ha sido creada exitosamente. Ya puedes empezar a
        explorar nuestros productos.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
<div class="container mt-3">
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-sign-out-alt me-2"></i>
        <strong>¡Hasta luego!</strong> Has cerrado sesión exitosamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid">
    <div class="row hero-section">
        <div class="col-md-6">
            <div id="retroCarousel" class="carousel slide" data-bs-ride="carousel">
                <?php
                // Configuración
                $image_dir = 'assets/images/retro';
                // Usar ruta relativa en lugar de DOCUMENT_ROOT para compatibilidad local y producción
                if (is_dir($image_dir)) {
                    $files = scandir($image_dir);
                } else {
                    $files = [];
                }
                $images = [];

                // Función para verificar si es una imagen válida
                function is_valid_image($file) {
                    $allowed_types = [
                        'jpg', 'jpeg', 'png', 'gif', 'webp', 
                        'bmp', 'svg', 'avif', 'heic', 'tiff', 
                        'jfif', 'pjpeg', 'pjp'
                    ];
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    return in_array($ext, $allowed_types);
                }

                // Filtrar solo archivos de imagen
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_valid_image($file)) {
                        $images[] = $file;
                    }
                }

                // Si hay imágenes, crear los indicadores
                if (!empty($images)) {
                    echo '<div class="carousel-indicators">';
                    foreach ($images as $index => $image) {
                        $active = $index === 0 ? 'active' : '';
                        echo "<button type='button' 
                              data-bs-target='#retroCarousel' 
                              data-bs-slide-to='{$index}' 
                              class='{$active}'></button>";
                    }
                    echo '</div>';
                }
                ?>

                <!-- Slides -->
                <div class="carousel-inner rounded-4"><?php
                    if (!empty($images)) {
                        foreach ($images as $index => $image) {
                            $active = $index === 0 ? 'active' : '';
                            echo "<div class='carousel-item {$active}'>";
                            echo "<img src='{$image_dir}/{$image}' class='d-block w-100' alt='Imagen retro'>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='carousel-item active'>";
                        echo "<div class='no-images-placeholder'>";
                        echo "<p>No hay imágenes disponibles</p>";
                        echo "</div>";
                        echo "</div>";
                    }
                ?></div>


                <!-- Controles -->
                <button class="carousel-control-prev" type="button" data-bs-target="#retroCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#retroCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="hero-content">
                <h1>FESTIVAL RETRO</h1>
                <p>Pasión por los píxeles y las consolas clásicas. Desde cartuchos de NES hasta discos de PlayStation,
                    aquí cada partida comienza con nostalgia. Compra, vende o intercambia tus videojuegos favoritos y
                    revive esos momentos en los que un "Game Over" solo significaba volver a intentarlo.</p>
            </div>
        </div>
    </div>
</div>

<!-- Servicios Section -->
<section class="services-section py-5">
    <div class="container">
        <div class="row justify-content-center g-4">
            <!-- Envíos -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <img src="assets/images/icons/enviar.png" alt="Envíos a todo el país">
                    </div>
                    <h3>ENVIOS</h3>
                    <h4>A TODO EL PAIS</h4>
                </div>
            </div>

            <!-- Vendemos tu consola -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <img src="assets/images/icons/juego.png" alt="Vendemos tu consola">
                    </div>
                    <h3>VENDEMOS</h3>
                    <h4>TU CONSOLA</h4>
                </div>
            </div>

            <!-- Descuentos -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <img src="assets/images/icons/billete-de-banco.png" alt="Descuentos en efectivo">
                    </div>
                    <h3>DESCUENTOS</h3>
                    <h4>EN EFECTIVO</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección de Novedades -->
<section class="novedades-section">
    <h2 class="novedades-title">NOVEDADES</h2>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="horizontal-carousel" id="productCarousel">
                    <!-- Botones de navegación -->
                    <button class="carousel-control-prev" type="button" id="productCarouselPrev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" id="productCarouselNext">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>

                    <!-- Contenedor de productos con scroll horizontal -->
                    <div class="carousel-track" id="productCarouselTrack">
                        <?php if (!empty($new_products)): ?>
                        <?php foreach ($new_products as $product): ?>
                        <div class="product-slide">
                            <div class="product-card">
                                <button class="favorite-btn btn-wishlist"
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                                <?php 
                                        // Construir ruta de imagen correctamente
                                        $product_image = 'assets/images/products/product1.jpg'; // Default
                                        if (!empty($product['primary_image'])) {
                                            $product_image = 'uploads/products/' . htmlspecialchars($product['primary_image']);
                                        } elseif (!empty($product['image_url'])) {
                                            $product_image = htmlspecialchars($product['image_url']);
                                        }
                                        ?>
                                <img src="<?php echo $product_image; ?>" class="card-img-top"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    onerror="this.src='assets/images/products/product1.jpg'">
                                <div class="card-body">
                                    <h5 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <?php if (!empty($product['console_name'])): ?>
                                        <p class="product-console-name"><?php echo htmlspecialchars($product['console_name']); ?></p>
                                    <?php endif; ?>
                                    <?php 
                                            // Obtener precios y descuentos
                                            $product_price_ars = $product['price_pesos'] ?? $product['price'] ?? 0;
                                            $product_price_usd = $product['price_dollars'] ?? $product['price_usd'] ?? 0;
                                            $discountARS = $product['discount_percentage_ars'] ?? 0;
                                            $discountUSD = $product['discount_percentage_usd'] ?? 0;
                                            $isOnSale = !empty($product['is_on_sale']);
                                            
                                            // Calcular precios finales con descuento
                                            $finalPriceARS = $product_price_ars;
                                            $finalPriceUSD = $product_price_usd;
                                            
                                            if ($isOnSale && $discountARS > 0) {
                                                $finalPriceARS = $product_price_ars * (1 - ($discountARS / 100));
                                            }
                                            
                                            if ($isOnSale && $discountUSD > 0) {
                                                $finalPriceUSD = $product_price_usd * (1 - ($discountUSD / 100));
                                            }
                                            ?>
                                    
                                    <?php if ($isOnSale && $discountARS > 0): ?>
                                        <!-- Producto con descuento -->
                                        <div class="price-with-discount">
                                            <span class="discount-badge-home"><?php echo intval($discountARS); ?>% OFF</span>
                                            <p class="product-price-original-home">
                                                $<?php echo number_format($product_price_ars, 0, ',', '.'); ?>
                                            </p>
                                            <p class="product-price-discount-home">
                                                $<?php echo number_format(intval($finalPriceARS), 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <!-- Precio normal (sin descuento) -->
                                        <p class="product-price-cash">
                                            $<?php echo number_format($product_price_ars * 0.9, 0, ',', '.'); ?> En efectivo</p>
                                        <p class="product-price-card">
                                            $<?php echo number_format($product_price_ars, 0, ',', '.'); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="card-buttons">
                                        <a href="<?php echo getProductUrl($product); ?>"
                                            class="btn btn-secondary view-details me-2">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php 
                                        // Verificar si el producto ya está en el carrito
                                        $isInCart = !empty($_SESSION['cart']) && isset($_SESSION['cart'][$product['id']]);
                                        $btnClass = $isInCart ? 'btn-add-to-cart-modern in-cart' : 'btn-add-to-cart-modern';
                                        ?>
                                        <button class="<?php echo $btnClass; ?>" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-product-price="<?php echo $regular_price; ?>"
                                                data-product-image="<?php echo htmlspecialchars($product_image); ?>"
                                                data-in-cart="<?php echo $isInCart ? 'true' : 'false'; ?>">
                                            <?php if ($isInCart): ?>
                                                <!-- Producto en carrito: solo mostrar icono del carrito -->
                                                <i class="fas fa-shopping-cart cart-icon"></i>
                                            <?php else: ?>
                                                <!-- Producto no en carrito: mostrar carrito normal -->
                                                <i class="fas fa-shopping-cart cart-icon"></i>
                                                <div class="loading-spinner"></div>
                                                <i class="fas fa-check success-check"></i>
                                                <span class="btn-text">AGREGAR</span>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="product-slide">
                            <div class="text-center text-white">
                                <p>No hay productos disponibles en este momento.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección de Productos Destacados -->
<section class="featured-section">
    <h2 class="featured-title">PRODUCTOS DESTACADOS</h2>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="horizontal-carousel" id="featuredCarousel">
                    <!-- Botones de navegación -->
                    <button class="carousel-control-prev" type="button" id="featuredCarouselPrev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" id="featuredCarouselNext">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>

                    <!-- Contenedor de productos con scroll horizontal -->
                    <div class="carousel-track" id="featuredCarouselTrack">
                        <?php if (!empty($featured_products)): ?>
                        <?php foreach ($featured_products as $product): ?>
                        <div class="product-slide">
                            <div class="featured-card">
                                <button class="favorite-btn btn-wishlist"
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                                <?php 
                                    // Construir ruta de imagen correctamente
                                    $featured_image = 'assets/images/products/product1.jpg';
                                    if (!empty($product['primary_image'])) {
                                        $featured_image = 'uploads/products/' . htmlspecialchars($product['primary_image']);
                                    } elseif (!empty($product['image_url'])) {
                                        $featured_image = htmlspecialchars($product['image_url']);
                                    }
                                ?>
                                <div class="featured-card-image"
                                    style="background-image: url('<?php echo $featured_image; ?>');">
                                    <div class="featured-overlay">
                                        <div class="featured-content">
                                            <h5 class="featured-name"><?php echo htmlspecialchars($product['name']); ?>
                                            </h5>
                                            <?php if (!empty($product['console_name'])): ?>
                                                <p class="featured-console-name"><?php echo htmlspecialchars($product['console_name']); ?></p>
                                            <?php endif; ?>
                                            <p class="featured-price-cash">
                                                $<?php echo number_format($product['price_pesos'] ?? $product['price'], 0, ',', '.'); ?>
                                            </p>
                                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                            <p class="featured-price-card text-decoration-line-through">
                                                $<?php echo number_format($product['sale_price'], 0, ',', '.'); ?></p>
                                            <?php endif; ?>
                                            <div class="featured-buttons">
                                                <a href="<?php echo getProductUrl($product); ?>"
                                                    class="btn btn-outline-light view-details-featured me-2">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php 
                                                // Verificar si el producto ya está en el carrito
                                                $isInCart = !empty($_SESSION['cart']) && isset($_SESSION['cart'][$product['id']]);
                                                $btnClass = $isInCart ? 'btn-add-to-cart-modern in-cart' : 'btn-add-to-cart-modern';
                                                ?>
                                                <button class="<?php echo $btnClass; ?>" 
                                                        data-product-id="<?php echo $product['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-product-price="<?php echo $product['price_pesos'] ?? $product['price']; ?>"
                                                        data-product-image="<?php echo htmlspecialchars($featured_image); ?>"
                                                        data-in-cart="<?php echo $isInCart ? 'true' : 'false'; ?>">
                                                    <?php if ($isInCart): ?>
                                                        <!-- Producto en carrito: solo mostrar icono del carrito -->
                                                        <i class="fas fa-shopping-cart cart-icon"></i>
                                                    <?php else: ?>
                                                        <!-- Producto no en carrito: mostrar carrito normal -->
                                                        <i class="fas fa-shopping-cart cart-icon"></i>
                                                        <div class="loading-spinner"></div>
                                                        <i class="fas fa-check success-check"></i>
                                                        <span class="btn-text">AGREGAR</span>
                                                    <?php endif; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="product-slide">
                            <div class="featured-card">
                                <div class="featured-card-image"
                                    style="background: linear-gradient(135deg, #1a1a1a, #333);">
                                    <div class="featured-overlay">
                                        <div class="featured-content">
                                            <h5 class="featured-name">No hay productos destacados</h5>
                                            <p class="featured-price-cash">Próximamente nuevos productos</p>
                                            <div class="featured-buttons">
                                                <a href="productos.php" class="btn btn-outline-light">Ver todos los
                                                    productos</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Sección de Contacto -->
<section class="contact-section">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 p-0">
                <div class="contact-image-container">
                    <img src="assets/images/contacto.jpg" alt="Contacto" class="contact-image">
                </div>
            </div>
            <div class="col-md-6 col-lg-5 p-0">
                <div class="contact-container">
                    <h2 class="contact-title">CONTÁCTANOS</h2>
                    <form class="contact-form" action="contact_process.php" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" class="form-control contact-input" placeholder="Nombre"
                                        name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" class="form-control contact-input" placeholder="Apellido"
                                        name="apellido" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="email" class="form-control contact-input" placeholder="Email" name="email"
                                required>
                        </div>
                        <div class="form-group">
                            <textarea class="form-control contact-textarea" placeholder="Mensaje" name="mensaje"
                                rows="4" required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-contact">ENVIAR MENSAJE</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Estilos para descuentos en index.php - Sutil
const discountStyles = document.createElement('style');
discountStyles.textContent = `
    .price-with-discount {
        position: relative;
        text-align: center;
        margin: 10px 0;
    }
    
    .discount-badge-home {
        display: inline-block;
        background: #e53935;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        margin-bottom: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .product-price-original-home {
        font-size: 0.85rem;
        color: #999;
        text-decoration: line-through;
        margin: 5px 0 2px 0;
        opacity: 0.7;
    }
    
    .product-price-discount-home {
        font-size: 1.3rem;
        font-weight: 700;
        color: #22c55e;
        margin: 0;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }
`;
document.head.appendChild(discountStyles);

// Debug: Mostrar estado actual del carrito en la consola
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DEBUG CARRITO ===');

    // Verificar estado del carrito para cada producto
    const productIds = [1, 2, 3, 4];
    productIds.forEach(productId => {
        fetch(`ajax/check-cart-item.php?product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                console.log(`Producto ${productId}:`, data);
            })
            .catch(error => console.error(`Error verificando producto ${productId}:`, error));
    });

    // Verificar contador total del carrito
    fetch('ajax/get-cart-count.php')
        .then(response => response.json())
        .then(data => {
            console.log('Estado general del carrito:', data);
        })
        .catch(error => console.error('Error cargando estado del carrito:', error));
});
</script>

<?php include 'includes/footer.php'; ?>