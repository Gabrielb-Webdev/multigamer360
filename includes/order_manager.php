<?php
require_once 'config/database.php';

class OrderManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Crear nuevo pedido
    public function createOrder($orderData) {
        try {
            $this->pdo->beginTransaction();
            
            // Generar número de pedido único
            $orderNumber = $this->generateOrderNumber();
            
            // Insertar pedido
            $sql = "INSERT INTO orders (
                order_number, user_id, status, payment_status, payment_method, 
                shipping_method_id, subtotal, shipping_cost, tax_amount, 
                discount_amount, total, coupon_code,
                billing_first_name, billing_last_name, billing_email, billing_phone,
                billing_company, billing_address_1, billing_address_2, billing_city,
                billing_state, billing_postal_code, billing_country,
                shipping_first_name, shipping_last_name, shipping_company,
                shipping_address_1, shipping_address_2, shipping_city,
                shipping_state, shipping_postal_code, shipping_country, notes
            ) VALUES (
                :order_number, :user_id, :status, :payment_status, :payment_method,
                :shipping_method_id, :subtotal, :shipping_cost, :tax_amount,
                :discount_amount, :total, :coupon_code,
                :billing_first_name, :billing_last_name, :billing_email, :billing_phone,
                :billing_company, :billing_address_1, :billing_address_2, :billing_city,
                :billing_state, :billing_postal_code, :billing_country,
                :shipping_first_name, :shipping_last_name, :shipping_company,
                :shipping_address_1, :shipping_address_2, :shipping_city,
                :shipping_state, :shipping_postal_code, :shipping_country, :notes
            )";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind todos los parámetros
            $stmt->bindParam(':order_number', $orderNumber);
            $stmt->bindParam(':user_id', $orderData['user_id']);
            $stmt->bindParam(':status', $orderData['status']);
            $stmt->bindParam(':payment_status', $orderData['payment_status']);
            $stmt->bindParam(':payment_method', $orderData['payment_method']);
            $stmt->bindParam(':shipping_method_id', $orderData['shipping_method_id']);
            $stmt->bindParam(':subtotal', $orderData['subtotal']);
            $stmt->bindParam(':shipping_cost', $orderData['shipping_cost']);
            $stmt->bindParam(':tax_amount', $orderData['tax_amount']);
            $stmt->bindParam(':discount_amount', $orderData['discount_amount']);
            $stmt->bindParam(':total', $orderData['total']);
            $stmt->bindParam(':coupon_code', $orderData['coupon_code']);
            
            // Datos de facturación
            $stmt->bindParam(':billing_first_name', $orderData['billing_first_name']);
            $stmt->bindParam(':billing_last_name', $orderData['billing_last_name']);
            $stmt->bindParam(':billing_email', $orderData['billing_email']);
            $stmt->bindParam(':billing_phone', $orderData['billing_phone']);
            $stmt->bindParam(':billing_company', $orderData['billing_company']);
            $stmt->bindParam(':billing_address_1', $orderData['billing_address_1']);
            $stmt->bindParam(':billing_address_2', $orderData['billing_address_2']);
            $stmt->bindParam(':billing_city', $orderData['billing_city']);
            $stmt->bindParam(':billing_state', $orderData['billing_state']);
            $stmt->bindParam(':billing_postal_code', $orderData['billing_postal_code']);
            $stmt->bindParam(':billing_country', $orderData['billing_country']);
            
            // Datos de envío
            $stmt->bindParam(':shipping_first_name', $orderData['shipping_first_name']);
            $stmt->bindParam(':shipping_last_name', $orderData['shipping_last_name']);
            $stmt->bindParam(':shipping_company', $orderData['shipping_company']);
            $stmt->bindParam(':shipping_address_1', $orderData['shipping_address_1']);
            $stmt->bindParam(':shipping_address_2', $orderData['shipping_address_2']);
            $stmt->bindParam(':shipping_city', $orderData['shipping_city']);
            $stmt->bindParam(':shipping_state', $orderData['shipping_state']);
            $stmt->bindParam(':shipping_postal_code', $orderData['shipping_postal_code']);
            $stmt->bindParam(':shipping_country', $orderData['shipping_country']);
            $stmt->bindParam(':notes', $orderData['notes']);
            
            $stmt->execute();
            $orderId = $this->pdo->lastInsertId();
            
            // Insertar items del pedido
            foreach ($orderData['items'] as $item) {
                $this->addOrderItem($orderId, $item);
            }
            
            // Actualizar stock de productos
            foreach ($orderData['items'] as $item) {
                $this->updateProductStock($item['product_id'], $item['quantity']);
            }
            
            // Si se usó un cupón, incrementar contador
            if (!empty($orderData['coupon_code'])) {
                $this->incrementCouponUsage($orderData['coupon_code']);
            }
            
            $this->pdo->commit();
            
            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error al crear pedido: ' . $e->getMessage()];
        }
    }
    
    // Generar número de pedido único
    private function generateOrderNumber() {
        $prefix = 'MG360-';
        $timestamp = time();
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . substr($timestamp, -6) . $random;
    }
    
    // Agregar item al pedido
    private function addOrderItem($orderId, $item) {
        $sql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, total) 
                VALUES (:order_id, :product_id, :product_name, :product_sku, :quantity, :price, :total)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $item['product_name']);
        $stmt->bindParam(':product_sku', $item['product_sku']);
        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':price', $item['price']);
        $stmt->bindParam(':total', $item['total']);
        
        return $stmt->execute();
    }
    
    // Actualizar stock del producto
    private function updateProductStock($productId, $quantity) {
        $sql = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Incrementar uso de cupón
    private function incrementCouponUsage($couponCode) {
        $sql = "UPDATE coupons SET used_count = used_count + 1 WHERE code = :code";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':code', $couponCode);
        return $stmt->execute();
    }
    
    // Obtener pedido por ID
    public function getOrderById($orderId) {
        $sql = "SELECT o.*, sm.name as shipping_method_name, sm.description as shipping_method_description
                FROM orders o
                LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
                WHERE o.id = :order_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        $order = $stmt->fetch();
        
        if ($order) {
            $order['items'] = $this->getOrderItems($orderId);
        }
        
        return $order;
    }
    
    // Obtener pedido por número
    public function getOrderByNumber($orderNumber) {
        $sql = "SELECT o.*, sm.name as shipping_method_name, sm.description as shipping_method_description
                FROM orders o
                LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
                WHERE o.order_number = :order_number";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':order_number', $orderNumber);
        $stmt->execute();
        
        $order = $stmt->fetch();
        
        if ($order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }
        
        return $order;
    }
    
    // Obtener items del pedido
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.image_url 
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener pedidos de un usuario
    public function getUserOrders($userId, $limit = 10) {
        $sql = "SELECT o.*, sm.name as shipping_method_name
                FROM orders o
                LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
                WHERE o.user_id = :user_id
                ORDER BY o.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Actualizar estado del pedido
    public function updateOrderStatus($orderId, $status) {
        $sql = "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :order_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al actualizar estado: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Error desconocido al actualizar estado'];
    }
    
    // Obtener métodos de envío
    public function getShippingMethods() {
        $sql = "SELECT * FROM shipping_methods WHERE is_active = TRUE ORDER BY cost ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    // Validar cupón
    public function validateCoupon($code, $orderTotal = 0) {
        $sql = "SELECT * FROM coupons 
                WHERE code = :code 
                AND is_active = TRUE 
                AND (starts_at IS NULL OR starts_at <= NOW())
                AND (expires_at IS NULL OR expires_at >= NOW())
                AND (usage_limit IS NULL OR used_count < usage_limit)
                AND minimum_amount <= :order_total";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':order_total', $orderTotal);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // Calcular descuento del cupón
    public function calculateCouponDiscount($coupon, $orderTotal) {
        if (!$coupon) return 0;
        
        if ($coupon['type'] === 'percentage') {
            $discount = ($orderTotal * $coupon['value']) / 100;
            
            // Aplicar límite máximo si existe
            if ($coupon['maximum_discount'] && $discount > $coupon['maximum_discount']) {
                $discount = $coupon['maximum_discount'];
            }
        } else {
            $discount = $coupon['value'];
        }
        
        return min($discount, $orderTotal); // No puede ser mayor al total
    }
}

// Crear instancia global
$orderManager = new OrderManager($pdo);
?>