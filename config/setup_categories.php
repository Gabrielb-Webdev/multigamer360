<?php
require_once 'database.php';

try {
    $sql = file_get_contents('categories_tables.sql');
    $pdo->exec($sql);
    echo "Tablas de categorías creadas correctamente\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>