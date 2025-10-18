<?php
/**
 * SCRIPT DE DIAGNÓSTICO WISHLIST
 * Sube este archivo a Hostinger y accede a: /debug-wishlist.php?product_id=2
 */

session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 2;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Wishlist - MultiGamer360</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff88;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #dc262b; }
        h2 { color: #ffc107; margin-top: 30px; }
        .success { color: #00ff88; }
        .error { color: #dc262b; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            border-left: 4px solid #dc262b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background: #dc262b;
            color: white;
        }
        tr:hover {
            background: #2a2a2a;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .badge-yes { background: #28a745; color: white; }
        .badge-no { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <h1>🔍 DIAGNÓSTICO WISHLIST - MultiGamer360</h1>
    <p>Producto ID: <strong><?php echo $product_id; ?></strong></p>

    <h2>1️⃣ Verificación de Sesión</h2>
    <?php
    echo "<table>";
    echo "<tr><th>Variable</th><th>Valor</th><th>Estado</th></tr>";
    
    $sessionActive = session_status() === PHP_SESSION_ACTIVE;
    echo "<tr>";
    echo "<td><code>session_status()</code></td>";
    echo "<td>" . ($sessionActive ? "PHP_SESSION_ACTIVE" : "NO ACTIVE") . "</td>";
    echo "<td><span class='badge " . ($sessionActive ? "badge-yes" : "badge-no") . "'>" . ($sessionActive ? "✅ OK" : "❌ FAIL") . "</span></td>";
    echo "</tr>";
    
    $isLogged = isLoggedIn();
    echo "<tr>";
    echo "<td><code>isLoggedIn()</code></td>";
    echo "<td>" . ($isLogged ? "TRUE" : "FALSE") . "</td>";
    echo "<td><span class='badge " . ($isLogged ? "badge-yes" : "badge-no") . "'>" . ($isLogged ? "✅ OK" : "❌ FAIL") . "</span></td>";
    echo "</tr>";
    
    $hasUserId = isset($_SESSION['user_id']);
    echo "<tr>";
    echo "<td><code>\$_SESSION['user_id']</code></td>";
    echo "<td>" . ($hasUserId ? $_SESSION['user_id'] : "NO EXISTE") . "</td>";
    echo "<td><span class='badge " . ($hasUserId ? "badge-yes" : "badge-no") . "'>" . ($hasUserId ? "✅ OK" : "❌ FAIL") . "</span></td>";
    echo "</tr>";
    
    $hasUsername = isset($_SESSION['username']);
    echo "<tr>";
    echo "<td><code>\$_SESSION['username']</code></td>";
    echo "<td>" . ($hasUsername ? $_SESSION['username'] : "NO EXISTE") . "</td>";
    echo "<td><span class='badge " . ($hasUsername ? "badge-yes" : "badge-no") . "'>" . ($hasUsername ? "✅ OK" : "❌ FAIL") . "</span></td>";
    echo "</tr>";
    
    echo "</table>";
    
    echo "<h3 class='info'>📋 Todas las variables de sesión:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    ?>

    <h2>2️⃣ Verificación de Base de Datos - Tabla user_favorites</h2>
    <?php
    if ($isLogged && $hasUserId) {
        $user_id = $_SESSION['user_id'];
        
        echo "<p class='success'>✅ Usuario logueado con ID: <strong>$user_id</strong></p>";
        
        // Consultar user_favorites para este usuario
        try {
            $stmt = $pdo->prepare("SELECT * FROM user_favorites WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $allFavorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Todos los favoritos del usuario (user_id = $user_id):</h3>";
            
            if (count($allFavorites) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>User ID</th><th>Product ID</th><th>Fecha</th><th>¿Es producto actual?</th></tr>";
                foreach ($allFavorites as $fav) {
                    $isCurrent = ($fav['product_id'] == $product_id);
                    echo "<tr" . ($isCurrent ? " style='background:#1a4d1a;'" : "") . ">";
                    echo "<td>" . $fav['id'] . "</td>";
                    echo "<td>" . $fav['user_id'] . "</td>";
                    echo "<td>" . $fav['product_id'] . "</td>";
                    echo "<td>" . ($fav['created_at'] ?? 'N/A') . "</td>";
                    echo "<td><span class='badge " . ($isCurrent ? "badge-yes" : "badge-no") . "'>" . ($isCurrent ? "✅ SÍ" : "❌ NO") . "</span></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ Este usuario NO tiene productos en wishlist</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error al consultar user_favorites: " . $e->getMessage() . "</p>";
        }
        
        // Consultar específicamente el producto actual
        echo "<h3>¿Este producto ($product_id) está en wishlist?</h3>";
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $count = $stmt->fetchColumn();
            
            echo "<table>";
            echo "<tr><th>Consulta SQL</th><th>Resultado</th><th>Estado</th></tr>";
            echo "<tr>";
            echo "<td><code>SELECT COUNT(*) FROM user_favorites WHERE user_id = $user_id AND product_id = $product_id</code></td>";
            echo "<td><strong>$count</strong> registro(s)</td>";
            echo "<td><span class='badge " . ($count > 0 ? "badge-yes" : "badge-no") . "'>" . ($count > 0 ? "✅ SÍ está en wishlist" : "❌ NO está en wishlist") . "</span></td>";
            echo "</tr>";
            echo "</table>";
            
            $isInWishlist = $count > 0;
            
            echo "<h3>Resultado final:</h3>";
            echo "<p style='font-size: 1.5em;'>";
            if ($isInWishlist) {
                echo "<span class='success'>✅ \$isInWishlist = TRUE</span><br>";
                echo "<span class='success'>✅ El botón DEBERÍA mostrarse como ACTIVO (corazón rojo lleno)</span>";
            } else {
                echo "<span class='error'>❌ \$isInWishlist = FALSE</span><br>";
                echo "<span class='error'>❌ El botón DEBERÍA mostrarse como INACTIVO (corazón blanco vacío)</span>";
            }
            echo "</p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error en consulta específica: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Usuario NO está logueado. No se puede verificar wishlist.</p>";
    }
    ?>

    <h2>3️⃣ Verificación del Producto</h2>
    <?php
    try {
        $stmt = $pdo->prepare("SELECT id, name, price_pesos, is_active FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            foreach ($product as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Producto con ID $product_id NO encontrado</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error al consultar producto: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>4️⃣ Tipos de Datos</h2>
    <?php
    if ($isLogged && $hasUserId) {
        echo "<table>";
        echo "<tr><th>Variable</th><th>Valor</th><th>Tipo</th></tr>";
        
        $sessionUserId = $_SESSION['user_id'];
        echo "<tr>";
        echo "<td><code>\$_SESSION['user_id']</code></td>";
        echo "<td>$sessionUserId</td>";
        echo "<td>" . gettype($sessionUserId) . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><code>\$product_id</code></td>";
        echo "<td>$product_id</td>";
        echo "<td>" . gettype($product_id) . "</td>";
        echo "</tr>";
        
        echo "</table>";
        
        echo "<p class='info'>ℹ️ Si user_id es 'string' y la BD espera 'integer', puede haber problemas de comparación.</p>";
    }
    ?>

    <h2>5️⃣ Recomendaciones</h2>
    <pre>
<?php
if (!$isLogged) {
    echo "❌ PROBLEMA: Usuario no está logueado\n";
    echo "   SOLUCIÓN: Inicia sesión en /login.php\n\n";
}

if ($isLogged && $hasUserId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "✅ El producto SÍ está en la wishlist\n";
            echo "   Si el botón aparece vacío después de F5:\n";
            echo "   1. Verifica que product-details.php tenga el código de verificación actualizado\n";
            echo "   2. Limpia caché del navegador (Ctrl+Shift+Del)\n";
            echo "   3. Verifica que no haya JavaScript modificando el botón después de cargar\n\n";
        } else {
            echo "ℹ️ El producto NO está en la wishlist actualmente\n";
            echo "   Agrega el producto desde: /product-details.php?id=$product_id\n\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "📝 Pasos siguientes:\n";
echo "   1. Agrega el producto a wishlist\n";
echo "   2. Recarga ESTA página (F5)\n";
echo "   3. Verifica que 'COUNT(*)' sea 1 o mayor\n";
echo "   4. Ve a /product-details.php?id=$product_id\n";
echo "   5. Verifica que el comentario HTML tenga isInWishlist = TRUE\n";
?>
    </pre>

    <hr style="margin: 40px 0; border-color: #444;">
    <p style="text-align: center; opacity: 0.7;">
        <a href="product-details.php?id=<?php echo $product_id; ?>" style="color: #00ff88;">← Volver a Product Details</a> |
        <a href="wishlist.php" style="color: #00ff88;">Ver Wishlist →</a>
    </p>
</body>
</html>
