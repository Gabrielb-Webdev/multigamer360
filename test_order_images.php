<?php
require_once 'config/database.php';

// ID de una orden existente - cambia este número por uno de tus órdenes
$order_number = 'MG360-20251029-5930'; // Cambia esto por el ID de tu última orden

echo "<h2>Test de Imágenes de Orden</h2>";
echo "<p>Orden: $order_number</p>";

try {
    // Obtener orden
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$order_number]);
    $order_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_data) {
        die("Orden no encontrada");
    }
    
    echo "<h3>Orden encontrada - ID: {$order_data['id']}</h3>";
    
    // Obtener items con la misma consulta que usa order_confirmation.php
    $stmt = $pdo->prepare("
        SELECT oi.*, pi.image_url 
        FROM order_items oi 
        LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_data['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Items encontrados: " . count($items) . "</h3>";
    echo "<pre>";
    print_r($items);
    echo "</pre>";
    
    echo "<h3>Construcción de rutas:</h3>";
    foreach ($items as $item) {
        $image_path = 'uploads/products/default.jpg';
        if (!empty($item['image_url'])) {
            if (strpos($item['image_url'], 'uploads/products/') === 0) {
                $image_path = $item['image_url'];
            } else {
                $image_path = 'uploads/products/' . $item['image_url'];
            }
        }
        
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Producto:</strong> {$item['product_name']}<br>";
        echo "<strong>Product ID:</strong> {$item['product_id']}<br>";
        echo "<strong>image_url de DB:</strong> " . ($item['image_url'] ?? 'NULL') . "<br>";
        echo "<strong>Ruta final:</strong> $image_path<br>";
        echo "<strong>URL completa:</strong> <a href='$image_path' target='_blank'>$image_path</a><br>";
        echo "<img src='$image_path' style='max-width: 200px; margin-top: 10px;' onerror='this.style.border=\"2px solid red\"'>";
        echo "</div>";
    }
    
    // También mostrar todas las imágenes disponibles en product_images
    echo "<h3>Todas las imágenes primarias en product_images:</h3>";
    $stmt = $pdo->query("
        SELECT pi.*, p.name as product_name 
        FROM product_images pi 
        LEFT JOIN products p ON pi.product_id = p.id 
        WHERE pi.is_primary = 1
        ORDER BY pi.product_id
    ");
    $all_primary_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($all_primary_images);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
