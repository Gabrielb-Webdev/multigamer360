<?php
require_once 'database.php';

try {
    $sql = file_get_contents('brands_tables.sql');
    $pdo->exec($sql);
    echo "Tabla de marcas creada correctamente\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>