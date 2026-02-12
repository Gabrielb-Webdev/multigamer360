<?php
// Archivo de prueba para detectar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Diagnóstico</h1>";

// 1. Verificar PHP
echo "<h2>1. Versión de PHP</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. Verificar sesión
echo "<h2>2. Test de Sesión</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✓ Sesión iniciada correctamente<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "✗ Error en sesión: " . $e->getMessage() . "<br>";
}

// 3. Verificar conexión a base de datos
echo "<h2>3. Test de Base de Datos</h2>";
try {
    require_once 'config/database.php';
    echo "✓ Conexión a base de datos exitosa<br>";
    echo "Base de datos: " . ($pdo ? "Conectada" : "No conectada") . "<br>";
} catch (Exception $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "<br>";
}

// 4. Verificar includes
echo "<h2>4. Test de Includes</h2>";
$includes = [
    'includes/auth.php',
    'includes/functions.php',
    'includes/product_manager.php',
    'config/user_manager.php',
    'includes/cart_manager.php'
];

foreach ($includes as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "✓ $file - OK<br>";
        } catch (Exception $e) {
            echo "✗ $file - ERROR: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✗ $file - NO EXISTE<br>";
    }
}

// 5. Verificar ProductManager
echo "<h2>5. Test de ProductManager</h2>";
try {
    $productManager = new ProductManager($pdo);
    echo "✓ ProductManager inicializado<br>";

    $featured = $productManager->getFeaturedProducts(5);
    echo "✓ getFeaturedProducts() - " . count($featured) . " productos<br>";

    $new = $productManager->getNewProducts(5);
    echo "✓ getNewProducts() - " . count($new) . " productos<br>";
} catch (Exception $e) {
    echo "✗ Error en ProductManager: " . $e->getMessage() . "<br>";
}

// 6. Verificar rutas de imágenes
echo "<h2>6. Test de Rutas</h2>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";

$image_dir = 'assets/images/retro';
$full_path = $_SERVER['DOCUMENT_ROOT'] . '/multigamer360/' . $image_dir;
echo "Ruta imágenes calculada: $full_path<br>";
echo "¿Existe?: " . (is_dir($full_path) ? "Sí" : "No") . "<br>";

// Intentar sin el subdirectorio
$full_path2 = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_dir;
echo "Ruta alternativa: $full_path2<br>";
echo "¿Existe?: " . (is_dir($full_path2) ? "Sí" : "No") . "<br>";

// Ruta relativa
echo "Ruta relativa existe: " . (is_dir($image_dir) ? "Sí" : "No") . "<br>";

// 7. Verificar header.php
echo "<h2>7. Test de Header</h2>";
try {
    ob_start();
    include 'includes/header.php';
    $header_output = ob_get_clean();
    echo "✓ Header incluido sin errores<br>";
    echo "Tamaño del output: " . strlen($header_output) . " bytes<br>";
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ Error en header: " . $e->getMessage() . "<br>";
}

echo "<h2>✓ Diagnóstico Completo</h2>";
?>
