<?php
// Configurar sesión antes que nada
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Iniciar sesión
session_start();

// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log para debugging
error_log("=== BULK UPDATE STATUS DEBUG ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));
error_log("Cookie data: " . print_r($_COOKIE, true));

// Configurar headers JSON
header('Content-Type: application/json');

/**
 * Verificar si el usuario tiene un permiso específico
 */
function hasPermission($module, $action) {
    if (!isset($_SESSION['user_id'])) {
        error_log("No user_id in session");
        return false;
    }
    
    if (!isset($_SESSION['role'])) {
        error_log("No role in session");
        return false;
    }
    
    // Admin tiene todos los permisos
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Verificar permisos específicos
    if (isset($_SESSION['permissions'][$module])) {
        return in_array($action, $_SESSION['permissions'][$module]);
    }
    
    return false;
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        error_log("No CSRF token in session");
        return false;
    }
    
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    error_log("CSRF token valid: " . ($isValid ? 'yes' : 'no'));
    return $isValid;
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    error_log("Authentication failed - No user_id in session");
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'No autenticado',
        'debug' => 'No user_id in session'
    ]);
    exit;
}

// Verificar permisos
if (!hasPermission('products', 'edit')) {
    error_log("Permission denied for user_id: " . $_SESSION['user_id']);
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'No tienes permisos para editar productos',
        'debug' => 'Permission denied'
    ]);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Verificar CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    error_log("CSRF token verification failed");
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Token de seguridad inválido',
        'debug' => 'CSRF validation failed'
    ]);
    exit;
}

// Obtener IDs de productos y nuevo estado
$product_ids = $_POST['product_ids'] ?? '';
$new_status = $_POST['status'] ?? '';

error_log("Product IDs: " . $product_ids);
error_log("New Status: " . $new_status);

// Validar datos
if (empty($product_ids)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'No se especificaron productos'
    ]);
    exit;
}

// Validar estado
$valid_statuses = ['active', 'inactive', 'draft'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Estado inválido'
    ]);
    exit;
}

// Convertir IDs a array
$ids_array = explode(',', $product_ids);
$ids_array = array_map('intval', $ids_array);
$ids_array = array_filter($ids_array);

if (empty($ids_array)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'IDs de productos inválidos'
    ]);
    exit;
}

error_log("Processing " . count($ids_array) . " products");

try {
    // Incluir el archivo de conexión que crea $pdo
    require_once '../../config/database.php';
    
    // Crear placeholders para la consulta
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));
    
    // Preparar la consulta
    $query = "UPDATE products SET status = ? WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    
    // Crear array de parámetros (estado + IDs)
    $params = array_merge([$new_status], $ids_array);
    
    error_log("Query: " . $query);
    error_log("Params: " . print_r($params, true));
    
    // Ejecutar actualización
    $stmt->execute($params);
    $affected = $stmt->rowCount();
    
    error_log("Affected rows: " . $affected);
    
    // Traducir estados para el mensaje
    $status_names = [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'draft' => 'Borrador'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => "$affected producto(s) cambiado(s) a estado: " . $status_names[$new_status],
        'affected' => $affected
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar productos: ' . $e->getMessage(),
        'debug' => $e->getMessage()
    ]);
}
