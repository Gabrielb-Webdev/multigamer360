<?php
/**
 * =====================================================
 * SHIPPING CALCULATOR - ARGENTINA
 * Calcula costos de envío dinámicos con múltiples transportistas
 * =====================================================
 */

class ShippingCalculator {
    
    private $pdo;
    private $originZip;
    private $originAddress;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Obtener CP de origen desde configuración (tu local)
        $this->originZip = '1426'; // ACTUALIZAR con tu CP real
        $this->originAddress = 'Av. Corrientes 1234, CABA'; // ACTUALIZAR
    }
    
    /**
     * Calcular todas las opciones de envío disponibles
     */
    public function calculateShipping($destinationZip, $weightKg = 1.0, $declaredValue = 10000) {
        $quotes = [];
        
        // Obtener proveedores activos
        $stmt = $this->pdo->prepare("
            SELECT * FROM shipping_providers 
            WHERE is_active = 1 
            ORDER BY provider_key
        ");
        $stmt->execute();
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($providers as $provider) {
            // Verificar cobertura
            if (!$this->checkCoverage($provider, $destinationZip)) {
                continue;
            }
            
            // Intentar obtener de cache primero
            $cached = $this->getCachedQuote($provider['provider_key'], $destinationZip, $weightKg);
            if ($cached) {
                $quotes[] = $cached;
                continue;
            }
            
            // Calcular según proveedor
            $quote = null;
            switch ($provider['provider_key']) {
                case 'andreani':
                    $quote = $this->calculateAndreani($provider, $destinationZip, $weightKg, $declaredValue);
                    break;
                case 'oca':
                    $quote = $this->calculateOCA($provider, $destinationZip, $weightKg, $declaredValue);
                    break;
                case 'correo_argentino':
                    $quote = $this->calculateCorreoArgentino($provider, $destinationZip, $weightKg, $declaredValue);
                    break;
                case 'moova':
                    $quote = $this->calculateMoova($provider, $destinationZip, $weightKg, $declaredValue);
                    break;
            }
            
            if ($quote) {
                // Guardar en cache
                $this->cacheQuote($quote, $destinationZip, $weightKg, $declaredValue);
                $quotes[] = $quote;
            }
        }
        
        // Ordenar por precio
        usort($quotes, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        return $quotes;
    }
    
    /**
     * Verificar si el proveedor cubre el código postal destino
     */
    private function checkCoverage($provider, $destinationZip) {
        $coverage = $provider['coverage_type'];
        
        if ($coverage === 'nacional') {
            return true;
        }
        
        if ($coverage === 'caba_gba') {
            // CPs de CABA: 1000-1439
            // CPs de GBA: 1600-1900 (aprox)
            $zip = intval($destinationZip);
            return ($zip >= 1000 && $zip <= 1439) || ($zip >= 1600 && $zip <= 1900);
        }
        
        return false;
    }
    
    /**
     * Calcular envío con Andreani
     */
    private function calculateAndreani($provider, $destinationZip, $weightKg, $declaredValue) {
        $config = json_decode($provider['config_json'], true);
        
        // API de Andreani requiere autenticación con x-authorization-token
        $apiUrl = $provider['api_url'] . '/tarifas';
        
        $payload = [
            'cpOrigen' => $this->originZip,
            'cpDestino' => $destinationZip,
            'peso' => $weightKg,
            'valorDeclarado' => $declaredValue,
            'contrato' => $config['contrato'] ?? ''
        ];
        
        try {
            $response = $this->makeRequest($apiUrl, $payload, [
                'x-authorization-token: ' . ($provider['api_key'] ?? '')
            ]);
            
            if ($response && isset($response['tarifaConIva'])) {
                return [
                    'provider' => 'andreani',
                    'service_name' => 'Andreani Estándar',
                    'price' => floatval($response['tarifaConIva']),
                    'delivery_days' => $response['plazoEntrega'] ?? 3,
                    'description' => 'Envío a domicilio',
                    'raw' => $response
                ];
            }
        } catch (Exception $e) {
            error_log("Error Andreani: " . $e->getMessage());
        }
        
        // Fallback: calcular estimado
        return $this->estimateAndreani($destinationZip, $weightKg);
    }
    
    /**
     * Calcular envío con OCA
     */
    private function calculateOCA($provider, $destinationZip, $weightKg, $declaredValue) {
        $config = json_decode($provider['config_json'], true);
        
        // OCA usa SOAP, pero podemos usar estimaciones o su API REST si la tienen
        // Por ahora implemento estimación basada en zonas
        
        $basePrice = 5000;
        $pricePerKg = 1000;
        
        // Ajustar según distancia (estimado por CP)
        $distance = $this->estimateDistance($this->originZip, $destinationZip);
        
        if ($distance > 500) {
            $basePrice += 2000;
        } elseif ($distance > 200) {
            $basePrice += 1000;
        }
        
        $total = $basePrice + ($weightKg * $pricePerKg);
        
        return [
            'provider' => 'oca',
            'service_name' => 'OCA E-Pack',
            'price' => $total,
            'delivery_days' => $distance > 300 ? 5 : 3,
            'description' => 'Envío a domicilio',
            'estimated' => true
        ];
    }
    
    /**
     * Calcular envío con Correo Argentino
     */
    private function calculateCorreoArgentino($provider, $destinationZip, $weightKg, $declaredValue) {
        // Correo Argentino tiene tarifas más económicas pero menos confiables
        
        $distance = $this->estimateDistance($this->originZip, $destinationZip);
        
        $basePrice = 3500;
        $pricePerKg = 800;
        
        if ($distance > 500) {
            $basePrice += 1500;
        } elseif ($distance > 200) {
            $basePrice += 800;
        }
        
        $total = $basePrice + ($weightKg * $pricePerKg);
        
        return [
            'provider' => 'correo_argentino',
            'service_name' => 'Correo Argentino Clásico',
            'price' => $total,
            'delivery_days' => $distance > 300 ? 7 : 4,
            'description' => 'Envío estándar a domicilio',
            'estimated' => true
        ];
    }
    
    /**
     * Calcular envío con Moova (solo CABA/GBA)
     */
    private function calculateMoova($provider, $destinationZip, $weightKg, $declaredValue) {
        // Moova solo opera en CABA y GBA
        $zip = intval($destinationZip);
        
        if (($zip >= 1000 && $zip <= 1439) || ($zip >= 1600 && $zip <= 1900)) {
            // Precio fijo para zona metropolitana
            return [
                'provider' => 'moova',
                'service_name' => 'Moova Mensajería',
                'price' => 3500,
                'delivery_days' => 1,
                'description' => 'Entrega en moto - Mismo día',
                'estimated' => false
            ];
        }
        
        return null;
    }
    
    /**
     * Estimación de Andreani como fallback
     */
    private function estimateAndreani($destinationZip, $weightKg) {
        $distance = $this->estimateDistance($this->originZip, $destinationZip);
        
        $basePrice = 6000;
        $pricePerKg = 1200;
        
        if ($distance > 500) {
            $basePrice += 3000;
        } elseif ($distance > 200) {
            $basePrice += 1500;
        }
        
        $total = $basePrice + ($weightKg * $pricePerKg);
        
        return [
            'provider' => 'andreani',
            'service_name' => 'Andreani Estándar',
            'price' => $total,
            'delivery_days' => $distance > 300 ? 4 : 2,
            'description' => 'Envío a domicilio',
            'estimated' => true
        ];
    }
    
    /**
     * Estimar distancia basada en códigos postales (simplificado)
     */
    private function estimateDistance($zipOrigin, $zipDestination) {
        // Tabla simplificada de distancias por rango de CP
        $zones = [
            ['min' => 1000, 'max' => 1499, 'distance' => 0],    // CABA
            ['min' => 1600, 'max' => 1900, 'distance' => 30],   // GBA
            ['min' => 2000, 'max' => 2999, 'distance' => 180],  // Buenos Aires Interior
            ['min' => 3000, 'max' => 3999, 'distance' => 350],  // Santa Fe, Entre Ríos
            ['min' => 4000, 'max' => 4999, 'distance' => 600],  // Norte (Salta, Jujuy)
            ['min' => 5000, 'max' => 5999, 'distance' => 400],  // Córdoba, La Rioja
            ['min' => 8000, 'max' => 9999, 'distance' => 1000], // Patagonia
        ];
        
        $destZip = intval($zipDestination);
        
        foreach ($zones as $zone) {
            if ($destZip >= $zone['min'] && $destZip <= $zone['max']) {
                return $zone['distance'];
            }
        }
        
        return 500; // Default
    }
    
    /**
     * Hacer request HTTP
     */
    private function makeRequest($url, $payload, $headers = []) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json'
        ], $headers));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Obtener cotización del cache
     */
    private function getCachedQuote($provider, $destinationZip, $weightKg) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM shipping_quotes_cache
            WHERE provider_key = ?
            AND destination_zip = ?
            AND weight_kg = ?
            AND valid_until > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$provider, $destinationZip, $weightKg]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            return [
                'provider' => $cached['provider_key'],
                'service_name' => $cached['service_type'],
                'price' => floatval($cached['price']),
                'delivery_days' => intval($cached['delivery_days']),
                'cached' => true
            ];
        }
        
        return null;
    }
    
    /**
     * Guardar cotización en cache (válido por 24hs)
     */
    private function cacheQuote($quote, $destinationZip, $weightKg, $declaredValue) {
        $stmt = $this->pdo->prepare("
            INSERT INTO shipping_quotes_cache 
            (provider_key, origin_zip, destination_zip, weight_kg, declared_value, 
             service_type, price, delivery_days, valid_until, raw_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), ?)
        ");
        
        $stmt->execute([
            $quote['provider'],
            $this->originZip,
            $destinationZip,
            $weightKg,
            $declaredValue,
            $quote['service_name'],
            $quote['price'],
            $quote['delivery_days'],
            json_encode($quote['raw'] ?? [])
        ]);
    }
    
    /**
     * Formatear precio argentino
     */
    public function formatPrice($price) {
        return '$' . number_format($price, 0, ',', '.');
    }
}
?>
