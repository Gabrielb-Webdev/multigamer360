<?php
// Capturar cualquier salida no deseada
ob_start();

// Manejo de errores para no romper el JSON
error_reporting(0);
ini_set('display_errors', 0);

// Intentar cargar archivos de configuración
try {
    if (file_exists('../../config/database.php')) {
        require_once '../../config/database.php';
    }
    if (file_exists('../functions.php')) {
        require_once '../functions.php';
    }
} catch (Exception $e) {
    // Silenciar errores de include
}

// Limpiar cualquier salida previa
ob_end_clean();

// Establecer header JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    $productName = isset($_GET['product_name']) ? trim($_GET['product_name']) : '';
    $productType = isset($_GET['product_type']) ? trim($_GET['product_type']) : 'console';

    if (empty($productName)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nombre de producto requerido'
        ]);
        exit;
    }

    // Función para obtener información de Wikipedia en español (SIN LÍMITES)
    function getWikipediaInfo($searchTerm) {
        // Limpiar el término de búsqueda
        $searchTerm = trim($searchTerm);
        
        // Intentar diferentes variaciones del término
        $searchVariations = [
            $searchTerm,
            $searchTerm . ' consola',
            $searchTerm . ' videojuegos',
            str_replace(' Consola', '', $searchTerm),
            str_replace(' Console', '', $searchTerm)
        ];
        
        foreach ($searchVariations as $variation) {
            $encodedTerm = urlencode($variation);
            
            // Primero buscar el artículo
            $searchUrl = "https://es.wikipedia.org/w/api.php?action=opensearch&search={$encodedTerm}&limit=1&format=json";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $searchUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $searchResponse = curl_exec($ch);
            
            if (!$searchResponse) continue;
            
            $searchData = json_decode($searchResponse, true);
            if (empty($searchData[1][0])) continue;
            
            $pageTitle = $searchData[1][0];
            
            // Obtener contenido completo en español
            $contentUrl = "https://es.wikipedia.org/api/rest_v1/page/summary/" . urlencode($pageTitle);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $contentUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data['extract'])) {
                    return $data;
                }
            }
        }
        
        return null;
    }
    
    // Función para buscar información en Wikidata (base de datos estructurada)
    function getWikidataInfo($searchTerm) {
        try {
            // Primero buscar el ID de Wikidata
            $searchUrl = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search=" . 
                         urlencode($searchTerm) . "&language=es&format=json&limit=1";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $searchUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['search'][0])) {
                    $item = $data['search'][0];
                    return [
                        'id' => $item['id'] ?? null,
                        'label' => $item['label'] ?? null,
                        'description' => $item['description'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error en Wikidata: " . $e->getMessage());
        }
        
        return null;
    }
    
    // Función para buscar especificaciones técnicas de consolas
    function getTechnicalSpecs($productName, $productType) {
        $specs = [];
        
        // Base de datos de especificaciones comunes
        $specsDatabase = [
            'Nintendo 64' => [
                'fabricante' => 'Nintendo',
                'año_lanzamiento' => '1996',
                'procesador' => 'NEC VR4300 64-bit RISC a 93.75 MHz',
                'memoria' => '4 MB RDRAM (expandible a 8 MB)',
                'resolucion' => 'Hasta 640x480',
                'puertos' => '4 puertos de control',
                'soporte' => 'Cartuchos de 4-64 MB'
            ],
            'PlayStation 2' => [
                'fabricante' => 'Sony',
                'año_lanzamiento' => '2000',
                'procesador' => 'Emotion Engine 128-bit a 294 MHz',
                'memoria' => '32 MB RDRAM',
                'resolucion' => 'Hasta 1280x1024',
                'medios' => 'DVD, CD, PS1',
                'puertos' => '2 puertos USB, lector de memorias'
            ],
            'Xbox 360' => [
                'fabricante' => 'Microsoft',
                'año_lanzamiento' => '2005',
                'procesador' => 'IBM Xenon 3-core a 3.2 GHz',
                'gpu' => 'ATI Xenos 500 MHz',
                'memoria' => '512 MB GDDR3',
                'resolucion' => 'Hasta 1080p',
                'almacenamiento' => 'Disco duro 20-500 GB'
            ],
            'PlayStation 5' => [
                'fabricante' => 'Sony',
                'año_lanzamiento' => '2020',
                'procesador' => 'AMD Zen 2 8-core a 3.5 GHz',
                'gpu' => 'AMD RDNA 2 con 10.28 TFLOPS',
                'memoria' => '16 GB GDDR6',
                'almacenamiento' => 'SSD 825 GB NVMe',
                'resolucion' => '4K hasta 120fps, 8K compatible',
                'caracteristicas' => 'Ray tracing, audio 3D Tempest'
            ],
            'Nintendo Switch' => [
                'fabricante' => 'Nintendo',
                'año_lanzamiento' => '2017',
                'procesador' => 'NVIDIA Custom Tegra',
                'memoria' => '4 GB LPDDR4',
                'pantalla' => '6.2" táctil capacitiva 720p',
                'almacenamiento' => '32 GB (expandible con microSD)',
                'bateria' => '4-9 horas de autonomía',
                'modos' => 'TV, sobremesa, portátil'
            ],
            'Xbox Series X' => [
                'fabricante' => 'Microsoft',
                'año_lanzamiento' => '2020',
                'procesador' => 'AMD Zen 2 8-core a 3.8 GHz',
                'gpu' => 'AMD RDNA 2 con 12 TFLOPS',
                'memoria' => '16 GB GDDR6',
                'almacenamiento' => 'SSD 1 TB NVMe',
                'resolucion' => '4K nativo hasta 120fps',
                'caracteristicas' => 'Ray tracing, Quick Resume, VRR'
            ]
        ];
        
        // Buscar especificaciones
        $cleanName = str_replace([' Consola', ' Console'], '', $productName);
        foreach ($specsDatabase as $console => $consoleSpecs) {
            if (stripos($cleanName, $console) !== false || stripos($console, $cleanName) !== false) {
                return $consoleSpecs;
            }
        }
        
        return $specs;
    }
    
    // Función para generar descripción mejorada con especificaciones
    function generateEnhancedDescription($productName, $productType, $wikiExtract, $specs) {
        $cleanName = str_replace([' Consola', ' Console'], '', $productName);
        
        // Si tenemos extracto de Wikipedia, usarlo como base
        if ($wikiExtract) {
            $desc = strip_tags($wikiExtract);
            $desc = preg_replace('/\s+/', ' ', $desc);
            $desc = trim($desc);
            
            // Limitar a 600 caracteres
            if (strlen($desc) > 600) {
                $lastPeriod = strrpos(substr($desc, 0, 600), '.');
                if ($lastPeriod !== false) {
                    $desc = substr($desc, 0, $lastPeriod + 1);
                } else {
                    $desc = substr($desc, 0, 597) . '...';
                }
            }
            
            // Agregar especificaciones técnicas si están disponibles
            if (!empty($specs)) {
                $techInfo = [];
                if (isset($specs['fabricante'])) $techInfo[] = "Fabricante: {$specs['fabricante']}";
                if (isset($specs['año_lanzamiento'])) $techInfo[] = "Año: {$specs['año_lanzamiento']}";
                if (isset($specs['procesador'])) $techInfo[] = "CPU: {$specs['procesador']}";
                if (isset($specs['memoria'])) $techInfo[] = "RAM: {$specs['memoria']}";
                
                if (!empty($techInfo)) {
                    $desc .= " || ESPECIFICACIONES: " . implode(' | ', array_slice($techInfo, 0, 4));
                }
            }
            
            $desc .= " || Disponible en MultiGamer360 con garantía y envíos a todo Colombia.";
            return $desc;
        }
        
        // Si no hay Wikipedia, usar descripciones personalizadas mejoradas
        return generateDescriptionFromSpecs($productName, $productType, $specs);
    }
    
    // Generar descripción desde especificaciones
    function generateDescriptionFromSpecs($productName, $productType, $specs) {
        $cleanName = str_replace([' Consola', ' Console'], '', $productName);
        
        if ($productType === 'console' && !empty($specs)) {
            $parts = [];
            $parts[] = "{$cleanName} es una consola de videojuegos";
            
            if (isset($specs['fabricante'])) {
                $parts[] = "desarrollada por {$specs['fabricante']}";
            }
            
            if (isset($specs['año_lanzamiento'])) {
                $parts[] = "lanzada en {$specs['año_lanzamiento']}";
            }
            
            $desc = implode(' ', $parts) . '.';
            
            // Agregar características técnicas
            $features = [];
            if (isset($specs['procesador'])) $features[] = "Equipada con {$specs['procesador']}";
            if (isset($specs['memoria'])) $features[] = "{$specs['memoria']} de memoria";
            if (isset($specs['resolucion'])) $features[] = "soporta {$specs['resolucion']}";
            if (isset($specs['caracteristicas'])) $features[] = $specs['caracteristicas'];
            
            if (!empty($features)) {
                $desc .= ' ' . implode(', ', $features) . '.';
            }
            
            // Agregar beneficios de almacenamiento/conectividad
            if (isset($specs['almacenamiento'])) {
                $desc .= " Cuenta con {$specs['almacenamiento']} de almacenamiento.";
            }
            if (isset($specs['puertos'])) {
                $desc .= " Incluye {$specs['puertos']}.";
            }
            
            $desc .= " || Encuéntrala en MultiGamer360, tu tienda especializada en consolas retro y modernas con garantía de calidad.";
            
            return $desc;
        }
        
        // Descripción mejorada para accesorios
        if ($productType === 'accessory') {
            // Detectar tipo de accesorio
            $accessoryType = 'accesorio';
            $features = [];
            
            if (stripos($productName, 'control') !== false || stripos($productName, 'controller') !== false) {
                $accessoryType = 'control';
                $features[] = 'diseño ergonómico para largas sesiones de juego';
                $features[] = 'respuesta precisa de botones';
                $features[] = 'compatible con juegos clásicos y retro';
            } elseif (stripos($productName, 'cable') !== false) {
                $accessoryType = 'cable';
                $features[] = 'conexión estable y confiable';
                $features[] = 'fabricado con materiales de alta calidad';
                $features[] = 'diseño duradero';
            } elseif (stripos($productName, 'memory') !== false || stripos($productName, 'memoria') !== false) {
                $accessoryType = 'tarjeta de memoria';
                $features[] = 'almacenamiento confiable para tus partidas guardadas';
                $features[] = 'compatibilidad garantizada';
                $features[] = 'fácil de usar';
            } elseif (stripos($productName, 'pack') !== false || stripos($productName, 'pak') !== false) {
                $accessoryType = 'accesorio';
                $features[] = 'mejora tu experiencia de juego';
                $features[] = 'fácil instalación';
                $features[] = 'compatible con tu consola';
            }
            
            $desc = "{$productName} es un {$accessoryType} gaming de alta calidad";
            
            if (!empty($features)) {
                $desc .= ' que ofrece ' . implode(', ', $features);
            }
            
            $desc .= ". Ideal para coleccionistas y gamers que buscan equipamiento confiable. | Disponible en MultiGamer360 con garantía.";
            
            return $desc;
        }
        
        // Descripción por defecto para otros casos
        return "{$productName} es un accesorio gaming de alta calidad diseñado para mejorar tu experiencia de juego. Fabricado con materiales duraderos y optimizado para máximo rendimiento. | Disponible en MultiGamer360.";
    }
    
    // Función para buscar imágenes de consolas en Google Images (sin API key)
    function searchConsoleImages($productName, $productType) {
        $images = [];
        
        // Limpiar nombre
        $cleanName = str_replace([' Consola', ' Console', ' consola', ' console'], '', $productName);
        
        // Palabras a EXCLUIR (accesorios, controllers, etc.)
        $excludeWords = [];
        
        if ($productType === 'console') {
            // Para CONSOLAS: buscar específicamente la consola, NO accesorios
            $queries = [
                $cleanName . ' console -controller -pad -modem -rumble -jumper -memory -transfer',
                $cleanName . ' videogame console system',
                $cleanName . ' console original box',
                $cleanName . ' gaming system',
                $productName . ' system'
            ];
            $excludeWords = ['controller', 'pad', 'modem', 'rumble', 'pak', 'jumper', 'memory', 'cable', 'adapter', 'accessory'];
        } else {
            // Para ACCESORIOS: buscar el accesorio ESPECÍFICO sin la consola
            $accessoryOnlyName = $productName;
            
            // Detectar y extraer nombres de consolas comunes
            $consoles = ['Nintendo 64', 'N64', 'PlayStation', 'PS1', 'PS2', 'PS3', 'PS4', 'PS5', 
                        'Xbox', 'Xbox 360', 'Xbox One', 'Series X', 'Switch', 'GameCube', 'Wii'];
            
            $detectedConsole = '';
            foreach ($consoles as $console) {
                if (stripos($productName, $console) !== false) {
                    $detectedConsole = $console;
                    // Remover el nombre de la consola para buscar solo el accesorio
                    $accessoryOnlyName = str_replace($console, '', $productName);
                    $accessoryOnlyName = trim($accessoryOnlyName);
                    break;
                }
            }
            
            // Detectar tipo de accesorio
            $accessoryType = '';
            if (stripos($accessoryOnlyName, 'control') !== false || stripos($accessoryOnlyName, 'controller') !== false) {
                $accessoryType = 'controller';
            } elseif (stripos($accessoryOnlyName, 'cable') !== false) {
                $accessoryType = 'cable';
            } elseif (stripos($accessoryOnlyName, 'memory') !== false || stripos($accessoryOnlyName, 'memoria') !== false) {
                $accessoryType = 'memory card';
            } elseif (stripos($accessoryOnlyName, 'pack') !== false || stripos($accessoryOnlyName, 'pak') !== false) {
                $accessoryType = 'pak';
            }
            
            // Queries MUY específicas para accesorios (sin nombre de consola en la mayoría)
            $queries = [];
            
            // Si detectamos el tipo, buscar genéricamente primero
            if ($accessoryType && $detectedConsole) {
                $queries[] = $detectedConsole . ' ' . $accessoryType;  // "Nintendo 64 controller"
                $queries[] = $accessoryType . ' ' . $detectedConsole;  // "controller Nintendo 64"
            }
            
            // Buscar solo el tipo de accesorio sin marca
            if ($accessoryType) {
                $queries[] = $accessoryType . ' gaming';  // "controller gaming"
                $queries[] = $accessoryType . ' gamepad';  // "controller gamepad"
            }
            
            // Fallback con nombre completo pero marcando que es accesorio
            $queries[] = $productName . ' accessory';
            
            // EXCLUIR imágenes de la consola misma
            $excludeWords = ['console', 'system', 'box', 'packaging'];
            
            // Si detectamos consola específica, también excluir búsquedas que solo mencionen la consola
            if ($detectedConsole) {
                // Esto ayudará a filtrar imágenes que son solo de la consola
                $excludeWords[] = 'front';
                $excludeWords[] = 'unboxing';
            }
        }
        
        // Usar Wikimedia Commons para buscar imágenes
        foreach ($queries as $query) {
            $wikiImages = searchWikimediaImages($query, $excludeWords);
            if (!empty($wikiImages)) {
                $images = array_merge($images, $wikiImages);
                if (count($images) >= 3) break;
            }
        }
        
        return array_unique(array_slice($images, 0, 5));
    }
    
    // Función para buscar en Wikimedia Commons
    function searchWikimediaImages($searchTerm, $excludeWords = []) {
        $images = [];
        
        $apiUrl = "https://commons.wikimedia.org/w/api.php?action=query&list=search&srsearch=" . 
                  urlencode($searchTerm) . "&srnamespace=6&format=json&srlimit=10";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['query']['search'])) {
                foreach ($data['query']['search'] as $result) {
                    $imageTitle = $result['title'];
                    $imageTitleLower = strtolower($imageTitle);
                    
                    // Filtrar por palabras excluidas (accesorios cuando buscamos consolas)
                    $shouldExclude = false;
                    foreach ($excludeWords as $word) {
                        if (stripos($imageTitleLower, strtolower($word)) !== false) {
                            $shouldExclude = true;
                            break;
                        }
                    }
                    
                    if (!$shouldExclude) {
                        $imageUrl = getWikimediaImageUrl($imageTitle);
                        if ($imageUrl) {
                            $images[] = $imageUrl;
                        }
                    }
                }
            }
        }
        
        return $images;
    }
    
    // Función para obtener imágenes de Pexels (API GRATUITA - 200 req/hora)
    function getPexelsImages($query, $limit = 3) {
        $images = [];
        // API Key gratuita de Pexels (puedes crear una en pexels.com/api)
        $apiKey = '563492ad6f91700001000001d8c7e8c6c6f04d7db4f6c3d6d9c8b3e4';
        
        $url = "https://api.pexels.com/v1/search?query=" . urlencode($query . " gaming console") . "&per_page={$limit}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: {$apiKey}"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['photos']) && is_array($data['photos'])) {
                foreach ($data['photos'] as $photo) {
                    if (isset($photo['src']['large'])) {
                        $images[] = $photo['src']['large'];
                    }
                }
            }
        }
        
        return $images;
    }
    
    // Función para generar descripción profesional en español
    function generateDescription($productName, $productType, $wikiExtract = null) {
        // Limpiar nombre
        $cleanName = str_replace([' Consola', ' Console'], '', $productName);
        
        if ($wikiExtract) {
            // Limpiar y usar extracto de Wikipedia
            $desc = strip_tags($wikiExtract);
            $desc = preg_replace('/\s+/', ' ', $desc);
            $desc = trim($desc);
            
            // Limitar a 600 caracteres para tener más información
            if (strlen($desc) > 600) {
                // Buscar el último punto antes de 600 caracteres
                $lastPeriod = strrpos(substr($desc, 0, 600), '.');
                if ($lastPeriod !== false) {
                    $desc = substr($desc, 0, $lastPeriod + 1);
                } else {
                    $desc = substr($desc, 0, 597) . '...';
                }
            }
            
            // Agregar call to action
            $desc .= " | Encuentra este y más productos en MultiGamer360, tu tienda gaming de confianza con envíos a todo el país.";
            
            return $desc;
        }
        
        // Generar descripción profesional basada en el tipo y nombre específico
        if ($productType === 'console') {
            // Descripciones específicas para consolas conocidas
            $consoleDescriptions = [
                'Nintendo 64' => "La Nintendo 64 es una consola de videojuegos legendaria lanzada por Nintendo en 1996. Revolucionó la industria con sus gráficos 3D y controles analógicos innovadores. Con un catálogo icónico que incluye Super Mario 64, The Legend of Zelda: Ocarina of Time y GoldenEye 007, esta consola marcó una era dorada del gaming. Su mando con joystick analógico fue pionero y sus 4 puertos de control permitieron experiencias multijugador inolvidables.",
                
                'PlayStation 2' => "La PlayStation 2 es la consola más vendida de todos los tiempos, con más de 155 millones de unidades. Lanzada por Sony en el año 2000, ofreció gráficos revolucionarios, retrocompatibilidad con PS1 y capacidad de reproducir DVDs. Su extenso catálogo incluye obras maestras como God of War, Final Fantasy X y Grand Theft Auto: San Andreas.",
                
                'Xbox 360' => "Xbox 360 definió una generación de gaming online con Xbox Live. Lanzada por Microsoft en 2005, ofreció gráficos HD espectaculares, un catálogo exclusivo impresionante con Halo, Gears of War y Forza, además de entretenimiento multimedia completo. Su control inalámbrico y interfaz intuitiva establecieron nuevos estándares.",
                
                'PlayStation 5' => "PlayStation 5 representa la nueva generación de consolas con su SSD ultra rápido que elimina tiempos de carga, gráficos 4K a 120fps y el revolucionario control DualSense con retroalimentación háptica. Ray tracing en tiempo real, audio 3D Tempest y retrocompatibilidad con miles de juegos de PS4 la convierten en la consola definitiva.",
                
                'Nintendo Switch' => "Nintendo Switch es la consola híbrida revolucionaria que puedes jugar en casa o portátil. Con sus Joy-Con desmontables, pantalla táctil y dock para TV, ofrece flexibilidad total. Su catálogo incluye exclusivos como Zelda: Breath of the Wild, Mario Odyssey y Animal Crossing, perfecto para gaming en cualquier lugar.",
                
                'Xbox Series X' => "Xbox Series X es la consola más potente del mercado, capaz de 4K nativo a 120fps con ray tracing. Su arquitectura Zen 2 personalizada, 12 TFLOPS de potencia gráfica y SSD de 1TB ofrecen tiempos de carga casi instantáneos. Game Pass te da acceso a cientos de juegos el día de lanzamiento."
            ];
            
            // Buscar descripción específica
            foreach ($consoleDescriptions as $key => $value) {
                if (stripos($cleanName, $key) !== false || stripos($key, $cleanName) !== false) {
                    return $value . " | Disponible en MultiGamer360 con garantía y envíos a todo Colombia.";
                }
            }
            
            // Descripción genérica profesional para consolas no listadas
            return "{$cleanName} es una consola de videojuegos que ofrece entretenimiento de calidad con un catálogo diverso de títulos. Diseñada para brindar experiencias gaming inmersivas, cuenta con tecnología avanzada para su época y controles ergonómicos. Ideal para coleccionistas y gamers que aprecian los clásicos. Esta consola representa una parte importante de la historia del gaming y sigue siendo apreciada por su legado y títulos exclusivos. | Encuéntrala en MultiGamer360, especialistas en consolas retro y modernas con garantía de calidad.";
        } else {
            // Descripción para accesorios
            return "{$productName} es un accesorio gaming de alta calidad diseñado para mejorar significativamente tu experiencia de juego. Fabricado con materiales duraderos y pensado para ofrecer máximo rendimiento y comodidad durante largas sesiones gaming. Compatible y optimizado para brindarte ventaja competitiva. Este accesorio es esencial para gamers serios que buscan optimizar su setup y llevar su experiencia al siguiente nivel. | Disponible en MultiGamer360, tu aliado para el gaming profesional.";
        }
    }
    
    // Función para obtener más imágenes de Wikimedia Commons
    function getWikimediaImages($wikiTitle, $limit = 3) {
        $images = [];
        try {
            $apiUrl = "https://es.wikipedia.org/w/api.php?action=query&titles=" . urlencode($wikiTitle) . 
                      "&prop=images&format=json&imlimit=" . $limit;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['query']['pages'])) {
                    foreach ($data['query']['pages'] as $page) {
                        if (isset($page['images'])) {
                            foreach ($page['images'] as $image) {
                                if (isset($image['title'])) {
                                    $imageUrl = getWikimediaImageUrl($image['title']);
                                    if ($imageUrl) {
                                        $images[] = $imageUrl;
                                        if (count($images) >= $limit) break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignorar errores
        }
        return $images;
    }
    
    // Función para obtener URL de imagen de Wikimedia
    function getWikimediaImageUrl($imageTitle) {
        try {
            $apiUrl = "https://commons.wikimedia.org/w/api.php?action=query&titles=" . 
                      urlencode($imageTitle) . "&prop=imageinfo&iiprop=url&format=json";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['query']['pages'])) {
                    foreach ($data['query']['pages'] as $page) {
                        if (isset($page['imageinfo'][0]['url'])) {
                            return $page['imageinfo'][0]['url'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignorar errores
        }
        return null;
    }

    // Buscar información en Wikipedia (español, sin límites)
    $wikiData = getWikipediaInfo($productName);
    
    // Buscar información técnica adicional
    $wikidataInfo = getWikidataInfo($productName);
    $technicalSpecs = getTechnicalSpecs($productName, $productType);

    $response = [
        'success' => true,
        'data' => [
            'title' => $productName,
            'description' => '',
            'image' => null,
            'images' => [],
            'specs' => $technicalSpecs,
            'source' => 'basic'
        ]
    ];

    $wikiExtract = null;
    
    if ($wikiData && isset($wikiData['extract'])) {
        $wikiExtract = $wikiData['extract'];
        // Usar descripción mejorada con especificaciones
        $response['data']['description'] = generateEnhancedDescription($productName, $productType, $wikiExtract, $technicalSpecs);
        $response['data']['source'] = 'wikipedia+specs';
        
        // Obtener imagen principal de Wikipedia
        if (isset($wikiData['thumbnail']['source'])) {
            $response['data']['image'] = $wikiData['thumbnail']['source'];
            $response['data']['images'][] = $wikiData['thumbnail']['source'];
        } elseif (isset($wikiData['originalimage']['source'])) {
            $response['data']['image'] = $wikiData['originalimage']['source'];
            $response['data']['images'][] = $wikiData['originalimage']['source'];
        }
        
        // Intentar obtener más imágenes de Wikimedia Commons
        if (isset($wikiData['title'])) {
            $additionalImages = getWikimediaImages($wikiData['title']);
            if (!empty($additionalImages)) {
                $response['data']['images'] = array_merge($response['data']['images'], $additionalImages);
                if (empty($response['data']['image']) && !empty($additionalImages)) {
                    $response['data']['image'] = $additionalImages[0];
                }
            }
        }
    } else {
        // Si no hay Wikipedia, generar descripción profesional con especificaciones
        $response['data']['description'] = generateEnhancedDescription($productName, $productType, null, $technicalSpecs);
        $response['data']['source'] = 'generated+specs';
    }
    
    // Buscar imágenes adicionales de consolas/accesorios
    if (count($response['data']['images']) < 2) {
        $consoleImages = searchConsoleImages($productName, $productType);
        if (!empty($consoleImages)) {
            $response['data']['images'] = array_merge($response['data']['images'], $consoleImages);
            if (empty($response['data']['image'])) {
                $response['data']['image'] = $consoleImages[0];
            }
            $response['data']['source'] = $response['data']['source'] === 'wikipedia' ? 'wikipedia+wikimedia' : 'wikimedia';
        }
    }
    
    // Buscar imágenes en Pexels si aún faltan
    if (count($response['data']['images']) < 2) {
        $pexelsImages = getPexelsImages($productName, 3);
        if (!empty($pexelsImages)) {
            $response['data']['images'] = array_merge($response['data']['images'], $pexelsImages);
            if (empty($response['data']['image'])) {
                $response['data']['image'] = $pexelsImages[0];
            }
            if ($response['data']['source'] === 'basic') {
                $response['data']['source'] = 'pexels';
            }
        }
    }
    
    // Eliminar duplicados
    $response['data']['images'] = array_values(array_unique($response['data']['images']));
    
    // Fallback final: asegurar al menos una imagen
    if (empty($response['data']['image'])) {
        // Buscar imagen por nombre en Unsplash
        $cleanName = str_replace([' Consola', ' Console', ' consola'], '', $productName);
        $unsplashUrl = "https://source.unsplash.com/800x600/?" . urlencode($cleanName . " gaming console");
        $response['data']['image'] = $unsplashUrl;
        $response['data']['images'][] = $unsplashUrl;
    }

    echo json_encode($response);
    
} catch (Exception $e) {
    // Si hay cualquier error, devolver JSON válido de todos modos
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener información: ' . $e->getMessage(),
        'data' => [
            'title' => isset($_GET['product_name']) ? $_GET['product_name'] : '',
            'description' => '',
            'image' => null,
            'images' => [],
            'source' => 'error'
        ]
    ]);
}
?>
