<?php
require_once 'config/database.php';

class ProductManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Obtener productos destacados
    public function getFeaturedProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       pi.image_url as primary_image,
                       p.console, p.publication_date, p.price_usd, p.price_pesos, p.release_date,
                       p.main_image, p.long_description, p.rating, p.developer,
                       p.publisher, p.genre, p.condition_product, p.tags
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_featured = 1 AND p.is_active = 1 
                ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener productos nuevos (novedades)
    public function getNewProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       pi.image_url as primary_image,
                       p.console, p.publication_date, p.price_usd, p.price_pesos, p.release_date,
                       p.main_image, p.long_description, p.rating, p.developer,
                       p.publisher, p.genre, p.condition_product, p.tags
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_new = 1 AND p.is_active = 1 
                ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener todos los productos con filtros
    public function getProducts($filters = []) {
        $sql = "SELECT p.*, 
                       c.name as category_name, 
                       b.name as brand_name,
                       co.name as console_name, 
                       co.slug as console_slug,
                       pi.image_url as primary_image,
                       p.created_at as publication_date
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN consoles co ON p.console_id = co.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_active = 1";
        
        $params = [];
        
        // Filtro por categoría (slug único)
        if (!empty($filters['category'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }
        
        // Filtro por múltiples categorías (IDs)
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = str_repeat('?,', count($filters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $filters['categories']);
        }
        
        // Filtro por marca (slug único)
        if (!empty($filters['brand'])) {
            $sql .= " AND b.slug = ?";
            $params[] = $filters['brand'];
        }
        
        // Filtro por múltiples marcas (IDs)
        if (!empty($filters['brands']) && is_array($filters['brands'])) {
            $placeholders = str_repeat('?,', count($filters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $filters['brands']);
        }
        
        // Filtro por múltiples consolas (IDs de tabla consoles)
        if (!empty($filters['consoles']) && is_array($filters['consoles'])) {
            $placeholders = str_repeat('?,', count($filters['consoles']) - 1) . '?';
            $sql .= " AND p.console_id IN ($placeholders)";
            $params = array_merge($params, $filters['consoles']);
        }
        
        // Filtro por múltiples géneros (IDs de tabla genres con relación N:N)
        if (!empty($filters['genres']) && is_array($filters['genres'])) {
            $placeholders = str_repeat('?,', count($filters['genres']) - 1) . '?';
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_genres pg 
                        WHERE pg.product_id = p.id 
                        AND pg.genre_id IN ($placeholders)
                    )";
            $params = array_merge($params, $filters['genres']);
        }
        
        // Búsqueda general
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        // Búsqueda por etiquetas internas (solo para administradores)
        if (!empty($filters['tags']) && isset($filters['admin_search']) && $filters['admin_search']) {
            $sql .= " AND JSON_CONTAINS(p.tags, JSON_ARRAY(?))";
            $params[] = $filters['tags'];
        }
        
        // Filtros de precio
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price_pesos >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price_pesos <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Orden personalizable - SIEMPRE productos con stock primero
        if (!empty($filters['order_by'])) {
            $sql .= " ORDER BY (p.stock_quantity > 0) DESC, " . $filters['order_by'];
        } else {
            $sql .= " ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC";
        }
        
        // Paginación - Agregar directamente al SQL con valores sanitizados
        // No usar placeholders (?) para LIMIT/OFFSET porque PDO los convierte a strings
        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Obtener producto por ID
    public function getProductById($id) {
        $sql = "SELECT p.*, 
                       c.name as category_name, 
                       b.name as brand_name,
                       co.name as console_name,
                       co.slug as console_slug,
                       pi.image_url as primary_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN consoles co ON p.console_id = co.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.id = :id AND p.is_active = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener todas las imágenes de un producto
    public function getProductImages($product_id) {
        $sql = "SELECT * FROM product_images 
                WHERE product_id = :product_id 
                ORDER BY is_primary DESC, display_order ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener producto por slug
    public function getProductBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.slug = :slug AND p.is_active = TRUE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Obtener productos relacionados
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.category_id = :category_id 
                AND p.id != :product_id 
                AND p.is_active = TRUE 
                ORDER BY RAND() 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Buscar productos
    public function searchProducts($query, $limit = 20) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE (p.name LIKE :query OR p.description LIKE :query OR p.short_description LIKE :query)
                AND p.is_active = TRUE 
                ORDER BY p.name ASC 
                LIMIT :limit";
        
        $searchTerm = '%' . $query . '%';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener categorías
    public function getCategories() {
        $sql = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    // Obtener marcas
    public function getBrands() {
        $sql = "SELECT * FROM brands WHERE is_active = TRUE ORDER BY name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    // Obtener reseñas de un producto
    public function getProductReviews($productId) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM product_reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = :product_id AND r.is_approved = TRUE 
                ORDER BY r.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener promedio de rating de un producto
    public function getProductRating($productId) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM product_reviews 
                WHERE product_id = :product_id AND is_approved = TRUE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Contar productos con filtros
    public function countProducts($filters = []) {
        $sql = "SELECT COUNT(*) as total
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.is_active = 1";
        
        $params = [];
        
        // Filtro por categoría (slug único)
        if (!empty($filters['category'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }
        
        // Filtro por múltiples categorías (IDs)
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = str_repeat('?,', count($filters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $filters['categories']);
        }
        
        // Filtro por marca (slug único)
        if (!empty($filters['brand'])) {
            $sql .= " AND b.slug = ?";
            $params[] = $filters['brand'];
        }
        
        // Filtro por múltiples marcas (IDs)
        if (!empty($filters['brands']) && is_array($filters['brands'])) {
            $placeholders = str_repeat('?,', count($filters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $filters['brands']);
        }
        
        // Filtro por múltiples consolas (IDs de tabla consoles) - CORREGIDO
        if (!empty($filters['consoles']) && is_array($filters['consoles'])) {
            $placeholders = str_repeat('?,', count($filters['consoles']) - 1) . '?';
            $sql .= " AND p.console_id IN ($placeholders)";
            $params = array_merge($params, $filters['consoles']);
        }
        
        // Filtro por múltiples géneros (IDs de tabla genres con relación N:N) - CORREGIDO
        if (!empty($filters['genres']) && is_array($filters['genres'])) {
            $placeholders = str_repeat('?,', count($filters['genres']) - 1) . '?';
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_genres pg 
                        WHERE pg.product_id = p.id 
                        AND pg.genre_id IN ($placeholders)
                    )";
            $params = array_merge($params, $filters['genres']);
        }
        
        // Búsqueda general
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        // Filtros de precio
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price_pesos >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price_pesos <= ?";
            $params[] = $filters['max_price'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    // Funciones para manejo de etiquetas
    public function addTagsToProduct($product_id, $tags) {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        
        $sql = "UPDATE products SET tags = JSON_ARRAY_APPEND(COALESCE(tags, JSON_ARRAY()), '$', ?) WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($tags as $tag) {
            $stmt->execute([$tag, $product_id]);
        }
        
        return true;
    }
    
    public function removeTagFromProduct($product_id, $tag) {
        $sql = "UPDATE products SET tags = JSON_REMOVE(tags, JSON_UNQUOTE(JSON_SEARCH(tags, 'one', ?))) WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$tag, $product_id]);
    }
    
    public function getProductsByTag($tag, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       p.console, p.publication_date, p.price_usd, p.price_pesos, p.release_date,
                       p.main_image, p.long_description, p.rating, p.developer,
                       p.publisher, p.genre, p.condition_product, p.tags
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.is_active = 1 AND JSON_CONTAINS(p.tags, JSON_ARRAY(?))
                ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC";
        
        if ($limit) {
            $limit = (int)$limit; // Sanitizar
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tag]);
        
        return $stmt->fetchAll();
    }
    
    public function getAllTags() {
        $sql = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) as tag
                FROM products p
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                ) numbers
                WHERE JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']')) IS NOT NULL
                AND tags IS NOT NULL
                ORDER BY tag";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Funciones para manejo de precios
    public function updateProductPrices($product_id, $price_pesos, $price_usd = null) {
        if ($price_usd === null) {
            // Calcular USD basado en un tipo de cambio (esto debería venir de una configuración)
            $exchange_rate = $this->getExchangeRate();
            $price_usd = round($price_pesos / $exchange_rate, 2);
        }
        
        $sql = "UPDATE products SET price_pesos = ?, price_usd = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$price_pesos, $price_usd, $product_id]);
    }
    
    public function getExchangeRate($from = 'ARS', $to = 'USD') {
        $sql = "SELECT 
                    (SELECT exchange_rate FROM currency_settings WHERE currency_code = ?) / 
                    (SELECT exchange_rate FROM currency_settings WHERE currency_code = ?) as rate";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$to, $from]);
        $result = $stmt->fetch();
        
        return $result ? $result['rate'] : 1000; // Default rate if not configured
    }
    
    public function formatPrice($amount, $currency = 'ARS') {
        $settings = $this->getCurrencySettings($currency);
        
        if (!$settings) {
            return '$' . number_format($amount, 2);
        }
        
        $formatted = number_format($amount, $settings['decimal_places'], '.', ',');
        
        if ($settings['symbol_position'] === 'before') {
            return $settings['symbol'] . ' ' . $formatted;
        } else {
            return $formatted . ' ' . $settings['symbol'];
        }
    }
    
    private function getCurrencySettings($currency_code) {
        $sql = "SELECT * FROM currency_settings WHERE currency_code = ? AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$currency_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ===================================================
    // SISTEMA DE FILTROS DINÁMICOS
    // ===================================================
    
    /**
     * Obtener todas las categorías de filtros disponibles
     */
    public function getFilterCategories() {
        $sql = "SELECT * FROM tag_categories WHERE is_active = 1 ORDER BY filter_order, display_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener etiquetas disponibles por categoría (solo las que tienen productos)
     */
    public function getAvailableTagsByCategory($category_id = null) {
        $sql = "
        SELECT DISTINCT 
            pt.tag,
            pt.display_name,
            pt.category_id,
            tc.name as category_name,
            tc.display_name as category_display_name,
            tc.icon as category_icon,
            COUNT(DISTINCT p.id) as product_count
        FROM product_tags pt
        JOIN tag_categories tc ON pt.category_id = tc.id
        JOIN products p ON JSON_CONTAINS(p.tags, JSON_ARRAY(pt.tag))
        WHERE pt.is_active = 1 AND tc.is_active = 1 AND p.is_active = 1
        ";
        
        $params = [];
        if ($category_id) {
            $sql .= " AND pt.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " GROUP BY pt.id, pt.tag, pt.display_name, pt.category_id, tc.name, tc.display_name, tc.icon
                  HAVING product_count > 0
                  ORDER BY tc.filter_order, pt.display_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener filtros organizados por categoría
     */
    public function getOrganizedFilters() {
        $filters = [];
        $categories = $this->getFilterCategories();
        
        foreach ($categories as $category) {
            $tags = $this->getAvailableTagsByCategory($category['id']);
            if (!empty($tags)) {
                $filters[] = [
                    'category' => $category,
                    'tags' => $tags
                ];
            }
        }
        
        return $filters;
    }
    
    /**
     * Buscar productos con filtros dinámicos por etiquetas
     */
    public function getProductsWithDynamicFilters($filters = []) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       pi.image_url as primary_image,
                       p.console, p.publication_date, p.price_usd, p.price_pesos, p.release_date,
                       p.main_image, p.long_description, p.rating, p.developer,
                       p.publisher, p.genre, p.condition_product, p.tags
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_active = 1";
        
        $params = [];
        $where_conditions = [];
        
        // Filtros tradicionales
        if (!empty($filters['category'])) {
            $where_conditions[] = "c.slug = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $where_conditions[] = "b.slug = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "p.price_pesos >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "p.price_pesos <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Filtros dinámicos por etiquetas
        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            $tag_conditions = [];
            foreach ($filters['tags'] as $tag) {
                $tag_conditions[] = "JSON_CONTAINS(p.tags, JSON_ARRAY(?))";
                $params[] = $tag;
            }
            if (!empty($tag_conditions)) {
                $where_conditions[] = "(" . implode(" AND ", $tag_conditions) . ")";
            }
        }
        
        // Agregar condiciones WHERE
        if (!empty($where_conditions)) {
            $sql .= " AND " . implode(" AND ", $where_conditions);
        }
        
        // Orden personalizable - SIEMPRE productos con stock primero
        if (!empty($filters['order_by'])) {
            $sql .= " ORDER BY (p.stock_quantity > 0) DESC, " . $filters['order_by'];
        } else {
            $sql .= " ORDER BY (p.stock_quantity > 0) DESC, p.created_at DESC";
        }
        
        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Contar productos con filtros dinámicos
     */
    public function countProductsWithDynamicFilters($filters = []) {
        $sql = "SELECT COUNT(*) as total
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.is_active = TRUE";
        
        $params = [];
        $where_conditions = [];
        
        // Filtros tradicionales
        if (!empty($filters['category'])) {
            $where_conditions[] = "c.slug = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $where_conditions[] = "b.slug = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "p.price_pesos >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "p.price_pesos <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Filtros dinámicos por etiquetas
        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            $tag_conditions = [];
            foreach ($filters['tags'] as $tag) {
                $tag_conditions[] = "JSON_CONTAINS(p.tags, JSON_ARRAY(?))";
                $params[] = $tag;
            }
            if (!empty($tag_conditions)) {
                $where_conditions[] = "(" . implode(" AND ", $tag_conditions) . ")";
            }
        }
        
        // Agregar condiciones WHERE
        if (!empty($where_conditions)) {
            $sql .= " AND " . implode(" AND ", $where_conditions);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Agregar etiqueta automáticamente categorizada
     */
    public function addCategorizedTag($product_id, $tag, $display_name = null) {
        // Verificar si la etiqueta ya existe en product_tags
        $check_sql = "SELECT category_id FROM product_tags WHERE tag = ? AND is_active = 1 LIMIT 1";
        $check_stmt = $this->pdo->prepare($check_sql);
        $check_stmt->execute([$tag]);
        $existing = $check_stmt->fetch();
        
        $category_id = null;
        
        if ($existing) {
            $category_id = $existing['category_id'];
        } else {
            // Auto-categorizar nueva etiqueta
            $category_id = $this->autoCategorizTag($tag);
            
            // Agregar a product_tags
            if ($category_id) {
                $insert_tag_sql = "INSERT INTO product_tags (tag, category_id, display_name, auto_detected) VALUES (?, ?, ?, TRUE)";
                $insert_tag_stmt = $this->pdo->prepare($insert_tag_sql);
                $insert_tag_stmt->execute([$tag, $category_id, $display_name ?: ucfirst(str_replace('-', ' ', $tag))]);
            }
        }
        
        // Agregar etiqueta al producto
        return $this->addTagsToProduct($product_id, [$tag]);
    }
    
    /**
     * Auto-categorizar una etiqueta basada en patrones
     */
    private function autoCategorizTag($tag) {
        $patterns = [
            'consolas' => '/(ps[0-9]|playstation|xbox|nintendo|switch|wii|3ds|psp)/i',
            'generos' => '/(accion|aventura|rpg|shooter|estrategia|deportes|carreras|lucha)/i',
            'marcas' => '/(sony|nintendo|microsoft|square|capcom|konami|bandai|sega)/i',
            'accesorios' => '/(mando|cable|cargador|funda|memoria|control)/i',
            'condicion' => '/(nuevo|usado|coleccion|refurbished)/i'
        ];
        
        foreach ($patterns as $category_name => $pattern) {
            if (preg_match($pattern, $tag)) {
                $sql = "SELECT id FROM tag_categories WHERE name = ? AND is_active = 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$category_name]);
                $result = $stmt->fetch();
                return $result ? $result['id'] : null;
            }
        }
        
        // Por defecto: tipo
        $sql = "SELECT id FROM tag_categories WHERE name = 'tipo' AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
}

// Crear instancia global
$productManager = new ProductManager($pdo);
?>