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
require_once __DIR__ . '/../controllers/AsistenciaController.php';
require_once __DIR__ . '/../controllers/ReporteController.php';

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

        // 2. Manejo de Errores explícitos 403 y 404
        if ($action === '403') {
            require __DIR__ . '/../views/errors/403.php';
            return;
        }
        if ($action === '404') {
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        // 3. Manejo del Inicio de Sesión
        if ($action === 'login') {
            $authController = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $authController->login();
                return;
            } else {
                if ($estaAutenticado) {
                    if (($_SESSION['rol'] ?? '') === 'Aprendiz') {
                        header('Location: index.php?action=mi-perfil');
                    } else {
                        header('Location: index.php?action=dashboard');
                    }
                    exit();
                }
                $authController->mostrarLogin();
                return;
            }
        }

        // 4. Protección de Rutas: Si no está autenticado, fuerza la pantalla de Login
        if (!$estaAutenticado) {
            $authController = new AuthController();
            $authController->mostrarLogin();
            return;
        }

        // 5. Validación de Rutas Existentes (404)
        $rutasValidas = [
            'login', 'logout', 'dashboard', 'usuarios', 'usuario-crear', 'usuario-editar',
            'usuario-actualizar', 'usuario-eliminar', 'asistencia', 'registrar-ingreso',
            'abrir-sesion-asistencia', 'cerrar-jornada', 'historial', 'fichas', 'ficha-crear', 'ficha-editar',
            'ficha-guardar', 'ficha-eliminar', 'reportes', 'excusas-admin', 'reporte-pdf',
            'reporte-excel', 'mi-perfil', 'mi-perfil-guardar', 'mis-excusas', '403', '404'
        ];

        if (!empty($action) && !in_array($action, $rutasValidas)) {
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        // 6. Restricciones por Rol (403 Prohibido)
        $rolSesion = $_SESSION['rol'] ?? '';

        if ($rolSesion === 'Aprendiz') {
            $rutasPermitidasAprendiz = ['mi-perfil', 'mi-perfil-guardar', 'mis-excusas', 'logout'];
            if (empty($action)) {
                $action = 'mi-perfil';
            } elseif (!in_array($action, $rutasPermitidasAprendiz)) {
                require __DIR__ . '/../views/errors/403.php';
                return;
            }
        } elseif ($rolSesion === 'Instructor') {
            $rutasSoloAdmin = ['usuario-crear', 'usuario-editar', 'usuario-actualizar', 'usuario-eliminar', 'ficha-crear', 'ficha-editar', 'ficha-guardar', 'ficha-eliminar'];
            if (in_array($action, $rutasSoloAdmin)) {
                require __DIR__ . '/../views/errors/403.php';
                return;
            }
            if (empty($action)) {
                $action = 'dashboard';
            }
        } else if (empty($action)) {
            $action = 'dashboard';
        }

        // Saneo y permisos por Rol
        $usuarioIdSesion    = filter_var($_SESSION['usuario_id'] ?? 0, FILTER_VALIDATE_INT);
        $usuarioIdSesion    = ($usuarioIdSesion !== false && $usuarioIdSesion > 0) ? $usuarioIdSesion : 0;

        $fichaSeleccionada  = !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : null;
        $instructorIdFiltro = ($rolSesion === 'Instructor') ? $usuarioIdSesion : null;

        // 7. Carga de datos desde la Base de Datos para las vistas
        $statsHoy      = IngresoModel::obtenerEstadisticasHoy($fichaSeleccionada, $instructorIdFiltro);
        $statsAprendiz = ['activos' => AprendizModel::contarActivos($fichaSeleccionada, $instructorIdFiltro)];
        $ultimos       = IngresoModel::obtenerUltimosMovimientos(8, $fichaSeleccionada, $instructorIdFiltro);

        // Captura de filtros de la URL para consultas en el historial
        $filtros = [
            'fecha_inicio'  => $_GET['fecha_inicio'] ?? date('Y-m-01'),
            'fecha_fin'     => $_GET['fecha_fin'] ?? date('Y-m-d'),
            'estado'        => $_GET['estado'] ?? '',
            'ficha_id'      => $fichaSeleccionada,
            'instructor_id' => $instructorIdFiltro
        ];

        // Obtener fichas asignadas si es Instructor, o todas si es Administrador
        if ($rolSesion === 'Instructor') {
            $fichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion);
        } else {
            $fichas = HorarioModel::obtenerTodasFichas();
        }

        $registros = IngresoModel::obtenerHistorialConFiltros($filtros);
        $excusas   = ExcusaModel::obtenerTodas();

        $ficha   = null;
        $mensaje = null;

        if (isset($_SESSION['mensaje'])) {
            $mensaje = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }

        $datosAprendiz = AprendizModel::obtenerPorUsuarioId($usuarioIdSesion);
        $aprendiz      = $datosAprendiz ?? [
            'nombre'       => htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'),
            'documento'    => '—',
            'estado'       => 'Activo',
            'numero_ficha' => '—',
            'programa'     => '—',
            'correo'       => htmlspecialchars($_SESSION['correo'] ?? '—', ENT_QUOTES, 'UTF-8'),
            'telefono'     => '—'
        ];

        if ($datosAprendiz && isset($datosAprendiz['id_aprendiz'])) {
            $idAprendizSesion = filter_var($datosAprendiz['id_aprendiz'], FILTER_VALIDATE_INT);
            $asistencias = ($idAprendizSesion !== false && $idAprendizSesion > 0)
                ? IngresoModel::HistorialAprendiz($idAprendizSesion)
                : [];
        } else {
            $asistencias = $registros;
        }

        // 8. Despacho de Vistas
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
                (new AsistenciaController())->index($mensaje);
                break;
            case 'registrar-ingreso':
                (new AsistenciaController())->lecturaCodigoRfid();
                break;
            case 'abrir-sesion-asistencia':
                (new AsistenciaController())->abrirVentanaAsistencia();
                break;
            case 'cerrar-jornada':
                (new AsistenciaController())->cerrarJornada();
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
                (new ReporteController())->index();
                break;
            case 'reporte-pdf':
                (new ReporteController())->exportarPDF();
                break;
            case 'reporte-excel':
                (new ReporteController())->exportarExcel();
                break;
            case 'mi-perfil':
                require __DIR__ . '/../views/aprendiz/perfil.php';
                break;
            case 'mis-excusas':
                require __DIR__ . '/../views/aprendiz/excusas.php';
                break;
            case 'dashboard':
                require __DIR__ . '/../views/admin/dashboard.php';
                break;
            case '403':
                require __DIR__ . '/../views/errors/403.php';
                break;
            case '404':
            default:
                require __DIR__ . '/../views/errors/404.php';
                break;
        }
    }
}
