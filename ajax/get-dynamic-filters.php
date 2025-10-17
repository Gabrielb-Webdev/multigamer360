<?php
/**
 * AJAX - Obtener filtros disponibles dinámicamente
 * Retorna los filtros con conteos actualizados según los filtros actualmente aplicados
 */

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}

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
    exit;
    
} catch (Exception $e) {
    error_log("Error en get-dynamic-filters.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener filtros: ' . $e->getMessage(),
        'filters' => [
            'brands' => [],
            'consoles' => [],
            'genres' => []
        ],
        'product_count' => 0
    ]);
}

exit;

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
    $result['brands'] = [];
    try {
        $sql = "SELECT DISTINCT b.id, b.name
                FROM brands b
                ORDER BY b.name";
        
        $stmt = $pdo->query($sql);
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar productos para cada marca
        foreach ($brands as &$brand) {
            $countSql = "SELECT COUNT(*) as cnt 
                         FROM products p 
                         WHERE p.brand_id = ? AND $whereClause";
            $countParams = array_merge([$brand['id']], $params);
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $brand['product_count'] = (int)$countStmt->fetchColumn();
        }
        
        $result['brands'] = $brands;
    } catch (Exception $e) {
        error_log("Error obteniendo marcas: " . $e->getMessage());
        $result['brands'] = [];
    }
    
    // Obtener consolas con conteo
    $result['consoles'] = [];
    try {
        $sql = "SELECT DISTINCT c.id, c.name
                FROM consoles c
                ORDER BY c.name";
        
        $stmt = $pdo->query($sql);
        $consoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar productos para cada consola
        foreach ($consoles as &$console) {
            $countSql = "SELECT COUNT(*) as cnt
                         FROM products p
                         WHERE p.console_id = ? AND $whereClause";
            $countParams = array_merge([$console['id']], $params);
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $console['product_count'] = (int)$countStmt->fetchColumn();
        }
        
        $result['consoles'] = $consoles;
    } catch (Exception $e) {
        error_log("Error obteniendo consolas: " . $e->getMessage());
        $result['consoles'] = [];
    }
    
    // Obtener géneros con conteo
    $result['genres'] = [];
    try {
        $sql = "SELECT DISTINCT g.id, g.name
                FROM genres g
                ORDER BY g.name";
        
        $stmt = $pdo->query($sql);
        $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar productos para cada género
        foreach ($genres as &$genre) {
            $countSql = "SELECT COUNT(*) as cnt
                         FROM products p
                         WHERE p.genre_id = ? AND $whereClause";
            $countParams = array_merge([$genre['id']], $params);
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $genre['product_count'] = (int)$countStmt->fetchColumn();
        }
        
        $result['genres'] = $genres;
    } catch (Exception $e) {
        error_log("Error obteniendo géneros: " . $e->getMessage());
        $result['genres'] = [];
    }
    
    return $result;
}
?>
