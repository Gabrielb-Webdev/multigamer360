<?php
/**
 * DEBUG: Verificar estado de productos
 * Ejecutar este archivo para ver los valores actuales de status e is_active
 */

require_once 'config/database.php';

echo "<h2>🔍 Estado de Productos - Verificación</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    table { background: white; border-collapse: collapse; width: 100%; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    th { background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; }
    td { padding: 10px; border-bottom: 1px solid #ddd; }
    tr:hover { background: #f8f9fa; }
    .ok { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #28a745; color: white; }
    .badge-danger { background: #dc3545; color: white; }
    .badge-warning { background: #ffc107; color: black; }
</style>";

try {
    // Obtener todos los productos
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            status,
            is_active,
            created_at,
            updated_at
        FROM products
        ORDER BY id DESC
        LIMIT 20
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Últimos 20 Productos</h3>";
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>status (ENUM)</th>
            <th>is_active (INT)</th>
            <th>Estado</th>
            <th>Sincronización</th>
          </tr>";
    
    foreach ($products as $product) {
        $status = $product['status'] ?? 'NULL';
        $is_active = $product['is_active'] ?? 'NULL';
        
        // Verificar sincronización
        $sync_ok = false;
        if ($status === 'active' && $is_active == 1) {
            $sync_ok = true;
        } elseif ($status === 'inactive' && $is_active == 0) {
            $sync_ok = true;
        } elseif ($status === 'draft' && $is_active == 0) {
            $sync_ok = true;
        }
        
        $sync_class = $sync_ok ? 'ok' : 'error';
        $sync_text = $sync_ok ? '✓ OK' : '✗ INCONSISTENTE';
        
        // Badge del estado
        if ($status === 'active') {
            $badge = '<span class="badge badge-success">ACTIVO</span>';
        } elseif ($status === 'inactive') {
            $badge = '<span class="badge badge-danger">INACTIVO</span>';
        } elseif ($status === 'draft') {
            $badge = '<span class="badge badge-warning">BORRADOR</span>';
        } else {
            $badge = '<span class="badge badge-danger">NULL/VACÍO</span>';
        }
        
        echo "<tr>";
        echo "<td><strong>{$product['id']}</strong></td>";
        echo "<td>" . htmlspecialchars(substr($product['name'], 0, 40)) . "</td>";
        echo "<td><code>'{$status}'</code></td>";
        echo "<td><code>{$is_active}</code></td>";
        echo "<td>{$badge}</td>";
        echo "<td class='{$sync_class}'>{$sync_text}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Estadísticas
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activos,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactivos,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as borradores,
            SUM(CASE WHEN status IS NULL OR status = '' THEN 1 ELSE 0 END) as sin_status,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as is_active_si,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as is_active_no
        FROM products
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📈 Estadísticas Generales</h3>";
    echo "<table>";
    echo "<tr><th>Métrica</th><th>Valor</th></tr>";
    echo "<tr><td>Total de Productos</td><td><strong>{$stats['total']}</strong></td></tr>";
    echo "<tr><td colspan='2'><hr></td></tr>";
    echo "<tr><td>status = 'active'</td><td><span class='badge badge-success'>{$stats['activos']}</span></td></tr>";
    echo "<tr><td>status = 'inactive'</td><td><span class='badge badge-danger'>{$stats['inactivos']}</span></td></tr>";
    echo "<tr><td>status = 'draft'</td><td><span class='badge badge-warning'>{$stats['borradores']}</span></td></tr>";
    echo "<tr><td>status = NULL/vacío</td><td><span class='badge badge-danger'>{$stats['sin_status']}</span></td></tr>";
    echo "<tr><td colspan='2'><hr></td></tr>";
    echo "<tr><td>is_active = 1</td><td><strong>{$stats['is_active_si']}</strong></td></tr>";
    echo "<tr><td>is_active = 0</td><td><strong>{$stats['is_active_no']}</strong></td></tr>";
    echo "</table>";
    
    // Verificar producto específico (ID 59)
    echo "<h3>🎯 Producto ID 59 (Ejemplo)</h3>";
    $product_59 = $pdo->query("SELECT * FROM products WHERE id = 59")->fetch(PDO::FETCH_ASSOC);
    
    if ($product_59) {
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$product_59['id']}</td></tr>";
        echo "<tr><td>Nombre</td><td>" . htmlspecialchars($product_59['name']) . "</td></tr>";
        echo "<tr><td><strong>status</strong></td><td><code>'{$product_59['status']}'</code></td></tr>";
        echo "<tr><td><strong>is_active</strong></td><td><code>{$product_59['is_active']}</code></td></tr>";
        echo "<tr><td>updated_at</td><td>{$product_59['updated_at']}</td></tr>";
        echo "</table>";
    } else {
        echo "<p class='error'>⚠️ No se encontró producto con ID 59</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📋 Acciones Recomendadas</h3>";
echo "<ol>";
echo "<li>Si hay productos con status NULL/vacío, ejecutar <code>fix_product_status.sql</code></li>";
echo "<li>Si hay inconsistencias, verificar que product_edit.php esté guardando ambos campos</li>";
echo "<li>Probar editar un producto y verificar que status e is_active se actualicen juntos</li>";
echo "</ol>";

?>
