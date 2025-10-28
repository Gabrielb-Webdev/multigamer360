<?php
/**
 * =====================================================
 * USER MANAGER SIMPLIFICADO - MULTIGAMER360
 * =====================================================
 * Sistema de gestión de usuarios SIN tabla de roles
 * Usa roles simples: 'admin', 'customer'
 * =====================================================
 */

class UserManagerSimple {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registrar nuevo usuario
     */
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
            
            // Rol por defecto: cliente
            // Roles disponibles: 'administrador', 'cliente', 'colaborador', 'moderador'
            $role = $data['role'] ?? 'cliente';
            
            // Validar que el rol sea válido
            $valid_roles = ['administrador', 'cliente', 'colaborador', 'moderador'];
            if (!in_array($role, $valid_roles)) {
                $role = 'cliente';
            }
            
            // Hash de la contraseña
            $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $sql = "INSERT INTO users (
                        email, password, first_name, last_name, phone, birth_date,
                        role, is_active, email_verified, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['email'],
                $hashed_password,
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['phone'] ?? null,
                $data['birth_date'] ?? null,
                $role
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
    
    /**
     * Login de usuario
     */
    public function loginUser($email, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, password, first_name, last_name, 
                       phone, birth_date, role, is_active, email_verified
                FROM users 
                WHERE email = ? AND is_active = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Actualizar last_login
                $updateLoginStmt = $this->pdo->prepare("
                    UPDATE users 
                    SET last_login = NOW() 
                    WHERE id = ?
                ");
                $updateLoginStmt->execute([$user['id']]);
                
                // Remover password del array antes de retornar
                unset($user['password']);
                
                // Agregar información adicional del rol
                $user['role_name'] = $this->getRoleName($user['role']);
                $user['role_slug'] = $user['role'];
                $user['is_admin'] = ($user['role'] === 'administrador');
                $user['can_manage_content'] = in_array($user['role'], ['administrador', 'colaborador', 'moderador']);
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verificar si email existe
     */
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Validar datos de usuario
     */
    public function validateUserData($data) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email inválido'];
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            return ['valid' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        
        if (empty($data['first_name']) || strlen(trim($data['first_name'])) < 2) {
            return ['valid' => false, 'message' => 'El nombre es requerido (mínimo 2 caracteres)'];
        }
        
        if (empty($data['last_name']) || strlen(trim($data['last_name'])) < 2) {
            return ['valid' => false, 'message' => 'El apellido es requerido (mínimo 2 caracteres)'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getUserById($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, first_name, last_name, phone, 
                       role, is_active, email_verified, created_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Actualizar perfil de usuario
     */
    public function updateUserProfile($user_id, $data) {
        try {
            $sql = "UPDATE users SET 
                        first_name = ?,
                        last_name = ?,
                        phone = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $user_id
            ]);
            
            return ['success' => true, 'message' => 'Perfil actualizado'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verificar contraseña actual
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
            }
            
            // Actualizar con nueva contraseña
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            
            return ['success' => true, 'message' => 'Contraseña actualizada'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verificar si el usuario es admin
     */
    public function isAdmin($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user && $user['role'] === 'administrador';
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtener nombre legible del rol
     */
    public function getRoleName($role) {
        $roles = [
            'administrador' => 'Administrador',
            'cliente' => 'Cliente',
            'colaborador' => 'Colaborador',
            'moderador' => 'Moderador'
        ];
        return $roles[$role] ?? 'Cliente';
    }
    
    /**
     * Obtener todos los roles disponibles
     */
    public function getAvailableRoles() {
        return [
            'administrador' => 'Administrador - Acceso completo al sistema',
            'colaborador' => 'Colaborador - Puede gestionar productos y contenido',
            'moderador' => 'Moderador - Puede moderar reviews y comentarios',
            'cliente' => 'Cliente - Usuario normal que compra productos'
        ];
    }
    
    /**
     * Verificar si el usuario puede gestionar contenido
     */
    public function canManageContent($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user && in_array($user['role'], ['administrador', 'colaborador', 'moderador']);
        } catch (PDOException $e) {
            return false;
        }
    }
}
