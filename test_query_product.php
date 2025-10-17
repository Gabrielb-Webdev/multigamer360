<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Test de Consulta ProductById</h1>";

$product_id = 1;

// Test consulta original (la que falló)
echo "<h2>Consulta con LEFT JOIN a consoles:</h2>";
try {
    $sql = "SELECT p.*, 
                   c.name as category_name, 
                   b.name as brand_name,
                   co.name as console_name,
                   p.price_pesos,
                   p.price_usd,
                   p.stock_quantity,
                   p.discount_percentage,
                   p.is_new,
                   p.is_featured,
                   p.is_on_sale,
                   p.image_url,
                   p.description,
                   p.long_description
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN consoles co ON p.console_id = co.id
            WHERE p.id = :id AND p.is_active = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "✅ Producto encontrado con JOIN a consoles<br>";
        echo "<pre>";
        print_r($product);
        echo "</pre>";
    } else {
        echo "❌ No se encontró producto<br>";
    }
} catch (PDOException $e) {
    echo "❌ ERROR en consulta con consoles: " . $e->getMessage() . "<br>";
    echo "Código SQL State: " . $e->getCode() . "<br>";
}

// Test consulta sin consoles
echo "<h2>Consulta SIN JOIN a consoles:</h2>";
try {
    $sql2 = "SELECT p.*, 
                    c.name as category_name, 
                    b.name as brand_name
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             LEFT JOIN brands b ON p.brand_id = b.id 
             WHERE p.id = :id AND p.is_active = 1";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt2->execute();
    $product2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($product2) {
        echo "✅ Producto encontrado SIN consoles JOIN<br>";
        echo "<pre>";
        print_r($product2);
        echo "</pre>";
    } else {
        echo "❌ No se encontró producto<br>";
    }
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

// Verificar si existe tabla consoles
echo "<h2>Verificar tabla consoles:</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'consoles'")->fetchAll();
    if (count($tables) > 0) {
        echo "✅ Tabla 'consoles' existe<br>";
        
        // Mostrar estructura
        $cols = $pdo->query("DESCRIBE consoles")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Estructura de consoles:</h3>";
        echo "<pre>";
        print_r($cols);
        echo "</pre>";
    } else {
        echo "❌ Tabla 'consoles' NO existe<br>";
    }
} catch (PDOException $e) {
    echo "❌ ERROR al verificar consoles: " . $e->getMessage() . "<br>";
}
?>
