<?php
/**
 * SCRIPT DE ACTUALIZACIÓN DE BASE DE DATOS
 * Agregar columnas de descuento por moneda (ARS y USD)
 * 
 * INSTRUCCIONES:
 * 1. Ejecutar este archivo una sola vez desde el navegador: /admin/add_discount_columns.php
 * 2. El script agregará las columnas necesarias a la tabla products
 * 3. Después de ejecutarlo, puedes eliminarlo o mantenerlo como respaldo
 */

require_once '../config/config.php';

// Verificar que solo admins puedan ejecutar esto
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ <h1>Acceso denegado</h1><p>Solo administradores pueden ejecutar este script.</p>');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Base de Datos - Descuentos por Moneda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .container {
            max-width: 800px;
        }
        .card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: none;
            border-radius: 15px;
        }
        .btn-execute {
            font-size: 18px;
            padding: 15px 40px;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
    </style>
    
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-database fa-4x text-primary mb-3"></i>
                    <h2 class="mb-3">🔧 Actualización de Base de Datos</h2>
                    <p class="text-muted">Agregar columnas de descuento por moneda (ARS y USD)</p>
                </div>

                <div id="result-area">
                    <?php
                    if (isset($_POST['execute'])) {
                        try {
                            echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Ejecutando cambios en la base de datos...</div>';

                            // Comenzar transacción
                            $pdo->beginTransaction();

                            $changes = [];
                            $errors = [];

                            // 1. Verificar si discount_percentage_ars existe
                            $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'discount_percentage_ars'");
                            if ($check->rowCount() == 0) {
                                $pdo->exec("ALTER TABLE products ADD COLUMN discount_percentage_ars DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Descuento en pesos argentinos (ARS)'");
                                $changes[] = "✅ Columna 'discount_percentage_ars' agregada exitosamente";
                            } else {
                                $changes[] = "ℹ️ Columna 'discount_percentage_ars' ya existe";
                            }

                            // 2. Verificar si discount_percentage_usd existe
                            $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'discount_percentage_usd'");
                            if ($check->rowCount() == 0) {
                                $pdo->exec("ALTER TABLE products ADD COLUMN discount_percentage_usd DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Descuento en dólares (USD)'");
                                $changes[] = "✅ Columna 'discount_percentage_usd' agregada exitosamente";
                            } else {
                                $changes[] = "ℹ️ Columna 'discount_percentage_usd' ya existe";
                            }

                            // 3. Migrar datos existentes de discount_percentage a discount_percentage_ars
                            $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'discount_percentage'");
                            if ($check->rowCount() > 0) {
                                $pdo->exec("UPDATE products SET discount_percentage_ars = discount_percentage WHERE discount_percentage > 0");
                                $changes[] = "✅ Datos de 'discount_percentage' migrados a 'discount_percentage_ars'";
                            }

                            // Confirmar cambios
                            $pdo->commit();

                            echo '<div class="alert alert-success mt-3">';
                            echo '<h4><i class="fas fa-check-circle me-2"></i>¡Actualización completada exitosamente!</h4>';
                            echo '<hr>';
                            echo '<h5>Cambios realizados:</h5><ul class="mb-0">';
                            foreach ($changes as $change) {
                                echo "<li>$change</li>";
                            }
                            echo '</ul></div>';

                            echo '<div class="alert alert-info mt-3">';
                            echo '<h5><i class="fas fa-info-circle me-2"></i>Estructura actualizada:</h5>';
                            echo '<pre class="mb-0">';
                            echo "products\n";
                            echo "├── discount_percentage_ars (ARS) [NUEVA]\n";
                            echo "├── discount_percentage_usd (USD) [NUEVA]\n";
                            echo "└── is_on_sale (booleano)";
                            echo '</pre>';
                            echo '</div>';

                            echo '<div class="text-center mt-4">';
                            echo '<a href="product_create.php" class="btn btn-primary btn-lg">';
                            echo '<i class="fas fa-plus me-2"></i>Crear Producto';
                            echo '</a>';
                            echo '</div>';

                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            echo '<div class="alert alert-danger">';
                            echo '<h4><i class="fas fa-exclamation-triangle me-2"></i>Error al actualizar la base de datos</h4>';
                            echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        // Mostrar información y botón de ejecución
                        ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>¡Atención!</h5>
                            <p class="mb-0">Este script modificará la estructura de la base de datos. Se recomienda hacer un respaldo antes de continuar.</p>
                        </div>

                        <h5 class="mb-3"><i class="fas fa-list-check me-2"></i>Cambios que se realizarán:</h5>
                        <ul>
                            <li>✨ Agregar columna <code>discount_percentage_ars</code> (Descuento en Pesos Argentinos)</li>
                            <li>✨ Agregar columna <code>discount_percentage_usd</code> (Descuento en Dólares USD)</li>
                            <li>🔄 Migrar datos existentes de <code>discount_percentage</code> a <code>discount_percentage_ars</code></li>
                            <li>📊 Tipo de dato: DECIMAL(5,2) - permite valores de 0.00 a 999.99</li>
                        </ul>

                        <h5 class="mb-3 mt-4"><i class="fas fa-code me-2"></i>SQL que se ejecutará:</h5>
                        <pre><code>ALTER TABLE products 
ADD COLUMN discount_percentage_ars DECIMAL(5,2) DEFAULT 0.00 
COMMENT 'Descuento en pesos argentinos (ARS)';

ALTER TABLE products 
ADD COLUMN discount_percentage_usd DECIMAL(5,2) DEFAULT 0.00 
COMMENT 'Descuento en dólares (USD)';

UPDATE products 
SET discount_percentage_ars = discount_percentage 
WHERE discount_percentage > 0;</code></pre>

                        <form method="POST" class="text-center mt-4">
                            <button type="submit" name="execute" class="btn btn-primary btn-execute">
                                <i class="fas fa-play me-2"></i>Ejecutar Actualización
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="products.php" class="btn btn-link text-muted">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar y volver
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-white">
                <i class="fas fa-shield-alt me-1"></i>
                Solo accesible para administradores
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
