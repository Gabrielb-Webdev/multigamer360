<?php
/**
 * SCRIPT DE LIMPIEZA DE CACHÉ Y VERIFICACIÓN
 * Ejecuta este script para limpiar caché y verificar el estado actual
 */

// Limpiar OPcache si está habilitado
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache limpiado<br>";
} else {
    echo "ℹ️ OPcache no está habilitado<br>";
}

// Limpiar cualquier output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Iniciar sesión
session_start();

echo "<h1>🧹 Limpieza y Verificación del Sistema</h1>";
echo "<pre>";

echo "\n=== 1. Estado de la Sesión ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User Logged In: " . (isset($_SESSION['user_id']) ? "YES (ID: {$_SESSION['user_id']})" : "NO") . "\n";

if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    
    echo "\n=== 2. Verificando Archivos PHP ===\n";
    $files = [
        'product-details.php',
        'product-details-temp.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $modified = filemtime($file);
            $date = date('Y-m-d H:i:s', $modified);
            echo "✅ $file - Última modificación: $date\n";
            
            // Buscar si tiene la nueva estructura if-else
            $content = file_get_contents($file);
            if (strpos($content, '<!-- PRODUCTO EN WISHLIST - Botón ACTIVO -->') !== false) {
                echo "   ✅ Tiene el código NUEVO (if-else explícito)\n";
            } else if (strpos($content, '$heartClass = $isInWishlist') !== false) {
                echo "   ⚠️ Tiene el código VIEJO (ternario con variables)\n";
                echo "   ❌ NECESITAS ACTUALIZAR ESTE ARCHIVO\n";
            } else {
                echo "   ❓ Estructura desconocida\n";
            }
        } else {
            echo "❌ $file - NO EXISTE\n";
        }
    }
    
    echo "\n=== 3. Verificando Base de Datos ===\n";
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT product_id, created_at FROM user_favorites WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Productos en wishlist del usuario $user_id:\n";
        if (count($favorites) > 0) {
            foreach ($favorites as $fav) {
                echo "  - Product ID: {$fav['product_id']} (agregado: {$fav['created_at']})\n";
            }
        } else {
            echo "  (vacío)\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 4. Probando consulta específica para Producto ID 2 ===\n";
    $product_id = 2;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];
        
        $isInWishlist = $count > 0;
        
        echo "Usuario: $user_id\n";
        echo "Producto: $product_id\n";
        echo "Registros encontrados: $count\n";
        echo "¿En wishlist?: " . ($isInWishlist ? "SÍ ✅" : "NO ❌") . "\n";
        
        if ($isInWishlist) {
            echo "\nClases que DEBERÍAN generarse:\n";
            echo "  Botón: 'favorite-btn-detail btn-wishlist active'\n";
            echo "  Ícono: 'fas fa-heart'\n";
        } else {
            echo "\nClases que DEBERÍAN generarse:\n";
            echo "  Botón: 'favorite-btn-detail btn-wishlist'\n";
            echo "  Ícono: 'far fa-heart'\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "\n⚠️ No hay sesión activa. Inicia sesión primero.\n";
}

echo "\n=== 5. Información del Servidor ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Current Script: " . __FILE__ . "\n";

echo "\n=== 6. Recomendaciones ===\n";
echo "1. Sube product-details.php y product-details-temp.php actualizados a Hostinger\n";
echo "2. Limpia caché del navegador (Ctrl+Shift+Del)\n";
echo "3. Prueba en modo incógnito\n";
echo "4. Verifica que el código fuente HTML muestre '<!-- PRODUCTO EN WISHLIST -->'\n";
echo "5. Si sigue sin funcionar, verifica permisos de archivos en servidor\n";

echo "\n✅ Verificación completada\n";
echo "</pre>";

echo "<hr>";
echo "<p><a href='product-details.php?id=2'>← Probar Product Details (ID=2)</a></p>";
echo "<p><a href='debug-wishlist.php?product_id=2'>← Ver Debug Wishlist</a></p>";
?>
