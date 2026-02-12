<?php
/**
 * =====================================================
 * SCRIPT DE MIGRACIÓN: Agregar columna console_id
 * =====================================================
 * Este script agrega la columna console_id a la tabla products
 * de forma segura, verificando primero si ya existe.
 * 
 * EJECUTAR SOLO UNA VEZ
 * =====================================================
 */

require_once __DIR__ . '/../includes/config.php';

echo "<h2>Migración: Agregar columna console_id a tabla products</h2>";
echo "<hr>";

try {
    // Verificar si la columna ya existe
    echo "<p>🔍 Verificando si la columna console_id ya existe...</p>";
    
    $checkColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'console_id'");
    $columnExists = $checkColumn->fetch();
    
    if ($columnExists) {
        echo "<p style='color: orange;'>⚠️ La columna console_id ya existe en la tabla products.</p>";
        echo "<p>No es necesario ejecutar esta migración.</p>";
    } else {
        echo "<p>✓ La columna no existe. Procediendo con la migración...</p>";
        
        // Paso 1: Agregar la columna console_id
        echo "<p>📝 Paso 1: Agregando columna console_id...</p>";
        $pdo->exec("ALTER TABLE products ADD COLUMN console_id INT DEFAULT NULL AFTER brand_id");
        echo "<p style='color: green;'>✅ Columna console_id agregada exitosamente.</p>";
        
        // Paso 2: Agregar índice
        echo "<p>📝 Paso 2: Agregando índice idx_console...</p>";
        $pdo->exec("ALTER TABLE products ADD INDEX idx_console (console_id)");
        echo "<p style='color: green;'>✅ Índice agregado exitosamente.</p>";
        
        // Paso 3: Verificar si existe la tabla consoles
        $checkTable = $pdo->query("SHOW TABLES LIKE 'consoles'");
        $tableExists = $checkTable->fetch();
        
        if ($tableExists) {
            echo "<p>📝 Paso 3: Agregando foreign key constraint...</p>";
            try {
                $pdo->exec("ALTER TABLE products 
                           ADD CONSTRAINT fk_products_console 
                           FOREIGN KEY (console_id) REFERENCES consoles(id) 
                           ON DELETE SET NULL");
                echo "<p style='color: green;'>✅ Foreign key agregada exitosamente.</p>";
            } catch (PDOException $e) {
                // Si ya existe el constraint, mostrar advertencia pero continuar
                if (strpos($e->getMessage(), 'Duplicate key name') !== false || 
                    strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<p style='color: orange;'>⚠️ El foreign key constraint ya existe.</p>";
                } else {
                    throw $e;
                }
            }
        } else {
            echo "<p style='color: orange;'>⚠️ La tabla consoles no existe. Foreign key no agregado.</p>";
            echo "<p>Si necesitas la tabla consoles, ejecuta primero: migration_step1_consoles.sql</p>";
        }
        
        // Verificar resultado final
        echo "<hr>";
        echo "<h3>Verificación final:</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM products");
        $foundConsoleId = false;
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
            
            if ($col['Field'] === 'console_id') {
                $foundConsoleId = true;
            }
        }
        echo "</table>";
        
        echo "<hr>";
        if ($foundConsoleId) {
            echo "<h2 style='color: green;'>🎉 ¡MIGRACIÓN COMPLETADA EXITOSAMENTE!</h2>";
            echo "<p>La columna console_id ha sido agregada a la tabla products.</p>";
            echo "<p><strong>Siguiente paso:</strong> Actualiza tus productos para asignarles un console_id apropiado desde el panel de administración.</p>";
        } else {
            echo "<h2 style='color: red;'>❌ ERROR: La columna console_id no se encontró después de la migración.</h2>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ ERROR EN LA MIGRACIÓN</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Código de error: " . $e->getCode() . "</p>";
    
    // Dar información adicional sobre el error
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p><strong>La columna ya existe.</strong> Si ves este error, probablemente la migración ya se ejecutó anteriormente.</p>";
    }
}

echo "<hr>";
echo "<p><a href='check_columns.php'>← Volver a verificación de columnas</a></p>";
echo "<p><strong>IMPORTANTE:</strong> Después de ejecutar esta migración una vez, puedes eliminar este archivo por seguridad.</p>";
?>
