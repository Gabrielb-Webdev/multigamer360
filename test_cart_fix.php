<?php
/**
 * Script de prueba para verificar que el carrito funciona correctamente
 */

require_once 'config/session_config.php';
$sessionId = initSecureSession();

require_once 'config/database.php';
require_once 'includes/cart_manager.php';

// Activar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Carrito - Diagnóstico</h2>";
echo "<pre>";

// 1. Verificar conexión a BD
echo "1. VERIFICANDO CONEXIÓN A BASE DE DATOS...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $total = $stmt->fetchColumn();
    echo "   ✓ Conexión exitosa - Total productos en BD: $total\n\n";
} catch (Exception $e) {
    echo "   ✗ Error de conexión: " . $e->getMessage() . "\n\n";
    exit;
}

// 2. Verificar que existan productos
echo "2. VERIFICANDO PRODUCTOS DISPONIBLES...\n";
$stmt = $pdo->query("SELECT id, name, stock_quantity, price FROM products LIMIT 5");
$productos = $stmt->fetchAll();
if (count($productos) > 0) {
    echo "   ✓ Productos encontrados:\n";
    foreach ($productos as $prod) {
        echo "     - ID: {$prod['id']} | {$prod['name']} | Stock: {$prod['stock_quantity']} | Precio: \${$prod['price']}\n";
    }
    echo "\n";
    $product_id_test = $productos[0]['id'];
} else {
    echo "   ✗ No se encontraron productos en la BD\n\n";
    exit;
}

// 3. Probar el método getProductInfo
echo "3. PROBANDO getProductInfo PARA PRODUCTO ID $product_id_test...\n";
$cartManager = new CartManager($pdo);
$productInfo = $cartManager->getProductInfo($product_id_test);

if ($productInfo) {
    echo "   ✓ Producto encontrado:\n";
    echo "     - ID: {$productInfo['id']}\n";
    echo "     - Nombre: {$productInfo['name']}\n";
    echo "     - Stock: {$productInfo['stock']}\n";
    echo "     - Precio: \${$productInfo['price']}\n\n";
} else {
    echo "   ✗ ERROR: getProductInfo no devolvió datos\n\n";
    exit;
}

// 4. Probar agregar al carrito
echo "4. PROBANDO addToCart PARA PRODUCTO ID $product_id_test...\n";
$result = $cartManager->addToCart($product_id_test, 1);

echo "   Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($result['success']) {
    echo "   ✓ ÉXITO: Producto agregado al carrito\n";
    echo "   - Items en carrito: {$result['cart_count']}\n\n";
} else {
    echo "   ✗ ERROR: {$result['message']}\n\n";
}

// 5. Verificar contenido del carrito
echo "5. VERIFICANDO CONTENIDO DEL CARRITO...\n";
$cartInfo = $cartManager->getCartInfo();
if (count($cartInfo['items']) > 0) {
    echo "   ✓ Items en el carrito:\n";
    foreach ($cartInfo['items'] as $item) {
        echo "     - {$item['name']} (ID: {$item['product_id']}) x{$item['quantity']} = \${$item['subtotal']}\n";
    }
    echo "\n   Total del carrito: \$" . $cartInfo['total'] . "\n";
} else {
    echo "   ⚠ Carrito vacío\n";
}

echo "\n</pre>";

echo "<hr>";
echo "<h3>Conclusión:</h3>";
if ($result['success']) {
    echo "<p style='color: green; font-weight: bold;'>✓ El sistema de carrito está funcionando correctamente.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Hay un problema con el sistema de carrito. Revisa los logs arriba.</p>";
}
?>
