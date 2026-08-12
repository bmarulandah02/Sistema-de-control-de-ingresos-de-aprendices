<?php
// ──────────────────────────────────────────────
//  core/Router.php — Enrutador Principal del Sistema
// ──────────────────────────────────────────────

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/FichaController.php';
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
        $fichaSeleccionada = !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : null;
        $statsHoy          = IngresoModel::obtenerEstadisticasHoy($fichaSeleccionada);
        $statsAprendiz     = ['activos' => AprendizModel::contarActivos($fichaSeleccionada)];
        $ultimos           = IngresoModel::obtenerUltimosMovimientos(8, $fichaSeleccionada);

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
            case 'usuarios':
                (new UsuarioController())->index();
                break;
            case 'usuario-crear':
                (new UsuarioController())->guardar();
                break;
            case 'usuario-editar':
                (new UsuarioController())->formulario((int)($_GET['id'] ?? 0));
                break;
            case 'usuario-actualizar':
                (new UsuarioController())->guardar();
                break;
            case 'usuario-eliminar':
                (new UsuarioController())->eliminar();
                break;
            case 'mi-perfil-guardar':
                (new UsuarioController())->guardarPerfilPersonal();
                break;
            case 'asistencia':
                require __DIR__ . '/../views/asistencia/registro.php';
                break;
            case 'historial':
                require __DIR__ . '/../views/asistencia/historial.php';
                break;
            case 'fichas':
                (new FichaController())->index();
                break;
            case 'ficha-crear':
                (new FichaController())->formulario();
                break;
            case 'ficha-editar':
                (new FichaController())->formulario((int)($_GET['id'] ?? 0));
                break;
            case 'ficha-guardar':
                (new FichaController())->guardar();
                break;
            case 'ficha-eliminar':
                (new FichaController())->eliminar();
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
