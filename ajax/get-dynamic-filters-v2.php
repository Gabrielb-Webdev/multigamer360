<?php
/**
 * AJAX - Obtener filtros disponibles dinámicamente (VERSION SIMPLIFICADA)
 */

// Iniciar buffer de salida para capturar errores
ob_start();

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Verificar archivos requeridos
    if (!file_exists('../config/database.php')) {
        throw new Exception('database.php no encontrado');
    }
    
    require_once '../config/database.php';
    
    // Verificar conexión
    if (!isset($pdo)) {
        throw new Exception('Conexión a base de datos no disponible');
    }
    
    // Limpiar buffer y enviar respuesta
    ob_clean();
    header('Content-Type: application/json');
    
    // Obtener filtros aplicados
    $filters = [];
    
    if (!empty($_POST['categories'])) {
        $filters['categories'] = array_map('intval', explode(',', $_POST['categories']));
    }
    if (!empty($_POST['brands'])) {
        $filters['brands'] = array_map('intval', explode(',', $_POST['brands']));
    }
    if (!empty($_POST['consoles'])) {
        $filters['consoles'] = array_map('intval', explode(',', $_POST['consoles']));
    }
    
    // Contar productos (simplificado)
    $whereClause = ['1=1'];
    $params = [];
    
    if (!empty($filters['categories'])) {
        $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
        $whereClause[] = "category_id IN ($placeholders)";
        $params = array_merge($params, $filters['categories']);
    }
    if (!empty($filters['brands'])) {
        $placeholders = implode(',', array_fill(0, count($filters['brands']), '?'));
        $whereClause[] = "brand_id IN ($placeholders)";
        $params = array_merge($params, $filters['brands']);
    }
    if (!empty($filters['consoles'])) {
        $placeholders = implode(',', array_fill(0, count($filters['consoles']), '?'));
        $whereClause[] = "console_id IN ($placeholders)";
        $params = array_merge($params, $filters['consoles']);
    }
    
    $where = implode(' AND ', $whereClause);
    
    // Contar productos
    $sql = "SELECT COUNT(*) FROM products WHERE is_active = 1 AND $where";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productCount = $stmt->fetchColumn();
    
    // Obtener todas las marcas
    $sql = "SELECT id, name FROM brands ORDER BY name";
    $brands = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todas las consolas
    $sql = "SELECT id, name FROM consoles ORDER BY name";
    $consoles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todos los géneros
    $sql = "SELECT id, name FROM genres ORDER BY name";
    $genres = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'product_count' => (int)$productCount,
        'filters' => [
            'brands' => $brands,
            'consoles' => $consoles,
            'genres' => $genres
        ],
        'applied_filters' => $filters
    ]);
    
} catch (Exception $e) {
    // Limpiar buffer y enviar error
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

exit;
?>
