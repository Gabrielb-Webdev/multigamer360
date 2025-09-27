<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdmin() {
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Verificar utilizando el UserManager para obtener datos actualizados
    try {
        // Obtener la conexión global de base de datos
        global $pdo;
        
        // Si no existe $pdo, incluir database.php
        if (!isset($pdo)) {
            require_once 'config/database.php';
        }
        
        require_once 'config/user_manager.php';
        
        $userManager = new UserManager($pdo);
        $user = $userManager->getUserById($_SESSION['user_id']);
        
        if (!$user || !$user['is_active']) {
            return false;
        }
        
        // Verificar nivel de administrador (80+)
        $user_level = $user['role_level'] ?? 0;
        $user_role = $user['role'] ?? '';
        
        // Solo usuarios con nivel 80 o superior pueden ser admin
        $is_admin_level = ($user_level >= 80);
        $is_admin_role = in_array($user_role, ['admin', 'SuperAdmin', 'Admin']);
        
        return $is_admin_level && $is_admin_role;
        
    } catch (Exception $e) {
        error_log("Error verificando admin status: " . $e->getMessage());
        return false;
    }
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
