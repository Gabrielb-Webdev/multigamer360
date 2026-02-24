<?php
/**
 * =====================================================
 * TEST DE DIAGNÓSTICO - Sistema de Envíos
 * Verificación completa - Actualizado 24/02/2026 16:50
 * =====================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico del Sistema de Envíos</h1>";
echo "<hr>";

// 1. Verificar archivos
echo "<h2>1. Verificación de Archivos</h2>";

$files = [
    'config/database.php',
    'includes/shipping_calculator.php',
    'ajax/calculate-shipping.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    $status = $exists ? '✅ EXISTE' : '❌ NO EXISTE';
    echo "<p><strong>{$file}</strong>: {$status}</p>";
    
    if ($exists) {
        $size = filesize($file);
        $modified = date("Y-m-d H:i:s", filemtime($file));
        echo "<p style='margin-left: 20px;'>Tamaño: {$size} bytes | Modificado: {$modified}</p>";
    }
}

// 2. Verificar conexión a base de datos
echo "<hr><h2>2. Conexión a Base de Datos</h2>";

try {
    require_once 'config/database.php';
    echo "<p>✅ Conexión exitosa a la base de datos</p>";
    
    // 3. Verificar tabla shipping_providers
    echo "<hr><h2>3. Tabla shipping_providers</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipping_providers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de proveedores: <strong>{$result['total']}</strong></p>";
    
    if ($result['total'] > 0) {
        $stmt = $pdo->query("SELECT provider_key, provider_name, is_active, coverage_type FROM shipping_providers");
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>Proveedor</th><th>Nombre</th><th>Activo</th><th>Cobertura</th></tr>";
        
        foreach ($providers as $p) {
            $active = $p['is_active'] ? '✅ Sí' : '❌ No';
            echo "<tr>";
            echo "<td>{$p['provider_key']}</td>";
            echo "<td>{$p['provider_name']}</td>";
            echo "<td>{$active}</td>";
            echo "<td>{$p['coverage_type']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 4. Probar ShippingCalculator
    echo "<hr><h2>4. Prueba de ShippingCalculator</h2>";
    
    if (file_exists('includes/shipping_calculator.php')) {
        require_once 'includes/shipping_calculator.php';
        
        $calculator = new ShippingCalculator($pdo);
        echo "<p>✅ Clase ShippingCalculator cargada correctamente</p>";
        
        // Probar con S2001 (Rosario)
        echo "<h3>Probando envío a S2001 (Rosario)</h3>";
        $options = $calculator->calculateShipping('S2001', 1.0, 140000);
        
        if (empty($options)) {
            echo "<p>❌ No se encontraron opciones de envío</p>";
        } else {
            echo "<p>✅ Se encontraron <strong>" . count($options) . "</strong> opciones:</p>";
            echo "<ul>";
            foreach ($options as $opt) {
                echo "<li><strong>{$opt['service_name']}</strong>: \${$opt['price']} - {$opt['delivery_days']} días</li>";
            }
            echo "</ul>";
        }
        
        // Probar con 1426 (CABA)
        echo "<h3>Probando envío a 1426 (CABA)</h3>";
        $options2 = $calculator->calculateShipping('1426', 1.0, 140000);
        
        if (empty($options2)) {
            echo "<p>❌ No se encontraron opciones de envío</p>";
        } else {
            echo "<p>✅ Se encontraron <strong>" . count($options2) . "</strong> opciones:</p>";
            echo "<ul>";
            foreach ($options2 as $opt) {
                echo "<li><strong>{$opt['service_name']}</strong>: \${$opt['price']} - {$opt['delivery_days']} días</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>❌ No se puede cargar ShippingCalculator (archivo no encontrado)</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><em>Diagnóstico completado - " . date('Y-m-d H:i:s') . "</em></p>";
?>
