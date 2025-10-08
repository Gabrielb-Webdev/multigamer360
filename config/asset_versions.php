<?php
/**
 * Control de Versiones para Cache Busting
 * Actualiza estos valores cada vez que hagas cambios en CSS o JS
 */

// Versiones de archivos CSS
define('CSS_VERSION', [
    'style' => '0.2',              // CSS principal - Última actualización: dropdown consolas
    'console-selector' => '0.1',   // Selector de consolas
    'contact-modern' => '0.1',     // Página de contacto
    'cart-button-modern' => '0.1'  // Botón de carrito moderno
]);

// Versiones de archivos JavaScript
define('JS_VERSION', [
    'wishlist-system' => '0.1',      // Sistema de wishlist
    'modern-cart-button' => '0.1',   // Botón moderno de carrito
    'main' => '0.1'                  // JavaScript principal
]);

/**
 * Obtener URL con versión para cache busting
 * 
 * @param string $file Ruta del archivo CSS o JS
 * @param string $type Tipo de archivo: 'css' o 'js'
 * @return string URL con parámetro de versión
 */
function getVersionedAsset($file, $type = 'css') {
    // Extraer el nombre del archivo sin extensión ni ruta
    $filename = basename($file, '.' . $type);
    
    // Obtener la versión del array correspondiente
    if ($type === 'css') {
        $version = CSS_VERSION[$filename] ?? '1.0';
    } else {
        $version = JS_VERSION[$filename] ?? '1.0';
    }
    
    return $file . '?v=' . $version;
}

/**
 * Función helper para generar tag de CSS con versión
 */
function css($file) {
    $versionedFile = getVersionedAsset($file, 'css');
    return '<link rel="stylesheet" href="' . $versionedFile . '">';
}

/**
 * Función helper para generar tag de JS con versión
 */
function js($file) {
    $versionedFile = getVersionedAsset($file, 'js');
    return '<script src="' . $versionedFile . '"></script>';
}

// Función para incrementar versión automáticamente (útil para desarrollo)
function bumpVersion($filename, $type = 'css') {
    // Esta función puede ser llamada manualmente cuando necesites actualizar
    // Por ahora es solo una referencia para el futuro
}

/**
 * Historial de cambios:
 * 
 * v0.2 - style.css
 * - Ajuste de dropdown de consolas (alineado a la derecha)
 * 
 * v0.1 - Versión inicial de todos los archivos
 * - Implementación del sistema de versionado
 */
?>
