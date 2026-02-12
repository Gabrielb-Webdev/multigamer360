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
    $check = $pdo->prepare("SELECT id FROM consoles WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Esta consola ya existe']);
        exit;
    }
    
    // Insertar nueva consola
    $stmt = $pdo->prepare("INSERT INTO consoles (name, is_active, created_at) VALUES (?, 1, NOW())");
    $stmt->execute([$name]);
    
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $name
    ]);
} catch (PDOException $e) {
    error_log("Error al guardar consola: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar']);
}
