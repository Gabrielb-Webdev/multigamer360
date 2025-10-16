<?php
/**
 * Verificar estructura de la tabla user_favorites (wishlist)
 */

require_once 'config/database.php';

echo "<h2>Verificación de tabla user_favorites (Wishlist)</h2>";

// Test 1: Verificar si existe la tabla
echo "<h3>Test 1: ¿Existe la tabla user_favorites?</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_favorites'");
    $table_exists = $stmt->fetch();
    if ($table_exists) {
        echo "✅ La tabla user_favorites existe<br>";
    } else {
        echo "❌ La tabla user_favorites NO existe<br>";
        echo "<p><strong>Necesitas crear la tabla. SQL sugerido:</strong></p>";
        echo "<pre>";
        echo "CREATE TABLE user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        echo "</pre>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<br>";

// Test 2: Ver estructura de la tabla
echo "<h3>Test 2: Estructura de la tabla</h3>";
try {
    $stmt = $pdo->query("DESCRIBE user_favorites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 3: Ver índices
echo "<h3>Test 3: Índices de la tabla</h3>";
try {
    $stmt = $pdo->query("SHOW INDEX FROM user_favorites");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($indexes) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Nombre</th><th>Columna</th><th>Único</th><th>Tipo</th></tr>";
        foreach ($indexes as $idx) {
            echo "<tr>";
            echo "<td>{$idx['Key_name']}</td>";
            echo "<td>{$idx['Column_name']}</td>";
            echo "<td>" . ($idx['Non_unique'] == 0 ? 'SÍ' : 'NO') . "</td>";
            echo "<td>{$idx['Index_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No hay índices definidos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 4: Contar registros
echo "<h3>Test 4: Estadísticas de uso</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_favorites");
    $total = $stmt->fetchColumn();
    echo "📊 Total de productos en wishlists: <strong>{$total}</strong><br>";
    
    if ($total > 0) {
        // Usuarios con más productos en wishlist
        $stmt = $pdo->query("
            SELECT user_id, COUNT(*) as items 
            FROM user_favorites 
            GROUP BY user_id 
            ORDER BY items DESC 
            LIMIT 5
        ");
        $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<br><strong>Top 5 usuarios con más items en wishlist:</strong><br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>User ID</th><th>Items en Wishlist</th></tr>";
        foreach ($top_users as $user) {
            echo "<tr><td>{$user['user_id']}</td><td>{$user['items']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 5: Verificar integridad referencial
echo "<h3>Test 5: Verificar integridad referencial</h3>";
try {
    // Productos en wishlist que ya no existen
    $stmt = $pdo->query("
        SELECT uf.product_id, COUNT(*) as count
        FROM user_favorites uf
        LEFT JOIN products p ON uf.product_id = p.id
        WHERE p.id IS NULL
        GROUP BY uf.product_id
    ");
    $orphan_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphan_products) > 0) {
        echo "⚠️ Hay productos en wishlists que ya no existen:<br>";
        foreach ($orphan_products as $orphan) {
            echo "- Product ID {$orphan['product_id']}: {$orphan['count']} referencias<br>";
        }
    } else {
        echo "✅ Todos los productos en wishlists existen en la BD<br>";
    }
    
    // Usuarios en wishlist que ya no existen
    $stmt = $pdo->query("
        SELECT uf.user_id, COUNT(*) as count
        FROM user_favorites uf
        LEFT JOIN users u ON uf.user_id = u.id
        WHERE u.id IS NULL
        GROUP BY uf.user_id
    ");
    $orphan_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphan_users) > 0) {
        echo "⚠️ Hay usuarios en wishlists que ya no existen:<br>";
        foreach ($orphan_users as $orphan) {
            echo "- User ID {$orphan['user_id']}: {$orphan['count']} referencias<br>";
        }
    } else {
        echo "✅ Todos los usuarios en wishlists existen en la BD<br>";
    }
} catch (Exception $e) {
    echo "❌ Error verificando integridad: " . $e->getMessage() . "<br>";
}

echo "<br><br>";
echo "<h3>✅ Resumen</h3>";
echo "<p>La tabla <strong>user_favorites</strong> está funcionando y almacenando las wishlists en la base de datos.</p>";
echo "<p><strong>Ventajas:</strong></p>";
echo "<ul>";
echo "<li>✅ Persistencia entre sesiones</li>";
echo "<li>✅ Sincronización entre dispositivos</li>";
echo "<li>✅ No se pierde al cerrar el navegador</li>";
echo "<li>✅ Permite analytics y recomendaciones</li>";
echo "</ul>";

echo "<br>";
echo "<a href='wishlist.php'>Ver mi Wishlist</a> | ";
echo "<a href='productos.php'>Ver Productos</a>";
?>
