<?php
// Archivo temporal para capturar logs en tiempo real
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Crear directorio de logs si no existe
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/debug_cart.log';

// Limpiar el log si se solicita
if (isset($_GET['clear'])) {
    file_put_contents($log_file, '');
    header('Location: view_cart_logs.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log de Debug - Carrito</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        h2 {
            color: #4fc3f7;
            border-bottom: 2px solid #4fc3f7;
            padding-bottom: 10px;
        }
        .instructions {
            background: #2d2d30;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffa500;
            margin: 20px 0;
        }
        .instructions ol {
            margin: 10px 0;
        }
        .instructions li {
            margin: 5px 0;
        }
        .log-container {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            max-height: 600px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ffa500; }
        .info { color: #4fc3f7; }
        .buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        button, .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007acc;
            color: white;
        }
        .btn-primary:hover {
            background: #005a9e;
        }
        .btn-danger {
            background: #d32f2f;
            color: white;
        }
        .btn-danger:hover {
            background: #9a0007;
        }
        .btn-success {
            background: #388e3c;
            color: white;
        }
        .btn-success:hover {
            background: #2c6e31;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #858585;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 Log de Debug - Sistema de Carrito</h2>
        
        <div class="instructions">
            <strong>📋 Instrucciones:</strong>
            <ol>
                <li>Deja esta página abierta en otra pestaña</li>
                <li>Ve a <a href="productos.php" target="_blank" style="color: #4fc3f7;">productos.php</a></li>
                <li>Intenta agregar un producto al carrito</li>
                <li>Vuelve a esta página y presiona "Refrescar Logs"</li>
            </ol>
        </div>

        <div class="buttons">
            <button onclick="location.reload()" class="btn btn-primary">
                🔄 Refrescar Logs
            </button>
            <a href="?clear=1" class="btn btn-danger">
                🗑️ Limpiar Logs
            </a>
            <a href="debug_product_1.php" target="_blank" class="btn btn-success">
                🔬 Verificar Producto ID 1
            </a>
            <a href="productos.php" target="_blank" class="btn btn-success">
                🛒 Ir a Productos
            </a>
        </div>

        <h3>📄 Logs Actuales:</h3>
        <div class="log-container">
<?php
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    if (empty($logs)) {
        echo '<div class="empty-state">';
        echo '⚠️ Aún no hay logs. Intenta agregar un producto al carrito.';
        echo '</div>';
    } else {
        // Procesar logs para colorear
        $lines = explode("\n", $logs);
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $class = '';
            if (strpos($line, '✓') !== false || strpos($line, 'ÉXITO') !== false) {
                $class = 'success';
            } elseif (strpos($line, '✗') !== false || strpos($line, 'ERROR') !== false) {
                $class = 'error';
            } elseif (strpos($line, '⚠') !== false || strpos($line, 'WARNING') !== false) {
                $class = 'warning';
            } elseif (strpos($line, '===') !== false) {
                $class = 'info';
            }
            
            echo '<div class="' . $class . '">' . htmlspecialchars($line) . '</div>';
        }
    }
} else {
    echo '<div class="empty-state">';
    echo '✗ Archivo de log no existe aún. Se creará automáticamente al agregar un producto.';
    echo '</div>';
}
?>
        </div>

        <div class="buttons">
            <button onclick="location.reload()" class="btn btn-primary">
                🔄 Refrescar Logs
            </button>
        </div>
    </div>

    <script>
        // Auto-refresh cada 5 segundos si se detecta actividad
        let autoRefresh = false;
        
        // Detectar si hay logs nuevos
        const logContainer = document.querySelector('.log-container');
        if (logContainer.innerText.includes('===')) {
            // Si hay logs, ofrecer auto-refresh
            autoRefresh = confirm('¿Quieres activar la actualización automática cada 5 segundos?');
        }
        
        if (autoRefresh) {
            setInterval(() => {
                location.reload();
            }, 5000);
        }
    </script>
</body>
</html>

