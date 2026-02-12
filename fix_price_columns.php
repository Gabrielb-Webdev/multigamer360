<?php
/**
 * Script para reemplazar price_pesos y price_usd por price en TODOS los archivos admin
 */

$replacements = [
    'price_pesos' => 'price',
    'price_usd' => 'price'
];

$files = [
    'admin/products.php',
    'admin/product_create.php',
    'admin/product_edit.php',
    'admin/manage_tags_prices.php',
    'admin/reports_old_analytics.php',
    'admin/ajax/import_products_csv.php',
    'admin/ajax/save_reviewed_product.php',
    'admin/ajax/process_csv_preview.php'
];

echo "<h1>Reemplazo Masivo de Columnas de Precio</h1>";
echo "<hr>";

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original = $content;
        
        // Reemplazar price_pesos por price
        $content = str_replace("'price_pesos'", "'price'", $content);
        $content = str_replace('"price_pesos"', '"price"', $content);
        $content = str_replace('price_pesos', 'price', $content);
        
        // NO reemplazar price_usd por ahora, solo comentar/eliminar referencias
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "<p style='color:green'>✅ Actualizado: $file</p>";
        } else {
            echo "<p style='color:gray'>⏭️ Sin cambios: $file</p>";
        }
    } else {
        echo "<p style='color:red'>❌ No encontrado: $file</p>";
    }
}

echo "<hr>";
echo "<p><strong>Listo! Recarga el admin.</strong></p>";
?>
