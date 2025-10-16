<?php
require_once 'config/database.php';

echo "<h3>Estructura de la tabla products:</h3>";
$stmt = $pdo->query('DESCRIBE products');
echo "<pre>";
while($row = $stmt->fetch()) {
    echo sprintf(
        "%-30s | %-20s | Null: %-3s | Default: %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'],
        $row['Default'] ?? 'NULL'
    );
}
echo "</pre>";

echo "<h3>Verificando is_active en productos:</h3>";
$stmt = $pdo->query("SELECT id, name, is_active FROM products LIMIT 5");
echo "<pre>";
while($row = $stmt->fetch()) {
    echo sprintf(
        "ID: %d | Nombre: %-40s | is_active: %s\n",
        $row['id'],
        substr($row['name'], 0, 40),
        isset($row['is_active']) ? ($row['is_active'] ?? 'NULL') : 'NO EXISTE'
    );
}
echo "</pre>";
?>
