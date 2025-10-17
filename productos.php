<?php
/**
 * =====================================================
 * MULTIGAMER360 - PÁGINA DE PRODUCTOS (RENOVADA)
 * =====================================================
 * 
 * Descripción: Página principal para mostrar productos con filtros y búsqueda
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-16 (Renovación de Cards)
 * 
 * Funcionalidades:
 * - Listado de productos con paginación
 * - Filtros por categoría, marca y precio
 * - Búsqueda de productos
 * - Sistema de carrito y wishlist
 * - Nuevas cards de producto modernas
 */

// =====================================================
// ACTIVAR REPORTE DE ERRORES (TEMPORAL PARA DEBUG)
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// =====================================================
// CONFIGURACIÓN INICIAL
// =====================================================

try {
    // Incluir configuración de sesión
    require_once 'config/session_config.php';
    
    // Inicializar sesión con configuración segura
    $sessionId = initSecureSession();
    // =====================================================
    
    // Incluir archivos necesarios
    require_once 'config/database.php';
    require_once 'includes/auth.php';
    require_once 'includes/product_manager.php';
    require_once 'includes/smart_filters_v2.php'; // ACTUALIZADO: Nueva versión con tablas relacionales
    
} catch (Exception $e) {
    die("Error fatal al cargar archivos: " . $e->getMessage() . "<br>Archivo: " . $e->getFile() . "<br>Línea: " . $e->getLine());
}

// =====================================================
// INICIALIZAR SISTEMA DE FILTROS INTELIGENTES V2
// =====================================================

try {
    $smartFilters = new SmartFilters($pdo);
} catch (Exception $e) {
    die("Error al inicializar SmartFilters: " . $e->getMessage());
}

// Procesar filtros aplicados desde la URL
$appliedFilters = [];

// Categorías (IDs de categorías seleccionadas)
if (!empty($_GET['categories'])) {
    $appliedFilters['categories'] = array_map('intval', explode(',', $_GET['categories']));
}

// Marcas (IDs de marcas seleccionadas)
if (!empty($_GET['brands'])) {
    $appliedFilters['brands'] = array_map('intval', explode(',', $_GET['brands']));
}

// Consolas (IDs de consolas seleccionadas - ACTUALIZADO)
if (!empty($_GET['consoles'])) {
    $appliedFilters['consoles'] = array_map('intval', explode(',', $_GET['consoles']));
}

// Géneros (IDs de géneros seleccionados - ACTUALIZADO)
if (!empty($_GET['genres'])) {
    $appliedFilters['genres'] = array_map('intval', explode(',', $_GET['genres']));
}

// Obtener todos los filtros disponibles (solo compatibles con selección actual)
$availableFilters = $smartFilters->getAllAvailableFilters($appliedFilters);

// =====================================================
// PROCESAMIENTO DE PARÁMETROS Y FILTROS
// =====================================================

// Inicializar manager de productos
$productManager = new ProductManager($pdo);

// Construir filtros para consulta de productos
$filters = [];

// Aplicar categorías seleccionadas
if (!empty($appliedFilters['categories'])) {
    $filters['categories'] = $appliedFilters['categories'];
}

// Aplicar marcas seleccionadas
if (!empty($appliedFilters['brands'])) {
    $filters['brands'] = $appliedFilters['brands'];
}

// Aplicar consolas seleccionadas
if (!empty($appliedFilters['consoles'])) {
    $filters['consoles'] = $appliedFilters['consoles'];
}

// Aplicar géneros seleccionados
if (!empty($appliedFilters['genres'])) {
    $filters['genres'] = $appliedFilters['genres'];
}

// Aplicar búsqueda si existe
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Aplicar filtros de precio
if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
    $filters['min_price'] = (float)$_GET['min_price'];
}
if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
    $filters['max_price'] = (float)$_GET['max_price'];
}

// Configurar paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20; // Productos por página
$offset = ($page - 1) * $limit;

// Configurar ordenamiento
$order_options = [
    'price-asc' => 'p.price_pesos ASC',
    'price-desc' => 'p.price_pesos DESC', 
    'name-asc' => 'p.name ASC',
    'name-desc' => 'p.name DESC',
    'newest' => 'p.created_at DESC',
    'stock' => 'p.stock_quantity DESC'
];

$order_by = $order_options[$_GET['order'] ?? 'newest'] ?? 'p.created_at DESC';

// Aplicar configuración a filtros
$filters['order_by'] = $order_by;
$filters['limit'] = $limit;
$filters['offset'] = $offset;

// =====================================================
// INICIALIZAR VARIABLES DE VISTA
// =====================================================

// Variable para saber si hay filtros dinámicos disponibles
$dynamicFiltersAvailable = false; // Sistema de filtros estático por ahora

// Detectar si estamos en vista de videojuegos
$isVideoGamesView = false;
$currentConsole = null;

// Verificar si hay categoría de videojuegos seleccionada
if (!empty($appliedFilters['categories'])) {
    foreach ($availableFilters['categories'] as $cat) {
        if (in_array($cat['id'], $appliedFilters['categories']) && 
            (strtolower($cat['slug']) === 'videojuegos' || strtolower($cat['name']) === 'videojuegos')) {
            $isVideoGamesView = true;
            break;
        }
    }
}

// Verificar si hay una consola específica seleccionada
if (!empty($appliedFilters['consoles']) && count($appliedFilters['consoles']) === 1) {
    $consoleId = $appliedFilters['consoles'][0];
    foreach ($availableFilters['consoles'] as $console) {
        if ($console['id'] == $consoleId) {
            $currentConsole = $console;
            break;
        }
    }
}

// =====================================================
// OBTENER DATOS
// =====================================================

try {
    // DEBUG: Ver qué filtros se están aplicando
    error_log("=== DEBUG PRODUCTOS.PHP ===");
    error_log("Filtros aplicados: " . print_r($filters, true));
    
    // Obtener productos usando la función apropiada según disponibilidad
    if ($dynamicFiltersAvailable && !empty($filters['tags'])) {
        $products = $productManager->getProductsWithDynamicFilters($filters);
        $totalProducts = $productManager->countProductsWithDynamicFilters($filters);
    } else {
        $products = $productManager->getProducts($filters);
        $totalProducts = $productManager->countProducts($filters);
    }
    
    // DEBUG: Ver cuántos productos se obtuvieron
    error_log("Total productos obtenidos: " . count($products));
    error_log("Total count productos: " . $totalProducts);
    error_log("==========================");
    
    // Debug visible en HTML (solo para desarrollo)
    echo "<!-- DEBUG: Filtros aplicados: " . htmlspecialchars(print_r($filters, true)) . " -->";
    echo "<!-- DEBUG: Total productos encontrados: " . count($products) . " -->";
    echo "<!-- DEBUG: Total count: " . $totalProducts . " -->";
    
    if (count($products) > 0) {
        echo "<!-- DEBUG: Primer producto: " . htmlspecialchars(print_r($products[0], true)) . " -->";
    }
    
    $totalPages = ceil($totalProducts / $limit);
    $categories = $productManager->getCategories();
    $brands = $productManager->getBrands();
    
    // Obtener wishlist del usuario si está logueado
    $userWishlist = [];
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT product_id FROM user_favorites WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userWishlist = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'product_id');
    }
    
    // =====================================================
    // OBTENER PRODUCTOS EN CARRITO (PARA MOSTRAR ESTADO CORRECTO INMEDIATAMENTE)
    // =====================================================
    $productsInCart = [];
    if (!empty($_SESSION['cart'])) {
        $productsInCart = array_keys($_SESSION['cart']);
    }
    
} catch (Exception $e) {
    error_log("Error en productos.php: " . $e->getMessage());
    $products = [];
    $totalProducts = 0;
    $totalPages = 0;
    $categories = [];
    $brands = [];
    $userWishlist = [];
    $productsInCart = [];
}

// =====================================================
// INCLUIR HEADER
// =====================================================
require_once 'includes/header.php';
?>

<!-- CSS específico para selector de consolas -->
<link rel="stylesheet" href="assets/css/console-selector.css?v=0.2">

<!-- =====================================================
     CONTENIDO PRINCIPAL
     ===================================================== -->

<main class="productos-page">
    <div class="container-fluid">
        <div class="row">

            <!-- =====================================================
                 FILTROS LATERALES (OCULTOS POR DEFECTO EN MÓVIL)
                 ===================================================== -->
            <div class="col-lg-3 col-md-4" id="filters-column">
                <!-- Botón para mostrar filtros en móvil -->
                <div class="mobile-filters-trigger d-lg-none">
                    <button class="btn btn-filters-toggle w-100" id="toggle-filters-btn" onclick="toggleMobileFilters()">
                        <i class="fas fa-filter me-2"></i>
                        <span id="filters-btn-text">Mostrar Filtros</span>
                        <i class="fas fa-chevron-down ms-2" id="filters-chevron"></i>
                    </button>
                </div>
                
                <!-- Overlay para cerrar el drawer -->
                <div class="filters-overlay" id="filters-overlay" onclick="closeMobileFilters()"></div>
                
                <div class="filters-sidebar" id="filters-sidebar">
                    <!-- Header del drawer -->
                    <div class="drawer-header">
                        <h5 class="drawer-title">
                            <i class="fas fa-filter"></i> FILTROS
                        </h5>
                        <button class="btn-close-drawer" onclick="closeMobileFilters()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Breadcrumb / Navegación -->
                    <?php if ($currentConsole && !$isVideoGamesView): ?>
                    <div class="console-breadcrumb-filter">
                        <a href="productos.php?category=videojuegos" class="back-link">
                            <i class="fas fa-arrow-left"></i> Volver a Consolas
                        </a>
                        <div class="current-console">
                            <i class="fas fa-gamepad"></i>
                            <strong><?php echo htmlspecialchars($currentConsole['name']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- =====================================================
                         FILTRO 1: CATEGORÍAS
                         ===================================================== -->
                    <?php if (!empty($availableFilters['categories'])): ?>
                    <div class="filter-section">
                        <h5 class="filter-title collapsible-filter" data-target="categories-filter">
                            <i class="fas fa-th-large"></i> Categorías
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h5>
                        <div class="filter-options collapse-content" id="categories-filter">
                            <?php 
                            $selectedCategories = $appliedFilters['categories'] ?? [];
                            foreach ($availableFilters['categories'] as $category): 
                            ?>
                            <div class="filter-option">
                                <input type="checkbox" 
                                       id="cat_<?php echo $category['id']; ?>" 
                                       class="filter-checkbox category-filter"
                                       value="<?php echo $category['id']; ?>"
                                       <?php echo in_array($category['id'], $selectedCategories) ? 'checked' : ''; ?>>
                                <label for="cat_<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <span class="filter-count">(<?php echo $category['product_count']; ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- =====================================================
                         FILTRO 2: PRECIO
                         ===================================================== -->
                    <div class="filter-section">
                        <h5 class="filter-title">
                            <i class="fas fa-dollar-sign"></i> Precio
                        </h5>
                        <div class="filter-options">
                            <?php if (!empty($availableFilters['price_range'])): ?>
                            <div class="price-range-info mb-2">
                                <small class="text-muted">
                                    Rango: $<?php echo number_format($availableFilters['price_range']['min'], 0, ',', '.'); ?> 
                                    - $<?php echo number_format($availableFilters['price_range']['max'], 0, ',', '.'); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            <div class="price-inputs">
                                <input type="number" 
                                       id="price-min" 
                                       class="price-input"
                                       placeholder="Mínimo"
                                       value="<?php echo $_GET['min_price'] ?? ''; ?>"
                                       <?php if (!empty($availableFilters['price_range'])): ?>
                                       min="<?php echo $availableFilters['price_range']['min']; ?>"
                                       <?php endif; ?>>
                                <span class="price-separator">-</span>
                                <input type="number" 
                                       id="price-max" 
                                       class="price-input"
                                       placeholder="Máximo"
                                       value="<?php echo $_GET['max_price'] ?? ''; ?>"
                                       <?php if (!empty($availableFilters['price_range'])): ?>
                                       max="<?php echo $availableFilters['price_range']['max']; ?>"
                                       <?php endif; ?>>
                            </div>
                        </div>
                    </div>

                    <!-- =====================================================
                         FILTRO 3: MARCAS
                         ===================================================== -->
                    <?php if (!empty($availableFilters['brands'])): ?>
                    <div class="filter-section">
                        <h5 class="filter-title collapsible-filter" data-target="brands-filter">
                            <i class="fas fa-industry"></i> Marcas
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h5>
                        <div class="filter-options collapse-content" id="brands-filter">
                            <?php 
                            $selectedBrands = $appliedFilters['brands'] ?? [];
                            foreach ($availableFilters['brands'] as $brand): 
                            ?>
                            <div class="filter-option">
                                <input type="checkbox" 
                                       id="brand_<?php echo $brand['id']; ?>" 
                                       class="filter-checkbox brand-filter"
                                       value="<?php echo $brand['id']; ?>"
                                       <?php echo in_array($brand['id'], $selectedBrands) ? 'checked' : ''; ?>>
                                <label for="brand_<?php echo $brand['id']; ?>">
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                    <span class="filter-count">(<?php echo $brand['product_count']; ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- =====================================================
                         FILTRO 4: CONSOLAS
                         ===================================================== -->
                    <?php if (!empty($availableFilters['consoles'])): ?>
                    <div class="filter-section">
                        <h5 class="filter-title collapsible-filter" data-target="consoles-filter">
                            <i class="fas fa-gamepad"></i> Consolas
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h5>
                        <div class="filter-options collapse-content" id="consoles-filter">
                            <?php 
                            $selectedConsoles = $appliedFilters['consoles'] ?? [];
                            foreach ($availableFilters['consoles'] as $console): 
                            ?>
                            <div class="filter-option">
                                <input type="checkbox" 
                                       id="console_<?php echo $console['id']; ?>" 
                                       class="filter-checkbox console-filter"
                                       value="<?php echo $console['id']; ?>"
                                       <?php echo in_array($console['id'], $selectedConsoles) ? 'checked' : ''; ?>>
                                <label for="console_<?php echo $console['id']; ?>">
                                    <?php echo htmlspecialchars($console['name']); ?>
                                    <span class="filter-count">(<?php echo $console['product_count']; ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- =====================================================
                         FILTRO 5: GÉNEROS
                         ===================================================== -->
                    <?php if (!empty($availableFilters['genres'])): ?>
                    <div class="filter-section">
                        <h5 class="filter-title collapsible-filter" data-target="genres-filter">
                            <i class="fas fa-tags"></i> Géneros
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h5>
                        <div class="filter-options collapse-content" id="genres-filter">
                            <?php 
                            $selectedGenres = $appliedFilters['genres'] ?? [];
                            foreach ($availableFilters['genres'] as $genre): 
                            ?>
                            <div class="filter-option">
                                <input type="checkbox" 
                                       id="genre_<?php echo $genre['id']; ?>" 
                                       class="filter-checkbox genre-filter"
                                       value="<?php echo $genre['id']; ?>"
                                       <?php echo in_array($genre['id'], $selectedGenres) ? 'checked' : ''; ?>>
                                <label for="genre_<?php echo $genre['id']; ?>">
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                    <span class="filter-count">(<?php echo $genre['product_count']; ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Botones de acciones de filtros -->
                    <div class="filter-actions">
                        <button id="apply-filters-btn" class="btn btn-custom-primary btn-sm w-100 mb-2" onclick="applyAllFilters()">
                            <i class="fas fa-check"></i> Aplicar Filtros <span id="filter-count" class="badge bg-light text-dark ms-1" style="display: none;">0</span>
                        </button>
                        <button id="clear-filters-btn" class="btn btn-outline-light btn-sm w-100" onclick="clearAllFilters()">
                            <i class="fas fa-eraser"></i> Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 ÁREA PRINCIPAL DE PRODUCTOS
                 ===================================================== -->
            <div class="col-lg-9 col-md-8">
                
                <!-- Toolbar con información y ordenamiento -->
                <div class="products-toolbar">
                    <div class="products-info">
                        <span class="products-count">
                            <i class="fas fa-box"></i>
                            Mostrando <?php echo min($limit, $totalProducts); ?> de <?php echo $totalProducts; ?> productos
                        </span>
                    </div>
                    <div class="products-sort">
                        <select id="sort-products" class="form-select" onchange="changeSort()">
                            <option value="newest" <?php echo ($_GET['order'] ?? 'newest') == 'newest' ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="price-asc" <?php echo ($_GET['order'] ?? '') == 'price-asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                            <option value="price-desc" <?php echo ($_GET['order'] ?? '') == 'price-desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                            <option value="name-asc" <?php echo ($_GET['order'] ?? '') == 'name-asc' ? 'selected' : ''; ?>>Nombre: A-Z</option>
                            <option value="name-desc" <?php echo ($_GET['order'] ?? '') == 'name-desc' ? 'selected' : ''; ?>>Nombre: Z-A</option>
                            <option value="stock" <?php echo ($_GET['order'] ?? '') == 'stock' ? 'selected' : ''; ?>>Stock disponible</option>
                        </select>
                    </div>
                </div>

                <!-- =====================================================
                     SELECTOR DE CONSOLAS (Cuando se selecciona "Videojuegos")
                     ===================================================== -->
                <?php if ($isVideoGamesView && !$currentConsole && false): // Temporalmente deshabilitado ?>
                    <div class="console-selector-section">
                        <h2 class="section-title">Selecciona tu Consola</h2>
                        <p class="section-subtitle">Explora nuestra colección de videojuegos por plataforma</p>
                        <?php // echo $videoGameFilters->renderConsoleGrid(); ?>
                    </div>
                <?php endif; ?>

                <!-- =====================================================
                     GRID DE PRODUCTOS (ESTRUCTURA LIMPIA)
                     ===================================================== -->
                <div class="products-grid" id="products-grid">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
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
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" 
                                   class="btn-view-fixed"
                                   title="Ver detalles del producto">
                                    <i class="far fa-eye"></i>
                                </a>
                                
                                <!-- Imagen de fondo que ocupa toda la card -->
                                <?php 
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
                                ?>
                                <div class="product-image-background" 
                                     style="background-image: url('<?php echo htmlspecialchars($product_image); ?>');"
                                     data-fallback="assets/images/products/product1.jpg">
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
                    <?php else: ?>
                        <!-- Mensaje cuando no hay productos -->
                        <div class="no-products">
                            <div class="no-products-content">
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <h4>No se encontraron productos</h4>
                                <p>Intenta ajustar los filtros o realizar una búsqueda diferente.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- =====================================================
                     PAGINACIÓN
                     ===================================================== -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación de productos" class="mt-4" id="products-pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<!-- =====================================================
     ESTILOS CSS BÁSICOS
     ===================================================== -->
<style>
/* =====================================================
   PRODUCTOS - ESTILOS BÁSICOS PARA NUEVAS CARDS
   ===================================================== */

/* Contenedor principal */
.productos-page {
    min-height: 100vh;
    padding: 20px 0;
}

.productos-page .container-fluid {
    max-width: 1400px;
    margin: 0 auto;
}

/* Grid de productos optimizado - 4 columnas exactas */
.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* Exactamente 4 columnas */
    gap: 20px;
    margin-top: 24px;
    padding: 0;
    max-width: 100%;
    width: 100%;
}

/* Nueva card de producto - alta con imagen completa */
.product-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    border: 1px solid #ddd;
    transition: all 0.3s ease;
    height: 380px; /* Reducir altura para contenido simplificado */
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

/* Imagen de fondo que ocupa toda la card */
.product-image-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    transition: transform 0.4s ease;
}

.product-card:hover .product-image-background {
    transform: scale(1.05);
}

/* Overlay con degradado para legibilidad del texto */
.product-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.50) 0%,
        rgba(0, 0, 0, 0.65) 60%,
        rgba(0, 0, 0, 0.4) 85%,
        transparent 100%
    );
    color: white;
    padding: 20px 15px 15px;
    transform: translateY(20px); /* Ocultar por defecto */
    transition: all 0.3s ease;
    opacity: 0; /* Completamente oculto por defecto */
    min-height: 140px; /* Reducir altura para contenido simplificado */
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.product-card:hover .product-overlay {
    transform: translateY(0);
    opacity: 1; /* Completamente visible al hacer hover */
}

/* Información del producto en overlay */
.product-info-overlay {
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 5px; /* Reducir espaciado para acercar elementos */
}

.product-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;
    overflow: hidden;
}

.product-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #00ff88;
    margin-bottom: 5px;
}

.product-stock {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 3px;
}

.product-category {
    font-size: 0.8rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px; /* Reducir espacio para el botón */
}

/* Nuevos estilos para información del producto */
.product-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.product-console {
    color: #007bff;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.product-condition {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.condition-used {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.condition-refurbished {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.condition-digital {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.condition-collector {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.product-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 8px;
}

.product-developer,
.product-genre {
    font-size: 0.75rem;
    opacity: 0.8;
    display: flex;
    align-items: center;
    gap: 4px;
}

.product-rating {
    margin-bottom: 8px;
}

.product-rating .stars {
    display: flex;
    align-items: center;
    gap: 2px;
}

.product-rating .stars i {
    color: #ffc107;
    font-size: 0.85rem;
}

.product-rating .rating-value {
    margin-left: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #ffc107;
}

.product-pricing {
    margin-bottom: 5px;
}

.product-price-usd {
    font-size: 0.85rem;
    color: #6c757d;
    margin: 0;
    font-weight: 500;
}

/* Estilos para la nueva estructura simplificada */
.product-console {
    display: flex;
    justify-content: flex-start;
}

.console-name {
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-block;
}

.product-price-simple {
    font-size: 1.4rem;
    font-weight: 700;
    color: #00ff88;
    margin: 15px 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* Botón wishlist fijo siempre visible */
.btn-wishlist-fixed {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
    border: none;
    border-radius: 50%;
    color: #666;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-wishlist-fixed:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-wishlist-fixed.active {
    background: rgba(220, 53, 69, 0.9);
    color: white;
}

.btn-wishlist-fixed.active:hover {
    background: rgba(220, 53, 69, 1);
}

/* Botón de vista de producto fijo siempre visible */
.btn-view-fixed {
    position: absolute;
    top: 58px; /* Debajo del botón de wishlist con más espacio */
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
    border: none;
    border-radius: 50%;
    color: #666;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    text-decoration: none;
}

.btn-view-fixed:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: #8B0000;
    text-decoration: none;
}

/* Botones de acción - ahora solo carrito */
.product-actions {
    display: flex;
    align-items: center;
}

/* Botón agregar al carrito - ahora ocupa todo el ancho */
.btn-add-cart {
    background: linear-gradient(135deg, #8B0000, #6B0000);
    border: none;
    border-radius: 8px;
    color: white;
    padding: 8px 12px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 40px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    position: relative;
    overflow: hidden;
}

/* Espacio reservado para los nuevos estilos del botón */
.cart-button-placeholder {
    width: 100%;
    height: 45px;
    margin: 8px 0;
}

/* Botón sin stock */
.btn-no-stock {
    background: #6c757d;
    border: none;
    border-radius: 6px;
    color: white;
    padding: 10px 16px;
    font-size: 0.9rem;
    cursor: not-allowed;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 44px;
    opacity: 0.7;
}

/* Botón personalizado para filtros */
.btn-custom-primary {
    background: linear-gradient(135deg, #8B0000, #6B0000);
    border: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-custom-primary:hover {
    background: linear-gradient(135deg, #A50000, #8B0000);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
}

/* =====================================================
   SISTEMA DE FILTROS OCULTOS
   ===================================================== */

/* Botón para mostrar filtros en móvil */
.mobile-filters-trigger {
    margin-bottom: 20px;
}

.btn-filters-toggle {
    background: linear-gradient(135deg, var(--primary-color), #a00000);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 12px 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-filters-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 0, 0, 0.4);
    background: linear-gradient(135deg, #a00000, var(--primary-color));
}

.btn-filters-toggle:active {
    transform: translateY(0);
}

/* Animación del chevron */
#filters-chevron {
    transition: transform 0.3s ease;
}

#filters-chevron.rotated {
    transform: rotate(180deg);
}

/* Filtros sidebar - ocultos por defecto en móvil */
.filters-sidebar {
    padding-right: 15px;
    transition: all 0.3s ease;
}

/* Estados de visibilidad */
@media (min-width: 992px) {
    /* En desktop, siempre mostrar filtros */
    .filters-sidebar {
        display: block !important;
        position: relative;
        width: auto;
        height: auto;
        transform: none;
        background: transparent;
        backdrop-filter: none;
        box-shadow: none;
        border: none;
        overflow: visible;
        flex-direction: column;
    }
    
    .mobile-filters-trigger,
    .filters-overlay,
    .drawer-header {
        display: none !important;
    }
}

@media (max-width: 991.98px) {
    /* Overlay para cerrar el drawer */
    .filters-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .filters-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    
    /* Drawer lateral de filtros */
    .filters-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: rgba(26, 26, 26, 0.98);
        backdrop-filter: blur(15px);
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0;
        border: none;
        border-radius: 0;
        margin: 0;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }
    
    .filters-sidebar.show {
        transform: translateX(0);
    }
    
    /* Header del drawer */
    .drawer-header {
        background: linear-gradient(135deg, var(--primary-color), #a00000);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        flex-shrink: 0;
    }
    
    .drawer-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
    }
    
    .drawer-title i {
        margin-right: 8px;
        font-size: 1rem;
    }
    
    .btn-close-drawer {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        font-size: 1.2rem;
        padding: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-close-drawer:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(90deg);
    }
    
    /* Contenido del drawer */
    .filters-sidebar .filter-section {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 18px;
        margin: 15px 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        width: auto;
    }
    
    .filters-sidebar .filter-title {
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        padding-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filters-sidebar .filter-option {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 8px 12px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .filters-sidebar .filter-option:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .filters-sidebar .filter-option label {
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.9rem;
        margin-left: 10px;
        flex: 1;
        cursor: pointer;
    }
    
    /* Botones de acción en el drawer */
    .filters-sidebar .filter-actions {
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(139, 0, 0, 0.15);
        margin-top: auto;
        flex-shrink: 0;
    }
    
    .filters-sidebar .filter-actions .btn {
        margin-bottom: 10px;
    }
    
    /* Inputs de precio en el drawer */
    .filters-sidebar .price-inputs {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        align-items: center;
        justify-content: space-between;
    }
    
    .filters-sidebar .price-input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 0.9rem;
    }
    
    .filters-sidebar .price-input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .filters-sidebar .price-separator {
        color: #fff;
        font-weight: 600;
        margin: 0 5px;
    }
    
    /* Scroll personalizado para el drawer */
    .filters-sidebar::-webkit-scrollbar {
        width: 8px;
    }
    
    .filters-sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .filters-sidebar::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 4px;
    }
    
    .filters-sidebar::-webkit-scrollbar-thumb:hover {
        background: #a00000;
    }
    
    /* Filtros colapsables en drawer */
    .filters-sidebar .collapsible-filter {
        padding-right: 30px;
    }
    
    .filters-sidebar .toggle-icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        transition: transform 0.3s ease;
        color: var(--primary-color);
    }
    
    .filters-sidebar .collapsible-filter[aria-expanded="true"] .toggle-icon {
        transform: translateY(-50%) rotate(180deg);
    }
}

/* Animaciones para el drawer lateral */
@keyframes slideInFromLeft {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(0);
    }
}

@keyframes slideOutToLeft {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-100%);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

/* Filtros - mantener estilos existentes */

.filter-section {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    backdrop-filter: blur(10px);
    width: 100%; /* Asegurar que ocupe todo el ancho disponible */
    box-sizing: border-box; /* Incluir padding en el ancho total */
}

.filter-title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
    border-bottom: 2px solid rgba(255,255,255,0.2);
    padding-bottom: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    transition: opacity 0.3s ease;
}

.filter-option.filter-disabled {
    opacity: 0.4;
    pointer-events: none;
}

.filter-option.filter-disabled label {
    color: #888;
    cursor: not-allowed;
}

.filter-checkbox {
    margin-right: 10px;
    flex-shrink: 0; /* No permitir que se comprima */
}

.filter-checkbox:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.filter-option label {
    color: #fff;
    font-size: 0.95rem;
    cursor: pointer;
    flex: 1;
    word-wrap: break-word; /* Permitir quiebre de línea si es necesario */
}

/* Estilos mejorados para inputs de precio */
.price-inputs {
    display: flex;
    align-items: center;
    gap: 8px; /* Reducir gap para más espacio */
    margin-bottom: 15px;
    width: 100%;
    box-sizing: border-box;
}

.price-input {
    flex: 1;
    padding: 8px 6px; /* Reducir padding horizontal */
    border: 1px solid #ddd;
    border-radius: 4px;
    background: rgba(255,255,255,0.9);
    font-size: 0.85rem; /* Reducir tamaño de fuente */
    width: 0; /* Forzar que respete flex: 1 */
    min-width: 0; /* Permitir que se comprima si es necesario */
    box-sizing: border-box;
}

.price-input::placeholder {
    font-size: 0.8rem;
    color: #999;
}

.price-separator {
    color: #fff;
    font-weight: bold;
    font-size: 0.9rem;
    flex-shrink: 0; /* No permitir que se comprima */
}

/* Botón aplicar precio */
.filter-options .btn {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 12px;
    font-size: 0.85rem;
}

/* Botones de acciones de filtros */
.filter-actions {
    margin-top: 15px;
    padding: 10px 0;
}

.filter-actions .btn {
    width: 100%;
    box-sizing: border-box;
    position: relative;
}

#apply-filters-btn {
    background: linear-gradient(135deg, #8B0000, #A00000);
    border: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(139, 0, 0, 0.2);
}

#apply-filters-btn:hover {
    background: linear-gradient(135deg, #A00000, #B00000);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(139, 0, 0, 0.3);
}

#clear-filters-btn {
    border: 1px solid #666;
    color: #ccc;
    background: transparent;
    transition: all 0.3s ease;
}

#clear-filters-btn:hover {
    border-color: #8B0000;
    color: #8B0000;
    background: rgba(139, 0, 0, 0.1);
}

#filter-count {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Efecto visual para botón con cambios pendientes */
.btn-pulse {
    animation: buttonPulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(139, 0, 0, 0.7);
}

@keyframes buttonPulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(139, 0, 0, 0.7);
    }
    70% {
        transform: scale(1.02);
        box-shadow: 0 0 0 4px rgba(139, 0, 0, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(139, 0, 0, 0);
    }
}

/* Estilos para filtros dinámicos de etiquetas */
.tag-count {
    font-size: 0.8rem;
    color: #8B0000;
    font-weight: bold;
    margin-left: 5px;
}

.filter-option input[type="checkbox"]:checked + label .tag-count {
    color: #fff;
}

/* Iconos de categorías de filtros */
.filter-title i {
    margin-right: 8px;
    color: #8B0000;
    width: 16px;
    text-align: center;
}

/* =====================================================
   FILTROS COLAPSABLES
   ===================================================== */

/* Títulos de filtros clickeables */
.collapsible-filter {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 30px;
    transition: all 0.3s ease;
}

.collapsible-filter:hover {
    color: #8B0000;
    background: rgba(139, 0, 0, 0.1);
    padding: 8px;
    border-radius: 5px;
    margin: -8px;
    margin-bottom: 7px;
}

/* Icono de toggle */
.toggle-icon {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
    color: #8B0000 !important;
    font-size: 0.9rem;
}

.collapsible-filter[aria-expanded="true"] .toggle-icon {
    transform: translateY(-50%) rotate(180deg);
}

/* Contenido colapsable */
.collapse-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
    opacity: 0;
}

.collapse-content.expanded {
    max-height: 500px; /* Altura máxima suficiente para todos los filtros */
    opacity: 1;
    transition: max-height 0.3s ease-in, opacity 0.3s ease-in;
}

/* Animación suave para la expansión */
.collapse-content.expanding {
    transition: max-height 0.4s cubic-bezier(0.4, 0.0, 0.2, 1), opacity 0.4s ease;
}

.collapse-content.collapsing {
    transition: max-height 0.3s cubic-bezier(0.4, 0.0, 0.2, 1), opacity 0.3s ease;
}

/* Mejorar spacing entre secciones de filtros */
.filter-section + .filter-section {
    margin-top: 25px;
}

.products-toolbar {
    display: flex;
    margin-top: 1.5rem;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    gap: 15px; /* Espacio entre elementos */
    flex-wrap: wrap; /* Permitir que se envuelvan en pantallas pequeñas */
    width: 100%;
    box-sizing: border-box;
}

.products-info {
    flex: 1;
    min-width: 200px; /* Ancho mínimo para evitar compresión excesiva */
}

.products-count {
    color: #fff;
    font-size: 0.95rem;
    white-space: nowrap; /* Evitar que el texto se corte */
}

.products-sort {
    flex-shrink: 0; /* No permitir que se comprima */
    min-width: 200px; /* Ancho mínimo para el select */
}

.products-sort .form-select {
    background: rgba(0,0,0,0.7);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 0.9rem;
    color: #fff;
    min-width: 180px; /* Asegurar ancho mínimo */
    max-width: 100%; /* No exceder el contenedor */
    box-sizing: border-box;
    backdrop-filter: blur(5px);
}

.products-sort .form-select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    background: rgba(0,0,0,0.8);
}

.products-sort .form-select option {
    background: #333;
    color: #fff;
    padding: 8px;
}

/* Mensaje sin productos */
.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #fff;
}

.no-products-content {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 40px;
    backdrop-filter: blur(10px);
    max-width: 400px;
    margin: 0 auto;
}

.no-products h4 {
    margin-bottom: 15px;
    font-weight: 600;
}

.no-products p {
    margin-bottom: 0;
    opacity: 0.8;
}

/* Responsividad para el grid de 4 columnas */
@media (max-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr); /* 3 columnas en tablets grandes */
        gap: 18px;
    }
    
    .product-card {
        height: 350px; /* Reducir altura en tablets */
    }
    
    .btn-add-cart, .btn-no-stock {
        padding: 8px 14px;
        font-size: 0.85rem;
    }
}

@media (max-width: 768px) {
    .filters-sidebar {
        margin-bottom: 20px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr 1fr; /* 2 columnas en móvil */
        gap: 12px;
    }
    
    .product-card {
        height: 250px; /* Altura reducida en móvil */
    }
    
    .btn-add-cart, .btn-no-stock {
        padding: 8px 10px;
        font-size: 0.75rem;
        gap: 6px;
        height: 38px;
    }
    
    .btn-wishlist-fixed {
        width: 32px;
        height: 32px;
        top: 6px;
        right: 6px;
    }
    
    .btn-view-fixed {
        width: 32px;
        height: 32px;
        top: 42px; /* Mejor espaciado para móvil */
        right: 6px;
    }
    
    /* Toolbar más compacto en móvil pequeño */
    .products-toolbar {
        padding: 10px;
        gap: 12px;
    }
    
    .products-count {
        font-size: 0.85rem;
    }
    
    .products-sort .form-select {
        padding: 6px 10px;
        font-size: 0.85rem;
        background: rgba(0,0,0,0.7);
        color: #fff;
    }
    
    /* Filtros más compactos en móvil pequeño */
    .filter-section {
        padding: 12px;
        margin-bottom: 15px;
    }
    
    .filter-title {
        font-size: 1rem;
        margin-bottom: 12px;
    }
    
    .price-inputs {
        gap: 4px;
    }
    
    .price-input {
        padding: 6px 3px;
        font-size: 0.75rem;
    }
    
    .price-separator {
        font-size: 0.8rem;
    }
    
    .filter-options .btn {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
}
</style>

<!-- =====================================================
     JAVASCRIPT BÁSICO
     ===================================================== -->
<script>
/**
 * =====================================================
 * PRODUCTOS - FUNCIONALIDAD JAVASCRIPT
 * =====================================================
 */

// Variables globales para el sistema de filtros
let pendingFilters = {
    categories: [],
    brands: [],
    consoles: [],
    genres: [],
    minPrice: '',
    maxPrice: ''
};

let filtersChanged = false;

// Inicializar pendingFilters con el estado actual de la página
function initializePendingFilters() {
    try {
        // Leer parámetros de la URL actual
        const urlParams = new URLSearchParams(window.location.search);
        
        // Inicializar categorías
        const categoryParam = urlParams.get('category');
        if (categoryParam) {
            pendingFilters.categories = categoryParam.split(',');
        }
        
        // Inicializar marcas
        const brandParam = urlParams.get('brands');
        if (brandParam) {
            pendingFilters.brands = brandParam.split(',');
        }
        
        // Inicializar consolas
        const consolesParam = urlParams.get('consoles');
        if (consolesParam) {
            pendingFilters.consoles = consolesParam.split(',');
        }
        
        // Inicializar géneros
        const genresParam = urlParams.get('genres');
        if (genresParam) {
            pendingFilters.genres = genresParam.split(',');
        }
        
        // Inicializar precios
        const minPrice = urlParams.get('min_price');
        const maxPrice = urlParams.get('max_price');
        if (minPrice) pendingFilters.minPrice = minPrice;
        if (maxPrice) pendingFilters.maxPrice = maxPrice;
        
        console.log('🔧 Estado inicial de filtros:', pendingFilters);
        
    } catch (error) {
        console.error('❌ Error inicializando filtros pendientes:', error);
    }
}

/**
 * Marcar checkboxes según filtros aplicados en la URL
 */
function markAppliedFilters() {
    try {
        // Obtener el estado actualizado desde el sistema global
        const filters = window.pendingFilters || pendingFilters;
        
        // Marcar categorías
        if (filters.categories && filters.categories.length > 0) {
            filters.categories.forEach(catId => {
                const checkbox = document.getElementById(`cat_${catId}`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        // Marcar marcas
        if (filters.brands && filters.brands.length > 0) {
            filters.brands.forEach(brandId => {
                const checkbox = document.getElementById(`brand_${brandId}`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        // Marcar consolas
        if (filters.consoles && filters.consoles.length > 0) {
            filters.consoles.forEach(consoleId => {
                const checkbox = document.getElementById(`console_${consoleId}`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        // Marcar géneros
        if (filters.genres && filters.genres.length > 0) {
            filters.genres.forEach(genreId => {
                const checkbox = document.getElementById(`genre_${genreId}`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        console.log('✅ Checkboxes marcados según filtros aplicados');
        
    } catch (error) {
        console.error('❌ Error marcando filtros aplicados:', error);
    }
}

/**
 * =====================================================
 * FILTROS COLAPSABLES - FUNCIONES
 * =====================================================
 */

/**
 * Inicializar filtros colapsables
 */
function initializeCollapsibleFilters() {
    console.log('🔧 Inicializando filtros colapsables...');
    
    try {
        // Obtener todos los filtros colapsables
        const collapsibleFilters = document.querySelectorAll('.collapsible-filter');
        
        if (collapsibleFilters.length === 0) {
            console.log('⚠️ No se encontraron filtros colapsables');
            return;
        }
        
        collapsibleFilters.forEach((filter, index) => {
            try {
                // Verificar si ya tiene el evento para evitar duplicados
                if (filter.hasAttribute('data-initialized')) {
                    return;
                }
                
                // Marcar como inicializado
                filter.setAttribute('data-initialized', 'true');
                
                // Verificar que tenga el atributo data-target
                const targetId = filter.getAttribute('data-target');
                if (!targetId) {
                    console.warn(`⚠️ Filtro ${index + 1} no tiene atributo data-target`);
                    return;
                }
                
                // Verificar que el elemento objetivo existe
                const targetElement = document.getElementById(targetId);
                if (!targetElement) {
                    console.warn(`⚠️ No se encontró elemento con ID: ${targetId}`);
                    return;
                }
                
                // Función del handler para poder referenciarla
                const clickHandler = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    try {
                        const targetId = this.getAttribute('data-target');
                        const targetElement = document.getElementById(targetId);
                        
                        if (targetElement) {
                            toggleFilter(this, targetElement);
                        } else {
                            console.error(`❌ No se puede encontrar elemento target: ${targetId}`);
                        }
                    } catch (error) {
                        console.error('❌ Error en click handler:', error);
                    }
                };
                
                // Agregar evento de click
                filter.addEventListener('click', clickHandler);
                
                // Establecer estado inicial - TODOS LOS FILTROS COLAPSADOS POR DEFECTO
                // Los usuarios pueden expandir según necesiten
                filter.setAttribute('aria-expanded', 'false');
                targetElement.classList.remove('expanded'); // Asegurar que no tenga la clase
                console.log(`✅ Filtro colapsado por defecto: ${targetId}`);
                
            } catch (error) {
                console.error(`❌ Error configurando filtro ${index + 1}:`, error);
            }
        });
        
        console.log(`✅ ${collapsibleFilters.length} filtros colapsables procesados`);
        
    } catch (error) {
        console.error('❌ Error general en initializeCollapsibleFilters:', error);
    }
}

/**
 * Toggle de un filtro específico
 */
function toggleFilter(filterTitle, filterContent) {
    try {
        // Validar parámetros
        if (!filterTitle) {
            console.error('❌ toggleFilter: filterTitle es null o undefined');
            return;
        }
        
        if (!filterContent) {
            console.error('❌ toggleFilter: filterContent es null o undefined');
            return;
        }
        
        const isExpanded = filterTitle.getAttribute('aria-expanded') === 'true';
        const icon = filterTitle.querySelector('.toggle-icon');
        
        if (isExpanded) {
            // Colapsar
            filterContent.classList.remove('expanded', 'expanding');
            filterContent.classList.add('collapsing');
            filterTitle.setAttribute('aria-expanded', 'false');
            
            // Quitar clase de animación después de la transición
            setTimeout(() => {
                if (filterContent) { // Verificar que aún existe
                    filterContent.classList.remove('collapsing');
                }
            }, 300);
            
        } else {
            // Expandir
            filterContent.classList.remove('collapsing');
            filterContent.classList.add('expanding', 'expanded');
            filterTitle.setAttribute('aria-expanded', 'true');
            
            // Quitar clase de animación después de la transición
            setTimeout(() => {
                if (filterContent) { // Verificar que aún existe
                    filterContent.classList.remove('expanding');
                }
            }, 400);
        }
        
        // Log para debugging con información más detallada
        const filterName = filterTitle.textContent ? filterTitle.textContent.trim() : 'Filtro sin nombre';
        const newState = isExpanded ? 'Colapsado' : 'Expandido';
        console.log(`🔄 Toggle filtro: ${filterName} - ${newState}`);
        
    } catch (error) {
        console.error('❌ Error en toggleFilter:', error);
        console.error('❌ filterTitle:', filterTitle);
        console.error('❌ filterContent:', filterContent);
    }
}

/**
 * Expandir un filtro específico (útil para cuando se selecciona una opción)
 */
function expandFilter(filterId) {
    const filterElement = document.getElementById(filterId);
    const filterTitle = document.querySelector(`[data-target="${filterId}"]`);
    
    if (filterElement && filterTitle && !filterElement.classList.contains('expanded')) {
        toggleFilter(filterTitle, filterElement);
    }
}

/**
 * Colapsar todos los filtros dinámicos
 */
function collapseAllDynamicFilters() {
    const dynamicFilters = document.querySelectorAll('[id^="dynamic-filter-"]');
    
    dynamicFilters.forEach(filter => {
        const filterId = filter.id;
        const filterTitle = document.querySelector(`[data-target="${filterId}"]`);
        
        if (filterTitle && filter.classList.contains('expanded')) {
            toggleFilter(filterTitle, filter);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('🛍️ Página de productos cargada (versión renovada)');
    
    // El sistema de filtros en productos-filtros.js ya inicializa desde URL
    // Solo necesitamos marcar los checkboxes según filtros aplicados
    setTimeout(() => {
        markAppliedFilters();
        // updateFilterCompatibility(); // DESHABILITADO - Causaba errores AJAX
    }, 100);
    
    // Verificar que las funciones básicas funcionen
    try {
        // Inicializar sistema de cards con imagen
        if (typeof initializeProductCards === 'function') {
            initializeProductCards();
            console.log('✅ Sistema de cards inicializado correctamente');
        } else {
            console.warn('⚠️ Función initializeProductCards no encontrada');
        }
        
        // Inicializar botones de wishlist
        if (typeof initializeWishlistButtons === 'function') {
            initializeWishlistButtons();
            console.log('✅ Botones de wishlist inicializados');
        } else {
            console.warn('⚠️ Función initializeWishlistButtons no encontrada');
        }
        
        // Inicializar filtros colapsables
        if (typeof initializeCollapsibleFilters === 'function') {
            initializeCollapsibleFilters();
            console.log('✅ Filtros colapsables inicializados');
        } else {
            console.warn('⚠️ Función initializeCollapsibleFilters no encontrada');
        }
        
    } catch (error) {
        console.error('❌ Error inicializando sistema:', error);
        console.error('❌ Stack trace:', error.stack);
    }
    
    // Filtros por categoría con manejo de errores
    try {
        const categoryFilters = document.querySelectorAll('.category-filter');
        console.log(`🔧 Configurando ${categoryFilters.length} filtros de categoría`);
        
        categoryFilters.forEach((checkbox, index) => {
            try {
                checkbox.addEventListener('change', function() {
                    updateFilter('category', this.value, this.checked);
                });
            } catch (error) {
                console.error(`❌ Error configurando filtro de categoría ${index + 1}:`, error);
            }
        });
    } catch (error) {
        console.error('❌ Error configurando filtros de categoría:', error);
    }
    
    // Filtros por marca con manejo de errores
    try {
        const brandFilters = document.querySelectorAll('.brand-filter');
        console.log(`🔧 Configurando ${brandFilters.length} filtros de marca`);
        
        brandFilters.forEach((checkbox, index) => {
            try {
                checkbox.addEventListener('change', function() {
                    updateFilter('brand', this.value, this.checked);
                });
            } catch (error) {
                console.error(`❌ Error configurando filtro de marca ${index + 1}:`, error);
            }
        });
    } catch (error) {
        console.error('❌ Error configurando filtros de marca:', error);
    }
    
    // Filtros por consolas
    try {
        const consoleFilters = document.querySelectorAll('.console-filter');
        console.log(`🔧 Configurando ${consoleFilters.length} filtros de consolas`);
        
        consoleFilters.forEach((checkbox, index) => {
            try {
                checkbox.addEventListener('change', function() {
                    updateFilter('console', this.value, this.checked);
                });
            } catch (error) {
                console.error(`❌ Error configurando filtro de consola ${index + 1}:`, error);
            }
        });
    } catch (error) {
        console.error('❌ Error configurando filtros de consolas:', error);
    }
    
    // Filtros por géneros
    try {
        const genreFilters = document.querySelectorAll('.genre-filter');
        console.log(`🔧 Configurando ${genreFilters.length} filtros de géneros`);
        
        genreFilters.forEach((checkbox, index) => {
            try {
                checkbox.addEventListener('change', function() {
                    updateFilter('genre', this.value, this.checked);
                });
            } catch (error) {
                console.error(`❌ Error configurando filtro de género ${index + 1}:`, error);
            }
        });
    } catch (error) {
        console.error('❌ Error configurando filtros de etiquetas:', error);
    }
    
    // Configurar campos de precio para el nuevo sistema
    try {
        const minPriceInput = document.getElementById('price-min');
        const maxPriceInput = document.getElementById('price-max');
        
        if (minPriceInput && maxPriceInput) {
            // Event listeners para actualizar filtros pendientes cuando cambien los precios
            minPriceInput.addEventListener('input', function() {
                pendingFilters.minPrice = this.value;
                filtersChanged = true;
                updateFilterButtons();
            });
            
            maxPriceInput.addEventListener('input', function() {
                pendingFilters.maxPrice = this.value;
                filtersChanged = true;
                updateFilterButtons();
            });
            
            console.log('✅ Campos de precio configurados para el nuevo sistema');
        }
    } catch (error) {
        console.error('❌ Error configurando campos de precio:', error);
    }
    
    console.log('✅ Configuración de página de productos completada');
    
    // Inicializar los botones de filtros después de que las funciones globales estén definidas
    setTimeout(function() {
        if (typeof updateFilterButtons === 'function') {
            updateFilterButtons();
        }
    }, 100);
    
    // Hacer las variables globales para que estén disponibles
    window.pendingFilters = pendingFilters;
    window.filtersChanged = filtersChanged;

// =====================================================
// FUNCIONES DE FILTROS - GLOBALES
// =====================================================

/**
 * Actualizar filtro (categoría o marca)
 */
function updateFilter(filterType, value, checked) {
    try {
        if (filterType === 'category') {
            if (checked) {
                if (!pendingFilters.categories.includes(value)) {
                    pendingFilters.categories.push(value);
                }
            } else {
                pendingFilters.categories = pendingFilters.categories.filter(cat => cat !== value);
            }
        } else if (filterType === 'brand') {
            if (checked) {
                if (!pendingFilters.brands.includes(value)) {
                    pendingFilters.brands.push(value);
                }
            } else {
                pendingFilters.brands = pendingFilters.brands.filter(brand => brand !== value);
            }
        } else if (filterType === 'console') {
            if (checked) {
                if (!pendingFilters.consoles.includes(value)) {
                    pendingFilters.consoles.push(value);
                }
            } else {
                pendingFilters.consoles = pendingFilters.consoles.filter(console => console !== value);
            }
        } else if (filterType === 'genre') {
            if (checked) {
                if (!pendingFilters.genres.includes(value)) {
                    pendingFilters.genres.push(value);
                }
            } else {
                pendingFilters.genres = pendingFilters.genres.filter(genre => genre !== value);
            }
        }
        
        filtersChanged = true;
        // updateFilterCompatibility(); // DESHABILITADO - Causaba errores AJAX
        updateFilterButtons();
        console.log('📝 Filtro actualizado:', filterType, value, checked);
        
    } catch (error) {
        console.error('❌ Error en updateFilter:', error);
    }
}

/**
 * Actualizar compatibilidad de filtros
 * Deshabilita opciones que no tienen productos compatibles con los filtros actuales
 */
function updateFilterCompatibility() {
    try {
        // TEMPORALMENTE DESHABILITADO - Habilitar todos los filtros
        // Esta función causaba errores AJAX que impedían el funcionamiento normal
        // TODO: Revisar y corregir includes/get_compatible_filters.php
        enableAllFilters();
        return;
        
        /* CÓDIGO ORIGINAL DESHABILITADO
        // Construir query string con filtros actuales
        const params = new URLSearchParams();
        
        if (pendingFilters.categories.length > 0) {
            params.set('category', pendingFilters.categories.join(','));
        }
        if (pendingFilters.brands.length > 0) {
            params.set('brands', pendingFilters.brands.join(','));
        }
        if (pendingFilters.consoles.length > 0) {
            params.set('consoles', pendingFilters.consoles.join(','));
        }
        if (pendingFilters.genres.length > 0) {
            params.set('genres', pendingFilters.genres.join(','));
        }
        
        // Si no hay filtros seleccionados, habilitar todo
        if (params.toString() === '') {
            enableAllFilters();
            return;
        }
        
        // Hacer petición AJAX para obtener filtros compatibles
        fetch(`includes/get_compatible_filters.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateFilterAvailability(data.filters);
                } else {
                    console.error('Error obteniendo filtros compatibles:', data.message);
                }
            })
            .catch(error => {
                console.error('Error en petición AJAX:', error);
            });
        */
        
    } catch (error) {
        console.error('❌ Error en updateFilterCompatibility:', error);
    }
}

/**
 * Actualizar disponibilidad de checkboxes basado en filtros compatibles
 */
function updateFilterAvailability(compatibleFilters) {
    // Actualizar categorías
    document.querySelectorAll('.category-filter').forEach(checkbox => {
        const isCompatible = compatibleFilters.categories.includes(parseInt(checkbox.value));
        const isSelected = pendingFilters.categories.includes(checkbox.value);
        
        checkbox.disabled = !isCompatible && !isSelected;
        
        // Agregar/quitar clase visual
        const label = checkbox.closest('.filter-option');
        if (label) {
            if (checkbox.disabled) {
                label.classList.add('filter-disabled');
            } else {
                label.classList.remove('filter-disabled');
            }
        }
    });
    
    // Actualizar marcas
    document.querySelectorAll('.brand-filter').forEach(checkbox => {
        const isCompatible = compatibleFilters.brands.includes(parseInt(checkbox.value));
        const isSelected = pendingFilters.brands.includes(checkbox.value);
        
        checkbox.disabled = !isCompatible && !isSelected;
        
        const label = checkbox.closest('.filter-option');
        if (label) {
            if (checkbox.disabled) {
                label.classList.add('filter-disabled');
            } else {
                label.classList.remove('filter-disabled');
            }
        }
    });
    
    // Actualizar consolas
    document.querySelectorAll('.console-filter').forEach(checkbox => {
        const isCompatible = compatibleFilters.consoles.includes(parseInt(checkbox.value));
        const isSelected = pendingFilters.consoles.includes(checkbox.value);
        
        checkbox.disabled = !isCompatible && !isSelected;
        
        const label = checkbox.closest('.filter-option');
        if (label) {
            if (checkbox.disabled) {
                label.classList.add('filter-disabled');
            } else {
                label.classList.remove('filter-disabled');
            }
        }
    });
    
    // Actualizar géneros
    document.querySelectorAll('.genre-filter').forEach(checkbox => {
        const isCompatible = compatibleFilters.genres.includes(parseInt(checkbox.value));
        const isSelected = pendingFilters.genres.includes(checkbox.value);
        
        checkbox.disabled = !isCompatible && !isSelected;
        
        const label = checkbox.closest('.filter-option');
        if (label) {
            if (checkbox.disabled) {
                label.classList.add('filter-disabled');
            } else {
                label.classList.remove('filter-disabled');
            }
        }
    });
    
    console.log('✅ Disponibilidad de filtros actualizada');
}

/**
 * Habilitar todos los filtros
 */
function enableAllFilters() {
    document.querySelectorAll('.category-filter, .brand-filter, .console-filter, .genre-filter').forEach(checkbox => {
        checkbox.disabled = false;
        const label = checkbox.closest('.filter-option');
        if (label) {
            label.classList.remove('filter-disabled');
        }
    });
}

/**
 * Aplicar todos los filtros seleccionados
 */
function applyAllFilters() {
    try {
        const url = new URL(window.location);
        
        // Limpiar parámetros existentes
        url.searchParams.delete('categories');
        url.searchParams.delete('brands');
        url.searchParams.delete('consoles');
        url.searchParams.delete('genres');
        url.searchParams.delete('min_price');
        url.searchParams.delete('max_price');
        url.searchParams.delete('page');
        
        // Aplicar categorías
        if (pendingFilters.categories.length > 0) {
            url.searchParams.set('categories', pendingFilters.categories.join(','));
        }
        
        // Aplicar marcas
        if (pendingFilters.brands.length > 0) {
            url.searchParams.set('brands', pendingFilters.brands.join(','));
        }
        
        // Aplicar consolas
        if (pendingFilters.consoles.length > 0) {
            url.searchParams.set('consoles', pendingFilters.consoles.join(','));
        }
        
        // Aplicar géneros
        if (pendingFilters.genres.length > 0) {
            url.searchParams.set('genres', pendingFilters.genres.join(','));
        }
        
        // Aplicar precios
        if (pendingFilters.minPrice) {
            url.searchParams.set('min_price', pendingFilters.minPrice);
        }
        if (pendingFilters.maxPrice) {
            url.searchParams.set('max_price', pendingFilters.maxPrice);
        }
        
        console.log('🔄 Aplicando filtros:', pendingFilters);
        window.location.href = url.toString();
        
    } catch (error) {
        console.error('❌ Error en applyAllFilters:', error);
    }
}

// Make function globally accessible
window.applyAllFilters = applyAllFilters;

/**
 * Limpiar todos los filtros
 */
function clearAllFilters() {
    try {
        // Desmarcar todos los checkboxes
        document.querySelectorAll('.category-filter').forEach(cb => cb.checked = false);
        document.querySelectorAll('.brand-filter').forEach(cb => cb.checked = false);
        document.querySelectorAll('.console-filter').forEach(cb => cb.checked = false);
        document.querySelectorAll('.genre-filter').forEach(cb => cb.checked = false);
        
        // Limpiar campos de precio
        const minPriceInput = document.getElementById('price-min');
        const maxPriceInput = document.getElementById('price-max');
        if (minPriceInput) minPriceInput.value = '';
        if (maxPriceInput) maxPriceInput.value = '';
        
        // Resetear filtros pendientes
        pendingFilters = {
            categories: [],
            brands: [],
            consoles: [],
            genres: [],
            minPrice: '',
            maxPrice: ''
        };
        
        filtersChanged = false;
        updateFilterButtons();
        
        // Redirigir a la página sin filtros (limpiar TODOS los parámetros)
        const url = new URL(window.location);
        const search = url.searchParams.get('search');
        url.search = '';
        if (search) {
            url.searchParams.set('search', search); // Solo mantener búsqueda si existe
        }
        window.location.href = url.toString();
        
    } catch (error) {
        console.error('❌ Error en clearAllFilters:', error);
    }
}

// Make function globally accessible
window.clearAllFilters = clearAllFilters;

/**
 * Actualizar estado de los botones de filtros
 */
function updateFilterButtons() {
    const applyBtn = document.getElementById('apply-filters-btn');
    const clearBtn = document.getElementById('clear-filters-btn');
    const filterCount = document.getElementById('filter-count');
    
    if (applyBtn && clearBtn) {
        const totalFilters = pendingFilters.categories.length + 
                           pendingFilters.brands.length + 
                           pendingFilters.consoles.length +
                           pendingFilters.genres.length +
                           (pendingFilters.minPrice ? 1 : 0) +
                           (pendingFilters.maxPrice ? 1 : 0);
        
        // Always show both buttons
        applyBtn.style.display = 'block';
        clearBtn.style.display = 'block';
        
        // Update filter count badge
        if (filterCount) {
            if (totalFilters > 0) {
                filterCount.textContent = totalFilters;
                filterCount.style.display = 'inline';
            } else {
                filterCount.style.display = 'none';
            }
        }
        
        // Optional: Change button style based on whether there are changes
        if (filtersChanged) {
            applyBtn.classList.add('btn-pulse'); // Add pulsing effect when changes are pending
            console.log('🔄 Filtros pendientes de aplicar');
        } else {
            applyBtn.classList.remove('btn-pulse');
        }
        
        console.log('📊 Estado filtros:', { 
            filtersChanged, 
            totalFilters, 
            pendingFilters: JSON.stringify(pendingFilters)
        });
    }
}

/**
 * Aplicar filtro de precio (ahora solo actualiza pendingFilters)
 */
function applyPriceFilter() {
    try {
        const minPriceInput = document.getElementById('price-min');
        const maxPriceInput = document.getElementById('price-max');
        
        if (!minPriceInput || !maxPriceInput) {
            console.error('❌ No se encontraron los campos de precio');
            return;
        }
        
        // Solo actualizar filtros pendientes, no aplicar inmediatamente
        pendingFilters.minPrice = minPriceInput.value;
        pendingFilters.maxPrice = maxPriceInput.value;
        filtersChanged = true;
        updateFilterButtons();
        
    } catch (error) {
        console.error('❌ Error en applyPriceFilter:', error);
    }
}

// =====================================================
// FUNCIONES DE INICIALIZACIÓN
// =====================================================

/**
 * Inicializar sistema de cards con imagen
 */
function initializeProductCards() {
    console.log('🔧 Inicializando sistema de cards...');
    
    const imageBackgrounds = document.querySelectorAll('.product-image-background');
    console.log(`📸 Encontradas ${imageBackgrounds.length} imágenes de fondo`);
    
    imageBackgrounds.forEach((bg, index) => {
        try {
            const backgroundImage = bg.style.backgroundImage;
            const fallbackUrl = bg.dataset.fallback;
            
            if (backgroundImage) {
                // Extraer URL de la imagen del style
                const imageUrl = backgroundImage.slice(5, -2); // Remover url(" y ")
                
                // Crear imagen temporal para verificar si carga
                const testImage = new Image();
                testImage.onload = function() {
                    // Imagen carga correctamente, no hacer nada
                    console.log(`✅ Imagen ${index + 1} cargada:`, imageUrl);
                };
                testImage.onerror = function() {
                    // Imagen no carga, usar fallback
                    console.log(`❌ Error cargando imagen ${index + 1}, usando fallback:`, imageUrl);
                    bg.style.backgroundImage = `url('${fallbackUrl}')`;
                };
                testImage.src = imageUrl;
            } else {
                // No hay imagen, usar fallback
                bg.style.backgroundImage = `url('${fallbackUrl}')`;
                console.log(`📝 Usando fallback para imagen ${index + 1}`);
            }
        } catch (error) {
            console.error(`❌ Error procesando imagen ${index + 1}:`, error);
        }
    });
    
    console.log('✅ Sistema de cards inicializado completamente');
}

/**
 * Inicializar botones de wishlist
 */
function initializeWishlistButtons() {
    console.log('🔧 Inicializando botones de wishlist...');
    
    // Event listener para botones de wishlist fijos
    document.addEventListener('click', function(e) {
        if (e.target && e.target.closest && e.target.closest('.btn-wishlist-fixed')) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.btn-wishlist-fixed');
            const productId = button.getAttribute('data-product-id');
            
            if (productId) {
                toggleWishlist(button, productId);
            }
        }
    });
}

/**
 * Alternar producto en wishlist
 */
function toggleWishlist(button, productId) {
    const icon = button.querySelector('i');
    const isInWishlist = button.classList.contains('active');
    
    // Mostrar estado de carga
    button.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';
    
    // Llamada AJAX para wishlist
    fetch('ajax/toggle-wishlist.php', {
        method: 'POST',
        body: new URLSearchParams({
            product_id: productId,
            action: isInWishlist ? 'remove' : 'add'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Alternar estado visual
            if (isInWishlist) {
                button.classList.remove('active');
                icon.className = 'far fa-heart';
                console.log('💔 Producto removido de wishlist:', productId);
            } else {
                button.classList.add('active');
                icon.className = 'fas fa-heart';
                console.log('💖 Producto agregado a wishlist:', productId);
            }
            
            // Actualizar contador en header si existe la función
            if (typeof syncWishlistCount === 'function') {
                syncWishlistCount();
            }
        } else {
            throw new Error(data.message || 'Error al actualizar wishlist');
        }
    })
    .catch(error => {
        console.error('❌ Error con wishlist:', error);
        // Restaurar estado original en caso de error
        icon.className = isInWishlist ? 'fas fa-heart' : 'far fa-heart';
    })
    .finally(() => {
        button.disabled = false;
    });
}

// =====================================================
// SISTEMA DE FILTROS MÓVILES
// =====================================================

/**
 * Alternar visibilidad del drawer de filtros
 */
function toggleMobileFilters() {
    const sidebar = document.getElementById('filters-sidebar');
    const overlay = document.getElementById('filters-overlay');
    const button = document.getElementById('toggle-filters-btn');
    const text = document.getElementById('filters-btn-text');
    const chevron = document.getElementById('filters-chevron');
    
    if (sidebar && button && text && chevron) {
        const isVisible = sidebar.classList.contains('show');
        
        if (isVisible) {
            closeMobileFilters();
        } else {
            openMobileFilters();
        }
    }
}

/**
 * Abrir drawer de filtros
 */
function openMobileFilters() {
    const sidebar = document.getElementById('filters-sidebar');
    const overlay = document.getElementById('filters-overlay');
    const button = document.getElementById('toggle-filters-btn');
    const text = document.getElementById('filters-btn-text');
    const chevron = document.getElementById('filters-chevron');
    
    if (sidebar && overlay && button && text && chevron) {
        // Mostrar overlay
        overlay.classList.add('show');
        
        // Forzar reflow para asegurar que las transiciones funcionen
        sidebar.offsetHeight;
        
        // Mostrar drawer
        sidebar.classList.add('show');
        text.textContent = 'Ocultar Filtros';
        chevron.classList.add('rotated');
        button.setAttribute('aria-expanded', 'true');
        
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
        
        console.log('📱 Drawer de filtros abierto');
    } else {
        console.error('❌ No se pudieron encontrar los elementos del drawer');
    }
}

/**
 * Cerrar drawer de filtros
 */
function closeMobileFilters() {
    const sidebar = document.getElementById('filters-sidebar');
    const overlay = document.getElementById('filters-overlay');
    const button = document.getElementById('toggle-filters-btn');
    const text = document.getElementById('filters-btn-text');
    const chevron = document.getElementById('filters-chevron');
    
    if (sidebar && overlay && button && text && chevron) {
        // Ocultar drawer
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        text.textContent = 'Mostrar Filtros';
        chevron.classList.remove('rotated');
        button.setAttribute('aria-expanded', 'false');
        
        // Restaurar scroll del body
        document.body.style.overflow = '';
        
        console.log('📱 Drawer de filtros cerrado');
    }
}

// Hacer las funciones globales
window.toggleMobileFilters = toggleMobileFilters;
window.openMobileFilters = openMobileFilters;
window.closeMobileFilters = closeMobileFilters;

// Cerrar drawer con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('filters-sidebar');
        if (sidebar && sidebar.classList.contains('show')) {
            closeMobileFilters();
        }
    }
});

});

</script>

<!-- Smart Filters JavaScript -->
<script src="assets/js/smart-filters.js"></script>

<?php
// =====================================================
// INCLUIR FOOTER
// =====================================================
require_once 'includes/footer.php';
?>
