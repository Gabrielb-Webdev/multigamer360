<?php
/**
 * ARCHIVO DE DIAGNÓSTICO - COUPONS
 * Este archivo ayuda a identificar el problema exacto
 */

// Mostrar errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Coupons</title></head><body>";
echo "<h1>Diagnóstico de Coupons.php</h1>";

// Paso 1: Verificar que el archivo se carga
echo "<p>✅ Paso 1: Archivo de diagnóstico cargado correctamente</p>";

// Paso 2: Verificar ruta de auth.php
$auth_path = __DIR__ . '/inc/auth.php';
echo "<p>📁 Ruta de auth.php: " . $auth_path . "</p>";
echo "<p>" . (file_exists($auth_path) ? "✅ auth.php existe" : "❌ auth.php NO EXISTE") . "</p>";

if (!file_exists($auth_path)) {
    echo "<p style='color: red;'>❌ ERROR: No se encuentra el archivo inc/auth.php</p>";
    echo "<p>Archivos en directorio actual:</p><ul>";
    foreach (scandir(__DIR__) as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    exit;
}

// Paso 3: Intentar incluir auth.php
echo "<p>⏳ Paso 3: Intentando incluir auth.php...</p>";
try {
    require_once 'inc/auth.php';
    echo "<p>✅ auth.php incluido correctamente</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR al incluir auth.php: " . $e->getMessage() . "</p>";
    exit;
}

// Paso 4: Verificar conexión a base de datos
echo "<p>⏳ Paso 4: Verificando conexión a base de datos...</p>";
if (!isset($pdo)) {
    echo "<p style='color: red;'>❌ ERROR: Variable \$pdo no está definida</p>";
    exit;
}

try {
    $pdo->query("SELECT 1");
    echo "<p>✅ Conexión a base de datos OK</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ ERROR de base de datos: " . $e->getMessage() . "</p>";
    exit;
}

// Paso 5: Verificar tabla coupons
echo "<p>⏳ Paso 5: Verificando tabla coupons...</p>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'coupons'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabla 'coupons' existe</p>";
        
        // Mostrar estructura de la tabla
        $columns = $pdo->query("DESCRIBE coupons")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>📋 Columnas de la tabla coupons:</p><ul>";
        foreach ($columns as $col) {
            echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
        }
        echo "</ul>";
        
        // Contar registros
        $count = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
        echo "<p>📊 Total de cupones en la tabla: <strong>$count</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ ERROR: Tabla 'coupons' NO EXISTE</p>";
        echo "<p>Tablas disponibles:</p><ul>";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ ERROR al verificar tabla: " . $e->getMessage() . "</p>";
}

// Paso 6: Probar consulta de cupones
echo "<p>⏳ Paso 6: Probando consulta de cupones...</p>";
try {
    $stmt = $pdo->query("
        SELECT *, 
        CASE 
            WHEN end_date IS NOT NULL AND end_date < NOW() THEN 'expired'
            WHEN start_date > NOW() THEN 'scheduled'
            WHEN is_active = 1 THEN 'active'
            ELSE 'inactive'
        END as status_text
        FROM coupons 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Consulta ejecutada correctamente</p>";
    echo "<p>📊 Cupones obtenidos: <strong>" . count($coupons) . "</strong></p>";
    
    if (count($coupons) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Estado</th></tr>";
        foreach ($coupons as $coupon) {
            echo "<tr>";
            echo "<td>{$coupon['id']}</td>";
            echo "<td>{$coupon['code']}</td>";
            echo "<td>{$coupon['name']}</td>";
            echo "<td>{$coupon['type']}</td>";
            echo "<td>{$coupon['value']}</td>";
            echo "<td>{$coupon['status_text']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ ERROR en consulta: " . $e->getMessage() . "</p>";
}

// Paso 7: Verificar archivos de template
echo "<h2>Verificación de Templates</h2>";
$templates = [
    'inc/header.php' => 'Header',
    'inc/sidebar.php' => 'Sidebar',
    'inc/footer.php' => 'Footer'
];

foreach ($templates as $file => $name) {
    $exists = file_exists($file);
    echo "<p>" . ($exists ? "✅" : "❌") . " $name ($file): " . ($exists ? "OK" : "NO ENCONTRADO") . "</p>";
}

// Información de sesión
echo "<h2>Información de Sesión</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ Usuario logueado: ID = {$_SESSION['user_id']}</p>";
    echo "<p>✅ Es admin: " . (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 'Sí' : 'No') . "</p>";
} else {
    echo "<p>❌ No hay sesión activa</p>";
}

echo "<hr>";
echo "<h2>✅ DIAGNÓSTICO COMPLETADO</h2>";
echo "<p>Si todos los pasos anteriores muestran ✅, el problema puede estar en los archivos de template (header, sidebar, footer).</p>";
echo "<p><a href='coupons.php'>Intentar cargar coupons.php normal</a></p>";
echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo después del diagnóstico por seguridad.</p>";
echo "</body></html>";
?>
