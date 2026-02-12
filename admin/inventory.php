<?php
session_start();
// Redirigir a productos (ahora productos incluye gestión de inventario)
$_SESSION['info'] = 'La gestión de inventario ahora está integrada en la sección de Productos';
header('Location: products.php');
exit;
?>
