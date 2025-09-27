<?php
// Sistema de autenticación y verificación de sesiones

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para obtener el usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Si ya tenemos la información completa en sesión, devolverla
    if (isset($_SESSION['user_complete_data'])) {
        return $_SESSION['user_complete_data'];
    }
    
    // Si no, obtener los datos completos de la base de datos
    global $pdo;
    if (!isset($pdo)) {
        // Incluir configuración de base de datos si no está disponible
        require_once __DIR__ . '/../config/database.php';
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            // Guardar en sesión para uso futuro
            $_SESSION['user_complete_data'] = $userData;
            return $userData;
        }
    } catch (Exception $e) {
        error_log("Error al obtener datos del usuario: " . $e->getMessage());
    }
    
    // Fallback a datos básicos de sesión
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'full_name' => ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''),
        'role' => $_SESSION['role_slug'] ?? 'customer',
        'phone' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'role_id' => $_SESSION['role_id'] ?? 5,
        'role_name' => $_SESSION['role_name'] ?? 'Cliente',
        'role_slug' => $_SESSION['role_slug'] ?? 'cliente',
        'role_level' => $_SESSION['role_level'] ?? 10,
        'permissions' => $_SESSION['permissions'] ?? []
    ];
}

// Función para verificar si el usuario tiene un rol específico
function hasRole($role_slug) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return ($_SESSION['role_slug'] ?? '') === $role_slug;
}

// Función para verificar si el usuario tiene un nivel de rol mínimo
function hasMinRoleLevel($min_level) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return ($_SESSION['role_level'] ?? 0) >= $min_level;
}

// Función para verificar si el usuario es admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return hasMinRoleLevel(80); // Admin level o superior
    }
}

// Función para verificar si el usuario es super admin
if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return hasMinRoleLevel(100); // Super Admin level
    }
}

// Función para requerir login (redirigir si no está logueado)
function requireLogin($redirect_to = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect_to);
        exit;
    }
}

// Función para requerir un rol específico
function requireRole($role_slug, $redirect_to = 'index.php') {
    if (!hasRole($role_slug)) {
        header('Location: ' . $redirect_to);
        exit;
    }
}

// Función para requerir nivel de rol mínimo
function requireMinRoleLevel($min_level, $redirect_to = 'index.php') {
    if (!hasMinRoleLevel($min_level)) {
        header('Location: ' . $redirect_to);
        exit;
    }
}

// Función para requerir admin
function requireAdmin($redirect_to = 'index.php') {
    requireMinRoleLevel(80, $redirect_to);
}

// Función para obtener un saludo personalizado
function getGreeting($user_hour = null) {
    if (!isLoggedIn()) {
        return 'Bienvenido';
    }
    
    // Si no se proporciona la hora del usuario, usar la hora del servidor como fallback
    if ($user_hour === null) {
        // Intentar obtener la hora del usuario desde la sesión o cookie
        $user_hour = $_SESSION['user_local_hour'] ?? date('H');
    }
    
    $name = $_SESSION['first_name'] ?? 'Usuario';
    
    if ($user_hour < 12) {
        return "Buenos días, $name";
    } elseif ($user_hour < 18) {
        return "Buenas tardes, $name";
    } else {
        return "Buenas noches, $name";
    }
}

// Función para limpiar sesión (logout programático)
function logout($redirect_to = 'index.php') {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Redirigir
    header('Location: ' . $redirect_to);
    exit;
}
?>
