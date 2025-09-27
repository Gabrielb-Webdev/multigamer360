<?php
require_once 'database.php';

try {
    echo "Insertando categorías de ejemplo...\n";
    
    // Limpiar categorías existentes para evitar duplicados
    $pdo->exec("DELETE FROM categories");
    $pdo->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    
    // Insertar categorías principales
    $main_categories = [
        ['name' => 'Consolas', 'slug' => 'consolas', 'description' => 'Consolas de videojuegos de todas las marcas', 'icon_class' => 'fas fa-gamepad', 'sort_order' => 1],
        ['name' => 'PC Gaming', 'slug' => 'pc-gaming', 'description' => 'Componentes y accesorios para PC Gaming', 'icon_class' => 'fas fa-desktop', 'sort_order' => 2],
        ['name' => 'Videojuegos', 'slug' => 'videojuegos', 'description' => 'Videojuegos para todas las plataformas', 'icon_class' => 'fas fa-compact-disc', 'sort_order' => 3],
        ['name' => 'Accesorios', 'slug' => 'accesorios', 'description' => 'Accesorios y periféricos gaming', 'icon_class' => 'fas fa-headset', 'sort_order' => 4],
        ['name' => 'Retro Gaming', 'slug' => 'retro-gaming', 'description' => 'Consolas y juegos retro clásicos', 'icon_class' => 'fas fa-history', 'sort_order' => 5]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO categories (name, slug, description, icon_class, is_active, is_featured, sort_order, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 1, ?, ?, NOW(), NOW())
    ");
    
    foreach ($main_categories as $index => $category) {
        $is_featured = ($index < 3) ? 1 : 0; // Las primeras 3 son destacadas
        $stmt->execute([
            $category['name'],
            $category['slug'],
            $category['description'],
            $category['icon_class'],
            $is_featured,
            $category['sort_order']
        ]);
        echo "✓ Categoría principal: " . $category['name'] . "\n";
    }
    
    // Obtener IDs de categorías principales
    $categories_ids = [];
    $result = $pdo->query("SELECT id, slug FROM categories WHERE parent_id IS NULL");
    while ($row = $result->fetch()) {
        $categories_ids[$row['slug']] = $row['id'];
    }
    
    // Insertar subcategorías
    $subcategories = [
        // Consolas
        ['name' => 'PlayStation', 'slug' => 'playstation', 'description' => 'Consolas PlayStation', 'parent' => 'consolas', 'icon_class' => 'fab fa-playstation'],
        ['name' => 'Xbox', 'slug' => 'xbox', 'description' => 'Consolas Xbox', 'parent' => 'consolas', 'icon_class' => 'fab fa-xbox'],
        ['name' => 'Nintendo', 'slug' => 'nintendo', 'description' => 'Consolas Nintendo', 'parent' => 'consolas', 'icon_class' => 'fas fa-gamepad'],
        
        // PC Gaming
        ['name' => 'Tarjetas Gráficas', 'slug' => 'tarjetas-graficas', 'description' => 'GPUs para gaming', 'parent' => 'pc-gaming', 'icon_class' => 'fas fa-microchip'],
        ['name' => 'Procesadores', 'slug' => 'procesadores', 'description' => 'CPUs para gaming', 'parent' => 'pc-gaming', 'icon_class' => 'fas fa-microchip'],
        ['name' => 'Memoria RAM', 'slug' => 'memoria-ram', 'description' => 'Memoria RAM para gaming', 'parent' => 'pc-gaming', 'icon_class' => 'fas fa-memory'],
        ['name' => 'Almacenamiento', 'slug' => 'almacenamiento', 'description' => 'SSDs y HDDs', 'parent' => 'pc-gaming', 'icon_class' => 'fas fa-hdd'],
        
        // Videojuegos
        ['name' => 'PS5', 'slug' => 'juegos-ps5', 'description' => 'Juegos para PlayStation 5', 'parent' => 'videojuegos', 'icon_class' => 'fab fa-playstation'],
        ['name' => 'Xbox Series', 'slug' => 'juegos-xbox-series', 'description' => 'Juegos para Xbox Series X/S', 'parent' => 'videojuegos', 'icon_class' => 'fab fa-xbox'],
        ['name' => 'Nintendo Switch', 'slug' => 'juegos-nintendo-switch', 'description' => 'Juegos para Nintendo Switch', 'parent' => 'videojuegos', 'icon_class' => 'fas fa-gamepad'],
        ['name' => 'PC', 'slug' => 'juegos-pc', 'description' => 'Juegos para PC', 'parent' => 'videojuegos', 'icon_class' => 'fas fa-desktop'],
        
        // Accesorios
        ['name' => 'Controles', 'slug' => 'controles', 'description' => 'Controles y mandos', 'parent' => 'accesorios', 'icon_class' => 'fas fa-gamepad'],
        ['name' => 'Auriculares', 'slug' => 'auriculares', 'description' => 'Auriculares gaming', 'parent' => 'accesorios', 'icon_class' => 'fas fa-headphones'],
        ['name' => 'Teclados', 'slug' => 'teclados', 'description' => 'Teclados mecánicos gaming', 'parent' => 'accesorios', 'icon_class' => 'fas fa-keyboard'],
        ['name' => 'Ratones', 'slug' => 'ratones', 'description' => 'Ratones gaming', 'parent' => 'accesorios', 'icon_class' => 'fas fa-mouse'],
        ['name' => 'Sillas Gaming', 'slug' => 'sillas-gaming', 'description' => 'Sillas gaming', 'parent' => 'accesorios', 'icon_class' => 'fas fa-chair']
    ];
    
    $sub_stmt = $pdo->prepare("
        INSERT INTO categories (name, slug, description, parent_id, icon_class, is_active, is_featured, sort_order, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 1, 0, ?, NOW(), NOW())
    ");
    
    foreach ($subcategories as $index => $subcategory) {
        $parent_id = $categories_ids[$subcategory['parent']] ?? null;
        if ($parent_id) {
            $sub_stmt->execute([
                $subcategory['name'],
                $subcategory['slug'],
                $subcategory['description'],
                $parent_id,
                $subcategory['icon_class'],
                $index + 1
            ]);
            echo "✓ Subcategoría: " . $subcategory['name'] . " (padre: " . $subcategory['parent'] . ")\n";
        }
    }
    
    echo "\n✅ Categorías de ejemplo insertadas correctamente\n";
    
    // Mostrar resumen
    $total = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $main_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NULL")->fetchColumn();
    $sub_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL")->fetchColumn();
    
    echo "\n📊 Resumen:\n";
    echo "- Total de categorías: $total\n";
    echo "- Categorías principales: $main_count\n";
    echo "- Subcategorías: $sub_count\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>