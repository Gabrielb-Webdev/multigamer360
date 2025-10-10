<?php
/**
 * =====================================================
 * MULTIGAMER360 - PÁGINA DE REGISTRO
 * =====================================================
 * 
 * Descripción: Sistema de registro de nuevos usuarios
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-16
 * 
 * Funcionalidades:
 * - Registro de nuevos usuarios
 * - Validación de datos del formulario
 * - Verificación de fortaleza de contraseña
 * - Validación de email único
 * - Cifrado seguro de contraseñas
 * - Sistema de verificación por email
 * - Interfaz moderna y responsiva
 */

// =====================================================
// CONFIGURACIÓN INICIAL
// =====================================================

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'config/user_manager_simple.php';

// =====================================================
// INICIALIZACIÓN DE MANAGERS
// =====================================================

// Crear instancia del manejador de usuarios
try {
    $userManager = new UserManagerSimple($pdo);
} catch (Exception $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// =====================================================
// VARIABLES DE CONTROL
// =====================================================

$error = '';
$success = '';

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

/**
 * Validar fortaleza de contraseña
 * @param string $password - Contraseña a validar
 * @return bool - True si cumple los requisitos
 */
function isPasswordStrong($password) {
    // Mínimo 8 caracteres
    if (strlen($password) < 8) return false;
    
    // Al menos una mayúscula
    if (!preg_match('/[A-Z]/', $password)) return false;
    
    // Al menos una minúscula
    if (!preg_match('/[a-z]/', $password)) return false;
    
    // Al menos un número
    if (!preg_match('/[0-9]/', $password)) return false;
    
    // Al menos un carácter especial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) return false;
    
    return true;
}

// Función para validar nombres (solo letras, espacios y acentos)
function isValidName($name) {
    // Verificar longitud mínima
    if (strlen(trim($name)) < 2) return false;
    
    // Verificar que solo contenga letras, espacios, acentos, apostrofes y guiones
    // Incluye caracteres latinos con acentos
    if (!preg_match('/^[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s\'\-]+$/u', $name)) return false;
    
    return true;
}

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Procesar formulario de registro
if ($_POST) {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? '')
    ];
    
    // Validar confirmación de contraseña
    if ($data['password'] !== $data['confirm_password']) {
        $error = 'Las contraseñas no coinciden';
    } 
    // Validar campos requeridos
    else if (empty($data['email']) || empty($data['password']) || 
             empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Todos los campos marcados con * son obligatorios';
    }
    // Validar que nombres solo contengan letras, espacios y acentos
    else if (!isValidName($data['first_name'])) {
        $error = 'El nombre solo puede contener letras, espacios y acentos';
    }
    else if (!isValidName($data['last_name'])) {
        $error = 'El apellido solo puede contener letras, espacios y acentos';
    }
    // Validar formato de email
    else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido';
    }
    // Validar fortaleza de contraseña
    else if (!isPasswordStrong($data['password'])) {
        $error = 'La contraseña debe tener: mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial';
    } else {
        try {
            // Asignar rol de cliente por defecto
            $data['role'] = 'customer';
            
            $result = $userManager->registerUser($data);
            
            if ($result['success']) {
                // Auto-login después del registro exitoso
                $loginResult = $userManager->loginUser($data['email'], $data['password']);
                
                if ($loginResult['success']) {
                    // Establecer sesión
                    $_SESSION['user_id'] = $loginResult['user']['id'];
                    $_SESSION['email'] = $loginResult['user']['email'];
                    $_SESSION['first_name'] = $loginResult['user']['first_name'];
                    $_SESSION['last_name'] = $loginResult['user']['last_name'];
                    $_SESSION['role'] = $loginResult['user']['role'];
                    $_SESSION['role_name'] = $loginResult['user']['role_name'];
                    $_SESSION['is_admin'] = $loginResult['user']['is_admin'];
                    
                    // Redireccionar a la página principal con mensaje de bienvenida
                    header('Location: index.php?registered=1');
                    exit;
                } else {
                    $success = 'Usuario registrado exitosamente. Por favor, inicia sesión.';
                }
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Error al registrar usuario: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - MultiGamer360</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
        .iti {
            width: 100%;
            position: relative;
            display: block;
            height: 50px;
            border-radius: 8px;
            overflow: visible !important;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        /* Estado focus para todo el componente */
        .iti.iti--focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(139, 0, 0, 0.3);
        }
        
        .iti__flag-container {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 0;
            z-index: 1;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        
        .iti__selected-flag {
            z-index: 4;
            height: 100%;
            width: 100px;
            padding: 0 0px 0 15px;
            background: transparent;
            border: none;
            color: var(--text-color);
            transition: background 0.3s ease;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            outline: none;
            min-width: 100px;
            max-width: 100px;
        }
        
        .iti__selected-flag .iti__flag {
            margin-right: 8px;
            flex-shrink: 0;
        }
        
        .iti__selected-flag .iti__dial-code {
            flex-shrink: 0;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .iti__selected-flag:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .iti__arrow {
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid #ffffff;
            border-bottom: none;
            margin-left: 8px;
            margin-right: 6px;
            transition: transform 0.3s ease;
            display: inline-block;
            width: 0;
            height: 0;
            position: relative;
        }
        
        .iti--show-flags .iti__arrow {
            transform: rotate(180deg);
        }
        
        .iti__country-list {
            z-index: 1050 !important;
            max-height: 200px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.8);
            position: absolute !important;
            width: 100% !important;
            min-width: 300px;
            margin-top: 2px;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .iti__country-list--hide {
            display: none !important;
        }
        
        .iti__country {
            padding: 10px 16px !important;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            display: flex !important;
            align-items: center;
            cursor: pointer !important;
            pointer-events: auto !important;
            background: transparent !important;
        }
        
        .iti__country:hover,
        .iti__country.iti__highlight {
            background: rgba(139, 0, 0, 0.3) !important;
            color: #ffffff !important;
        }
        
        .iti__country:last-child {
            border-bottom: none;
        }
        
        .iti__country-name,
        .iti__dial-code {
            color: #ffffff !important;
        }
        
        .iti input[type=tel] {
            background: transparent;
            border: none;
            color: var(--text-color);
            height: 100%;
            padding: 0 15px 0 110px;
            border-radius: 8px;
            font-size: 16px;
            line-height: 1.5;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }
        
        .iti input[type=tel]:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .iti input[type=tel]::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Arreglar la flecha del dropdown */
        .iti__arrow {
            border-left: 4px solid transparent !important;
            border-right: 4px solid transparent !important;
            border-top: 5px solid #ffffff !important;
            border-bottom: none !important;
            margin-left: 6px;
            margin-right: 6px;
            transition: transform 0.3s ease;
            display: inline-block !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        /* Cuando el dropdown está abierto, rotar la flecha */
        .iti--show-flags .iti__arrow,
        .iti.iti--container-open .iti__arrow {
            transform: rotate(180deg) !important;
        }
        
        /* Estilos para las banderas y códigos de país */
        .iti__flag {
            margin-right: 8px;
            display: inline-block;
            width: 20px;
            height: 15px;
            background-image: url("https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/img/flags.png");
        }
        
        .iti__selected-flag .iti__flag {
            margin-right: 8px;
            display: inline-block;
            vertical-align: middle;
        }
        
        .iti__selected-flag .iti__dial-code {
            font-weight: 500;
            color: var(--text-color);
        }
        
        /* Asegurar que las banderas en el dropdown se muestren */
        .iti__country .iti__flag {
            width: 20px;
            height: 15px;
            background-image: url("https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/img/flags.png");
            margin-right: 8px;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Estados de validación */
        .iti.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
        }
        
        .iti.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
        }
        
        /* Scrollbar para la lista de países */
        .iti__country-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .iti__country-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .iti__country-list::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
    </style>
    
    <style>
        :root {
            --primary-color: #8B0000;
            --secondary-color: #000000;
            --text-color: #FFFFFF;
            --background-color: #1A1A1A;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        body {
            background-color: var(--background-color);
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--background-color);
        }
        
        .register-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .register-header h2 {
            color: var(--text-color);
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .register-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            height: 50px;
            padding: 0 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(139, 0, 0, 0.3);
            color: var(--text-color);
        }
        
        /* Estilos para validación en verde */
        .form-control.is-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3) !important;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.3) !important;
        }
        
        .valid-feedback {
            color: #28a745;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .btn-register {
            background: linear-gradient(45deg, var(--primary-color), #A00000);
            border: none;
            border-radius: 8px;
            color: var(--text-color);
            height: 55px;
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            background: linear-gradient(45deg, #A00000, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 0, 0, 0.4);
            color: var(--text-color);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .login-link p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #A00000;
        }
        
        .back-home {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 1000;
        }
        
        .back-home a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            backdrop-filter: blur(10px);
        }
        
        .back-home a:hover {
            color: var(--primary-color);
            background: rgba(139, 0, 0, 0.2);
            transform: scale(1.1);
        }
        
        .required-label::after {
            content: " *";
            color: var(--primary-color);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            padding: 0;
            font-size: 16px;
            transition: color 0.3s ease;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .password-toggle:focus {
            outline: none;
        }
        
        /* Icono de validación de contraseña al lado izquierdo del ojo */
        .password-validation-icon {
            position: absolute;
            right: 55px; /* A la izquierda del ojo */
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            z-index: 9;
            transition: all 0.3s ease;
            color: #dc3545; /* Rojo por defecto */
            display: none; /* Oculto por defecto */
        }
        
        .password-validation-icon.show {
            display: block;
        }
        
        .password-validation-icon.valid {
            color: #28a745; /* Verde cuando es válida */
        }
        
        /* Desactivar iconos automáticos de Bootstrap en campos de contraseña */
        input#password.form-control,
        input#confirm_password.form-control {
            background-image: none !important;
            padding-right: 50px !important;
        }
        
        input#password.is-valid,
        input#password.is-invalid,
        input#confirm_password.is-valid,
        input#confirm_password.is-invalid {
            background-image: none !important;
            padding-right: 50px !important;
        }
        
        /* Estilos para el wrapper del campo de contraseña */
        .password-field-wrapper {
            position: relative;
        }
        
        /* Estilos para los requisitos de contraseña como tooltip */
        .password-requirements-tooltip {
            position: absolute;
            right: -320px; /* A la derecha del campo */
            top: 50px; /* Alineado con el campo */
            width: 300px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: none; /* Oculto por defecto */
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s ease;
        }
        
        .password-requirements-tooltip.show {
            display: block;
            opacity: 1;
            transform: translateX(0);
        }
        
        /* Flecha que señala al campo desde la derecha */
        .tooltip-arrow {
            position: absolute;
            left: -8px;
            top: 20px;
            width: 0;
            height: 0;
            border-right: 8px solid rgba(0, 0, 0, 0.95);
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin: 8px 0;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .requirement i {
            margin-right: 10px;
            font-size: 12px;
            width: 12px;
            color: #dc3545; /* Rojo por defecto */
            transition: color 0.3s ease;
        }
        
        .requirement.valid i {
            color: #28a745; /* Verde cuando se cumple */
        }
        
        .requirement.valid span {
            color: #28a745;
            text-decoration: line-through;
        }
        
        .requirement span {
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Responsive para el tooltip */
        @media (max-width: 768px) {
            .password-requirements-tooltip {
                left: 0;
                right: 0;
                top: 60px;
                width: auto;
                margin: 0 10px;
                transform: translateX(0);
            }
            
            .password-requirements-tooltip.show {
                transform: translateX(0);
            }
            
            .tooltip-arrow {
                display: none;
            }
        }
        
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .text-info {
            color: #17a2b8 !important;
            font-size: 0.875rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }
        
        .text-info i.fa-spinner {
            margin-right: 5px;
        }
        
        /* Animación para el spinner */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b7d;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.2);
            border: 1px solid rgba(25, 135, 84, 0.5);
            color: #6bcf7f;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .register-card {
                margin: 10px;
                padding: 30px 20px;
            }
            
            .register-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Botón para volver al inicio -->
    <div class="back-home">
        <a href="index.php" title="Volver al inicio">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <h2><i class="fas fa-user-plus me-2"></i>Registrarse</h2>
                <p>Únete a la comunidad gamer</p>
            </div>

            <!-- Mensajes de error -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de registro -->
            <form method="POST" action="register.php" id="registerForm" autocomplete="off">
                <!-- Nombre y Apellido -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name" class="form-label required-label">Nombre</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   placeholder="Ingresa tu nombre" 
                                   value="<?php echo htmlspecialchars($data['first_name'] ?? ''); ?>" 
                                   autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name" class="form-label required-label">Apellido</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   placeholder="Ingresa tu apellido" 
                                   value="<?php echo htmlspecialchars($data['last_name'] ?? ''); ?>" 
                                   autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label required-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="tu@email.com" 
                           value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" 
                           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                </div>

                <!-- Contraseña -->
                <div class="form-group password-field-wrapper">
                    <label for="password" class="form-label required-label">Contraseña</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Mínimo 8 caracteres" 
                               autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                        <i class="fas fa-times password-validation-icon" id="passwordValidationIcon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                    
                    <!-- Lista de requisitos flotante -->
                    <div class="password-requirements-tooltip" id="passwordRequirements">
                        <div class="tooltip-arrow"></div>
                        <div class="requirement" id="length">
                            <i class="fas fa-times"></i>
                            <span>Mínimo 8 caracteres</span>
                        </div>
                        <div class="requirement" id="uppercase">
                            <i class="fas fa-times"></i>
                            <span>Al menos una mayúscula (A-Z)</span>
                        </div>
                        <div class="requirement" id="lowercase">
                            <i class="fas fa-times"></i>
                            <span>Al menos una minúscula (a-z)</span>
                        </div>
                        <div class="requirement" id="number">
                            <i class="fas fa-times"></i>
                            <span>Al menos un número (0-9)</span>
                        </div>
                        <div class="requirement" id="special">
                            <i class="fas fa-times"></i>
                            <span>Al menos un carácter especial (!@#$%^&*)</span>
                        </div>
                    </div>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="form-group">
                    <label for="confirm_password" class="form-label required-label">Confirmar Contraseña</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirma tu contraseña" 
                               autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                        <i class="fas fa-times password-validation-icon" id="confirmPasswordValidationIcon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>

                <!-- Botón de registro -->
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                </button>
            </form>

            <!-- Enlace de login -->
            <div class="login-link">
                <p>¿Ya tienes una cuenta?</p>
                <a href="login.php">Iniciar Sesión</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/register-validation.js"></script>
</body>
</html>
