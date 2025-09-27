<?php
/**
 * Middleware de autenticación para panel de administración
 * Verifica que el usuario esté logueado y tenga permisos de administrador
 */

session_start();
require_once 'security_config.php'; // Configuración de seguridad centralizada
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/user_manager.php';
require_once 'security_check.php'; // Verificaciones adicionales de seguridad
require_once 'access_control.php'; // Control de acceso directo y navegación

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Crear instancia del UserManager
$userManager = new UserManager($pdo);

// Verificar que el usuario tenga permisos de administrador (nivel >= 60)
$user = $userManager->getUserById($_SESSION['user_id']);

if (!$user || !$user['is_active']) {
    session_destroy();
    header('Location: login.php?error=account_inactive');
    exit;
}

// Verificar nivel de rol usando configuración centralizada
$user_level = $user['role_level'] ?? 0;
$user_role = $user['role'] ?? 'customer';

// Debug: Log user data to see what we're getting
error_log("User data from database: " . json_encode($user));
error_log("Extracted user_level: " . $user_level);
error_log("Extracted user_role: " . $user_role);

// Fix: If role is Super Administrador but level is 0, set it to 100
if (($user['role_name'] === 'Super Administrador' || $user_role === 'Super Administrador') && $user_level == 0) {
    $user_level = 100;
    error_log("Fixed user_level for Super Administrador: " . $user_level);
}

// Asegurar que el role level esté en la sesión
$_SESSION['user_role_level'] = $user_level;
$_SESSION['user_role'] = $user_role;

// SEGURIDAD CRÍTICA: Solo usuarios con rol de administrador pueden acceder
// Usar configuración centralizada para niveles mínimos
$admin_roles = ['admin', 'moderator', 'SuperAdmin', 'Admin'];

// Permitir acceso SOLO si el nivel es >= 80 Y tiene rol de admin
$has_admin_access = AdminSecurityConfig::isValidAdminLevel($user_level) && in_array($user_role, $admin_roles);

if (!$has_admin_access) {
    // Log del intento de acceso no autorizado usando función centralizada
    logSecurityIncident('Unauthorized admin access attempt', [
        'user_id' => $_SESSION['user_id'],
        'email' => $user['email'] ?? 'unknown',
        'role' => $user_role,
        'level' => $user_level,
        'required_level' => AdminSecurityConfig::MINIMUM_ADMIN_LEVEL
    ]);
    
    // Destruir sesión por seguridad
    session_destroy();
    
    // Redirigir con mensaje de error específico
    header('Location: login.php?error=admin_access_denied');
    exit;
}

// Función para verificar permisos específicos
function hasPermission($resource, $action) {
    global $user, $userManager, $pdo;
    
    // Si no hay usuario en la variable global, intentar obtenerlo de la sesión
    if (!$user && isset($_SESSION['user_id'])) {
        if (!$userManager) {
            $userManager = new UserManager($pdo);
        }
        $user = $userManager->getUserById($_SESSION['user_id']);
    }
    
    if (!$user || !$user['is_active']) {
        error_log("hasPermission: User not found or inactive");
        return false;
    }
    
    // Super admin tiene todos los permisos
    $user_level = $user['role_level'] ?? ($_SESSION['user_role_level'] ?? 0);
    $user_role = $user['role'] ?? ($_SESSION['user_role'] ?? '');
    
    error_log("hasPermission check: resource=$resource, action=$action, user_level=$user_level, user_role=$user_role");
    
    if ($user_level >= 100) {
        error_log("hasPermission: Super admin access granted");
        return true;
    }
    
    // Admin tiene la mayoría de permisos
    if ($user_level >= 80 || $user_role === 'admin') {
        $admin_permissions = [
            'users' => ['read', 'update'],
            'products' => ['create', 'read', 'update', 'delete'],
            'orders' => ['read', 'update'],
            'categories' => ['create', 'read', 'update', 'delete'],
            'brands' => ['create', 'read', 'update', 'delete'],
            'reports' => ['read']
        ];
        
        $has_permission = isset($admin_permissions[$resource]) && in_array($action, $admin_permissions[$resource]);
        error_log("hasPermission: Admin check result = " . ($has_permission ? 'true' : 'false'));
        return $has_permission;
    }
    
    // Verificar permisos específicos del rol
    if ($user['permissions'] && is_array($user['permissions'])) {
        $has_permission = isset($user['permissions'][$resource]) && in_array($action, $user['permissions'][$resource]);
        error_log("hasPermission: Role permissions check result = " . ($has_permission ? 'true' : 'false'));
        return $has_permission;
    }
    
    error_log("hasPermission: No permissions found, denying access");
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