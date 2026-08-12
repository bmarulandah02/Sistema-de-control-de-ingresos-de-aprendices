<?php
// ──────────────────────────────────────────────
//  core/Router.php — Enrutador Principal del Sistema
// ──────────────────────────────────────────────

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/IngresoModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';
require_once __DIR__ . '/../models/ExcusaModel.php';

class Router {

    public static function dispatch(): void {
        $action = $_GET['action'] ?? null;
        $estaAutenticado = isset($_SESSION['usuario_id']);

        // 1. Manejo del Cierre de Sesión
        if ($action === 'logout') {
            $authController = new AuthController();
            $authController->logout();
            return;
        }

        // 2. Manejo del Inicio de Sesión
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

        // 3. Protección de Rutas: Si no está autenticado, fuerza la pantalla de Login
        if (!$estaAutenticado) {
            $authController = new AuthController();
            $authController->mostrarLogin();
            return;
        }

        // 4. Determinar vista por defecto según el rol del usuario autenticado
        if (empty($action)) {
            $action = (($_SESSION['rol'] ?? '') === 'Aprendiz') ? 'mi-perfil' : 'dashboard';
        }

        // 5. Carga de datos desde la Base de Datos para las vistas
        $statsHoy       = IngresoModel::obtenerEstadisticasHoy();
        $statsAprendiz  = ['activos' => AprendizModel::contarActivos()];
        $ultimos        = IngresoModel::obtenerUltimosMovimientos(8);

        // Captura de filtros de la URL para consultas en el historial
        $filtros = [
            'fecha_inicio' => $_GET['fecha_inicio'] ?? date('Y-m-01'),
            'fecha_fin'    => $_GET['fecha_fin'] ?? date('Y-m-d'),
            'estado'       => $_GET['estado'] ?? ''
        ];

        $registros   = IngresoModel::obtenerHistorialConFiltros($filtros);
        $fichas      = HorarioModel::obtenerTodasFichas();
        $excusas     = ExcusaModel::obtenerTodas();
        
        $ficha       = null;
        $mensaje     = null;
        $asistencias = $registros;

        $datosAprendiz = AprendizModel::obtenerPorUsuarioId((int)($_SESSION['usuario_id'] ?? 0));
        $aprendiz      = $datosAprendiz ?? [
            'nombre'       => $_SESSION['nombre'] ?? 'Usuario',
            'documento'    => '—',
            'estado'       => 'Activo',
            'numero_ficha' => '—',
            'programa'     => '—',
            'correo'       => $_SESSION['correo'] ?? '—',
            'telefono'     => '—'
        ];

        // 6. Despacho de Vistas
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
