<?php
/**
 * Crear automáticamente datos faltantes (marcas, consolas, categorías, géneros)
 */

require_once '../../config/database_production.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$response = [
    'success' => true,
    'created' => [],
    'found' => [],
    'ids' => []
];

try {
    // CREAR O ENCONTRAR CATEGORÍA
    if (!empty($data['category'])) {
        $categoryName = trim($data['category']);
        
        // Buscar si existe
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([$categoryName]);
        $category = $stmt->fetch();
        
        if ($category) {
            $response['found'][] = "Categoría: {$categoryName}";
            $response['ids']['category_id'] = $category['id'];
        } else {
            // Crear nueva categoría
            $slug = strtolower(str_replace(' ', '-', $categoryName));
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$categoryName, $slug]);
            $response['ids']['category_id'] = $pdo->lastInsertId();
            $response['created'][] = "Categoría: {$categoryName}";
        }
    }
    
    // CREAR O ENCONTRAR MARCA
    if (!empty($data['brand'])) {
        $brandName = trim($data['brand']);
        
        // Buscar si existe
        $stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([$brandName]);
        $brand = $stmt->fetch();
        
        if ($brand) {
            $response['found'][] = "Marca: {$brandName}";
            $response['ids']['brand_id'] = $brand['id'];
        } else {
            // Crear nueva marca
            $slug = strtolower(str_replace(' ', '-', $brandName));
            $stmt = $pdo->prepare("INSERT INTO brands (name, slug, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$brandName, $slug]);
            $response['ids']['brand_id'] = $pdo->lastInsertId();
            $response['created'][] = "Marca: {$brandName}";
        }
    }
    
    // CREAR O ENCONTRAR CONSOLA
    if (!empty($data['console'])) {
        $consoleName = trim($data['console']);
        
        // Buscar si existe (búsqueda flexible)
        $stmt = $pdo->prepare("SELECT id FROM consoles WHERE LOWER(name) LIKE LOWER(?) LIMIT 1");
        $stmt->execute(['%' . $consoleName . '%']);
        $console = $stmt->fetch();
        
        if ($console) {
            $response['found'][] = "Consola: {$consoleName}";
            $response['ids']['console_id'] = $console['id'];
        } else {
            // Crear nueva consola
            $slug = strtolower(str_replace(' ', '-', $consoleName));
            $stmt = $pdo->prepare("INSERT INTO consoles (name, slug, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$consoleName, $slug]);
            $response['ids']['console_id'] = $pdo->lastInsertId();
            $response['created'][] = "Consola: {$consoleName}";
        }
    }
    
    // CREAR O ENCONTRAR GÉNEROS
    if (!empty($data['genres']) && is_array($data['genres'])) {
        $genreIds = [];
        
        foreach ($data['genres'] as $genreName) {
            $genreName = trim($genreName);
            if (empty($genreName)) continue;
            
            // Buscar si existe
            $stmt = $pdo->prepare("SELECT id FROM genres WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt->execute([$genreName]);
            $genre = $stmt->fetch();
            
            if ($genre) {
                $genreIds[] = $genre['id'];
                $response['found'][] = "Género: {$genreName}";
            } else {
                // Crear nuevo género
                $slug = strtolower(str_replace(' ', '-', $genreName));
                $stmt = $pdo->prepare("INSERT INTO genres (name, slug, is_active) VALUES (?, ?, 1)");
                $stmt->execute([$genreName, $slug]);
                $genreIds[] = $pdo->lastInsertId();
                $response['created'][] = "Género: {$genreName}";
            }
        }
        
        $response['ids']['genre_ids'] = $genreIds;
    }
    
    $response['message'] = 'Datos procesados correctamente';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
