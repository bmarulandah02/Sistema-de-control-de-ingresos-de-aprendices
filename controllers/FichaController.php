<?php
// ──────────────────────────────────────────────
//  controllers/FichaController.php — Gestión de Fichas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/HorarioModel.php';

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

        if ($rolSesion === 'Instructor') {
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
        $id = (int)($_GET['id'] ?? $_GET['ficha_id'] ?? 3234082);
        $ficha = HorarioModel::obtenerFichaPorId($id);

        if (!$ficha) {
            $todas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);
            if (!empty($todas)) {
                $ficha = $todas[0];
                $id = (int)$ficha['id'];
            }
        }

        $mesFiltro = trim($_GET['mes'] ?? '');
        $mesesDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($id);

        if (empty($mesFiltro) && !empty($mesesDisponibles)) {
            $mesFiltro = $mesesDisponibles[0]['mes_anio'];
        }

        $todosLosBloques = HorarioModel::obtenerHorarioBloquesFicha($id, null);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mesFiltro ?: null);
        $bloquesJson = json_encode($todosLosBloques, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($id);
        $asignaturas = HorarioModel::obtenerAsignaturasPorFicha($id);
        $todasFichas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);

        require __DIR__ . '/../views/fichas/horario.php';
    }

    /**
     * Muestra el panel de importación y escaneo de Excel de horarios y procesa la carga
     */
    public function importarHorario(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador' && ($_SESSION['rol'] ?? '') !== 'Instructor') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $idFichaDefault = (int)($_GET['ficha_id'] ?? 3234082);
        $todasFichas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idFicha = (int)($_POST['ficha_id'] ?? $idFichaDefault);
            $esEjemplo = !empty($_POST['usar_ejemplo']);
            $esAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

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
     * Vista imprimible / exportable del horario
     */
    public function imprimirHorario(): void {
        $id = (int)($_GET['id'] ?? $_GET['ficha_id'] ?? 3234082);
        $mes = trim($_GET['mes'] ?? '');
        $ficha = HorarioModel::obtenerFichaPorId($id);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mes ?: null);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($id);
        $mesesDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($id);

        require __DIR__ . '/../views/fichas/horario_imprimir.php';
    }
}
