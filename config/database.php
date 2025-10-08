<?php
// Detectar entorno automáticamente
$is_local = (
    (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1')) ||
    php_sapi_name() === 'cli' // Para CLI siempre usar configuración local
);

if ($is_local) {
    // Configuración LOCAL (XAMPP)
    $host = 'localhost';
    $dbname = 'multigamer360';
    $username = 'root';
    $password = '';
} else {
    // Configuración PRODUCCIÓN (Hostinger)
    $host = 'localhost';
    $dbname = 'u851317150_mg360_db';
    $username = 'u851317150_mg360_user';
    $password = 'MultiGamer2025';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Mensaje de depuración (solo en desarrollo)
    if ($is_local) {
        // echo "Conectado a base de datos LOCAL: $dbname";
    }
} catch (PDOException $e) {
    // En producción, no mostrar detalles del error
    if ($is_local) {
        die("Error de conexión LOCAL: " . $e->getMessage());
    } else {
        die("Error de conexión a la base de datos. Contacte al administrador.");
    }
}
?>