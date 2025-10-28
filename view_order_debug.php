<?php
session_start();
require_once 'config/database.php';

echo "<h1>Debug de Orden</h1>";
echo "<style>body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; } pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }</style>";

// Si hay order_id en URL
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    echo "<h2>Orden desde BD: $order_id</h2>";
    
    // Obtener orden
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$order_id]);
    $order_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order_data) {
        echo "<h3>Datos de orden:</h3>";
        echo "<pre>" . print_r($order_data, true) . "</pre>";
        
        // Obtener items con imagen
        $stmt = $pdo->prepare("
            SELECT oi.*, p.image_url, p.name as product_name_db
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_data['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Items de orden (con JOIN a products):</h3>";
        foreach ($items as $item) {
            echo "<pre>" . print_r($item, true) . "</pre>";
            
            if (!empty($item['image_url'])) {
                echo "<p>✅ Imagen encontrada: " . htmlspecialchars($item['image_url']) . "</p>";
                echo "<img src='" . htmlspecialchars($item['image_url']) . "' style='max-width: 200px; border: 2px solid #00ff00;' />";
            } else {
                echo "<p>❌ NO hay imagen para este producto</p>";
            }
            echo "<hr>";
        }
    } else {
        echo "<p style='color: red;'>❌ Orden no encontrada</p>";
    }
}

// Si hay orden en sesión
if (isset($_SESSION['completed_order'])) {
    echo "<h2>Orden desde SESIÓN</h2>";
    echo "<pre>" . print_r($_SESSION['completed_order'], true) . "</pre>";
}

if (!isset($_GET['order_id']) && !isset($_SESSION['completed_order'])) {
    echo "<p>No hay orden para debugear. Usa: ?order_id=MG360-XXXXXXXX-XXXX</p>";
}
?>
