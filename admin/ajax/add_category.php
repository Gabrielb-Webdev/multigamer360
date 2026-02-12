<?php
require_once '../../config/database_production.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$name = trim($_POST['name'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
    exit;
}

try {
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Esta categoría ya existe']);
        exit;
    }
    
    // Generar slug único
    $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $baseSlug = trim($baseSlug, '-');
    
    if (empty($baseSlug)) {
        $baseSlug = 'categoria-' . time();
    }
    
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    // Insertar nueva categoría con slug
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, is_active, created_at) VALUES (?, ?, 1, NOW())");
    $stmt->execute([$name, $slug]);
    
    $newId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Categoría agregada exitosamente',
        'id' => $newId,
        'name' => $name
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
}
