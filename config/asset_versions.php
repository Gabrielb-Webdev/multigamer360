<?php
/**
 * Control de Versiones para Cache Busting
 * Actualiza estos valores cada vez que hagas cambios en CSS o JS
 */

// Versiones de archivos CSS
define('CSS_VERSION', [
    'style' => '5.6',              // CSS principal - Carruseles centrados responsive
    'console-selector' => '4.2',   // Selector de consolas
    'contact-modern' => '4.2',     // Página de contacto
    'cart-button-modern' => '5.3'  // Botón de carrito moderno
]);

// Versiones de archivos JavaScript
define('JS_VERSION', [
    'wishlist-system' => '5.3',      // Sistema de wishlist
    'modern-cart-button' => '5.3',   // Botón moderno de carrito
    'main' => '5.8'                  // JavaScript principal - Fix modal timing + condición
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
