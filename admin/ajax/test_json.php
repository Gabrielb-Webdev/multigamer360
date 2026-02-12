<?php
// Test simple de respuesta JSON
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'El servidor PHP está funcionando correctamente',
    'timestamp' => time()
]);
exit;
