<?php
/**
 * =====================================================
 * SCRIPT DE MIGRACIÓN COMPLETA - BASE DE DATOS
 * =====================================================
 * Este script corrige TODAS las columnas faltantes
 * EJECUTAR SOLO UNA VEZ
 * =====================================================
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Migración Completa DB</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:900px;margin:0 auto;} .success{color:green;padding:10px;background:#d4edda;margin:10px 0;} .error{color:red;padding:10px;background:#f8d7da;margin:10px 0;} .info{color:blue;padding:10px;background:#d1ecf1;margin:10px 0;} table{width:100%;border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#333;color:white;}</style>";
echo "</head><body>";

echo "<h1>🔧 Migración Completa de Base de Datos</h1>";
echo "<hr>";

try {
    // ==========================================
    // PASO 1: Verificar columnas existentes
    // ==========================================
    echo "<h2>📊 Paso 1: Verificación de Columnas Existentes</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $existingColumns = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $col['Field'];
    }
    
    echo "<div class='info'><strong>Columnas actuales en products:</strong> " . implode(', ', $existingColumns) . "</div>";
    
    // ==========================================
    // PASO 2: Definir columnas necesarias
    // ==========================================
    echo "<h2>🔨 Paso 2: Agregando Columnas Faltantes</h2>";
    
    $requiredColumns = [
        'sku' => "VARCHAR(100) NULL UNIQUE",
        'main_image' => "VARCHAR(500) NULL",
        'is_featured' => "TINYINT(1) DEFAULT 0",
        'is_new' => "TINYINT(1) DEFAULT 0",
        'product_type' => "ENUM('physical', 'digital', 'service') DEFAULT 'physical'",
        'meta_title' => "VARCHAR(255) NULL",
        'meta_description' => "TEXT NULL",
        'meta_keywords' => "VARCHAR(500) NULL"
    ];
    
    $columnsAdded = 0;
    $columnsSkipped = 0;
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $pdo->exec("ALTER TABLE products ADD COLUMN $columnName $columnDef");
                echo "<div class='success'>✅ Columna '$columnName' agregada exitosamente</div>";
                $columnsAdded++;
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error al agregar '$columnName': " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='info'>⏭️ Columna '$columnName' ya existe</div>";
            $columnsSkipped++;
        }
    }
    
    // ==========================================
    // PASO 3: Verificar y corregir columnas problemáticas
    // ==========================================
    echo "<h2>🔍 Paso 3: Verificando Columnas Problemáticas</h2>";
    
    // Verificar que exista 'price' y no 'price_pesos'
    if (in_array('price', $existingColumns)) {
        echo "<div class='success'>✅ Columna 'price' existe correctamente</div>";
    } else {
        echo "<div class='error'>❌ Columna 'price' NO existe. Debe existir.</div>";
        
        // Si existe price_pesos, renombrarla
        if (in_array('price_pesos', $existingColumns)) {
            try {
                $pdo->exec("ALTER TABLE products CHANGE price_pesos price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                echo "<div class='success'>✅ Columna 'price_pesos' renombrada a 'price'</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error al renombrar: " . $e->getMessage() . "</div>";
            }
        } else {
            try {
                $pdo->exec("ALTER TABLE products ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                echo "<div class='success'>✅ Columna 'price' agregada</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Verificar console_id
    if (in_array('console_id', $existingColumns)) {
        echo "<div class='success'>✅ Columna 'console_id' existe</div>";
    } else {
        echo "<div class='error'>❌ Columna 'console_id' NO existe</div>";
    }
    
    // ==========================================
    // PASO 4: Crear tabla consoles si no existe
    // ==========================================
    echo "<h2>🎮 Paso 4: Verificando Tabla Consoles</h2>";
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'consoles'");
    if ($tableCheck->rowCount() == 0) {
        echo "<div class='info'>Creando tabla consoles...</div>";
        
        $consolesSQL = "
        CREATE TABLE IF NOT EXISTS consoles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            manufacturer VARCHAR(100) NULL,
            icon VARCHAR(255) NULL,
            description TEXT NULL,
            release_year INT NULL,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($consolesSQL);
            echo "<div class='success'>✅ Tabla consoles creada</div>";
            
            // Insertar consolas básicas
            $pdo->exec("INSERT INTO consoles (name, slug, manufacturer) VALUES 
                ('PlayStation 5', 'playstation-5', 'Sony'),
                ('PlayStation 4', 'playstation-4', 'Sony'),
                ('Xbox Series X', 'xbox-series-x', 'Microsoft'),
                ('Nintendo Switch', 'nintendo-switch', 'Nintendo'),
                ('PC', 'pc', 'Varios')
            ");
            echo "<div class='success'>✅ Consolas básicas agregadas</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='success'>✅ Tabla consoles ya existe</div>";
    }
    
    // ==========================================
    // PASO 5: Crear tabla genres si no existe
    // ==========================================
    echo "<h2>🎯 Paso 5: Verificando Tabla Genres</h2>";
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'genres'");
    if ($tableCheck->rowCount() == 0) {
        echo "<div class='info'>Creando tabla genres...</div>";
        
        $genresSQL = "
        CREATE TABLE IF NOT EXISTS genres (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            icon VARCHAR(255) NULL,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($genresSQL);
            echo "<div class='success'>✅ Tabla genres creada</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='success'>✅ Tabla genres ya existe</div>";
    }
    
    // ==========================================
    // PASO 6: Actualizar SKU automático
    // ==========================================
    echo "<h2>🔢 Paso 6: Generando SKUs Automáticos</h2>";
    
    if (in_array('sku', $existingColumns)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE sku IS NULL OR sku = ''");
        $nullSkuCount = $stmt->fetch()['count'];
        
        if ($nullSkuCount > 0) {
            echo "<div class='info'>Generando SKUs para $nullSkuCount productos...</div>";
            
            $stmt = $pdo->query("SELECT id FROM products WHERE sku IS NULL OR sku = ''");
            $products = $stmt->fetchAll();
            
            foreach ($products as $product) {
                $newSku = 'MG360-' . str_pad($product['id'], 6, '0', STR_PAD_LEFT);
                $updateStmt = $pdo->prepare("UPDATE products SET sku = ? WHERE id = ?");
                $updateStmt->execute([$newSku, $product['id']]);
            }
            
            echo "<div class='success'>✅ SKUs generados para $nullSkuCount productos</div>";
        } else {
            echo "<div class='success'>✅ Todos los productos tienen SKU</div>";
        }
    }
    
    // ==========================================
    // PASO 7: Resumen Final
    // ==========================================
    echo "<hr>";
    echo "<h2>📋 Resumen Final</h2>";
    
    // Recargar columnas
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $finalColumns = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $finalColumns[] = $col['Field'];
    }
    
    echo "<table>";
    echo "<tr><th>Columna</th><th>Estado</th></tr>";
    
    $criticalColumns = ['id', 'name', 'slug', 'description', 'price', 'stock_quantity', 'category_id', 'brand_id', 'console_id', 'sku', 'main_image', 'is_active', 'is_featured', 'is_new'];
    
    foreach ($criticalColumns as $col) {
        $exists = in_array($col, $finalColumns);
        $status = $exists ? "<span style='color:green'>✅ Existe</span>" : "<span style='color:red'>❌ Falta</span>";
        echo "<tr><td>$col</td><td>$status</td></tr>";
    }
    
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<h3>🎉 Migración Completada</h3>";
    echo "<p><strong>Columnas agregadas:</strong> $columnsAdded</p>";
    echo "<p><strong>Columnas omitidas (ya existían):</strong> $columnsSkipped</p>";
    echo "</div>";
    
    echo "<hr>";
    echo "<p><a href='admin/index.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:10px 5px;'>Ir al Dashboard</a>";
    echo "<a href='test_products.php' style='display:inline-block;padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;margin:10px 5px;'>Verificar Productos</a></p>";
    
    echo "<p style='color:red;'><strong>⚠️ IMPORTANTE:</strong> Después de verificar que todo funciona, elimina este archivo (fix_database.php) por seguridad.</p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error Fatal</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
