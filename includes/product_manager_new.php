<?php
class ProductManagerNew {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todos los productos con información relacionada
     */
    public function getAllProducts($limit = null, $offset = 0) {
        $sql = "SELECT 
                    p.*,
                    c.name as console_name,
                    c.manufacturer as console_manufacturer,
                    d.name as developer_name,
                    d.country as developer_country,
                    ct.name as collection_type_name,
                    ct.description as collection_type_description
                FROM products p
                LEFT JOIN consoles c ON p.console_id = c.id
                LEFT JOIN developers d ON p.developer_id = d.id
                LEFT JOIN collection_types ct ON p.collection_type_id = ct.id
                WHERE p.active = 1
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->pdo->prepare($sql);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener un producto por ID con toda su información
     */
    public function getProductById($id) {
        $sql = "SELECT 
                    p.*,
                    c.name as console_name,
                    c.slug as console_slug,
                    c.manufacturer as console_manufacturer,
                    d.name as developer_name,
                    d.slug as developer_slug,
                    d.country as developer_country,
                    ct.name as collection_type_name,
                    ct.description as collection_type_description
                FROM products p
                LEFT JOIN consoles c ON p.console_id = c.id
                LEFT JOIN developers d ON p.developer_id = d.id
                LEFT JOIN collection_types ct ON p.collection_type_id = ct.id
                WHERE p.id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Obtener tags del producto
            $product['tags'] = $this->getProductTags($id);
        }
        
        return $product;
    }
    
    /**
     * Obtener tags de un producto específico
     */
    public function getProductTags($productId) {
        $sql = "SELECT t.* 
                FROM tags t
                INNER JOIN product_tags pt ON t.id = pt.tag_id
                WHERE pt.product_id = :product_id
                ORDER BY t.name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear un nuevo producto
     */
    public function createProduct($data) {
        $sql = "INSERT INTO products (
                    name, slug, description, short_description,
                    price_pesos, price_usd, stock_quantity,
                    console_id, developer_id, condition_product,
                    is_collection, collection_type_id, featured,
                    active, image_url, gallery
                ) VALUES (
                    :name, :slug, :description, :short_description,
                    :price_pesos, :price_usd, :stock_quantity,
                    :console_id, :developer_id, :condition_product,
                    :is_collection, :collection_type_id, :featured,
                    :active, :image_url, :gallery
                )";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Generar slug si no se proporciona
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        // Bind parameters
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':slug', $data['slug']);
        $stmt->bindValue(':description', $data['description'] ?? '');
        $stmt->bindValue(':short_description', $data['short_description'] ?? '');
        $stmt->bindValue(':price_pesos', $data['price_pesos'] ?? 0);
        $stmt->bindValue(':price_usd', $data['price_usd'] ?? 0);
        $stmt->bindValue(':stock_quantity', $data['stock_quantity'] ?? 0);
        $stmt->bindValue(':console_id', $data['console_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':developer_id', $data['developer_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':condition_product', $data['condition_product'] ?? 'nuevo');
        $stmt->bindValue(':is_collection', $data['is_collection'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':collection_type_id', $data['collection_type_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':featured', $data['featured'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':active', $data['active'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':image_url', $data['image_url'] ?? '');
        $stmt->bindValue(':gallery', $data['gallery'] ?? null);
        
        if ($stmt->execute()) {
            $productId = $this->pdo->lastInsertId();
            
            // Agregar tags si se proporcionan
            if (!empty($data['tags'])) {
                $this->updateProductTags($productId, $data['tags']);
            }
            
            return $productId;
        }
        
        return false;
    }
    
    /**
     * Actualizar producto existente
     */
    public function updateProduct($id, $data) {
        $sql = "UPDATE products SET 
                    name = :name,
                    slug = :slug,
                    description = :description,
                    short_description = :short_description,
                    price_pesos = :price_pesos,
                    price_usd = :price_usd,
                    stock_quantity = :stock_quantity,
                    console_id = :console_id,
                    developer_id = :developer_id,
                    condition_product = :condition_product,
                    is_collection = :is_collection,
                    collection_type_id = :collection_type_id,
                    featured = :featured,
                    active = :active,
                    image_url = :image_url,
                    gallery = :gallery,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Generar slug si no se proporciona
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':slug', $data['slug']);
        $stmt->bindValue(':description', $data['description'] ?? '');
        $stmt->bindValue(':short_description', $data['short_description'] ?? '');
        $stmt->bindValue(':price_pesos', $data['price_pesos'] ?? 0);
        $stmt->bindValue(':price_usd', $data['price_usd'] ?? 0);
        $stmt->bindValue(':stock_quantity', $data['stock_quantity'] ?? 0);
        $stmt->bindValue(':console_id', $data['console_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':developer_id', $data['developer_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':condition_product', $data['condition_product'] ?? 'nuevo');
        $stmt->bindValue(':is_collection', $data['is_collection'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':collection_type_id', $data['collection_type_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':featured', $data['featured'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':active', $data['active'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':image_url', $data['image_url'] ?? '');
        $stmt->bindValue(':gallery', $data['gallery'] ?? null);
        
        $success = $stmt->execute();
        
        // Actualizar tags si se proporcionan
        if ($success && isset($data['tags'])) {
            $this->updateProductTags($id, $data['tags']);
        }
        
        return $success;
    }
    
    /**
     * Actualizar tags de un producto
     */
    public function updateProductTags($productId, $tagIds) {
        // Eliminar tags existentes
        $stmt = $this->pdo->prepare("DELETE FROM product_tags WHERE product_id = :product_id");
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Agregar nuevos tags
        if (!empty($tagIds)) {
            $stmt = $this->pdo->prepare("INSERT INTO product_tags (product_id, tag_id) VALUES (:product_id, :tag_id)");
            foreach ($tagIds as $tagId) {
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    /**
     * Obtener todas las consolas
     */
    public function getConsoles() {
        $stmt = $this->pdo->query("SELECT * FROM consoles ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las desarrolladoras
     */
    public function getDevelopers() {
        $stmt = $this->pdo->query("SELECT * FROM developers ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todos los tipos de colección
     */
    public function getCollectionTypes() {
        $stmt = $this->pdo->query("SELECT * FROM collection_types ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las etiquetas
     */
    public function getTags() {
        $stmt = $this->pdo->query("SELECT * FROM tags ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nueva consola
     */
    public function createConsole($name, $manufacturer = '') {
        $slug = $this->generateSlug($name);
        $stmt = $this->pdo->prepare("INSERT INTO consoles (name, slug, manufacturer) VALUES (:name, :slug, :manufacturer)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':slug', $slug);
        $stmt->bindValue(':manufacturer', $manufacturer);
        
        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * Crear nueva desarrolladora
     */
    public function createDeveloper($name, $country = '') {
        $slug = $this->generateSlug($name);
        $stmt = $this->pdo->prepare("INSERT INTO developers (name, slug, country) VALUES (:name, :slug, :country)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':slug', $slug);
        $stmt->bindValue(':country', $country);
        
        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * Crear nuevo tipo de colección
     */
    public function createCollectionType($name, $description = '') {
        $slug = $this->generateSlug($name);
        $stmt = $this->pdo->prepare("INSERT INTO collection_types (name, slug, description) VALUES (:name, :slug, :description)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':slug', $slug);
        $stmt->bindValue(':description', $description);
        
        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * Generar slug único
     */
    private function generateSlug($text) {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
    
    /**
     * Eliminar producto
     */
    public function deleteProduct($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Buscar productos
     */
    public function searchProducts($searchTerm, $filters = []) {
        $sql = "SELECT DISTINCT p.*,
                    c.name as console_name,
                    d.name as developer_name,
                    ct.name as collection_type_name
                FROM products p
                LEFT JOIN consoles c ON p.console_id = c.id
                LEFT JOIN developers d ON p.developer_id = d.id
                LEFT JOIN collection_types ct ON p.collection_type_id = ct.id
                LEFT JOIN product_tags pt ON p.id = pt.product_id
                LEFT JOIN tags t ON pt.tag_id = t.id
                WHERE p.active = 1";
        
        $params = [];
        
        if ($searchTerm) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search OR t.name LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }
        
        if (!empty($filters['console_id'])) {
            $sql .= " AND p.console_id = :console_id";
            $params[':console_id'] = $filters['console_id'];
        }
        
        if (!empty($filters['developer_id'])) {
            $sql .= " AND p.developer_id = :developer_id";
            $params[':developer_id'] = $filters['developer_id'];
        }
        
        if (!empty($filters['condition'])) {
            $sql .= " AND p.condition_product = :condition";
            $params[':condition'] = $filters['condition'];
        }
        
        if (!empty($filters['is_collection'])) {
            $sql .= " AND p.is_collection = :is_collection";
            $params[':is_collection'] = $filters['is_collection'];
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>