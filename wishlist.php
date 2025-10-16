<?php
require_once 'config/session_config.php';
initSecureSession();

require_once 'includes/auth.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Obtener productos de la wishlist
$wishlist_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.image_url, p.stock_quantity,
               uf.created_at as added_date
        FROM user_favorites uf
        JOIN products p ON uf.product_id = p.id
        WHERE uf.user_id = ? AND p.is_active = 1
        ORDER BY uf.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $wishlist_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $wishlist_products = [];
}
?>

<div class="container my-5">
    <!-- Encabezado de wishlist mejorado -->
    <div class="wishlist-header mb-5">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="wishlist-title">
                    <i class="fas fa-heart text-danger pulse-heart"></i>
                    Mi Lista de Deseos
                </h1>
                <p class="wishlist-subtitle">
                    Guarda tus productos favoritos para comprar más tarde
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="wishlist-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($wishlist_products); ?></span>
                        <span class="stat-label">Productos guardados</span>
                    </div>
                    <?php if (!empty($wishlist_products)): ?>
                    <div class="stat-item">
                        <span class="stat-number">
                            $<?php echo number_format(array_sum(array_column($wishlist_products, 'price')), 2); ?>
                        </span>
                        <span class="stat-label">Valor total</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($wishlist_products)): ?>
        <div class="wishlist-actions-bar mt-4">
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <a href="productos.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus"></i> Agregar más productos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($wishlist_products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-heart-broken display-1 text-danger mb-4"></i>
            <h3 class="text-white">Tu lista está vacía</h3>
            <p class="text-white">Explora productos y agréguelos a tu wishlist</p>
            <a href="productos.php" class="btn btn-outline-light">
                <i class="fas fa-gamepad me-2"></i>Ver Productos
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlist_products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4" id="product-<?php echo $product['id']; ?>">
                    <div class="wishlist-card">
                        <!-- Imagen del producto -->
                        <div class="wishlist-image-container">
                            <?php
                            // Procesar la imagen de manera similar a productos.php
                            $image_filename = !empty($product['image_url']) ? $product['image_url'] : 'product1.jpg';
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
                                 class="wishlist-product-image" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='assets/images/products/product1.jpg'">
                            
                            <!-- Botón de quitar de wishlist flotante -->
                            <button class="btn-remove-wishlist" 
                                    data-id="<?php echo $product['id']; ?>"
                                    title="Quitar de wishlist">
                                <i class="fas fa-times"></i>
                            </button>
                            
                            <!-- Indicador de stock -->
                            <div class="stock-indicator <?php echo ($product['stock_quantity'] > 0) ? 'in-stock' : 'out-stock'; ?>">
                                <?php echo ($product['stock_quantity'] > 0) ? 'En Stock' : 'Sin Stock'; ?>
                            </div>
                        </div>
                        
                        <!-- Información del producto -->
                        <div class="wishlist-info">
                            <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <div class="price-section">
                                <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                            </div>
                            <div class="added-date">
                                <i class="fas fa-heart text-danger"></i>
                                Agregado el <?php echo date('d/m/Y', strtotime($product['added_date'])); ?>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="wishlist-actions">
                            <div class="action-buttons">
                                <!-- Ver producto -->
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" 
                                   class="btn-action btn-view" 
                                   title="Ver detalles del producto">
                                    <i class="fas fa-eye"></i>
                                    <span>Ver Producto</span>
                                </a>
                                
                                <!-- Agregar al carrito -->
                                <button class="btn-action btn-cart add-to-cart" 
                                        data-id="<?php echo $product['id']; ?>"
                                        title="Agregar al carrito">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Al Carrito</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* ===================================================== */
/* ESTILOS PARA WISHLIST MEJORADA */
/* ===================================================== */

/* Encabezado de wishlist */
.wishlist-header {
    background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
    border-radius: 15px;
    padding: 30px;
    border: 1px solid #333;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.wishlist-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.pulse-heart {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.wishlist-subtitle {
    color: #999;
    font-size: 1.1rem;
    margin: 0;
}

.wishlist-stats {
    display: flex;
    gap: 30px;
    justify-content: flex-end;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: #dc3545;
    line-height: 1;
}

.stat-label {
    display: block;
    color: #999;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.wishlist-actions-bar {
    border-top: 1px solid #333;
    padding-top: 20px;
}

.wishlist-card {
    background: linear-gradient(145deg, #2c2c2c, #1a1a1a);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    border: 1px solid #333;
}

.wishlist-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    border-color: #dc3545;
}

.wishlist-image-container {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.wishlist-product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.wishlist-card:hover .wishlist-product-image {
    transform: scale(1.05);
}

/* Botón de quitar flotante */
.btn-remove-wishlist {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 2;
}

.btn-remove-wishlist:hover {
    background: #dc3545;
    transform: scale(1.1);
}

/* Indicador de stock */
.stock-indicator {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.stock-indicator.in-stock {
    background: rgba(40, 167, 69, 0.9);
    color: white;
}

.stock-indicator.out-stock {
    background: rgba(220, 53, 69, 0.9);
    color: white;
}

/* Información del producto */
.wishlist-info {
    padding: 20px;
}

.product-title {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
    line-height: 1.3;
    height: 2.6em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
}

.price-section {
    margin-bottom: 10px;
}

.current-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #28a745;
}

.added-date {
    color: #999;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Botones de acción */
.wishlist-actions {
    padding: 0 20px 20px;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.btn-action {
    flex: 1;
    padding: 12px 8px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-view {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
}

.btn-view:hover {
    background: linear-gradient(45deg, #0056b3, #004085);
    color: white;
    transform: translateY(-2px);
}

.btn-cart {
    background: linear-gradient(45deg, #28a745, #1e7e34);
    color: white;
}

.btn-cart:hover {
    background: linear-gradient(45deg, #1e7e34, #155724);
    transform: translateY(-2px);
}

.btn-no-stock {
    background: #6c757d;
    color: white;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Animaciones de carga */
.btn-action.loading {
    pointer-events: none;
}

.btn-action.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .wishlist-title {
        font-size: 2rem;
    }
    
    .wishlist-stats {
        justify-content: center;
        margin-top: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-action {
        padding: 15px;
    }
    
    .wishlist-image-container {
        height: 200px;
    }
    
    .wishlist-actions-bar .row {
        text-align: center;
    }
    
    .wishlist-actions-bar .col-md-6:last-child {
        margin-top: 15px;
    }
}

/* Animación de entrada */
.wishlist-card {
    animation: fadeInUp 0.6s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mensaje de lista vacía mejorado */
.text-center.py-5 {
    background: linear-gradient(145deg, #2c2c2c, #1a1a1a);
    border-radius: 15px;
    border: 1px solid #333;
    animation: fadeInUp 0.6s ease forwards;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quitar de wishlist - nuevo selector
    document.querySelectorAll('.btn-remove-wishlist').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            removeFromWishlist(productId, this);
        });
    });
    
    // Agregar al carrito - mantenemos el selector original
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            addToCart(productId, this);
        });
    });
});

function removeFromWishlist(productId, button) {
    // Mostrar estado de carga
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch('ajax/toggle-wishlist.php', {
        method: 'POST',
        body: new URLSearchParams({
            product_id: productId,
            action: 'remove'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animación de salida
            const productCard = document.getElementById('product-' + productId);
            productCard.style.transition = 'all 0.5s ease';
            productCard.style.transform = 'scale(0.8)';
            productCard.style.opacity = '0';
            
            setTimeout(() => {
                productCard.remove();
                
                // Actualizar contador en header si existe la función
                if (typeof syncWishlistCount === 'function') {
                    syncWishlistCount();
                }
                
                // Si no quedan productos, mostrar mensaje vacío
                const remainingProducts = document.querySelectorAll('[id^="product-"]');
                if (remainingProducts.length === 0) {
                    location.reload();
                }
            }, 500);
            
            // Mostrar notificación mejorada
            showNotification('Producto removido de tu wishlist', 'success');
        } else {
            // Restaurar botón en caso de error
            button.innerHTML = originalContent;
            button.disabled = false;
            showNotification(data.message || 'Error al quitar producto', 'error');
        }
    })
    .catch(error => {
        // Restaurar botón en caso de error
        button.innerHTML = originalContent;
        button.disabled = false;
        showNotification('Error de conexión', 'error');
    });
}

function addToCart(productId, button) {
    // Mostrar estado de carga
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Agregando...</span>';
    button.disabled = true;
    
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        body: new URLSearchParams({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cambiar temporalmente el botón para mostrar éxito
            button.innerHTML = '<i class="fas fa-check"></i><span>¡Agregado!</span>';
            button.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
            
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.disabled = false;
                button.style.background = '';
            }, 2000);
            
            showNotification('Producto agregado al carrito', 'success');
            
            // Actualizar contador del carrito si existe la función
            if (typeof syncCartInstantly === 'function') {
                syncCartInstantly();
            }
        } else {
            button.innerHTML = originalContent;
            button.disabled = false;
            showNotification(data.message || 'Error al agregar producto', 'error');
        }
    })
    .catch(error => {
        button.innerHTML = originalContent;
        button.disabled = false;
        showNotification('Error de conexión', 'error');
    });
}

// Función para mostrar notificaciones mejoradas
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `wishlist-notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
    `;
    
    // Estilos para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Agregar estilos CSS para las animaciones de notificación
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(notificationStyles);
</script>

<?php require_once 'includes/footer.php'; ?>