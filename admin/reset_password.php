<?php
require_once '../config/database.php';

echo "=== RESET DE CONTRASEÑA PARA GABRIEL ===\n\n";

try {
    // Buscar usuario Gabriel
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['Gbustosgarcia01@gmail.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Usuario encontrado: {$user['email']}\n";
        
        // Nueva contraseña
        $new_password = 'admin123';
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Actualizar contraseña
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$hashed, $user['id']]);
        
        echo "✅ Contraseña actualizada\n";
        echo "Nueva contraseña: $new_password\n";
        
        // Verificar que puede hacer login
        if (password_verify($new_password, $hashed)) {
            echo "✅ Verificación de contraseña exitosa\n";
        } else {
            echo "❌ Error en verificación de contraseña\n";
        }
        
        // Asegurar que el usuario esté activo y con el rol correcto
        $pdo->prepare("UPDATE users SET is_active = 1, role = 'admin' WHERE id = ?")->execute([$user['id']]);
        echo "✅ Usuario activado y rol asignado\n";
        
        echo "\n🎯 Ahora puedes hacer login con:\n";
        echo "Email: Gbustosgarcia01@gmail.com\n";
        echo "Password: admin123\n";
        
    } else {
        echo "❌ Usuario no encontrado\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>