<?php
/**
 * Diagnóstico de duplicados en wishlist
 */

require_once 'config/session_config.php';
initSecureSession();

require_once 'includes/auth.php';
if (!isLoggedIn()) {
    echo "Debes iniciar sesión primero";
    exit;
}

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

echo "<h2>Diagnóstico de Wishlist - Usuario ID: {$user_id}</h2>";

// Ver todos los registros en user_favorites para este usuario
echo "<h3>Registros en user_favorites:</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM user_favorites WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de registros: <strong>" . count($favorites) . "</strong></p>";
    
    if (count($favorites) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Product ID</th><th>Fecha Agregado</th><th>Acciones</th></tr>";
        foreach ($favorites as $fav) {
            echo "<tr>";
            echo "<td>{$fav['id']}</td>";
            echo "<td>{$fav['product_id']}</td>";
            echo "<td>{$fav['created_at']}</td>";
            echo "<td><a href='?delete={$fav['id']}'>Eliminar</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br>";

// Ver productos con JOIN
echo "<h3>Productos en wishlist (con JOIN):</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT uf.id as favorite_id, uf.product_id, uf.created_at,
               p.id, p.name, p.price_pesos as price
        FROM user_favorites uf
        JOIN products p ON uf.product_id = p.id
        WHERE uf.user_id = ? AND p.is_active = 1
        ORDER BY uf.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de productos: <strong>" . count($products) . "</strong></p>";
    
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Favorite ID</th><th>Product ID</th><th>Nombre</th><th>Precio</th><th>Fecha</th></tr>";
        foreach ($products as $prod) {
            echo "<tr>";
            echo "<td>{$prod['favorite_id']}</td>";
            echo "<td>{$prod['product_id']}</td>";
            echo "<td>{$prod['name']}</td>";
            echo "<td>\${$prod['price']}</td>";
            echo "<td>{$prod['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br>";

// Buscar duplicados
echo "<h3>Duplicados (mismo product_id):</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT product_id, COUNT(*) as count
        FROM user_favorites
        WHERE user_id = ?
        GROUP BY product_id
        HAVING count > 1
    ");
    $stmt->execute([$user_id]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "⚠️ Se encontraron duplicados:<br>";
        foreach ($duplicates as $dup) {
            echo "- Product ID {$dup['product_id']}: {$dup['count']} veces<br>";
        }
        echo "<br>";
        echo "<a href='?clean_duplicates=1' style='padding: 10px; background: red; color: white; text-decoration: none;'>Limpiar Duplicados</a>";
    } else {
        echo "✅ No hay duplicados";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Procesar eliminación
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE id = ? AND user_id = ?");
        $stmt->execute([$delete_id, $user_id]);
        echo "<script>alert('Registro eliminado'); window.location.href='check_wishlist_duplicates.php';</script>";
    } catch (Exception $e) {
        echo "Error eliminando: " . $e->getMessage();
    }
}

// Procesar limpieza de duplicados
if (isset($_GET['clean_duplicates'])) {
    try {
        // Mantener solo el registro más reciente de cada product_id
        $pdo->exec("
            DELETE uf1 FROM user_favorites uf1
            INNER JOIN user_favorites uf2 
            WHERE uf1.user_id = {$user_id}
            AND uf2.user_id = {$user_id}
            AND uf1.product_id = uf2.product_id
            AND uf1.created_at < uf2.created_at
        ");
        echo "<script>alert('Duplicados eliminados'); window.location.href='check_wishlist_duplicates.php';</script>";
    } catch (Exception $e) {
        echo "Error limpiando duplicados: " . $e->getMessage();
    }
}

echo "<br><br>";
echo "<a href='wishlist.php'>← Volver a Wishlist</a>";
?>
