<?php
// Script para ver los últimos errores de checkout
$log_file = __DIR__ . '/logs/checkout_errors.log';

echo "<h1>Últimos Errores de Checkout</h1>";
echo "<style>body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; } pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }</style>";

if (file_exists($log_file)) {
    echo "<h2>Archivo encontrado: $log_file</h2>";
    echo "<p>Tamaño: " . filesize($log_file) . " bytes</p>";
    echo "<p>Última modificación: " . date('Y-m-d H:i:s', filemtime($log_file)) . "</p>";
    
    $content = file_get_contents($log_file);
    if ($content) {
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    } else {
        echo "<p style='color: yellow;'>El archivo está vacío</p>";
    }
} else {
    echo "<p style='color: red;'>El archivo de log no existe todavía.</p>";
    echo "<p>Intenta hacer un pedido para generar errores.</p>";
}
?>
