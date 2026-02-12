<?php
// Redirigir búsquedas a la página de productos
$query = $_GET['search'] ?? ($_GET['q'] ?? '');
if (!empty($query)) {
    header("Location: productos.php?search=" . urlencode($query));
} else {
    header("Location: productos.php");
}
exit;
