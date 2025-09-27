<?php
// Sistema de gestión de usuarios con roles y permisos
require_once 'database.php';

class UserManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Registrar nuevo usuario con rol automático
    public function registerUser($data) {
        try {
            // Verificar si el email ya existe
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }
            
            // Validar datos
            $validation = $this->validateUserData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            // Asignar rol por defecto (Cliente = 5, a menos que se especifique otro)
            $role_id = $data['role_id'] ?? 5; // Cliente por defecto
            
            // Hash de la contraseña
            $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $sql = "INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['email'],
                $hashed_password,
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['phone'] ?? null,
                $role_id
            ]);
            
            $user_id = $this->pdo->lastInsertId();
            
            return [
                'success' => true, 
                'message' => 'Usuario registrado exitosamente',
                'user_id' => $user_id
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }
    
    // Login de usuario con información de rol
    public function loginUser($email, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.email, u.password, u.first_name, u.last_name, u.is_active, 
                       u.role_id, r.name as role_name, r.slug as role_slug, r.level as role_level, r.permissions
                FROM users u 
                LEFT JOIN user_roles r ON u.role_id = r.id
                WHERE u.email = ? AND u.is_active = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Remover password del array antes de retornar
                unset($user['password']);
                
                // Decodificar permisos JSON
                if ($user['permissions']) {
                    $user['permissions'] = json_decode($user['permissions'], true);
                }
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    // Verificar si email existe
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    // Validar datos de usuario
    public function validateUserData($data) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email inválido'];
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            return ['valid' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        return ['valid' => true];
    }
    
    // Obtener todos los roles disponibles
    public function getRoles() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM user_roles WHERE is_active = 1 ORDER BY level DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Obtener rol por ID
    public function getRoleById($role_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_roles WHERE id = ?");
            $stmt->execute([$role_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Verificar si usuario tiene permiso específico
    public function hasPermission($user_id, $resource, $action) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.permissions 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result && $result['permissions']) {
                $permissions = json_decode($result['permissions'], true);
                return isset($permissions[$resource]) && in_array($action, $permissions[$resource]);
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Verificar si es admin (nivel >= 80)
    public function isAdmin($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.level 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            return $result && $result['level'] >= 80;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Obtener todos los usuarios con sus roles
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("
                SELECT u.id, u.username, u.email, u.first_name, u.last_name, 
                       u.is_active, u.created_at, r.name as role_name, r.level
                FROM users u 
                LEFT JOIN user_roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Actualizar rol de usuario
    public function updateUserRole($user_id, $role_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$role_id, $user_id]);
            return ['success' => true, 'message' => 'Rol actualizado correctamente'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al actualizar rol: ' . $e->getMessage()];
        }
    }
    
    // Obtener usuario por ID con rol
    public function getUserById($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, r.name as role_name, r.slug as role_slug, r.level as role_level, r.permissions
                FROM users u 
                LEFT JOIN user_roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            // Decodificar permisos JSON si existen
            if ($user && $user['permissions']) {
                $user['permissions'] = json_decode($user['permissions'], true);
            }
            
            return $user;
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>