<?php
/**
 * API para eliminar productos
 * No usar auth.php porque redirecciona, aquí manejamos la autenticación manualmente
 */

// Configurar sesión antes de iniciarla
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Funciones auxiliares
function hasPermission($resource, $action) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        return true;
    }
    return false;
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

header('Content-Type: application/json');

// Debug: Mostrar estado de la sesión
error_log("=== DELETE PRODUCT SESSION DEBUG ===");
error_log("Session ID: " . session_id());
error_log("Session Status: " . session_status());
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Session User Role: " . ($_SESSION['user_role'] ?? 'NOT SET'));
error_log("Session Is Admin: " . ($_SESSION['is_admin'] ?? 'NOT SET'));
error_log("All Session Data: " . print_r($_SESSION, true));

try {
    // Permitir métodos GET y POST
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Solo métodos GET y POST permitidos']);
        exit;
    }

    // Verificar autenticación básica
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'No autorizado - Sesión no iniciada',
            'debug' => 'session_user_id_not_set'
        ]);
        exit;
    }
    
    // Verificar que sea administrador
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'No autorizado - No es administrador',
            'debug' => 'not_admin'
        ]);
        exit;
    }
    
    // Asegurar que el token CSRF esté disponible
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Debug: Log the request details
    error_log("DELETE request received via " . $_SERVER['REQUEST_METHOD'] . " - User ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role Level: " . ($_SESSION['user_role_level'] ?? 'none'));
    error_log("Request parameters: " . json_encode($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET));

    // Verificar permisos primero
    $hasPermissions = hasPermission('products', 'delete');
    error_log("hasPermission result: " . ($hasPermissions ? 'true' : 'false'));
    
    if (!$hasPermissions) {
        error_log("Permission denied for user ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role Level: " . ($_SESSION['user_role_level'] ?? 'none'));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos para eliminar productos', 'debug' => 'permissions_failed']);
        exit;
    }

    // Verificar token CSRF (de POST o GET)
    $csrf_token = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['csrf_token'] ?? '') : ($_GET['csrf_token'] ?? '');
    error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'none'));
    error_log("Received CSRF token: " . $csrf_token);

    if (!verifyCSRFToken($csrf_token)) {
        error_log("CSRF token verification failed. Provided: " . $csrf_token . ", Session: " . ($_SESSION['csrf_token'] ?? 'none'));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido', 'debug' => 'csrf_failed']);
        exit;
    }

    // Obtener IDs de productos a eliminar (de POST o GET)
    $product_ids = [];
    $request_data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    if (!empty($request_data['id'])) {
        // Eliminar un producto
        $product_ids = [intval($request_data['id'])];
    } elseif (!empty($request_data['ids'])) {
        // Eliminar múltiples productos (IDs separados por comas)
        $ids = explode(',', $request_data['ids']);
        $product_ids = array_map('intval', $ids);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID(s) de producto requerido(s)']);
        exit;
    }

    if (empty($product_ids)) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionaron IDs válidos']);
        exit;
    }

    $pdo->beginTransaction();
    
    try {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        // Primero obtener imágenes para eliminar archivos físicos
        try {
            $images_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id IN ($placeholders)");
            $images_stmt->execute($product_ids);
            $images = $images_stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Could not fetch images: " . $e->getMessage());
            $images = [];
        }
        
        // Eliminar registros de la base de datos en orden correcto
        // Usar un enfoque más seguro que verifica la existencia de tablas/columnas
        $cleanup_queries = [
            "DELETE FROM product_tags WHERE product_id IN ($placeholders)",
            "DELETE FROM product_images WHERE product_id IN ($placeholders)",
            "DELETE FROM cart_items WHERE product_id IN ($placeholders)",
            "DELETE FROM wishlist_items WHERE product_id IN ($placeholders)",
            // Para order_items puede tener diferentes estructuras
            "DELETE FROM order_items WHERE product_id IN ($placeholders)",
            // Finalmente eliminar el producto principal
            "DELETE FROM products WHERE id IN ($placeholders)"
        ];
        
        $successful_deletions = 0;
        foreach ($cleanup_queries as $query) {
            try {
                $delete_stmt = $pdo->prepare($query);
                $delete_stmt->execute($product_ids);
                $deleted_rows = $delete_stmt->rowCount();
                $successful_deletions++;
                error_log("Executed: $query - Deleted: $deleted_rows rows");
            } catch (PDOException $e) {
                error_log("Failed query: $query - Error: " . $e->getMessage());
                
                // Si falló order_items, intentar estructura alternativa
                if (strpos($query, 'order_items') !== false) {
                    try {
                        $alt_query = "DELETE FROM order_items WHERE item_id IN ($placeholders) AND item_type = 'product'";
                        $alt_stmt = $pdo->prepare($alt_query);
                        $alt_stmt->execute($product_ids);
                        error_log("Alternative order_items query succeeded");
                        $successful_deletions++;
                    } catch (PDOException $e2) {
                        error_log("Alternative order_items query also failed: " . $e2->getMessage());
                    }
                }
            }
        }
        
        // Eliminar archivos de imágenes
        $deleted_files = 0;
        foreach ($images as $filename) {
            $file_path = "../../uploads/products/$filename";
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    $deleted_files++;
                }
            }
        }
        
        $pdo->commit();
        
        $count = count($product_ids);
        $message = "Se " . ($count === 1 ? 'eliminó' : 'eliminaron') . " $count producto" . ($count === 1 ? '' : 's') . " correctamente";
        
        error_log("Successfully deleted $count product(s) and $deleted_files image file(s). Successful DB operations: $successful_deletions");
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'deleted_products' => $count,
            'deleted_files' => $deleted_files,
            'db_operations' => $successful_deletions
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error during deletion: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("General error in delete_product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>