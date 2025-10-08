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
