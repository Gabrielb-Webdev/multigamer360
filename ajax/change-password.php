<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';
initSecureSession();

// Verificar que el usuario esté logueado
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Incluir base de datos
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Función para validar fortaleza de contraseña
function isStrongPassword($password) {
    // Mínimo 8 caracteres
    if (strlen($password) < 8) {
        return false;
    }
    
    // Debe contener al menos una minúscula
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Debe contener al menos una mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Debe contener al menos un número
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Debe contener al menos un símbolo
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

try {
    // Validar datos recibidos
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }
    
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Obtener la contraseña actual del usuario
    $userStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Verificar la contraseña actual
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        exit;
    }
    
    // Verificar que la nueva contraseña no sea igual a la actual
    if (password_verify($new_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña no puede ser igual a la actual']);
        exit;
    }
    
    // Verificar historial de contraseñas (últimas 5 contraseñas)
    $historyStmt = $pdo->prepare("
        SELECT password_hash 
        FROM password_history 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $historyStmt->execute([$userId]);
    $passwordHistory = $historyStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Verificar si la nueva contraseña coincide con alguna anterior
    foreach ($passwordHistory as $oldPasswordHash) {
        if (password_verify($new_password, $oldPasswordHash)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Esta contraseña ya fue utilizada anteriormente. Por favor, elige una contraseña diferente.'
            ]);
            exit;
        }
    }
    
    // Validar fortaleza de contraseña en el backend
    if (!isStrongPassword($new_password)) {
        echo json_encode([
            'success' => false, 
            'message' => 'La contraseña debe contener al menos: 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.'
        ]);
        exit;
    }
    
    // Generar hash de la nueva contraseña
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Actualizar la contraseña
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateResult = $updateStmt->execute([$new_password_hash, $userId]);
        
        if (!$updateResult) {
            throw new Exception('Error al actualizar la contraseña');
        }
        
        // Agregar nueva contraseña al historial
        $historyStmt = $pdo->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
        $historyResult = $historyStmt->execute([$userId, $new_password_hash]);
        
        if (!$historyResult) {
            throw new Exception('Error al guardar en historial');
        }
        
        // Limpiar historial antiguo (mantener solo las últimas 5 contraseñas)
        $cleanupStmt = $pdo->prepare("
            DELETE FROM password_history 
            WHERE user_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM password_history 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ) AS recent_passwords
            )
        ");
        $cleanupStmt->execute([$userId, $userId]);
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña cambiada correctamente'
        ]);
        
        // Log de seguridad
        error_log("Password changed for user ID: $userId at " . date('Y-m-d H:i:s'));
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        error_log("Error changing password for user ID $userId: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al cambiar la contraseña: ' . $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en change-password.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>