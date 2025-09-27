<?php
/**
 * Script para configurar sistema de roles y crear usuario administrador
 */
require_once 'database.php';

try {
    echo "🔧 Configurando sistema de roles y usuarios...\n\n";
    
    // 1. Crear tabla de roles si no existe
    $createRolesTable = "
    CREATE TABLE IF NOT EXISTS user_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        slug VARCHAR(50) UNIQUE NOT NULL,
        level INT NOT NULL DEFAULT 0,
        permissions JSON,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createRolesTable);
    echo "✅ Tabla user_roles creada/verificada\n";
    
    // 2. Agregar campo role_id a users si no existe
    try {
        $addRoleColumn = "ALTER TABLE users ADD COLUMN role_id INT DEFAULT 5";
        $pdo->exec($addRoleColumn);
        echo "✅ Campo role_id agregado a tabla users\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Campo role_id ya existe en tabla users\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Insertar roles básicos
    $roles = [
        [
            'name' => 'Super Administrador',
            'slug' => 'super-admin',
            'level' => 100,
            'permissions' => json_encode([
                'users' => ['create', 'read', 'update', 'delete', 'manage_roles'],
                'products' => ['create', 'read', 'update', 'delete'],
                'orders' => ['read', 'update', 'delete'],
                'settings' => ['read', 'update'],
                'admin' => ['access_dashboard', 'manage_system']
            ])
        ],
        [
            'name' => 'Administrador',
            'slug' => 'admin',
            'level' => 80,
            'permissions' => json_encode([
                'users' => ['read', 'update'],
                'products' => ['create', 'read', 'update', 'delete'],
                'orders' => ['read', 'update'],
                'admin' => ['access_dashboard']
            ])
        ],
        [
            'name' => 'Moderador',
            'slug' => 'moderator',
            'level' => 60,
            'permissions' => json_encode([
                'products' => ['read', 'update'],
                'orders' => ['read'],
                'admin' => ['access_dashboard']
            ])
        ],
        [
            'name' => 'Empleado',
            'slug' => 'employee',
            'level' => 40,
            'permissions' => json_encode([
                'orders' => ['read'],
                'admin' => ['access_dashboard']
            ])
        ],
        [
            'name' => 'Cliente',
            'slug' => 'client',
            'level' => 20,
            'permissions' => json_encode([
                'profile' => ['read', 'update'],
                'orders' => ['read']
            ])
        ]
    ];
    
    // Verificar e insertar roles
    foreach ($roles as $role) {
        $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE slug = ?");
        $stmt->execute([$role['slug']]);
        
        if (!$stmt->fetch()) {
            $insertRole = $pdo->prepare("
                INSERT INTO user_roles (name, slug, level, permissions, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $insertRole->execute([
                $role['name'],
                $role['slug'],
                $role['level'],
                $role['permissions']
            ]);
            echo "✅ Rol '{$role['name']}' creado\n";
        } else {
            echo "ℹ️ Rol '{$role['name']}' ya existe\n";
        }
    }
    
    // 4. Crear usuario administrador por defecto
    echo "\n🔑 Configurando usuario administrador...\n";
    
    $adminEmail = 'admin@multigamer360.com';
    $adminPassword = 'admin123'; // Cambiar por una contraseña segura
    
    // Verificar si ya existe un admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    
    if (!$stmt->fetch()) {
        // Obtener ID del rol Super Administrador
        $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE slug = 'super-admin'");
        $stmt->execute();
        $superAdminRole = $stmt->fetch();
        
        if ($superAdminRole) {
            $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);
            
            $insertAdmin = $pdo->prepare("
                INSERT INTO users (email, password, first_name, last_name, role_id, is_active, email_verified) 
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ");
            $insertAdmin->execute([
                $adminEmail,
                $hashedPassword,
                'Super',
                'Administrador',
                $superAdminRole['id']
            ]);
            
            echo "✅ Usuario administrador creado:\n";
            echo "   📧 Email: {$adminEmail}\n";
            echo "   🔐 Contraseña: {$adminPassword}\n";
            echo "   ⚠️ IMPORTANTE: Cambiar la contraseña después del primer login\n";
        }
    } else {
        echo "ℹ️ Ya existe un usuario con email: {$adminEmail}\n";
    }
    
    // 5. Mostrar resumen de usuarios existentes
    echo "\n📊 Usuarios en el sistema:\n";
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.first_name, u.last_name, r.name as role_name, r.level
        FROM users u 
        LEFT JOIN user_roles r ON u.role_id = r.id
        ORDER BY r.level DESC, u.created_at ASC
    ");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $roleName = $user['role_name'] ?? 'Sin rol';
        $level = $user['level'] ?? 0;
        echo "   👤 ID: {$user['id']} | {$user['first_name']} {$user['last_name']} | {$user['email']} | {$roleName} (Nivel: {$level})\n";
    }
    
    echo "\n🎉 ¡Configuración completada exitosamente!\n";
    echo "\n📝 Para asignar rol de administrador a un usuario específico, usa:\n";
    echo "   UPDATE users SET role_id = 1 WHERE email = 'usuario@ejemplo.com';\n";
    echo "   (Donde role_id = 1 es Super Administrador)\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>