<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../config/session_config.php';
initSecureSession();

// Log para debugging
error_log("UPDATE-PROFILE: Request started");
error_log("UPDATE-PROFILE: POST data: " . print_r($_POST, true));

// Verificar que el usuario esté logueado
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    error_log("UPDATE-PROFILE: User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

error_log("UPDATE-PROFILE: User ID: " . $_SESSION['user_id']);

// Incluir base de datos
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Debug: Log all received data
    error_log("UPDATE-PROFILE: All POST data: " . print_r($_POST, true));
    
    // Validar datos recibidos
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth_date = $_POST['birth_date'] ?? null;
    
    // Debug específico para fecha de nacimiento
    error_log("UPDATE-PROFILE: birth_date received: " . var_export($birth_date, true));
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Nombre, apellido y email son obligatorios']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Verificar si el email ya está en uso por otro usuario
    $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkEmailStmt->execute([$email, $userId]);
    if ($checkEmailStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este email ya está en uso']);
        exit;
    }
    
    // Actualizar los datos del usuario
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, phone = ?, birth_date = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    $birth_date = empty($birth_date) ? null : $birth_date;
    
    // Debug: mostrar todos los valores que se van a actualizar
    error_log("UPDATE-PROFILE: Valores para UPDATE:");
    error_log("UPDATE-PROFILE: first_name: " . $first_name);
    error_log("UPDATE-PROFILE: last_name: " . $last_name);
    error_log("UPDATE-PROFILE: email: " . $email);
    error_log("UPDATE-PROFILE: phone: " . $phone);
    error_log("UPDATE-PROFILE: birth_date final: " . var_export($birth_date, true));
    error_log("UPDATE-PROFILE: user_id: " . $userId);
    
    $result = $updateStmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $birth_date,
        $userId
    ]);
    
    if ($result) {
        // Actualizar datos en sesión
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['email'] = $email;
        
        // Limpiar datos completos de sesión para forzar recarga
        unset($_SESSION['user_complete_data']);
        
        error_log("UPDATE-PROFILE: Profile updated successfully");
        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ]);
    } else {
        error_log("UPDATE-PROFILE: Database update failed");
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el perfil'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en update-profile.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>