<?php
// Test simple para ver si PHP funciona
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP está funcionando</h1>";
echo "<p>Versión de PHP: " . phpversion() . "</p>";

// Test de includes básicos
echo "<h2>Test de includes</h2>";

if (file_exists('config/database.php')) {
    echo "✓ config/database.php existe<br>";
    try {
        require_once 'config/database.php';
        echo "✓ config/database.php cargado<br>";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ config/database.php NO existe<br>";
}

if (file_exists('includes/auth.php')) {
    echo "✓ includes/auth.php existe<br>";
    try {
        require_once 'includes/auth.php';
        echo "✓ includes/auth.php cargado<br>";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ includes/auth.php NO existe<br>";
}

if (file_exists('includes/functions.php')) {
    echo "✓ includes/functions.php existe<br>";
    try {
        require_once 'includes/functions.php';
        echo "✓ includes/functions.php cargado<br>";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ includes/functions.php NO existe<br>";
}

echo "<h2>Todos los tests completados</h2>";
?>
