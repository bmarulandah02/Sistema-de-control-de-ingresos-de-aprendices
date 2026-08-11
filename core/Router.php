<?php
// ──────────────────────────────────────────────
//  core/Router.php — Manejo de Rutas y Sesiones
// ──────────────────────────────────────────────

require_once __DIR__ . '/../controllers/AuthController.php';

class Router {

    public static function dispatch(): void {
        $action = $_GET['action'] ?? null;
        $estaAutenticado = isset($_SESSION['usuario_id']);

        // Manejo de la acción de Cerrar Sesión
        if ($action === 'logout') {
            $authController = new AuthController();
            $authController->logout();
            return;
        }

        // Manejo de la acción de Inicio de Sesión
        if ($action === 'login') {
            $authController = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $authController->login();
                return;
            } else {
                if ($estaAutenticado) {
                    header('Location: index.php?action=dashboard');
                    exit();
                }
                $authController->mostrarLogin();
                return;
            }
        }

        // Si el usuario no ha iniciado sesión, forzar la pantalla de Login primero
        if (!$estaAutenticado) {
            $authController = new AuthController();
            $authController->mostrarLogin();
            return;
        }

        // Acción por defecto al estar autenticado
        if (empty($action)) {
            $action = ($_SESSION['rol'] === 'Aprendiz') ? 'mi-perfil' : 'dashboard';
        }

        // Datos simulados/preparados para la renderización de vistas
        $statsHoy = ['total' => 12, 'puntuales' => 10, 'retardos' => 2];
        $statsAprendiz = ['activos' => 35];
        $ultimos = [
            ['aprendiz' => 'Carlos Pérez', 'documento' => '1012345678', 'numero_ficha' => '2978456', 'hora_entrada' => '06:55:12', 'hora_salida' => '12:58:40', 'estado' => 'Puntual'],
            ['aprendiz' => 'María Rodríguez', 'documento' => '1098765432', 'numero_ficha' => '2978456', 'hora_entrada' => '07:12:05', 'hora_salida' => null, 'estado' => 'Retardo'],
            ['aprendiz' => 'Juan Gómez', 'documento' => '1023456789', 'numero_ficha' => '2978456', 'hora_entrada' => '06:58:30', 'hora_salida' => '13:00:10', 'estado' => 'Puntual'],
        ];
        $registros = $ultimos;
        $fichas = [
            ['id' => 1, 'numero_ficha' => '2978456', 'programa' => 'Análisis y Desarrollo de Software (ADSO)', 'instructor' => 'Juan Camilo Vanegas', 'total_aprendices' => 35, 'fecha_inicio' => '2024-01-15', 'fecha_fin' => '2025-12-15', 'estado' => 'Activo']
        ];
        $excusas = [
            ['id' => 1, 'aprendiz' => 'María Rodríguez', 'documento' => '1098765432', 'numero_ficha' => '2978456', 'motivo' => 'Cita médica Odontología', 'fecha_inicio' => '2026-08-05', 'fecha_fin' => '2026-08-05', 'archivo' => 'incapacidad.pdf', 'estado' => 'Pendiente', 'created_at' => '2026-08-05 08:00:00']
        ];
        $filtros = ['fecha_inicio' => date('Y-m-01'), 'fecha_fin' => date('Y-m-d'), 'estado' => ''];
        $ficha = null;
        $aprendiz = ['nombre' => $_SESSION['nombre'] ?? 'Carlos Pérez', 'documento' => '1012345678', 'estado' => 'Activo', 'numero_ficha' => '2978456', 'programa' => 'ADSO', 'correo' => $_SESSION['correo'] ?? 'carlos@sena.edu.co', 'telefono' => '3001234567'];
        $asistencias = $ultimos;
        $mensaje = null;

        // Despacho de Vistas
        switch ($action) {
            case 'asistencia':
                require __DIR__ . '/../views/asistencia/registro.php';
                break;
            case 'historial':
                require __DIR__ . '/../views/asistencia/historial.php';
                break;
            case 'fichas':
                require __DIR__ . '/../views/admin/fichas.php';
                break;
            case 'ficha-crear':
            case 'ficha-editar':
                require __DIR__ . '/../views/fichas/formulario.php';
                break;
            case 'reportes':
            case 'excusas-admin':
                require __DIR__ . '/../views/admin/reportes.php';
                break;
            case 'mi-perfil':
                require __DIR__ . '/../views/aprendiz/perfil.php';
                break;
            case 'mis-excusas':
                require __DIR__ . '/../views/aprendiz/excusas.php';
                break;
            case 'dashboard':
            default:
                require __DIR__ . '/../views/admin/dashboard.php';
                break;
        }
    }
}
