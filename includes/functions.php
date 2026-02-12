<?php
// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Genera una URL amigable para un producto usando su slug
 * @param array $product Array con datos del producto (debe contener 'slug' o 'id')
 * @return string URL del producto
 */
function getProductUrl($product) {
    // Preferir slug para SEO
    if (!empty($product['slug'])) {
        return 'producto/' . $product['slug'];
    }
    // Fallback a ID si no hay slug
    if (!empty($product['id'])) {
        return 'product-details.php?id=' . $product['id'];
    }
    // Fallback por defecto
    return 'productos.php';
}
?>
