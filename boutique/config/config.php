<?php
define('APP_NAME',    'Mi Boutique');
define('APP_VERSION', '1.0.0');
define('BASE_PATH',   dirname(__DIR__));

// ── URL base: se detecta automáticamente del servidor ────────
// Funciona en localhost Y en producción (tu-dominio.com, etc.)
(function () {
    $proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Obtiene la ruta del directorio raíz del proyecto relativa a document_root
    $docRoot  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $selfDir  = rtrim(dirname($_SERVER['SCRIPT_FILENAME'] ?? __FILE__), '/\\');
    // Calcula cuántos niveles subir desde el archivo actual hasta la raíz del proyecto
    $appRoot  = rtrim(BASE_PATH, '/\\');
    $relative = str_replace('\\', '/', str_replace($docRoot, '', $appRoot));
    $relative = '/' . ltrim($relative, '/');
    define('BASE_URL', $proto . '://' . $host . $relative);
})();

define('UPLOAD_PATH', BASE_PATH . '/uploads/logos/');

// Mail remitente genérico
define('MAIL_FROM',      'contacto@miboutique.com');
define('MAIL_FROM_NAME', 'Mi Boutique');

// Clip fee
define('CLIP_FEE_PCT', 3.6);
define('CLIP_IVA_PCT', 16.0);

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Sesión segura (secure=true en producción HTTPS)
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
