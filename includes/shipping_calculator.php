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
        
        // 📍 Punto de origen: Fitz Roy 1906, CABA
        $this->originZip = 'C1414';
        $this->originAddress = 'Fitz Roy 1906, C1414 Cdad. Autónoma de Buenos Aires, Argentina';
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
            // Motomensajería solo si está dentro de 20 km radio
            $distance = $this->estimateDistanceByZone($destinationZip);
            return $distance !== null && $distance <= 20;
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
     * Sistema basado en tarifas por zonas y peso
     */
    private function calculateCorreoArgentino($provider, $destinationZip, $weightKg, $declaredValue) {
        $config = json_decode($provider['config_json'], true);
        
        // Determinar zona según CP destino
        $zone = $this->getShippingZone($destinationZip);
        
        // Tarifas Correo Argentino 2026 (TEMPORALES - Hasta integrar API real)
        // TODO: Integrar API de Correo Argentino para cotización en tiempo real
        $tarifas = [
            'caba' => [
                'base' => 8200,
                'por_kg' => 1500,
                'dias' => 3
            ],
            'gba' => [
                'base' => 9500,
                'por_kg' => 1800,
                'dias' => 4
            ],
            'bs_as' => [
                'base' => 12000,
                'por_kg' => 2200,
                'dias' => 5
            ],
            'centro' => [
                'base' => 14500,
                'por_kg' => 2800,
                'dias' => 6
            ],
            'norte' => [
                'base' => 18500,
                'por_kg' => 3500,
                'dias' => 8
            ],
            'patagonia' => [
                'base' => 21500,
                'por_kg' => 4200,
                'dias' => 10
            ]
        ];
        
        $tarifa = $tarifas[$zone] ?? $tarifas['centro'];
        
        // Calcular precio total
        $precioBase = $tarifa['base'];
        $precioExtra = ($weightKg > 1) ? (($weightKg - 1) * $tarifa['por_kg']) : 0;
        $total = $precioBase + $precioExtra;
        
        // Servicio expreso (+50% pero menos días)
        $quotes = [];
        
        // 1. Clásico (económico)
        $quotes[] = [
            'provider' => 'correo_argentino',
            'service_name' => 'Correo Argentino Clásico',
            'price' => $total,
            'delivery_days' => $tarifa['dias'],
            'description' => 'Envío estándar - ' . $this->getZoneName($zone),
            'estimated' => true
        ];
        
        // 2. Expreso (más rápido, más caro)
        $quotes[] = [
            'provider' => 'correo_argentino',
            'service_name' => 'Correo Argentino Expreso',
            'price' => round($total * 1.5),
            'delivery_days' => max(2, $tarifa['dias'] - 2),
            'description' => 'Envío prioritario - ' . $this->getZoneName($zone),
            'estimated' => true
        ];
        
        // Retornar el clásico por defecto
        return $quotes[0];
    }
    
    /**
     * Determinar zona de envío según CP
     */
    private function getShippingZone($zipCode) {
        // Limpiar CP (puede venir con letras como C1426 o S2000)
        $zipClean = preg_replace('/[^0-9]/', '', $zipCode);
        $zip = intval($zipClean);
        
        // Zonas de Argentina por rangos de CP
        if ($zip >= 1000 && $zip <= 1439) return 'caba';          // Capital Federal
        if ($zip >= 1600 && $zip <= 1900) return 'gba';           // Gran Buenos Aires
        if ($zip >= 2000 && $zip <= 2999) return 'bs_as';         // Prov. Buenos Aires
        if ($zip >= 3000 && $zip <= 3999) return 'centro';        // Santa Fe, Entre Ríos, Corrientes
        if ($zip >= 4000 && $zip <= 4999) return 'norte';         // Salta, Jujuy, Tucumán
        if ($zip >= 5000 && $zip <= 5999) return 'centro';        // Córdoba, La Rioja
        if ($zip >= 6000 && $zip <= 6999) return 'centro';        // Mendoza, San Juan
        if ($zip >= 8000 && $zip <= 8999) return 'patagonia';     // Neuquén, Río Negro
        if ($zip >= 9000 && $zip <= 9999) return 'patagonia';     // Chubut, Santa Cruz
        
        return 'centro'; // Default
    }
    
    /**
     * Nombre legible de la zona
     */
    private function getZoneName($zone) {
        $names = [
            'caba' => 'Capital Federal',
            'gba' => 'Gran Buenos Aires',
            'bs_as' => 'Prov. Buenos Aires',
            'centro' => 'Zona Centro',
            'norte' => 'Zona Norte',
            'patagonia' => 'Patagonia'
        ];
        
        return $names[$zone] ?? 'Zona desconocida';
    }
    
    /**
     * Calcular envío con Motomensajería (solo dentro de 20 km)
     * Precio: $1.000 por kilómetro
     */
    private function calculateMoova($provider, $destinationZip, $weightKg, $declaredValue) {
        // Estimar distancia desde Fitz Roy 1906, C1414 CABA
        $distance = $this->estimateDistanceByZone($destinationZip);
        
        // Solo disponible si está dentro de 20 km
        if ($distance === null || $distance > 20) {
            return null;
        }
        
        // Precio: $1.000 por kilómetro
        $pricePerKm = 1000;
        $total = round($distance * $pricePerKm);
        
        // Mínimo $5.000 (5 km mínimo)
        $total = max($total, 5000);
        
        return [
            'provider' => 'moova',
            'service_name' => 'Motomensajería',
            'price' => $total,
            'delivery_days' => 1,
            'description' => "Envío rápido en moto - " . round($distance, 1) . " km - Mismo día",
            'estimated' => false,
            'distance_km' => round($distance, 1),
            'supports_cash_on_delivery' => true // Pago contraentrega disponible
        ];
    }
    
    /**
     * Estimar distancia en kilómetros desde origen (Fitz Roy 1906, C1414) hasta destino
     * Basado en zonas de código postal
     * TEMPORAL: Hasta implementar Google Maps Distance Matrix API
     */
    private function estimateDistanceByZone($destinationZip) {
        $zipClean = preg_replace('/[^0-9]/', '', $destinationZip);
        $zip = intval($zipClean);
        
        // Distancias aproximadas desde Fitz Roy 1906, Palermo (C1414)
        // CABA - Zonas cercanas
        if ($zip >= 1400 && $zip <= 1430) return 2;   // Palermo mismo (2 km)
        if ($zip >= 1000 && $zip <= 1100) return 6;   // Retiro, San Nicolás, Montserrat (6 km)
        if ($zip >= 1100 && $zip <= 1200) return 5;   // Recoleta, Balvanera (5 km)
        if ($zip >= 1200 && $zip <= 1300) return 7;   // Almagro, Caballito (7 km)
        if ($zip >= 1300 && $zip <= 1400) return 8;   // Villa Crespo, Flores (8 km)
        
        // CABA - Zonas lejanas
        if ($zip >= 1431 && $zip <= 1439) return 12;  // Belgrano, Núñez (12 km)
        if ($zip >= 1001 && $zip <= 1050) return 15;  // La Boca, Barracas (15 km)
        
        // GBA Norte (dentro de 20 km)
        if ($zip >= 1600 && $zip <= 1629) return 18;  // Vicente López, Olivos (18 km)
        if ($zip >= 1630 && $zip <= 1659) return 20;  // San Isidro (20 km - límite)
        
        // GBA Sur (mayormente fuera de 20 km)
        if ($zip >= 1824 && $zip <= 1832) return 16;  // Lanús Oeste (16 km)
        if ($zip >= 1870 && $zip <= 1875) return 19;  // Avellaneda Centro (19 km)
        
        // Resto de GBA: fuera de rango
        if ($zip >= 1660 && $zip <= 1900) return 25;  // GBA general (25+ km)
        
        // Fuera de CABA/GBA: null (no disponible moto)
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
