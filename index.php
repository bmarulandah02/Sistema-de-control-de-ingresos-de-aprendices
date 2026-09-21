<?php
// ──────────────────────────────────────────────
//  index.php — Punto de Entrada Único
// ──────────────────────────────────────────────


declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar la zona horaria oficial de Colombia (America/Bogota - UTC-5)
date_default_timezone_set('America/Bogota');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/core/Router.php';

Router::dispatch();
