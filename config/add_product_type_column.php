<?php
/**
 * Migración: Agregar columna product_type a la tabla products
 * Fecha: 31-12-2025
 * 
 * Esta columna permite diferenciar entre juegos, consolas y accesorios
 * para mejorar los filtros del sitio.
 */

require_once __DIR__ . '/database_production.php';

try {
    echo "Iniciando migración: Agregar columna product_type...\n";
    
    // Verificar si la columna ya existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'product_type'");
    
    if ($checkColumn->rowCount() === 0) {
        // Agregar columna product_type
        $pdo->exec("
            ALTER TABLE products 
            ADD COLUMN product_type ENUM('game', 'console', 'accessory') DEFAULT 'game'
            AFTER console_id
        ");
        
        echo "✓ Columna product_type agregada exitosamente\n";
        
        // Crear índice para mejorar búsquedas
        $pdo->exec("
            ALTER TABLE products 
            ADD INDEX idx_product_type (product_type)
        ");
        
        echo "✓ Índice idx_product_type creado\n";
        
        // Actualizar productos existentes como 'game' por defecto
        $updated = $pdo->exec("UPDATE products SET product_type = 'game' WHERE product_type IS NULL");
        echo "✓ $updated productos actualizados como 'game'\n";
        
    } else {
        echo "⚠ La columna product_type ya existe. Saltando migración.\n";
    }
    
    echo "\n✅ Migración completada exitosamente!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
