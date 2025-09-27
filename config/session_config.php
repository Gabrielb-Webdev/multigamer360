<?php
/**
 * Session Configuration Helper
 * Configuración consistente de sesiones para todo el sitio
 */

if (!function_exists('initSecureSession')) {
    function initSecureSession() {
        // Solo configurar si la sesión no ha iniciado aún
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar parámetros de sesión antes de iniciarla
            ini_set('session.cookie_lifetime', 0);
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_domain', '');
            ini_set('session.cookie_secure', false); // HTTP en desarrollo
            ini_set('session.cookie_httponly', true);
            ini_set('session.cookie_samesite', 'Lax');
            
            // Iniciar sesión
            session_start();
            
            // Debugging
            error_log("SESSION INIT: ID = " . session_id() . " at " . $_SERVER['REQUEST_URI']);
        }
        
        return session_id();
    }
}
?>