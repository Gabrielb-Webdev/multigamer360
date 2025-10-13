<?php
require_once 'config/database.php';

echo "=== ESTRUCTURA DE LA TABLA USERS ===\n\n";

$stmt = $pdo->query('DESCRIBE users');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo $column['Field'] . "\n";
}

echo "\n=== ÚLTIMA COLUMNA ===\n";
echo end($columns)['Field'];
