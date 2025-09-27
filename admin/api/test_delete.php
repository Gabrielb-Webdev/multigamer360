<?php
// Script de test para verificar la eliminación
require_once '../inc/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no logueado']);
    exit;
}

echo json_encode([
    'success' => true,
    'user_id' => $_SESSION['user_id'],
    'role_level' => $_SESSION['user_role_level'],
    'csrf_token' => $_SESSION['csrf_token'] ?? 'no_token',
    'has_permissions' => hasPermission('products', 'delete'),
    'message' => 'Test endpoint funcionando correctamente'
]);
?>