<?php
/**
 * AJAX - Obtener filtros disponibles dinámicamente
 * Retorna los filtros con conteos actualizados según los filtros actualmente aplicados
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/SmartFilters.php';

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
    
    // Inicializar SmartFilters
    $smartFilters = new SmartFilters($pdo);
    
    // Obtener filtros disponibles con los conteos actualizados
    $availableFilters = $smartFilters->getAllAvailableFilters($appliedFilters);
    
    // Contar productos totales con estos filtros
    $productCount = $smartFilters->countProductsWithFilters($appliedFilters);
    
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
?>
