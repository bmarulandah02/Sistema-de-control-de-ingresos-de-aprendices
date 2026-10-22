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

// 4. Iniciar sesión si no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 5. Cargar enrutador y despachar solicitud
require_once __DIR__ . '/core/Router.php';

Router::dispatch();
