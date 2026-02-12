<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $stmt = $pdo->query('DESCRIBE products');
    echo "Columnas actuales en la tabla products:\n";
    echo "=====================================\n\n";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s | %-20s | %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
