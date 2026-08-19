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

        if ($rolSesion === 'Instructor') {
            $fichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion);
        } else {
            $fichas = HorarioModel::obtenerTodasFichas();
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
                'fecha_fin'     => $fecha_fin
            ];

            if ($esEdicion) {
                HorarioModel::actualizarFicha($datos);
            } else {
                HorarioModel::crearFicha($datos);
            }

            header('Location: index.php?action=fichas&ok=1');
            exit();
        }

        $this->formulario();
    }

    /**
     * Elimina una ficha por su ID (Solo Administrador)
     */
    public function eliminar(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=fichas&error=sin_permiso');
            exit();
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            HorarioModel::eliminarFicha($id);
        }
        header('Location: index.php?action=fichas&ok=1');
        exit();
    }
}
