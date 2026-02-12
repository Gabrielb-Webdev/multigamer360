<?php
/**
 * Verificaciones de seguridad adicionales para el panel de administración
 * Incluye rate limiting, detección de User Agents sospechosos, y timeout de sesión
 */

// Asegurar que la configuración de seguridad esté cargada
if (!class_exists('AdminSecurityConfig')) {
    require_once 'security_config.php';
}

// Verificar que venimos de una sesión válida de administrador
function verifyAdminAccess() {
    // Verificar IP bloqueada
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (AdminSecurityConfig::isIpBlocked($client_ip)) {
        logSecurityIncident('Blocked IP attempted access', ['ip' => $client_ip]);
        http_response_code(403);
        die('Access denied');
    }
    
    // Verificar headers de seguridad
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Verificar user agent sospechoso usando configuración centralizada
    if (AdminSecurityConfig::isSuspiciousUserAgent($user_agent)) {
        logSecurityIncident('Suspicious user agent detected', [
            'user_agent' => $user_agent,
            'ip' => $client_ip
        ]);
        
        // Opcional: bloquear completamente
        http_response_code(403);
        die('Access denied - Suspicious activity detected');
    }
    
    // Verificar referer para evitar hotlinking malicioso
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer)) {
        $referer_host = parse_url($referer, PHP_URL_HOST);
        $current_host = $_SERVER['HTTP_HOST'] ?? '';
        
        if ($referer_host && $referer_host !== $current_host && !in_array($referer_host, AdminSecurityConfig::getAllowedHosts())) {
            logSecurityIncident('Suspicious referer detected', [
                'referer' => $referer,
                'referer_host' => $referer_host,
                'current_host' => $current_host
            ]);
        }
    }
}

// Rate limiting por IP para prevenir ataques de fuerza bruta
function checkRateLimit() {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_key = 'rate_limit_' . md5($client_ip);
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = [];
    }
    
    $current_time = time();
    $time_window = AdminSecurityConfig::PAGE_REQUEST_WINDOW;
    $max_requests = AdminSecurityConfig::MAX_PAGE_REQUESTS;
    
    // Limpiar requests antiguos
    $_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    // Verificar si excede el límite
    if (count($_SESSION[$rate_limit_key]) >= $max_requests) {
        logSecurityIncident('Rate limit exceeded', [
            'ip' => $client_ip,
            'requests' => count($_SESSION[$rate_limit_key]),
            'time_window' => $time_window
        ]);
        
        http_response_code(429);
        header('Retry-After: ' . $time_window);
        die('Too many requests. Please try again later.');
    }
    
    // Agregar request actual
    $_SESSION[$rate_limit_key][] = $current_time;
}

// Verificar timeout de sesión
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > AdminSecurityConfig::SESSION_TIMEOUT) {
            logAccessAttempt('Session timeout', false, [
                'inactive_time' => $inactive_time,
                'max_timeout' => AdminSecurityConfig::SESSION_TIMEOUT
            ]);
            
            session_destroy();
            header('Location: login.php?error=session_timeout');
            exit;
        }
    }
    
    // Actualizar tiempo de última actividad
    $_SESSION['last_activity'] = time();
}

// Verificar integridad de la sesión
function checkSessionIntegrity() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Verificar que el user_id sea un entero válido
    if (!is_numeric($_SESSION['user_id'])) {
        logSecurityIncident('Invalid user_id in session', [
            'user_id' => $_SESSION['user_id']
        ]);
        session_destroy();
        return false;
    }
    
    // Verificar token de sesión si existe
    if (isset($_SESSION['session_token'])) {
        $expected_token = hash('sha256', $_SESSION['user_id'] . $_SERVER['HTTP_USER_AGENT'] . session_id());
        if (!hash_equals($_SESSION['session_token'], $expected_token)) {
            logSecurityIncident('Session token mismatch', [
                'user_id' => $_SESSION['user_id']
            ]);
            session_destroy();
            return false;
        }
    } else {
        // Generar token si no existe
        $_SESSION['session_token'] = hash('sha256', $_SESSION['user_id'] . $_SERVER['HTTP_USER_AGENT'] . session_id());
    }
    
    return true;
}

// Verificar headers requeridos
function checkRequiredHeaders() {
    foreach (AdminSecurityConfig::getRequiredHeaders() as $header) {
        if (!isset($_SERVER[$header]) || empty($_SERVER[$header])) {
            logSecurityIncident('Missing required header', [
                'missing_header' => $header
            ]);
            http_response_code(400);
            die('Bad request - Missing required headers');
        }
    }
}

// Ejecutar todas las verificaciones de seguridad
try {
    checkRequiredHeaders();
    verifyAdminAccess();
    checkRateLimit();
    
    // Solo verificar sesión si estamos autenticados
    if (isset($_SESSION['user_id'])) {
        if (!checkSessionIntegrity()) {
            header('Location: login.php?error=session_invalid');
            exit;
        }
        checkSessionTimeout();
    }
    
} catch (Exception $e) {
    logSecurityIncident('Security check exception', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    die('Security verification failed');
}
?>