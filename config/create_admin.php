<?php
/**
 * =====================================================
 * CREAR ADMINISTRADOR PRINCIPAL - MULTIGAMER360
 * =====================================================
 * 
 * Descripción: Script para crear el usuario administrador principal
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-20
 */

require_once 'database.php';

echo "<h2>👑 Creando administrador principal...</h2>";

try {
    // Verificar si ya existe un administrador
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE level >= 80");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();

    if ($admin_count > 0) {
        echo "<div class='alert alert-info'>ℹ️ Ya existe al menos un administrador en el sistema</div>";
    } else {
        // Crear el administrador principal
        $admin_data = [
            'username' => 'admin',
            'email' => 'Gbustosgarcia01@gmail.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'first_name' => 'Gabriel',
            'last_name' => 'Bustos',
            'level' => 100,
            'is_active' => 1
        ];

        $sql = "INSERT INTO users (username, email, password, first_name, last_name, level, is_active, created_at) 
                VALUES (:username, :email, :password, :first_name, :last_name, :level, :is_active, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($admin_data);

        echo "<div class='alert alert-success'>✅ Administrador principal creado exitosamente</div>";
        echo "<div class='alert alert-info'>";
        echo "<h6>📋 Credenciales del administrador:</h6>";
        echo "<strong>Email:</strong> " . $admin_data['email'] . "<br>";
        echo "<strong>Contraseña:</strong> admin123<br>";
        echo "<strong>Nivel:</strong> 100 (Super Administrador)<br>";
        echo "<strong>Nombre:</strong> " . $admin_data['first_name'] . " " . $admin_data['last_name'];
        echo "</div>";
    }

    // Verificar estado final
    $stmt = $pdo->prepare("SELECT * FROM users WHERE level >= 80");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='alert alert-success mt-4'>";
    echo "<h5>🎯 Administradores en el sistema:</h5>";
    foreach ($admins as $admin) {
        echo "<div class='d-flex justify-content-between'>";
        echo "<span>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</span>";
        echo "<span class='badge bg-primary'>Nivel " . $admin['level'] . "</span>";
        echo "</div>";
    }
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Administrador - MultiGamer360</title>
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