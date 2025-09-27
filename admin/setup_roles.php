<?php
/**
 * Script para verificar y corregir la configuración de roles y permisos
 */

require_once '../config/database.php';

echo "<h1>Verificación del Sistema de Roles</h1>";

try {
    // Verificar si la tabla user_roles existe
    $check_table = $pdo->query("SHOW TABLES LIKE 'user_roles'");
    if ($check_table->rowCount() == 0) {
        echo "<h2>Creando tabla user_roles...</h2>";
        
        $sql = "CREATE TABLE `user_roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `slug` varchar(50) NOT NULL,
            `level` int(11) NOT NULL DEFAULT 0,
            `permissions` text,
            `description` text,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "✅ Tabla user_roles creada<br>";
    } else {
        echo "✅ Tabla user_roles existe<br>";
    }
    
    // Verificar y crear roles básicos
    $roles = [
        [
            'name' => 'Super Administrador',
            'slug' => 'superadmin',
            'level' => 100,
            'permissions' => json_encode([
                'users' => ['create', 'read', 'update', 'delete'],
                'products' => ['create', 'read', 'update', 'delete'],
                'orders' => ['create', 'read', 'update', 'delete'],
                'categories' => ['create', 'read', 'update', 'delete'],
                'brands' => ['create', 'read', 'update', 'delete'],
                'settings' => ['create', 'read', 'update', 'delete'],
                'reports' => ['read']
            ]),
            'description' => 'Acceso completo al sistema'
        ],
        [
            'name' => 'Administrador',
            'slug' => 'admin',
            'level' => 80,
            'permissions' => json_encode([
                'users' => ['read', 'update'],
                'products' => ['create', 'read', 'update', 'delete'],
                'orders' => ['read', 'update'],
                'categories' => ['create', 'read', 'update', 'delete'],
                'brands' => ['create', 'read', 'update', 'delete'],
                'reports' => ['read']
            ]),
            'description' => 'Gestión de productos, pedidos y contenido'
        ],
        [
            'name' => 'Moderador',
            'slug' => 'moderator',
            'level' => 60,
            'permissions' => json_encode([
                'products' => ['read', 'update'],
                'orders' => ['read', 'update'],
                'categories' => ['read'],
                'brands' => ['read']
            ]),
            'description' => 'Gestión de pedidos y productos'
        ],
        [
            'name' => 'Cliente Premium',
            'slug' => 'premium',
            'level' => 20,
            'permissions' => json_encode([]),
            'description' => 'Cliente con beneficios especiales'
        ],
        [
            'name' => 'Cliente',
            'slug' => 'customer',
            'level' => 10,
            'permissions' => json_encode([]),
            'description' => 'Cliente estándar'
        ]
    ];
    
    echo "<h2>Verificando roles...</h2>";
    foreach ($roles as $role) {
        $check = $pdo->prepare("SELECT id FROM user_roles WHERE slug = ?");
        $check->execute([$role['slug']]);
        
        if (!$check->fetch()) {
            try {
                $insert = $pdo->prepare("
                    INSERT INTO user_roles (name, slug, level, permissions, description, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                $insert->execute([
                    $role['name'],
                    $role['slug'], 
                    $role['level'],
                    $role['permissions'],
                    $role['description']
                ]);
                echo "✅ Rol '{$role['name']}' creado<br>";
            } catch (PDOException $e) {
                echo "⚠️ Rol '{$role['name']}' ya existe o error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ Rol '{$role['name']}' existe<br>";
        }
    }
    
    // Verificar si existe la columna role_id en users
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'");
    if ($check_column->rowCount() == 0) {
        echo "<h2>Agregando columna role_id a users...</h2>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role_id INT DEFAULT 5");
        echo "✅ Columna role_id agregada<br>";
    }
    
    // Verificar usuarios admin actuales
    echo "<h2>Usuarios administradores actuales:</h2>";
    $admin_users = $pdo->query("
        SELECT u.id, u.username, u.email, u.role, r.name as role_name, r.level as role_level 
        FROM users u 
        LEFT JOIN user_roles r ON u.role_id = r.id 
        WHERE u.role IN ('admin', 'superadmin') OR r.level >= 60
    ");
    
    $users = $admin_users->fetchAll();
    if (empty($users)) {
        echo "<p style='color: orange;'>⚠️ No se encontraron usuarios administradores</p>";
        
        // Buscar usuario con email gabriel
        $gabriel = $pdo->prepare("SELECT * FROM users WHERE email LIKE '%gabriel%' OR username LIKE '%gabriel%'");
        $gabriel->execute();
        $gabriel_user = $gabriel->fetch();
        
        if ($gabriel_user) {
            echo "<h3>Convirtiendo usuario Gabriel en administrador...</h3>";
            
            // Obtener ID del rol admin
            $admin_role = $pdo->query("SELECT id FROM user_roles WHERE slug = 'admin'")->fetch();
            
            if ($admin_role) {
                $update = $pdo->prepare("UPDATE users SET role_id = ?, role = 'admin' WHERE id = ?");
                $update->execute([$admin_role['id'], $gabriel_user['id']]);
                echo "✅ Usuario Gabriel convertido a administrador<br>";
            }
        }
    } else {
        foreach ($users as $user) {
            echo "✅ {$user['username']} ({$user['email']}) - Rol: {$user['role_name']} (Nivel: {$user['role_level']})<br>";
        }
    }
    
    // Verificar sesión actual
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<h2>Sesión actual:</h2>";
        $current_user = $pdo->prepare("
            SELECT u.*, r.name as role_name, r.level as role_level, r.permissions 
            FROM users u 
            LEFT JOIN user_roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $current_user->execute([$_SESSION['user_id']]);
        $user = $current_user->fetch();
        
        if ($user) {
            echo "Usuario: {$user['username']} ({$user['email']})<br>";
            echo "Rol: {$user['role_name']} (Nivel: {$user['role_level']})<br>";
            echo "Puede acceder al admin: " . ($user['role_level'] >= 60 ? "✅ SÍ" : "❌ NO") . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f5f5f5;
}
h1, h2, h3 {
    color: #333;
}
</style>