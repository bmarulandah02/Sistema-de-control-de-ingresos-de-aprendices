<?php
// ──────────────────────────────────────────────
//  core/Csrf.php — Manejador de Tokens Anti-CSRF
// ──────────────────────────────────────────────

declare(strict_types=1);

class Csrf {

    /**
     * Obtiene el token CSRF actual de la sesión o genera uno criptográficamente seguro si no existe.
     */
    public static function getToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Retorna un input hidden HTML con el token CSRF actual para incluir en formularios.
     */
    public static function campoHtml(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Valida si un token coincide con el de la sesión mediante comparación segura contra temporización.
     */
    public static function validar(?string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sesionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sesionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sesionToken, $token);
    }

    /**
     * Regenera el token CSRF (útil después del login o cambios de privilegios).
     */
    public static function regenerar(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
