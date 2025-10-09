<?php
/**
 * Script de Deployment Manual para Hostinger
 * 
 * Este script ejecuta un git pull para actualizar el sitio
 * Sube este archivo a Hostinger y accédelo desde el navegador
 * URL: https://teal-fish-507993.hostingersite.com/deploy.php
 */

// Configuración de seguridad
define('DEPLOY_SECRET', 'multigamer360_deploy_2025'); // Cambia esto por algo más seguro

// Verificar parámetro de seguridad
$secret = $_GET['secret'] ?? '';

if ($secret !== DEPLOY_SECRET) {
    http_response_code(403);
    die('❌ Acceso denegado. Proporciona el secret correcto.');
}

// Deshabilitar límite de tiempo
set_time_limit(300);

// Mostrar cabecera HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment - MultiGamer360</title>
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
        .command {
            background: #222;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #00ff00;
            overflow-x: auto;
        }
        .success {
            color: #00ff00;
        }
        .error {
            color: #ff0000;
        }
        .warning {
            color: #ffaa00;
        }
        .info {
            color: #00aaff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Deployment Script - MultiGamer360</h1>
        <p class="info">Ejecutando actualización desde GitHub...</p>
        
        <?php
        // Obtener directorio actual
        $dir = __DIR__;
        echo "<div class='command'><strong>📁 Directorio:</strong> {$dir}</div>";
        
        // Cambiar al directorio del proyecto
        chdir($dir);
        
        // Función para ejecutar comandos y mostrar output
        function executeCommand($command, $label) {
            echo "<div class='command'>";
            echo "<strong class='info'>{$label}</strong><br>";
            echo "<pre>";
            
            $output = [];
            $return_var = 0;
            exec($command . " 2>&1", $output, $return_var);
            
            foreach ($output as $line) {
                echo htmlspecialchars($line) . "\n";
            }
            
            echo "</pre>";
            
            if ($return_var === 0) {
                echo "<span class='success'>✅ Comando ejecutado exitosamente</span>";
            } else {
                echo "<span class='error'>❌ Error al ejecutar comando (código: {$return_var})</span>";
            }
            
            echo "</div>";
            
            return $return_var;
        }
        
        // Verificar si git está disponible
        echo "<h2>1️⃣ Verificando Git...</h2>";
        $git_check = executeCommand('git --version', '🔍 Verificando versión de Git');
        
        if ($git_check !== 0) {
            echo "<div class='error'>❌ Git no está disponible. Contacta a soporte de Hostinger.</div>";
            exit;
        }
        
        // Obtener branch actual
        echo "<h2>2️⃣ Branch Actual...</h2>";
        executeCommand('git branch', '📌 Branch actual');
        
        // Hacer stash de cambios locales
        echo "<h2>3️⃣ Guardando cambios locales (si hay)...</h2>";
        executeCommand('git stash', '💾 Git stash');
        
        // Fetch desde origin
        echo "<h2>4️⃣ Descargando actualizaciones...</h2>";
        executeCommand('git fetch origin', '📥 Git fetch');
        
        // Pull desde origin
        echo "<h2>5️⃣ Aplicando actualizaciones...</h2>";
        $pull_result = executeCommand('git pull origin main', '🔄 Git pull');
        
        // Verificar último commit
        echo "<h2>6️⃣ Último Commit...</h2>";
        executeCommand('git log -1 --oneline', '📝 Último commit');
        
        // Limpiar caché de PHP (si existe OPcache)
        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                echo "<div class='command'><span class='success'>✅ Caché de PHP limpiado (OPcache)</span></div>";
            }
        }
        
        // Mostrar resumen
        echo "<h2>📊 Resumen</h2>";
        echo "<div class='command'>";
        if ($pull_result === 0) {
            echo "<span class='success'>✅ Deployment completado exitosamente</span><br>";
            echo "<span class='info'>🌐 Recarga tu sitio web para ver los cambios</span><br>";
            echo "<span class='warning'>⚠️ Si no ves cambios, limpia el caché del navegador (Ctrl + Shift + R)</span>";
        } else {
            echo "<span class='error'>❌ Hubo errores durante el deployment</span><br>";
            echo "<span class='warning'>⚠️ Verifica los mensajes de error arriba</span>";
        }
        echo "</div>";
        
        echo "<p class='info'>⏰ Deployment ejecutado: " . date('Y-m-d H:i:s') . "</p>";
        ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="?secret=<?php echo DEPLOY_SECRET; ?>" style="color: #00ff00;">🔄 Ejecutar de nuevo</a> | 
            <a href="index.php" style="color: #00aaff;">🏠 Ir al sitio</a>
        </div>
    </div>
</body>
</html>
