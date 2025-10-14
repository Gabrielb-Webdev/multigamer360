<?php
require_once 'inc/auth.php';

// $userManager ya está disponible desde auth.php

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_campaign') {
        // Crear nueva campaña
        $name = trim($_POST['campaign_name']);
        $subject = trim($_POST['subject']);
        $template_id = $_POST['template_id'];
        $recipient_type = $_POST['recipient_type'];
        
        if (!empty($name) && !empty($subject) && !empty($template_id)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO email_campaigns (name, subject, template_id, recipient_type, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([$name, $subject, $template_id, $recipient_type, $_SESSION['user_id']]);
                
                $campaign_id = $pdo->lastInsertId();
                $success_msg = "Campaña creada exitosamente (ID: $campaign_id)";
            } catch (Exception $e) {
                $error_msg = "Error al crear campaña: " . $e->getMessage();
            }
        } else {
            $error_msg = "Todos los campos son requeridos";
        }
    }
    elseif ($action === 'add_subscriber') {
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO newsletter_subscribers (email, first_name, last_name, source) 
                    VALUES (?, ?, ?, 'manual')
                    ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name), 
                    last_name = VALUES(last_name),
                    status = 'active'
                ");
                $stmt->execute([$email, $first_name, $last_name]);
                $success_msg = "Suscriptor agregado exitosamente";
            } catch (Exception $e) {
                $error_msg = "Error al agregar suscriptor";
            }
        } else {
            $error_msg = "Email inválido";
        }
    }
    elseif ($action === 'unsubscribe') {
        $subscriber_id = $_POST['subscriber_id'];
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = NOW() WHERE id = ?");
        $stmt->execute([$subscriber_id]);
        $success_msg = "Suscriptor dado de baja";
    }
}

// Obtener estadísticas
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_subscribers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscribers,
        COUNT(CASE WHEN status = 'unsubscribed' THEN 1 END) as unsubscribed,
        COUNT(CASE WHEN DATE(subscribed_at) = CURDATE() THEN 1 END) as today_subscriptions
    FROM newsletter_subscribers
");
$subscriber_stats = $stats_stmt->fetch();

$campaign_stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_campaigns,
        COUNT(CASE WHEN status = 'sent' OR status = 'completed' THEN 1 END) as sent_campaigns,
        COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_campaigns,
        COALESCE(SUM(emails_sent), 0) as total_emails_sent
    FROM email_campaigns
");
$campaign_stats = $campaign_stats_stmt->fetch();

// Obtener suscriptores
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM newsletter_subscribers WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY subscribed_at DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

// Obtener plantillas
$templates_stmt = $pdo->query("SELECT * FROM email_templates WHERE is_active = 1 ORDER BY name");
$templates = $templates_stmt->fetchAll();

// Obtener campañas recientes
$recent_campaigns_stmt = $pdo->query("
    SELECT c.*, u.email as created_by_email 
    FROM email_campaigns c
    LEFT JOIN users u ON c.created_by = u.id
    ORDER BY c.created_at DESC 
    LIMIT 10
");
$recent_campaigns = $recent_campaigns_stmt->fetchAll();

$page_title = "Email Marketing";
require_once 'inc/header.php';
?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'inc/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-envelope me-2"></i>Email Marketing</h1>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                            <i class="fas fa-paper-plane me-1"></i>Nueva Campaña
                        </button>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSubscriberModal">
                            <i class="fas fa-user-plus me-1"></i>Agregar Suscriptor
                        </button>
                    </div>
                </div>

                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo number_format($subscriber_stats['total_subscribers']); ?></div>
                                        <div>Total Suscriptores</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-check fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo number_format($subscriber_stats['active_subscribers']); ?></div>
                                        <div>Activos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-paper-plane fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo number_format($campaign_stats['sent_campaigns']); ?></div>
                                        <div>Campañas Enviadas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-envelope fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo number_format($campaign_stats['total_emails_sent']); ?></div>
                                        <div>Emails Enviados</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="emailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="subscribers-tab" data-bs-toggle="tab" data-bs-target="#subscribers" type="button" role="tab">
                            <i class="fas fa-users me-1"></i>Suscriptores
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns" type="button" role="tab">
                            <i class="fas fa-paper-plane me-1"></i>Campañas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                            <i class="fas fa-file-alt me-1"></i>Plantillas
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="emailTabContent">
                    <!-- Tab Suscriptores -->
                    <div class="tab-pane fade show active" id="subscribers" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-0">Suscriptores (<?php echo count($subscribers); ?>)</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="GET" class="d-flex">
                                            <input type="text" class="form-control me-2" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar...">
                                            <select class="form-select me-2" name="status">
                                                <option value="">Todos</option>
                                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Activos</option>
                                                <option value="unsubscribed" <?php echo $status_filter === 'unsubscribed' ? 'selected' : ''; ?>>Dados de baja</option>
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Email</th>
                                                <th>Nombre</th>
                                                <th>Estado</th>
                                                <th>Fuente</th>
                                                <th>Fecha</th>
                                                <th>Emails</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subscribers as $subscriber): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $name = trim($subscriber['first_name'] . ' ' . $subscriber['last_name']);
                                                        echo $name ?: '<em class="text-muted">Sin nombre</em>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = $subscriber['status'] === 'active' ? 'success' : 'secondary';
                                                        $status_text = $subscriber['status'] === 'active' ? 'Activo' : 'Inactivo';
                                                        ?>
                                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $sources = [
                                                            'website' => 'Sitio web',
                                                            'checkout' => 'Checkout',
                                                            'manual' => 'Manual',
                                                            'import' => 'Importación'
                                                        ];
                                                        echo $sources[$subscriber['source']] ?? $subscriber['source'];
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($subscriber['subscribed_at'])); ?></td>
                                                    <td><?php echo $subscriber['email_count']; ?></td>
                                                    <td>
                                                        <?php if ($subscriber['status'] === 'active'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="unsubscribe">
                                                                <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Dar de baja">
                                                                    <i class="fas fa-user-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($subscribers)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">No se encontraron suscriptores</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Campañas -->
                    <div class="tab-pane fade" id="campaigns" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Campañas Recientes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Asunto</th>
                                                <th>Estado</th>
                                                <th>Destinatarios</th>
                                                <th>Enviados</th>
                                                <th>Fecha</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_campaigns as $campaign): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($campaign['subject']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_classes = [
                                                            'draft' => 'secondary',
                                                            'scheduled' => 'warning',
                                                            'sending' => 'info',
                                                            'sent' => 'success',
                                                            'completed' => 'success'
                                                        ];
                                                        $status_class = $status_classes[$campaign['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($campaign['status']); ?></span>
                                                    </td>
                                                    <td><?php echo number_format($campaign['total_recipients']); ?></td>
                                                    <td><?php echo number_format($campaign['emails_sent']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($campaign['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($recent_campaigns)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">No hay campañas creadas</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Plantillas -->
                    <div class="tab-pane fade" id="templates" role="tabpanel">
                        <div class="row">
                            <?php foreach ($templates as $template): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h6>
                                            <p class="card-text text-muted"><?php echo htmlspecialchars($template['subject']); ?></p>
                                            <span class="badge bg-info"><?php echo ucfirst($template['template_type']); ?></span>
                                            <div class="mt-3">
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Vista previa
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Nueva Campaña -->
    <div class="modal fade" id="createCampaignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Campaña de Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_campaign">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Campaña *</label>
                            <input type="text" class="form-control" name="campaign_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Asunto del Email *</label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Plantilla *</label>
                            <select class="form-select" name="template_id" required>
                                <option value="">Seleccionar plantilla</option>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Destinatarios *</label>
                            <select class="form-select" name="recipient_type" required>
                                <option value="all_subscribers">Todos los suscriptores activos</option>
                                <option value="active_users">Usuarios registrados activos</option>
                                <option value="customers">Solo clientes con compras</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Campaña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Suscriptor -->
    <div class="modal fade" id="addSubscriberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Suscriptor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_subscriber">
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name="first_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" class="form-control" name="last_name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Suscriptor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php require_once 'inc/footer.php'; ?>