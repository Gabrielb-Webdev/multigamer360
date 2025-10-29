<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => true, 'items' => []]);
        exit();
    }
    
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    // Obtener stock actual de todos los productos en el carrito
    $stmt = $pdo->prepare("
        SELECT id, name, stock_quantity 
        FROM products 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $validation_results = [];
    $has_issues = false;
    
    foreach ($products as $product) {
        $product_id = $product['id'];
        $requested_quantity = $_SESSION['cart'][$product_id];
        $available_stock = $product['stock_quantity'];
        
        $item_status = [
            'id' => $product_id,
            'name' => $product['name'],
            'requested_quantity' => $requested_quantity,
            'available_stock' => $available_stock,
            'status' => 'ok'
        ];
        
        if ($available_stock <= 0) {
            // Sin stock - debe eliminarse
            $item_status['status'] = 'out_of_stock';
            $item_status['message'] = 'Producto agotado - debe ser eliminado del carrito';
            $has_issues = true;
            
            // Eliminar automáticamente del carrito
            unset($_SESSION['cart'][$product_id]);
            
        } elseif ($requested_quantity > $available_stock) {
            // Stock insuficiente - ajustar cantidad
            $item_status['status'] = 'stock_limited';
            $item_status['message'] = "Solo hay $available_stock unidades disponibles";
            $item_status['adjusted_quantity'] = $available_stock;
            $has_issues = true;
            
            // Ajustar automáticamente la cantidad
            $_SESSION['cart'][$product_id] = $available_stock;
        }
        
        $validation_results[] = $item_status;
    }
    
    // Limpiar carrito si está vacío
    if (empty($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    echo json_encode([
        'success' => true,
        'has_issues' => $has_issues,
        'items' => $validation_results
    ]);
    
} catch (Exception $e) {
    error_log("Error validando stock del carrito: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al validar stock'
    ]);
}
?>
