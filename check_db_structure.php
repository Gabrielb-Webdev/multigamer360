<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/session_config.php';
require 'config/database.php';

echo "<h2>Estructura de la tabla products</h2>";

try {
    // Obtener columnas de la tabla products
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // También verificar si hay productos
    $count = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = TRUE")->fetchColumn();
    echo "<p><strong>Productos activos en DB: $count</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
