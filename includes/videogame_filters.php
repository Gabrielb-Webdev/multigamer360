<?php
/**
 * Sistema de Filtros Jerárquicos para Videojuegos
 * Similar a GameOn - Muestra consolas cuando se selecciona "Videojuegos"
 */

class VideoGameFilters {
    private $pdo;
    
    // Mapeo de consolas con sus categorías principales
    private $consoleMap = [
        'NINTENDO' => [
            'name' => 'Nintendo',
            'consoles' => ['NES', 'SNES', 'N64', 'Gamecube', 'Wii', 'Wii U', 'Switch', 'Gameboy/Gameboy Color', 'Gameboy advance', 'NDS', '3DS', 'Famicom']
        ],
        'SEGA' => [
            'name' => 'Sega',
            'consoles' => ['Game Gear', 'Genesis', 'Saturn', 'Dreamcast', 'Sega CD']
        ],
        'SONY' => [
            'name' => 'Sony',
            'consoles' => ['PS', 'PS2', 'PS3', 'PS4', 'PS5', 'PSVita']
        ],
        'MICROSOFT' => [
            'name' => 'Microsoft',
            'consoles' => ['Xbox', 'Xbox 360', 'Xbox One']
        ],
        'OTROS' => [
            'name' => 'Otros',
            'consoles' => ['Colecovision', 'Manuales', 'Varios', 'Master System', 'PC', 'NeoGeo']
        ],
        'RETRO' => [
            'name' => 'Retro',
            'consoles' => ['Home Computer', 'Repuestos', 'Atari', '3DO']
        ]
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener la categoría principal de Videojuegos
     */
    public function getVideoGamesCategory() {
        $stmt = $this->pdo->prepare("
            SELECT id, name, slug 
            FROM categories 
            WHERE slug = 'videojuegos' OR name LIKE '%videojuegos%' OR name LIKE '%video%juegos%'
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las subcategorías (consolas) de videojuegos
     */
    public function getConsoleCategories($parentId = null) {
        if ($parentId) {
            $stmt = $this->pdo->prepare("
                SELECT id, name, slug, parent_id, icon_class, image
                FROM categories 
                WHERE parent_id = ? AND is_active = 1
                ORDER BY sort_order ASC, name ASC
            ");
            $stmt->execute([$parentId]);
        } else {
            // Obtener todas las categorías que parecen ser consolas
            $stmt = $this->pdo->query("
                SELECT id, name, slug, parent_id, icon_class, image
                FROM categories 
                WHERE is_active = 1
                AND (
                    parent_id IS NOT NULL 
                    OR name IN ('NES', 'SNES', 'PS', 'PS2', 'PS3', 'PS4', 'Xbox', 'Nintendo', 'Sega', 'Sony')
                )
                ORDER BY sort_order ASC, name ASC
            ");
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Organizar consolas por fabricante
     */
    public function getOrganizedConsoles() {
        $allCategories = $this->getConsoleCategories();
        $organized = [];
        
        foreach ($this->consoleMap as $key => $group) {
            $organized[$key] = [
                'name' => $group['name'],
                'consoles' => []
            ];
            
            foreach ($allCategories as $category) {
                if (in_array($category['name'], $group['consoles'])) {
                    $organized[$key]['consoles'][] = $category;
                }
            }
        }
        
        return $organized;
    }
    
    /**
     * Obtener consolas con conteo de productos
     */
    public function getConsolesWithCount() {
        $videoGamesCategory = $this->getVideoGamesCategory();
        
        if (!$videoGamesCategory) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id, 
                c.name, 
                c.slug, 
                c.icon_class,
                c.image,
                COUNT(DISTINCT pc.product_id) as product_count
            FROM categories c
            LEFT JOIN product_categories pc ON c.id = pc.category_id
            LEFT JOIN products p ON pc.product_id = p.id AND p.status = 'active'
            WHERE c.parent_id = ? AND c.is_active = 1
            GROUP BY c.id, c.name, c.slug, c.icon_class, c.image
            HAVING product_count > 0
            ORDER BY c.sort_order ASC, c.name ASC
        ");
        
        $stmt->execute([$videoGamesCategory['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener filtros dinámicos específicos para una consola
     * Solo muestra opciones de filtros que realmente existen en productos de esa consola
     */
    public function getConsoleSpecificFilters($consoleSlug) {
        $console = $this->getCurrentConsole($consoleSlug);
        
        if (!$console) {
            return [];
        }
        
        // Obtener tags que realmente existen en productos de esta consola
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT 
                tc.id as category_id,
                tc.name as category_name,
                tc.slug as category_slug,
                t.id as tag_id,
                t.name as tag_name,
                t.slug as tag_slug,
                COUNT(DISTINCT pt.product_id) as product_count
            FROM tag_categories tc
            INNER JOIN tags t ON tc.id = t.category_id
            INNER JOIN product_tags pt ON t.id = pt.tag_id
            INNER JOIN products p ON pt.product_id = p.id
            INNER JOIN product_categories pc ON p.id = pc.product_id
            WHERE pc.category_id = ? 
            AND p.status = 'active'
            GROUP BY tc.id, tc.name, tc.slug, t.id, t.name, t.slug
            HAVING product_count > 0
            ORDER BY tc.sort_order ASC, tc.name ASC, t.name ASC
        ");
        
        $stmt->execute([$console['id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar por categorías
        $organized = [];
        foreach ($results as $row) {
            $catId = $row['category_id'];
            
            if (!isset($organized[$catId])) {
                $organized[$catId] = [
                    'id' => $catId,
                    'name' => $row['category_name'],
                    'slug' => $row['category_slug'],
                    'tags' => []
                ];
            }
            
            $organized[$catId]['tags'][] = [
                'id' => $row['tag_id'],
                'name' => $row['tag_name'],
                'slug' => $row['tag_slug'],
                'count' => $row['product_count']
            ];
        }
        
        return array_values($organized);
    }
    
    /**
     * Obtener marcas específicas disponibles para una consola
     */
    public function getConsoleBrands($consoleSlug) {
        $console = $this->getCurrentConsole($consoleSlug);
        
        if (!$console) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT 
                b.id, 
                b.name,
                COUNT(DISTINCT p.id) as product_count
            FROM brands b
            INNER JOIN products p ON b.id = p.brand_id
            INNER JOIN product_categories pc ON p.id = pc.product_id
            WHERE pc.category_id = ? 
            AND p.status = 'active'
            GROUP BY b.id, b.name
            HAVING product_count > 0
            ORDER BY b.name ASC
        ");
        
        $stmt->execute([$console['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener rango de precios para una consola específica
     */
    public function getConsolePriceRange($consoleSlug) {
        $console = $this->getCurrentConsole($consoleSlug);
        
        if (!$console) {
            return ['min' => 0, 'max' => 0];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                MIN(p.price_pesos) as min_price,
                MAX(p.price_pesos) as max_price
            FROM products p
            INNER JOIN product_categories pc ON p.id = pc.product_id
            WHERE pc.category_id = ? 
            AND p.status = 'active'
            AND p.price_pesos > 0
        ");
        
        $stmt->execute([$console['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'min' => $result['min_price'] ?? 0,
            'max' => $result['max_price'] ?? 0
        ];
    }
    
    /**
     * Verificar si estamos en la vista de videojuegos
     */
    public function isVideoGamesView($categorySlug = null) {
        if (!$categorySlug) {
            $categorySlug = $_GET['category'] ?? null;
        }
        
        return $categorySlug === 'videojuegos' || stripos($categorySlug, 'video') !== false;
    }
    
    /**
     * Obtener la consola actual seleccionada
     */
    public function getCurrentConsole($categorySlug = null) {
        if (!$categorySlug) {
            $categorySlug = $_GET['category'] ?? null;
        }
        
        if (!$categorySlug) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT id, name, slug, parent_id 
            FROM categories 
            WHERE slug = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$categorySlug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generar HTML para el selector de consolas (estilo GameOn)
     */
    public function renderConsoleGrid() {
        $consoles = $this->getConsolesWithCount();
        
        if (empty($consoles)) {
            return '<p>No hay consolas disponibles.</p>';
        }
        
        $html = '<div class="console-grid">';
        
        foreach ($consoles as $console) {
            $imageUrl = !empty($console['image']) ? $console['image'] : 'assets/images/default-console.png';
            $iconClass = !empty($console['icon_class']) ? $console['icon_class'] : 'fas fa-gamepad';
            
            $html .= '
            <a href="productos.php?category=' . urlencode($console['slug']) . '" class="console-card">
                <div class="console-image">
                    ' . ($console['image'] ? '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($console['name']) . '">' : '<i class="' . htmlspecialchars($iconClass) . '"></i>') . '
                </div>
                <h3 class="console-name">' . htmlspecialchars($console['name']) . '</h3>
                <span class="console-count">' . $console['product_count'] . ' productos</span>
            </a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
?>
