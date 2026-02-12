<?php
// Configuraciones adicionales del perfil
// Este archivo se incluye para mejorar la conectividad de datos

if (!function_exists('getUserStats')) {
    function getUserStats($userId, $pdo) {
        try {
            // Estadísticas de pedidos
            $orderStats = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total), 0) as total_spent,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders
                FROM orders 
                WHERE user_id = ?
            ");
            $orderStats->execute([$userId]);
            $stats = $orderStats->fetch();
            
            // Productos favoritos (wishlist)
            $wishlistStats = $pdo->prepare("SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = ?");
            $wishlistStats->execute([$userId]);
            $wishlist = $wishlistStats->fetch();
            
            // Productos comprados únicos
            $uniqueProducts = $pdo->prepare("
                SELECT COUNT(DISTINCT oi.product_id) as unique_products 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.user_id = ?
            ");
            $uniqueProducts->execute([$userId]);
            $products = $uniqueProducts->fetch();
            
            return [
                'total_orders' => $stats['total_orders'] ?? 0,
                'total_spent' => $stats['total_spent'] ?? 0,
                'delivered_orders' => $stats['delivered_orders'] ?? 0,
                'pending_orders' => $stats['pending_orders'] ?? 0,
                'wishlist_count' => $wishlist['wishlist_count'] ?? 0,
                'unique_products' => $products['unique_products'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Error en getUserStats: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'total_spent' => 0,
                'delivered_orders' => 0,
                'pending_orders' => 0,
                'wishlist_count' => 0,
                'unique_products' => 0
            ];
        }
    }
}

if (!function_exists('getRecentActivity')) {
    function getRecentActivity($userId, $pdo) {
        try {
            // Últimos 5 pedidos
            $recentOrders = $pdo->prepare("
                SELECT id, order_number, status, total, created_at 
                FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $recentOrders->execute([$userId]);
            
            return $recentOrders->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error en getRecentActivity: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getUserAddresses')) {
    function getUserAddresses($userId, $pdo) {
        try {
            $addresses = $pdo->prepare("
                SELECT * FROM user_addresses 
                WHERE user_id = ? 
                ORDER BY is_default DESC, created_at DESC
            ");
            $addresses->execute([$userId]);
            
            return $addresses->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error en getUserAddresses: " . $e->getMessage());
            return [];
        }
    }
}
?>