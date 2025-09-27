<?php
// Iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

// Obtener los datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar que se recibieron los datos necesarios
if (isset($data['user_hour']) && is_numeric($data['user_hour'])) {
    // Guardar la hora local del usuario en la sesión
    $_SESSION['user_local_hour'] = (int)$data['user_hour'];
    
    // Opcionalmente, también guardar la zona horaria
    if (isset($data['timezone'])) {
        $_SESSION['user_timezone'] = $data['timezone'];
    }
    
    // Respuesta exitosa
    echo json_encode(['status' => 'success', 'message' => 'Horario actualizado']);
} else {
    // Error en los datos
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
}
?>