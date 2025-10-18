<?php
/**
 * =====================================================
 * MULTIGAMER360 - PÁGINA DE LOGIN
 * =====================================================
 * 
 * Descripción: Sistema de autenticación de usuarios
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-16
 * 
 * Funcionalidades:
 * - Autenticación de usuarios con email y contraseña
 * - Validación de credenciales
 * - Gestión de sesiones de usuario
 * - Redirección después del login
 * - Manejo de errores de autenticación
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

// =====================================================
// VERIFICACIÓN DE SESIÓN ACTIVA
// =====================================================

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// =====================================================
// PROCESAMIENTO DEL FORMULARIO
// =====================================================

// Procesar formulario de login
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validar que los campos no estén vacíos
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        try {
            $result = $userManager->loginUser($email, $password);
            
            if ($result['success']) {
                // Guardar datos en sesión
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['email'] = $result['user']['email'];
                $_SESSION['first_name'] = $result['user']['first_name'];
                $_SESSION['last_name'] = $result['user']['last_name'];
                $_SESSION['role'] = $result['user']['role'];
                $_SESSION['role_name'] = $result['user']['role_name'];
                $_SESSION['is_admin'] = $result['user']['is_admin'];
                
                // Redirigir según el rol
                if ($result['user']['is_admin']) {
                    // Admin - ir al panel de administración
                    header('Location: admin/dashboard.php');
                } else {
                    // Usuario normal - ir al inicio
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Error de conexión a la base de datos: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MultiGamer360</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=1.2" rel="stylesheet">
    
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
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--background-color);
        }
        
        .login-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-header h2 {
            color: var(--text-color);
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .login-header p {
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 16px;
            color: var(--text-color);
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.2);
            color: var(--text-color);
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #A50000);
            border: none;
            border-radius: 8px;
            padding: 14px 30px;
            font-weight: bold;
            color: var(--text-color);
            width: 100%;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #A50000, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(139, 0, 0, 0.2);
            color: var(--text-color);
            border-left: 4px solid var(--primary-color);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .register-link p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #A50000;
        }
        
        .back-home {
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 1000;
        }
        
        .back-home a {
            color: rgba(255, 255, 255, 0.6);
            font-size: 24px;
            transition: all 0.3s ease;
            text-decoration: none;
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
        
        .forgot-password {
            text-align: center;
            margin: 20px 0;
        }
        
        .forgot-password a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: var(--primary-color);
        }
        
        .required-label::after {
            content: " *";
            color: var(--primary-color);
        }
        
        /* Password toggle styles */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-card {
                margin: 10px;
                padding: 30px 20px;
            }
            
            .login-header h2 {
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

    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <h2><i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión</h2>
                <p>Accede a tu cuenta gamer</p>
            </div>

            <!-- Mensajes de error -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de login -->
            <form method="POST" action="" id="loginForm">
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label required-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="tu@email.com" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label required-label">Contraseña</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Ingresa tu contraseña" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Botón de login -->
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                </button>
            </form>

            <!-- Enlace de contraseña olvidada -->
            <div class="forgot-password">
                <a href="#" onclick="alert('Función de recuperación en desarrollo');">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <!-- Enlace de registro -->
            <div class="register-link">
                <p>¿No tienes una cuenta?</p>
                <a href="register.php">Crear cuenta nueva</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                
                if (!email || !password) {
                    e.preventDefault();
                    alert('Por favor completa todos los campos requeridos');
                    return false;
                }
            });
        });
        
        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>