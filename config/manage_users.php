<?php
/**
 * Script para gestionar usuarios y roles
 */
require_once 'database.php';

try {
    echo "📊 SISTEMA DE GESTIÓN DE USUARIOS Y ROLES\n";
    echo "==========================================\n\n";
    
    // 1. Mostrar roles disponibles
    echo "🎭 ROLES DISPONIBLES:\n";
    $stmt = $pdo->query("SELECT * FROM user_roles ORDER BY level DESC");
    $roles = $stmt->fetchAll();
    
    foreach ($roles as $role) {
        echo "   ID: {$role['id']} | {$role['name']} (Nivel: {$role['level']}) | Slug: {$role['slug']}\n";
    }
    
    // 2. Mostrar usuarios existentes
    echo "\n👥 USUARIOS EN EL SISTEMA:\n";
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.first_name, u.last_name, u.role_id, r.name as role_name, r.level
        FROM users u 
        LEFT JOIN user_roles r ON u.role_id = r.id
        ORDER BY r.level DESC, u.created_at ASC
    ");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "   ℹ️ No hay usuarios registrados\n";
    } else {
        foreach ($users as $user) {
            $roleName = $user['role_name'] ?? 'Sin rol';
            $level = $user['level'] ?? 0;
            $roleId = $user['role_id'] ?? 'NULL';
            echo "   👤 ID: {$user['id']} | {$user['first_name']} {$user['last_name']} | {$user['email']} | {$roleName} (Nivel: {$level}) | Role ID: {$roleId}\n";
        }
    }
    
    // 3. Proporcionar comandos para asignar administrador
    echo "\n🔧 COMANDOS PARA ASIGNAR ROLES:\n";
    echo "================================\n";
    
    if (!empty($users)) {
        echo "Para convertir un usuario en SUPER ADMINISTRADOR, ejecuta:\n";
        echo "UPDATE users SET role_id = 1 WHERE id = [ID_DEL_USUARIO];\n\n";
        
        echo "Para convertir un usuario en ADMINISTRADOR, ejecuta:\n";
        echo "UPDATE users SET role_id = 2 WHERE id = [ID_DEL_USUARIO];\n\n";
        
        echo "Ejemplo - Para hacer administrador al usuario con ID 1:\n";
        echo "UPDATE users SET role_id = 1 WHERE id = 1;\n\n";
        
        // Verificar si hay algún super admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        $stmt->execute();
        $adminCount = $stmt->fetch()['count'];
        
        if ($adminCount == 0) {
            echo "⚠️ IMPORTANTE: No hay usuarios con rol de Super Administrador.\n";
            echo "Se recomienda asignar este rol a al menos un usuario.\n\n";
        } else {
            echo "✅ Hay {$adminCount} usuario(s) con rol de Super Administrador.\n\n";
        }
    }
    
    // 4. Crear script SQL para ejecutar directamente
    echo "📄 SCRIPT SQL GENERADO (copy/paste en phpMyAdmin):\n";
    echo "==================================================\n";
    
    if (!empty($users)) {
        echo "-- Asignar rol de Super Administrador al primer usuario registrado\n";
        $firstUser = $users[0];
        echo "UPDATE users SET role_id = 1 WHERE id = {$firstUser['id']};  -- {$firstUser['email']}\n\n";
        
        echo "-- Ver resultado\n";
        echo "SELECT u.id, u.email, u.first_name, u.last_name, r.name as role_name \n";
        echo "FROM users u LEFT JOIN user_roles r ON u.role_id = r.id \n";
        echo "WHERE u.id = {$firstUser['id']};\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>