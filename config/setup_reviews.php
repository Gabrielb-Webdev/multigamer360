<?php
require_once 'database.php';

try {
    // Crear tabla de reseñas
    $reviews_table_sql = "
    CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        order_id INT DEFAULT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        title VARCHAR(255) NOT NULL,
        comment TEXT NOT NULL,
        is_verified_purchase BOOLEAN DEFAULT FALSE,
        is_approved BOOLEAN DEFAULT FALSE,
        admin_response TEXT DEFAULT NULL,
        admin_response_by INT DEFAULT NULL,
        admin_response_at TIMESTAMP NULL,
        helpful_count INT DEFAULT 0,
        reported_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_response_by) REFERENCES users(id) ON DELETE SET NULL,
        
        UNIQUE KEY unique_user_product (user_id, product_id),
        INDEX idx_product (product_id),
        INDEX idx_user (user_id),
        INDEX idx_rating (rating),
        INDEX idx_approved (is_approved),
        INDEX idx_created (created_at)
    )";
    
    $pdo->exec($reviews_table_sql);
    echo "✅ Tabla 'reviews' creada exitosamente\n";
    
    // Crear tabla de votos útiles en reseñas
    $review_votes_table_sql = "
    CREATE TABLE IF NOT EXISTS review_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        user_id INT NOT NULL,
        vote_type ENUM('helpful', 'not_helpful') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        
        UNIQUE KEY unique_user_review_vote (user_id, review_id),
        INDEX idx_review (review_id),
        INDEX idx_user (user_id)
    )";
    
    $pdo->exec($review_votes_table_sql);
    echo "✅ Tabla 'review_votes' creada exitosamente\n";
    
    // Crear tabla de reportes de reseñas
    $review_reports_table_sql = "
    CREATE TABLE IF NOT EXISTS review_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        reported_by INT NOT NULL,
        reason ENUM('spam', 'inappropriate', 'fake', 'offensive', 'other') NOT NULL,
        comment TEXT,
        status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        reviewed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
        
        INDEX idx_review (review_id),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    )";
    
    $pdo->exec($review_reports_table_sql);
    echo "✅ Tabla 'review_reports' creada exitosamente\n";
    
    // Verificar si ya hay reseñas
    $check_stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
    $count = $check_stmt->fetchColumn();
    
    if ($count == 0) {
        // Insertar reseñas de ejemplo
        $sample_reviews = [
            [
                'product_id' => 1,
                'user_id' => 1,
                'rating' => 5,
                'title' => 'Excelente juego retro',
                'comment' => 'Kingdom Hearts 2 es una obra maestra. La historia es épica y el sistema de combate es muy divertido. Los gráficos y la música son increíbles.',
                'is_verified_purchase' => 1,
                'is_approved' => 1
            ],
            [
                'product_id' => 1,
                'user_id' => 2,
                'rating' => 4,
                'title' => 'Muy buen estado',
                'comment' => 'El juego llegó en excelente estado, funciona perfectamente. Solo le doy 4 estrellas porque el precio está un poco alto, pero vale la pena por la nostalgia.',
                'is_verified_purchase' => 1,
                'is_approved' => 1
            ],
            [
                'product_id' => 4,
                'user_id' => 1,
                'rating' => 5,
                'title' => 'Clásico imperdible',
                'comment' => 'Super Mario 64 es simplemente perfecto. Un clásico que nunca pasa de moda. Los controles son intuitivos y el juego tiene una rejugabilidad increíble.',
                'is_verified_purchase' => 0,
                'is_approved' => 1
            ],
            [
                'product_id' => 3,
                'user_id' => 2,
                'rating' => 5,
                'title' => 'Final Fantasy VII - Legendario',
                'comment' => 'Uno de los mejores RPGs de todos los tiempos. La historia de Cloud y sus compañeros es inolvidable. Gráficos impresionantes para su época.',
                'is_verified_purchase' => 1,
                'is_approved' => 1
            ]
        ];
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, rating, title, comment, is_verified_purchase, is_approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sample_reviews as $review) {
            $insert_stmt->execute([
                $review['product_id'],
                $review['user_id'],
                $review['rating'],
                $review['title'],
                $review['comment'],
                $review['is_verified_purchase'],
                $review['is_approved']
            ]);
        }
        
        echo "✅ Reseñas de ejemplo insertadas exitosamente\n";
    } else {
        echo "ℹ️ Las reseñas ya existen en la base de datos\n";
    }
    
    // Agregar columnas a la tabla products para estadísticas de reseñas
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00");
        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0");
        echo "✅ Columnas de rating agregadas a la tabla products\n";
        
        // Actualizar estadísticas de productos existentes
        $update_stats_sql = "
            UPDATE products p SET 
                average_rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM reviews r 
                    WHERE r.product_id = p.id AND r.is_approved = 1
                ),
                review_count = (
                    SELECT COUNT(*) 
                    FROM reviews r 
                    WHERE r.product_id = p.id AND r.is_approved = 1
                )
        ";
        $pdo->exec($update_stats_sql);
        echo "✅ Estadísticas de productos actualizadas\n";
        
    } catch (Exception $e) {
        echo "ℹ️ Las columnas de rating ya existen en products\n";
    }
    
    echo "\n🎉 Sistema de reseñas configurado correctamente!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>