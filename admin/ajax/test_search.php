<?php
/**
 * Test de búsqueda combinada KNOWN_DATABASE + RAWG
 * Accede a: http://localhost/admin/ajax/test_search.php?q=Kingdom Hearts
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$query = $_GET['q'] ?? 'Kingdom Hearts';

echo "<!-- Testing search_game_multi.php with query: " . htmlspecialchars($query) . " -->\n\n";

// Llamar a search_game_multi.php
$url = 'http://localhost/admin/ajax/search_game_multi.php?query=' . urlencode($query) . '&action=search';

echo "<pre>\n";
echo "URL: " . htmlspecialchars($url) . "\n\n";

// Usar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n\n";
echo "Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

echo "\n\n";
echo "Conteo por fuente:\n";
$data = json_decode($response, true);
if ($data['success'] && isset($data['data']['sources_detail'])) {
    echo "- KNOWN_DATABASE: " . $data['data']['sources_detail']['known_database'] . " resultados\n";
    echo "- RAWG API: " . $data['data']['sources_detail']['rawg'] . " resultados\n";
    echo "- TOTAL: " . $data['data']['count'] . " resultados\n";
}

echo "</pre>";
