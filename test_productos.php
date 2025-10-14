<?php
// Test simple para ver errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: PHP funciona<br>";

try {
    require_once 'config/database.php';
    echo "Test 2: Database.php cargado<br>";
} catch (Exception $e) {
    echo "Error en database.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'includes/smart_filters_v2.php';
    echo "Test 3: smart_filters_v2.php cargado<br>";
} catch (Exception $e) {
    echo "Error en smart_filters_v2.php: " . $e->getMessage() . "<br>";
}

try {
    $smartFilters = new SmartFilters($pdo);
    echo "Test 4: SmartFilters instanciado<br>";
} catch (Exception $e) {
    echo "Error al crear SmartFilters: " . $e->getMessage() . "<br>";
}

echo "Todos los tests pasaron!";
?>
