<?php
require_once '../inc/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar permisos
if (!hasPermission('products', 'create')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$manufacturer = trim($data['manufacturer'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
    exit;
}

try {
    // Generar slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    $stmt = $pdo->prepare("INSERT INTO consoles (name, slug, manufacturer, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
    $stmt->execute([$name, $slug, $manufacturer]);
    
    $console_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'console' => [
            'id' => $console_id,
            'name' => $name,
            'slug' => $slug,
            'manufacturer' => $manufacturer
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al crear consola: ' . $e->getMessage()]);
}
