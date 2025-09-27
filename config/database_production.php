<?php
// Configuración de base de datos para producción (Hostinger)
$host = 'localhost';
$dbname = 'u851317150_mg360_db';
$username = 'u851317150_mg360_user';
$password = 'MultiGamer2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>