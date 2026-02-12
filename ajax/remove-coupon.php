<?php
session_start();
header('Content-Type: application/json');

// Remover cupón aplicado de la sesión
if (isset($_SESSION['applied_coupon'])) {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Cupón removido exitosamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No hay cupón aplicado']);
}
?>