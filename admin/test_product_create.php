<?php
/**
 * Script de diagnóstico para product_create.php
 * Ejecutar en: /admin/test_product_create.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

echo "<h2>🔍 Diagnóstico de product_create.php</h2>";
echo "<hr>";

// 1. Verificar sesión
echo "<h3>1. Estado de la Sesión:</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sesión activa<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Variables de sesión: <pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "❌ Sesión no activa<br>";
}

// 2. Verificar GET parameters
echo "<h3>2. Parámetros GET:</h3>";
if (!empty($_GET)) {
    echo "<pre>" . print_r($_GET, true) . "</pre>";
} else {
    echo "⚠️ No hay parámetros GET<br>";
}

// 3. Simular creación de sesión con producto
echo "<h3>3. Simulación de Producto Creado:</h3>";
$_SESSION['product_created'] = true;
$_SESSION['product_id'] = 999;
$_SESSION['product_name'] = 'Producto de Prueba';

echo "✅ Variables de sesión establecidas:<br>";
echo "- product_created: " . ($_SESSION['product_created'] ? 'true' : 'false') . "<br>";
echo "- product_id: " . $_SESSION['product_id'] . "<br>";
echo "- product_name: " . $_SESSION['product_name'] . "<br>";

// 4. Probar condición del modal
echo "<h3>4. Prueba de Condición del Modal:</h3>";
$showModal = false;
$productId = 0;
$productName = '';

if (isset($_SESSION['product_created']) && $_SESSION['product_created']) {
    $showModal = true;
    $productId = $_SESSION['product_id'] ?? 0;
    $productName = $_SESSION['product_name'] ?? '';
    echo "✅ Modal debería mostrarse (SESSION)<br>";
    echo "- Product ID: $productId<br>";
    echo "- Product Name: $productName<br>";
} elseif (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['pid'])) {
    $showModal = true;
    $productId = intval($_GET['pid']);
    $productName = urldecode($_GET['pname'] ?? '');
    echo "✅ Modal debería mostrarse (GET)<br>";
    echo "- Product ID: $productId<br>";
    echo "- Product Name: $productName<br>";
} else {
    echo "❌ Modal NO debería mostrarse<br>";
}

echo "<h3>5. JSON Encode Test:</h3>";
$testName = "Kingdom Hearts 3";
echo "Test con nombre: $testName<br>";
echo "JSON Encoded: " . json_encode($testName) . "<br>";

echo "<hr>";
echo "<h3>✅ Diagnóstico completado</h3>";
echo "<p><a href='product_create.php?success=1&pid=999&pname=" . urlencode('Test Product') . "'>Probar con GET params</a></p>";
echo "<p><a href='product_create.php'>Volver a product_create.php</a></p>";

// Limpiar
unset($_SESSION['product_created']);
unset($_SESSION['product_id']);
unset($_SESSION['product_name']);
?>
