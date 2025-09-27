<?php
require_once 'database.php';

echo "=== COMPLETANDO SISTEMA DE INVENTARIO ===" . PHP_EOL;

try {
    // 1. Agregar columna min_stock_level a products
    echo "1. Agregando columna min_stock_level..." . PHP_EOL;
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS min_stock_level INT DEFAULT 5");
    echo "   ✓ Columna min_stock_level agregada" . PHP_EOL;
    
    // 2. Crear tabla inventory_movements
    echo "2. Creando tabla inventory_movements..." . PHP_EOL;
    $sql = "CREATE TABLE IF NOT EXISTS inventory_movements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
        quantity INT NOT NULL,
        previous_stock INT NOT NULL,
        new_stock INT NOT NULL,
        reason VARCHAR(255),
        reference_type ENUM('purchase', 'sale', 'manual', 'return') DEFAULT 'manual',
        reference_id INT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "   ✓ Tabla inventory_movements creada" . PHP_EOL;
    
    // 3. Actualizar productos existentes con min_stock_level
    echo "3. Configurando niveles mínimos de stock..." . PHP_EOL;
    $pdo->exec("UPDATE products SET min_stock_level = 5 WHERE min_stock_level IS NULL OR min_stock_level = 0");
    echo "   ✓ Niveles mínimos configurados" . PHP_EOL;
    
    // 4. Crear algunos movimientos de ejemplo
    echo "4. Creando movimientos de inventario de ejemplo..." . PHP_EOL;
    
    $stmt = $pdo->query("SELECT id, stock_quantity FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        // Crear movimiento inicial
        $stmt = $pdo->prepare("INSERT INTO inventory_movements (product_id, movement_type, quantity, previous_stock, new_stock, reason, reference_type) VALUES (?, 'in', ?, 0, ?, 'Stock inicial', 'manual')");
        $stmt->execute([$product['id'], $product['stock_quantity'], $product['stock_quantity']]);
    }
    echo "   ✓ Movimientos de ejemplo creados" . PHP_EOL;
    
    // 5. Verificación final
    echo PHP_EOL . "=== VERIFICACIÓN FINAL ===" . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0");
    $products_with_stock = $stmt->fetch()['total'];
    echo "✓ Productos con stock: " . $products_with_stock . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity <= min_stock_level AND stock_quantity > 0");
    $low_stock = $stmt->fetch()['total'];
    echo "⚠️  Productos con stock bajo: " . $low_stock . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0");
    $out_of_stock = $stmt->fetch()['total'];
    echo "❌ Productos sin stock: " . $out_of_stock . PHP_EOL;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory_movements");
    $movements = $stmt->fetch()['total'];
    echo "📦 Movimientos registrados: " . $movements . PHP_EOL;
    
    $stmt = $pdo->query("SELECT SUM(price * stock_quantity) as total_value FROM products WHERE stock_quantity > 0");
    $total_value = $stmt->fetch()['total_value'] ?? 0;
    echo "💰 Valor total del inventario: $" . number_format($total_value, 2) . PHP_EOL;
    
    echo PHP_EOL . "🎉 ¡Sistema de inventario completado exitosamente!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>