<?php
require 'config/database.php';

echo "Columnas de products que contienen 'console':\n\n";

try {
    $stmt = $pdo->query('DESCRIBE products');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if(stripos($row['Field'], 'console') !== false) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
