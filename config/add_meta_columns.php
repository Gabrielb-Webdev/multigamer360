<?php
/**
 * Script para agregar columnas meta_title y meta_description a la tabla products
 * Ejecutar desde el navegador: /config/add_meta_columns.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Actualización de Base de Datos - Columnas SEO</h2>";
echo "<hr>";

try {
    // Verificar si las columnas ya existen
    $checkQuery = "SHOW COLUMNS FROM products LIKE 'meta_title'";
    $stmt = $pdo->query($checkQuery);
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "<p style='color: orange;'>⚠️ La columna <strong>meta_title</strong> ya existe.</p>";
    } else {
        echo "<p>➕ Agregando columna <strong>meta_title</strong>...</p>";
        $pdo->exec("ALTER TABLE products ADD COLUMN meta_title VARCHAR(60) DEFAULT NULL AFTER description");
        echo "<p style='color: green;'>✅ Columna <strong>meta_title</strong> agregada correctamente.</p>";
    }
    
    // Verificar meta_description
    $checkQuery = "SHOW COLUMNS FROM products LIKE 'meta_description'";
    $stmt = $pdo->query($checkQuery);
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "<p style='color: orange;'>⚠️ La columna <strong>meta_description</strong> ya existe.</p>";
    } else {
        echo "<p>➕ Agregando columna <strong>meta_description</strong>...</p>";
        $pdo->exec("ALTER TABLE products ADD COLUMN meta_description VARCHAR(160) DEFAULT NULL AFTER meta_title");
        echo "<p style='color: green;'>✅ Columna <strong>meta_description</strong> agregada correctamente.</p>";
    }
    
    echo "<hr>";
    echo "<h3>📋 Estructura actual de la tabla products:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    
    $stmt = $pdo->query("DESCRIBE products");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $highlight = in_array($row['Field'], ['meta_title', 'meta_description']) ? " style='background: #d4edda;'" : "";
        echo "<tr{$highlight}>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Actualización completada exitosamente.</strong></p>";
    echo "<p><a href='../admin/product_create.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Volver a Crear Producto</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Código de error: " . $e->getCode() . "</p>";
}
?>
