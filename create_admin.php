<?php
/**
 * Script para crear usuario administrador
 * EJECUTAR SOLO UNA VEZ y luego ELIMINAR este archivo por seguridad
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Crear Admin</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:600px;margin:0 auto;} .success{color:green;} .error{color:red;}</style>";
echo "</head><body>";

echo "<h1>Crear Usuario Administrador</h1>";
echo "<hr>";

try {
    // Datos del administrador
    $email = "admin@multigamer360.com";
    $password = "Admin123!"; // Cámbiala después de iniciar sesión
    $firstName = "Administrador";
    $lastName = "Sistema";
    
    // Verificar si ya existe un admin con cualquiera de los roles admin
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ? OR role IN ('admin', 'administrador')");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "<p class='error'>⚠️ Ya existe un usuario administrador.</p>";
        echo "<p>Email: <strong>$email</strong></p>";
        echo "<p>Si olvidaste la contraseña, actualízala manualmente en la base de datos.</p>";
        
        // Mostrar todos los usuarios admin
        $stmt = $pdo->query("SELECT id, email, first_name, last_name, role FROM users WHERE role IN ('admin', 'administrador')");
        $admins = $stmt->fetchAll();
        
        echo "<h3>Usuarios administradores existentes:</h3>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin['id']} - {$admin['email']} ({$admin['first_name']} {$admin['last_name']})</li>";
        }
        echo "</ul>";
        
        echo "<h3>¿Quieres restablecer la contraseña?</h3>";
        echo "<p>Descomenta el código de actualización en este archivo y ejecútalo nuevamente.</p>";
        
        // DESCOMENTA ESTAS LÍNEAS PARA RESETEAR LA CONTRASEÑA
        /*
        $newPassword = password_hash("Admin123!", PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$newPassword, $email]);
        echo "<p class='success'>✅ Contraseña restablecida a: Admin123!</p>";
        */
        
    } else {
        // Crear nuevo usuario admin
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (
                    email, 
                    password, 
                    first_name, 
                    last_name, 
                    role, 
                    is_active, 
                    email_verified,
                    created_at
                ) VALUES (?, ?, ?, ?, 'admin', 1, 1, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $email,
            $passwordHash,
            $firstName,
            $lastName
        ]);
        
        echo "<div class='success'>";
        echo "<h2>✅ Usuario Administrador Creado Exitosamente</h2>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Contraseña:</strong> $password</p>";
        echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Cambia esta contraseña después de iniciar sesión.</p>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h3>Pasos siguientes:</h3>";
        echo "<ol>";
        echo "<li>Anota estas credenciales en un lugar seguro</li>";
        echo "<li>Inicia sesión en el panel de administración</li>";
        echo "<li>Cambia tu contraseña desde el perfil</li>";
        echo "<li><strong style='color: red;'>ELIMINA este archivo (create_admin.php) por seguridad</strong></li>";
        echo "</ol>";
        
        echo "<p><a href='admin/login.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin-top:20px;'>Ir al Login Admin</a></p>";
    }
    
    echo "<hr>";
    echo "<h3>📊 Estadísticas de Usuarios</h3>";
    
    // Contar usuarios totales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    echo "<p>Total de usuarios: <strong>$totalUsers</strong></p>";
    
    // Contar por rol
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roles = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($roles as $role) {
        echo "<li>{$role['role']}: {$role['count']}</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
