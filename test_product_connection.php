<?php
/**
 * Test Product Database Connection
 * Diagnóstico para product-details
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Conexión - Product Details</h1>";

// Test 1: Incluir archivos necesarios
echo "<h2>1. Probando includes...</h2>";
try {
    require_once 'config/database.php';
    echo "✅ config/database.php - OK<br>";
} catch (Exception $e) {
    echo "❌ config/database.php - ERROR: " . $e->getMessage() . "<br>";
    die();
}

try {
    require_once 'includes/product_manager.php';
    echo "✅ includes/product_manager.php - OK<br>";
} catch (Exception $e) {
    echo "❌ includes/product_manager.php - ERROR: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Verificar conexión PDO
echo "<h2>2. Verificando conexión PDO...</h2>";
if (isset($pdo)) {
    echo "✅ Variable \$pdo existe<br>";
    echo "Tipo: " . get_class($pdo) . "<br>";
} else {
    echo "❌ Variable \$pdo NO existe<br>";
    die();
}

// Test 3: Probar ProductManager
echo "<h2>3. Probando ProductManager...</h2>";
try {
    $productManager = new ProductManager($pdo);
    echo "✅ ProductManager creado correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error al crear ProductManager: " . $e->getMessage() . "<br>";
    die();
}

// Test 4: Obtener producto ID=1
echo "<h2>4. Obteniendo producto ID=1...</h2>";
try {
    $product = $productManager->getProductById(1);
    
    if ($product) {
        echo "✅ Producto encontrado<br>";
        echo "<pre>";
        print_r($product);
        echo "</pre>";
    } else {
        echo "❌ Producto NO encontrado (retornó false/null)<br>";
        
        // Verificar si hay productos en la tabla
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de productos activos en DB: " . $count['total'] . "<br>";
        
        // Mostrar primeros 5 productos
        $stmt = $pdo->query("SELECT id, name, is_active FROM products LIMIT 5");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Primeros 5 productos en la tabla:</h3>";
        echo "<pre>";
        print_r($products);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Error al obtener producto: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 5: Verificar método getProductById
echo "<h2>5. Verificando método getProductById...</h2>";
echo "Clase ProductManager tiene estos métodos:<br>";
$methods = get_class_methods($productManager);
echo "<pre>";
print_r($methods);
echo "</pre>";

if (in_array('getProductById', $methods)) {
    echo "✅ Método getProductById existe<br>";
} else {
    echo "❌ Método getProductById NO existe<br>";
}

echo "<h2>Test completado</h2>";
?>
