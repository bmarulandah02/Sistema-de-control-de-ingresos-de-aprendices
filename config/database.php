<?php 
// ──────────────────────────────────────────────
//  config/database.php — Conexión a Base de Datos (PDO) con Soporte .env
// ──────────────────────────────────────────────

require_once __DIR__ . '/../core/Env.php';

class MySQL {
    private $conexion;

    /**
     * Establece la conexión a la base de datos leyendo parámetros desde .env
     */
    public function conectarBD() {
        $host       = (string) env('DB_HOST', 'localhost');
        $port       = (string) env('DB_PORT', '3306');
        $dbname     = (string) env('DB_NAME', 'control_ingreso_aprendices');
        $usuario    = (string) env('DB_USER', 'root');
        $contrasena = (string) env('DB_PASS', '');
        $charset    = (string) env('DB_CHARSET', 'utf8mb4');
        $timeZoneDb = (string) env('DB_TIMEZONE', '-05:00');
        $timeZoneApp= (string) env('APP_TIMEZONE', 'America/Bogota');
         if (file_exists(__DIR__ . '/database.local.php')) {
            include __DIR__ . '/database.local.php';
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            date_default_timezone_set($timeZoneApp);
            $this->conexion = new PDO($dsn, $usuario, $contrasena);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexion->exec("SET time_zone = '{$timeZoneDb}'");
        } catch (PDOException $e) {
            if (env('APP_DEBUG', false)) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
            }
            $this->conexion = null;
        }
    }

    /**
     * Retorna el objeto de conexión PDO activo
     */
    public function getConexion() {
        return $this->conexion;
    }

    /**
     * Cierra la conexión PDO activa
     */
    public function desconectar() {
        $this->conexion = null;
    }
}
?>