<?php
// ──────────────────────────────────────────────
//  controllers/AuthController.php — Autenticación
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/UsuarioModel.php';

class AuthController {

    /**
     * Muestra la vista de formulario de Login.
     */
    public function mostrarLogin(?string $error = null): void {
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesa la solicitud POST de inicio de sesión.
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo   = trim($_POST['correo'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($correo) || empty($password)) {
                $this->mostrarLogin('Por favor completa todos los campos.');
                return;
            }

            // Comprobar la conexión con la base de datos
            if (!UsuarioModel::probarConexion()) {
                $this->mostrarLogin('No se pudo conectar a la base de datos MySQL "asistencia_aprendices". Verifica que MySQL esté activo y la base de datos creada en phpMyAdmin.');
                return;
            }

            $usuario = UsuarioModel::buscarPorUsuarioOEmail($correo);

            if ($usuario && UsuarioModel::verificarPassword($password, $usuario['contrasena'])) {
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['nombre']     = $usuario['nombre'];
                $_SESSION['correo']     = $usuario['correo'];
                $_SESSION['rol']        = $usuario['rol'];

                // Redireccionar al dashboard según el rol o panel general
                header('Location: index.php?action=dashboard');
                exit();
            } else {
                $this->mostrarLogin('Usuario/Correo o contraseña incorrectos.');
                return;
            }
        }

        $this->mostrarLogin();
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        header('Location: index.php?action=login');
        exit();
    }
}
