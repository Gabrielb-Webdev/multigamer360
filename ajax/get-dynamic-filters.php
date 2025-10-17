<?php
/**
 * AJAX - Obtener filtros disponibles dinámicamente
 * Retorna los filtros con conteos actualizados según los filtros actualmente aplicados
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/ProductManager.php';

try {
    // Obtener filtros aplicados desde POST
    $appliedFilters = [];
    
    if (!empty($_POST['categories'])) {
        $appliedFilters['categories'] = array_map('intval', explode(',', $_POST['categories']));
    }
    
    if (!empty($_POST['brands'])) {
        $appliedFilters['brands'] = array_map('intval', explode(',', $_POST['brands']));
    }
    
    if (!empty($_POST['consoles'])) {
        $appliedFilters['consoles'] = array_map('intval', explode(',', $_POST['consoles']));
    }
    
    if (!empty($_POST['genres'])) {
        $appliedFilters['genres'] = array_map('intval', explode(',', $_POST['genres']));
    }
    
    if (isset($_POST['min_price']) && $_POST['min_price'] !== '') {
        $appliedFilters['min_price'] = (float)$_POST['min_price'];
    }
    
    if (isset($_POST['max_price']) && $_POST['max_price'] !== '') {
        $appliedFilters['max_price'] = (float)$_POST['max_price'];
    }
    
    // Inicializar ProductManager
    $productManager = new ProductManager($pdo);
    
    // Construir filtros para la consulta
    $filters = [];
    if (!empty($appliedFilters['categories'])) {
        $filters['categories'] = $appliedFilters['categories'];
    }
    if (!empty($appliedFilters['brands'])) {
        $filters['brands'] = $appliedFilters['brands'];
    }
    if (!empty($appliedFilters['consoles'])) {
        $filters['consoles'] = $appliedFilters['consoles'];
    }
    if (!empty($appliedFilters['genres'])) {
        $filters['genres'] = $appliedFilters['genres'];
    }
    if (isset($appliedFilters['min_price'])) {
        $filters['min_price'] = $appliedFilters['min_price'];
    }
    if (isset($appliedFilters['max_price'])) {
        $filters['max_price'] = $appliedFilters['max_price'];
    }
    
    // Contar productos con estos filtros
    $productCount = $productManager->countProducts($filters);
    
    // Obtener filtros disponibles calculando conteos dinámicamente
    $availableFilters = getAvailableFiltersWithCounts($pdo, $filters);
    
    echo json_encode([
        'success' => true,
        'filters' => $availableFilters,
        'product_count' => $productCount,
        'applied_filters' => $appliedFilters
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-dynamic-filters.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener filtros: ' . $e->getMessage()
    ]);
}

/**
 * Obtener filtros disponibles con conteos dinámicos
 */
function getAvailableFiltersWithCounts($pdo, $currentFilters) {
    $result = [];
    
    // Construir WHERE base según filtros aplicados
    $whereParts = ['p.is_active = 1'];
    $params = [];
    
    if (!empty($currentFilters['categories'])) {
        $placeholders = str_repeat('?,', count($currentFilters['categories']) - 1) . '?';
        $whereParts[] = "p.category_id IN ($placeholders)";
        $params = array_merge($params, $currentFilters['categories']);
    }
    
    if (!empty($currentFilters['brands'])) {
        $placeholders = str_repeat('?,', count($currentFilters['brands']) - 1) . '?';
        $whereParts[] = "p.brand_id IN ($placeholders)";
        $params = array_merge($params, $currentFilters['brands']);
    }
    
    if (!empty($currentFilters['consoles'])) {
        $placeholders = str_repeat('?,', count($currentFilters['consoles']) - 1) . '?';
        $whereParts[] = "p.console_id IN ($placeholders)";
        $params = array_merge($params, $currentFilters['consoles']);
    }
    
    if (isset($currentFilters['min_price'])) {
        $whereParts[] = "p.price_pesos >= ?";
        $params[] = $currentFilters['min_price'];
    }
    
    if (isset($currentFilters['max_price'])) {
        $whereParts[] = "p.price_pesos <= ?";
        $params[] = $currentFilters['max_price'];
    }
    
    $whereClause = implode(' AND ', $whereParts);
    
    // Obtener marcas con conteo
    $sql = "SELECT b.id, b.name, COUNT(DISTINCT p.id) as product_count
            FROM brands b
            LEFT JOIN products p ON b.id = p.brand_id AND $whereClause
            GROUP BY b.id, b.name
            HAVING product_count > 0 OR b.id IN (" . implode(',', $currentFilters['brands'] ?? [0]) . ")
            ORDER BY b.name";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result['brands'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $result['brands'] = [];
    }
    
    // Obtener consolas con conteo
    $sql = "SELECT c.id, c.name, COUNT(DISTINCT p.id) as product_count
            FROM consoles c
            LEFT JOIN products p ON c.id = p.console_id AND $whereClause
            GROUP BY c.id, c.name
            HAVING product_count > 0 OR c.id IN (" . implode(',', $currentFilters['consoles'] ?? [0]) . ")
            ORDER BY c.name";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result['consoles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $result['consoles'] = [];
    }
    
    // Obtener géneros con conteo (si la tabla existe)
    try {
        $sql = "SELECT g.id, g.name, COUNT(DISTINCT p.id) as product_count
                FROM genres g
                LEFT JOIN products p ON g.id = p.genre_id AND $whereClause
                GROUP BY g.id, g.name
                HAVING product_count > 0 OR g.id IN (" . implode(',', $currentFilters['genres'] ?? [0]) . ")
                ORDER BY g.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result['genres'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $result['genres'] = [];
    }
    
    return $result;
}
?>
    ]);
}
?>
