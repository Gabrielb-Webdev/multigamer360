<?php
/**
 * Auto-completar información del juego - RAWG API Only
 * Version: 1.2 - Solo RAWG API (sin base de datos local)
 * API Key: 575f338491134d84bd86df30627a95fe
 */

header('Content-Type: application/json');

if (!isset($_GET['game_name']) || empty($_GET['game_name'])) {
    echo json_encode(['success' => false, 'message' => 'Nombre del juego no proporcionado.']);
    exit;
}

$gameName = trim($_GET['game_name']);
$selectedPlatform = isset($_GET['platform']) ? trim($_GET['platform']) : null;
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
        throw new Exception('No se encontró el juego');
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
    
    // Plataformas
    $platforms = array_map(function($p) {
        return $p['platform']['name'];
    }, $gameDetails['platforms'] ?? []);
    
    // Géneros
    $genresRaw = array_map(function($g) {
        return $g['name'];
    }, $gameDetails['genres'] ?? []);
    $genresSpanish = mapGenresToSpanish($genresRaw);
    
    // Developers y Publishers
    $developers = array_map(function($d) {
        return $d['name'];
    }, $gameDetails['developers'] ?? []);
    
    $publishers = array_map(function($p) {
        return $p['name'];
    }, $gameDetails['publishers'] ?? []);
    
    $brand = $publishers[0] ?? $developers[0] ?? '';
    
    // Imágenes
    $images = [];
    if (!empty($gameDetails['background_image'])) {
        $images[] = $gameDetails['background_image'];
    }
    if (!empty($gameDetails['short_screenshots'])) {
        foreach ($gameDetails['short_screenshots'] as $ss) {
            if (isset($ss['image']) && count($images) < 6) {
                $images[] = $ss['image'];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'title' => $gameDetails['name'] ?? $gameName,
            'description' => generateSpanishDescription($gameDetails),
            'released' => $gameDetails['released'] ?? '',
            'rating' => $gameDetails['rating'] ?? 0,
            'platforms' => $platforms,
            'genres' => $genresSpanish,
            'developers' => $developers,
            'publishers' => $publishers,
            'brand' => $brand,
            'images' => $images
        ],
        'platform_source' => 'RAWG',
        'message' => '✅ Información cargada desde RAWG API'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generateSpanishDescription($gameDetails) {
    $name = $gameDetails['name'] ?? 'Este juego';
    $desc = $gameDetails['description_raw'] ?? '';
    
    if (!empty($desc)) {
        $desc = strip_tags($desc);
        $desc = preg_replace('/\s+/', ' ', $desc);
        return mb_substr($desc, 0, 500);
    }
    
    return "{$name} es un videojuego con experiencia inmersiva y entretenimiento de calidad.";
}

function mapGenresToSpanish($genres) {
    $map = [
        'Action' => 'Acción',
        'Adventure' => 'Aventura',
        'RPG' => 'RPG',
        'Strategy' => 'Estrategia',
        'Shooter' => 'Disparos',
        'Puzzle' => 'Puzles',
        'Racing' => 'Carreras',
        'Sports' => 'Deportes',
        'Fighting' => 'Lucha',
        'Platformer' => 'Plataformas',
    ];
    
    return array_map(function($g) use ($map) {
        return $map[$g] ?? $g;
    }, $genres);
}
