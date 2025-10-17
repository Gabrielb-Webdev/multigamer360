<?php
/**
 * TEST DE LÓGICA DE FILTROS
 * Verificar el comportamiento correcto de géneros vs. categorías
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Filtros</title></head><body>";
echo "<h1>🧪 TEST DE LÓGICA DE FILTROS BIDIRECCIONALES</h1>";

// Obtener categoría Videojuegos
$stmt = $pdo->query("SELECT id, name FROM categories WHERE slug = 'videojuegos' OR name LIKE '%videojuego%' LIMIT 1");
$videojuegosCategory = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>📦 Categorías</h2>";
$stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
foreach ($categories as $cat) {
    $highlight = ($cat['id'] == $videojuegosCategory['id']) ? ' style="background: lightgreen;"' : '';
    echo "<tr{$highlight}><td>{$cat['id']}</td><td>{$cat['name']}</td><td>{$cat['slug']}</td></tr>";
}
echo "</table>";
echo "<p><strong>Categoría Videojuegos ID:</strong> {$videojuegosCategory['id']}</p>";

// Obtener género _CONSOLA_
echo "<h2>🎮 Género Invisible _CONSOLA_</h2>";
$stmt = $pdo->query("SELECT id, name FROM genres WHERE name = '_CONSOLA_' OR id = 999 LIMIT 1");
$consolaGenre = $stmt->fetch(PDO::FETCH_ASSOC);
if ($consolaGenre) {
    echo "<p>✅ <strong>Género _CONSOLA_ encontrado:</strong> ID {$consolaGenre['id']} - {$consolaGenre['name']}</p>";
} else {
    echo "<p>❌ <strong>Género _CONSOLA_ NO encontrado</strong></p>";
}

// Contar productos por categoría y géneros
echo "<h2>📊 Distribución de Productos</h2>";

// Productos por categoría
$stmt = $pdo->query("
    SELECT c.name as category, COUNT(p.id) as count 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 
    GROUP BY c.id, c.name 
    ORDER BY c.name
");
echo "<h3>Por Categoría:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Categoría</th><th>Productos Activos</th></tr>";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "<tr><td>{$row['category']}</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

// Productos con género _CONSOLA_
if ($consolaGenre) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) as count 
        FROM products p 
        INNER JOIN product_genres pg ON p.id = pg.product_id 
        WHERE p.is_active = 1 AND pg.genre_id = ?
    ");
    $stmt->execute([$consolaGenre['id']]);
    $consolaCount = $stmt->fetchColumn();
    echo "<h3>Con género _CONSOLA_:</h3>";
    echo "<p><strong>{$consolaCount}</strong> productos tienen el género invisible _CONSOLA_</p>";
}

// Productos de videojuegos con géneros visibles
$stmt = $pdo->prepare("
    SELECT g.name as genre, COUNT(DISTINCT p.id) as count 
    FROM products p 
    INNER JOIN product_genres pg ON p.id = pg.product_id 
    INNER JOIN genres g ON pg.genre_id = g.id 
    WHERE p.is_active = 1 
    AND p.category_id = ? 
    AND g.id != 999 
    AND g.name NOT LIKE '\_%' 
    GROUP BY g.id, g.name 
    ORDER BY g.name
");
$stmt->execute([$videojuegosCategory['id']]);
echo "<h3>Videojuegos por Género (visibles):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Género</th><th>Videojuegos</th></tr>";
$genreRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($genreRows) > 0) {
    foreach ($genreRows as $row) {
        echo "<tr><td>{$row['genre']}</td><td>{$row['count']}</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>⚠️ No hay videojuegos con géneros asignados</td></tr>";
}
echo "</table>";

// TEST: Simular filtro de categoría "Consolas"
echo "<h2>🧪 TEST 1: Filtrar categoría 'Consolas'</h2>";
$stmt = $pdo->query("SELECT id FROM categories WHERE name LIKE '%consola%' LIMIT 1");
$consolasCategory = $stmt->fetch(PDO::FETCH_ASSOC);

if ($consolasCategory) {
    echo "<p>ID Categoría Consolas: <strong>{$consolasCategory['id']}</strong></p>";
    
    // Simular que se filtró "Consolas" - todos los géneros deberían dar 0
    $shouldApplyGenres = !($videojuegosCategory['id'] && in_array($videojuegosCategory['id'], [$consolasCategory['id']]));
    
    echo "<p><strong>¿Aplicar géneros?</strong> " . ($shouldApplyGenres ? "✅ SÍ" : "❌ NO (filtro Consolas activo)") . "</p>";
    
    if (!$shouldApplyGenres) {
        echo "<p style='background: lightcoral; padding: 10px;'>✅ CORRECTO: Los géneros NO aplican cuando se filtra categoría 'Consolas'</p>";
    }
}

// TEST: Simular filtro de género "Acción"
echo "<h2>🧪 TEST 2: Filtrar género 'Acción'</h2>";
$stmt = $pdo->query("SELECT id, name FROM genres WHERE name LIKE '%acci%' AND id != 999 LIMIT 1");
$accionGenre = $stmt->fetch(PDO::FETCH_ASSOC);

if ($accionGenre) {
    echo "<p>ID Género Acción: <strong>{$accionGenre['id']} ({$accionGenre['name']})</strong></p>";
    
    // Al filtrar por género, DEBE forzar categoría Videojuegos
    echo "<p><strong>Categoría forzada:</strong> Videojuegos (ID {$videojuegosCategory['id']})</p>";
    
    // Contar productos
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) as count 
        FROM products p 
        INNER JOIN product_genres pg ON p.id = pg.product_id 
        WHERE p.is_active = 1 
        AND p.category_id = ? 
        AND pg.genre_id = ?
    ");
    $stmt->execute([$videojuegosCategory['id'], $accionGenre['id']]);
    $count = $stmt->fetchColumn();
    
    echo "<p style='background: lightgreen; padding: 10px;'>✅ Productos de Acción (solo Videojuegos): <strong>{$count}</strong></p>";
}

echo "<hr>";
echo "<h2>✅ Reglas de Validación</h2>";
echo "<ol>";
echo "<li>❌ Las <strong>CONSOLAS físicas NO tienen géneros</strong> (solo _CONSOLA_ invisible)</li>";
echo "<li>✅ Los <strong>VIDEOJUEGOS SÍ tienen géneros</strong> (Acción, RPG, etc.)</li>";
echo "<li>🔄 Si filtro <strong>categoría 'Consolas'</strong> → Todos los géneros deben dar <strong>0 productos</strong></li>";
echo "<li>🔄 Si filtro <strong>género 'Acción'</strong> → Debe forzar <strong>categoría 'Videojuegos'</strong></li>";
echo "<li>🔄 Filtros deben ser <strong>bidireccionales</strong> (arriba→abajo y abajo→arriba)</li>";
echo "</ol>";

echo "</body></html>";
?>
