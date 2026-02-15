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
            'short_description' => generateShortSpanishDescription($gameDetails),
            'released' => $gameDetails['released'] ?? '',
            'rating' => $gameDetails['rating'] ?? 0,
            'platforms' => $platforms,
            'genres' => $genresSpanish,
            'developers' => $developers,
            'publishers' => $publishers,
            'brand' => $brand,
            'category' => mapCategoryToSpanish($genresSpanish),
            'images' => $images
        ],
        'platform_source' => 'RAWG',
        'message' => '✅ Información cargada desde RAWG API y traducida al español'
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
        $desc = trim($desc);
        
        // Limitar a 500 caracteres para traducción
        $descToTranslate = mb_substr($desc, 0, 500);
        
        // Intentar traducir al español usando MyMemory API (gratuita, sin API key)
        try {
            $translated = translateToSpanish($descToTranslate);
            if (!empty($translated)) {
                return $translated;
            }
        } catch (Exception $e) {
            error_log("Error traduciendo descripción: " . $e->getMessage());
        }
        
        // Si la traducción falla, devolver el texto original
        return $descToTranslate;
    }
    
    return "{$name} es un videojuego con experiencia inmersiva y entretenimiento de calidad.";
}

function translateToSpanish($text) {
    if (empty($text)) {
        return $text;
    }
    
    // Usar MyMemory Translation API (gratuita, sin API key)
    $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($text) . '&langpair=en|es';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MultiGamer360/2.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && !empty($response)) {
        $data = json_decode($response, true);
        if (isset($data['responseData']['translatedText'])) {
            return $data['responseData']['translatedText'];
        }
    }
    
    // Si falla, devolver el texto original
    return $text;
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
        'Simulation' => 'Simulación',
        'Casual' => 'Casual',
        'Indie' => 'Indie',
        'Arcade' => 'Arcade',
        'Massively Multiplayer' => 'Multijugador Masivo',
        'Family' => 'Familiar',
        'Board Games' => 'Juegos de Mesa',
        'Card' => 'Cartas',
        'Educational' => 'Educativo'
    ];
    
    return array_map(function($g) use ($map) {
        return $map[$g] ?? $g;
    }, $genres);
}

function mapCategoryToSpanish($genres) {
    // Mapear el primer género a una categoría general
    if (empty($genres)) {
        return 'Juegos';
    }
    
    $categoryMap = [
        'Acción' => 'Juegos de Acción',
        'Aventura' => 'Juegos de Aventura',
        'RPG' => 'Juegos RPG',
        'Estrategia' => 'Juegos de Estrategia',
        'Disparos' => 'Juegos de Disparos',
        'Deportes' => 'Juegos de Deportes',
        'Carreras' => 'Juegos de Carreras',
        'Simulación' => 'Juegos de Simulación'
    ];
    
    $firstGenre = $genres[0];
    return $categoryMap[$firstGenre] ?? 'Juegos';
}

function generateShortSpanishDescription($gameDetails) {
    $name = $gameDetails['name'] ?? 'Este juego';
    $desc = $gameDetails['description_raw'] ?? '';
    
    if (!empty($desc)) {
        $desc = strip_tags($desc);
        $desc = preg_replace('/\s+/', ' ', $desc);
        
        // Obtener las primeras 2 oraciones o 150 caracteres
        $shortDesc = mb_substr($desc, 0, 150);
        
        // Traducir
        try {
            $translated = translateToSpanish($shortDesc);
            if (!empty($translated)) {
                return $translated;
            }
        } catch (Exception $e) {
            error_log("Error traduciendo descripción corta: " . $e->getMessage());
        }
        
        return $shortDesc;
    }
    
    return "Videojuego {$name} disponible en MultiGamer360";
}
