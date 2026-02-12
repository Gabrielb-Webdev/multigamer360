<?php
/**
 * Script para agregar todas las columnas faltantes a la tabla products
 * Ejecutar desde el navegador: /config/add_meta_columns.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Actualización de Base de Datos - Columnas Completas</h2>";
echo "<hr>";

try {
    $columnsToAdd = [
        'meta_title' => [
            'sql' => "ALTER TABLE products ADD COLUMN meta_title VARCHAR(60) DEFAULT NULL AFTER description",
            'label' => 'meta_title (SEO)',
            'after' => 'description'
        ],
        'meta_description' => [
            'sql' => "ALTER TABLE products ADD COLUMN meta_description VARCHAR(160) DEFAULT NULL AFTER meta_title",
            'label' => 'meta_description (SEO)',
            'after' => 'meta_title'
        ],
        'status' => [
            'sql' => "ALTER TABLE products ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER meta_description",
            'label' => 'status (Estado del producto)',
            'after' => 'meta_description'
        ],
        'is_active' => [
            'sql' => "ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER status",
            'label' => 'is_active (Activo/Inactivo)',
            'after' => 'status'
        ]
    ];
    
    // No agregar created_at y updated_at si ya existen (probablemente ya están)
    $timestampColumns = ['created_at', 'updated_at'];
    foreach ($timestampColumns as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM products LIKE '{$col}'");
        if ($check->rowCount() === 0) {
            if ($col === 'created_at') {
                $columnsToAdd[$col] = [
                    'sql' => "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
                    'label' => 'created_at (Fecha de creación)',
                    'after' => 'is_active'
                ];
            } else {
                $columnsToAdd[$col] = [
                    'sql' => "ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
                    'label' => 'updated_at (Fecha de actualización)',
                    'after' => 'created_at'
                ];
            }
        }
    }
    
    foreach ($columnsToAdd as $columnName => $config) {
        // Verificar si la columna ya existe
        $checkQuery = "SHOW COLUMNS FROM products LIKE '{$columnName}'";
        $stmt = $pdo->query($checkQuery);
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            echo "<p style='color: orange;'>⚠️ La columna <strong>{$config['label']}</strong> ya existe.</p>";
        } else {
            echo "<p>➕ Agregando columna <strong>{$config['label']}</strong>...</p>";
            $pdo->exec($config['sql']);
            echo "<p style='color: green;'>✅ Columna <strong>{$config['label']}</strong> agregada correctamente.</p>";
        }
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
