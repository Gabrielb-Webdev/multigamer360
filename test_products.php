<?php
/**
 * Script de prueba para verificar productos
 */

// Activar errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Test Productos</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#333;color:white;}</style>";
echo "</head><body>";

echo "<h1>Verificación de Productos</h1>";
echo "<hr>";

try {
    // 1. Verificar conexión a BD
    echo "<h2>✅ Conexión a Base de Datos</h2>";
    echo "<p>Conectado correctamente</p>";
    
    // 2. Contar productos totales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $result = $stmt->fetch();
    echo "<h2>📊 Total de Productos en BD: " . $result['total'] . "</h2>";
    
    // 3. Productos activos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "<p>Productos activos (is_active=1): <strong>" . $result['total'] . "</strong></p>";
    
    // 4. Productos con stock
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1 AND stock_quantity > 0");
    $result = $stmt->fetch();
    echo "<p>Productos activos con stock: <strong>" . $result['total'] . "</strong></p>";
    
    // 5. Verificar columnas críticas
    echo "<h2>🔍 Verificación de Columnas</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $critical_columns = ['console_id', 'price', 'is_active', 'stock_quantity'];
    foreach ($critical_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p>✅ Columna '$col' existe</p>";
        } else {
            echo "<p>❌ Columna '$col' NO existe</p>";
        }
    }
    
    // 6. Mostrar primeros 5 productos
    echo "<h2>📦 Primeros 5 Productos</h2>";
    $stmt = $pdo->query("SELECT id, name, price, stock_quantity, is_active, console_id, category_id, brand_id 
                         FROM products 
                         LIMIT 5");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Nombre</th>";
        echo "<th>Precio</th>";
        echo "<th>Stock</th>";
        echo "<th>Activo</th>";
        echo "<th>Console ID</th>";
        echo "<th>Category ID</th>";
        echo "<th>Brand ID</th>";
        echo "</tr>";
        
        foreach ($products as $prod) {
            echo "<tr>";
            echo "<td>" . $prod['id'] . "</td>";
            echo "<td>" . htmlspecialchars($prod['name']) . "</td>";
            echo "<td>$" . $prod['price'] . "</td>";
            echo "<td>" . $prod['stock_quantity'] . "</td>";
            echo "<td>" . ($prod['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($prod['console_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($prod['category_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($prod['brand_id'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No hay productos en la base de datos</p>";
    }
    
    // 7. Verificar tabla consoles
    echo "<h2>🎮 Tabla Consoles</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM consoles");
        $result = $stmt->fetch();
        echo "<p>Total consolas: <strong>" . $result['total'] . "</strong></p>";
        
        if ($result['total'] > 0) {
            $stmt = $pdo->query("SELECT id, name FROM consoles LIMIT 10");
            $consoles = $stmt->fetchAll();
            echo "<ul>";
            foreach ($consoles as $console) {
                echo "<li>ID: {$console['id']} - {$console['name']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p>❌ Tabla consoles no existe o error: " . $e->getMessage() . "</p>";
    }
    
    // 8. Verificar tabla genres
    echo "<h2>🎯 Tabla Genres</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM genres");
        $result = $stmt->fetch();
        echo "<p>Total géneros: <strong>" . $result['total'] . "</strong></p>";
    } catch (PDOException $e) {
        echo "<p>❌ Tabla genres no existe o error: " . $e->getMessage() . "</p>";
    }
    
    // 9. Probar query de productos.php
    echo "<h2>🧪 Test Query de productos.php</h2>";
    $sql = "SELECT p.*, 
                   c.name as category_name, 
                   b.name as brand_name,
                   co.name as console_name, 
                   co.slug as console_slug,
                   pi.image_url as primary_image,
                   p.created_at as publication_date
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN consoles co ON p.console_id = co.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1
            ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC
            LIMIT 5";
    
    try {
        $stmt = $pdo->query($sql);
        $testProducts = $stmt->fetchAll();
        echo "<p>✅ Query ejecutado correctamente. Productos obtenidos: <strong>" . count($testProducts) . "</strong></p>";
        
        if (count($testProducts) > 0) {
            echo "<h3>Productos obtenidos:</h3>";
            foreach ($testProducts as $prod) {
                echo "<p>• ID: {$prod['id']} - {$prod['name']} - ${$prod['price']}</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p>❌ Error en query: " . $e->getMessage() . "</p>";
    }
    
    // 10. Resumen
    echo "<hr>";
    echo "<h2>📝 Resumen</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $activeCount = $stmt->fetch()['total'];
    
    if ($activeCount > 0) {
        echo "<p style='color: green; font-size: 18px;'>✅ Hay {$activeCount} productos activos en la base de datos</p>";
        echo "<p><a href='productos.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir a Productos</a></p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ No hay productos activos. Necesitas activar productos desde el panel de administración.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ ERROR</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR GENERAL</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
