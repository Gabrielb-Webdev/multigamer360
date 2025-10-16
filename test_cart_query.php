<?php
/**
 * Test para verificar la consulta del carrito
 */

require_once 'config/database.php';

// Simular productos en el carrito
$cart = [
    1 => 1,  // Product ID 1, quantity 1
    2 => 1,  // Product ID 2, quantity 1
    3 => 1   // Product ID 3, quantity 1
];

echo "<h2>Test de consulta del carrito</h2>";

// Test 1: Verificar si existe la tabla product_images
echo "<h3>Test 1: ¿Existe la tabla product_images?</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_images'");
    $table_exists = $stmt->fetch();
    if ($table_exists) {
        echo "✅ La tabla product_images existe<br>";
    } else {
        echo "❌ La tabla product_images NO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 2: Consulta SIMPLE sin product_images
echo "<h3>Test 2: Consulta simple (sin product_images)</h3>";
try {
    $product_ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT id, name, price_pesos as price, image_url
        FROM products 
        WHERE id IN ($placeholders) AND is_active = 1
    ");
    
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "✅ Consulta exitosa: " . count($products) . " productos encontrados<br>";
        echo "<pre>" . print_r($products, true) . "</pre>";
    } else {
        echo "❌ No se encontraron productos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error en consulta simple: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 3: Consulta CON product_images (si existe)
if ($table_exists) {
    echo "<h3>Test 3: Consulta con product_images</h3>";
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price_pesos as price, p.image_url,
                   COALESCE(
                       (SELECT pi.image_url 
                        FROM product_images pi 
                        WHERE pi.product_id = p.id 
                        AND pi.is_primary = 1 
                        AND pi.is_active = 1 
                        LIMIT 1),
                       p.image_url
                   ) as primary_image
            FROM products p
            WHERE p.id IN ($placeholders) AND p.is_active = 1
        ");
        
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            echo "✅ Consulta exitosa: " . count($products) . " productos encontrados<br>";
            echo "<pre>" . print_r($products, true) . "</pre>";
        } else {
            echo "❌ No se encontraron productos<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error en consulta con product_images: " . $e->getMessage() . "<br>";
    }
}

echo "<br><br>";
echo "<a href='carrito.php'>Volver al carrito</a>";
?>
