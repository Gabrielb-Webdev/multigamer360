<?php
require_once 'config/database.php';

class UserManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Registrar nuevo usuario
    public function registerUser($email, $password, $firstName, $lastName, $phone = null) {
        // Verificar si el email ya existe
        if ($this->getUserByEmail($email)) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password, first_name, last_name, phone) 
                VALUES (:email, :password, :first_name, :last_name, :phone)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':phone', $phone);
            
            if ($stmt->execute()) {
                $userId = $this->pdo->lastInsertId();
                return ['success' => true, 'user_id' => $userId, 'message' => 'Usuario registrado exitosamente'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al registrar usuario: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Error desconocido al registrar usuario'];
    }
    
    // Autenticar usuario
    public function authenticateUser($email, $password) {
        $user = $this->getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último login
            $this->updateLastLogin($user['id']);
            
            // Remover password del array por seguridad
            unset($user['password']);
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
    }
    
    // Obtener usuario por email
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email AND is_active = TRUE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Obtener usuario por ID
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = :id AND is_active = TRUE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Actualizar último login
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Actualizar perfil de usuario
    public function updateUserProfile($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone', 'birth_date'];
        $updateFields = [];
        $params = ['id' => $userId];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'message' => 'No hay campos para actualizar'];
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Perfil actualizado exitosamente'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al actualizar perfil: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Error desconocido al actualizar perfil'];
    }
    
    // Cambiar contraseña
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
        
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al cambiar contraseña: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Error desconocido al cambiar contraseña'];
    }
    
    // Obtener direcciones del usuario
    public function getUserAddresses($userId) {
        $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Agregar dirección
    public function addUserAddress($userId, $addressData) {
        $sql = "INSERT INTO user_addresses (user_id, type, first_name, last_name, company, address_line_1, address_line_2, city, state, postal_code, country, phone, is_default) 
                VALUES (:user_id, :type, :first_name, :last_name, :company, :address_line_1, :address_line_2, :city, :state, :postal_code, :country, :phone, :is_default)";
        
        try {
            // Si es dirección por defecto, quitar default de las demás
            if (!empty($addressData['is_default'])) {
                $this->removeDefaultAddress($userId);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':type', $addressData['type']);
            $stmt->bindParam(':first_name', $addressData['first_name']);
            $stmt->bindParam(':last_name', $addressData['last_name']);
            $stmt->bindParam(':company', $addressData['company']);
            $stmt->bindParam(':address_line_1', $addressData['address_line_1']);
            $stmt->bindParam(':address_line_2', $addressData['address_line_2']);
            $stmt->bindParam(':city', $addressData['city']);
            $stmt->bindParam(':state', $addressData['state']);
            $stmt->bindParam(':postal_code', $addressData['postal_code']);
            $stmt->bindParam(':country', $addressData['country']);
            $stmt->bindParam(':phone', $addressData['phone']);
            $stmt->bindParam(':is_default', $addressData['is_default'], PDO::PARAM_BOOL);
            
            if ($stmt->execute()) {
                return ['success' => true, 'address_id' => $this->pdo->lastInsertId()];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al agregar dirección: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Error desconocido al agregar dirección'];
    }
    
    // Quitar dirección por defecto
    private function removeDefaultAddress($userId) {
        $sql = "UPDATE user_addresses SET is_default = FALSE WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Obtener favoritos del usuario
    public function getUserFavorites($userId) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name, f.created_at as favorited_at
                FROM user_favorites f
                JOIN products p ON f.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN brands b ON p.brand_id = b.id
                WHERE f.user_id = :user_id AND p.is_active = TRUE
                ORDER BY f.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Agregar/quitar favorito
    public function toggleFavorite($userId, $productId) {
        // Verificar si ya está en favoritos
        $sql = "SELECT id FROM user_favorites WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            // Quitar de favoritos
            $sql = "DELETE FROM user_favorites WHERE user_id = :user_id AND product_id = :product_id";
            $action = 'removed';
        } else {
            // Agregar a favoritos
            $sql = "INSERT INTO user_favorites (user_id, product_id) VALUES (:user_id, :product_id)";
            $action = 'added';
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true, 'action' => $action];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al modificar favoritos: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Error desconocido al modificar favoritos'];
    }
}

// Crear instancia global
$userManager = new UserManager($pdo);
?>