<?php
/**
 * Búsqueda de juegos con RAWG API
 * Version: 3.0
 * Fuente: RAWG Video Games Database
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
ini_set('log_errors', 1);

// Habilitar CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuración de APIs
$apis = [
    'rawg' => [
        'key' => '575f338491134d84bd86df30627a95fe',
        'base_url' => 'https://api.rawg.io/api'
    ]
];

// Obtener parámetros
$action = isset($_GET['action']) ? $_GET['action'] : 'search';
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$gameId = isset($_GET['game_id']) ? trim($_GET['game_id']) : '';

// Logging function
function logError($message) {
    error_log('[search_game_rawg.php] ' . $message);
}

// Validar entrada
if (empty($query) && empty($gameId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Se requiere query o game_id',
        'debug' => [
            'action' => $action,
            'query' => $query,
            'game_id' => $gameId
        ]
    ]);
    exit;
}

/**
 * Función para hacer peticiones HTTP con cURL
 */
function makeRequest($url, $headers = []) {
    logError('Making request to: ' . $url);
    
    if (!function_exists('curl_init')) {
        throw new Exception('cURL no está disponible en el servidor');
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para entornos de desarrollo
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.0 (PHP/' . phpversion() . ')');
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    
    if ($errno) {
        logError('cURL error: ' . $error . ' (Code: ' . $errno . ')');
        throw new Exception('Error de conexión: ' . $error);
    }
    
    if ($httpCode !== 200) {
        logError('HTTP error: ' . $httpCode);
        logError('Response: ' . substr($response, 0, 500));
        throw new Exception('Error HTTP ' . $httpCode);
    }
    
    return $response;
}

/**
 * Buscar en RAWG API
 */
function searchRAWG($query, $apiKey) {
    $url = 'https://api.rawg.io/api/games?key=' . urlencode($apiKey) . 
           '&search=' . urlencode($query) . 
           '&page_size=10' .
           '&ordering=-rating';
    
    $response = makeRequest($url);
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON de RAWG');
    }
    
    return $data;
}

/**
 * Obtener detalles de juego en RAWG
 */
function getGameDetailsRAWG($gameId, $apiKey) {
    $url = 'https://api.rawg.io/api/games/' . urlencode($gameId) . 
           '?key=' . urlencode($apiKey);
    
    $response = makeRequest($url);
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON de RAWG');
    }
    
    return $data;
}

try {
    logError('Action: ' . $action . ', Query: ' . $query . ', GameID: ' . $gameId);
    
    if ($action === 'search') {
        // Búsqueda de juegos
        $data = searchRAWG($query, $apis['rawg']['key']);
        
        logError('Found ' . count($data['results'] ?? []) . ' results');
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'source' => 'RAWG'
        ]);
        
    } elseif ($action === 'details') {
        // Obtener detalles de un juego específico
        $data = getGameDetailsRAWG($gameId, $apis['rawg']['key']);
        
        logError('Got game details for ID: ' . $gameId);
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'source' => 'RAWG'
        ]);
        
    } else {
        throw new Exception('Acción no válida: ' . $action);
    }
    
} catch (Exception $e) {
    logError('Exception: ' . $e->getMessage());
    logError('Trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'action' => $action,
            'query' => $query,
            'game_id' => $gameId,
            'php_version' => phpversion(),
            'curl_available' => function_exists('curl_init'),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
