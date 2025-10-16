<?php
/**
 * Prueba simple de agregar al carrito
 * Accede a este archivo directamente desde el navegador:
 * https://teal-fish-507993.hostingersite.com/test_add_cart_simple.php?product_id=1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Test Simple: Agregar Producto al Carrito</h2>";
echo "<pre style='background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 5px;'>";

// Configuración
require_once 'config/session_config.php';
$sessionId = initSecureSession();
require_once 'config/database.php';
require_once 'includes/cart_manager.php';

echo "✅ Archivos cargados correctamente\n\n";

// Obtener ID del producto desde URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 1;

echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 1: VERIFICAR CONEXIÓN A BASE DE DATOS\n";
echo "═══════════════════════════════════════════════════════════════\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $total = $stmt->fetchColumn();
    echo "✅ Conexión a BD exitosa\n";
    echo "   Total productos en BD: $total\n\n";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n\n";
    exit;
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 2: BUSCAR PRODUCTO ID $product_id\n";
echo "═══════════════════════════════════════════════════════════════\n";

$stmt = $pdo->prepare("SELECT id, name, stock_quantity, price_pesos FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if ($product) {
    echo "✅ Producto encontrado:\n";
    echo "   ID: {$product['id']}\n";
    echo "   Nombre: {$product['name']}\n";
    echo "   Stock: {$product['stock_quantity']}\n";
    echo "   Precio: \${$product['price_pesos']}\n\n";
} else {
    echo "❌ Producto ID $product_id NO EXISTE en la base de datos\n";
    
    // Mostrar productos disponibles
    echo "\n📦 Productos disponibles:\n";
    $stmt = $pdo->query("SELECT id, name, stock_quantity, price_pesos FROM products LIMIT 5");
    $available = $stmt->fetchAll();
    foreach ($available as $p) {
        echo "   - ID: {$p['id']} | {$p['name']} | Stock: {$p['stock_quantity']} | Precio: \${$p['price_pesos']}\n";
    }
    echo "\n";
    exit;
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 3: PROBAR getProductInfo() del CartManager\n";
echo "═══════════════════════════════════════════════════════════════\n";

$cartManager = new CartManager($pdo);
$productInfo = $cartManager->getProductInfo($product_id);

if ($productInfo) {
    echo "✅ getProductInfo() funcionó correctamente:\n";
    echo "   ID: {$productInfo['id']}\n";
    echo "   Nombre: {$productInfo['name']}\n";
    echo "   Stock: {$productInfo['stock']}\n";
    echo "   Precio: \${$productInfo['price']}\n\n";
} else {
    echo "❌ getProductInfo() devolvió FALSE\n";
    echo "   Esto significa que hay un problema con la función\n\n";
    exit;
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 4: INTENTAR AGREGAR AL CARRITO\n";
echo "═══════════════════════════════════════════════════════════════\n";

$result = $cartManager->addToCart($product_id, 1);

echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

if ($result['success']) {
    echo "✅ ¡ÉXITO! El producto se agregó al carrito\n";
    echo "   Items en carrito: {$result['cart_count']}\n";
    
    // Mostrar contenido del carrito
    echo "\n📦 Contenido del carrito:\n";
    $cartInfo = $cartManager->getCartInfo();
    foreach ($cartInfo['items'] as $item) {
        echo "   - {$item['name']} x{$item['quantity']} = \${$item['subtotal']}\n";
    }
    echo "   Total: \${$cartInfo['total']}\n";
} else {
    echo "❌ ERROR: {$result['message']}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "PASO 5: VERIFICAR SESIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "Session ID: $sessionId\n";
echo "Session cart: " . print_r($_SESSION['cart'] ?? [], true) . "\n";

echo "</pre>";

echo "<hr>";
echo "<h3>🔗 Enlaces útiles:</h3>";
echo "<ul>";
echo "<li><a href='test_add_cart_simple.php?product_id=1'>Probar con producto ID 1</a></li>";
echo "<li><a href='test_add_cart_simple.php?product_id=2'>Probar con producto ID 2</a></li>";
echo "<li><a href='test_add_cart_simple.php?product_id=3'>Probar con producto ID 3</a></li>";
echo "<li><a href='view_cart_logs.php'>Ver logs detallados</a></li>";
echo "<li><a href='debug_product_1.php'>Debug producto ID 1</a></li>";
echo "<li><a href='productos.php'>Volver a productos</a></li>";
echo "</ul>";
?>
