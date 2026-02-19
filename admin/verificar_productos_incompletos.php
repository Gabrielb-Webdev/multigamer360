<?php
/**
 * VERIFICAR PRODUCTOS CON INFORMACIÓN FALTANTE
 * Muestra un reporte visual de productos incompletos
 */

require_once '../config/database.php';

echo "<h2>🔍 Productos con Información Faltante</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    table { background: white; border-collapse: collapse; width: 100%; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    th { background: #dc3545; color: white; padding: 12px; text-align: left; font-weight: 600; position: sticky; top: 0; }
    td { padding: 10px; border-bottom: 1px solid #ddd; }
    tr:hover { background: #f8f9fa; }
    .ok { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block; margin: 2px; }
    .badge-danger { background: #dc3545; color: white; }
    .badge-success { background: #28a745; color: white; }
    .summary { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .summary h3 { margin-top: 0; color: #dc3545; }
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
    .summary-item { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3545; }
    .summary-item strong { display: block; font-size: 24px; color: #dc3545; margin-top: 5px; }
</style>";

try {
    // Resumen de problemas
    $summary_query = "
        SELECT 
            COUNT(DISTINCT CASE WHEN p.description IS NULL OR p.description = '' THEN p.id END) as sin_descripcion,
            COUNT(DISTINCT CASE WHEN p.category_id IS NULL THEN p.id END) as sin_categoria,
            COUNT(DISTINCT CASE WHEN p.brand_id IS NULL THEN p.id END) as sin_marca,
            COUNT(DISTINCT CASE WHEN p.console_id IS NULL THEN p.id END) as sin_consola,
            COUNT(DISTINCT CASE WHEN pi.id IS NULL THEN p.id END) as sin_imagen,
            COUNT(DISTINCT CASE WHEN pg.product_id IS NULL THEN p.id END) as sin_generos,
            COUNT(DISTINCT CASE WHEN p.price_pesos = 0 OR p.price_pesos IS NULL THEN p.id END) as sin_precio_ars,
            COUNT(DISTINCT CASE WHEN p.price_dollars = 0 OR p.price_dollars IS NULL THEN p.id END) as sin_precio_usd
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_genres pg ON p.id = pg.product_id
    ";
    
    $summary = $pdo->query($summary_query)->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='summary'>";
    echo "<h3>📊 Resumen de Problemas</h3>";
    echo "<div class='summary-grid'>";
    
    $issues = [
        'sin_descripcion' => '📝 Sin Descripción',
        'sin_categoria' => '📁 Sin Categoría',
        'sin_marca' => '🏷️ Sin Marca',
        'sin_consola' => '🎮 Sin Consola',
        'sin_imagen' => '🖼️ Sin Imagen',
        'sin_generos' => '🎯 Sin Géneros',
        'sin_precio_ars' => '💲 Sin Precio ARS',
        'sin_precio_usd' => '💵 Sin Precio USD'
    ];
    
    foreach ($issues as $key => $label) {
        $count = $summary[$key];
        echo "<div class='summary-item'>";
        echo "<div>{$label}</div>";
        echo "<strong>{$count}</strong>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
    // Tabla detallada
    $detailed_query = "
        SELECT 
            p.id,
            p.name,
            p.status,
            p.stock_quantity,
            CASE WHEN p.description IS NULL OR p.description = '' THEN 0 ELSE 1 END as tiene_descripcion,
            CASE WHEN p.category_id IS NULL THEN 0 ELSE 1 END as tiene_categoria,
            CASE WHEN p.brand_id IS NULL THEN 0 ELSE 1 END as tiene_marca,
            CASE WHEN p.console_id IS NULL THEN 0 ELSE 1 END as tiene_consola,
            CASE WHEN pi.id IS NULL THEN 0 ELSE 1 END as tiene_imagen,
            CASE WHEN pg.product_id IS NULL THEN 0 ELSE 1 END as tiene_generos,
            CASE WHEN p.price_pesos = 0 OR p.price_pesos IS NULL THEN 0 ELSE 1 END as tiene_precio_ars,
            CASE WHEN p.price_dollars = 0 OR p.price_dollars IS NULL THEN 0 ELSE 1 END as tiene_precio_usd,
            c.name as categoria_nombre,
            b.name as marca_nombre,
            co.name as consola_nombre
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_genres pg ON p.id = pg.product_id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN consoles co ON p.console_id = co.id
        WHERE 
            p.description IS NULL OR p.description = '' OR
            p.category_id IS NULL OR
            p.brand_id IS NULL OR
            p.console_id IS NULL OR
            pi.id IS NULL OR
            pg.product_id IS NULL OR
            p.price_pesos = 0 OR p.price_pesos IS NULL OR
            p.price_dollars = 0 OR p.price_dollars IS NULL
        GROUP BY p.id
        ORDER BY p.id DESC
    ";
    
    $products = $pdo->query($detailed_query)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Productos con Información Incompleta (" . count($products) . " productos)</h3>";
    
    if (count($products) === 0) {
        echo "<div class='summary'>";
        echo "<p style='color: #28a745; font-size: 18px; text-align: center;'>";
        echo "✅ ¡Excelente! Todos los productos tienen la información completa.";
        echo "</p>";
        echo "</div>";
    } else {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Producto</th>";
        echo "<th>Estado</th>";
        echo "<th>Stock</th>";
        echo "<th>Descripción</th>";
        echo "<th>Categoría</th>";
        echo "<th>Marca</th>";
        echo "<th>Consola</th>";
        echo "<th>Imagen</th>";
        echo "<th>Géneros</th>";
        echo "<th>Precio ARS</th>";
        echo "<th>Precio USD</th>";
        echo "<th>Problemas</th>";
        echo "<th>Acción</th>";
        echo "</tr>";
        
        foreach ($products as $product) {
            $problems = [];
            
            if (!$product['tiene_descripcion']) $problems[] = 'Descripción';
            if (!$product['tiene_categoria']) $problems[] = 'Categoría';
            if (!$product['tiene_marca']) $problems[] = 'Marca';
            if (!$product['tiene_consola']) $problems[] = 'Consola';
            if (!$product['tiene_imagen']) $problems[] = 'Imagen';
            if (!$product['tiene_generos']) $problems[] = 'Géneros';
            if (!$product['tiene_precio_ars']) $problems[] = 'Precio ARS';
            if (!$product['tiene_precio_usd']) $problems[] = 'Precio USD';
            
            $problem_count = count($problems);
            $problem_color = $problem_count > 4 ? 'danger' : 'warning';
            
            echo "<tr>";
            echo "<td><strong>{$product['id']}</strong></td>";
            echo "<td>" . htmlspecialchars(substr($product['name'], 0, 40)) . "</td>";
            echo "<td>" . ($product['status'] === 'active' ? '✓ Activo' : '✗ Inactivo') . "</td>";
            echo "<td>{$product['stock_quantity']}</td>";
            echo "<td>" . ($product['tiene_descripcion'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_categoria'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_marca'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_consola'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_imagen'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_generos'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_precio_ars'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['tiene_precio_usd'] ? '✅' : '❌') . "</td>";
            echo "<td><span class='badge badge-{$problem_color}'>{$problem_count} problemas</span></td>";
            echo "<td><a href='product_edit.php?id={$product['id']}' target='_blank' style='color: #007bff;'>Editar</a></td>";
            echo "</tr>";
            
            // Fila de detalles de problemas
            if (!empty($problems)) {
                echo "<tr style='background: #fff3cd;'>";
                echo "<td colspan='14' style='padding-left: 40px;'>";
                echo "<small><strong>Falta:</strong> " . implode(', ', $problems) . "</small>";
                echo "</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='products.php' style='color: #007bff;'>← Volver a Productos</a></p>";

?>
