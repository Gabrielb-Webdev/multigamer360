<?php
$page_title = 'Configuraciones del Sistema';
$page_actions = '<button class="btn btn-success" onclick="saveAllSettings()"><i class="fas fa-save"></i> Guardar Todas las Configuraciones</button>';

require_once 'inc/header.php';

// Verificar permisos
if (!hasPermission('settings', 'read')) {
    $_SESSION['error'] = 'No tiene permisos para ver configuraciones';
    header('Location: index.php');
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings' && hasPermission('settings', 'update')) {
        try {
            $settings = $_POST['settings'];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
                $stmt->execute([$key, $value]);
            }
            
            $_SESSION['success'] = 'Configuraciones actualizadas exitosamente';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al actualizar configuraciones: ' . $e->getMessage();
        }
        
        header('Location: settings.php');
        exit;
    }
}

// Obtener configuraciones actuales
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Configuraciones por defecto si no existen
$default_settings = [
    'site_name' => 'MultiGamer360',
    'site_description' => 'Tu tienda gaming online',
    'site_email' => 'info@multigamer360.com',
    'site_phone' => '+1 (555) 123-4567',
    'site_address' => '123 Gaming Street, Tech City',
    'currency' => 'USD',
    'currency_symbol' => '$',
    'tax_rate' => '8.5',
    'shipping_cost' => '9.99',
    'free_shipping_threshold' => '100.00',
    'payment_paypal_enabled' => '1',
    'payment_stripe_enabled' => '1',
    'payment_cash_enabled' => '1',
    'email_smtp_host' => 'smtp.gmail.com',
    'email_smtp_port' => '587',
    'email_smtp_username' => '',
    'email_smtp_password' => '',
    'social_facebook' => '',
    'social_twitter' => '',
    'social_instagram' => '',
    'social_youtube' => '',
    'seo_meta_title' => 'MultiGamer360 - Tu tienda gaming online',
    'seo_meta_description' => 'Los mejores videojuegos, consolas y accesorios gaming al mejor precio',
    'seo_meta_keywords' => 'videojuegos, gaming, consolas, PC gaming, accesorios',
    'maintenance_mode' => '0',
    'user_registration_enabled' => '1',
    'reviews_auto_approve' => '0',
    'inventory_low_stock_alert' => '5'
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<div class="container-fluid">
    <!-- Pestañas de configuración -->
    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                <i class="fas fa-cog"></i> General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                <i class="fas fa-credit-card"></i> Pagos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab">
                <i class="fas fa-shipping-fast"></i> Envíos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                <i class="fas fa-envelope"></i> Email
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">
                <i class="fas fa-search"></i> SEO
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                <i class="fas fa-share-alt"></i> Redes Sociales
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">
                <i class="fas fa-tools"></i> Avanzado
            </button>
        </li>
    </ul>

    <form id="settingsForm" method="POST">
        <input type="hidden" name="action" value="update_settings">
        
        <div class="tab-content" id="settingsTabsContent">
            <!-- Configuraciones Generales -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información del Sitio</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Sitio</label>
                                    <input type="text" class="form-control" name="settings[site_name]" value="<?= htmlspecialchars($settings['site_name']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" name="settings[site_description]" rows="3"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email de Contacto</label>
                                    <input type="email" class="form-control" name="settings[site_email]" value="<?= htmlspecialchars($settings['site_email']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="settings[site_phone]" value="<?= htmlspecialchars($settings['site_phone']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control" name="settings[site_address]" rows="2"><?= htmlspecialchars($settings['site_address']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-money-bill"></i> Configuración de Moneda</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Moneda</label>
                                    <select class="form-control" name="settings[currency]">
                                        <option value="USD" <?= $settings['currency'] === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                                        <option value="EUR" <?= $settings['currency'] === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                        <option value="COP" <?= $settings['currency'] === 'COP' ? 'selected' : '' ?>>Peso Colombiano (COP)</option>
                                        <option value="MXN" <?= $settings['currency'] === 'MXN' ? 'selected' : '' ?>>Peso Mexicano (MXN)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Símbolo de Moneda</label>
                                    <input type="text" class="form-control" name="settings[currency_symbol]" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" maxlength="5">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tasa de Impuesto (%)</label>
                                    <input type="number" class="form-control" name="settings[tax_rate]" value="<?= htmlspecialchars($settings['tax_rate']) ?>" step="0.1" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuraciones de Pagos -->
            <div class="tab-pane fade" id="payments" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fab fa-paypal"></i> PayPal</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[payment_paypal_enabled]" value="1" <?= $settings['payment_paypal_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Habilitar PayPal</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" class="form-control" name="settings[paypal_client_id]" value="<?= htmlspecialchars($settings['paypal_client_id'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Client Secret</label>
                                    <input type="password" class="form-control" name="settings[paypal_client_secret]" value="<?= htmlspecialchars($settings['paypal_client_secret'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fab fa-stripe"></i> Stripe</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[payment_stripe_enabled]" value="1" <?= $settings['payment_stripe_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Habilitar Stripe</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Publishable Key</label>
                                    <input type="text" class="form-control" name="settings[stripe_publishable_key]" value="<?= htmlspecialchars($settings['stripe_publishable_key'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Secret Key</label>
                                    <input type="password" class="form-control" name="settings[stripe_secret_key]" value="<?= htmlspecialchars($settings['stripe_secret_key'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Pago en Efectivo</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[payment_cash_enabled]" value="1" <?= $settings['payment_cash_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Habilitar Pago en Efectivo</label>
                                </div>
                                <p class="text-muted small">Permite a los clientes pagar en efectivo al recibir su pedido.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuraciones de Envío -->
            <div class="tab-pane fade" id="shipping" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shipping-fast"></i> Costos de Envío</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Costo de Envío Estándar</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= htmlspecialchars($settings['currency_symbol']) ?></span>
                                        <input type="number" class="form-control" name="settings[shipping_cost]" value="<?= htmlspecialchars($settings['shipping_cost']) ?>" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Umbral para Envío Gratis</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= htmlspecialchars($settings['currency_symbol']) ?></span>
                                        <input type="number" class="form-control" name="settings[free_shipping_threshold]" value="<?= htmlspecialchars($settings['free_shipping_threshold']) ?>" step="0.01" min="0">
                                    </div>
                                    <div class="form-text">Pedidos por encima de este monto tendrán envío gratis</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock"></i> Tiempos de Entrega</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Tiempo de Procesamiento (días)</label>
                                    <input type="number" class="form-control" name="settings[processing_time]" value="<?= htmlspecialchars($settings['processing_time'] ?? '1') ?>" min="1" max="30">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tiempo de Entrega Estándar (días)</label>
                                    <input type="number" class="form-control" name="settings[delivery_time_standard]" value="<?= htmlspecialchars($settings['delivery_time_standard'] ?? '3-5') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tiempo de Entrega Express (días)</label>
                                    <input type="number" class="form-control" name="settings[delivery_time_express]" value="<?= htmlspecialchars($settings['delivery_time_express'] ?? '1-2') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuraciones de Email -->
            <div class="tab-pane fade" id="email" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-server"></i> Configuración SMTP</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Servidor SMTP</label>
                                    <input type="text" class="form-control" name="settings[email_smtp_host]" value="<?= htmlspecialchars($settings['email_smtp_host']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Puerto</label>
                                    <input type="number" class="form-control" name="settings[email_smtp_port]" value="<?= htmlspecialchars($settings['email_smtp_port']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Usuario SMTP</label>
                                    <input type="text" class="form-control" name="settings[email_smtp_username]" value="<?= htmlspecialchars($settings['email_smtp_username']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contraseña SMTP</label>
                                    <input type="password" class="form-control" name="settings[email_smtp_password]" value="<?= htmlspecialchars($settings['email_smtp_password']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-envelope-open"></i> Configuración de Emails</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Email del Remitente</label>
                                    <input type="email" class="form-control" name="settings[email_from]" value="<?= htmlspecialchars($settings['email_from'] ?? $settings['site_email']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Remitente</label>
                                    <input type="text" class="form-control" name="settings[email_from_name]" value="<?= htmlspecialchars($settings['email_from_name'] ?? $settings['site_name']) ?>">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[email_notifications_enabled]" value="1" <?= ($settings['email_notifications_enabled'] ?? '1') ? 'checked' : '' ?>>
                                    <label class="form-check-label">Habilitar Notificaciones por Email</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuraciones SEO -->
            <div class="tab-pane fade" id="seo" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-search"></i> Optimización para Motores de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Título Meta (SEO)</label>
                                    <input type="text" class="form-control" name="settings[seo_meta_title]" value="<?= htmlspecialchars($settings['seo_meta_title']) ?>" maxlength="60">
                                    <div class="form-text">Máximo 60 caracteres</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción Meta</label>
                                    <textarea class="form-control" name="settings[seo_meta_description]" rows="3" maxlength="160"><?= htmlspecialchars($settings['seo_meta_description']) ?></textarea>
                                    <div class="form-text">Máximo 160 caracteres</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Palabras Clave</label>
                                    <textarea class="form-control" name="settings[seo_meta_keywords]" rows="3"><?= htmlspecialchars($settings['seo_meta_keywords']) ?></textarea>
                                    <div class="form-text">Separadas por comas</div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[seo_sitemap_enabled]" value="1" <?= ($settings['seo_sitemap_enabled'] ?? '1') ? 'checked' : '' ?>>
                                    <label class="form-check-label">Generar Sitemap Automáticamente</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redes Sociales -->
            <div class="tab-pane fade" id="social" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-share-alt"></i> Enlaces de Redes Sociales</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                                    <input type="url" class="form-control" name="settings[social_facebook]" value="<?= htmlspecialchars($settings['social_facebook']) ?>" placeholder="https://facebook.com/tupagina">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-twitter"></i> Twitter</label>
                                    <input type="url" class="form-control" name="settings[social_twitter]" value="<?= htmlspecialchars($settings['social_twitter']) ?>" placeholder="https://twitter.com/tuusuario">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                                    <input type="url" class="form-control" name="settings[social_instagram]" value="<?= htmlspecialchars($settings['social_instagram']) ?>" placeholder="https://instagram.com/tuusuario">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-youtube"></i> YouTube</label>
                                    <input type="url" class="form-control" name="settings[social_youtube]" value="<?= htmlspecialchars($settings['social_youtube']) ?>" placeholder="https://youtube.com/tucanal">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuraciones Avanzadas -->
            <div class="tab-pane fade" id="advanced" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tools"></i> Configuraciones del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" value="1" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Modo Mantenimiento</label>
                                    <div class="form-text">Desactiva el sitio para visitantes (no afecta administradores)</div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[user_registration_enabled]" value="1" <?= $settings['user_registration_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Permitir Registro de Usuarios</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[reviews_auto_approve]" value="1" <?= $settings['reviews_auto_approve'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Auto-aprobar Reseñas</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-warehouse"></i> Configuraciones de Inventario</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Alerta de Stock Bajo</label>
                                    <input type="number" class="form-control" name="settings[inventory_low_stock_alert]" value="<?= htmlspecialchars($settings['inventory_low_stock_alert']) ?>" min="1">
                                    <div class="form-text">Notificar cuando el stock sea menor o igual a este número</div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[inventory_auto_update]" value="1" <?= ($settings['inventory_auto_update'] ?? '1') ? 'checked' : '' ?>>
                                    <label class="form-check-label">Actualización Automática de Inventario</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> Guardar Todas las Configuraciones
            </button>
            <button type="button" class="btn btn-secondary btn-lg" onclick="location.reload()">
                <i class="fas fa-undo"></i> Cancelar
            </button>
        </div>
    </form>
</div>

<script>
function saveAllSettings() {
    document.getElementById('settingsForm').submit();
}

// Validación en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar email
        const emailInputs = form.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            if (input.value && !isValidEmail(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        // Validar URLs
        const urlInputs = form.querySelectorAll('input[type="url"]');
        urlInputs.forEach(input => {
            if (input.value && !isValidUrl(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showAlert('Por favor, corrija los errores en el formulario', 'error');
        }
    });
});

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}
</script>

<?php require_once 'inc/footer.php'; ?>