<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

function sendJson($data) {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    
    if (!isset($pdo)) {
        sendJson(['success' => false, 'message' => 'DB no disponible', 'filters' => ['brands' => [], 'consoles' => [], 'genres' => []], 'product_count' => 0]);
    }
    
    $filters = [];
    if (!empty($_POST['categories'])) $filters['categories'] = array_map('intval', explode(',', $_POST['categories']));
    if (!empty($_POST['brands'])) $filters['brands'] = array_map('intval', explode(',', $_POST['brands']));
    if (!empty($_POST['consoles'])) $filters['consoles'] = array_map('intval', explode(',', $_POST['consoles']));
    if (!empty($_POST['genres'])) $filters['genres'] = array_map('intval', explode(',', $_POST['genres']));
    
    $where = ['p.is_active = 1'];
    $params = [];
    
    // Determinar el tipo de filtro principal
    $isConsoleFilter = !empty($filters['consoles']);
    $isVideoGameFilter = !empty($filters['categories']);
    
    if (!empty($filters['categories'])) {
        $ph = implode(',', array_fill(0, count($filters['categories']), '?'));
        $where[] = "p.category_id IN ($ph)";
        $params = array_merge($params, $filters['categories']);
    }
    if (!empty($filters['brands'])) {
        $ph = implode(',', array_fill(0, count($filters['brands']), '?'));
        $where[] = "p.brand_id IN ($ph)";
        $params = array_merge($params, $filters['brands']);
    }
    if (!empty($filters['consoles'])) {
        $ph = implode(',', array_fill(0, count($filters['consoles']), '?'));
        $where[] = "p.console_id IN ($ph)";
        $params = array_merge($params, $filters['consoles']);
    }
    
    // Verificar si existe la columna genre_id
    $hasGenreColumn = false;
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'genre_id'")->fetch();
        $hasGenreColumn = !empty($checkColumn);
    } catch (Exception $e) {
        error_log("Error verificando columna genre_id: " . $e->getMessage());
    }
    
    // Solo aplicar filtro de géneros si NO es un filtro de consolas y la columna existe
    if ($hasGenreColumn && !$isConsoleFilter && !empty($filters['genres'])) {
        $ph = implode(',', array_fill(0, count($filters['genres']), '?'));
        $where[] = "p.genre_id IN ($ph)";
        $params = array_merge($params, $filters['genres']);
    }
    
    $whereClause = implode(' AND ', $where);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
    $stmt->execute($params);
    $productCount = (int)$stmt->fetchColumn();
    
    // MARCAS - Siempre mostrar todas, con conteo
    $brands = [];
    $stmt = $pdo->query("SELECT id, name FROM brands ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        // Siempre mostrar la marca, incluso con 0 productos
        $brands[] = ['id' => $b['id'], 'name' => $b['name'], 'product_count' => 0];
    }
    
    // CONSOLAS - Siempre mostrar todas, con conteo
    $consoles = [];
    $stmt = $pdo->query("SELECT id, name FROM consoles ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        // Siempre mostrar la consola, incluso con 0 productos
        $consoles[] = ['id' => $c['id'], 'name' => $c['name'], 'product_count' => 0];
    }
    
    // GÉNEROS - Siempre mostrar todos si la columna existe
    $genres = [];
    if ($hasGenreColumn) {
        try {
            $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
                // Siempre mostrar el género, incluso con 0 productos
                $genres[] = ['id' => $g['id'], 'name' => $g['name'], 'product_count' => 0];
            }
        } catch (Exception $e) {
            error_log("Error obteniendo géneros: " . $e->getMessage());
        }
    }
    
    sendJson([
        'success' => true,
        'product_count' => $productCount,
        'filters' => ['brands' => $brands, 'consoles' => $consoles, 'genres' => $genres],
        'applied_filters' => $filters
    ]);
    
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => $e->getMessage(), 'filters' => ['brands' => [], 'consoles' => [], 'genres' => []], 'product_count' => 0]);
}
?>
