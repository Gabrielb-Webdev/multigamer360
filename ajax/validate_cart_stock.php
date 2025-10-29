<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener user_id si está logueado
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => true, 'items' => [], 'has_issues' => false]);
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
    $products_to_delete_from_db = []; // IDs a eliminar de la BD
    
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
            
            // Eliminar de la sesión
            unset($_SESSION['cart'][$product_id]);
            
            // Marcar para eliminar de la BD
            $products_to_delete_from_db[] = $product_id;
            
            error_log("Producto $product_id ({$product['name']}) eliminado del carrito - sin stock");
            
        } elseif ($requested_quantity > $available_stock) {
            // Stock insuficiente - ajustar cantidad
            $item_status['status'] = 'stock_limited';
            $item_status['message'] = "Solo hay $available_stock unidades disponibles";
            $item_status['adjusted_quantity'] = $available_stock;
            $has_issues = true;
            
            // Ajustar cantidad en la sesión
            $_SESSION['cart'][$product_id] = $available_stock;
            
            // Actualizar cantidad en la BD
            if ($user_id) {
                $stmt_update = $pdo->prepare("
                    UPDATE cart_sessions 
                    SET quantity = ? 
                    WHERE user_id = ? AND product_id = ?
                ");
                $stmt_update->execute([$available_stock, $user_id, $product_id]);
            }
            
            error_log("Producto $product_id ({$product['name']}) ajustado a $available_stock unidades");
        }
        
        $validation_results[] = $item_status;
    }
    
    // Eliminar productos agotados de la BASE DE DATOS
    if ($user_id && !empty($products_to_delete_from_db)) {
        $placeholders_delete = str_repeat('?,', count($products_to_delete_from_db) - 1) . '?';
        $stmt_delete = $pdo->prepare("
            DELETE FROM cart_sessions 
            WHERE user_id = ? AND product_id IN ($placeholders_delete)
        ");
        $params = array_merge([$user_id], $products_to_delete_from_db);
        $stmt_delete->execute($params);
        
        error_log("Eliminados " . count($products_to_delete_from_db) . " productos de la BD para usuario $user_id");
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
