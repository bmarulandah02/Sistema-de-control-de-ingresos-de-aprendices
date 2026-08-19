<?php
// ──────────────────────────────────────────────
//  controllers/UsuarioController.php — Gestión de Usuarios
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';

class UsuarioController {

    /**
     * Muestra la lista de usuarios filtrando según el rol de la sesión
     */
    public function index(): void {
        $rolSesion       = $_SESSION['rol'] ?? null;
        $usuarioIdSesion = (int) ($_SESSION['usuario_id'] ?? 0);
        $usuarios        = UsuarioModel::obtenerTodosConRoles($rolSesion, $usuarioIdSesion);
        require __DIR__ . '/../views/admin/usuarios.php';
    }

    /**
     * Muestra el formulario para registrar o editar un usuario (Solo Administrador)
     */
    public function formulario(?int $id = null, ?string $error = null): void {
        $rolSesion = $_SESSION['rol'] ?? '';

        // Restricción de Seguridad: Únicamente el Administrador puede registrar o editar usuarios
        if ($rolSesion !== 'Administrador') {
            header('Location: index.php?action=usuarios&error=sin_permiso');
            exit();
        }

        $roles = UsuarioModel::obtenerRoles();
        $fichas = HorarioModel::obtenerTodasFichas();
        $usuarioEditar = $id ? UsuarioModel::obtenerPorId($id) : null;

        require __DIR__ . '/../views/admin/formulario_usuario.php';
    }

    /**
     * Procesa la creación o actualización de un usuario
     */
    public function guardar(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=usuarios&error=sin_permiso');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idUsuario      = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
            $nombre         = trim($_POST['nombre'] ?? '');
            $apellido       = trim($_POST['apellido'] ?? '');
            $identificacion = trim($_POST['identificacion'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $correo         = trim($_POST['correo'] ?? '');
            $password       = trim($_POST['password'] ?? '');
            $fk_rol         = (int) ($_POST['fk_rol'] ?? 0);
            $fk_ficha       = (int) ($_POST['fk_ficha'] ?? 0);
            $codigo_rfid    = !empty($_POST['codigo_rfid']) ? (int) $_POST['codigo_rfid'] : null;
            $esEdicion      = !empty($idUsuario);

            if (empty($nombre) || empty($apellido) || empty($identificacion) || empty($correo) || empty($fk_rol)) {
                $this->formulario($idUsuario, 'Por favor completa todos los campos obligatorios (*).');
                return;
            }

            if (!$esEdicion && empty($password)) {
                $this->formulario(null, 'La contraseña es requerida para un nuevo usuario.');
                return;
            }

            if (UsuarioModel::existeIdentificacionOEmail($identificacion, $correo, $idUsuario)) {
                $this->formulario($idUsuario, 'Ya existe un usuario registrado con esa identificación o correo electrónico.');
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

            if ($esEdicion) {
                UsuarioModel::actualizarUsuario($idUsuario, $datosUsuario);
                if ($fk_rol === 3 || $fk_rol === 2 && !empty($fk_ficha)) {
                    AprendizModel::actualizarAprendiz($codigo_rfid, $fk_ficha, $idUsuario);
                }
            } else {
                $idInsertado = UsuarioModel::crearUsuario($datosUsuario);
                if ($idInsertado && (!empty($fk_ficha))) {
                    AprendizModel::crearAprendiz($codigo_rfid, $fk_ficha, $idInsertado);
                }
            }

            header('Location: index.php?action=usuarios&success=' . ($esEdicion ? 'actualizado' : 'registrado'));
            exit();
        }

        $this->formulario();
    }

    /**
     * Procesa la actualización de datos desde la sección "Mi Perfil" (solo teléfono y contraseña)
     */
    public function guardarPerfilPersonal(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
            $telefono  = trim($_POST['telefono'] ?? '');
            $password  = trim($_POST['password'] ?? '');

            if ($idUsuario > 0) {
                UsuarioModel::actualizarPerfilPersonal($idUsuario, $telefono, $password);
                header('Location: index.php?action=mi-perfil&success=perfil_actualizado');
                exit();
            }
        }
        header('Location: index.php?action=mi-perfil');
        exit();
    }

    /**
     * Elimina un usuario verificando permisos (Solo Administrador)
     */
    public function eliminar(): void {
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: index.php?action=usuarios&error=sin_permiso');
            exit();
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            UsuarioModel::eliminarUsuario($id);
        }
        header('Location: index.php?action=usuarios&success=eliminado');
        exit();
    }
}
