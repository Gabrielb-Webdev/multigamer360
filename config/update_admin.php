<?php
/**
 * =====================================================
 * ACTUALIZAR ADMINISTRADOR - MULTIGAMER360
 * =====================================================
 * 
 * Descripción: Script para actualizar el nivel del administrador
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-20
 */

require_once 'database.php';

echo "<h2>👑 Actualizando administrador principal...</h2>";

try {
    // Actualizar el usuario Gbustosgarcia01@gmail.com a administrador nivel 100
    $sql = "UPDATE users SET level = 100, role = 'admin' WHERE email = 'Gbustosgarcia01@gmail.com'";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();

    if ($result && $stmt->rowCount() > 0) {
        echo "<div class='alert alert-success'>✅ Usuario actualizado a administrador nivel 100</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ No se encontró el usuario o ya tenía el nivel correcto</div>";
    }

    // Verificar el resultado
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'Gbustosgarcia01@gmail.com'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        echo "<div class='alert alert-info'>";
        echo "<h6>📋 Datos del administrador actualizado:</h6>";
        echo "<strong>Email:</strong> " . htmlspecialchars($admin['email']) . "<br>";
        echo "<strong>Nombre:</strong> " . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "<br>";
        echo "<strong>Rol:</strong> " . htmlspecialchars($admin['role']) . "<br>";
        echo "<strong>Nivel:</strong> " . $admin['level'] . "<br>";
        echo "<strong>Estado:</strong> " . ($admin['is_active'] ? 'Activo' : 'Inactivo');
        echo "</div>";
    }

    // Verificar cuántos administradores hay ahora
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE level >= 80");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();

    echo "<div class='alert alert-success mt-4'>";
    echo "<h5>🎯 Estado final:</h5>";
    echo "Total de administradores en el sistema: <strong>$admin_count</strong>";
    echo "</div>";

    if ($admin_count > 0) {
        echo "<div class='alert alert-success'>";
        echo "<h6>🔑 Credenciales de acceso:</h6>";
        echo "<strong>Email:</strong> Gbustosgarcia01@gmail.com<br>";
        echo "<strong>Contraseña:</strong> admin123<br>";
        echo "<strong>Nivel:</strong> 100 (Super Administrador)";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Administrador - MultiGamer360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <!-- El contenido PHP se muestra arriba -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>