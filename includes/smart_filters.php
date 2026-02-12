<?php
/**
 * Sistema de Filtros Inteligentes y Compatibles
 * 
 * Este sistema construye filtros dinámicamente basándose en los productos existentes
 * y maneja la compatibilidad entre filtros (si seleccionas Accesorios, solo muestra
 * marcas/consolas/géneros de productos que son accesorios)
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
        
        // Aplicar filtros para mostrar solo categorías compatibles
        if (!empty($appliedFilters['brands'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['brands']) - 1) . '?';
            $sql .= " AND p.brand_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['brands']);
        }
        
        if (!empty($appliedFilters['consoles'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['consoles']) - 1) . '?';
            $sql .= " AND p.console IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            foreach ($appliedFilters['genres'] as $genre) {
                $sql .= " AND p.genre LIKE ?";
                $params[] = "%$genre%";
            }
        }
        
        $sql .= " GROUP BY c.id, c.name, c.slug
                  HAVING product_count > 0
                  ORDER BY c.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las marcas que tienen productos
     */
    public function getAvailableBrands($appliedFilters = []) {
        $sql = "SELECT DISTINCT b.id, b.name, b.slug, COUNT(DISTINCT p.id) as product_count
                FROM brands b
                INNER JOIN products p ON b.id = p.brand_id
                WHERE p.is_active = TRUE";
        
        $params = [];
        
        // Aplicar filtros para mostrar solo marcas compatibles
        if (!empty($appliedFilters['categories'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['categories']) - 1) . '?';
            $sql .= " AND p.category_id IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['categories']);
        }
        
        if (!empty($appliedFilters['consoles'])) {
            $placeholders = str_repeat('?,', count($appliedFilters['consoles']) - 1) . '?';
            $sql .= " AND p.console IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            foreach ($appliedFilters['genres'] as $genre) {
                $sql .= " AND p.genre LIKE ?";
                $params[] = "%$genre%";
            }
        }
        
        $sql .= " GROUP BY b.id, b.name, b.slug
                  HAVING product_count > 0
                  ORDER BY b.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las consolas únicas que tienen productos
     */
    public function getAvailableConsoles($appliedFilters = []) {
        $sql = "SELECT DISTINCT p.console, COUNT(DISTINCT p.id) as product_count
                FROM products p
                WHERE p.is_active = TRUE 
                AND p.console IS NOT NULL 
                AND p.console != ''";
        
        $params = [];
        
        // Aplicar filtros para mostrar solo consolas compatibles
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
            foreach ($appliedFilters['genres'] as $genre) {
                $sql .= " AND p.genre LIKE ?";
                $params[] = "%$genre%";
            }
        }
        
        $sql .= " GROUP BY p.console
                  HAVING product_count > 0
                  ORDER BY p.console ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear resultados
        $consoles = [];
        foreach ($results as $row) {
            $consoles[] = [
                'name' => $row['console'],
                'slug' => $this->slugify($row['console']),
                'product_count' => $row['product_count']
            ];
        }
        
        return $consoles;
    }
    
    /**
     * Obtener todos los géneros únicos que tienen productos
     */
    public function getAvailableGenres($appliedFilters = []) {
        $sql = "SELECT DISTINCT p.genre, COUNT(DISTINCT p.id) as product_count
                FROM products p
                WHERE p.is_active = TRUE 
                AND p.genre IS NOT NULL 
                AND p.genre != ''";
        
        $params = [];
        
        // Aplicar filtros para mostrar solo géneros compatibles
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
            $sql .= " AND p.console IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        $sql .= " GROUP BY p.genre
                  HAVING product_count > 0
                  ORDER BY p.genre ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar géneros (pueden ser múltiples separados por comas o barras)
        $genres = [];
        foreach ($results as $row) {
            // Dividir por comas, barras, y otros separadores comunes
            $genreList = preg_split('/[,\/|;]/', $row['genre']);
            foreach ($genreList as $genre) {
                $genre = trim($genre);
                if (empty($genre)) continue;
                
                $slug = $this->slugify($genre);
                if (!isset($genres[$slug])) {
                    $genres[$slug] = [
                        'name' => $genre,
                        'slug' => $slug,
                        'product_count' => 0
                    ];
                }
                $genres[$slug]['product_count'] += $row['product_count'];
            }
        }
        
        // Ordenar por nombre
        usort($genres, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return array_values($genres);
    }
    
    /**
     * Obtener rango de precios disponibles
     */
    public function getAvailablePriceRange($appliedFilters = []) {
        $sql = "SELECT 
                    MIN(p.price_pesos) as min_price,
                    MAX(p.price_pesos) as max_price
                FROM products p
                WHERE p.is_active = TRUE 
                AND p.price_pesos > 0";
        
        $params = [];
        
        // Aplicar filtros para mostrar solo rango de precios compatible
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
            $sql .= " AND p.console IN ($placeholders)";
            $params = array_merge($params, $appliedFilters['consoles']);
        }
        
        if (!empty($appliedFilters['genres'])) {
            foreach ($appliedFilters['genres'] as $genre) {
                $sql .= " AND p.genre LIKE ?";
                $params[] = "%$genre%";
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'min' => $result['min_price'] ?? 0,
            'max' => $result['max_price'] ?? 0
        ];
    }
    
    /**
     * Convertir texto a slug
     */
    private function slugify($text) {
        // Reemplazar caracteres especiales
        $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], strtolower($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
    
    /**
     * Obtener todos los filtros disponibles de una vez
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
?>
