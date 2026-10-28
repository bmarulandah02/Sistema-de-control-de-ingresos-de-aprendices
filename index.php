<?php
// ──────────────────────────────────────────────
//  index.php — Punto de Entrada Único
// ──────────────────────────────────────────────

declare(strict_types=1);

// 1. Cargar variables de entorno desde .env
require_once __DIR__ . '/core/Env.php';
Env::load(__DIR__ . '/.env');

// 2. Control de reporte de errores según APP_DEBUG
$appDebug = (bool) env('APP_DEBUG', true);
if ($appDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// 3. Configurar la zona horaria oficial
date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Bogota'));

// 4. Iniciar sesión con cookie endurecida
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');   // rechaza IDs de sesión inventados por un atacante
    ini_set('session.use_only_cookies', '1');  // nunca aceptar el ID por URL
    ini_set('session.cookie_httponly', '1');   // bloqueo directo en configuración ini contra lectura vía JS
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,      // ← punto 4: ya no se lee con document.cookie
        'samesite' => 'Lax',     // ← ayuda contra CSRF en POST
    ]);
    session_start();
}

// 5. Cargar enrutador y despachar solicitud
require_once __DIR__ . '/core/Router.php';

Router::dispatch();
