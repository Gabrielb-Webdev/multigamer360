<?php
// Archivo de prueba simple para verificar PHP y configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Simple</title></head><body>";
echo "<h1>✅ PHP Funciona</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test 1: ¿Existe config/database.php?
if (file_exists('config/database.php')) {
    echo "<p>✅ config/database.php existe</p>";
    
    // Test 2: ¿Se puede incluir sin errores?
    try {
        require_once 'config/database.php';
        echo "<p>✅ config/database.php se cargó correctamente</p>";
        
        // Test 3: ¿Existe la conexión PDO?
        if (isset($pdo)) {
            echo "<p>✅ Objeto PDO existe</p>";
            
            // Test 4: ¿Funciona la consulta?
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
                $result = $stmt->fetch();
                echo "<p>✅ Productos en DB: " . $result['total'] . "</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error consultando productos: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ Objeto PDO no existe</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error cargando database.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config/database.php NO existe</p>";
}

// Test 5: Session
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Sesión iniciada: " . session_id() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error iniciando sesión: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
