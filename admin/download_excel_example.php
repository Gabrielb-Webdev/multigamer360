<?php
/**
 * Generador de archivo Excel de ejemplo para importación de productos
 */

require_once '../config/database.php';

// Obtener opciones dinámicas de la base de datos
try {
    $categorias = $pdo->query("SELECT name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $marcas = $pdo->query("SELECT name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $consolas = $pdo->query("SELECT name FROM consoles WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $generos = $pdo->query("SELECT name FROM genres WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categorias = ['Videojuegos'];
    $marcas = ['Nintendo', 'Sony'];
    $consolas = ['PlayStation', 'Nintendo 64'];
    $generos = ['Acción', 'Aventura', 'RPG'];
}

// Headers para descarga Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_productos_multigamer360.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Usar BOM UTF-8 para caracteres especiales
echo "\xEF\xBB\xBF";

// Iniciar tabla HTML (Excel puede leer este formato)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .header { background-color: #2c3e50; color: white; font-weight: bold; text-align: center; }
        .instructions { background-color: #3498db; color: white; font-size: 11px; }
        .required { background-color: #e74c3c; color: white; }
        .optional { background-color: #95a5a6; color: white; }
        .example { background-color: #ecf0f1; }
    </style>
</head>
<body>
<table border="1">
    <thead>
        <!-- FILA 1: ENCABEZADOS -->
        <tr class="header">
            <th>nombre_producto</th>
            <th>tipo_producto</th>
            <th>consola</th>
            <th>estado</th>
            <th>stock</th>
            <th>precio_pesos</th>
            <th>precio_dolares</th>
        </tr>
        
        <!-- FILA 2: INSTRUCCIONES Y OPCIONES -->
        <tr class="instructions">
            <td style="width: 300px;">OBLIGATORIO<br/>Nombre completo del producto<br/>(Ej: Super Mario 64)</td>
            <td style="width: 150px;">OBLIGATORIO<br/>Tipo de producto<br/>Opciones: juego, consola, accesorio</td>
            <td style="width: 150px;">OBLIGATORIO si es juego<br/>Consola/Plataforma<br/>Opciones: <?php echo implode(', ', array_slice($consolas, 0, 8)); ?>...</td>
            <td style="width: 150px;">OBLIGATORIO<br/>Estado de venta<br/>Opciones: activo, inactivo</td>
            <td style="width: 120px;">OBLIGATORIO<br/>Cantidad en stock<br/>Solo número (Ej: 5)</td>
            <td style="width: 150px;">OBLIGATORIO<br/>Precio en pesos colombianos<br/>Solo número (Ej: 10000)</td>
            <td style="width: 150px;">OPCIONAL<br/>Precio en dólares<br/>Solo número (Ej: 10)</td>
        </tr>
    </thead>
    <tbody>
        <!-- PRODUCTOS DE EJEMPLO -->
        <tr class="example">
            <td>Super Mario 64</td>
            <td>juego</td>
            <td>Nintendo 64</td>
            <td>activo</td>
            <td>5</td>
            <td>10000</td>
            <td>10</td>
        </tr>
        
        <tr class="example">
            <td>The Legend of Zelda: Ocarina of Time</td>
            <td>juego</td>
            <td>Nintendo 64</td>
            <td>activo</td>
            <td>3</td>
            <td>12000</td>
            <td>12</td>
        </tr>
        
        <tr class="example">
            <td>Nintendo 64 Consola</td>
            <td>consola</td>
            <td>Nintendo 64</td>
            <td>activo</td>
            <td>2</td>
            <td>250000</td>
            <td>65</td>
        </tr>
        
        <tr class="example">
            <td>Control Nintendo 64 Original</td>
            <td>accesorio</td>
            <td>Nintendo 64</td>
            <td>activo</td>
            <td>10</td>
            <td>45000</td>
            <td>12</td>
        </tr>
        
        <tr class="example">
            <td>Kingdom Hearts</td>
            <td>juego</td>
            <td>PlayStation 2</td>
            <td>activo</td>
            <td>4</td>
            <td>8000</td>
            <td>8</td>
        </tr>
        
        <tr class="example">
            <td>Final Fantasy VII</td>
            <td>juego</td>
            <td>PlayStation</td>
            <td>activo</td>
            <td>2</td>
            <td>9000</td>
            <td>9</td>
        </tr>
        
        <tr class="example">
            <td>PlayStation 2 Slim</td>
            <td>consola</td>
            <td>PlayStation 2</td>
            <td>activo</td>
            <td>1</td>
            <td>180000</td>
            <td>45</td>
        </tr>
        
        <tr class="example">
            <td>Memory Card PS2 8MB</td>
            <td>accesorio</td>
            <td>PlayStation 2</td>
            <td>activo</td>
            <td>15</td>
            <td>25000</td>
            <td>7</td>
        </tr>
        
        <tr class="example">
            <td>God of War</td>
            <td>juego</td>
            <td>PlayStation 2</td>
            <td>activo</td>
            <td>6</td>
            <td>7500</td>
            <td>7.5</td>
        </tr>
        
        <tr class="example">
            <td>Cable HDMI para Xbox 360</td>
            <td>accesorio</td>
            <td>Xbox 360</td>
            <td>activo</td>
            <td>20</td>
            <td>15000</td>
            <td>4</td>
        </tr>
    </tbody>
</table>
</body>
</html>
