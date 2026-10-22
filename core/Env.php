<?php
// ──────────────────────────────────────────────
//  core/Env.php — Cargador Ligero de Variables de Entorno (.env)
// ──────────────────────────────────────────────

declare(strict_types=1);

class Env {

    /**
     * @var bool Indica si el archivo .env ya fue procesado
     */
    private static bool $loaded = false;

    /**
     * Carga y parsea el archivo .env si existe.
     *
     * @param string|null $filePath Ruta al archivo .env (por defecto la raíz del proyecto)
     * @param bool $override Si es true, sobrescribe variables ya existentes
     */
    public static function load(?string $filePath = null, bool $override = false): void {
        if (self::$loaded && !$override) {
            return;
        }

        $path = $filePath ?? dirname(__DIR__) . '/.env';

        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar líneas vacías y comentarios (# o ;)
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            // Debe contener un signo igual '='
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            // Eliminar comillas dobles o simples envolventes
            $len = strlen($value);
            if ($len >= 2) {
                $firstChar = $value[0];
                $lastChar  = $value[$len - 1];
                if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Convertir valores especiales a sus tipos nativos
            $parsedValue = match (strtolower($value)) {
                'true', '(true)'   => true,
                'false', '(false)' => false,
                'null', '(null)'   => null,
                'empty', '(empty)' => '',
                default            => $value,
            };

            // Asignar en $_ENV, $_SERVER y putenv
            if ($override || !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $parsedValue;
            }
            if ($override || !array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $parsedValue;
            }
            putenv("{$key}={$value}");
        }

        self::$loaded = true;
    }

    /**
     * Obtiene el valor de una variable de entorno con fallback a un valor por defecto.
     *
     * @param string $key Nombre de la variable
     * @param mixed $default Valor si no se encuentra
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed {
        if (!self::$loaded) {
            self::load();
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return $envVal;
        }

        return $default;
    }
}

// ── FUNCIÓN HELPER GLOBAL env() ─────────────────────────────
if (!function_exists('env')) {
    /**
     * Acceso rápido a variables de entorno con valor predeterminado.
     *
     * @param string $key Clave de la variable
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed {
        return Env::get($key, $default);
    }
}
