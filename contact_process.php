<?php
header('Content-Type: application/json');

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Verificar que la request sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y limpiar datos del formulario
$nombre = cleanInput($_POST['nombre'] ?? '');
$apellido = cleanInput($_POST['apellido'] ?? '');
$email = cleanInput($_POST['email'] ?? '');
$asunto = cleanInput($_POST['asunto'] ?? '');
$mensaje = cleanInput($_POST['mensaje'] ?? '');

// Validaciones
$errors = [];

if (empty($nombre)) {
    $errors[] = 'El nombre es requerido';
}

if (empty($apellido)) {
    $errors[] = 'El apellido es requerido';
}

if (empty($email)) {
    $errors[] = 'El email es requerido';
} elseif (!isValidEmail($email)) {
    $errors[] = 'El email no tiene un formato válido';
}

if (empty($asunto)) {
    $errors[] = 'El asunto es requerido';
}

if (empty($mensaje)) {
    $errors[] = 'El mensaje es requerido';
} elseif (strlen($mensaje) < 10) {
    $errors[] = 'El mensaje debe tener al menos 10 caracteres';
}

// Si hay errores, devolver error
if (!empty($errors)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Por favor corrige los siguientes errores: ' . implode(', ', $errors)
    ]);
    exit;
}

// Guardar mensaje en archivo de texto (sin base de datos)
try {
    $log_file = 'logs/contact_messages.txt';
    
    // Crear directorio si no existe
    if (!is_dir('logs')) {
        mkdir('logs', 0777, true);
    }
    
    $message_data = [
        'fecha' => date('Y-m-d H:i:s'),
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'asunto' => $asunto,
        'mensaje' => $mensaje,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $log_entry = "==========================================\n";
    $log_entry .= "NUEVO MENSAJE DE CONTACTO\n";
    $log_entry .= "==========================================\n";
    $log_entry .= "Fecha: " . $message_data['fecha'] . "\n";
    $log_entry .= "Nombre: " . $message_data['nombre'] . " " . $message_data['apellido'] . "\n";
    $log_entry .= "Email: " . $message_data['email'] . "\n";
    $log_entry .= "Asunto: " . $message_data['asunto'] . "\n";
    $log_entry .= "IP: " . $message_data['ip'] . "\n";
    $log_entry .= "Mensaje:\n" . $message_data['mensaje'] . "\n";
    $log_entry .= "==========================================\n\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    // Continuar sin guardar si hay error
}

// Preparar el email
$asuntos_nombres = [
    'consulta_producto' => 'Consulta sobre productos',
    'soporte_tecnico' => 'Soporte técnico',
    'sugerencia' => 'Sugerencia',
    'reclamo' => 'Reclamo',
    'otro' => 'Otro'
];

$asunto_nombre = $asuntos_nombres[$asunto] ?? 'Consulta general';

// Email para el administrador
$to_admin = 'contacto@multigamer360.com'; // Cambiar por tu email real
$subject_admin = "[MultiGamer360] Nuevo mensaje de contacto: $asunto_nombre";

$message_admin = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #c11b1b; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .field strong { color: #c11b1b; }
        .footer { background-color: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Nuevo Mensaje de Contacto - MultiGamer360</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <strong>Nombre:</strong> $nombre $apellido
            </div>
            <div class='field'>
                <strong>Email:</strong> $email
            </div>
            <div class='field'>
                <strong>Asunto:</strong> $asunto_nombre
            </div>
            <div class='field'>
                <strong>Mensaje:</strong><br>
                " . nl2br($mensaje) . "
            </div>
            <div class='field'>
                <strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "
            </div>
            <div class='field'>
                <strong>IP:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "
            </div>
        </div>
        <div class='footer'>
            Este mensaje fue enviado desde el formulario de contacto de MultiGamer360
        </div>
    </div>
</body>
</html>
";

// Headers para el email del admin
$headers_admin = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: MultiGamer360 <noreply@multigamer360.com>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
];

// Email de confirmación para el usuario
$subject_user = "Gracias por contactarnos - MultiGamer360";

$message_user = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #c11b1b; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .footer { background-color: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
        .highlight { color: #c11b1b; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>¡Gracias por contactarnos!</h2>
        </div>
        <div class='content'>
            <p>Hola <span class='highlight'>$nombre</span>,</p>
            
            <p>Hemos recibido tu mensaje sobre: <strong>$asunto_nombre</strong></p>
            
            <p>Nuestro equipo revisará tu consulta y te responderemos lo antes posible en la dirección de email: <strong>$email</strong></p>
            
            <p><strong>Tu mensaje:</strong><br>
            " . nl2br($mensaje) . "</p>
            
            <p>Tiempo estimado de respuesta: <strong>24-48 horas hábiles</strong></p>
            
            <p>Si tu consulta es urgente, también puedes contactarnos directamente a través de nuestros canales de soporte.</p>
            
            <p>¡Gracias por elegir MultiGamer360!</p>
        </div>
        <div class='footer'>
            <p>MultiGamer360 - Tu tienda gaming de confianza</p>
            <p>Este es un email automático, por favor no respondas a esta dirección.</p>
        </div>
    </div>
</body>
</html>
";

// Headers para el email del usuario
$headers_user = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: MultiGamer360 <noreply@multigamer360.com>',
    'X-Mailer: PHP/' . phpversion()
];

// Intentar enviar emails
$email_sent = false;

try {
    // Enviar email al admin
    $admin_sent = mail($to_admin, $subject_admin, $message_admin, implode("\r\n", $headers_admin));
    
    // Enviar email de confirmación al usuario
    $user_sent = mail($email, $subject_user, $message_user, implode("\r\n", $headers_user));
    
    $email_sent = $admin_sent; // Solo necesitamos que el email del admin se envíe
    
} catch (Exception $e) {
    error_log("Error enviando email de contacto: " . $e->getMessage());
}

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'message' => '¡Gracias por contactarnos! Tu mensaje ha sido enviado correctamente. Te responderemos pronto.'
]);

// Log del mensaje (opcional)
error_log("Nuevo mensaje de contacto de: $nombre $apellido ($email) - Asunto: $asunto_nombre");
?>
