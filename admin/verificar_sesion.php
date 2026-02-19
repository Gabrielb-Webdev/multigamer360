<?php
/**
 * DEBUG: Verificar sesión y permisos del usuario
 * Ejecutar este archivo para ver los datos de sesión actuales
 */

session_start();

echo "<h2>🔐 Información de Sesión y Permisos</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .card { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .card h3 { margin-top: 0; color: #667eea; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #667eea; color: white; }
    .ok { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #28a745; color: white; }
    .badge-danger { background: #dc3545; color: white; }
    .badge-warning { background: #ffc107; color: black; }
    code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
</style>";

if (!isset($_SESSION['user_id'])) {
    echo "<div class='card'>";
    echo "<p class='error'>❌ No hay sesión activa. Por favor, inicia sesión en el panel de administración.</p>";
    echo "<p><a href='login.php' style='color: #007bff;'>→ Ir a Login</a></p>";
    echo "</div>";
    exit;
}

echo "<div class='card'>";
echo "<h3>👤 Usuario Actual</h3>";
echo "<table>";
echo "<tr><th>Campo</th><th>Valor</th></tr>";
echo "<tr><td><strong>User ID</strong></td><td><code>" . $_SESSION['user_id'] . "</code></td></tr>";
echo "<tr><td><strong>Email</strong></td><td><code>" . ($_SESSION['email'] ?? 'N/A') . "</code></td></tr>";
echo "<tr><td><strong>Rol</strong></td><td>";

$role = $_SESSION['role'] ?? 'N/A';
if ($role === 'admin' || $role === 'administrador') {
    echo "<span class='badge badge-success'>{$role}</span>";
} else {
    echo "<span class='badge badge-warning'>{$role}</span>";
}

echo "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='card'>";
echo "<h3>🔑 Verificación de Permisos</h3>";
echo "<table>";
echo "<tr><th>Permiso</th><th>Estado</th></tr>";

// Verificar si es admin
$is_admin = ($role === 'admin' || $role === 'administrador');
echo "<tr>";
echo "<td><strong>Es Administrador</strong></td>";
echo "<td>" . ($is_admin ? "<span class='ok'>✅ SÍ</span>" : "<span class='error'>❌ NO</span>") . "</td>";
echo "</tr>";

// Verificar permisos de productos
$has_product_permissions = isset($_SESSION['permissions']['products']);
echo "<tr>";
echo "<td><strong>Tiene permisos de productos</strong></td>";
echo "<td>" . ($has_product_permissions ? "<span class='ok'>✅ SÍ</span>" : "<span class='error'>❌ NO</span>") . "</td>";
echo "</tr>";

// Si tiene permisos, mostrarlos
if ($has_product_permissions) {
    echo "<tr>";
    echo "<td><strong>Acciones permitidas</strong></td>";
    echo "<td><code>" . implode(', ', $_SESSION['permissions']['products']) . "</code></td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='card'>";
echo "<h3>📋 Datos Completos de Sesión</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

echo "<div class='card'>";
echo "<h3>✅ Resultado del Diagnóstico</h3>";

if ($is_admin) {
    echo "<p class='ok' style='font-size: 18px;'>✅ Tu usuario tiene ROL DE ADMINISTRADOR</p>";
    echo "<p>Deberías poder cambiar estados de productos sin problemas.</p>";
    echo "<p><strong>Tu rol es:</strong> <code>{$role}</code></p>";
    echo "<p>Los roles válidos de admin son: <code>admin</code> o <code>administrador</code></p>";
} else {
    echo "<p class='error' style='font-size: 18px;'>⚠️ Tu usuario NO es administrador</p>";
    echo "<p><strong>Tu rol actual:</strong> <code>{$role}</code></p>";
    echo "<p>Para tener acceso completo, tu rol debe ser <code>admin</code> o <code>administrador</code></p>";
    
    if ($has_product_permissions) {
        echo "<p class='ok'>✅ Pero tienes permisos específicos de productos</p>";
    } else {
        echo "<p class='error'>❌ Y tampoco tienes permisos específicos de productos</p>";
    }
}

echo "</div>";

echo "<hr>";
echo "<p><a href='products.php' style='color: #007bff;'>← Volver a Productos</a></p>";

?>
