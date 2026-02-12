<?php
/**
 * API para obtener filtros compatibles basados en selección actual
 * Devuelve IDs de categorías, marcas, consolas y géneros que tienen productos
 * compatibles con los filtros actuales
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener filtros actuales desde parámetros GET
    $currentFilters = [
        'categories' => isset($_GET['category']) ? array_map('intval', explode(',', $_GET['category'])) : [],
        'brands' => isset($_GET['brands']) ? array_map('intval', explode(',', $_GET['brands'])) : [],
        'consoles' => isset($_GET['consoles']) ? array_map('intval', explode(',', $_GET['consoles'])) : [],
        'genres' => isset($_GET['genres']) ? array_map('intval', explode(',', $_GET['genres'])) : []
    ];
    
    // Construir query base
    $sql = "SELECT DISTINCT
                p.category_id,
                p.brand_id,
                p.console_id,
                pg.genre_id
            FROM products p
            LEFT JOIN product_genres pg ON p.id = pg.product_id
            WHERE p.is_active = TRUE";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros actuales
    if (!empty($currentFilters['categories'])) {
        $placeholders = str_repeat('?,', count($currentFilters['categories']) - 1) . '?';
        $sql .= " AND p.category_id IN ($placeholders)";
        $params = array_merge($params, $currentFilters['categories']);
        $types .= str_repeat('i', count($currentFilters['categories']));
    }
    
    if (!empty($currentFilters['brands'])) {
        $placeholders = str_repeat('?,', count($currentFilters['brands']) - 1) . '?';
        $sql .= " AND p.brand_id IN ($placeholders)";
        $params = array_merge($params, $currentFilters['brands']);
        $types .= str_repeat('i', count($currentFilters['brands']));
    }
    
    if (!empty($currentFilters['consoles'])) {
        $placeholders = str_repeat('?,', count($currentFilters['consoles']) - 1) . '?';
        $sql .= " AND p.console_id IN ($placeholders)";
        $params = array_merge($params, $currentFilters['consoles']);
        $types .= str_repeat('i', count($currentFilters['consoles']));
    }
    
    if (!empty($currentFilters['genres'])) {
        $placeholders = str_repeat('?,', count($currentFilters['genres']) - 1) . '?';
        $sql .= " AND EXISTS (
            SELECT 1 FROM product_genres pg2 
            WHERE pg2.product_id = p.id 
            AND pg2.genre_id IN ($placeholders)
        )";
        $params = array_merge($params, $currentFilters['genres']);
        $types .= str_repeat('i', count($currentFilters['genres']));
    }
    
    // Ejecutar query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Recopilar IDs compatibles
    $compatibleFilters = [
        'categories' => [],
        'brands' => [],
        'consoles' => [],
        'genres' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        if ($row['category_id'] && !in_array($row['category_id'], $compatibleFilters['categories'])) {
            $compatibleFilters['categories'][] = (int)$row['category_id'];
        }
        if ($row['brand_id'] && !in_array($row['brand_id'], $compatibleFilters['brands'])) {
            $compatibleFilters['brands'][] = (int)$row['brand_id'];
        }
        if ($row['console_id'] && !in_array($row['console_id'], $compatibleFilters['consoles'])) {
            $compatibleFilters['consoles'][] = (int)$row['console_id'];
        }
        if ($row['genre_id'] && !in_array($row['genre_id'], $compatibleFilters['genres'])) {
            $compatibleFilters['genres'][] = (int)$row['genre_id'];
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'filters' => $compatibleFilters
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
