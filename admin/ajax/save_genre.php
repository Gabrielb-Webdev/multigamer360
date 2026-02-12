<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
    exit;
}

try {
    // Verificar si ya existe
    $check = $pdo->prepare("SELECT id FROM genres WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este género ya existe']);
        exit;
    }
    
    // Insertar nuevo género
    $stmt = $pdo->prepare("INSERT INTO genres (name, is_active, created_at) VALUES (?, 1, NOW())");
    $stmt->execute([$name]);
    
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $name
    ]);
} catch (PDOException $e) {
    error_log("Error al guardar género: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar']);
}
