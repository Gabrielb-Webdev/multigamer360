<?php
require_once 'database.php';

// Marcas adicionales para completar las 15 principales
$additional_brands = [
    ['name' => 'AMD', 'slug' => 'amd', 'logo_url' => 'uploads/brands/amd.png'],
    ['name' => 'NVIDIA', 'slug' => 'nvidia', 'logo_url' => 'uploads/brands/nvidia.png'],
    ['name' => 'Razer', 'slug' => 'razer', 'logo_url' => 'uploads/brands/razer.png'],
    ['name' => 'Logitech', 'slug' => 'logitech', 'logo_url' => 'uploads/brands/logitech.png'],
    ['name' => 'Corsair', 'slug' => 'corsair', 'logo_url' => 'uploads/brands/corsair.png'],
    ['name' => 'SteelSeries', 'slug' => 'steelseries', 'logo_url' => 'uploads/brands/steelseries.png'],
    ['name' => 'HyperX', 'slug' => 'hyperx', 'logo_url' => 'uploads/brands/hyperx.png']
];

try {
    echo "Agregando marcas adicionales..." . PHP_EOL;
    
    $stmt = $pdo->prepare("INSERT INTO brands (name, slug, logo_url, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE updated_at = NOW()");
    
    foreach ($additional_brands as $brand) {
        $stmt->execute([$brand['name'], $brand['slug'], $brand['logo_url']]);
        echo "✓ Marca agregada: " . $brand['name'] . PHP_EOL;
    }
    
    // Verificar total
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM brands WHERE is_active = 1');
    $count = $stmt->fetch()['total'];
    echo PHP_EOL . "Total de marcas activas: " . $count . PHP_EOL;
    
    // Mostrar todas las marcas
    echo PHP_EOL . "=== TODAS LAS MARCAS GAMING ===" . PHP_EOL;
    $stmt = $pdo->query('SELECT name, slug FROM brands WHERE is_active = 1 ORDER BY name');
    $i = 1;
    while ($row = $stmt->fetch()) {
        echo $i . ". " . $row['name'] . " (" . $row['slug'] . ")" . PHP_EOL;
        $i++;
    }
    
    echo PHP_EOL . "✅ Sistema de marcas completado!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>