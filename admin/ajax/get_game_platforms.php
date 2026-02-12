<?php
/**
 * Obtener plataformas disponibles para un juego - RAWG API Only
 * Version: 1.2 - Solo RAWG API (sin base de datos local)
 * API Key: 575f338491134d84bd86df30627a95fe
 */

header('Content-Type: application/json');

if (!isset($_GET['game_name']) || empty($_GET['game_name'])) {
    echo json_encode(['success' => false, 'error' => 'Nombre del juego no proporcionado']);
    exit;
}

$gameName = trim($_GET['game_name']);
$apiKey = '575f338491134d84bd86df30627a95fe';

try {
    // Buscar directamente en RAWG API
    $searchUrl = 'https://api.rawg.io/api/games?search=' . urlencode($gameName) . '&key=' . urlencode($apiKey);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/1.2');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Error al conectar con RAWG');
    }

    $data = json_decode($response, true);

    if (empty($data['results'])) {
        throw new Exception('Juego no encontrado');
    }

    $game = $data['results'][0];
    $gameId = $game['id'];
    
    // Obtener detalles completos
    $detailsUrl = "https://api.rawg.io/api/games/{$gameId}?key=" . urlencode($apiKey);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $detailsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $detailsResponse = curl_exec($ch);
    curl_close($ch);

    $gameDetails = json_decode($detailsResponse, true);
    
    $platforms = [];
    if (!empty($gameDetails['platforms'])) {
        foreach ($gameDetails['platforms'] as $platform) {
            $platformName = $platform['platform']['name'];
            $platforms[] = [
                'name' => $platformName,
                'slug' => strtolower(str_replace(' ', '-', $platformName))
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'game_name' => $gameDetails['name'],
        'platforms' => $platforms,
        'source' => 'RAWG'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}