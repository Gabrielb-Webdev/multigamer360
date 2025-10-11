<?php
/**
 * Limpiar variables de sesión del modal de éxito
 * Se llama via AJAX después de mostrar el modal
 */

session_start();

// Limpiar variables del modal
unset($_SESSION['product_created']);
unset($_SESSION['product_id']);
unset($_SESSION['product_name']);

// Responder
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Session cleaned']);
?>
