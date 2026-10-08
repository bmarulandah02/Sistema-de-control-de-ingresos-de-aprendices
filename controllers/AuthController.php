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
        Csrf::getToken(); // Asegura la existencia de un token CSRF para el formulario
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesa la solicitud POST de inicio de sesión con Rate Limiting y CSRF.
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo   = trim($_POST['correo'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $csrfToken = $_POST['csrf_token'] ?? null;

            // 1. Verificación de Rate Limiting (Bloqueo de 5 min tras 5 intentos fallidos)
            $ahora = time();
            $bloqueoHasta = $_SESSION['login_bloqueo_hasta'] ?? 0;
            if ($bloqueoHasta > $ahora) {
                $minutosRestantes = ceil(($bloqueoHasta - $ahora) / 60);
                $this->mostrarLogin("Demasiados intentos fallidos. Por seguridad, el acceso está bloqueado temporalmente por {$minutosRestantes} minuto(s).");
                return;
            }

            // 2. Verificación Anti-CSRF si se envió token en el formulario
            if ($csrfToken !== null && !Csrf::validar($csrfToken)) {
                $this->mostrarLogin('La sesión de seguridad expiró (Token CSRF no válido). Por favor recarga e intenta de nuevo.');
                return;
            }

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
                // Reiniciar contador de intentos fallidos
                $_SESSION['login_intentos'] = 0;
                unset($_SESSION['login_bloqueo_hasta']);

                $_SESSION['usuario_id']     = $usuario['id_usuario'];
                $_SESSION['nombre']         = $usuario['nombre'];
                $_SESSION['correo']         = $usuario['correo'];
                $_SESSION['rol']            = $usuario['rol'];
                $_SESSION['ultimo_acceso']  = time();

                // Regenerar token CSRF tras autenticación exitosa (Prevención de fijación de sesión)
                Csrf::regenerar();

                // Redireccionar a su sección correspondiente según el rol
                if ($usuario['rol'] === 'Aprendiz') {
                    header('Location: index.php?action=mi-perfil');
                } else {
                    header('Location: index.php?action=dashboard');
                }
                exit();
            } else {
                // Registrar intento fallido
                $intentos = ($_SESSION['login_intentos'] ?? 0) + 1;
                $_SESSION['login_intentos'] = $intentos;

                if ($intentos >= 5) {
                    $_SESSION['login_bloqueo_hasta'] = time() + 300; // 5 minutos = 300s
                    $_SESSION['login_intentos'] = 0;
                    $this->mostrarLogin('Has superado el límite de 5 intentos fallidos consecutivos. Tu acceso se ha bloqueado temporalmente por 5 minutos por seguridad.');
                    return;
                }

                $restantes = 5 - $intentos;
                $this->mostrarLogin("Usuario/Correo o contraseña incorrectos. (Intentos restantes: {$restantes})");
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
