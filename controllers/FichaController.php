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
            $instructoresExtra = $_POST['instructores'] ?? [];
            $instructoresExtra = is_array($instructoresExtra) ? array_map('intval', $instructoresExtra) : [];
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

                      // El titular siempre queda vinculado, además de los instructores marcados
            $instructoresExtra[] = $instructor_id;
            HorarioModel::guardarInstructoresFicha($idFichaFinal, $instructoresExtra);

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

    
    //valida el mes recibido (formato AAAA-MM, por ejemplo 2026-10).
    //Si viene vacío o inválido, usa el mes actual.
     
    private function normalizarMes(?string $mes): string {
        if ($mes !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return $mes;
        }
        return date('Y-m');
    }

    /**
     * Ayudante: devuelve todas las fechas del mes (AAAA-MM-DD), sin los domingos.
     */
    private function diasDelMes(string $mes): array {
        $dias = [];
        $totalDias = (int) date('t', strtotime($mes . '-01'));
        for ($d = 1; $d <= $totalDias; $d++) {
            $fecha = sprintf('%s-%02d', $mes, $d);
            if (date('w', strtotime($fecha)) !== '0') { // 0 = domingo
                $dias[] = $fecha;
            }
        }
        return $dias;
    }

    /**
     * Muestra la pantalla para armar el horario del mes (Solo Administrador)
     */
    public function horario(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $idFicha = (int) ($_GET['id'] ?? 0);
        $ficha = $idFicha > 0 ? HorarioModel::obtenerFichaPorId($idFicha) : null;
        if (!$ficha) {
            header('Location: index.php?action=fichas');
            exit();
        }

        $mes               = $this->normalizarMes($_GET['mes'] ?? null);
        $dias              = $this->diasDelMes($mes);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($idFicha);
        $horarioMes        = HorarioModel::obtenerHorarioMes($idFicha, $mes);
        $bloques           = HorarioModel::obtenerBloquesPorJornada($ficha['jornada'] ?? 'Mañana');

        require __DIR__ . '/../views/fichas/horario.php';
    }

    /**
     * Guarda el horario del mes que armó el administrador (Solo Administrador)
     */
    public function guardarHorario(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=fichas');
            exit();
        }

        $idFicha      = (int) ($_POST['id'] ?? 0);
        $mes          = $this->normalizarMes($_POST['mes'] ?? null);
        $asignaciones = $_POST['asignaciones'] ?? [];

        if ($idFicha <= 0 || !is_array($asignaciones)) {
            header('Location: index.php?action=fichas');
            exit();
        }

        $guardado = HorarioModel::guardarHorarioMes($idFicha, $mes, $asignaciones);

        header('Location: index.php?action=ficha-horario&id=' . $idFicha
             . '&mes=' . urlencode($mes)
             . ($guardado ? '&ok=1' : '&error=1'));
        exit();
    }

    /**
     * Muestra el horario del mes listo para imprimir (Administrador e instructores de la ficha)
     */
    public function imprimirHorario(): void {
        $rol       = $_SESSION['rol'] ?? '';
        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

        if (!in_array($rol, ['Administrador', 'Instructor'], true)) {
            header('Location: index.php?action=403');
            exit();
        }

        $idFicha = (int) ($_GET['id'] ?? 0);
        $ficha = $idFicha > 0 ? HorarioModel::obtenerFichaPorId($idFicha) : null;
        if (!$ficha) {
            header('Location: index.php?action=fichas');
            exit();
        }

        // Un instructor solo puede imprimir el horario de una ficha donde participa
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($idFicha);
        if ($rol === 'Instructor') {
            $idsPermitidos = array_column($instructoresFicha, 'id');
            if (!in_array($usuarioId, $idsPermitidos, true)) {
                header('Location: index.php?action=403');
                exit();
            }
        }

        $mes        = $this->normalizarMes($_GET['mes'] ?? null);
        $dias       = $this->diasDelMes($mes);
        $horarioMes = HorarioModel::obtenerHorarioMes($idFicha, $mes);
        $bloques    = HorarioModel::obtenerBloquesPorJornada($ficha['jornada'] ?? 'Mañana');

        require __DIR__ . '/../views/fichas/horario_imprimir.php';
    }


}
