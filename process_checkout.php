<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependencias
require_once 'config/database.php';
require_once 'includes/cart_manager.php';

// Verificar si hay productos en el carrito
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: carrito.php');
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}

// Validar datos requeridos
$required_fields = ['firstName', 'lastName', 'email', 'phone', 'paymentMethod'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $_SESSION['checkout_error'] = "Todos los campos requeridos deben ser completados.";
        header('Location: checkout.php');
        exit();
    }
}

// Obtener datos del formulario
$firstName = trim($_POST['firstName']);
$lastName = trim($_POST['lastName']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$paymentMethod = $_POST['paymentMethod'];

// Obtener método de envío de la sesión (ya fue guardado en el carrito)
$shippingMethod = $_SESSION['shipping_method'] ?? '';
$shippingCost = $_SESSION['shipping_cost'] ?? 0;
$shippingName = $_SESSION['shipping_name'] ?? 'No especificado';
$postalCode = $_SESSION['postal_code'] ?? '';

if (empty($shippingMethod)) {
    $_SESSION['checkout_error'] = "Método de envío no seleccionado.";
    header('Location: carrito.php');
    exit();
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['checkout_error'] = "El formato del email no es válido.";
    header('Location: checkout.php');
    exit();
}

// Validar datos de dirección para envíos a domicilio
$delivery_methods = ['5648', '6214', '3500']; // correoNube, correoExpreso, motoCABA
$address = '';
$city = '';
$province = '';
$zipCode = '';

if (in_array($shippingMethod, $delivery_methods)) {
    $address_fields = ['address', 'city', 'province', 'zipCode'];
    
    foreach ($address_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $_SESSION['checkout_error'] = "La dirección completa es requerida para envíos a domicilio.";
            header('Location: checkout.php');
            exit();
        }
    }
    
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $zipCode = trim($_POST['zipCode']);
}

// Validar método de pago según tipo de envío
$pickup_methods = ['0', '2207']; // multigamer360, puntoRetiro
$valid_payment_methods = [];

if (in_array($shippingMethod, $pickup_methods)) {
    $valid_payment_methods = ['local', 'online'];
} else {
    $valid_payment_methods = ['online', 'cod'];
}

if (!in_array($paymentMethod, $valid_payment_methods)) {
    $_SESSION['checkout_error'] = "Método de pago no válido para el tipo de envío seleccionado.";
    header('Location: checkout.php');
    exit();
}

// =====================================================
// OBTENER PRODUCTOS DE LA BASE DE DATOS
// =====================================================
$subtotal = 0;
$cart_items = [];

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price_pesos as price, pi.image_url
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.id IN ($placeholders)
    ");
    
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $item_total = $product['price'] * $quantity;
        $subtotal += $item_total;
        
        // Construir ruta completa de la imagen
        $image_path = 'uploads/products/default.jpg';
        if (!empty($product['image_url'])) {
            if (strpos($product['image_url'], 'uploads/products/') === 0) {
                $image_path = $product['image_url'];
            } else {
                $image_path = 'uploads/products/' . $product['image_url'];
            }
        }
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $item_total,
            'image' => $image_path
        ];
    }
}

// Procesar cupón aplicado si existe
$coupon_discount = 0;
$coupon_data = null;
if (isset($_SESSION['applied_coupon'])) {
    $coupon_data = $_SESSION['applied_coupon'];
    
    // Verificar que el cupón sigue siendo válido para el subtotal actual
    if ($coupon_data['cart_total'] == $subtotal) {
        $coupon_discount = $coupon_data['discount_amount'];
    } else {
        // Recalcular descuento si el subtotal cambió
        try {
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? AND is_active = 1");
            $stmt->execute([$coupon_data['id']]);
            $coupon = $stmt->fetch();
            
            if ($coupon && $subtotal >= $coupon['minimum_amount']) {
                if ($coupon['type'] === 'percentage') {
                    $coupon_discount = ($subtotal * $coupon['value']) / 100;
                    if ($coupon['maximum_discount'] && $coupon_discount > $coupon['maximum_discount']) {
                        $coupon_discount = $coupon['maximum_discount'];
                    }
                } else {
                    $coupon_discount = $coupon['value'];
                }
                
                if ($coupon_discount > $subtotal) {
                    $coupon_discount = $subtotal;
                }
                
                // Actualizar datos del cupón en sesión
                $_SESSION['applied_coupon']['discount_amount'] = $coupon_discount;
                $_SESSION['applied_coupon']['cart_total'] = $subtotal;
                $_SESSION['applied_coupon']['new_total'] = $subtotal - $coupon_discount;
            } else {
                // Cupón ya no es válido
                unset($_SESSION['applied_coupon']);
                $coupon_data = null;
            }
        } catch (Exception $e) {
            // Error al validar cupón, removerlo
            unset($_SESSION['applied_coupon']);
            $coupon_data = null;
        }
    }
}

$subtotal_with_discount = $subtotal - $coupon_discount;
$total = $subtotal_with_discount + $shippingCost;

// Obtener nombres de métodos
$payment_names = [
    'local' => 'Pagar en el Local',
    'online' => 'Pago Online',
    'cod' => 'Contra Entrega'
];

$payment_name = $payment_names[$paymentMethod] ?? 'Desconocido';

// Generar ID de orden único
$order_id = 'MG360-' . date('Ymd') . '-' . rand(1000, 9999);

// Preparar datos de usuario
$user_id = $_SESSION['user_id'] ?? null;

// =====================================================
// GUARDAR ORDEN EN LA BASE DE DATOS
// =====================================================
try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Insertar orden principal
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, user_id, 
            customer_first_name, customer_last_name, customer_email, customer_phone,
            shipping_address, shipping_city, shipping_province, shipping_postal_code,
            shipping_method, shipping_cost,
            payment_method, payment_status,
            subtotal, discount_amount, total_amount,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $order_id,
        $user_id,
        $firstName,
        $lastName,
        $email,
        $phone,
        $address ?: null,
        $city ?: null,
        $province ?: null,
        $postalCode,
        $shippingName,
        $shippingCost,
        $payment_name,
        'pending',
        $subtotal,
        $coupon_discount,
        $total,
        'pending'
    ]);
    
    $inserted_order_id = $pdo->lastInsertId();
    
    // Insertar items de la orden Y DESCONTAR STOCK
    $stmt_insert_item = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt_update_stock = $pdo->prepare("
        UPDATE products 
        SET stock_quantity = stock_quantity - ? 
        WHERE id = ? AND stock_quantity >= ?
    ");
    
    $stmt_check_stock = $pdo->prepare("
        SELECT id, name, stock_quantity FROM products WHERE id = ?
    ");
    
    foreach ($cart_items as $item) {
        // Verificar stock actual
        $stmt_check_stock->execute([$item['id']]);
        $product_check = $stmt_check_stock->fetch(PDO::FETCH_ASSOC);
        
        error_log("DEBUG - Producto: {$item['name']}, ID: {$item['id']}, Stock actual: " . ($product_check['stock_quantity'] ?? 'NULL') . ", Cantidad a comprar: {$item['quantity']}");
        
        if (!$product_check) {
            throw new Exception("Producto no encontrado: " . $item['name']);
        }
        
        if ($product_check['stock_quantity'] < $item['quantity']) {
            throw new Exception("Stock insuficiente para: " . $item['name'] . " (Disponible: {$product_check['stock_quantity']}, Solicitado: {$item['quantity']})");
        }
        
        // Insertar item de orden (con imagen)
        $image_to_save = $item['image'] ?? null;
        error_log("DEBUG CHECKOUT - Guardando item: {$item['name']}, image: " . ($image_to_save ?? 'NULL'));
        
        $stmt_insert_item->execute([
            $inserted_order_id,
            $item['id'],
            $item['name'],
            $item['quantity'],
            $item['price'],
            $item['total'],
            $image_to_save  // Guardar la imagen
        ]);
        
        // Descontar stock
        $stmt_update_stock->execute([
            $item['quantity'],
            $item['id'],
            $item['quantity']
        ]);
        
        $rows_affected = $stmt_update_stock->rowCount();
        error_log("DEBUG - Stock actualizado para {$item['name']}: $rows_affected filas afectadas");
        
        // Verificar que se actualizó el stock
        if ($rows_affected === 0) {
            throw new Exception("Error al actualizar stock de: " . $item['name'] . " - No se pudo descontar del inventario");
        }
    }
    
    // Guardar uso de cupón si existe
    if ($coupon_data && $coupon_discount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $coupon_data['id'],
            $user_id,
            $inserted_order_id,
            $coupon_discount
        ]);
        
        // Incrementar contador de uso del cupón
        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$coupon_data['id']]);
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    // Crear datos de la orden para mostrar en confirmación
    $order_data = [
        'order_id' => $order_id,
        'date' => date('Y-m-d H:i:s'),
        'customer' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'province' => $province,
            'zip_code' => $zipCode
        ],
        'items' => $cart_items,
        'shipping' => [
            'method' => $shippingMethod,
            'name' => $shippingName,
            'cost' => $shippingCost
        ],
        'payment' => [
            'method' => $paymentMethod,
            'name' => $payment_name
        ],
        'coupon' => $coupon_data ? [
            'code' => $coupon_data['code'],
            'name' => $coupon_data['name'],
            'type' => $coupon_data['type'],
            'value' => $coupon_data['value'],
            'discount_amount' => $coupon_discount
        ] : null,
        'totals' => [
            'subtotal' => $subtotal,
            'coupon_discount' => $coupon_discount,
            'subtotal_with_discount' => $subtotal_with_discount,
            'shipping' => $shippingCost,
            'total' => $total
        ]
    ];
    
    // Guardar en sesión para mostrar confirmación
    $_SESSION['completed_order'] = $order_data;
    
    // Limpiar carrito de la sesión
    $_SESSION['cart'] = [];
    
    // IMPORTANTE: Eliminar el carrito de la BASE DE DATOS también
    if ($user_id) {
        $stmt_delete_cart = $pdo->prepare("DELETE FROM cart_sessions WHERE user_id = ?");
        $stmt_delete_cart->execute([$user_id]);
        error_log("Carrito eliminado de BD para usuario: $user_id");
    }
    
    // Limpiar datos de envío y cupones
    unset($_SESSION['applied_coupon']);
    unset($_SESSION['shipping_method']);
    unset($_SESSION['shipping_cost']);
    unset($_SESSION['shipping_name']);
    unset($_SESSION['postal_code']);
    
    // Redirigir a página de confirmación
    header('Location: order_confirmation.php?order_id=' . $order_id);
    exit();
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    
    error_log("Error al procesar orden: " . $e->getMessage());
    $_SESSION['checkout_error'] = "Hubo un error al procesar tu orden. Por favor, intenta nuevamente.";
    header('Location: checkout.php');
    exit();
}
?>