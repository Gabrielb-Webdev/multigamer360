<?php
/**
 * =====================================================
 * MULTIGAMER360 - CARRITO DE COMPRAS
 * =====================================================
 * 
 * Descripción: Página del carrito de compras con gestión completa
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-16
 * 
 * Funcionalidades:
 * - Visualización de productos en el carrito
 * - Modificación de cantidades
 * - Eliminación de productos
 * - Cálculo de totales con impuestos
 * - Aplicación de cupones de descuento
 * - Gestión de direcciones de envío
 * - Integración con sistema de checkout
 */

// =====================================================
// CONFIGURACIÓN INICIAL
// =====================================================

require_once 'config/database.php';
require_once 'includes/cart_manager.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// INICIALIZACIÓN DE MANAGERS
// =====================================================

// Crear instancia del manejador de carrito
$cartManager = new CartManager($pdo);

// IMPORTANTE: Sincronizar carrito desde la base de datos al cargar la página
$cartManager->syncCartFromDatabase();

// DEBUG: Mostrar estado del carrito para debugging
error_log("CARRITO DEBUG - Session ID: " . session_id());
error_log("CARRITO DEBUG - Session cart: " . print_r($_SESSION['cart'] ?? [], true));
error_log("CARRITO DEBUG - Cart count: " . $cartManager->getCartCount());
error_log("CARRITO DEBUG - Cart total: " . $cartManager->getCartTotal());

// =====================================================
// OBTENER CÓDIGO POSTAL DEL USUARIO
// =====================================================

$user_postal_code = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT postal_code FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['postal_code'])) {
            $user_postal_code = $result['postal_code'];
            error_log("CARRITO DEBUG - User postal code: " . $user_postal_code);
        }
    } catch (PDOException $e) {
        error_log("CARRITO DEBUG - Error getting postal code: " . $e->getMessage());
    }
}

// =====================================================
// PROCESAMIENTO DE ACCIONES
// =====================================================

// Manejar limpieza del carrito ANTES del header
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    $cartManager->clearCart();
    // Redirigir a carrito sin parámetros para evitar bucles
    header('Location: carrito.php');
    exit;
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

/**
 * Obtener productos del carrito desde la base de datos
 * @param PDO $pdo - Conexión a la base de datos
 * @param array $cart - Array con el carrito de la sesión
 * @return array - Array con los productos del carrito
 */
function getCartProducts($pdo, $cart) {
    if (empty($cart)) {
        return [];
    }
    
    try {
        $product_ids = array_keys($cart);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        // Verificar si la tabla product_images existe
        $table_check = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetch();
        
        if ($table_check) {
            // Consulta CON product_images (sin usar is_active que no existe)
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, 
                       COALESCE(p.price_pesos, p.price_dollars, 0) as price, 
                       p.main_image, p.stock_quantity,
                       COALESCE(
                           (SELECT pi.image_url 
                            FROM product_images pi 
                            WHERE pi.product_id = p.id 
                            AND pi.is_primary = 1
                            LIMIT 1),
                           p.main_image,
                           ''
                       ) as primary_image,
                       CASE 
                           WHEN p.category_id = 1 THEN 'PlayStation'
                           WHEN p.category_id = 2 THEN 'Nintendo'
                           WHEN p.category_id = 3 THEN 'Xbox'
                           WHEN p.category_id = 4 THEN 'Sega'
                           ELSE 'General'
                       END as category,
                       CASE 
                           WHEN p.brand_id = 1 THEN 'Sony'
                           WHEN p.brand_id = 2 THEN 'Nintendo'
                           WHEN p.brand_id = 3 THEN 'Microsoft'
                           WHEN p.brand_id = 4 THEN 'Sega'
                           ELSE 'Genérico'
                       END as brand
                FROM products p
                WHERE p.id IN ($placeholders) AND p.is_active = 1
            ");
        } else {
            // Consulta SIN product_images (fallback)
            $stmt = $pdo->prepare("
                SELECT id, name, 
                       COALESCE(price_pesos, price_dollars, 0) as price, 
                       main_image, stock_quantity,
                       COALESCE(main_image, '') as primary_image,
                       CASE 
                           WHEN category_id = 1 THEN 'PlayStation'
                           WHEN category_id = 2 THEN 'Nintendo'
                           WHEN category_id = 3 THEN 'Xbox'
                           WHEN category_id = 4 THEN 'Sega'
                           ELSE 'General'
                       END as category,
                       CASE 
                           WHEN brand_id = 1 THEN 'Sony'
                           WHEN brand_id = 2 THEN 'Nintendo'
                           WHEN brand_id = 3 THEN 'Microsoft'
                           WHEN brand_id = 4 THEN 'Sega'
                           ELSE 'Genérico'
                       END as brand
                FROM products
                WHERE id IN ($placeholders) AND is_active = 1
            ");
        }
        
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cartProducts = [];
        foreach ($products as $product) {
            $product_id = $product['id'];
            $quantity = $cart[$product_id];
            
            $product['quantity'] = $quantity;
            $product['subtotal'] = $product['price'] * $quantity;
            $cartProducts[] = $product;
        }
        
        return $cartProducts;
        
    } catch (Exception $e) {
        error_log("Error obteniendo productos del carrito: " . $e->getMessage());
        return [];
    }
}

// Obtener productos del carrito
$cartProducts = getCartProducts($pdo, $_SESSION['cart']);
$cartCount = $cartManager->getCartCount();

// Calcular totales
$subtotal = $cartManager->getCartTotal();
$shippingCost = 5000; // Costo fijo de envío
$total = $subtotal + $shippingCost;

// Agregar productos de ejemplo al carrito si está vacío (para testing) ANTES del header
// COMENTADO: Esta lógica causaba redirecciones infinitas
/*
if (empty($_SESSION['cart']) && !isset($_GET['empty'])) {
    $_SESSION['cart'][1] = 1; // Rayman 2
    // Recargar para mostrar el producto
    header('Location: carrito.php');
    exit;
}
*/

// Ahora incluir el header después de todo el procesamiento
require_once 'includes/header.php';

// Debug temporal - eliminar después
// echo "<!-- DEBUG: Cart contents: " . print_r($_SESSION['cart'], true) . " -->";
// echo "<!-- DEBUG: Cart products: " . print_r($cartProducts, true) . " -->";
// echo "<!-- DEBUG: Cart count: " . $cartCount . " -->";
?>

<style>
/* Ocultar flechas (spinners) del input type="number" */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield; /* Firefox */
    appearance: textfield;
}

/* Eliminar borde azul al hacer focus y hacer campo no editable visualmente */
.quantity-input {
    pointer-events: none !important;
    user-select: none !important;
    cursor: default !important;
}

.quantity-input:focus {
    outline: none !important;
    box-shadow: none !important;
    border-color: var(--bs-border-color) !important;
}

/* Opciones de envío dinámicas */
.hover-shipping {
    transition: all 0.3s ease;
    cursor: pointer;
}

.hover-shipping:hover {
    border-color: var(--bs-danger) !important;
    background-color: rgba(220, 53, 69, 0.1);
    transform: translateY(-2px);
}

.hover-shipping input:checked + label {
    color: var(--bs-danger);
}
</style>

<!-- Página del Carrito -->
<div class="container-fluid" style="padding: 20px 15px;">
    <!-- Título del carrito -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="text-white mb-0">Mi Carrito</h1>
        </div>
    </div>

    <!-- Contenido del carrito -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <?php if (empty($cartProducts)): ?>
                <!-- Carrito vacío -->
                <div class="card bg-dark border-secondary">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h3 class="text-white">Tu carrito está vacío</h3>
                        <p class="text-muted mb-4">¡Agrega algunos productos increíbles para comenzar!</p>
                        <a href="productos.php" class="btn btn-outline-light">
                            <i class="fas fa-gamepad me-2"></i>Ver Productos
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Productos en el carrito -->
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-dark border-bottom border-secondary">
                        <h5 class="mb-0 text-white">Productos en tu carrito</h5>
                    </div>
                    <div class="card-body bg-dark">
                        <?php foreach ($cartProducts as $product): ?>
                            <?php
                            $quantity = $_SESSION['cart'][$product['id']];
                            $subtotal = $product['price'] * $quantity;
                            ?>
                            <div class="row border-bottom border-secondary py-3 align-items-center" data-product-id="<?php echo $product['id']; ?>">
                                <div class="col-md-2">
                                    <?php
                                    // Obtener la imagen principal (misma lógica que productos.php)
                                    $image_filename = !empty($product['primary_image']) ? $product['primary_image'] : 
                                                     (!empty($product['main_image']) ? $product['main_image'] : 'product1.jpg');
                                    
                                    // Construir rutas posibles
                                    $possible_paths = [
                                        'uploads/products/' . $image_filename,
                                        'assets/images/products/' . $image_filename,
                                        'admin/uploads/products/' . $image_filename
                                    ];
                                    
                                    // Buscar la ruta correcta
                                    $product_image = 'assets/images/products/product1.jpg'; // Imagen por defecto
                                    $doc_root = $_SERVER['DOCUMENT_ROOT'];
                                    
                                    foreach ($possible_paths as $path) {
                                        $full_path = $doc_root . '/' . $path;
                                        if (file_exists($full_path)) {
                                            $product_image = $path;
                                            break;
                                        }
                                    }
                                    
                                    // Si no se encontró, intentar con la ruta directa
                                    if ($product_image === 'assets/images/products/product1.jpg' && !empty($image_filename)) {
                                        if (strpos($image_filename, '/') !== false || strpos($image_filename, 'http') === 0) {
                                            $product_image = $image_filename;
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="img-fluid rounded"
                                         onerror="this.src='assets/images/products/product1.jpg';">
                                </div>
                                <div class="col-md-5">
                                    <h6 class="mb-1 text-white"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <p class="text-danger h5 mb-0">$<?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-light">Cantidad</label>
                                    <div class="input-group">
                                        <button class="btn btn-outline-light" type="button" 
                                                onclick="updateQuantity(<?php echo $product['id']; ?>, -1)" 
                                                <?php echo ($quantity <= 1) ? 'disabled' : ''; ?>>-</button>
                                        <input type="number" class="form-control quantity-input bg-dark text-white border-secondary text-center" 
                                               value="<?php echo $quantity; ?>" 
                                               min="1" 
                                               max="<?php echo min($product['stock_quantity'], 10); ?>"
                                               data-stock="<?php echo $product['stock_quantity']; ?>"
                                               readonly
                                               tabindex="-1"
                                               onchange="updateQuantity(<?php echo $product['id']; ?>, this.value, true)">
                                        <button class="btn btn-outline-light" type="button" 
                                                onclick="updateQuantity(<?php echo $product['id']; ?>, 1)"
                                                <?php echo ($quantity >= min($product['stock_quantity'], 10)) ? 'disabled' : ''; ?>>+</button>
                                    </div>
                                    <?php if ($product['stock_quantity'] <= 5 && $product['stock_quantity'] > 0): ?>
                                        <small class="text-warning">⚠️ Solo quedan <?php echo $product['stock_quantity']; ?> unidades</small>
                                    <?php elseif ($product['stock_quantity'] <= 0): ?>
                                        <small class="text-danger">❌ Producto agotado</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="removeFromCart(<?php echo $product['id']; ?>)" 
                                            title="Eliminar producto">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Opciones adicionales -->
                <div class="card mt-3 bg-dark border-secondary">
                    <div class="card-body bg-dark">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="giftOption">
                            <label class="form-check-label text-white" for="giftOption">
                                <i class="fas fa-gift me-2"></i>¿Es para regalo? Marca esta casilla para que sea envuelto
                            </label>
                        </div>

                        <div class="mb-0">
                            <h6 class="text-white"><i class="fas fa-ticket-alt me-2"></i>¿Quieres usar el valor de crédito? Coloca tu CP</h6>
                            <div class="input-group">
                                <input type="text" class="form-control bg-dark text-white border-secondary" placeholder="Código de cupón">
                                <button class="btn btn-outline-danger" type="button">Aplicar</button>
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <div class="col-lg-4">
            <!-- Resumen del pedido -->
            <div class="card bg-dark border-secondary">
                <div class="card-body bg-dark p-4">
                    <!-- Subtotal -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
                        <span class="text-white h6 mb-0">Subtotal (sin envío):</span>
                        <span class="text-white h5 mb-0">$<?php echo number_format($total - $shippingCost, 0, ',', '.'); ?></span>
                    </div>

                    <!-- Código Postal -->
                    <div class="mb-4">
                        <h6 class="text-info mb-3">INGRESA TU CÓDIGO POSTAL</h6>
                        
                        <!-- Formulario de ingreso de CP (inicial) -->
                        <div id="codigoPostalForm">
                            <div class="mb-3">
                                <input type="text" 
                                       class="form-control bg-dark text-white border-secondary" 
                                       placeholder="Ej: 1426, S2001, C1414" 
                                       id="codigoPostal"
                                       value="<?php echo htmlspecialchars($user_postal_code ?? ''); ?>"
                                       onkeyup="updateCalculateButton()"
                                       maxlength="5">
                            </div>
                            <button class="btn btn-secondary w-100 mb-3" id="calculateBtn" onclick="calcularEnvio()" <?php echo empty($user_postal_code) ? 'disabled' : ''; ?>>
                                CALCULAR
                            </button>
                        </div>
                        
                        <!-- Confirmación de CP (después de calcular) -->
                        <div id="codigoPostalConfirmed" style="display: none;">
                            <div class="bg-dark border border-success rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-success fw-bold">
                                            <i class="fas fa-map-marker-alt me-2"></i>Entregas para el CP: <span id="confirmedCP"></span>
                                        </div>
                                        <div class="text-muted small">Código postal confirmado</div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" onclick="cambiarCodigoPostal()">
                                        <i class="fas fa-edit"></i> CAMBIAR CP
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div id="codigoPostalStatus" class="text-muted small" style="display: none;">
                            <i class="fas fa-check-circle text-success me-1"></i>Código postal válido
                        </div>
                        <div id="noCodigoPostal" class="text-danger small">
                            <i class="fas fa-exclamation-circle me-1"></i>No sé mi código postal
                        </div>
                    </div>

                    <!-- Opciones de envío (se cargan dinámicamente) -->
                    <div id="shippingOptions" style="display: none;" class="mb-4">
                        <h6 class="text-white mb-3">
                            <i class="fas fa-shipping-fast me-2"></i>OPCIONES DE ENVÍO
                            <span id="shippingToCP" class="text-muted small"></span>
                        </h6>
                        
                        <!--Loader mientras carga -->
                        <div id="shippingLoader" style="display: none;" class="text-center py-4">
                            <div class="spinner-border text-danger" role="status">
                                <span class="visually-hidden">Calculando...</span>
                            </div>
                            <p class="text-muted mt-2">Calculando opciones de envío...</p>
                        </div>
                        
                        <!-- Contenedor para opciones dinámicas -->
                        <div id="shippingMethodsContainer"></div>
                        
                        <!-- Mensaje si no hay opciones -->
                        <div id="noShippingOptions" style="display: none;" class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay opciones de envío disponibles para este código postal.
                            Podés retirar en nuestro local sin cargo.
                        </div>
                    </div>

                    <!-- Opciones de retiro (SIEMPRE VISIBLES) -->
                    <div id="pickupOptions" class="mb-4">
                        <h6 class="text-white mb-3 mt-4">RETIRAR POR</h6>
                        
                        <div class="form-check mb-3 p-3 border border-secondary rounded">
                            <input class="form-check-input" type="radio" name="shippingMethod" id="multigamer360" value="0" onchange="updateShippingSelection(0, '0')">
                            <label class="form-check-label text-white w-100" for="multigamer360">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div>Multigamer 360</div>
                                        <div class="text-success small">Retirá hoy</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-success fw-bold">Gratis</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="form-check mb-3 p-3 border border-secondary rounded">
                            <input class="form-check-input" type="radio" name="shippingMethod" id="puntoRetiro" value="2207" onchange="updateShippingSelection(2207, '2207')">
                            <label class="form-check-label text-white w-100" for="puntoRetiro">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div>Punto de retiro</div>
                                        <div class="text-success small">Retirá entre 2 y 6 días hábiles</div>
                                        <div class="text-danger small">👁 Ver ubicaciones</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-danger fw-bold">$2.207</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Total (oculto hasta que se calcule el envío) -->
                    <div class="border-top border-secondary pt-3" id="totalSection" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-danger h5 mb-0">Total:</span>
                            <div class="text-end">
                                <div class="text-danger h4 mb-0" id="totalAmount">$<?php echo number_format($total, 0, ',', '.'); ?></div>
                                <div class="text-muted small" id="totalWithTax" style="display: none;">O $XXX con Efectivo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="d-grid gap-3 mt-4">
                <button type="button" class="btn btn-danger btn-lg py-3 w-100" onclick="iniciarCompra()">
                    <i class="fas fa-credit-card me-2"></i>INICIAR COMPRA
                </button>
                <a href="productos.php" class="btn btn-outline-danger py-2">
                    <i class="fas fa-arrow-left me-2"></i>VER MÁS PRODUCTOS
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Actualizar cantidad
function updateQuantity(productId, change, absolute = false) {
    const data = new FormData();
    data.append('product_id', productId);
    
    if (absolute) {
        data.append('quantity', change);
        data.append('action', 'set');
    } else {
        data.append('quantity_change', change);
        data.append('action', 'update');
    }
    
    fetch('ajax/update-cart.php', {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.log('Update quantity result:', result); // Debug
        if (result.success) {
            // Verificar si el carrito está vacío
            if (result.cart_count === 0) {
                console.log('Carrito vacío después de actualizar cantidad, redirigiendo...');
                window.location.href = 'carrito.php?empty=1';
            } else {
                location.reload();
            }
        } else {
            console.error('Error al actualizar carrito:', result.message);
            alert('Error al actualizar el carrito: ' + (result.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el carrito: ' + error.message);
    });
}

// Remover producto del carrito
function removeFromCart(productId) {
    console.log('Eliminando producto:', productId); // Debug
    
    const data = new FormData();
    data.append('product_id', productId);
    data.append('action', 'clear'); // Agregar acción para eliminar completamente
    
    fetch('ajax/remove-from-cart.php', {
        method: 'POST',
        body: data
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
            console.log('Response data:', result); // Debug
            if (result.success) {
                console.log('Producto eliminado exitosamente'); // Debug
                
                // Verificar si el carrito está vacío
                if (result.cart_count === 0) {
                    console.log('Carrito vacío, redirigiendo...');
                    // Redirigir a carrito vacío
                    window.location.href = 'carrito.php?empty=1';
                } else {
                    // Recargar la página normal
                    location.reload();
                }
            } else {
                console.error('Error del servidor:', result.message);
                alert('Error al eliminar el producto: ' + (result.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            alert('Error al eliminar el producto: ' + error.message);
        });
}

// Limpiar todo el carrito
function clearCart() {
    console.log('Limpiando carrito completo...'); // Debug
    
    fetch('ajax/clear-cart.php', {
        method: 'POST'
    })
    .then(response => {
        console.log('Clear cart response status:', response.status); // Debug
        return response.json();
    })
    .then(result => {
            console.log('Clear cart response data:', result); // Debug
            if (result.success) {
                console.log('Carrito limpiado exitosamente, recargando página...'); // Debug
                location.reload();
            } else {
                alert('Error al limpiar el carrito: ' + (result.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al limpiar carrito:', error);
            alert('Error al limpiar el carrito');
        });
}

// Ir al checkout
function goToCheckout() {
    // Verificar que se haya seleccionado un método de envío
    const selectedShipping = document.querySelector('input[name="shippingMethod"]:checked');
    
    if (!selectedShipping) {
        alert('Por favor, ingresa tu código postal y selecciona un método de envío antes de continuar.');
        return;
    }
    
    // Guardar información de envío en sessionStorage
    const shippingData = {
        method: selectedShipping.value,
        methodName: selectedShipping.closest('.form-check').querySelector('label').innerText.trim(),
        postalCode: document.getElementById('codigoPostal').value
    };
    
    sessionStorage.setItem('selectedShipping', JSON.stringify(shippingData));
    
    window.location.href = 'checkout.php';
}

// Función para auto-calcular envío basado en código postal
function autoCalculateShipping() {
    const codigoPostal = document.getElementById('codigoPostal').value;
    const shippingOptions = document.getElementById('shippingOptions');
    const noCodigoPostal = document.getElementById('noCodigoPostal');
    const codigoPostalStatus = document.getElementById('codigoPostalStatus');
    
    // Limpiar selecciones previas
    document.querySelectorAll('input[name="shippingMethod"]').forEach(radio => {
        radio.checked = false;
    });
    
    if (codigoPostal.length >= 4) {
        // Mostrar opciones de envío
        shippingOptions.style.display = 'block';
        noCodigoPostal.style.display = 'none';
        codigoPostalStatus.style.display = 'block';
        
        // Guardar código postal en el form data (sin auto-seleccionar método)
        const cp = parseInt(codigoPostal);
        
        // Solo determinar el código de sucursal sin seleccionar automáticamente
        let sucursalCode = '';
        if (cp >= 1000 && cp <= 1499) {
            // CABA
            sucursalCode = '3500';
        } else if (cp >= 1400 && cp <= 2000) {
            // Gran Buenos Aires
            sucursalCode = '5648';
        } else {
            // Interior del país
            sucursalCode = '6214';
        }
        
        updateFormData(sucursalCode, codigoPostal);
        
        // Hacer scroll suave hacia las opciones de envío
        setTimeout(() => {
            shippingOptions.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
        
    } else if (codigoPostal.length === 0) {
        // Si está vacío, ocultar todo
        shippingOptions.style.display = 'none';
        noCodigoPostal.style.display = 'block';
        codigoPostalStatus.style.display = 'none';
        
        // Resetear total
        const subtotal = <?php echo $total - $shippingCost; ?>;
        document.getElementById('totalAmount').textContent = '$' + subtotal.toLocaleString('es-AR');
        document.getElementById('totalWithTax').style.display = 'none';
    } else {
        // Código incompleto
        shippingOptions.style.display = 'none';
        noCodigoPostal.style.display = 'none';
        codigoPostalStatus.style.display = 'none';
    }
}

// Función para actualizar el estado del botón calcular
function updateCalculateButton() {
    const codigoPostal = document.getElementById('codigoPostal').value;
    const calculateBtn = document.getElementById('calculateBtn');
    const noCodigoPostal = document.getElementById('noCodigoPostal');
    
    if (codigoPostal.length >= 4) {
        calculateBtn.disabled = false;
        calculateBtn.className = 'btn btn-danger w-100 mb-3';
        calculateBtn.style.cursor = 'pointer';
        noCodigoPostal.style.display = 'none';
    } else {
        calculateBtn.disabled = true;
        calculateBtn.className = 'btn btn-secondary w-100 mb-3';
        calculateBtn.style.cursor = 'not-allowed';
        
        // Mostrar "No sé mi código postal" solo si el campo está vacío
        if (codigoPostal.length === 0) {
            noCodigoPostal.style.display = 'block';
        } else {
            noCodigoPostal.style.display = 'none';
        }
        
        // Ocultar opciones si se borra el código
        const shippingOptions = document.getElementById('shippingOptions');
        const codigoPostalStatus = document.getElementById('codigoPostalStatus');
        shippingOptions.style.display = 'none';
        codigoPostalStatus.style.display = 'none';
        
        // Resetear total
        const subtotal = <?php echo $total - $shippingCost; ?>;
        document.getElementById('totalAmount').textContent = '$' + subtotal.toLocaleString('es-AR');
        document.getElementById('totalWithTax').style.display = 'none';
        
        // Limpiar form data
        updateFormData('', '');
    }
}

// Función para mostrar/ocultar opciones de envío (mantenida para compatibilidad)
function toggleShippingOptions() {
    updateCalculateButton();
}

// Función legacy - ahora redirige al cálculo manual
function autoCalculateShipping() {
    updateCalculateButton();
}

// Función para calcular envío (ejecuta al hacer click en CALCULAR)
function calcularEnvio() {
    const codigoPostal = document.getElementById('codigoPostal').value;
    const shippingOptions = document.getElementById('shippingOptions');
    const noCodigoPostal = document.getElementById('noCodigoPostal');
    const codigoPostalStatus = document.getElementById('codigoPostalStatus');
    
    if (codigoPostal.length >= 4) {
        // Mostrar confirmación de CP y ocultar formulario
        mostrarConfirmacionCP(codigoPostal);
        
        // Mostrar opciones de envío
        shippingOptions.style.display = 'block';
        noCodigoPostal.style.display = 'none';
        codigoPostalStatus.style.display = 'none';
        
        // Limpiar selecciones previas
        document.querySelectorAll('input[name="shippingMethod"]').forEach(radio => {
            radio.checked = false;
        });
        
        // Guardar código postal sin auto-seleccionar método
        const cp = parseInt(codigoPostal);
        
        // Solo determinar el código de sucursal sin seleccionar automáticamente
        let sucursalCode = '';
        if (cp >= 1000 && cp <= 1499) {
            // CABA
            sucursalCode = '3500';
        } else if (cp >= 1400 && cp <= 2000) {
            // Gran Buenos Aires
            sucursalCode = '5648';
        } else {
            // Interior del país
            sucursalCode = '6214';
        }
        
        updateFormData(sucursalCode, codigoPostal);
        
        // Hacer scroll suave hacia las opciones de envío
        setTimeout(() => {
            shippingOptions.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
        
    } else {
        alert('Por favor, ingresa un código postal válido (mínimo 4 dígitos)');
    }
}

// Función para mostrar la confirmación del código postal y calcular envíos
async function mostrarConfirmacionCP(codigoPostal) {
    const formCP = document.getElementById('codigoPostalForm');
    const confirmedCP = document.getElementById('codigoPostalConfirmed');
    const confirmedCPSpan = document.getElementById('confirmedCP');
    const shippingOptions = document.getElementById('shippingOptions');
    const shippingToCP = document.getElementById('shippingToCP');
    const loader = document.getElementById('shippingLoader');
    const container = document.getElementById('shippingMethodsContainer');
    const noOptions = document.getElementById('noShippingOptions');
    
    // Ocultar formulario y mostrar confirmación
    formCP.style.display = 'none';
    confirmedCP.style.display = 'block';
    confirmedCPSpan.textContent = codigoPostal;
    
    // Mostrar opciones de envío y loader
    shippingOptions.style.display = 'block';
    shippingToCP.textContent = '(CP: ' + codigoPostal + ')';
    loader.style.display = 'block';
    container.innerHTML = '';
    noOptions.style.display = 'none';
    
    try {
        // Llamar a la API para calcular envíos
        const response = await fetch('ajax/calculate-shipping.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `postal_code=${codigoPostal}&cart_total=<?php echo $subtotal; ?>&cart_weight=1.5`
        });
        
        const data = await response.json();
        
        loader.style.display = 'none';
        
        if (data.success && data.options && data.options.length > 0) {
            // Crear HTML para cada opción
            let optionsHTML = '';
            
            data.options.forEach((option, index) => {
                const isFirst = index === 0;
                optionsHTML += `
                    <div class="form-check mb-3 p-3 border border-secondary rounded hover-shipping">
                        <input class="form-check-input" type="radio" name="shippingMethod" 
                               id="shipping_${option.id}" 
                               value="${option.price}" 
                               data-method-id="${option.id}"
                               data-method-name="${option.name}"
                               onchange="updateShippingSelection(${option.price}, '${option.id}', '${option.name}')"
                               ${isFirst ? 'checked' : ''}>
                        <label class="form-check-label text-white w-100" for="shipping_${option.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-white">${option.name}</div>
                                    ${option.description ? `<div class="text-white-50 small">${option.description}</div>` : ''}
                                    <div class="text-success small">
                                        <i class="fas fa-clock me-1"></i>${option.delivery_text}
                                    </div>
                                    ${option.is_estimated ? '<span class="badge bg-info text-dark mt-1">Estimado</span>' : ''}
                                </div>
                                <div class="text-end">
                                    <span class="text-danger fw-bold fs-5">${option.price_formatted}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                `;
            });
            
            container.innerHTML = optionsHTML;
            
            // Auto-seleccionar la primera opción
            if (data.options[0]) {
                updateShippingSelection(
                    data.options[0].price, 
                    data.options[0].id,
                    data.options[0].name
                );
            }
            
        } else {
            // No hay opciones disponibles
            noOptions.style.display = 'block';
            container.innerHTML = '';
        }
        
    } catch (error) {
        console.error('Error al calcular envío:', error);
        loader.style.display = 'none';
        
        // Mostrar error
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Error al calcular envío. Por favor intenta nuevamente.
            </div>
        `;
    }
}

// Función para cambiar el código postal
function cambiarCodigoPostal() {
    const formCP = document.getElementById('codigoPostalForm');
    const confirmedCP = document.getElementById('codigoPostalConfirmed');
    const shippingOptions = document.getElementById('shippingOptions');
    const noCodigoPostal = document.getElementById('noCodigoPostal');
    const codigoPostalInput = document.getElementById('codigoPostal');
    const totalSection = document.getElementById('totalSection');
    
    // Mostrar formulario y ocultar confirmación
    formCP.style.display = 'block';
    confirmedCP.style.display = 'none';
    
    // Ocultar opciones de envío y total
    shippingOptions.style.display = 'none';
    noCodigoPostal.style.display = 'block';
    totalSection.style.display = 'none';
    
    // Limpiar selecciones
    document.querySelectorAll('input[name="shippingMethod"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Resetear total
    const subtotal = <?php echo $total - $shippingCost; ?>;
    document.getElementById('totalAmount').textContent = '$' + subtotal.toLocaleString('es-AR');
    document.getElementById('totalWithTax').style.display = 'none';
    
    // Limpiar form data
    updateFormData('', '');
    
    // Enfocar el input
    codigoPostalInput.focus();
    
    // Actualizar estado del botón
    updateCalculateButton();
}

// Función para actualizar los campos hidden del formulario
function updateFormData(shippingMethod, postalCode) {
    const shippingMethodInput = document.getElementById('selectedShippingMethod');
    const postalCodeInput = document.getElementById('selectedPostalCode');
    
    if (shippingMethodInput) {
        shippingMethodInput.value = shippingMethod;
    }
    
    if (postalCodeInput) {
        postalCodeInput.value = postalCode;
    }
}

// Función para validar antes de ir al checkout
function validateShipping() {
    const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked');
    const postalCode = document.getElementById('codigoPostal').value;
    
    if (!shippingMethod) {
        alert('Por favor, selecciona un método de envío antes de continuar.');
        return false;
    }
    
    // Si es retiro en local (Multigamer 360), NO requiere código postal
    if (shippingMethod.value === '0') {
        return true;
    }
    
    // Para envíos a domicilio, SÍ requiere código postal
    if (!postalCode) {
        alert('Por favor, ingresa tu código postal antes de continuar.');
        return false;
    }
    
    return true;
}

// Función para iniciar compra (guardar shipping y redirigir)
function iniciarCompra() {
    // Primero validar
    if (!validateShipping()) {
        return;
    }
    
    // Obtener método seleccionado
    const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked');
    const postalCode = document.getElementById('codigoPostal').value;
    
    // Preparar datos
    const formData = new FormData();
    formData.append('shippingMethod', shippingMethod.value);
    
    // Solo enviar código postal si NO es retiro en local
    if (shippingMethod.value !== '0') {
        formData.append('postalCode', postalCode);
    } else {
        // Para retiro en local, no enviamos código postal (o enviamos string vacío)
        formData.append('postalCode', '');
    }
    
    // Enviar por AJAX
    fetch('ajax/set-shipping.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirigir a checkout
            window.location.href = 'checkout.php';
        } else {
            alert('Error al guardar método de envío: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

// Función para manejar clicks manuales en opciones de envío
function updateShippingSelection(cost, methodId, methodName = '') {
    const postalCode = document.getElementById('codigoPostal').value;
    updateShipping(cost);
    updateFormData(methodId, postalCode);
    
    // Guardar también el nombre del método
    const shippingNameInput = document.getElementById('selectedShippingName');
    if (shippingNameInput && methodName) {
        shippingNameInput.value = methodName;
    }
}

// Función para actualizar el total cuando se selecciona un método de envío
function updateShipping(shippingCost) {
    const subtotal = <?php echo $total - $shippingCost; ?>;
    const newTotal = subtotal + parseInt(shippingCost);
    
    // Mostrar la sección del total
    document.getElementById('totalSection').style.display = 'block';
    
    document.getElementById('totalAmount').textContent = '$' + newTotal.toLocaleString('es-AR');
    
    // Mostrar precio con efectivo (simulado con 10% descuento)
    const totalWithCash = Math.floor(newTotal * 0.9);
    const totalWithTaxElement = document.getElementById('totalWithTax');
    totalWithTaxElement.textContent = 'O $' + totalWithCash.toLocaleString('es-AR') + ' con Efectivo';
    totalWithTaxElement.style.display = 'block';
}

// Auto-calcular si hay código postal precargado
window.addEventListener('DOMContentLoaded', function() {
    const codigoPostalInput = document.getElementById('codigoPostal');
    const userPostalCode = '<?php echo $user_postal_code ?? ''; ?>';
    
    // Si hay código postal del usuario, calcular automáticamente
    if (userPostalCode && userPostalCode.length >= 4) {
        console.log('Código postal detectado:', userPostalCode);
        
        // Simular click en el botón calcular después de un momento
        setTimeout(function() {
            calcularEnvio();
        }, 500);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
