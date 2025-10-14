<?php
/**
 * =====================================================
 * SISTEMA DE FILTROS INTELIGENTES V2.0
 * =====================================================
 * 
 * Trabaja con la nueva estructura de base de datos:
 * - categories (tabla existente)
 * - brands (tabla existente)
 * - consoles (nueva tabla de referencia)
 * - genres (nueva tabla de referencia)
 * - product_genres (relación N:N entre productos y géneros)
 * 
 * Los filtros se construyen dinámicamente basándose en productos existentes
 * y maneja compatibilidad (filtros interdependientes)
 */

class SmartFilters {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todas las categorías que tienen productos
     */
    public function getAvailableCategories($appliedFilters = []) {
        $sql = "SELECT DISTINCT c.id, c.name, c.slug, COUNT(DISTINCT p.id) as product_count
                FROM categories c
                INNER JOIN products p ON c.id = p.category_id
                WHERE p.is_active = TRUE";
        
        $params = [];
        
        // Aplicar filtros para compatibilidad
        if (!empty($appliedFilters['brands'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['brands']);
        }
        
        if (!empty($appliedFilters['consoles'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['consoles']) - 1) . '?';
            $sql .= " AND p.console_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['genres']) - 1) . '?';
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_genres pg 
                        WHERE pg.product_id = p.id 
                        AND pg.genre_id IN ($placeholders)
                    )";
            $params = array_merge($params, $appliedFilters['genres']);
        }
        
        $sql .= " GROUP BY c.id, c.name, c.slug
                  HAVING product_count > 0
                  ORDER BY c.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las marcas que tienen productos (compatibles con filtros aplicados)
     */
    public function getAvailableBrands($appliedFilters = []) {
        $sql = "SELECT DISTINCT b.id, b.name, b.slug, COUNT(DISTINCT p.id) as product_count
                FROM brands b
                INNER JOIN products p ON b.id = p.brand_id
                WHERE p.is_active = TRUE";
        
        $params = [];
        
        // Aplicar filtros para compatibilidad
        if (!empty($appliedFilters['categories'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['categories']);
        }
        
        if (!empty($appliedFilters['consoles'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['consoles']) - 1) . '?';
            $sql .= " AND p.console_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['genres']) - 1) . '?';
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_genres pg 
                        WHERE pg.product_id = p.id 
                        AND pg.genre_id IN ($placeholders)
                    )";
            $params = array_merge($params, $appliedFilters['genres']);
        }
        
        $sql .= " GROUP BY b.id, b.name, b.slug
                  HAVING product_count > 0
                  ORDER BY b.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las consolas que tienen productos (compatibles con filtros aplicados)
     */
    public function getAvailableConsoles($appliedFilters = []) {
        $sql = "SELECT DISTINCT co.id, co.name, co.slug, co.manufacturer, 
                       COUNT(DISTINCT p.id) as product_count
                FROM consoles co
                INNER JOIN products p ON co.id = p.console_id
                WHERE p.is_active = TRUE";
        
        $params = [];
        
        // Aplicar filtros para compatibilidad
        if (!empty($appliedFilters['categories'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['categories']);
        }
        
        if (!empty($appliedFilters['brands'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['brands']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['genres']) - 1) . '?';
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_genres pg 
                        WHERE pg.product_id = p.id 
                        AND pg.genre_id IN ($placeholders)
                    )";
            $params = array_merge($params, $appliedFilters['genres']);
        }
        
        $sql .= " GROUP BY co.id, co.name, co.slug, co.manufacturer
                  HAVING product_count > 0
                  ORDER BY co.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todos los géneros que tienen productos (compatibles con filtros aplicados)
     */
    public function getAvailableGenres($appliedFilters = []) {
        $sql = "SELECT DISTINCT g.id, g.name, g.slug,
                       COUNT(DISTINCT pg.product_id) as product_count
                FROM genres g
                INNER JOIN product_genres pg ON g.id = pg.genre_id
                INNER JOIN products p ON pg.product_id = p.id
                WHERE p.is_active = TRUE";
        
        $params = [];
        
        // Aplicar filtros para compatibilidad
        if (!empty($appliedFilters['categories'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['categories']);
        }
        
        if (!empty($appliedFilters['brands'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['brands']);
        }
        
        if (!empty($appliedFilters['consoles'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['consoles']) - 1) . '?';
            $sql .= " AND p.console_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        $sql .= " GROUP BY g.id, g.name, g.slug
                  HAVING product_count > 0
                  ORDER BY g.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener rango de precios de productos (compatibles con filtros aplicados)
     */
    public function getAvailablePriceRange($appliedFilters = []) {
        $sql = "SELECT 
                    MIN(p.price) as min,
                    MAX(p.price) as max
                FROM products p
                WHERE p.is_active = TRUE";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($appliedFilters['categories'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['categories']);
        }
        
        if (!empty($appliedFilters['brands'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['brands']);
        }
        
        if (!empty($appliedFilters['consoles'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['consoles']) - 1) . '?';
            $sql .= " AND p.console_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['genres']) - 1) . '?';
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_genres pg 
                        WHERE pg.product_id = p.id 
                        AND pg.genre_id IN ($placeholders)
                    )";
            $params = array_merge($params, $appliedFilters['genres']);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'min' => $result['min'] ?? 0,
            'max' => $result['max'] ?? 0
        ];
    }
    
    /**
     * Obtener todos los filtros disponibles en una sola llamada
     */
    public function getAllAvailableFilters($appliedFilters = []) {
        return [
            'categories' => $this->getAvailableCategories($appliedFilters),
            'brands' => $this->getAvailableBrands($appliedFilters),
            'consoles' => $this->getAvailableConsoles($appliedFilters),
            'genres' => $this->getAvailableGenres($appliedFilters),
            'price_range' => $this->getAvailablePriceRange($appliedFilters)
        ];
    }
}
