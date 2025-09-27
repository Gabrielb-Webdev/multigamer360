<?php
// Panel de administración para gestión de usuarios y roles
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Verificar si tiene permisos de administrador (nivel >= 80)
if (!isset($_SESSION['role_level']) || $_SESSION['role_level'] < 80) {
    die('Acceso denegado. Solo administradores pueden acceder a esta sección.');
}

require_once '../config/database.php';
require_once '../config/user_manager.php';

$userManager = new UserManager($pdo);

// Procesar acciones
$message = '';
$error = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_role') {
        $user_id = $_POST['user_id'] ?? 0;
        $new_role_id = $_POST['role_id'] ?? 0;
        
        if ($user_id && $new_role_id) {
            $result = $userManager->updateUserRole($user_id, $new_role_id);
            if ($result['success']) {
                $message = 'Rol actualizado correctamente';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Obtener datos
$users = $userManager->getAllUsers();
$roles = $userManager->getRoles();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - MultiGamer360</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #8B0000;
            --secondary-color: #000000;
            --text-color: #FFFFFF;
            --background-color: #1A1A1A;
            --card-bg: rgba(0, 0, 0, 0.8);
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Roboto', sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), #A50000);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .admin-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .table-dark {
            background-color: var(--card-bg);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #A50000);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #A50000, var(--primary-color));
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-super-admin { background: #dc3545; color: white; }
        .role-admin { background: #fd7e14; color: white; }
        .role-moderator { background: #6f42c1; color: white; }
        .role-vip { background: #20c997; color: white; }
        .role-customer { background: #6c757d; color: white; }
        
        .stats-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-users-cog me-2"></i>Panel de Administración</h1>
                    <p class="mb-0">Gestión de usuarios y roles del sistema</p>
                </div>
                <div class="col-md-6 text-end">
                    <span class="me-3">
                        <i class="fas fa-user-shield me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?> 
                        (<?php echo htmlspecialchars($_SESSION['role_name']); ?>)
                    </span>
                    <a href="../index.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                    <a href="../logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-1"></i>Salir
                    </a>
                </div>
            </div>
            
            <!-- Navegación del Admin -->
            <div class="row mt-3">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <div class="container-fluid">
                            <ul class="navbar-nav me-auto">
                                <li class="nav-item">
                                    <a class="nav-link active" href="dashboard.php">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="coupons.php">
                                        <i class="fas fa-tags me-1"></i>Cupones
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="reviews.php">
                                        <i class="fas fa-star me-1"></i>Reseñas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="newsletter.php">
                                        <i class="fas fa-envelope me-1"></i>Newsletter
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="reports.php">
                                        <i class="fas fa-chart-bar me-1"></i>Reportes
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($users); ?></div>
                    <div>Total Usuarios</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count(array_filter($users, fn($u) => $u['level'] >= 80)); ?></div>
                    <div>Administradores</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count(array_filter($users, fn($u) => $u['level'] === 10)); ?></div>
                    <div>Clientes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($roles); ?></div>
                    <div>Roles Disponibles</div>
                </div>
            </div>
        </div>

        <!-- Gestión de Usuarios -->
        <div class="row">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header bg-transparent border-bottom border-secondary">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Gestión de Usuarios
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Nombre</th>
                                        <th>Rol Actual</th>
                                        <th>Cambiar Rol</th>
                                        <th>Registro</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $role_class = '';
                                                switch($user['level']) {
                                                    case 100: $role_class = 'role-super-admin'; break;
                                                    case 80: $role_class = 'role-admin'; break;
                                                    case 50: $role_class = 'role-moderator'; break;
                                                    case 20: $role_class = 'role-vip'; break;
                                                    default: $role_class = 'role-customer'; break;
                                                }
                                                ?>
                                                <span class="role-badge <?php echo $role_class; ?>">
                                                    <?php echo htmlspecialchars($user['role_name'] ?? 'Sin rol'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="input-group input-group-sm" style="max-width: 200px;">
                                                            <select name="role_id" class="form-select form-select-sm">
                                                                <?php foreach ($roles as $role): ?>
                                                                    <option value="<?php echo $role['id']; ?>">
                                                                        <?php echo htmlspecialchars($role['name']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-save"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <small class="text-muted">Tu cuenta</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Roles -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header bg-transparent border-bottom border-secondary">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Roles del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($roles as $role): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?php echo htmlspecialchars($role['name']); ?>
                                                <span class="badge bg-primary"><?php echo $role['level']; ?></span>
                                            </h6>
                                            <p class="card-text small">
                                                <?php echo htmlspecialchars($role['description']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>