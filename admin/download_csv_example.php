<?php
/**
 * Descarga del archivo CSV de ejemplo para importación de productos
 */

require_once '../config/database.php';

// Nombre del archivo para descargar
$filename = 'ejemplo_productos_multigamer360.csv';

// Headers para forzar descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Obtener opciones dinámicas de la base de datos
try {
    $categorias = $pdo->query("SELECT name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $marcas = $pdo->query("SELECT name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $consolas = $pdo->query("SELECT name FROM consoles WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $generos = $pdo->query("SELECT name FROM genres WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Valores por defecto si hay error
    $categorias = ['Videojuegos', 'Accesorios', 'Consolas'];
    $marcas = ['Nintendo', 'Sony', 'Microsoft'];
    $consolas = ['PlayStation', 'Xbox', 'Nintendo Switch'];
    $generos = ['Acción', 'Aventura', 'RPG'];
}

// Crear el contenido del CSV con instrucciones
$csv_content = [
    // FILA 1: Encabezados
    ['titulo', 'sku', 'descripcion', 'descripcion_corta', 'precio_pesos', 'precio_dolares', 'stock', 'categoria', 'marca', 'consola', 'generos', 'destacado', 'novedad', 'activo', 'etiquetas', 'meta_titulo', 'meta_descripcion', 'imagen_principal'],
    
    // FILA 2: Instrucciones y opciones disponibles
    [
        'OBLIGATORIO - Nombre del producto',
        'OBLIGATORIO - Código único (ej: SM64-N64-001)',
        'OPCIONAL - Descripción completa del producto',
        'OPCIONAL - Descripción breve para vista previa',
        'OBLIGATORIO - Precio en pesos (solo número, ej: 10000)',
        'OPCIONAL - Precio en dólares (solo número, ej: 10)',
        'OBLIGATORIO - Cantidad en stock (número entero)',
        'OBLIGATORIO - Categorías: ' . implode(', ', $categorias),
        'OBLIGATORIO - Marcas: ' . implode(', ', $marcas),
        'OBLIGATORIO - Consolas: ' . implode(', ', $consolas),
        'OPCIONAL - Géneros separados por ; (ej: ' . implode(';', array_slice($generos, 0, 3)) . ')',
        'OPCIONAL - si/no - Aparece en destacados',
        'OPCIONAL - si/no - Aparece en novedades',
        'OPCIONAL - si/no - Visible en tienda (por defecto: si)',
        'OPCIONAL - Etiquetas separadas por comas (ej: mario,plataformas,3d)',
        'OPCIONAL - Título para SEO (50-60 caracteres)',
        'OPCIONAL - Descripción para SEO (150-160 caracteres)',
        'OPCIONAL - Nombre del archivo de imagen en /uploads/products/ (ej: mario64.jpg)'
    ],
    
    // EJEMPLOS REALES DE PRODUCTOS
    [
        'Super Mario 64',
        'SM64-N64-001',
        'Super Mario 64 es un videojuego de plataformas en 3D desarrollado por Nintendo EAD y publicado por Nintendo para Nintendo 64. Es el primer juego de Mario en presentar gráficos tridimensionales completamente renderizados. Acompaña a Mario en su aventura por el Castillo de la Princesa Peach para rescatarla de las garras del malvado Bowser. Con 15 mundos únicos y 120 estrellas para coleccionar.',
        'El clásico juego de plataformas 3D de Mario para Nintendo 64',
        '10000',
        '10',
        '5',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Nintendo',
        'Nintendo 64',
        'Plataformas;Aventura;Acción',
        'si',
        'no',
        'si',
        'mario,plataformas,3d,clasico,nintendo 64',
        'Super Mario 64 Nintendo 64 Original - Clásico',
        'Compra Super Mario 64 original para Nintendo 64. El juego de plataformas 3D que revolucionó la industria. Envío rápido y producto garantizado.',
        ''
    ],
    [
        'The Legend of Zelda: Ocarina of Time',
        'ZELDA-OOT-N64',
        'The Legend of Zelda: Ocarina of Time es un videojuego de acción-aventura desarrollado por Nintendo EAD para Nintendo 64. Considerado por muchos como uno de los mejores videojuegos de todos los tiempos. Sigue la historia de Link, quien debe viajar en el tiempo para derrotar a Ganondorf y salvar el reino de Hyrule. Incluye mazmorras épicas, puzzles desafiantes y un sistema de combate revolucionario.',
        'La aventura épica de Link en 3D - Considerado uno de los mejores juegos de la historia',
        '12000',
        '12',
        '3',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Nintendo',
        'Nintendo 64',
        'Aventura;RPG;Acción',
        'si',
        'si',
        'si',
        'zelda,aventura,rpg,link,ocarina,hyrule',
        'Zelda Ocarina of Time N64 - El Mejor Juego de Todos',
        'Adquiere The Legend of Zelda: Ocarina of Time para Nintendo 64. Aventura épica con Link. Estado impecable. El juego mejor valorado de la historia.',
        ''
    ],
    [
        'Kingdom Hearts',
        'KH-PS2-001',
        'Kingdom Hearts es un videojuego de rol de acción desarrollado por Square Enix en colaboración con Disney. Combina personajes icónicos de Disney con elementos de Final Fantasy en una historia original y emotiva. Sigue a Sora, Donald y Goofy mientras viajan por diversos mundos de Disney buscando a sus amigos perdidos y luchando contra la oscuridad. Sistema de combate en tiempo real, historia profunda y banda sonora memorable.',
        'Aventura RPG que combina Disney con Final Fantasy en una experiencia única',
        '8000',
        '8',
        '10',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Square Enix',
        'PlayStation 2',
        'RPG;Aventura;Acción',
        'no',
        'no',
        'si',
        'kingdom hearts,rpg,disney,square enix,final fantasy',
        'Kingdom Hearts PS2 Original - Disney + Final Fantasy',
        'Compra Kingdom Hearts para PlayStation 2. RPG de acción con personajes de Disney y Final Fantasy. Historia emocionante. Buen estado.',
        ''
    ],
    [
        'Final Fantasy VII',
        'FF7-PS1-001',
        'Final Fantasy VII es un videojuego de rol japonés desarrollado por Square para PlayStation. Revolucionó el género RPG con sus gráficos 3D, historia cinematográfica y personajes memorables. Sigue la historia de Cloud Strife y el grupo AVALANCHA en su lucha contra Shinra Corporation y el misterioso Sephiroth. Incluye sistema de Materia innovador, invocaciones espectaculares y una trama que te mantendrá enganchado por más de 60 horas.',
        'El RPG épico que revolucionó los videojuegos - Cloud vs Sephiroth',
        '9000',
        '9',
        '2',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Square Enix',
        'PlayStation',
        'RPG;Aventura',
        'si',
        'no',
        'si',
        'final fantasy,rpg,cloud,sephiroth,jrpg,playstation',
        'Final Fantasy VII PS1 Usado - Clásico RPG',
        'Final Fantasy VII original para PlayStation 1. Estado usado pero funcional. El RPG que marcó una generación. Cloud, Sephiroth y Aeris te esperan.',
        ''
    ],
    [
        'Crash Bandicoot',
        'CRASH-PS1-001',
        'Crash Bandicoot es un videojuego de plataformas desarrollado por Naughty Dog para PlayStation. Controla al marsupial Crash en su aventura para detener los planes malvados del Dr. Neo Cortex en las Islas Wumpa. Con niveles desafiantes, gráficos coloridos para su época y una jugabilidad adictiva, se convirtió en la mascota no oficial de PlayStation. Incluye cajas TNT, frutas Wumpa y jefes memorables.',
        'El marsupial más famoso de PlayStation - Plataformas 3D',
        '5000',
        '5',
        '8',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Activision',
        'PlayStation',
        'Plataformas;Acción',
        'no',
        'si',
        'si',
        'crash bandicoot,plataformas,naughty dog,playstation',
        'Crash Bandicoot PS1 Original - Plataformas Clásico',
        'Compra Crash Bandicoot para PlayStation 1. Juego de plataformas clásico con el carismático marsupial. Diversión asegurada.',
        ''
    ],
    [
        'Mario Kart 64',
        'MK64-N64-001',
        'Mario Kart 64 es un videojuego de carreras desarrollado y publicado por Nintendo para Nintendo 64. Compite con todos tus personajes favoritos del universo Mario en 16 circuitos únicos llenos de ítems especiales y atajos secretos. Modo multijugador hasta 4 jugadores simultáneos. Incluye modos Gran Prix, Time Trial, Versus y Battle. Diversión garantizada con familia y amigos.',
        'Carreras divertidas con Mario y amigos - Hasta 4 jugadores',
        '8500',
        '8.5',
        '4',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Nintendo',
        'Nintendo 64',
        'Carreras;Multijugador;Arcade',
        'no',
        'no',
        'si',
        'mario kart,carreras,multiplayer,nintendo 64,mario',
        'Mario Kart 64 N64 - Carreras Multijugador',
        'Mario Kart 64 original para Nintendo 64. El mejor juego de carreras multijugador. Hasta 4 jugadores. Diversión familiar asegurada.',
        ''
    ],
    [
        'God of War',
        'GOW-PS2-001',
        'God of War es un videojuego de acción y aventura desarrollado por Santa Monica Studio para PlayStation 2. Sigue la historia de Kratos, un guerrero espartano que busca venganza contra Ares, el Dios de la Guerra. Con un sistema de combate brutal, puzzles ingeniosos y jefes épicos basados en la mitología griega. Gráficos impresionantes y una historia madura que redefinió el género de acción.',
        'La épica venganza de Kratos contra los dioses del Olimpo',
        '7500',
        '7.5',
        '6',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Sony',
        'PlayStation 2',
        'Acción;Aventura;Hack and Slash',
        'si',
        'no',
        'si',
        'god of war,kratos,accion,mitologia,playstation 2',
        'God of War PS2 Original - Acción Épica',
        'God of War para PlayStation 2. Acción brutal con Kratos. Mitología griega. Combates espectaculares. Uno de los mejores juegos de PS2.',
        ''
    ],
    [
        'Pokemon Stadium',
        'PKMNSTD-N64-001',
        'Pokemon Stadium es un videojuego de combate desarrollado por Nintendo para Nintendo 64. Lleva tus batallas Pokemon a la pantalla grande con gráficos 3D completos. Incluye todos los Pokemon de primera generación, múltiples modos de juego y minijuegos divertidos. Compatible con Game Boy mediante Transfer Pak para importar tus Pokemon. Ideal para fans de Pokemon que quieren ver sus combates en 3D.',
        'Batallas Pokemon en 3D para Nintendo 64 con Transfer Pak',
        '6000',
        '6',
        '7',
        count($categorias) > 0 ? $categorias[0] : 'Videojuegos',
        'Nintendo',
        'Nintendo 64',
        'RPG;Combate;Estrategia',
        'no',
        'si',
        'si',
        'pokemon,stadium,nintendo 64,rpg,batallas',
        'Pokemon Stadium N64 - Batallas Pokemon 3D',
        'Pokemon Stadium para Nintendo 64. Batallas en 3D con todos los Pokemon de primera generación. Compatible con Transfer Pak. Diversión garantizada.',
        ''
    ]
];

// Abrir stream de salida
$output = fopen('php://output', 'w');

// Escribir BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Escribir cada línea del CSV
foreach ($csv_content as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
