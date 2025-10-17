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
    
    // Construir WHERE para conteo de cada filtro (sin incluir el filtro actual)
    // Para MARCAS: excluir filtro de marcas del WHERE
    $brands = [];
    $stmt = $pdo->query("SELECT id, name FROM brands ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        // Construir WHERE sin el filtro de marcas
        $brandWhere = ['p.is_active = 1', 'p.brand_id = ?'];
        $brandParams = [$b['id']];
        
        if (!empty($filters['categories'])) {
            $ph = implode(',', array_fill(0, count($filters['categories']), '?'));
            $brandWhere[] = "p.category_id IN ($ph)";
            $brandParams = array_merge($brandParams, $filters['categories']);
        }
        if (!empty($filters['consoles'])) {
            $ph = implode(',', array_fill(0, count($filters['consoles']), '?'));
            $brandWhere[] = "p.console_id IN ($ph)";
            $brandParams = array_merge($brandParams, $filters['consoles']);
        }
        if ($hasGenreColumn && !$isConsoleFilter && !empty($filters['genres'])) {
            $ph = implode(',', array_fill(0, count($filters['genres']), '?'));
            $brandWhere[] = "p.genre_id IN ($ph)";
            $brandParams = array_merge($brandParams, $filters['genres']);
        }
        
        $brandWhereClause = implode(' AND ', $brandWhere);
        $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $brandWhereClause");
        $s->execute($brandParams);
        $brands[] = ['id' => $b['id'], 'name' => $b['name'], 'product_count' => (int)$s->fetchColumn()];
    }
    
    // Para CONSOLAS: excluir filtro de consolas del WHERE
    $consoles = [];
    $stmt = $pdo->query("SELECT id, name FROM consoles ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        // Construir WHERE sin el filtro de consolas
        $consoleWhere = ['p.is_active = 1', 'p.console_id = ?'];
        $consoleParams = [$c['id']];
        
        if (!empty($filters['categories'])) {
            $ph = implode(',', array_fill(0, count($filters['categories']), '?'));
            $consoleWhere[] = "p.category_id IN ($ph)";
            $consoleParams = array_merge($consoleParams, $filters['categories']);
        }
        if (!empty($filters['brands'])) {
            $ph = implode(',', array_fill(0, count($filters['brands']), '?'));
            $consoleWhere[] = "p.brand_id IN ($ph)";
            $consoleParams = array_merge($consoleParams, $filters['brands']);
        }
        // Las consolas NO usan filtro de géneros
        
        $consoleWhereClause = implode(' AND ', $consoleWhere);
        $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $consoleWhereClause");
        $s->execute($consoleParams);
        $consoles[] = ['id' => $c['id'], 'name' => $c['name'], 'product_count' => (int)$s->fetchColumn()];
    }
    
    // Para GÉNEROS: excluir filtro de géneros del WHERE y solo si NO es filtro de consolas
    $genres = [];
    if ($hasGenreColumn) {
        try {
            $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
                // Si hay filtro de consolas, mostrar géneros pero con count 0
                if ($isConsoleFilter) {
                    $genres[] = ['id' => $g['id'], 'name' => $g['name'], 'product_count' => 0];
                } else {
                    // Construir WHERE sin el filtro de géneros
                    $genreWhere = ['p.is_active = 1', 'p.genre_id = ?'];
                    $genreParams = [$g['id']];
                    
                    if (!empty($filters['categories'])) {
                        $ph = implode(',', array_fill(0, count($filters['categories']), '?'));
                        $genreWhere[] = "p.category_id IN ($ph)";
                        $genreParams = array_merge($genreParams, $filters['categories']);
                    }
                    if (!empty($filters['brands'])) {
                        $ph = implode(',', array_fill(0, count($filters['brands']), '?'));
                        $genreWhere[] = "p.brand_id IN ($ph)";
                        $genreParams = array_merge($genreParams, $filters['brands']);
                    }
                    
                    $genreWhereClause = implode(' AND ', $genreWhere);
                    $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $genreWhereClause");
                    $s->execute($genreParams);
                    $genres[] = ['id' => $g['id'], 'name' => $g['name'], 'product_count' => (int)$s->fetchColumn()];
                }
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
