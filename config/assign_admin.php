<?php
/**
 * Script para asignar rol de Super Administrador a Gabriel Bustos
 */
require_once 'database.php';

try {
    echo "🔧 Asignando rol de Super Administrador...\n\n";
    
    // Asignar rol de Super Administrador (ID: 1) al usuario Gabriel Bustos (ID: 24)
    $stmt = $pdo->prepare("UPDATE users SET role_id = 1 WHERE id = 24");
    $stmt->execute();
    
    echo "✅ Rol actualizado exitosamente!\n\n";
    
    // Verificar el cambio
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.first_name, u.last_name, r.name as role_name, r.level, r.permissions
        FROM users u 
        LEFT JOIN user_roles r ON u.role_id = r.id 
        WHERE u.id = 24
    ");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "📊 INFORMACIÓN DEL USUARIO ACTUALIZADA:\n";
        echo "========================================\n";
        echo "👤 ID: {$user['id']}\n";
        echo "📧 Email: {$user['email']}\n";
        echo "👨‍💼 Nombre: {$user['first_name']} {$user['last_name']}\n";
        echo "🎭 Rol: {$user['role_name']}\n";
        echo "⭐ Nivel: {$user['level']}\n";
        echo "🔑 Permisos: " . ($user['permissions'] ? "Completos (JSON)" : "No definidos") . "\n\n";
        
        if ($user['permissions']) {
            $permissions = json_decode($user['permissions'], true);
            echo "🛡️ PERMISOS DETALLADOS:\n";
            foreach ($permissions as $resource => $actions) {
                echo "   📂 {$resource}: " . implode(', ', $actions) . "\n";
            }
        }
        
        echo "\n🎉 ¡Gabriel Bustos ahora es Super Administrador!\n";
        echo "✅ Tiene acceso completo a todas las funciones del sistema.\n";
        
    } else {
        echo "❌ Error: No se pudo verificar la actualización.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>