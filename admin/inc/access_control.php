<?php
/**
 * Verificación de acceso directo
 * Este archivo previene el acceso directo a URLs del admin sin autenticación
 */

// Asegurar que la configuración de seguridad esté cargada
if (!class_exists('AdminSecurityConfig')) {
    require_once 'security_config.php';
}

// Verificar si es un acceso directo (sin referer válido o sin sesión)
function preventDirectAccess() {
    $current_script = basename($_SERVER['PHP_SELF']);
    $allowed_direct_access = AdminSecurityConfig::getDirectAccessPages();
    
    // Si es una página que no permite acceso directo
    if (!in_array($current_script, $allowed_direct_access)) {
        // Verificar si hay sesión válida
        if (!isset($_SESSION['user_id'])) {
            // Redirigir al login
            header('Location: login.php?error=direct_access_denied');
            exit;
        }
        
        // Verificar referer para prevenir hotlinking
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $current_domain = $_SERVER['HTTP_HOST'] ?? '';
        
        if (!empty($referer) && strpos($referer, $current_domain) === false) {
            // Log del intento sospechoso
            if (function_exists('logSecurityIncident')) {
                logSecurityIncident('Suspicious referer detected', [
                    'referer' => $referer,
                    'expected_domain' => $current_domain,
                    'script' => $current_script
                ]);
            }
        }
    }
}

// Verificar patrón de navegación sospechoso
function checkNavigationPattern() {
    // Verificar si hay muchas solicitudes en poco tiempo
    if (!isset($_SESSION['page_requests'])) {
        $_SESSION['page_requests'] = [];
    }
    
    $current_time = time();
    $time_window = AdminSecurityConfig::PAGE_REQUEST_WINDOW;
    $max_requests = AdminSecurityConfig::MAX_PAGE_REQUESTS;
    
    // Limpiar requests antiguos
    $_SESSION['page_requests'] = array_filter($_SESSION['page_requests'], function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    // Agregar request actual
    $_SESSION['page_requests'][] = $current_time;
    
    // Verificar si excede el límite
    if (count($_SESSION['page_requests']) > $max_requests) {
        if (function_exists('logSecurityIncident')) {
            logSecurityIncident('Excessive page requests detected', [
                'requests_count' => count($_SESSION['page_requests']),
                'time_window' => $time_window
            ]);
        }
        
        // Opcional: bloquear temporalmente
        http_response_code(429);
        die('Too many requests. Please slow down.');
    }
}

// Verificar headers de seguridad
function checkSecurityHeaders() {
    $required_headers = AdminSecurityConfig::getRequiredHeaders();
    
    foreach ($required_headers as $header) {
        if (!isset($_SERVER[$header]) || empty($_SERVER[$header])) {
            if (function_exists('logSecurityIncident')) {
                logSecurityIncident('Missing required header', [
                    'missing_header' => $header
                ]);
            }
            http_response_code(400);
            die('Bad request');
        }
    }
    
    // Verificar host válido
    $allowed_hosts = AdminSecurityConfig::getAllowedHosts();
    
    if (!in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) {
        if (function_exists('logSecurityIncident')) {
            logSecurityIncident('Invalid host header', [
                'provided_host' => $_SERVER['HTTP_HOST'],
                'allowed_hosts' => $allowed_hosts
            ]);
        }
        http_response_code(403);
        die('Forbidden');
    }
}

// Verificar método HTTP permitido
function checkAllowedMethods() {
    $current_script = basename($_SERVER['PHP_SELF']);
    $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Scripts que solo permiten GET usando configuración centralizada
    $get_only_scripts = AdminSecurityConfig::getGetOnlyScripts();
    
    if (in_array($current_script, $get_only_scripts) && !in_array($request_method, ['GET', 'HEAD'])) {
        if (function_exists('logSecurityIncident')) {
            logSecurityIncident('Invalid HTTP method', [
                'script' => $current_script,
                'method' => $request_method
            ]);
        }
        http_response_code(405);
        header('Allow: GET, HEAD');
        die('Method not allowed');
    }
}

// Ejecutar todas las verificaciones de acceso directo
preventDirectAccess();
checkNavigationPattern();
checkSecurityHeaders();
checkAllowedMethods();
?>