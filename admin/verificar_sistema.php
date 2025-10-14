<?php
/**
 * SCRIPT DE VERIFICACIÓN - HOSTINGER
 * Verificar que todo esté configurado correctamente después de la corrección
 * 
 * INSTRUCCIONES:
 * 1. Subir este archivo a /public_html/admin/
 * 2. Acceder desde: https://teal-fish-507993.hostingersite.com/admin/verificar_sistema.php
 * 3. Revisar los resultados
 * 4. ELIMINAR este archivo después de verificar (seguridad)
 */

session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación del Sistema - MultiGamer360</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        .check-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .summary h2 {
            margin-top: 0;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Verificación del Sistema - MultiGamer360</h1>
        
        <div class="info">
            <strong>🌐 Servidor:</strong> <?php echo $_SERVER['HTTP_HOST']; ?><br>
            <strong>📅 Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
            <strong>🐘 PHP:</strong> <?php echo PHP_VERSION; ?>
        </div>

        <h2>📋 Archivos del Sistema</h2>
        
        <?php
        $archivos_criticos = [
            'inc/auth.php' => 'Sistema de autenticación',
            'inc/header.php' => 'Header del panel',
            'inc/sidebar.php' => 'Sidebar de navegación',
            'inc/footer.php' => 'Footer del panel',
            'coupons.php' => 'Gestión de cupones',
            'reviews.php' => 'Gestión de reseñas',
            'reports.php' => 'Reportes y analíticas',
            'newsletter.php' => 'Email marketing',
            'media.php' => 'Gestión de medios (NUEVO)',
        ];

        foreach ($archivos_criticos as $archivo => $descripcion) {
            $existe = file_exists($archivo);
            $legible = $existe ? is_readable($archivo) : false;
            
            if ($existe && $legible) {
                echo "<div class='check-item success'>";
                echo "<span class='icon'>✅</span>";
                echo "<strong>{$descripcion}</strong><br>";
                echo "<small>Archivo: <code>{$archivo}</code> - " . number_format(filesize($archivo)) . " bytes</small>";
                echo "</div>";
            } elseif ($existe && !$legible) {
                echo "<div class='check-item warning'>";
                echo "<span class='icon'>⚠️</span>";
                echo "<strong>{$descripcion}</strong><br>";
                echo "<small>Archivo: <code>{$archivo}</code> - Existe pero no es legible (revisar permisos)</small>";
                echo "</div>";
            } else {
                echo "<div class='check-item error'>";
                echo "<span class='icon'>❌</span>";
                echo "<strong>{$descripcion}</strong><br>";
                echo "<small>Archivo: <code>{$archivo}</code> - NO ENCONTRADO</small>";
                echo "</div>";
            }
        }
        ?>

        <h2>📁 Carpetas del Sistema</h2>
        
        <?php
        $carpetas = [
            '../uploads' => 'Carpeta de archivos multimedia',
            '../config' => 'Carpeta de configuración',
            'inc' => 'Carpeta de includes',
        ];

        foreach ($carpetas as $carpeta => $descripcion) {
            $existe = is_dir($carpeta);
            $escribible = $existe ? is_writable($carpeta) : false;
            $permisos = $existe ? substr(sprintf('%o', fileperms($carpeta)), -4) : 'N/A';
            
            if ($existe && $escribible) {
                echo "<div class='check-item success'>";
                echo "<span class='icon'>✅</span>";
                echo "<strong>{$descripcion}</strong><br>";
                echo "<small>Carpeta: <code>{$carpeta}</code> - Permisos: {$permisos} - Escribible ✓</small>";
                echo "</div>";
            } elseif ($existe && !$escribible) {
                echo "<div class='check-item warning'>";
                echo "<span class='icon'>⚠️</span>";
                echo "<strong>{$descripcion}</strong><br>";
                echo "<small>Carpeta: <code>{$carpeta}</code> - Permisos: {$permisos} - NO ESCRIBIBLE (cambiar a 755 o 775)</small>";
                echo "</div>";
            } else {
                echo "<div class='check-item error'>";
                echo "<span class='icon'>❌</span>";
                echo "<strong>{$descripcion}</strong><br>";
                echo "<small>Carpeta: <code>{$carpeta}</code> - NO ENCONTRADA (crear carpeta)</small>";
                echo "</div>";
            }
        }
        ?>

        <h2>🗄️ Base de Datos</h2>
        
        <?php
        try {
            require_once '../config/database.php';
            
            echo "<div class='check-item success'>";
            echo "<span class='icon'>✅</span>";
            echo "<strong>Conexión a Base de Datos</strong><br>";
            echo "<small>Conexión exitosa</small>";
            echo "</div>";
            
            // Verificar tabla media_files
            $stmt = $pdo->query("SHOW TABLES LIKE 'media_files'");
            $tabla_existe = $stmt->rowCount() > 0;
            
            if ($tabla_existe) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM media_files");
                $total = $stmt->fetch()['total'];
                
                echo "<div class='check-item success'>";
                echo "<span class='icon'>✅</span>";
                echo "<strong>Tabla media_files</strong><br>";
                echo "<small>Tabla existe - Total de archivos: {$total}</small>";
                echo "</div>";
            } else {
                echo "<div class='check-item error'>";
                echo "<span class='icon'>❌</span>";
                echo "<strong>Tabla media_files</strong><br>";
                echo "<small>Tabla NO EXISTE - Ejecutar script SQL de creación</small>";
                echo "</div>";
            }
            
            // Verificar otras tablas críticas
            $tablas_criticas = ['users', 'products', 'orders', 'coupons', 'reviews', 'newsletter_subscribers'];
            $tablas_ok = 0;
            
            foreach ($tablas_criticas as $tabla) {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$tabla}'");
                if ($stmt->rowCount() > 0) {
                    $tablas_ok++;
                }
            }
            
            echo "<div class='check-item success'>";
            echo "<span class='icon'>✅</span>";
            echo "<strong>Tablas del Sistema</strong><br>";
            echo "<small>{$tablas_ok} de " . count($tablas_criticas) . " tablas críticas encontradas</small>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='check-item error'>";
            echo "<span class='icon'>❌</span>";
            echo "<strong>Error de Base de Datos</strong><br>";
            echo "<small>Error: " . htmlspecialchars($e->getMessage()) . "</small>";
            echo "</div>";
        }
        ?>

        <h2>🔐 Configuración PHP</h2>
        
        <?php
        $configuraciones = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
        ];

        foreach ($configuraciones as $config => $valor) {
            echo "<div class='check-item success'>";
            echo "<span class='icon'>ℹ️</span>";
            echo "<strong>{$config}</strong>: {$valor}";
            echo "</div>";
        }
        ?>

        <div class="summary">
            <h2>📊 Resumen</h2>
            <p><strong>Sistema verificado correctamente.</strong></p>
            <p>Si todos los elementos muestran ✅, el sistema está listo para funcionar.</p>
            <p>Si hay ❌ o ⚠️, seguir las instrucciones indicadas para corregir.</p>
            
            <h3>🔗 Páginas a probar:</h3>
            <ul>
                <li><a href="coupons.php" target="_blank">Gestión de Cupones</a></li>
                <li><a href="reviews.php" target="_blank">Gestión de Reseñas</a></li>
                <li><a href="reports.php" target="_blank">Reportes y Analíticas</a></li>
                <li><a href="newsletter.php" target="_blank">Email Marketing</a></li>
                <li><a href="media.php" target="_blank">Gestión de Medios (NUEVO)</a></li>
            </ul>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <strong>⚠️ IMPORTANTE:</strong> Por seguridad, elimina este archivo después de verificar el sistema.
            </div>
        </div>
    </div>
</body>
</html>
