<?php
// ──────────────────────────────────────────────
//  controllers/FichaController.php — Gestión de Fichas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/HorarioModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';

class FichaController {

    /**
     * Muestra la lista de fichas de formación (filtradas si es Instructor)
     */
    public function index(): void {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int) ($_SESSION['usuario_id'] ?? 0);

        $filtros = [
            'q'      => trim($_GET['q'] ?? ''),
            'estado' => trim($_GET['estado'] ?? 'Activo')
        ];

        // Instructores solo pueden ver sus propias fichas activas — bloquear estado vía URL
        if ($rolSesion === 'Instructor') {
            $filtros['estado'] = 'Activo';
            $fichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion, $filtros);
        } else {
            $fichas = HorarioModel::obtenerTodasFichas($filtros);
        }

        require __DIR__ . '/../views/admin/fichas.php';
    }

    /**
     * Muestra el formulario para crear o editar una ficha (Solo Administrador)
     */
    public function formulario(?int $id = null, ?string $error = null): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $instructores = HorarioModel::obtenerInstructores();
        $ficha = $id ? HorarioModel::obtenerFichaPorId($id) : null;
        require __DIR__ . '/../views/fichas/formulario.php';
    }

    /**
     * Procesa la creación o actualización de una ficha (Solo Administrador)
     */
    public function guardar(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numero_ficha  = (int) ($_POST['numero_ficha'] ?? 0);
            $programa      = trim($_POST['programa'] ?? '');
            $instructor_id = (int) ($_POST['instructor_id'] ?? 0);
            $jornada       = trim($_POST['jornada'] ?? 'Mañana');
            $fecha_inicio  = trim($_POST['fecha_inicio'] ?? '');
            $fecha_fin     = trim($_POST['fecha_fin'] ?? '');
            $estado        = trim($_POST['estado'] ?? 'Activo');
            $esEdicion     = !empty($_POST['id']);

            if (empty($numero_ficha) || empty($programa) || empty($instructor_id)) {
                $this->formulario($esEdicion ? (int)$_POST['id'] : null, 'Por favor completa todos los campos obligatorios (*).');
                return;
            }

            $datos = [
                'numero_ficha'  => $numero_ficha,
                'programa'      => $programa,
                'instructor_id' => $instructor_id,
                'jornada'       => $jornada,
                'fecha_inicio'  => $fecha_inicio,
                'fecha_fin'     => $fecha_fin,
                'estado'        => $estado
            ];

            if ($esEdicion) {
                HorarioModel::actualizarFicha($datos);
                $idFichaFinal = (int) $_POST['id'];
            } else {
                HorarioModel::crearFicha($datos);
                $idFichaFinal = $numero_ficha;
            }

            // Guardar asignaturas técnicas y transversales si se enviaron
            $asignaturasInput = $_POST['asignaturas'] ?? [];
            if (is_array($asignaturasInput)) {
                HorarioModel::guardarAsignaturasFicha($idFichaFinal, $asignaturasInput);
            }

            header('Location: index.php?action=fichas&ok=1');
            exit();
        }

        $this->formulario();
    }

    /**
     * Oculta / Finaliza una ficha por su ID (Solo Administrador)
     */
    public function eliminar(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $cantAprendices = HorarioModel::contarAprendicesEnFicha($id);
            if ($cantAprendices > 0) {
                $ficha = HorarioModel::obtenerFichaPorId($id);
                $numFicha = $ficha ? $ficha['numero_ficha'] : $id;
                header('Location: index.php?action=fichas&error=ficha_con_aprendices&cant=' . $cantAprendices . '&num=' . urlencode($numFicha));
                exit();
            }

            HorarioModel::finalizarFicha($id);
        }
        header('Location: index.php?action=fichas&ok=finalizada');
        exit();
    }

    /**
     * Reactiva una ficha finalizada cambiándola a estado Activo (Solo Administrador)
     */
    public function reactivar(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            HorarioModel::reactivarFicha($id);
        }
        header('Location: index.php?action=fichas&ok=reactivada');
        exit();
    }

    /**
     * Muestra la vista interactiva de horario para una ficha específica
     */
    public function horario(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        if ($rolSesion === 'Instructor') {
            $todasFichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion, ['estado' => 'Activo']);
            if (empty($todasFichas)) {
                $_SESSION['mensaje'] = ['tipo' => 'warning', 'texto' => 'No tienes fichas asociadas actualmente.'];
                header('Location: index.php?action=fichas');
                exit();
            }
        } else {
            $todasFichas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);
        }

        $idSolicitado = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : null);

        // Si es instructor, asegurar que solo pueda ver fichas a las que está asociado
        if ($rolSesion === 'Instructor') {
            $idsAsignados = array_map(fn($f) => (int)$f['id'], $todasFichas);
            if ($idSolicitado && in_array($idSolicitado, $idsAsignados)) {
                $id = $idSolicitado;
            } else {
                $id = (int)$todasFichas[0]['id'];
            }
        } else {
            $id = $idSolicitado ?: (!empty($todasFichas[0]['id']) ? (int)$todasFichas[0]['id'] : 3234082);
        }

        $ficha = HorarioModel::obtenerFichaPorId($id);

        if (!$ficha && !empty($todasFichas)) {
            $ficha = $todasFichas[0];
            $id = (int)$ficha['id'];
        }

        // En el calendario de la ficha se muestra el horario COMPLETO (todos los instructores y materias de la ficha)
        $mesFiltro = trim($_GET['mes'] ?? '');
        $mesesDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($id);

        if (empty($mesFiltro) && !empty($mesesDisponibles)) {
            $mesFiltro = $mesesDisponibles[0]['mes_anio'];
        }

        $todosLosBloques = HorarioModel::obtenerHorarioBloquesFicha($id);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mesFiltro ?: null);
        $bloquesJson = json_encode($todosLosBloques, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($id);
        $asignaturas = HorarioModel::obtenerAsignaturasPorFicha($id);

        require __DIR__ . '/../views/fichas/horario.php';
    }

    /**
     * Muestra el panel de importación y escaneo de Excel de horarios y procesa la carga.
     * Solo permitido para el Administrador o el Instructor Encargado (titular) de la ficha.
     */
    public function importarHorario(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador' && ($_SESSION['rol'] ?? '') !== 'Instructor') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        if ($rolSesion === 'Instructor') {
            $fichasInstructor = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion, ['estado' => 'Activo']);
            // Un instructor solo puede importar horarios en las fichas donde sea el instructor encargado (titular)
            $todasFichas = array_values(array_filter($fichasInstructor, function($f) use ($usuarioIdSesion) {
                return (int)($f['instructor_id'] ?? 0) === $usuarioIdSesion;
            }));

            if (empty($todasFichas)) {
                $_SESSION['mensaje'] = [
                    'tipo' => 'warning',
                    'texto' => 'Acceso restringido: Solo el Administrador o el instructor titular/encargado puede subir horarios. No eres el encargado de ninguna ficha activa.'
                ];
                header('Location: index.php?action=fichas');
                exit();
            }
        } else {
            $todasFichas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);
        }

        $idFichaDefault = !empty($todasFichas[0]['id']) ? (int)$todasFichas[0]['id'] : 3234082;
        if (!empty($_GET['ficha_id'])) {
            $idGet = (int)$_GET['ficha_id'];
            if ($rolSesion !== 'Instructor' || in_array($idGet, array_map(fn($f) => (int)$f['id'], $todasFichas))) {
                $idFichaDefault = $idGet;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idFicha = (int)($_POST['ficha_id'] ?? $idFichaDefault);
            $esEjemplo = !empty($_POST['usar_ejemplo']);
            $esAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

            // 1. Validar permisos: solo Administrador o el Instructor Encargado (titular) de esta ficha
            $fichaDestino = HorarioModel::obtenerFichaPorId($idFicha);
            $esAdmin = ($rolSesion === 'Administrador');
            $esEncargado = ($fichaDestino && (int)($fichaDestino['instructor_id'] ?? 0) === $usuarioIdSesion);

            if (!$esAdmin && !$esEncargado) {
                $errorMsg = "Acceso Denegado: Solo el Administrador o el instructor encargado/titular de la Ficha {$idFicha} puede subir o modificar su horario.";
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['exito' => false, 'mensaje' => $errorMsg]);
                    exit();
                }
                $error = $errorMsg;
                require __DIR__ . '/../views/fichas/importar_horario.php';
                return;
            }

            // 2. Validar si ya existe un horario registrado para esta ficha
            $bloquesExistentes = HorarioModel::contarBloquesHorarioFicha($idFicha);
            if ($bloquesExistentes > 0) {
                $errorMsg = "Esta Ficha ({$idFicha}) ya tiene un horario vinculado con {$bloquesExistentes} bloques de clase programados. Para evitar duplicidades o sobreescritura accidental, no es posible subir otro horario encima.";
                if ($rolSesion !== 'Administrador') {
                    $errorMsg .= " Si se cargó un horario equivocado o se requiere reemplazarlo, únicamente el Administrador tiene la opción de eliminar el horario actual para permitir una nueva carga.";
                } else {
                    $errorMsg .= " Como Administrador, puedes usar la opción 'Eliminar Horario de la Ficha' para borrarlo y permitir cargar un archivo nuevo.";
                }

                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'exito' => false,
                        'mensaje' => $errorMsg,
                        'horario_existente' => true,
                        'es_admin' => $esAdmin,
                        'bloques_existentes' => $bloquesExistentes,
                        'ficha_id' => $idFicha
                    ]);
                    exit();
                }
                $error = $errorMsg;
                require __DIR__ . '/../views/fichas/importar_horario.php';
                return;
            }

            $rutaArchivo = '';

            if ($esEjemplo) {
                $rutaArchivo = __DIR__ . '/../public/uploads/horario/horario-ejemplo.xlsx';
            } elseif (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'xlsx') {
                    $errorMsg = 'Solo se permiten archivos en formato Excel (.xlsx).';
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['exito' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $error = $errorMsg;
                    require __DIR__ . '/../views/fichas/importar_horario.php';
                    return;
                }

                $dirUploads = __DIR__ . '/../public/uploads/horario/';
                if (!is_dir($dirUploads)) {
                    mkdir($dirUploads, 0777, true);
                }
                $nombreArchivo = 'horario_' . $idFicha . '_' . time() . '.xlsx';
                $rutaArchivo = $dirUploads . $nombreArchivo;
                move_uploaded_file($_FILES['archivo_excel']['tmp_name'], $rutaArchivo);
            } else {
                $errorMsg = 'Por favor selecciona un archivo Excel válido o utiliza el horario de ejemplo.';
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['exito' => false, 'mensaje' => $errorMsg]);
                    exit();
                }
                $error = $errorMsg;
                require __DIR__ . '/../views/fichas/importar_horario.php';
                return;
            }

            $resultado = HorarioModel::importarHorarioExcel($rutaArchivo, $idFicha);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($resultado);
                exit();
            }

            require __DIR__ . '/../views/fichas/importar_horario.php';
            return;
        }

        require __DIR__ . '/../views/fichas/importar_horario.php';
    }

    /**
     * Elimina por completo el horario de una ficha (acción exclusiva del Administrador)
     */
    public function eliminarHorario(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        if ($rolSesion !== 'Administrador') {
            $_SESSION['mensaje'] = [
                'texto' => 'Acceso denegado: Únicamente el Administrador tiene permisos para eliminar el horario de una ficha.',
                'tipo' => 'error'
            ];
            header('Location: index.php?action=fichas');
            exit();
        }

        $idFicha = (int)($_POST['ficha_id'] ?? $_GET['ficha_id'] ?? $_GET['id'] ?? 0);
        if ($idFicha <= 0) {
            $_SESSION['mensaje'] = ['texto' => 'ID de ficha no válido.', 'tipo' => 'error'];
            header('Location: index.php?action=fichas');
            exit();
        }

        $resultado = HorarioModel::eliminarHorarioCompletoFicha($idFicha);

        $esAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultado);
            exit();
        }

        if ($resultado['exito']) {
            $_SESSION['mensaje'] = [
                'texto' => "Horario de la Ficha {$idFicha} eliminado correctamente ({$resultado['bloques_eliminados']} bloques borrados). Ahora se puede subir el horario correcto.",
                'tipo' => 'success'
            ];
        } else {
            $_SESSION['mensaje'] = [
                'texto' => $resultado['mensaje'] ?? 'Error al eliminar el horario.',
                'tipo' => 'error'
            ];
        }

        $redirect = $_GET['redirect'] ?? '';
        $destino = ($redirect === 'importar')
            ? "index.php?action=ficha-horario-importar&ficha_id={$idFicha}"
            : "index.php?action=ficha-horario&id={$idFicha}";
        header("Location: {$destino}");
        exit();
    }

    /**
     * Vista imprimible / exportable del horario
     */
    public function imprimirHorario(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        $id = (int)($_GET['id'] ?? $_GET['ficha_id'] ?? 3234082);

        if ($rolSesion === 'Instructor') {
            $fichasInstructor = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion);
            $idsAsignados = array_map(fn($f) => (int)$f['id'], $fichasInstructor);
            if (!in_array($id, $idsAsignados)) {
                header('Location: index.php?action=fichas&error=sin_permiso');
                exit();
            }
        }

        $mes = trim($_GET['mes'] ?? '');
        $ficha = HorarioModel::obtenerFichaPorId($id);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mes ?: null);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($id);
        $mesesDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($id);

        require __DIR__ . '/../views/fichas/horario_imprimir.php';
    }

    /**
     * API REST Asíncrona: Retorna los bloques de horario en formato JSON
     * Útil para paginación dinámica por mes o lazy loading sin recargar la página.
     */
    public function apiBloques(): void {
        header('Content-Type: application/json; charset=utf-8');
        
        $id = (int)($_GET['id'] ?? $_GET['ficha_id'] ?? 0);
        $mes = trim($_GET['mes'] ?? '');

        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'mensaje' => 'ID de ficha no válido',
                'bloques' => []
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mes ?: null);

        echo json_encode([
            'success' => true,
            'ficha_id' => $id,
            'mes' => $mes,
            'total' => count($bloques),
            'bloques' => $bloques
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Muestra el panel de importación y escaneo de Excel de aprendices y procesa la carga masiva.
     * Soporta lectura de la carpeta public/uploads/Aprendices/, dropzone, detección de campos nulos,
     * asignación de contraseña sena2025 y deja el código RFID en null para administración.
     */
    public function importarAprendices(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador' && ($_SESSION['rol'] ?? '') !== 'Instructor') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        if ($rolSesion === 'Instructor') {
            $todasFichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion, ['estado' => 'Activo']);
            if (empty($todasFichas)) {
                $_SESSION['mensaje'] = [
                    'tipo' => 'warning',
                    'texto' => 'No tienes fichas asociadas actualmente para importar aprendices.'
                ];
                header('Location: index.php?action=fichas');
                exit();
            }
        } else {
            $todasFichas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);
        }

        $idFichaDefault = !empty($todasFichas[0]['id']) ? (int)$todasFichas[0]['id'] : 3234082;
        if (!empty($_GET['ficha_id'])) {
            $idGet = (int)$_GET['ficha_id'];
            if ($rolSesion !== 'Instructor' || in_array($idGet, array_map(fn($f) => (int)$f['id'], $todasFichas))) {
                $idFichaDefault = $idGet;
            }
        }

        // Explorar carpeta public/uploads/Aprendices/
        $dirAprendices = __DIR__ . '/../public/uploads/Aprendices/';
        if (!is_dir($dirAprendices)) {
            @mkdir($dirAprendices, 0777, true);
        }

        $archivosDetectados = [];
        if (is_dir($dirAprendices)) {
            $archivos = scandir($dirAprendices);
            foreach ($archivos as $archivo) {
                if ($archivo !== '.' && $archivo !== '..' && preg_match('/\.(xlsx|xls|csv)$/i', $archivo)) {
                    $rutaCompleta = $dirAprendices . $archivo;
                    $archivosDetectados[] = [
                        'nombre' => $archivo,
                        'ruta_relativa' => 'public/uploads/Aprendices/' . rawurlencode($archivo),
                        'tamano_kb' => round(filesize($rutaCompleta) / 1024, 1),
                        'fecha' => date('d/m/Y H:i', filemtime($rutaCompleta))
                    ];
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $esAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            
            // 1. Verificar si viene JSON por body
            $inputJSON = file_get_contents('php://input');
            $dataJson = json_decode($inputJSON, true);

            $idFicha = 0;
            $aprendices = [];

            if (!empty($dataJson['aprendices']) && !empty($dataJson['ficha_id'])) {
                $idFicha = (int)$dataJson['ficha_id'];
                $aprendices = $dataJson['aprendices'];
            } elseif (!empty($_POST['aprendices_json'])) {
                $idFicha = (int)($_POST['ficha_id'] ?? $idFichaDefault);
                $aprendices = json_decode($_POST['aprendices_json'], true) ?: [];
            } elseif (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
                // Guardar archivo nuevo subido en la carpeta public/uploads/Aprendices/
                $nombreOrig = basename($_FILES['archivo_excel']['name']);
                $ext = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
                if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
                    $errorMsg = 'Solo se permiten archivos en formato Excel (.xlsx, .xls) o CSV.';
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['exito' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $error = $errorMsg;
                    require __DIR__ . '/../views/fichas/importar_aprendices.php';
                    return;
                }
                $nombreDestino = 'Aprendices_' . time() . '_' . $nombreOrig;
                move_uploaded_file($_FILES['archivo_excel']['tmp_name'], $dirAprendices . $nombreDestino);

                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'exito' => true,
                        'archivo_guardado' => $nombreDestino,
                        'ruta_relativa' => 'public/uploads/Aprendices/' . rawurlencode($nombreDestino),
                        'mensaje' => 'Archivo guardado correctamente en la carpeta de Aprendices.'
                    ]);
                    exit();
                }
            }

            if (empty($aprendices)) {
                $errorMsg = 'No se recibieron filas válidas de aprendices para procesar.';
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['exito' => false, 'mensaje' => $errorMsg]);
                    exit();
                }
                $error = $errorMsg;
                require __DIR__ . '/../views/fichas/importar_aprendices.php';
                return;
            }

            // Validar ficha seleccionada para Instructores
            if ($rolSesion === 'Instructor') {
                $idsPermitidos = array_map(fn($f) => (int)$f['id'], $todasFichas);
                if (!in_array($idFicha, $idsPermitidos)) {
                    $errorMsg = 'Acceso denegado: No tienes permisos para importar aprendices en esta ficha.';
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['exito' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $error = $errorMsg;
                    require __DIR__ . '/../views/fichas/importar_aprendices.php';
                    return;
                }
            }

            $resultado = AprendizModel::importarAprendices($aprendices, $idFicha);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($resultado);
                exit();
            }

            require __DIR__ . '/../views/fichas/importar_aprendices.php';
            return;
        }

        require __DIR__ . '/../views/fichas/importar_aprendices.php';
    }
}
