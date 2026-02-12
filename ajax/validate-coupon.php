<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
$cart_total = floatval($_POST['cart_total'] ?? 0);
$user_id = $_SESSION['user_id'];

if (empty($coupon_code)) {
    echo json_encode(['success' => false, 'message' => 'Código de cupón requerido']);
    exit;
}

if ($cart_total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total del carrito inválido']);
    exit;
}

try {
    // Buscar el cupón
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND is_active = 1 
        AND (start_date IS NULL OR start_date <= NOW()) 
        AND (end_date IS NULL OR end_date > NOW())
    ");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Cupón inválido o expirado']);
        exit;
    }

    // Verificar monto mínimo
    if ($cart_total < $coupon['minimum_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => "El monto mínimo para este cupón es $" . number_format($coupon['minimum_amount'], 2)
        ]);
        exit;
    }

    // Verificar límite de uso general
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Este cupón ha alcanzado su límite de uso']);
        exit;
    }

    // Verificar límite de uso por usuario
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM coupon_usage 
        WHERE coupon_id = ? AND user_id = ?
    ");
    $stmt->execute([$coupon['id'], $user_id]);
    $user_usage = $stmt->fetchColumn();

    if ($user_usage >= $coupon['per_user_limit']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ya has usado este cupón el máximo número de veces permitidas'
        ]);
        exit;
    }

    // Calcular descuento
    $discount_amount = 0;
    if ($coupon['type'] === 'percentage') {
        $discount_amount = ($cart_total * $coupon['value']) / 100;
        
        // Aplicar descuento máximo si está definido
        if ($coupon['maximum_discount'] && $discount_amount > $coupon['maximum_discount']) {
            $discount_amount = $coupon['maximum_discount'];
        }
    } else {
        $discount_amount = $coupon['value'];
    }

    // Asegurar que el descuento no sea mayor al total
    if ($discount_amount > $cart_total) {
        $discount_amount = $cart_total;
    }

    $new_total = $cart_total - $discount_amount;

    // Guardar cupón en sesión para aplicar en el checkout
    $_SESSION['applied_coupon'] = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'name' => $coupon['name'],
        'type' => $coupon['type'],
        'value' => $coupon['value'],
        'discount_amount' => $discount_amount,
        'cart_total' => $cart_total,
        'new_total' => $new_total
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Cupón aplicado exitosamente',
        'coupon' => [
            'code' => $coupon['code'],
            'name' => $coupon['name'],
            'type' => $coupon['type'],
            'value' => $coupon['value'],
            'discount_amount' => $discount_amount,
            'cart_total' => $cart_total,
            'new_total' => $new_total
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
