<?php
/**
 * Middleware de autenticación para panel de administración
 * Verifica que el usuario esté logueado y tenga rol de ADMINISTRADOR
 * Sistema simplificado usando UserManagerSimple
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/user_manager_simple.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php?error=access_denied');
    exit;
}

// Crear instancia del UserManagerSimple
$userManager = new UserManagerSimple($pdo);

// Obtener información actualizada del usuario
try {
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, phone, birth_date, 
               role, is_active, email_verified, created_at
        FROM users 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: login.php?error=account_inactive');
        exit;
    }
    
    // VERIFICACIÓN CRÍTICA: Solo administradores pueden acceder
    if ($user['role'] !== 'administrador') {
        // Log del intento de acceso no autorizado
        error_log("ACCESO NO AUTORIZADO AL ADMIN - Usuario: " . $user['email'] . 
                 " - Rol: " . $user['role'] . 
                 " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . 
                 " - Fecha: " . date('Y-m-d H:i:s'));
        
        // Destruir sesión por seguridad
        session_destroy();
        
        // Redirigir con mensaje de error
        header('Location: login.php?error=insufficient_permissions');
        exit;
    }
    
    // Actualizar variables de sesión
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
} catch (PDOException $e) {
    error_log("Error en auth.php: " . $e->getMessage());
    session_destroy();
    header('Location: login.php?error=database_error');
    exit;
}

// Función para verificar permisos específicos
function hasPermission($resource, $action) {
    // En el sistema simplificado, los administradores tienen todos los permisos
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        return true;
    }
    return false;
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generar token CSRF para esta sesión
$csrf_token = generateCSRFToken();

// Información del usuario actual para usar en templates
$current_user = $user;
$current_user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
?>