<?php
/**
 * Script para limpiar la sesión del producto después de mostrar el modal de éxito
 * Este archivo es llamado via AJAX desde product_create.php
 */

session_start();

// Limpiar las variables de sesión relacionadas con el producto
if (isset($_SESSION['modal_show'])) {
    unset($_SESSION['modal_show']);
}

if (isset($_SESSION['modal_product_id'])) {
    unset($_SESSION['modal_product_id']);
}

if (isset($_SESSION['modal_product_name'])) {
    unset($_SESSION['modal_product_name']);
}

if (isset($_SESSION['modal_product_slug'])) {
    unset($_SESSION['modal_product_slug']);
}

// Responder con JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Session cleared successfully',
    'timestamp' => date('Y-m-d H:i:s')
]);
