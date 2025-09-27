<?php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Endpoint de test funcionando',
    'method' => $_SERVER['REQUEST_METHOD'],
    'session_active' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'role_level' => $_SESSION['user_role_level'] ?? null
]);
?>