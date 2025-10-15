<?php
/**
 * Test para verificar por qué no aparecen productos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session_config.php';
require_once 'config/database.php';
require_once 'includes/product_manager.php';

echo "<h2>Test de Productos - Diagnóstico</h2>";

$productManager = new ProductManager($pdo);

// 1. Verificar productos en la base de datos
echo "<h3>1. Productos activos en base de datos:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = TRUE");
$result = $stmt->fetch();
echo "<p><strong>Total productos activos: " . $result['total'] . "</strong></p>";

// 2. Ver todos los productos sin filtros
echo "<h3>2. Productos sin filtros:</h3>";
$products = $productManager->getProducts();
echo "<p><strong>Productos obtenidos: " . count($products) . "</strong></p>";

if (!empty($products)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Categoría ID</th><th>Marca ID</th><th>Console ID</th><th>Active</th><th>Stock</th></tr>";
    foreach ($products as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['name']}</td>";
        echo "<td>{$p['category_id']}</td>";
        echo "<td>{$p['brand_id']}</td>";
        echo "<td>" . ($p['console_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($p['is_active'] ? 'Sí' : 'No') . "</td>";
        echo "<td>{$p['stock_quantity']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No se obtuvieron productos</p>";
}

// 3. Test con filtros vacíos (como en productos.php)
echo "<h3>3. Test con filtros vacíos (como productos.php):</h3>";
$filters = [
    'order_by' => 'p.created_at DESC',
    'limit' => 20,
    'offset' => 0
];

$products2 = $productManager->getProducts($filters);
echo "<p><strong>Productos con filtros básicos: " . count($products2) . "</strong></p>";

// 4. Verificar categorías disponibles
echo "<h3>4. Categorías en la base de datos:</h3>";
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
foreach ($categories as $cat) {
    echo "<tr>";
    echo "<td>{$cat['id']}</td>";
    echo "<td>{$cat['name']}</td>";
    echo "<td>{$cat['slug']}</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Verificar marcas disponibles
echo "<h3>5. Marcas en la base de datos:</h3>";
$stmt = $pdo->query("SELECT * FROM brands");
$brands = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
foreach ($brands as $brand) {
    echo "<tr>";
    echo "<td>{$brand['id']}</td>";
    echo "<td>{$brand['name']}</td>";
    echo "<td>{$brand['slug']}</td>";
    echo "</tr>";
}
echo "</table>";

// 6. Verificar consolas disponibles
echo "<h3>6. Consolas en la base de datos:</h3>";
$stmt = $pdo->query("SELECT * FROM consoles");
$consoles = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
foreach ($consoles as $console) {
    echo "<tr>";
    echo "<td>{$console['id']}</td>";
    echo "<td>{$console['name']}</td>";
    echo "<td>{$console['slug']}</td>";
    echo "</tr>";
}
echo "</table>";

// 7. Verificar estructura SQL completa
echo "<h3>7. Query SQL completo (debug):</h3>";
$sql = "SELECT p.*, 
               c.name as category_name, 
               b.name as brand_name,
               co.name as console_name, 
               co.slug as console_slug,
               p.created_at as publication_date
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN consoles co ON p.console_id = co.id
        WHERE p.is_active = TRUE
        ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC
        LIMIT 20";

echo "<pre>" . htmlspecialchars($sql) . "</pre>";

$stmt = $pdo->query($sql);
$products3 = $stmt->fetchAll();
echo "<p><strong>Productos obtenidos con query directo: " . count($products3) . "</strong></p>";

?>
