<?php
/**
 * Script de Verificación de Versión
 * 
 * Muestra información sobre la versión actual del código en el servidor
 * URL: https://teal-fish-507993.hostingersite.com/version.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información de Versión - MultiGamer360</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #000;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #00ff00;
        }
        h1 {
            color: #00ff00;
            text-align: center;
        }
        .info-block {
            background: #222;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #00ff00;
        }
        .label {
            color: #00aaff;
            font-weight: bold;
        }
        .value {
            color: #00ff00;
        }
        .command {
            background: #111;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #333;
            overflow-x: auto;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
        }
        a {
            color: #00ff00;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Información de Versión - MultiGamer360</h1>
        
        <div class="info-block">
            <div class="label">📅 Fecha y Hora:</div>
            <div class="value"><?php echo date('Y-m-d H:i:s'); ?></div>
        </div>
        
        <div class="info-block">
            <div class="label">📁 Directorio Raíz:</div>
            <div class="value"><?php echo __DIR__; ?></div>
        </div>
        
        <div class="info-block">
            <div class="label">🐘 Versión de PHP:</div>
            <div class="value"><?php echo PHP_VERSION; ?></div>
        </div>
        
        <div class="info-block">
            <div class="label">🌐 Servidor:</div>
            <div class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></div>
        </div>
        
        <?php
        // Obtener información de Git
        chdir(__DIR__);
        
        // Branch actual
        $branch = trim(shell_exec('git branch --show-current 2>&1'));
        echo "<div class='info-block'>";
        echo "<div class='label'>🌿 Branch Git:</div>";
        echo "<div class='value'>" . htmlspecialchars($branch) . "</div>";
        echo "</div>";
        
        // Último commit
        $last_commit = shell_exec('git log -1 --pretty=format:"%h - %s (%ar)" 2>&1');
        echo "<div class='info-block'>";
        echo "<div class='label'>📝 Último Commit:</div>";
        echo "<div class='command'><pre>" . htmlspecialchars($last_commit) . "</pre></div>";
        echo "</div>";
        
        // Estado de Git
        $git_status = shell_exec('git status --short 2>&1');
        echo "<div class='info-block'>";
        echo "<div class='label'>📊 Estado de Git:</div>";
        echo "<div class='command'><pre>" . htmlspecialchars($git_status ?: 'Sin cambios pendientes') . "</pre></div>";
        echo "</div>";
        
        // Últimos 5 commits
        $recent_commits = shell_exec('git log -5 --oneline 2>&1');
        echo "<div class='info-block'>";
        echo "<div class='label'>📜 Últimos 5 Commits:</div>";
        echo "<div class='command'><pre>" . htmlspecialchars($recent_commits) . "</pre></div>";
        echo "</div>";
        
        // Verificar archivos modificados recientemente
        echo "<div class='info-block'>";
        echo "<div class='label'>⏰ Archivos Modificados (últimos 10):</div>";
        echo "<div class='command'><pre>";
        $modified_files = shell_exec('ls -lt | head -10 2>&1');
        echo htmlspecialchars($modified_files);
        echo "</pre></div>";
        echo "</div>";
        
        // Versiones de archivos clave
        echo "<div class='info-block'>";
        echo "<div class='label'>📄 Versiones de Assets:</div>";
        echo "<div class='command'>";
        
        // Verificar header.php
        $header_content = @file_get_contents('includes/header.php');
        if ($header_content) {
            preg_match('/style\.css\?v=([0-9.]+)/', $header_content, $css_version);
            echo "<div>CSS: v" . ($css_version[1] ?? 'No encontrado') . "</div>";
        }
        
        // Verificar footer.php
        $footer_content = @file_get_contents('includes/footer.php');
        if ($footer_content) {
            preg_match('/main\.js\?v=([0-9.]+)/', $footer_content, $js_version);
            echo "<div>JS (main.js): v" . ($js_version[1] ?? 'No encontrado') . "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        
        // Verificar función clearAllFilters en productos.php
        echo "<div class='info-block'>";
        echo "<div class='label'>🔍 Verificación de clearAllFilters() en productos.php:</div>";
        echo "<div class='command'>";
        $productos_content = @file_get_contents('productos.php');
        if ($productos_content) {
            if (strpos($productos_content, "url.searchParams.get('category')") !== false) {
                echo "<div style='color: #00ff00;'>✅ Función actualizada correctamente (preserva category)</div>";
            } else {
                echo "<div style='color: #ff0000;'>❌ Función NO actualizada (versión antigua)</div>";
            }
        }
        echo "</div>";
        echo "</div>";
        
        // Verificar main.js
        echo "<div class='info-block'>";
        echo "<div class='label'>🔍 Verificación de clearAllFilters() en main.js:</div>";
        echo "<div class='command'>";
        $main_js_content = @file_get_contents('assets/js/main.js');
        if ($main_js_content) {
            if (strpos($main_js_content, "url.searchParams.get('category')") !== false) {
                echo "<div style='color: #00ff00;'>✅ Archivo actualizado correctamente (preserva category)</div>";
            } else {
                echo "<div style='color: #ff0000;'>❌ Archivo NO actualizado (versión antigua)</div>";
            }
        }
        echo "</div>";
        echo "</div>";
        ?>
        
        <div style="text-align: center; margin-top: 20px; padding: 20px; background: #222; border-radius: 5px;">
            <p><strong>🔧 Acciones:</strong></p>
            <a href="deploy.php?secret=multigamer360_deploy_2025" style="margin: 0 10px;">🚀 Ejecutar Deploy</a> | 
            <a href="index.php" style="margin: 0 10px;">🏠 Ir al Sitio</a> |
            <a href="version.php" style="margin: 0 10px;">🔄 Recargar</a>
        </div>
    </div>
</body>
</html>
