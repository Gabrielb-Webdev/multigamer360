<?php
/**
 * SCRIPT PARA AGREGAR COLUMNA is_new A products
 * Ejecutar UNA SOLA VEZ desde el navegador o CLI
 */

require_once 'database.php';

echo "<h2>Agregando columna is_new a tabla products...</h2>\n";

try {
    // Verificar si la columna ya existe
    $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_new'");
    
    if ($check->rowCount() > 0) {
        echo "<p style='color: orange;'>✓ La columna 'is_new' ya existe en la tabla products.</p>\n";
    } else {
        // Agregar columna is_new
        $pdo->exec("ALTER TABLE products ADD COLUMN is_new TINYINT(1) DEFAULT 0 COMMENT 'Marcar como novedad/nuevo producto' AFTER is_featured");
        echo "<p style='color: green;'>✓ Columna 'is_new' agregada exitosamente.</p>\n";
        
        // Crear índice
        $pdo->exec("CREATE INDEX idx_products_new ON products(is_new)");
        echo "<p style='color: green;'>✓ Índice idx_products_new creado exitosamente.</p>\n";
    }
    
    // Mostrar estructura actual
    echo "<h3>Estructura actual de la tabla products:</h3>\n";
    echo "<pre>\n";
    $columns = $pdo->query("DESCRIBE products");
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        $highlight = ($col['Field'] == 'is_new' || $col['Field'] == 'is_featured') ? ' <-- ✓' : '';
        echo sprintf("%-25s %-20s %s\n", $col['Field'], $col['Type'], $highlight);
    }
    echo "</pre>\n";
    
    echo "<h3 style='color: green;'>✓ ¡Proceso completado exitosamente!</h3>\n";
    echo "<p><strong>Ahora puedes:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Marcar productos como 'Producto Destacado' para que aparezcan en la sección de destacados del home</li>\n";
    echo "<li>Marcar productos como 'Novedad' para que aparezcan en la sección de novedades del home</li>\n";
    echo "</ul>\n";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
}

echo "\n<hr>\n";
echo "<p><a href='../admin/products.php'>← Volver al panel de productos</a></p>\n";
?>
