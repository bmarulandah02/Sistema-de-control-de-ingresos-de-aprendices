<?php
// ──────────────────────────────────────────────
//  controllers/UsuarioController.php — Gestión de Usuarios
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';

class UsuarioController {

    /**
     * Muestra la lista de usuarios (Instructores y Aprendices)
     */
    public function index(): void {
        $usuarios = UsuarioModel::obtenerTodosConRoles();
        require __DIR__ . '/../views/admin/usuarios.php';
    }

    /**
     * Muestra el formulario para registrar un nuevo Instructor o Aprendiz
     */
    public function formulario(?string $error = null, ?string $success = null): void {
        $roles = UsuarioModel::obtenerRoles();
        $fichas = HorarioModel::obtenerTodasFichas();
        require __DIR__ . '/../views/admin/formulario_usuario.php';
    }

    /**
     * Procesa la inserción de un nuevo usuario
     */
    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre         = trim($_POST['nombre'] ?? '');
            $apellido       = trim($_POST['apellido'] ?? '');
            $identificacion = trim($_POST['identificacion'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $correo         = trim($_POST['correo'] ?? '');
            $password       = trim($_POST['password'] ?? '');
            $fk_rol         = (int) ($_POST['fk_rol'] ?? 0);
            $fk_ficha       = (int) ($_POST['fk_ficha'] ?? 0);
            $codigo_rfid    = !empty($_POST['codigo_rfid']) ? (int) $_POST['codigo_rfid'] : null;

            if (empty($nombre) || empty($apellido) || empty($identificacion) || empty($correo) || empty($password) || empty($fk_rol)) {
                $this->formulario('Por favor completa todos los campos obligatorios (*).');
                return;
            }

            if (UsuarioModel::existeIdentificacionOEmail($identificacion, $correo)) {
                $this->formulario('Ya existe un usuario registrado con esa identificación o correo electrónico.');
                return;
            }

            $datosUsuario = [
                'nombre'         => $nombre,
                'apellido'       => $apellido,
                'identificacion' => $identificacion,
                'telefono'       => $telefono,
                'nombre_usuario' => $correo,
                'contrasena'     => $password,
                'fk_rol'         => $fk_rol
            ];

            $idUsuario = UsuarioModel::crearUsuario($datosUsuario);

            if (!$idUsuario) {
                $this->formulario('Ocurrió un error al guardar el usuario en la base de datos.');
                return;
            }

            // Si el rol es Aprendiz (id_rol 3), crear también el registro en la tabla aprendiz
            if ($fk_rol === 3) {
                if (empty($fk_ficha)) {
                    $this->formulario('Para registrar un Aprendiz debes seleccionar una Ficha de Formación.');
                    return;
                }

                AprendizModel::crearAprendiz($codigo_rfid, $fk_ficha, $idUsuario);
            }

            header('Location: index.php?action=usuarios&success=registrado');
            exit();
        }

        $this->formulario();
    }
}
