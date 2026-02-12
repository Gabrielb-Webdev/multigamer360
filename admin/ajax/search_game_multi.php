<?php
/**
 * Sistema de búsqueda de juegos - RAWG API Only
 * Version: 1.2 - Solo RAWG API (sin base de datos local)
 * Fuentes: RAWG API (500,000+ juegos)
 * API Key: 575f338491134d84bd86df30627a95fe
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : 'search';
$gameId = isset($_GET['game_id']) ? $_GET['game_id'] : '';

function logDebug($msg) {
    error_log('[search_game_multi.php v1.1] ' . $msg);
}

if (empty($query) && empty($gameId)) {
    echo json_encode(['success' => false, 'error' => 'Query requerido']);
    exit;
}

define('RAWG_API_KEY', '575f338491134d84bd86df30627a95fe');
define('RAWG_API_URL', 'https://api.rawg.io/api');

function httpGet($url, $timeout = 10) {
    logDebug('Request to: ' . $url);
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.2');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        if ($httpCode !== 200) {
            throw new Exception('HTTP ' . $httpCode);
        }
        return $response;
    }
    
    $response = @file_get_contents($url);
    if ($response === false) {
        throw new Exception('No se pudo conectar a la API');
    }
    return $response;
}

function searchGames($query) {
    $apiKey = RAWG_API_KEY;
    $url = RAWG_API_URL . '/games?key=' . urlencode($apiKey) .
           '&search=' . urlencode($query) .
           '&page_size=40&ordering=-rating';

    logDebug('RAWG API: calling ' . $url);

    try {
        $response = httpGet($url, 15);
        logDebug('RAWG Response received: ' . strlen($response) . ' bytes');
        
        $data = json_decode($response, true);

        if (!$data) {
            logDebug('RAWG: JSON decode failed - response was: ' . substr($response, 0, 200));
            return [];
        }

        if (!isset($data['results'])) {
            logDebug('RAWG: No results field in response');
            return [];
        }

        $results = $data['results'];
        logDebug('RAWG: Raw results count = ' . count($results));
        
        // Normalizar resultados
        $normalized = [];
        foreach ($results as $r) {
            $normalized[] = [
                'id' => 'rawg-' . $r['id'],
                'name' => $r['name'] ?? '',
                'platforms' => $r['platforms'] ?? [],
                'background_image' => $r['background_image'] ?? null,
                'rating' => $r['rating'] ?? 0,
                'released' => $r['released'] ?? '',
                'source' => 'RAWG'
            ];
        }
        
        logDebug('RAWG API: found ' . count($normalized) . ' results');
        return $normalized;
        
    } catch (Exception $e) {
        logDebug('RAWG Error: ' . $e->getMessage());
        return [];
    }
}

function getDetailsRAWG($gameId) {
    $apiKey = RAWG_API_KEY;
    $url = RAWG_API_URL . '/games/' . urlencode($gameId) . 
           '?key=' . urlencode($apiKey);
    
    try {
        $response = httpGet($url, 15);
        return json_decode($response, true);
    } catch (Exception $e) {
        throw new Exception('Error getting details: ' . $e->getMessage());
    }
}

try {
    if ($action === 'search') {
        logDebug('═══════════════════════════════════════════');
        logDebug('🔍 BÚSQUEDA: "' . $query . '" (v1.2 RAWG ONLY)');
        logDebug('═══════════════════════════════════════════');
        
        logDebug('→ Buscando en RAWG API...');
        $results = searchGames($query);
        
        logDebug('═══════════════════════════════════════════');
        logDebug('✅ TOTAL: ' . count($results) . ' resultados');
        logDebug('   - RAWG API: ' . count($results));
        logDebug('═══════════════════════════════════════════');

        echo json_encode([
            'success' => true,
            'data' => [
                'results' => $results,
                'count' => count($results),
                'sources_detail' => [
                    'rawg' => count($results)
                ]
            ]
        ]);
        
    } elseif ($action === 'details') {
        $data = getDetailsRAWG($gameId);
        echo json_encode(['success' => true, 'data' => $data]);
    }
    
} catch (Exception $e) {
    logDebug('❌ ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
