<?php
// ──────────────────────────────────────────────
//  controllers/FichaController.php — Gestión de Fichas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/HorarioModel.php';

class FichaController {

    /**
     * Muestra la lista de fichas de formación
     */
    public function index(): void {
        $fichas = HorarioModel::obtenerTodasFichas();
        require __DIR__ . '/../views/admin/fichas.php';
    }

    /**
     * Muestra el formulario para crear o editar una ficha
     */
    public function formulario(?int $id = null, ?string $error = null): void {
        $instructores = HorarioModel::obtenerInstructores();
        $ficha = $id ? HorarioModel::obtenerFichaPorId($id) : null;
        require __DIR__ . '/../views/fichas/formulario.php';
    }

    /**
     * Procesa la creación o actualización de una ficha
     */
    public function guardar(): void {
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
     * Elimina una ficha por su ID
     */
    public function eliminar(): void {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            HorarioModel::eliminarFicha($id);
        }
        header('Location: index.php?action=fichas&ok=1');
        exit();
    }
}
