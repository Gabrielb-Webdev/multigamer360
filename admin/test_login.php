<?php
session_start();
require_once '../config/database.php';
require_once '../config/user_manager.php';

echo "=== TEST DE LOGIN ADMINISTRATIVO ===\n\n";

// Simular login del usuario Gabriel
$userManager = new UserManager($pdo);
$result = $userManager->loginUser('Gbustosgarcia01@gmail.com', 'admin123');

if ($result['success']) {
    echo "✅ Login exitoso\n";
    $user = $result['user'];
    
    echo "Usuario: {$user['email']}\n";
    echo "Rol: {$user['role']}\n";
    echo "Nivel de rol: " . ($user['role_level'] ?? 'NULL') . "\n";
    echo "Activo: " . ($user['is_active'] ? 'SÍ' : 'NO') . "\n";
    
    // Establecer sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    echo "\n✅ Sesión establecida\n";
    echo "Session ID: {$_SESSION['user_id']}\n";
    
    // Verificar acceso admin
    $current_user = $userManager->getUserById($_SESSION['user_id']);
    $user_level = $current_user['role_level'] ?? 0;
    $user_role = $current_user['role'] ?? 'customer';
    $has_admin_access = ($user_level >= 60) || in_array($user_role, ['admin', 'moderator']);
    
    echo "\nVerificación de acceso admin:\n";
    echo "- Nivel de rol: $user_level\n";
    echo "- Rol: $user_role\n";
    echo "- Acceso admin: " . ($has_admin_access ? 'SÍ' : 'NO') . "\n";
    
    if ($has_admin_access) {
        echo "\n🎉 ¡El usuario puede acceder al panel de administración!\n";
        echo "\nAhora puedes intentar acceder a: http://localhost/multigamer360/admin/\n";
    }
    
} else {
    echo "❌ Error en login: " . $result['message'] . "\n";
}
?>