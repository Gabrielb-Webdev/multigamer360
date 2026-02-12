/**
 * Test de búsqueda combinada desde navegador
 * Coloca esto en la consola (F12) del navegador
 */

function testGameSearch(query = 'Kingdom Hearts') {
    console.clear();
    console.log('%c🔍 PRUEBA DE BÚSQUEDA COMBINADA v1.1', 'font-size: 16px; font-weight: bold; color: #007ACC;');
    console.log('Query: ' + query);
    console.log('URL: /admin/ajax/search_game_multi.php?query=' + encodeURIComponent(query) + '&action=search');
    console.log('---');

    fetch('/admin/ajax/search_game_multi.php?query=' + encodeURIComponent(query) + '&action=search')
        .then(response => {
            console.log('HTTP Status:', response.status);
            return response.json();
        })
        .then(data => {
            console.clear();
            
            console.log('%c✅ BÚSQUEDA COMPLETADA', 'font-size: 14px; font-weight: bold; color: green;');
            console.log('Query: ' + query);
            console.log('---');
            
            if (!data.success) {
                console.error('❌ Error:', data.error);
                return;
            }

            // Mostrar resumen
            console.log('%c📊 RESUMEN', 'font-weight: bold; color: #007ACC;');
            console.log('Total de resultados:', data.data.count);
            console.log('- KNOWN_DATABASE:', data.data.sources_detail.known_database);
            console.log('- RAWG API:', data.data.sources_detail.rawg);
            console.log('');

            // Mostrar resultados por fuente
            const knownResults = data.data.results.filter(r => r.source === 'KNOWN_DATABASE');
            const rawgResults = data.data.results.filter(r => r.source === 'RAWG');

            if (knownResults.length > 0) {
                console.log('%c📦 KNOWN_DATABASE (' + knownResults.length + ' resultados)', 'font-weight: bold; color: #2E7D32; background: #E8F5E9; padding: 4px;');
                knownResults.forEach((game, i) => {
                    const platforms = game.platforms.map(p => p.platform.name).join(', ');
                    console.log((i+1) + '. ' + game.name + ' → ' + platforms);
                });
                console.log('');
            } else {
                console.log('%c📦 KNOWN_DATABASE: Sin resultados', 'color: gray;');
            }

            if (rawgResults.length > 0) {
                console.log('%c🌐 RAWG API (' + rawgResults.length + ' resultados)', 'font-weight: bold; color: #1976D2; background: #E3F2FD; padding: 4px;');
                rawgResults.slice(0, 10).forEach((game, i) => {
                    const platforms = game.platforms.length > 0 
                        ? game.platforms.map(p => p.platform.name).join(', ')
                        : 'N/A';
                    console.log((i+1) + '. ' + game.name + ' → ' + platforms);
                });
                if (rawgResults.length > 10) {
                    console.log('... y ' + (rawgResults.length - 10) + ' más');
                }
                console.log('');
            } else {
                console.log('%c🌐 RAWG API: Sin resultados', 'color: gray;');
            }

            // Mostrar datos completos
            console.log('%c📄 DATOS COMPLETOS', 'font-weight: bold; color: #666;');
            console.table(data.data.results);
        })
        .catch(error => {
            console.error('❌ Error en la solicitud:', error);
        });
}

// Ejecutar con Kingdom Hearts
console.log('Ejecutando: testGameSearch("Kingdom Hearts")');
testGameSearch('Kingdom Hearts');

// Otros tests útiles:
// testGameSearch('Crash');
// testGameSearch('Final Fantasy');
// testGameSearch('God of War');
