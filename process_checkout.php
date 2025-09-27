<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$required_fields = ['firstName', 'lastName', 'email', 'phone', 'shippingMethod', 'paymentMethod'];

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
$shippingMethod = $_POST['shippingMethod'];
$paymentMethod = $_POST['paymentMethod'];

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

// Productos de ejemplo (simulando base de datos)
$products_data = [
    1 => [
        'id' => 1,
        'name' => 'Rayman 2 the Great Escape PlayStation 1',
        'price' => 45000,
        'image_url' => 'assets/images/products/product1.jpg',
        'category' => 'PlayStation',
        'brand' => 'Sony'
    ],
    2 => [
        'id' => 2,
        'name' => 'Super Mario 64 Nintendo 64',
        'price' => 65000,
        'image_url' => 'assets/images/products/product2.jpg',
        'category' => 'Nintendo 64',
        'brand' => 'Nintendo'
    ],
    3 => [
        'id' => 3,
        'name' => 'Final Fantasy VII PlayStation 1',
        'price' => 55000,
        'image_url' => 'assets/images/products/product3.jpg',
        'category' => 'PlayStation',
        'brand' => 'Sony'
    ],
    4 => [
        'id' => 4,
        'name' => 'The Legend of Zelda: Ocarina of Time Nintendo 64',
        'price' => 75000,
        'image_url' => 'assets/images/products/product4.jpg',
        'category' => 'Nintendo 64',
        'brand' => 'Nintendo'
    ]
];

// Calcular totales
$subtotal = 0;
$cart_items = [];

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    if (isset($products_data[$product_id])) {
        $product = $products_data[$product_id];
        $item_total = $product['price'] * $quantity;
        $subtotal += $item_total;
        
        $cart_items[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $item_total
        ];
    }
}

// Calcular costo de envío
$shipping_costs = [
    '5648' => 5648,  // correoNube
    '6214' => 6214,  // correoExpreso
    '3500' => 3500,  // motoCABA
    '0' => 0,        // multigamer360
    '2207' => 2207   // puntoRetiro
];

$shipping_cost = $shipping_costs[$shippingMethod] ?? 0;

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
$total = $subtotal_with_discount + $shipping_cost;

// Obtener nombres de métodos
$shipping_names = [
    '5648' => 'Correo Nube - Capital Federal',
    '6214' => 'Correo Expreso - Interior del País',
    '3500' => 'Moto - CABA',
    '0' => 'Retirar por MultiGamer360',
    '2207' => 'Punto de Retiro'
];

$payment_names = [
    'local' => 'Pagar en el Local',
    'online' => 'Pago Online',
    'cod' => 'Contra Entrega'
];

$shipping_name = $shipping_names[$shippingMethod] ?? 'Desconocido';
$payment_name = $payment_names[$paymentMethod] ?? 'Desconocido';

// Generar ID de orden único
$order_id = 'MG360-' . date('Ymd') . '-' . rand(1000, 9999);

// Crear datos de la orden
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
        'name' => $shipping_name,
        'cost' => $shipping_cost
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
        'shipping' => $shipping_cost,
        'total' => $total
    ]
];

// Guardar en base de datos si hay cupón aplicado
if ($coupon_data && $coupon_discount > 0) {
    try {
        // Registrar uso del cupón
        $stmt = $pdo->prepare("
            INSERT INTO coupon_usage (coupon_id, user_id, discount_amount) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $coupon_data['id'],
            $_SESSION['user_id'] ?? 0,
            $coupon_discount
        ]);
        
        // Incrementar contador de uso del cupón
        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$coupon_data['id']]);
        
    } catch (Exception $e) {
        error_log("Error al registrar uso de cupón: " . $e->getMessage());
    }
}

// Aquí puedes guardar la orden en base de datos
// Por ahora la guardamos en sesión para mostrar confirmación
$_SESSION['completed_order'] = $order_data;

// Limpiar carrito y cupón aplicado
$_SESSION['cart'] = [];
unset($_SESSION['applied_coupon']);

// Redirigir a página de confirmación
header('Location: order_confirmation.php');
exit();
?>