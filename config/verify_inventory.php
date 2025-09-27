<?php
require_once 'database.php';

echo "=== VERIFICANDO SISTEMA DE INVENTARIO ===" . PHP_EOL;

try {
    // Verificar tabla products
    echo "1. Verificando tabla products..." . PHP_EOL;
    $result = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'");
    if ($result->rowCount() > 0) {
        echo "   ✓ Columna stock_quantity existe" . PHP_EOL;
    } else {
        echo "   ❌ Columna stock_quantity no existe" . PHP_EOL;
    }
    
    $result = $pdo->query("SHOW COLUMNS FROM products LIKE 'min_stock_level'");
    if ($result->rowCount() > 0) {
        echo "   ✓ Columna min_stock_level existe" . PHP_EOL;
    } else {
        echo "   ❌ Columna min_stock_level no existe" . PHP_EOL;
    }
    
    // Verificar productos con stock
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0");
    $products_with_stock = $stmt->fetch()['total'];
    echo "   ✓ Productos con stock: " . $products_with_stock . PHP_EOL;
    
    // Verificar productos con stock bajo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity <= min_stock_level AND stock_quantity > 0");
    $low_stock = $stmt->fetch()['total'];
    echo "   ⚠️  Productos con stock bajo: " . $low_stock . PHP_EOL;
    
    // Verificar productos sin stock
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0");
    $out_of_stock = $stmt->fetch()['total'];
    echo "   ❌ Productos sin stock: " . $out_of_stock . PHP_EOL;
    
    // Verificar tabla inventory_movements si existe
    echo PHP_EOL . "2. Verificando tabla inventory_movements..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE inventory_movements");
        echo "   ✓ Tabla inventory_movements existe" . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory_movements");
        $movements = $stmt->fetch()['total'];
        echo "   ✓ Movimientos de inventario registrados: " . $movements . PHP_EOL;
    } catch (Exception $e) {
        echo "   ❌ Tabla inventory_movements no existe" . PHP_EOL;
    }
    
    // Valor total del inventario
    echo PHP_EOL . "3. Calculando valor del inventario..." . PHP_EOL;
    $stmt = $pdo->query("SELECT SUM(price * stock_quantity) as total_value FROM products WHERE stock_quantity > 0");
    $total_value = $stmt->fetch()['total_value'] ?? 0;
    echo "   💰 Valor total del inventario: $" . number_format($total_value, 2) . PHP_EOL;
    
    echo PHP_EOL . "✅ Verificación completada!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . PHP_EOL;
}
?>