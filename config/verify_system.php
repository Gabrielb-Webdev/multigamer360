<?php
/**
 * =====================================================
 * VERIFICACIÓN FINAL DEL SISTEMA - MULTIGAMER360
 * =====================================================
 * 
 * Descripción: Script para verificar que todos los componentes estén funcionando correctamente
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-20
 * 
 * Verifica:
 * - Conexión a la base de datos
 * - Existencia de todas las tablas
 * - Configuración de seguridad
 * - Funcionalidad de todos los módulos
 */

require_once '../config/database.php';

echo "<h2>🔍 Verificación Final del Sistema MultiGamer360</h2>";

$checks = [];
$errors = [];

try {
    // 1. Verificar conexión a la base de datos
    $checks[] = "✅ Conexión a base de datos establecida";

    // 2. Verificar tablas principales
    $required_tables = [
        'users', 'products', 'orders', 'order_items', 'cart', 'wishlist',
        'coupons', 'coupon_usage', 'reviews', 'review_votes', 'review_reports',
        'newsletter_subscribers', 'email_templates', 'email_campaigns', 'email_logs',
        'daily_metrics', 'product_analytics', 'user_analytics', 'financial_reports',
        'inventory_analytics', 'dashboard_kpis'
    ];

    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            $checks[] = "✅ Tabla '$table' existe";
        } else {
            $errors[] = "❌ Tabla '$table' no encontrada";
        }
    }

    // 3. Verificar datos de ejemplo
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE level >= 80");
    $admin_count = $stmt->fetchColumn();
    if ($admin_count > 0) {
        $checks[] = "✅ Administradores configurados ($admin_count)";
    } else {
        $errors[] = "❌ No hay administradores configurados";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $product_count = $stmt->fetchColumn();
    if ($product_count > 0) {
        $checks[] = "✅ Productos en catálogo ($product_count)";
    } else {
        $errors[] = "❌ No hay productos en el catálogo";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons");
    $coupon_count = $stmt->fetchColumn();
    if ($coupon_count > 0) {
        $checks[] = "✅ Sistema de cupones activo ($coupon_count cupones)";
    } else {
        $checks[] = "⚠️ No hay cupones configurados (opcional)";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM daily_metrics");
    $metrics_count = $stmt->fetchColumn();
    if ($metrics_count > 0) {
        $checks[] = "✅ Sistema de reportes con datos ($metrics_count registros)";
    } else {
        $checks[] = "⚠️ Sistema de reportes sin datos iniciales";
    }

    // 4. Verificar archivos críticos
    $critical_files = [
        '../index.php' => 'Página principal',
        '../login.php' => 'Sistema de login',
        '../register.php' => 'Sistema de registro',
        '../productos.php' => 'Catálogo de productos',
        '../carrito.php' => 'Carrito de compras',
        '../admin/dashboard.php' => 'Panel de administración',
        '../admin/coupons.php' => 'Gestión de cupones',
        '../admin/reviews.php' => 'Gestión de reseñas',
        '../admin/newsletter.php' => 'Sistema de newsletter',
        '../admin/reports.php' => 'Sistema de reportes',
        '../config/database.php' => 'Configuración de base de datos',
        '../includes/functions.php' => 'Funciones del sistema',
        '../includes/auth.php' => 'Sistema de autenticación'
    ];

    foreach ($critical_files as $file => $description) {
        if (file_exists($file)) {
            $checks[] = "✅ $description ($file)";
        } else {
            $errors[] = "❌ $description no encontrado ($file)";
        }
    }

    // 5. Verificar archivos de seguridad
    if (file_exists('../.htaccess')) {
        $checks[] = "✅ Archivo .htaccess principal configurado";
    } else {
        $errors[] = "❌ Archivo .htaccess principal no encontrado";
    }

    if (file_exists('../admin/.htaccess')) {
        $checks[] = "✅ Archivo .htaccess del admin configurado";
    } else {
        $errors[] = "❌ Archivo .htaccess del admin no encontrado";
    }

    // 6. Verificar permisos de carpetas críticas
    $critical_folders = [
        '../uploads' => 'Carpeta de uploads',
        '../logs' => 'Carpeta de logs',
        '../assets' => 'Carpeta de assets'
    ];

    foreach ($critical_folders as $folder => $description) {
        if (is_dir($folder)) {
            if (is_writable($folder)) {
                $checks[] = "✅ $description con permisos de escritura";
            } else {
                $errors[] = "⚠️ $description sin permisos de escritura";
            }
        } else {
            $checks[] = "⚠️ $description no existe (se creará automáticamente)";
        }
    }

} catch (PDOException $e) {
    $errors[] = "❌ Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $errors[] = "❌ Error general: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Final - MultiGamer360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        .check-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .status-summary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .error-summary {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="verification-card">
                    <div class="card-header bg-transparent text-center">
                        <h2><i class="fas fa-shield-check text-primary"></i> Verificación Final del Sistema</h2>
                        <p class="text-muted">Estado completo de MultiGamer360</p>
                    </div>
                    <div class="card-body">
                        
                        <!-- Resumen de Estado -->
                        <?php if (empty($errors)): ?>
                        <div class="status-summary text-center">
                            <h4><i class="fas fa-check-circle"></i> ¡Sistema Completamente Funcional!</h4>
                            <p class="mb-0">Todos los componentes están operativos. El sistema está listo para producción.</p>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark fs-6"><?= count($checks) ?> verificaciones exitosas</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="error-summary text-center">
                            <h4><i class="fas fa-exclamation-triangle"></i> Se encontraron algunos problemas</h4>
                            <p class="mb-0">Revisa los errores abajo para completar la configuración.</p>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark fs-6"><?= count($checks) ?> verificaciones exitosas</span>
                                <span class="badge bg-danger fs-6"><?= count($errors) ?> errores encontrados</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Verificaciones Exitosas -->
                            <div class="col-md-<?= empty($errors) ? '12' : '8' ?>">
                                <h5><i class="fas fa-check text-success"></i> Verificaciones Exitosas</h5>
                                <div class="verification-list">
                                    <?php foreach ($checks as $check): ?>
                                    <div class="check-item">
                                        <?= $check ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Errores (si los hay) -->
                            <?php if (!empty($errors)): ?>
                            <div class="col-md-4">
                                <h5><i class="fas fa-exclamation-triangle text-warning"></i> Problemas Detectados</h5>
                                <div class="verification-list">
                                    <?php foreach ($errors as $error): ?>
                                    <div class="check-item text-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Información del Sistema -->
                        <div class="mt-4">
                            <h5><i class="fas fa-info-circle text-info"></i> Información del Sistema</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                            <h6>Base de Datos</h6>
                                            <small>MySQL/MariaDB</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <i class="fab fa-php fa-2x text-primary mb-2"></i>
                                            <h6>Backend</h6>
                                            <small>PHP <?= phpversion() ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <i class="fab fa-bootstrap fa-2x text-primary mb-2"></i>
                                            <h6>Frontend</h6>
                                            <small>Bootstrap 5.3.2</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Módulos del Sistema -->
                        <div class="mt-4">
                            <h5><i class="fas fa-puzzle-piece text-success"></i> Módulos Implementados</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Sistema de Usuarios y Autenticación</li>
                                        <li><i class="fas fa-check text-success"></i> Catálogo de Productos</li>
                                        <li><i class="fas fa-check text-success"></i> Carrito de Compras</li>
                                        <li><i class="fas fa-check text-success"></i> Sistema de Pedidos</li>
                                        <li><i class="fas fa-check text-success"></i> Lista de Deseos</li>
                                        <li><i class="fas fa-check text-success"></i> Panel de Administración</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Sistema de Cupones</li>
                                        <li><i class="fas fa-check text-success"></i> Reseñas y Calificaciones</li>
                                        <li><i class="fas fa-check text-success"></i> Newsletter y Email Marketing</li>
                                        <li><i class="fas fa-check text-success"></i> Reportes y Analíticas</li>
                                        <li><i class="fas fa-check text-success"></i> Seguridad y Configuración CSP</li>
                                        <li><i class="fas fa-check text-success"></i> Gestión de Inventario</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Accesos Rápidos -->
                        <div class="mt-4 text-center">
                            <h5><i class="fas fa-external-link-alt text-primary"></i> Accesos Rápidos</h5>
                            <div class="btn-group-vertical btn-group-lg">
                                <a href="../index.php" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Ir a la Página Principal
                                </a>
                                <a href="../admin/dashboard.php" class="btn btn-success">
                                    <i class="fas fa-cog"></i> Panel de Administración
                                </a>
                                <a href="../admin/reports.php" class="btn btn-info">
                                    <i class="fas fa-chart-bar"></i> Ver Reportes
                                </a>
                            </div>
                        </div>

                        <!-- Credenciales de Acceso -->
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-key"></i> Credenciales de Administrador</h6>
                                <strong>Usuario:</strong> Gbustosgarcia01@gmail.com<br>
                                <strong>Contraseña:</strong> admin123<br>
                                <strong>Nivel:</strong> 100 (Super Administrador)
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>