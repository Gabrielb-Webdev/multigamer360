<?php
session_start();
require_once '../config/database.php';
require_once '../config/user_manager.php';

// Si ya está logueado y es admin, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    $userManager = new UserManager($pdo);
    $user = $userManager->getUserById($_SESSION['user_id']);
    
    if ($user && $user['role_level'] >= 60) {
        header('Location: index.php');
        exit;
    }
}

$error = '';

// Verificar si hay error por URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'admin_access_denied':
            $error = 'Acceso denegado. Solo los administradores pueden acceder a este panel.';
            break;
        case 'account_inactive':
            $error = 'Su cuenta está inactiva. Contacte al administrador.';
            break;
        case 'insufficient_permissions':
            $error = 'No tiene los permisos necesarios para acceder al panel administrativo.';
            break;
        case 'access_denied':
            $error = 'Acceso no autorizado.';
            break;
        default:
            $error = 'Error de autenticación.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $userManager = new UserManager($pdo);
        $result = $userManager->loginUser($email, $password);
        
        if ($result['success']) {
            $user = $result['user'];
            
            // VERIFICACIÓN ESTRICTA: Solo administradores (nivel >= 80)
            if ($user['role_level'] >= 80) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role_name'];
                
                // Log de acceso exitoso
                error_log("ACCESO ADMIN EXITOSO - Usuario: " . $user['email'] . 
                         " - Rol: " . $user['role_name'] . 
                         " - Nivel: " . $user['role_level'] . 
                         " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . 
                         " - Fecha: " . date('Y-m-d H:i:s'));
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Acceso denegado. Se requieren permisos de administrador (nivel 80+). Su nivel actual: ' . $user['role_level'];
                
                // Log del intento de acceso denegado
                error_log("INTENTO DE LOGIN ADMIN DENEGADO - Usuario: " . $user['email'] . 
                         " - Rol: " . $user['role_name'] . 
                         " - Nivel: " . $user['role_level'] . 
                         " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . 
                         " - Fecha: " . date('Y-m-d H:i:s'));
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - MultiGamer360</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 600;
        }
        
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 1rem rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-shield-alt text-warning"></i>
                <h2>MultiGamer360</h2>
                <p class="text-muted">Panel de Administración</p>
                <div class="alert alert-warning border-0" style="background: rgba(255, 193, 7, 0.1);">
                    <small>
                        <i class="fas fa-lock me-1"></i>
                        <strong>Acceso Restringido:</strong> Solo usuarios con permisos de administrador (Nivel 80+)
                    </small>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'insufficient_permissions'): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-shield-alt me-2"></i>
                    No tiene permisos suficientes para acceder al panel de administración.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required autocomplete="email">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Contraseña
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           required autocomplete="current-password">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <a href="../index.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Volver al sitio web
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>