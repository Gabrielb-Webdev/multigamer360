<?php
require_once 'database.php';

try {
    echo "Actualizando estructura de la tabla categories...\n";
    
    // Actualizar estructura de tabla
    $pdo->exec("ALTER TABLE categories 
        MODIFY COLUMN name VARCHAR(255) NOT NULL,
        MODIFY COLUMN slug VARCHAR(255) NOT NULL"
    );
    
    // Agregar columnas nuevas una por una
    $columns_to_add = [
        "ADD COLUMN parent_id INT NULL AFTER description",
        "ADD COLUMN image VARCHAR(255) DEFAULT '' AFTER parent_id", 
        "ADD COLUMN banner_image VARCHAR(255) DEFAULT '' AFTER image",
        "ADD COLUMN icon_class VARCHAR(100) DEFAULT '' AFTER banner_image",
        "ADD COLUMN meta_title VARCHAR(255) DEFAULT '' AFTER icon_class",
        "ADD COLUMN meta_description TEXT AFTER meta_title",
        "ADD COLUMN meta_keywords TEXT AFTER meta_description",
        "ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER is_active",
        "ADD COLUMN sort_order INT DEFAULT 0 AFTER is_featured"
    ];
    
    foreach ($columns_to_add as $column_sql) {
        try {
            $pdo->exec("ALTER TABLE categories $column_sql");
            echo "✓ Agregada columna\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Columna ya existe\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Migrar image_url a image si existe
    try {
        $pdo->exec("UPDATE categories SET image = image_url WHERE image_url IS NOT NULL AND image_url != ''");
        echo "✓ Migrados datos de image_url a image\n";
    } catch (Exception $e) {
        echo "- No se pudieron migrar datos de imagen\n";
    }
    
    // Crear índices
    $indexes = [
        "CREATE INDEX idx_categories_slug ON categories(slug)",
        "CREATE INDEX idx_categories_parent ON categories(parent_id)",
        "CREATE INDEX idx_categories_active ON categories(is_active)",
        "CREATE INDEX idx_categories_sort ON categories(sort_order)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "✓ Índice creado\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "- Índice ya existe\n";
            } else {
                echo "- Error en índice: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Agregar columna category_id a products
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT NULL AFTER id");
        echo "✓ Agregada columna category_id a products\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "- Columna category_id ya existe en products\n";
        } else {
            throw $e;
        }
    }
    
    // Crear índice en products
    try {
        $pdo->exec("CREATE INDEX idx_product_category ON products(category_id)");
        echo "✓ Índice category_id creado en products\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "- Índice category_id ya existe en products\n";
        } else {
            echo "- Error en índice products: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Estructura de categorías actualizada correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>