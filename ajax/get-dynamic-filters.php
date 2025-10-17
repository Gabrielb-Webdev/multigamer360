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
    
    // Obtener filtros aplicados
    $filters = [];
    if (!empty($_POST['categories'])) $filters['categories'] = array_map('intval', explode(',', $_POST['categories']));
    if (!empty($_POST['brands'])) $filters['brands'] = array_map('intval', explode(',', $_POST['brands']));
    if (!empty($_POST['consoles'])) $filters['consoles'] = array_map('intval', explode(',', $_POST['consoles']));
    if (!empty($_POST['genres'])) $filters['genres'] = array_map('intval', explode(',', $_POST['genres']));
    
    // Verificar si existe la tabla product_genres
    $hasProductGenresTable = false;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'product_genres'")->fetch();
        $hasProductGenresTable = !empty($checkTable);
    } catch (Exception $e) {
        error_log("Error verificando tabla product_genres: " . $e->getMessage());
    }
    
    // Obtener ID de categoría "Videojuegos" para saber si podemos aplicar géneros
    $videojuegosCategoryId = null;
    try {
        $stmt = $pdo->query("SELECT id FROM categories WHERE slug = 'videojuegos' OR name LIKE '%videojuego%' LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $videojuegosCategoryId = $result['id'];
        }
    } catch (Exception $e) {
        error_log("Error obteniendo categoría videojuegos: " . $e->getMessage());
    }
    
    // Determinar si se deben aplicar géneros:
    // SOLO si NO hay filtro de categoría O si el filtro de categoría incluye "Videojuegos"
    $shouldApplyGenres = empty($filters['categories']) || 
                         ($videojuegosCategoryId && in_array($videojuegosCategoryId, $filters['categories']));
    
    // Si hay filtro de géneros, FORZAR que solo se muestren videojuegos
    if (!empty($filters['genres']) && $videojuegosCategoryId) {
        $filters['categories'] = [$videojuegosCategoryId];
    }
    
    // ===== CONTAR TOTAL DE PRODUCTOS CON TODOS LOS FILTROS =====
    $where = ["p.is_active = 1"];
    $params = [];
    $joins = [];
    
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
    
    // SOLO aplicar géneros si NO hay filtro de categoría o si incluye "Videojuegos"
    if ($hasProductGenresTable && !empty($filters['genres']) && $shouldApplyGenres) {
        $joins[] = "INNER JOIN product_genres pg ON p.id = pg.product_id";
        $ph = implode(',', array_fill(0, count($filters['genres']), '?'));
        $where[] = "pg.genre_id IN ($ph)";
        $params = array_merge($params, $filters['genres']);
    }
    
    $joinClause = implode(' ', $joins);
    $whereClause = implode(' AND ', $where);
    
    $sql = "SELECT COUNT(DISTINCT p.id) FROM products p $joinClause WHERE $whereClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();    
    // ==========================================
    // OBTENER MARCAS CON CONTEO INTELIGENTE
    // ==========================================
    // Para cada marca, contar productos considerando SOLO los filtros que NO sean de marca
    $brands = [];
    $stmt = $pdo->query("SELECT id, name FROM brands ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $brandWhere = ['p.is_active = 1', 'p.brand_id = ?'];
        $brandJoins = [];
        $brandParams = [$b['id']];
        
        // Aplicar filtros EXCEPTO el de marcas
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
        // APLICAR géneros SOLO si NO estamos filtrando Consolas
        if ($hasProductGenresTable && !empty($filters['genres']) && $shouldApplyGenres) {
            $brandJoins[] = "INNER JOIN product_genres pg ON p.id = pg.product_id";
            $ph = implode(',', array_fill(0, count($filters['genres']), '?'));
            $brandWhere[] = "pg.genre_id IN ($ph)";
            $brandParams = array_merge($brandParams, $filters['genres']);
        }
        
        $brandJoinClause = implode(' ', $brandJoins);
        $brandWhereClause = implode(' AND ', $brandWhere);
        $sql = "SELECT COUNT(DISTINCT p.id) FROM products p $brandJoinClause WHERE $brandWhereClause";
        $s = $pdo->prepare($sql);
        $s->execute($brandParams);
        $brands[] = ['id' => $b['id'], 'name' => $b['name'], 'product_count' => (int)$s->fetchColumn()];
    }
    
    // ==========================================
    // OBTENER CONSOLAS CON CONTEO INTELIGENTE
    // ==========================================
    // Para cada consola, contar productos considerando SOLO los filtros que NO sean de consolas
    $consoles = [];
    $stmt = $pdo->query("SELECT id, name FROM consoles ORDER BY name");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        $consoleWhere = ['p.is_active = 1', 'p.console_id = ?'];
        $consoleJoins = [];
        $consoleParams = [$c['id']];
        
        // Aplicar filtros EXCEPTO el de consolas
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
        // APLICAR géneros SOLO si NO estamos filtrando categoría Consolas
        if ($hasProductGenresTable && !empty($filters['genres']) && $shouldApplyGenres) {
            $consoleJoins[] = "INNER JOIN product_genres pg ON p.id = pg.product_id";
            $ph = implode(',', array_fill(0, count($filters['genres']), '?'));
            $consoleWhere[] = "pg.genre_id IN ($ph)";
            $consoleParams = array_merge($consoleParams, $filters['genres']);
        }
        
        $consoleJoinClause = implode(' ', $consoleJoins);
        $consoleWhereClause = implode(' AND ', $consoleWhere);
        $sql = "SELECT COUNT(DISTINCT p.id) FROM products p $consoleJoinClause WHERE $consoleWhereClause";
        $s = $pdo->prepare($sql);
        $s->execute($consoleParams);
        $consoles[] = ['id' => $c['id'], 'name' => $c['name'], 'product_count' => (int)$s->fetchColumn()];
    }
    
    // ==========================================
    // OBTENER GÉNEROS CON CONTEO INTELIGENTE
    // ==========================================
    // REGLA CRÍTICA: Los géneros SOLO aplican a Videojuegos, NUNCA a Consolas
    // Si hay filtro de categoría "Consolas", devolver todos los géneros con product_count = 0
    $genres = [];
    if ($hasProductGenresTable) {
        try {
            // Excluir género invisible "_CONSOLA_" (ID 999)
            $stmt = $pdo->query("SELECT id, name FROM genres WHERE id != 999 AND name NOT LIKE '\\\_%' ESCAPE '\\\\' ORDER BY name");
            
            // Si está filtrando categoría "Consolas", todos los géneros tienen 0 productos
            $isFilteringConsolesCategory = !empty($filters['categories']) && 
                                           !($videojuegosCategoryId && in_array($videojuegosCategoryId, $filters['categories']));
            
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
                if ($isFilteringConsolesCategory) {
                    // Si filtro Consolas, NO hay géneros disponibles
                    $genres[] = ['id' => $g['id'], 'name' => $g['name'], 'product_count' => 0];
                } else {
                    // Contar productos normalmente (solo Videojuegos pueden tener géneros)
                    $genreWhere = ['p.is_active = 1'];
                    $genreJoin = "INNER JOIN product_genres pg ON p.id = pg.product_id AND pg.genre_id = ?";
                    $genreParams = [$g['id']];
                    
                    // FORZAR categoría Videojuegos
                    if ($videojuegosCategoryId) {
                        $genreWhere[] = "p.category_id = ?";
                        $genreParams[] = $videojuegosCategoryId;
                    }
                    
                    // Aplicar filtros EXCEPTO el de géneros
                    if (!empty($filters['brands'])) {
                        $ph = implode(',', array_fill(0, count($filters['brands']), '?'));
                        $genreWhere[] = "p.brand_id IN ($ph)";
                        $genreParams = array_merge($genreParams, $filters['brands']);
                    }
                    if (!empty($filters['consoles'])) {
                        $ph = implode(',', array_fill(0, count($filters['consoles']), '?'));
                        $genreWhere[] = "p.console_id IN ($ph)";
                        $genreParams = array_merge($genreParams, $filters['consoles']);
                    }
                    
                    $genreWhereClause = implode(' AND ', $genreWhere);
                    $sql = "SELECT COUNT(DISTINCT p.id) FROM products p $genreJoin WHERE $genreWhereClause";
                    $s = $pdo->prepare($sql);
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
