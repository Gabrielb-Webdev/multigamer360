<?php 
/**
 * Product Details Page - MultiGamer360
 * Last Updated: 2025-10-17 22:30
 * Version: 3.0 - Dark Theme + Database Integration
 * Based on working demo with database connection
 */

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración y clases necesarias
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/product_manager.php';

// Crear instancia del manager de productos
$productManager = new ProductManager($pdo);

include 'includes/header.php'; 

// Obtener ID del producto
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Obtener producto desde la base de datos
$current_product = $productManager->getProductById($product_id);

// Si no se encuentra el producto, redirigir
if (!$current_product) {
    header('Location: productos.php');
    exit;
}

// Asegurar valores por defecto para campos que pueden no existir
$current_product['stock_quantity'] = $current_product['stock_quantity'] ?? 0;
$current_product['price_pesos'] = $current_product['price_pesos'] ?? 0;
$current_product['discount_percentage'] = $current_product['discount_percentage'] ?? 0;
$current_product['is_new'] = $current_product['is_new'] ?? 0;
$current_product['is_featured'] = $current_product['is_featured'] ?? 0;
$current_product['is_on_sale'] = $current_product['is_on_sale'] ?? 0;
$current_product['description'] = $current_product['description'] ?? '';
$current_product['long_description'] = $current_product['long_description'] ?? $current_product['description'];
$current_product['image_url'] = $current_product['image_url'] ?? 'product1.jpg';
$current_product['sku'] = $current_product['sku'] ?? 'N/A';
$current_product['category_name'] = $current_product['category_name'] ?? 'Sin categoría';
$current_product['brand_name'] = $current_product['brand_name'] ?? 'Sin marca';

// Obtener productos relacionados de la misma categoría
$related_products = [];
if (!empty($current_product['category_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
            LIMIT 4
        ");
        $stmt->execute([$current_product['category_id'], $product_id]);
        $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting related products: " . $e->getMessage());
    }
}

// ELIMINAR ESTA SECCIÓN DE DEMO_PRODUCTS - YA NO LA NECESITAMOS
$demo_products_old = [
    1 => [
        'id' => 1,
        'name' => 'Kingdom Hearts',
        'slug' => 'kingdom-hearts',
        'description' => 'Kingdom Hearts es un videojuego de rol de acción desarrollado y publicado por Square en colaboración con Disney Interactive. Es el primer juego de la serie Kingdom Hearts.',
        'long_description' => 'Kingdom Hearts es un videojuego de rol de acción desarrollado y publicado por Square en colaboración con Disney Interactive. Es el primer juego de la serie Kingdom Hearts y el resultado de una colaboración entre Square y The Walt Disney Company. El juego combina personajes y escenarios de las películas animadas de Disney con los de la serie Final Fantasy de Square.',
        'price_pesos' => 100.00,
        'price_dollars' => 10.00,
        'stock_quantity' => 15,
        'min_stock_level' => 5,
        'category_name' => 'Videojuegos',
        'brand_name' => 'Square Enix',
        'console_name' => 'PlayStation 2',
        'is_new' => 1,
        'is_featured' => 1,
        'is_on_sale' => 0,
        'discount_percentage' => 0,
        'image_url' => 'product1.jpg',
        'sku' => 'KINGDOM-5050'
    ],
    2 => [
        'id' => 2,
        'name' => 'Kingdom Hearts 2',
        'slug' => 'kingdom-hearts-2',
        'description' => 'Kingdom Hearts II es la secuela de Kingdom Hearts y Kingdom Hearts: Chain of Memories. Al igual que sus predecesores, el videojuego fue desarrollado por Square Enix.',
        'long_description' => 'Kingdom Hearts II es la secuela de Kingdom Hearts y Kingdom Hearts: Chain of Memories. Al igual que sus predecesores, el videojuego fue desarrollado por Square Enix. Continúa la historia de Sora, Donald y Goofy mientras viajan por varios mundos de Disney.',
        'price_pesos' => 120.00,
        'price_dollars' => 12.00,
        'stock_quantity' => 10,
        'min_stock_level' => 3,
        'category_name' => 'Videojuegos',
        'brand_name' => 'Square Enix',
        'console_name' => 'PlayStation 2',
        'is_new' => 0,
        'is_featured' => 1,
        'is_on_sale' => 1,
        'discount_percentage' => 10,
        'image_url' => 'product2.jpg',
        'sku' => 'KINGDOM-3600'
    ],
    3 => [
        'id' => 3,
        'name' => 'Final Fantasy VII',
        'slug' => 'final-fantasy-vii',
        'description' => 'Final Fantasy VII es un videojuego de rol desarrollado por la compañía japonesa Square para la consola PlayStation.',
        'long_description' => 'Final Fantasy VII es un videojuego de rol desarrollado por la compañía japonesa Square para la consola PlayStation. Es la séptima entrega numerada de la serie Final Fantasy y la primera en utilizar gráficos 3D. Cuenta la historia de Cloud Strife, un mercenario que se une a un grupo ecoterrorista para luchar contra una corporación corrupta.',
        'price_pesos' => 150.00,
        'price_dollars' => 15.00,
        'stock_quantity' => 8,
        'min_stock_level' => 2,
        'category_name' => 'Videojuegos',
        'brand_name' => 'Square Enix',
        'console_name' => 'PlayStation',
        'is_new' => 0,
        'is_featured' => 1,
        'is_on_sale' => 0,
        'discount_percentage' => 0,
        'image_url' => 'product3.jpg',
        'sku' => 'FF7-5951'
    ],
    10 => [
        'id' => 10,
        'name' => 'PlayStation 5',
        'slug' => 'ps5',
        'description' => 'PlayStation 5 (PS5) es la consola de videojuegos de sobremesa de novena generación desarrollada por Sony Interactive Entertainment.',
        'long_description' => 'PlayStation 5 (PS5) es la consola de videojuegos de sobremesa de novena generación desarrollada por Sony Interactive Entertainment. Lanzada en noviembre de 2020, es la sucesora de PlayStation 4. Cuenta con un procesador AMD Zen 2, gráficos AMD RDNA 2 y un SSD ultrarrápido.',
        'price_pesos' => 200.00,
        'price_dollars' => 20.00,
        'stock_quantity' => 5,
        'min_stock_level' => 1,
        'category_name' => 'Consolas',
        'brand_name' => 'Sony',
        'console_name' => 'PlayStation 5',
        'is_new' => 1,
        'is_featured' => 1,
        'is_on_sale' => 0,
        'discount_percentage' => 0,
        'image_url' => 'product4.jpg',
        'sku' => 'PS5X-141239'
    ]
];

// Obtener el producto actual
$current_product = $demo_products[$product_id] ?? $demo_products[1];
?>

<div class="container-fluid product-details-container">

    <div class="row product-detail-row">
        <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">INICIO</a></li>
            <li class="breadcrumb-item"><a href="productos.php">PRODUCTOS</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($current_product['name']); ?></li>
        </ol>
    </nav>
        <!-- Product Images Section -->
        <div class="col-md-6">
            <div class="product-image-section">
                <!-- Main Product Image -->
                <div class="main-product-image">
                    <?php 
                    $image_filename = $current_product['image_url'];
                    $product_image = 'assets/images/products/' . $image_filename;
                    
                    // Fallback a imagen por defecto si no existe
                    if (!file_exists($product_image)) {
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
                    <div class="thumbnail-item">
                        <img src="assets/images/products/product3.jpg" alt="Imagen 3" class="img-fluid">
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
                    <?php if ($current_product['stock_quantity'] <= 0): ?>
                        <span class="badge bg-danger">SIN STOCK</span>
                    <?php elseif ($current_product['stock_quantity'] <= 5): ?>
                        <span class="badge bg-warning">POCAS UNIDADES</span>
                    <?php endif; ?>
                    <?php if ($current_product['is_on_sale']): ?>
                        <span class="badge bg-danger">OFERTA</span>
                    <?php endif; ?>
                </div>

                <!-- Product Description -->
                <?php if (!empty($current_product['description'])): ?>
                    <p class="product-description"><?php echo nl2br(htmlspecialchars($current_product['description'])); ?></p>
                <?php endif; ?>
                
                <!-- Stock information -->
                <div class="stock-info-detail">
                    <?php if ($current_product['stock_quantity'] > 0): ?>
                        <span class="stock-available">
                            <i class="fas fa-check-circle text-success"></i> 
                            <?php echo $current_product['stock_quantity']; ?> unidades disponibles
                        </span>
                    <?php else: ?>
                        <span class="stock-unavailable">
                            <i class="fas fa-times-circle text-danger"></i> 
                            Sin stock disponible
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- SKU and Category -->
                <div class="product-meta mt-3">
                    <p><strong>SKU:</strong> <?php echo htmlspecialchars($current_product['sku']); ?></p>
                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($current_product['category_name']); ?></p>
                    <p><strong>Marca:</strong> <?php echo htmlspecialchars($current_product['brand_name']); ?></p>
                    <?php if (!empty($current_product['console_name'])): ?>
                        <p><strong>Consola:</strong> <?php echo htmlspecialchars($current_product['console_name']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="product-pricing">
                    <?php 
                    // Calcular precios
                    $product_price = $current_product['price_pesos'];
                    $discount = $current_product['discount_percentage'];
                    
                    if ($discount > 0) {
                        $regular_price = $product_price;
                        $sale_price = $product_price * (1 - ($discount / 100));
                        $cash_price = $sale_price * 0.9;
                    } else {
                        $regular_price = $product_price;
                        $cash_price = $product_price * 0.9;
                    }
                    ?>
                    
                    <?php if ($discount > 0): ?>
                        <div class="price-original">
                            <span class="text-decoration-line-through">$<?php echo number_format($regular_price, 2, ',', '.'); ?></span>
                            <span class="badge bg-danger ms-2"><?php echo $discount; ?>% OFF</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="price-cash">
                        <span class="price-value">$<?php echo number_format($cash_price, 2, ',', '.'); ?></span>
                        <span class="price-label">En efectivo</span>
                    </div>
                    <div class="price-card">
                        $<?php echo number_format($discount > 0 ? $sale_price : $regular_price, 2, ',', '.'); ?>
                    </div>
                </div>

                <div class="payment-options">
                    <div class="discount-info">
                        <i class="fas fa-credit-card"></i>
                        <span>En 4 cuotas sin interés <strong>$<?php echo number_format(($discount > 0 ? $sale_price : $regular_price) / 4, 2, ',', '.'); ?></strong></span>
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
                    <?php if ($current_product['stock_quantity'] > 0): ?>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn minus">-</button>
                            <input type="number" 
                                   class="quantity-input" 
                                   value="1" 
                                   min="1" 
                                   max="<?php echo $current_product['stock_quantity']; ?>"
                                   id="product-quantity">
                            <button type="button" class="quantity-btn plus">+</button>
                        </div>
                        
                        <button class="btn btn-danger btn-lg" style="padding: 15px 40px;">
                            <i class="fas fa-shopping-cart"></i> AGREGAR AL CARRITO
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-ban"></i>
                            SIN STOCK
                        </button>
                    <?php endif; ?>
                </div>

                <div class="shipping-calculator mt-4">
                    <h5>Calcular envío</h5>
                    <div class="shipping-form">
                        <input type="text" class="form-control" placeholder="Tu código postal">
                        <button type="button" class="btn btn-primary btn-calculate">CALCULAR</button>
                    </div>
                    <small class="shipping-note">No sé mi código postal</small>
                </div>

                <div class="product-description mt-4">
                    <h5>Descripción Completa</h5>
                    <?php if (!empty($current_product['long_description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($current_product['long_description'])); ?></p>
                    <?php else: ?>
                        <p>Producto de alta calidad disponible en MultiGamer360. ¡No te lo pierdas!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Similar Products Section -->
    <section class="similar-products-section mt-5">
        <h2 class="similar-products-title">OTROS PRODUCTOS QUE TE PUEDEN INTERESAR</h2>
        <div class="row">
            <?php 
            // Mostrar otros productos demo
            $other_products = array_filter($demo_products, function($p) use ($product_id) {
                return $p['id'] != $product_id;
            });
            $count = 0;
            foreach (array_slice($other_products, 0, 4) as $product): 
                $count++;
            ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="similar-product-card">
                    <img src="assets/images/products/product<?php echo $count; ?>.jpg" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="img-fluid"
                         onerror="this.src='assets/images/products/product1.jpg'">
                    <div class="similar-product-info">
                        <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                        <div class="similar-price">
                            <span class="similar-price-cash">$<?php echo number_format($product['price_pesos'] * 0.9, 0, ',', '.'); ?></span>
                            <span class="similar-price-label">En efectivo</span>
                        </div>
                        <div class="similar-price-card">$<?php echo number_format($product['price_pesos'], 0, ',', '.'); ?></div>
                        <a href="product-details-demo.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-danger mt-2">
                            VER DETALLES
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>



<?php include 'includes/footer.php'; ?>

<script>
// Funcionalidad para los botones de cantidad
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.querySelector('.quantity-input');
    const minusBtn = document.querySelector('.quantity-btn.minus');
    const plusBtn = document.querySelector('.quantity-btn.plus');
    const maxStock = <?php echo $current_product['stock_quantity']; ?>;

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

    console.log('Product Details Demo loaded successfully!');
    console.log('Product:', <?php echo json_encode($current_product); ?>);
});
</script>

<!-- 
╔══════════════════════════════════════════════════════════════════╗
║  PRODUCT DETAILS PAGE - DEMO VERSION 2.5                         ║
║  Last Updated: 2025-10-17 at 22:00                               ║
║  HARDCODED DATA - NO DATABASE REQUIRED                           ║
║  Purpose: Testing and debugging display issues                   ║
╚══════════════════════════════════════════════════════════════════╝
-->

