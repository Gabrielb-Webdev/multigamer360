<?php
/**
 * DIAGNÓSTICO COMPLETO - PRODUCTOS
 * Este archivo debe subirse a Hostinger para diagnosticar el problema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración
require_once 'config/session_config.php';
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #e74c3c; padding-bottom: 10px; }
        h2 { color: #e74c3c; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #e74c3c; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .code { background: #f4f4f4; padding: 10px; border-left: 3px solid #e74c3c; margin: 10px 0; overflow-x: auto; }
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Completo de Productos</h1>
        
        <?php
        // 1. VERIFICAR CONEXIÓN A BASE DE DATOS
        echo "<h2>1. ✅ Conexión a Base de Datos</h2>";
        echo "<div class='info-box'>";
        echo "<strong>Estado:</strong> <span class='success'>Conectado correctamente</span><br>";
        echo "<strong>Base de datos:</strong> " . $pdo->query("SELECT DATABASE()")->fetchColumn();
        echo "</div>";
        
        // 2. VERIFICAR TABLA PRODUCTS
        echo "<h2>2. 📊 Estructura de Tabla 'products'</h2>";
        try {
            $stmt = $pdo->query("DESCRIBE products");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                $highlight = (stripos($col['Field'], 'active') !== false || 
                             stripos($col['Field'], 'console') !== false || 
                             stripos($col['Field'], 'category') !== false ||
                             stripos($col['Field'], 'brand') !== false) ? 'style="background: #ffffcc;"' : '';
                echo "<tr $highlight>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
        
        // 3. CONTAR PRODUCTOS
        echo "<h2>3. 📦 Conteo de Productos</h2>";
        $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $activos = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
        $inactivos = $total - $activos;
        
        echo "<div class='info-box'>";
        echo "<strong>Total productos:</strong> $total<br>";
        echo "<strong>Productos activos (is_active = 1):</strong> <span class='success'>$activos</span><br>";
        echo "<strong>Productos inactivos:</strong> <span class='warning'>$inactivos</span>";
        echo "</div>";
        
        // 4. VER PRODUCTOS ACTIVOS
        echo "<h2>4. 🎮 Productos Activos (Detalles)</h2>";
        $stmt = $pdo->query("SELECT id, name, category_id, brand_id, console_id, is_active, stock_quantity, price_pesos, created_at 
                             FROM products 
                             WHERE is_active = 1 
                             LIMIT 10");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Cat ID</th><th>Brand ID</th><th>Console ID</th><th>Active</th><th>Stock</th><th>Precio</th></tr>";
            foreach ($products as $p) {
                echo "<tr>";
                echo "<td>{$p['id']}</td>";
                echo "<td>{$p['name']}</td>";
                echo "<td>{$p['category_id']}</td>";
                echo "<td>{$p['brand_id']}</td>";
                echo "<td>" . ($p['console_id'] ?? '<span class="warning">NULL</span>') . "</td>";
                echo "<td>" . ($p['is_active'] ? '✅' : '❌') . "</td>";
                echo "<td>{$p['stock_quantity']}</td>";
                echo "<td>\${$p['price_pesos']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No hay productos activos en la base de datos</p>";
        }
        
        // 5. VERIFICAR CATEGORÍAS
        echo "<h2>5. 📁 Categorías Disponibles</h2>";
        $stmt = $pdo->query("SELECT * FROM categories LIMIT 20");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>{$cat['id']}</td>";
            echo "<td>{$cat['name']}</td>";
            echo "<td>{$cat['slug']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 6. VERIFICAR MARCAS
        echo "<h2>6. 🏷️ Marcas Disponibles</h2>";
        $stmt = $pdo->query("SELECT * FROM brands LIMIT 20");
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
        foreach ($brands as $brand) {
            echo "<tr>";
            echo "<td>{$brand['id']}</td>";
            echo "<td>{$brand['name']}</td>";
            echo "<td>{$brand['slug']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 7. VERIFICAR CONSOLAS (SI EXISTE LA TABLA)
        echo "<h2>7. 🎮 Consolas Disponibles</h2>";
        try {
            $stmt = $pdo->query("SELECT * FROM consoles LIMIT 20");
            $consoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($consoles) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Manufacturer</th></tr>";
                foreach ($consoles as $console) {
                    echo "<tr>";
                    echo "<td>{$console['id']}</td>";
                    echo "<td>{$console['name']}</td>";
                    echo "<td>{$console['slug']}</td>";
                    echo "<td>" . ($console['manufacturer'] ?? '-') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ La tabla 'consoles' existe pero está vacía</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ La tabla 'consoles' no existe o hay un error: " . $e->getMessage() . "</p>";
        }
        
        // 8. TEST DE QUERY PRINCIPAL
        echo "<h2>8. 🔍 Test de Query Principal (Como en productos.php)</h2>";
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
                WHERE p.is_active = 1
                ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC
                LIMIT 20";
        
        echo "<div class='code'>" . htmlspecialchars($sql) . "</div>";
        
        try {
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='info-box'>";
            echo "<strong>Resultados obtenidos:</strong> <span class='success'>" . count($results) . " productos</span>";
            echo "</div>";
            
            if (count($results) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Marca</th><th>Consola</th><th>Stock</th><th>Precio</th></tr>";
                foreach ($results as $r) {
                    echo "<tr>";
                    echo "<td>{$r['id']}</td>";
                    echo "<td>{$r['name']}</td>";
                    echo "<td>" . ($r['category_name'] ?? '<span class="warning">NULL</span>') . "</td>";
                    echo "<td>" . ($r['brand_name'] ?? '<span class="warning">NULL</span>') . "</td>";
                    echo "<td>" . ($r['console_name'] ?? '<span class="warning">NULL</span>') . "</td>";
                    echo "<td>{$r['stock_quantity']}</td>";
                    echo "<td>\${$r['price_pesos']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ El query no devolvió productos</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Error en query: " . $e->getMessage() . "</p>";
        }
        
        // 9. VERIFICAR PRODUCT_MANAGER
        echo "<h2>9. 🛠️ Test de ProductManager Class</h2>";
        try {
            require_once 'includes/product_manager.php';
            $productManager = new ProductManager($pdo);
            
            $filters = ['limit' => 20, 'offset' => 0];
            $testProducts = $productManager->getProducts($filters);
            $testCount = $productManager->countProducts($filters);
            
            echo "<div class='info-box'>";
            echo "<strong>ProductManager->getProducts():</strong> <span class='success'>" . count($testProducts) . " productos</span><br>";
            echo "<strong>ProductManager->countProducts():</strong> <span class='success'>$testCount productos</span>";
            echo "</div>";
            
            if (count($testProducts) == 0 && $activos > 0) {
                echo "<p class='error'>⚠️ PROBLEMA DETECTADO: Hay productos activos en DB pero ProductManager no los retorna</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error al usar ProductManager: " . $e->getMessage() . "</p>";
        }
        
        // 10. RECOMENDACIONES
        echo "<h2>10. 💡 Recomendaciones</h2>";
        echo "<div class='info-box'>";
        if (count($products) == 0) {
            echo "❌ <strong>No hay productos activos.</strong> Debes agregar productos desde el panel de administración.<br>";
        } else {
            echo "✅ Hay productos activos en la base de datos.<br>";
        }
        
        echo "<br><strong>Pasos siguientes:</strong><br>";
        echo "1. Verificar que los productos tengan category_id y brand_id válidos<br>";
        echo "2. Si usas consolas, verificar que console_id esté correcto o la tabla 'consoles' exista<br>";
        echo "3. Verificar que el archivo product_manager.php se esté usando correctamente<br>";
        echo "4. Revisar los logs de errores de PHP";
        echo "</div>";
        
        ?>
        
        <br><br>
        <p style="text-align: center; color: #888; margin-top: 50px;">
            Diagnóstico generado el <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>
