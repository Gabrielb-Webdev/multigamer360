<?php
/**
 * Configuración de Seguridad Global para Admin Panel
 * Centraliza todas las configuraciones y políticas de seguridad
 */

// Configuración de seguridad para administradores
class AdminSecurityConfig {
    // Niveles de acceso requeridos
    const MINIMUM_ADMIN_LEVEL = 80;
    const SUPER_ADMIN_LEVEL = 100;
    
    // Configuración de sesiones
    const SESSION_TIMEOUT = 3600; // 1 hora en segundos
    const MAX_IDLE_TIME = 1800;   // 30 minutos de inactividad
    
    // Configuración de rate limiting
    const MAX_LOGIN_ATTEMPTS = 5;      // Intentos de login por IP
    const LOGIN_LOCKOUT_TIME = 900;    // 15 minutos de bloqueo
    const MAX_PAGE_REQUESTS = 30;      // Requests por minuto
    const PAGE_REQUEST_WINDOW = 60;    // Ventana de tiempo en segundos
    
    // Configuración de logs
    const LOG_DIRECTORY = '../logs/';
    const SECURITY_LOG_FILE = 'security.log';
    const ACCESS_LOG_FILE = 'access.log';
    const ERROR_LOG_FILE = 'errors.log';
    
    // IPs y User Agents sospechosos
    const BLOCKED_IPS = [
        // Agregar IPs bloqueadas aquí
    ];
    
    const SUSPICIOUS_USER_AGENTS = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java'
    ];
    
    // Hosts permitidos
    public static function getAllowedHosts() {
        return [
            'localhost',
            '127.0.0.1',
            $_SERVER['HTTP_HOST'] ?? 'unknown'
        ];
    }
    
    // Páginas que permiten acceso directo (sin autenticación previa)
    public static function getDirectAccessPages() {
        return ['login.php', 'index.php'];
    }
    
    // Scripts que solo permiten método GET
    public static function getGetOnlyScripts() {
        return [
            'index.php', 'dashboard.php', 'products.php', 'orders.php', 
            'users.php', 'categories.php', 'brands.php', 'inventory.php'
        ];
    }
    
    // Headers de seguridad requeridos
    public static function getRequiredHeaders() {
        return [
            'HTTP_HOST',
            'HTTP_USER_AGENT'
        ];
    }
    
    // Verificar si un nivel de usuario es suficiente para admin
    public static function isValidAdminLevel($level) {
        return $level >= self::MINIMUM_ADMIN_LEVEL;
    }
    
    // Verificar si es super administrador
    public static function isSuperAdmin($level) {
        return $level >= self::SUPER_ADMIN_LEVEL;
    }
    
    // Obtener configuración de headers de seguridad HTTP
    public static function getSecurityHeaders() {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;"
        ];
    }
    
    // Aplicar headers de seguridad
    public static function applySecurityHeaders() {
        foreach (self::getSecurityHeaders() as $header => $value) {
            header($header . ': ' . $value);
        }
    }
    
    // Verificar si una IP está bloqueada
    public static function isIpBlocked($ip) {
        return in_array($ip, self::BLOCKED_IPS);
    }
    
    // Verificar si el user agent es sospechoso
    public static function isSuspiciousUserAgent($userAgent) {
        $userAgent = strtolower($userAgent);
        foreach (self::SUSPICIOUS_USER_AGENTS as $suspicious) {
            if (strpos($userAgent, $suspicious) !== false) {
                return true;
            }
        }
        return false;
    }
    
    // Obtener ruta completa del archivo de log
    public static function getLogPath($logFile) {
        $logDir = rtrim(self::LOG_DIRECTORY, '/') . '/';
        
        // Crear directorio si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        return $logDir . $logFile;
    }
    
    // Configuración de CSRF
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verificar CSRF token
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Limpiar datos de entrada
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

// Función global para logging de seguridad
if (!function_exists('logSecurityIncident')) {
    function logSecurityIncident($message, $context = []) {
        $logFile = AdminSecurityConfig::getLogPath(AdminSecurityConfig::SECURITY_LOG_FILE);
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $script = $_SERVER['PHP_SELF'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'user_id' => $userId,
            'script' => $script,
            'message' => $message,
            'user_agent' => $userAgent,
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

// Función global para logging de acceso
if (!function_exists('logAccessAttempt')) {
    function logAccessAttempt($action, $success = true, $details = []) {
        $logFile = AdminSecurityConfig::getLogPath(AdminSecurityConfig::ACCESS_LOG_FILE);
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'user_id' => $userId,
            'action' => $action,
            'success' => $success,
            'details' => $details
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

// Aplicar headers de seguridad automáticamente
AdminSecurityConfig::applySecurityHeaders();
?>