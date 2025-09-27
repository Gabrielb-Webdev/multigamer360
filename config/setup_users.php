<?php
// Script para configurar la base de datos de usuarios
require_once 'database.php';

try {
    // Leer y ejecutar el archivo SQL de usuarios
    $sql = file_get_contents(__DIR__ . '/users_table.sql');
    
    // Separar las consultas por punto y coma
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "✅ Tabla de usuarios creada exitosamente\n";
    echo "✅ Índices creados exitosamente\n";
    echo "✅ Base de datos configurada correctamente\n";
    
} catch (PDOException $e) {
    echo "❌ Error al configurar la base de datos: " . $e->getMessage() . "\n";
}
?>
