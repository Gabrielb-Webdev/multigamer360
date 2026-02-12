<?php
require_once 'database.php';

echo "=== VERIFICANDO SISTEMA DE RESEÑAS ===" . PHP_EOL;

try {
    // Verificar tabla reviews
    echo "1. Verificando tabla reviews..." . PHP_EOL;
    try {
        $result = $pdo->query("DESCRIBE reviews");
        echo "   ✓ Tabla reviews existe" . PHP_EOL;
        echo "   Columnas:" . PHP_EOL;
        while ($row = $result->fetch()) {
            echo "     - " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "   ❌ Tabla reviews no existe, creando..." . PHP_EOL;
        
        $sql = "CREATE TABLE reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            title VARCHAR(255),
            comment TEXT,
            is_approved TINYINT(1) DEFAULT 0,
            admin_response TEXT,
            admin_response_by INT,
            admin_response_at TIMESTAMP NULL,
            helpful_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_response_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_user_product (user_id, product_id)
        )";
        $pdo->exec($sql);
        echo "   ✓ Tabla reviews creada" . PHP_EOL;
    }
    
    // Verificar estadísticas de reseñas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $total_reviews = $stmt->fetch()['total'];
    echo PHP_EOL . "2. Estadísticas de reseñas:" . PHP_EOL;
    echo "   Total de reseñas: " . $total_reviews . PHP_EOL;
    
    if ($total_reviews > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM reviews WHERE is_approved = 0");
        $pending = $stmt->fetch()['pending'];
        echo "   Reseñas pendientes: " . $pending . PHP_EOL;
        
        $stmt = $pdo->query("SELECT COUNT(*) as approved FROM reviews WHERE is_approved = 1");
        $approved = $stmt->fetch()['approved'];
        echo "   Reseñas aprobadas: " . $approved . PHP_EOL;
        
        $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE is_approved = 1");
        $avg_rating = $stmt->fetch()['avg_rating'];
        echo "   Calificación promedio: " . number_format($avg_rating, 2) . "/5.0" . PHP_EOL;
        
        echo PHP_EOL . "   Últimas reseñas:" . PHP_EOL;
        $stmt = $pdo->query("
            SELECT r.rating, r.title, r.is_approved, u.name as user_name, p.name as product_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            JOIN products p ON r.product_id = p.id 
            ORDER BY r.created_at DESC 
            LIMIT 5
        ");
        while ($row = $stmt->fetch()) {
            $status = $row['is_approved'] ? '✅' : '⏳';
            echo "     " . $status . " " . str_repeat('⭐', $row['rating']) . " \"" . $row['title'] . "\" - " . $row['user_name'] . " (" . $row['product_name'] . ")" . PHP_EOL;
        }
    } else {
        echo "   No hay reseñas en el sistema" . PHP_EOL;
        echo "   Creando reseñas de ejemplo..." . PHP_EOL;
        
        // Obtener algunos productos y usuarios para crear reseñas de ejemplo
        $stmt = $pdo->query("SELECT id FROM products LIMIT 3");
        $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->query("SELECT id FROM users WHERE role != 'admin' LIMIT 2");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($products) > 0 && count($users) > 0) {
            $sample_reviews = [
                [$products[0], $users[0], 5, 'Excelente producto', 'Muy buena calidad, llegó en perfectas condiciones. Lo recomiendo 100%.', 1],
                [$products[0], $users[1] ?? $users[0], 4, 'Muy bueno', 'Buen producto, aunque el envío tardó un poco más de lo esperado.', 1],
                [count($products) > 1 ? $products[1] : $products[0], $users[0], 3, 'Regular', 'El producto está bien pero esperaba algo mejor por el precio.', 0]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, title, comment, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($sample_reviews as $review) {
                try {
                    $stmt->execute($review);
                    echo "     ✓ Reseña de ejemplo creada" . PHP_EOL;
                } catch (Exception $e) {
                    // Ignorar duplicados
                }
            }
        }
    }
    
    echo PHP_EOL . "✅ Sistema de reseñas verificado exitosamente!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>