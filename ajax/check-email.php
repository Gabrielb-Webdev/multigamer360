<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar que se envió el email
if (!isset($input['email']) || empty(trim($input['email']))) {
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'Email requerido'
    ]);
    exit;
}

$email = trim($input['email']);

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'Formato de email inválido'
    ]);
    exit;
}

try {
    // Verificar si el email ya existe en la base de datos
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $exists = $stmt->fetch() !== false;
    
    if ($exists) {
        echo json_encode([
            'valid' => true,
            'available' => false,
            'message' => 'Este email ya está registrado'
        ]);
    } else {
        echo json_encode([
            'valid' => true,
            'available' => true,
            'message' => 'Email disponible'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'Error de servidor'
    ]);
}
?>