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
    
    $where = ['p.is_active = 1'];
    $params = [];
    
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
    
    $whereClause = implode(' AND ', $where);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
    $stmt->execute($params);
    $productCount = (int)$stmt->fetchColumn();
    
    $brands = [];
    $stmt = $pdo->query("SELECT id, name FROM brands ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $cp = array_merge([$b['id']], $params);
        $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE p.brand_id = ? AND $whereClause");
        $s->execute($cp);
        $brands[] = ['id' => $b['id'], 'name' => $b['name'], 'product_count' => (int)$s->fetchColumn()];
    }
    
    $consoles = [];
    $stmt = $pdo->query("SELECT id, name FROM consoles ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        $cp = array_merge([$c['id']], $params);
        $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE p.console_id = ? AND $whereClause");
        $s->execute($cp);
        $consoles[] = ['id' => $c['id'], 'name' => $c['name'], 'product_count' => (int)$s->fetchColumn()];
    }
    
    $genres = [];
    $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
        $cp = array_merge([$g['id']], $params);
        $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE p.genre_id = ? AND $whereClause");
        $s->execute($cp);
        $genres[] = ['id' => $g['id'], 'name' => $g['name'], 'product_count' => (int)$s->fetchColumn()];
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
