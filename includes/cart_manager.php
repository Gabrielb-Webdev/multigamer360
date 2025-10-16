<?php
/**
 * Clase para manejar el carrito de compras con base de datos
 * Sincroniza entre sesión PHP y tabla cart_sessions
 */
class CartManager {
    private $pdo;
    private $session_id;
    private $user_id;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->session_id = session_id();
        $this->user_id = $_SESSION['user_id'] ?? null;
        
        // Inicializar carrito en sesión si no existe
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Sincronizar carrito al inicializar
        $this->syncCartFromDatabase();
    }

    /**
     * Obtener ID de sesión para carritos de invitados
     */
    private function getCartIdentifier() {
        return $this->user_id ?? $this->session_id;
    }

    /**
     * Sincronizar carrito desde la base de datos
     */
    public function syncCartFromDatabase() {
        try {
            // Validar que tenemos identificadores válidos
            if (empty($this->session_id) && empty($this->user_id)) {
                return false;
            }

            $sql = $this->user_id 
                ? "SELECT product_id, quantity FROM cart_sessions WHERE user_id = ?"
                : "SELECT product_id, quantity FROM cart_sessions WHERE id = ? AND user_id IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->getCartIdentifier()]);
            $cartItems = $stmt->fetchAll();

            // Limpiar carrito de sesión
            $_SESSION['cart'] = [];

            // Cargar items desde BD
            foreach ($cartItems as $item) {
                $_SESSION['cart'][$item['product_id']] = $item['quantity'];
            }

            return true;
        } catch (Exception $e) {
            error_log("Error sincronizando carrito desde BD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sincronizar carrito hacia la base de datos
     */
    public function syncCartToDatabase() {
        try {
            // Validar que tenemos un session_id válido
            if (empty($this->session_id)) {
                return false;
            }

            // Limpiar carrito actual en BD
            if ($this->user_id) {
                $stmt = $this->pdo->prepare("DELETE FROM cart_sessions WHERE user_id = ?");
                $stmt->execute([$this->user_id]);
            } else {
                $stmt = $this->pdo->prepare("DELETE FROM cart_sessions WHERE id = ? AND user_id IS NULL");
                $stmt->execute([$this->session_id]);
            }

            // Insertar items del carrito actual
            if (!empty($_SESSION['cart'])) {
                $sql = "INSERT INTO cart_sessions (id, user_id, product_id, quantity) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);

                foreach ($_SESSION['cart'] as $product_id => $quantity) {
                    $stmt->execute([
                        $this->session_id,
                        $this->user_id,
                        $product_id,
                        $quantity
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error sincronizando carrito hacia BD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Agregar producto al carrito
     */
    public function addToCart($product_id, $quantity = 1) {
        try {
            error_log("CartManager: addToCart llamado - Producto ID: $product_id, Cantidad: $quantity");
            
            // Validar que el producto existe y obtener info de stock
            $productInfo = $this->getProductInfo($product_id);
            
            error_log("CartManager: productInfo obtenido - " . print_r($productInfo, true));
            
            if (!$productInfo) {
                error_log("CartManager: ERROR - Producto $product_id no encontrado en BD");
                return ['success' => false, 'message' => 'Producto no encontrado'];
            }

            error_log("CartManager: Producto encontrado - Stock disponible: " . $productInfo['stock']);

            // Verificar stock disponible
            $currentQuantityInCart = $this->getProductQuantity($product_id);
            $totalRequestedQuantity = $currentQuantityInCart + $quantity;

            error_log("CartManager: Cantidad en carrito: $currentQuantityInCart, Total solicitado: $totalRequestedQuantity");

            if ($totalRequestedQuantity > $productInfo['stock']) {
                $availableToAdd = $productInfo['stock'] - $currentQuantityInCart;
                if ($availableToAdd <= 0) {
                    return [
                        'success' => false, 
                        'message' => 'Este producto ya no tiene stock disponible',
                        'stock_available' => 0
                    ];
                } else {
                    return [
                        'success' => false, 
                        'message' => "Solo puedes agregar {$availableToAdd} unidad(es) más de este producto",
                        'stock_available' => $availableToAdd
                    ];
                }
            }

            // Agregar a sesión
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }

            // Limitar por stock disponible (no más que el stock del producto)
            $_SESSION['cart'][$product_id] = min($_SESSION['cart'][$product_id], $productInfo['stock']);

            error_log("CartManager: Producto agregado a sesión - Nueva cantidad: " . $_SESSION['cart'][$product_id]);

            // Sincronizar con BD
            $this->syncCartToDatabase();

            error_log("CartManager: Carrito sincronizado con BD exitosamente");

            return [
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal(),
                'cart' => $_SESSION['cart'],
                'stock_info' => [
                    'available_stock' => $productInfo['stock'],
                    'in_cart' => $_SESSION['cart'][$product_id],
                    'remaining_stock' => $productInfo['stock'] - $_SESSION['cart'][$product_id]
                ]
            ];
        } catch (Exception $e) {
            error_log("Error agregando al carrito: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Remover producto del carrito
     */
    public function removeFromCart($product_id) {
        try {
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                
                // Sincronizar con BD
                $this->syncCartToDatabase();

                return [
                    'success' => true,
                    'message' => 'Producto eliminado del carrito',
                    'cart_count' => $this->getCartCount(),
                    'cart_total' => $this->getCartTotal(),
                    'cart' => $_SESSION['cart']
                ];
            } else {
                return ['success' => false, 'message' => 'Producto no encontrado en el carrito'];
            }
        } catch (Exception $e) {
            error_log("Error removiendo del carrito: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Actualizar cantidad de un producto
     */
    public function updateQuantity($product_id, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($product_id);
            }

            if (!$this->validateProduct($product_id)) {
                return ['success' => false, 'message' => 'Producto no encontrado'];
            }

            // Limitar cantidad
            $quantity = min($quantity, 10);
            $_SESSION['cart'][$product_id] = $quantity;

            // Sincronizar con BD
            $this->syncCartToDatabase();

            return [
                'success' => true,
                'message' => 'Cantidad actualizada',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal(),
                'cart' => $_SESSION['cart']
            ];
        } catch (Exception $e) {
            error_log("Error actualizando cantidad: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Limpiar todo el carrito
     */
    public function clearCart() {
        try {
            $_SESSION['cart'] = [];
            $this->syncCartToDatabase();

            return [
                'success' => true,
                'message' => 'Carrito limpiado',
                'cart_count' => 0,
                'cart_total' => 0,
                'cart' => []
            ];
        } catch (Exception $e) {
            error_log("Error limpiando carrito: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Verificar si un producto está en el carrito
     */
    public function isInCart($product_id) {
        return isset($_SESSION['cart'][$product_id]) && $_SESSION['cart'][$product_id] > 0;
    }

    /**
     * Obtener cantidad de un producto en el carrito
     */
    public function getProductQuantity($product_id) {
        return $_SESSION['cart'][$product_id] ?? 0;
    }

    /**
     * Obtener número total de items en el carrito
     */
    public function getCartCount() {
        return array_sum($_SESSION['cart']);
    }

    /**
     * Obtener total del carrito con precios de BD
     */
    public function getCartTotal() {
        if (empty($_SESSION['cart'])) {
            return 0;
        }

        try {
            $product_ids = array_keys($_SESSION['cart']);
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            
            $stmt = $this->pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $total = 0;
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                if (isset($products[$product_id])) {
                    $total += $products[$product_id] * $quantity;
                }
            }

            return $total;
        } catch (Exception $e) {
            error_log("Error calculando total del carrito: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener información completa del carrito
     */
    public function getCartInfo() {
        return [
            'success' => true,
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'cart' => $_SESSION['cart']
        ];
    }

    /**
     * Validar que un producto existe en la base de datos
     */
    private function validateProduct($product_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            return $stmt->fetchColumn() !== false;
        } catch (Exception $e) {
            error_log("Error validando producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Migrar carrito cuando un usuario se loguea
     */
    public function migrateGuestCart($new_user_id) {
        try {
            if ($this->user_id || empty($_SESSION['cart'])) {
                return true; // Ya tiene usuario o carrito vacío
            }

            // Obtener carrito del usuario logueado
            $stmt = $this->pdo->prepare("SELECT product_id, quantity FROM cart_sessions WHERE user_id = ?");
            $stmt->execute([$new_user_id]);
            $userCart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Combinar carritos
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                if (isset($userCart[$product_id])) {
                    $_SESSION['cart'][$product_id] = min($userCart[$product_id] + $quantity, 10);
                }
            }

            // Limpiar carrito de invitado
            $stmt = $this->pdo->prepare("DELETE FROM cart_sessions WHERE id = ? AND user_id IS NULL");
            $stmt->execute([$this->session_id]);

            // Actualizar user_id y sincronizar
            $this->user_id = $new_user_id;
            $_SESSION['user_id'] = $new_user_id;
            $this->syncCartToDatabase();

            return true;
        } catch (Exception $e) {
            error_log("Error migrando carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información completa del producto incluyendo stock
     */
    public function getProductInfo($product_id) {
        try {
            error_log("CartManager::getProductInfo - Buscando producto ID: $product_id");
            
            // Consulta ULTRA SIMPLE solo con campos que SIEMPRE existen
            $stmt = $this->pdo->prepare("
                SELECT id, name, price, stock_quantity as stock
                FROM products 
                WHERE id = ?
            ");
            $stmt->execute([$product_id]);
            $result = $stmt->fetch();
            
            if ($result) {
                error_log("CartManager::getProductInfo - ✓ Producto ENCONTRADO: {$result['name']}");
                error_log("CartManager::getProductInfo - Stock: {$result['stock']}");
                
                // Agregar campos opcionales con valores por defecto
                $result['sale_price'] = null;
                $result['is_active'] = 1;
                $result['low_stock_threshold'] = 5;
                $result['is_featured'] = 0;
                $result['is_new'] = 0;
                
                // Intentar obtener campos opcionales si existen
                try {
                    $stmt2 = $this->pdo->prepare("
                        SELECT sale_price, is_active, low_stock_threshold, is_featured, is_new
                        FROM products 
                        WHERE id = ?
                    ");
                    $stmt2->execute([$product_id]);
                    $optional = $stmt2->fetch();
                    
                    if ($optional) {
                        $result['sale_price'] = $optional['sale_price'] ?? null;
                        $result['is_active'] = $optional['is_active'] ?? 1;
                        $result['low_stock_threshold'] = $optional['low_stock_threshold'] ?? 5;
                        $result['is_featured'] = $optional['is_featured'] ?? 0;
                        $result['is_new'] = $optional['is_new'] ?? 0;
                    }
                } catch (Exception $e_opt) {
                    error_log("CartManager::getProductInfo - Campos opcionales no disponibles (OK)");
                }
                
                return $result;
            } else {
                error_log("CartManager::getProductInfo - ✗ Producto NO ENCONTRADO con ID: $product_id");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CartManager::getProductInfo - ERROR: " . $e->getMessage());
            error_log("CartManager::getProductInfo - Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Verificar disponibilidad de stock para una cantidad específica
     */
    public function checkStockAvailability($product_id, $requested_quantity) {
        try {
            $productInfo = $this->getProductInfo($product_id);
            if (!$productInfo) {
                return [
                    'available' => false,
                    'message' => 'Producto no encontrado',
                    'stock' => 0
                ];
            }

            $currentInCart = $this->getProductQuantity($product_id);
            $totalRequested = $currentInCart + $requested_quantity;

            if ($totalRequested > $productInfo['stock']) {
                $availableToAdd = max(0, $productInfo['stock'] - $currentInCart);
                return [
                    'available' => false,
                    'message' => $availableToAdd > 0 
                        ? "Solo puedes agregar {$availableToAdd} unidad(es) más" 
                        : 'Sin stock disponible',
                    'stock' => $productInfo['stock'],
                    'in_cart' => $currentInCart,
                    'available_to_add' => $availableToAdd
                ];
            }

            return [
                'available' => true,
                'message' => 'Stock disponible',
                'stock' => $productInfo['stock'],
                'in_cart' => $currentInCart,
                'available_to_add' => $productInfo['stock'] - $currentInCart
            ];
        } catch (Exception $e) {
            error_log("Error verificando stock: " . $e->getMessage());
            return [
                'available' => false,
                'message' => 'Error verificando stock',
                'stock' => 0
            ];
        }
    }

    /**
     * Registrar movimiento de stock (para auditoría)
     */
    public function logStockMovement($product_id, $movement_type, $quantity, $reason = null) {
        try {
            // Obtener stock actual
            $stmt = $this->pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $currentStock = $stmt->fetchColumn();

            if ($currentStock === false) {
                return false;
            }

            // Calcular nuevo stock
            $newStock = $currentStock;
            if ($movement_type === 'out') {
                $newStock = $currentStock - $quantity;
            } elseif ($movement_type === 'in') {
                $newStock = $currentStock + $quantity;
            }

            // Registrar movimiento
            $stmt = $this->pdo->prepare("
                INSERT INTO stock_movements 
                (product_id, movement_type, quantity, previous_stock, new_stock, reason, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $product_id,
                $movement_type,
                $quantity,
                $currentStock,
                $newStock,
                $reason,
                $this->user_id
            ]);
        } catch (Exception $e) {
            error_log("Error registrando movimiento de stock: " . $e->getMessage());
            return false;
        }
    }
}
?>