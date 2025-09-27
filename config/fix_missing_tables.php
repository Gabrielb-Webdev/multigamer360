<?php
/**
 * =====================================================
 * SETUP TABLAS FALTANTES - MULTIGAMER360
 * =====================================================
 * 
 * Descripción: Script para crear tablas que faltan en el sistema
 * Autor: MultiGamer360 Development Team
 * Fecha: 2025-09-20
 */

require_once 'database.php';

echo "<h2>🔧 Creando tablas faltantes...</h2>";

try {
    // 1. Crear tabla cart si no existe
    $sql_cart = "
    CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (user_id, product_id),
        INDEX idx_user_id (user_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_cart);
    echo "<div class='alert alert-success'>✅ Tabla 'cart' creada exitosamente</div>";

    // 2. Crear tabla wishlist si no existe
    $sql_wishlist = "
    CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (user_id, product_id),
        INDEX idx_user_id (user_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_wishlist);
    echo "<div class='alert alert-success'>✅ Tabla 'wishlist' creada exitosamente</div>";

    // 3. Verificar si la columna 'level' existe en users
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('level', $columns)) {
        // Agregar columna level si no existe
        $sql_add_level = "ALTER TABLE users ADD COLUMN level INT DEFAULT 10";
        $pdo->exec($sql_add_level);
        echo "<div class='alert alert-success'>✅ Columna 'level' agregada a tabla 'users'</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ Columna 'level' ya existe en tabla 'users'</div>";
    }

    echo "<div class='alert alert-success mt-4'><h4>🎉 Tablas faltantes creadas correctamente!</h4></div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Tablas Faltantes - MultiGamer360</title>
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