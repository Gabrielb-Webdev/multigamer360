<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir sistema de autenticación
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/profile_helpers.php';

// Verificar que el usuario esté logueado
requireLogin();

$user = getCurrentUser();

// Obtener estadísticas adicionales del usuario
$userId = $user['id'];

// Usar las nuevas funciones helper
$userStats = getUserStats($userId, $pdo);
$recentActivity = getRecentActivity($userId, $pdo);

// Obtener último pedido
$stmtLastOrder = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmtLastOrder->execute([$userId]);
$lastOrder = $stmtLastOrder->fetch();

include 'includes/header.php';
?>

<style>
.profile-container {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.profile-card {
    background: rgba(40, 40, 40, 0.95);
    border: 1px solid #444;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    overflow: hidden;
}

.profile-header {
    background: linear-gradient(135deg, #dc3545 0%, #ff4757 100%);
    padding: 2rem;
    text-align: center;
    position: relative;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23fff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.1;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    border: 4px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 1;
}

.profile-avatar i {
    font-size: 3rem;
    color: #dc3545;
}

.profile-name {
    color: white;
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.profile-role {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    display: inline-block;
    position: relative;
    z-index: 1;
}

.profile-content {
    padding: 2rem;
}

.info-section {
    background: rgba(50, 50, 50, 0.5);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #555;
}

.info-title {
    color: #dc3545;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.info-title i {
    margin-right: 0.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.7rem 0;
    border-bottom: 1px solid #444;
    color: #e0e0e0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #bbb;
}

.info-value {
    font-weight: 600;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #dc3545 0%, #c62d3f 100%);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.5s ease;
}

.stat-card:hover::before {
    top: -100%;
    right: -100%;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-btn {
    background: rgba(60, 60, 60, 0.8);
    border: 2px solid #dc3545;
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-weight: 500;
}

.action-btn:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
}

.action-btn i {
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

.status-badge {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.recent-activity {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #444;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #dc3545, #c62d3f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
}

.activity-content {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr auto auto auto;
    gap: 1rem;
    align-items: center;
}

.activity-title {
    color: white;
    font-weight: 600;
}

.activity-status {
    padding: 0.2rem 0.6rem;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid #ffc107;
}

.status-processing {
    background: rgba(0, 123, 255, 0.2);
    color: #007bff;
    border: 1px solid #007bff;
}

.status-shipped {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
    border: 1px solid #6c757d;
}

.status-delivered {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid #28a745;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid #dc3545;
}

.activity-amount {
    color: #dc3545;
    font-weight: 700;
    font-size: 1.1rem;
}

.activity-date {
    color: #bbb;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .profile-content {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
    
    .activity-content {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .activity-status,
    .activity-amount,
    .activity-date {
        justify-self: start;
    }
}

/* Estilos específicos para el dropdown en la página de perfil */
.dropdown-menu.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 10000 !important;
    position: absolute !important;
}

.user-dropdown-menu.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 10000 !important;
    position: absolute !important;
    transform: none !important;
}

/* Asegurar que el header no interfiera con el dropdown */
.header-actions .dropdown {
    position: relative !important;
    z-index: 10001 !important;
}

.header-actions .dropdown-toggle {
    z-index: 10002 !important;
}

/* Evitar interferencias con la animación del perfil */
.profile-container * {
    z-index: auto !important;
}

.profile-card {
    z-index: 1 !important;
}

.profile-header {
    z-index: 2 !important;
}

/* El dropdown debe estar encima de todo y bien posicionado */
#userMenuDropdown {
    position: relative !important;
    z-index: 10003 !important;
}

.user-dropdown-menu {
    z-index: 10004 !important;
    position: absolute !important;
    top: calc(100% + 5px) !important;
    right: 0 !important;
    left: auto !important;
    min-width: 200px !important;
    margin-top: 0 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 8px !important;
    background: rgba(40, 40, 40, 0.95) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
    transform: translateY(0) !important;
}

/* Asegurar que el dropdown container tenga posición relativa */
.header-actions .dropdown {
    position: relative !important;
}

/* Mejorar la apariencia de los items del dropdown */
.user-dropdown-menu .dropdown-item {
    color: #fff !important;
    padding: 10px 15px !important;
    border-radius: 4px !important;
    margin: 2px 5px !important;
    transition: all 0.2s ease !important;
}

.user-dropdown-menu .dropdown-item:hover {
    background: rgba(220, 38, 27, 0.8) !important;
    color: #fff !important;
}

.user-dropdown-menu .dropdown-header {
    color: #dc262b !important;
    font-weight: bold !important;
    padding: 10px 15px 5px !important;
}

.user-dropdown-menu .dropdown-divider {
    border-color: rgba(255, 255, 255, 0.2) !important;
    margin: 5px 0 !important;
}
</style>

<div class="profile-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="profile-card">
                    <!-- Header del perfil -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                        <span class="profile-role">
                            <i class="fas fa-crown me-1"></i><?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>

                    <div class="profile-content">
                        <!-- Estadísticas -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $userStats['total_orders']; ?></div>
                                <div class="stat-label">Pedidos Realizados</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">$<?php echo number_format($userStats['total_spent'], 2); ?></div>
                                <div class="stat-label">Total Gastado</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $userStats['wishlist_count']; ?></div>
                                <div class="stat-label">Productos Favoritos</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $userStats['unique_products']; ?></div>
                                <div class="stat-label">Productos Únicos</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Información Personal -->
                                <div class="info-section">
                                    <h3 class="info-title">
                                        <i class="fas fa-user-circle"></i>Información Personal
                                    </h3>
                                    <div class="info-item">
                                        <span class="info-label">Nombre:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($user['first_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Apellido:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($user['last_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Teléfono:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'No especificado'); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">
                                            <i class="fas fa-calendar-alt text-danger me-1"></i>Fecha de Nacimiento:
                                        </span>
                                        <span class="info-value">
                                            <?php 
                                            if (!empty($user['birth_date'])) {
                                                $birthDate = new DateTime($user['birth_date']);
                                                $today = new DateTime();
                                                $age = $today->diff($birthDate)->y;
                                                
                                                echo $birthDate->format('d/m/Y') . ' <span style="color: #dc3545; font-weight: 500;">(' . $age . ' años)</span>';
                                            } else {
                                                echo '<span style="color: #888;">No especificada</span>';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Información de Cuenta -->
                                <div class="info-section">
                                    <h3 class="info-title">
                                        <i class="fas fa-cog"></i>Información de Cuenta
                                    </h3>
                                    <div class="info-item">
                                        <span class="info-label">Fecha de Registro:</span>
                                        <span class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Rol:</span>
                                        <span class="info-value"><?php echo ucfirst($user['role']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Estado:</span>
                                        <span class="status-badge">Activo</span>
                                    </div>
                                    <?php if ($lastOrder): ?>
                                    <div class="info-item">
                                        <span class="info-label">Último Pedido:</span>
                                        <span class="info-value"><?php echo date('d/m/Y', strtotime($lastOrder['created_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="info-section">
                            <h3 class="info-title">
                                <i class="fas fa-tools"></i>Acciones Disponibles
                            </h3>
                            <div class="action-buttons">
                                <a href="#" class="action-btn" onclick="showEditProfileModal()">
                                    <i class="fas fa-edit"></i>Editar Perfil
                                </a>
                                <a href="#" class="action-btn" onclick="showChangePasswordModal()">
                                    <i class="fas fa-key"></i>Cambiar Contraseña
                                </a>
                                <a href="order_history.php" class="action-btn">
                                    <i class="fas fa-history"></i>Historial de Pedidos
                                </a>
                                <a href="wishlist.php" class="action-btn">
                                    <i class="fas fa-heart"></i>Lista de Deseos
                                </a>
                            </div>
                        </div>

                        <!-- Actividad Reciente -->
                        <?php if (!empty($recentActivity)): ?>
                        <div class="info-section">
                            <h3 class="info-title">
                                <i class="fas fa-clock"></i>Actividad Reciente
                            </h3>
                            <div class="recent-activity">
                                <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Pedido #<?php echo $activity['order_number']; ?></div>
                                        <div class="activity-status status-<?php echo $activity['status']; ?>">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </div>
                                        <div class="activity-amount">$<?php echo number_format($activity['total'], 2); ?></div>
                                        <div class="activity-date"><?php echo date('d/m/Y', strtotime($activity['created_at'])); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Perfil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: #2d2d2d; border: 1px solid #444;">
            <div class="modal-header" style="border-bottom: 1px solid #444;">
                <h5 class="modal-title text-white" id="editProfileModalLabel">
                    <i class="fas fa-edit me-2"></i>Editar Perfil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editFirstName" class="form-label text-white">Nombre</label>
                                <input type="text" class="form-control" id="editFirstName" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                       style="background: #404040; border: 1px solid #555; color: white;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editLastName" class="form-label text-white">Apellido</label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                       style="background: #404040; border: 1px solid #555; color: white;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editEmail" class="form-label text-white">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       style="background: #404040; border: 1px solid #555; color: white;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editPhone" class="form-label text-white">Teléfono</label>
                                <input type="text" class="form-control" id="editPhone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       style="background: #404040; border: 1px solid #555; color: white;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Fecha de Nacimiento</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="editBirthDay" class="form-label text-white-50" style="font-size: 0.9em;">Día</label>
                                <select class="form-control" id="editBirthDay" name="birth_day" 
                                        style="background: #404040; border: 1px solid #555; color: white;">
                                    <option value="">Día</option>
                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo (isset($user['birth_date']) && date('j', strtotime($user['birth_date'])) == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="editBirthMonth" class="form-label text-white-50" style="font-size: 0.9em;">Mes</label>
                                <select class="form-control" id="editBirthMonth" name="birth_month" 
                                        style="background: #404040; border: 1px solid #555; color: white;">
                                    <option value="">Mes</option>
                                    <?php 
                                    $months = [
                                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                    ];
                                    foreach($months as $num => $name): 
                                    ?>
                                        <option value="<?php echo $num; ?>" 
                                                <?php echo (isset($user['birth_date']) && date('n', strtotime($user['birth_date'])) == $num) ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="editBirthYear" class="form-label text-white-50" style="font-size: 0.9em;">Año</label>
                                <select class="form-control" id="editBirthYear" name="birth_year" 
                                        style="background: #404040; border: 1px solid #555; color: white;">
                                    <option value="">Año</option>
                                    <?php 
                                    $currentYear = date('Y');
                                    $maxYear = $currentYear - 15; // Mínimo 15 años
                                    $minYear = $currentYear - 100; // Máximo 100 años
                                    
                                    for($year = $maxYear; $year >= $minYear; $year--): 
                                    ?>
                                        <option value="<?php echo $year; ?>" 
                                                <?php echo (isset($user['birth_date']) && date('Y', strtotime($user['birth_date'])) == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Campo oculto para enviar la fecha completa -->
                        <input type="hidden" id="editBirthDate" name="birth_date" value="<?php echo $user['birth_date'] ?? ''; ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #444;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="saveProfile()">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Cambiar Contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #2d2d2d; border: 1px solid #444;">
            <div class="modal-header" style="border-bottom: 1px solid #444;">
                <h5 class="modal-title text-white" id="changePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Cambiar Contraseña
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label text-white">Contraseña Actual</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required
                                   style="background: #404040; border: 1px solid #555; color: white;">
                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword"
                                    style="border-color: #555; color: #ccc;">
                                <i class="fas fa-eye" id="currentPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label text-white">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" name="new_password" required
                                   style="background: #404040; border: 1px solid #555; color: white;">
                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword"
                                    style="border-color: #555; color: #ccc;">
                                <i class="fas fa-eye" id="newPasswordIcon"></i>
                            </button>
                        </div>
                        <div class="form-text text-muted">
                            Mínimo 8 caracteres. No puedes usar contraseñas anteriores.
                        </div>
                        <div id="passwordStrength" class="mt-2" style="display: none;">
                            <div class="progress" style="height: 5px; background: #333;">
                                <div id="strengthBar" class="progress-bar" style="width: 0%; transition: all 0.3s ease;"></div>
                            </div>
                            <small id="strengthText" class="text-muted"></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label text-white">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required
                                   style="background: #404040; border: 1px solid #555; color: white;">
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword"
                                    style="border-color: #555; color: #ccc;">
                                <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="form-text" style="display: none;"></div>
                    </div>
                    <div id="passwordValidationMessage" class="alert" style="display: none; background: #dc262b; border: none; color: white;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="validationText"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #444;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="savePassword()">
                    <i class="fas fa-save me-1"></i>Cambiar Contraseña
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales para los modales */
.modal.show {
    display: block !important;
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

.modal-open {
    overflow: hidden;
}

.action-btn {
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: rgba(139, 0, 0, 0.2) !important;
    transform: translateY(-2px);
}
</style>

<script>
function showEditProfileModal() {
    console.log('Abriendo modal de editar perfil...');
    try {
        if (typeof window.bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
            modal.show();
        } else {
            // Fallback manual
            const modalEl = document.getElementById('editProfileModal');
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            modalEl.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            
            // Crear backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'editProfileBackdrop';
            document.body.appendChild(backdrop);
        }
    } catch (error) {
        console.error('Error abriendo modal:', error);
    }
}

function showChangePasswordModal() {
    console.log('Abriendo modal de cambiar contraseña...');
    try {
        if (typeof window.bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        } else {
            // Fallback manual
            const modalEl = document.getElementById('changePasswordModal');
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            modalEl.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            
            // Crear backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'changePasswordBackdrop';
            document.body.appendChild(backdrop);
        }
        
        // Inicializar funcionalidades del modal
        initPasswordModal();
    } catch (error) {
        console.error('Error abriendo modal:', error);
    }
}

// Inicializar funcionalidades del modal de contraseña
function initPasswordModal() {
    // Configurar botones de ojo para mostrar/ocultar contraseñas
    setupPasswordToggle('toggleCurrentPassword', 'currentPassword', 'currentPasswordIcon');
    setupPasswordToggle('toggleNewPassword', 'newPassword', 'newPasswordIcon');
    setupPasswordToggle('toggleConfirmPassword', 'confirmPassword', 'confirmPasswordIcon');
    
    // Configurar validación en tiempo real
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    newPasswordInput.addEventListener('input', validateNewPassword);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    
    // Limpiar el formulario
    document.getElementById('changePasswordForm').reset();
    document.getElementById('passwordValidationMessage').style.display = 'none';
    document.getElementById('passwordStrength').style.display = 'none';
    document.getElementById('passwordMatch').style.display = 'none';
}

// Configurar toggle de visibilidad de contraseña
function setupPasswordToggle(buttonId, inputId, iconId) {
    const button = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    // Remover listeners anteriores
    button.replaceWith(button.cloneNode(true));
    const newButton = document.getElementById(buttonId);
    
    newButton.addEventListener('click', function() {
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        
        // Cambiar icono
        if (type === 'text') {
            document.getElementById(iconId).className = 'fas fa-eye-slash';
        } else {
            document.getElementById(iconId).className = 'fas fa-eye';
        }
    });
}

// Validar nueva contraseña
function validateNewPassword() {
    const password = document.getElementById('newPassword').value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const strengthContainer = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        strengthContainer.style.display = 'none';
        return;
    }
    
    strengthContainer.style.display = 'block';
    
    // Calcular fortaleza
    let strength = 0;
    let requirements = [];
    
    if (password.length >= 8) {
        strength += 20;
    } else {
        requirements.push('al menos 8 caracteres');
    }
    
    if (/[a-z]/.test(password)) strength += 20;
    else requirements.push('minúsculas');
    
    if (/[A-Z]/.test(password)) strength += 20;
    else requirements.push('mayúsculas');
    
    if (/[0-9]/.test(password)) strength += 20;
    else requirements.push('números');
    
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;
    else requirements.push('símbolos');
    
    // Actualizar barra de progreso
    strengthBar.style.width = strength + '%';
    
    if (strength < 40) {
        strengthBar.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Débil - Falta: ' + requirements.join(', ');
        strengthText.style.color = '#dc3545';
    } else if (strength < 80) {
        strengthBar.style.backgroundColor = '#ffc107';
        strengthText.textContent = 'Media - Falta: ' + requirements.join(', ');
        strengthText.style.color = '#ffc107';
    } else {
        strengthBar.style.backgroundColor = '#28a745';
        strengthText.textContent = 'Fuerte';
        strengthText.style.color = '#28a745';
    }
    
    // Validar confirmación también
    validatePasswordMatch();
}

// Validar coincidencia de contraseñas
function validatePasswordMatch() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword.length === 0) {
        matchDiv.style.display = 'none';
        return;
    }
    
    matchDiv.style.display = 'block';
    
    if (newPassword === confirmPassword) {
        matchDiv.textContent = '✓ Las contraseñas coinciden';
        matchDiv.style.color = '#28a745';
    } else {
        matchDiv.textContent = '✗ Las contraseñas no coinciden';
        matchDiv.style.color = '#dc3545';
    }
}

// Función para cerrar modales manualmente
function closeModal(modalId) {
    const modalEl = document.getElementById(modalId);
    modalEl.style.display = 'none';
    modalEl.classList.remove('show');
    modalEl.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    
    // Remover backdrop
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Funciones para mostrar modales de notificación
function showSuccessModal(title, message, callback = null) {
    document.getElementById('successTitle').textContent = title;
    document.getElementById('successMessage').textContent = message;
    
    const modal = document.getElementById('successModal');
    if (typeof window.bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Si hay callback, ejecutarlo cuando se cierre
        if (callback) {
            modal.addEventListener('hidden.bs.modal', callback, { once: true });
        }
    } else {
        // Fallback manual
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function showErrorModal(title, message) {
    document.getElementById('errorTitle').textContent = title;
    document.getElementById('errorMessage').textContent = message;
    
    const modal = document.getElementById('errorModal');
    if (typeof window.bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        // Fallback manual
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function showInfoModal(title, message) {
    document.getElementById('infoTitle').textContent = title;
    document.getElementById('infoMessage').textContent = message;
    
    const modal = document.getElementById('infoModal');
    if (typeof window.bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        // Fallback manual
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function saveProfile() {
    console.log('Guardando perfil...');
    const form = document.getElementById('editProfileForm');
    
    if (!form) {
        console.error('Formulario no encontrado');
        showErrorModal('Error', 'Formulario no encontrado. Por favor, recarga la página e intenta nuevamente.');
        return;
    }
    
    const formData = new FormData(form);
    
    // Debug - mostrar datos que se envían
    console.log('FormData entries:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Verificar que los campos requeridos estén presentes
    const firstName = formData.get('first_name');
    const lastName = formData.get('last_name');
    const email = formData.get('email');
    
    if (!firstName || !lastName || !email) {
        showErrorModal('Campos Obligatorios', 'Por favor, completa todos los campos obligatorios: Nombre, Apellido y Email.');
        return;
    }
    
    // Validar y combinar fecha de nacimiento si se proporcionaron los campos
    const birthDay = formData.get('birth_day');
    const birthMonth = formData.get('birth_month');
    const birthYear = formData.get('birth_year');
    
    if (birthDay || birthMonth || birthYear) {
        // Si se proporcionó algún campo de fecha, todos deben estar completos
        if (!birthDay || !birthMonth || !birthYear) {
            showErrorModal('Fecha Incompleta', 'Si deseas actualizar tu fecha de nacimiento, por favor selecciona día, mes y año.');
            return;
        }
        
        // Validar que la fecha sea válida
        const birthDate = new Date(birthYear, birthMonth - 1, birthDay);
        const today = new Date();
        const minAge = new Date();
        minAge.setFullYear(today.getFullYear() - 15);
        
        // Verificar que la fecha no sea futura
        if (birthDate > today) {
            showErrorModal('Fecha Inválida', 'La fecha de nacimiento no puede ser posterior a hoy.');
            return;
        }
        
        // Verificar que tenga al menos 15 años
        if (birthDate > minAge) {
            showErrorModal('Edad Mínima', 'Debes tener al menos 15 años para usar esta plataforma.');
            return;
        }
        
        // Verificar que la fecha sea realista (no más de 100 años)
        const maxAge = new Date();
        maxAge.setFullYear(today.getFullYear() - 100);
        if (birthDate < maxAge) {
            showErrorModal('Fecha Inválida', 'Por favor, verifica que la fecha de nacimiento sea correcta.');
            return;
        }
        
        // Si la validación pasa, actualizar el campo hidden con la fecha formateada
        const formattedDate = birthYear + '-' + 
                             (birthMonth.toString().padStart(2, '0')) + '-' + 
                             (birthDay.toString().padStart(2, '0'));
        
        // Actualizar el campo hidden
        const hiddenDateField = document.getElementById('editBirthDate');
        if (hiddenDateField) {
            hiddenDateField.value = formattedDate;
        }
        
        // Eliminar los campos individuales del FormData y agregar la fecha combinada
        formData.delete('birth_day');
        formData.delete('birth_month');  
        formData.delete('birth_year');
        formData.set('birth_date', formattedDate);
        
        console.log('Fecha de nacimiento procesada:', formattedDate);
    } else {
        // Si no se proporcionaron campos de fecha, asegurar que no se envíen
        formData.delete('birth_day');
        formData.delete('birth_month');
        formData.delete('birth_year');
    }
    
    // Debug final - mostrar datos que se envían después del procesamiento
    console.log('FormData final que se enviará:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    console.log('Enviando petición a ajax/update-profile.php...');
    
    fetch('ajax/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Log del contenido de respuesta para debugging
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                throw new Error('Respuesta no es JSON válido: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Cerrar modal de edición primero
            try {
                if (typeof window.bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                    if (modal) modal.hide();
                } else {
                    closeModal('editProfileModal');
                }
            } catch (error) {
                console.log('Error cerrando modal:', error);
            }
            
            // Mostrar modal de éxito
            showSuccessModal(
                '¡Perfil Actualizado!', 
                'Tus datos han sido guardados correctamente. La página se actualizará en unos segundos.',
                () => {
                    setTimeout(() => location.reload(), 1500);
                }
            );
        } else {
            showErrorModal('Error al Actualizar', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Error de Conexión', 'No se pudo conectar con el servidor. Por favor, verifica tu conexión e intenta nuevamente.');
    });
}

function savePassword() {
    console.log('Cambiando contraseña...');
    const form = document.getElementById('changePasswordForm');
    
    if (!form) {
        console.error('Formulario de contraseña no encontrado');
        showErrorModal('Error', 'Formulario no encontrado. Por favor, recarga la página e intenta nuevamente.');
        return;
    }
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validaciones del frontend
    if (!currentPassword) {
        showErrorModal('Campo Requerido', 'Por favor, ingresa tu contraseña actual.');
        return;
    }
    
    if (!newPassword) {
        showErrorModal('Campo Requerido', 'Por favor, ingresa tu nueva contraseña.');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showErrorModal('Contraseñas no Coinciden', 'Las contraseñas que ingresaste no son iguales. Por favor, verifícalas e intenta nuevamente.');
        return;
    }
    
    if (newPassword.length < 8) {
        showErrorModal('Contraseña Muy Corta', 'Tu nueva contraseña debe tener al menos 8 caracteres para garantizar la seguridad de tu cuenta.');
        return;
    }
    
    // Validar fortaleza de contraseña
    const strengthScore = calculatePasswordStrength(newPassword);
    if (strengthScore < 60) {
        showErrorModal('Contraseña Débil', 'Tu contraseña debe ser más fuerte. Incluye mayúsculas, minúsculas, números y símbolos.');
        return;
    }
    
    // Comprobar si la nueva contraseña es igual a la actual
    if (currentPassword === newPassword) {
        showPasswordValidationError('No puedes usar tu contraseña actual como nueva contraseña.');
        return;
    }
    
    const formData = new FormData(form);
    
    console.log('Enviando datos de cambio de contraseña...');
    
    fetch('ajax/change-password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                throw new Error('Respuesta no es JSON válido: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Cerrar modal de cambio de contraseña
            try {
                if (typeof window.bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                    if (modal) modal.hide();
                } else {
                    closeModal('changePasswordModal');
                }
            } catch (error) {
                console.log('Error cerrando modal:', error);
            }
            
            // Mostrar modal de éxito
            showSuccessModal(
                '¡Contraseña Actualizada!', 
                'Tu contraseña ha sido cambiada exitosamente. Tu cuenta ahora es más segura.'
            );
            
            form.reset();
            // Ocultar mensajes de validación
            document.getElementById('passwordValidationMessage').style.display = 'none';
            document.getElementById('passwordStrength').style.display = 'none';
            document.getElementById('passwordMatch').style.display = 'none';
        } else {
            // Manejar errores específicos
            if (data.message.includes('contraseña anterior') || data.message.includes('password history')) {
                showPasswordValidationError('Esta contraseña ya fue utilizada anteriormente. Por favor, elige una contraseña diferente.');
            } else {
                showErrorModal('Error al Cambiar Contraseña', data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Error de Conexión', 'No se pudo cambiar la contraseña. Por favor, verifica tu conexión e intenta nuevamente.');
    });
}

// Calcular fortaleza de contraseña
function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 20;
    if (/[a-z]/.test(password)) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 20;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;
    
    return strength;
}

// Mostrar error de validación de contraseña
function showPasswordValidationError(message) {
    const validationDiv = document.getElementById('passwordValidationMessage');
    const validationText = document.getElementById('validationText');
    
    validationText.textContent = message;
    validationDiv.style.display = 'block';
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        validationDiv.style.display = 'none';
    }, 5000);
}

// Agregar event listeners para cerrar modales
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners para botones de cerrar
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            if (typeof window.bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                if (modal) modal.hide();
            } else {
                closeModal(modalId);
            }
        });
    });
    
    // Event listener para backdrop clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            document.body.classList.remove('modal-open');
            e.target.remove();
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                modal.style.display = 'none';
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            });
        }
    });
});
</script>

<script>
// JavaScript para la página de perfil - Animaciones
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile page loaded');
    
    // Animaciones del perfil
    const profileCard = document.querySelector('.profile-card');
    const sections = document.querySelectorAll('.info-section');
    
    // Animación de entrada para la tarjeta principal
    if (profileCard) {
        profileCard.style.opacity = '0';
        profileCard.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            profileCard.style.transition = 'all 0.6s ease';
            profileCard.style.opacity = '1';
            profileCard.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Animaciones escalonadas para las secciones
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            section.style.transition = 'all 0.5s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, 300 + (index * 100));
    });
});
</script>

<!-- Modales de Notificación -->
<!-- Modal de Éxito -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%); 
            border: 2px solid #dc3545; 
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(220, 53, 69, 0.3);
        ">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <div style="
                        background: linear-gradient(135deg, #dc3545, #ff4757);
                        width: 80px;
                        height: 80px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto;
                        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
                    ">
                        <i class="fas fa-check" style="font-size: 2.5rem; color: white;"></i>
                    </div>
                </div>
                <h3 class="text-white mb-3" id="successTitle" style="font-weight: 600;">¡Perfil Actualizado!</h3>
                <p class="text-light mb-4" id="successMessage" style="color: #ccc;">Operación completada correctamente</p>
                <button type="button" class="btn btn-lg px-5" data-bs-dismiss="modal" style="
                    background: linear-gradient(135deg, #dc3545, #ff4757);
                    border: none;
                    border-radius: 25px;
                    color: white;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(220, 53, 69, 0.4)';" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(220, 53, 69, 0.3)';">
                    <i class="fas fa-thumbs-up me-2"></i>¡Genial!
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Error -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(135deg, #dc3545, #e74c3c); border: none; border-radius: 15px;">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: white;"></i>
                </div>
                <h3 class="text-white mb-3" id="errorTitle">¡Oops!</h3>
                <p class="text-white mb-4" id="errorMessage">Ha ocurrido un error</p>
                <button type="button" class="btn btn-light btn-lg px-4" data-bs-dismiss="modal">
                    <i class="fas fa-redo me-2"></i>Intentar de nuevo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Información -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(135deg, #17a2b8, #007bff); border: none; border-radius: 15px;">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-info-circle" style="font-size: 4rem; color: white;"></i>
                </div>
                <h3 class="text-white mb-3" id="infoTitle">Información</h3>
                <p class="text-white mb-4" id="infoMessage">Mensaje informativo</p>
                <button type="button" class="btn btn-light btn-lg px-4" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Animaciones para los modales de notificación */
.modal.fade .modal-dialog {
    transform: translate(0, -50px);
    transition: all 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: translate(0, 0);
}

/* Efectos de hover para los botones de los modales */
.modal-content .btn-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
    transition: all 0.2s ease;
}

/* Animación de entrada para los iconos */
@keyframes bounceIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.modal.show .fas {
    animation: bounceIn 0.6s ease-out;
}
</style>

<?php include 'includes/footer.php'; ?>
