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

    /**
     * Muestra el formulario para solicitar recuperación de contraseña
     */
    public function mostrarRecuperar(?string $error = null, ?string $success = null): void {
        Csrf::getToken();
        require __DIR__ . '/../views/auth/recuperar.php';
    }

    /**
     * Procesa la solicitud de recuperación y envía el correo con SMTP
     */
    public function procesarRecuperar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->mostrarRecuperar();
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        if ($csrfToken !== null && !Csrf::validar($csrfToken)) {
            $this->mostrarRecuperar('Sesión expirada (Token CSRF inválido). Por favor intenta de nuevo.');
            return;
        }

        $identificador = trim($_POST['identificador'] ?? '');
        if (empty($identificador)) {
            $this->mostrarRecuperar('Por favor ingresa tu correo institucional o número de documento.');
            return;
        }

        require_once __DIR__ . '/../core/Mailer.php';

        $usuario = UsuarioModel::buscarPorUsuarioOEmail($identificador);

        // Por seguridad, para no revelar existencia de cuentas:
        if ($usuario && !empty($usuario['correo']) && filter_var($usuario['correo'], FILTER_VALIDATE_EMAIL)) {
            $tokenInfo = UsuarioModel::crearTokenRecuperacion((int)$usuario['id_usuario'], $usuario['correo']);

            if ($tokenInfo) {
                $nombreParaCorreo = $usuario['nombre'] ?? 'Usuario SENA';
                $resultado = Mailer::enviarRecuperacion(
                    $usuario['correo'],
                    $nombreParaCorreo,
                    $tokenInfo['token'],
                    $tokenInfo['codigo']
                );

                if (!$resultado['success']) {
                    error_log("Fallo al enviar correo de recuperación a {$usuario['correo']}: " . ($resultado['error'] ?? ''));
                    $this->mostrarRecuperar('Ocurrió un problema temporal al enviar el correo. Por favor intenta en unos minutos o contacta al administrador.');
                    return;
                }
            }
        }

        $mensajeExito = "Si los datos coinciden con un usuario registrado, hemos enviado las instrucciones y el código de verificación a tu correo.";
        $this->mostrarRecuperar(null, $mensajeExito);
    }

    /**
     * Procesa la validación por código de 6 dígitos introducido manualmente
     */
    public function procesarCodigoRecuperacion(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=recuperar-password');
            exit();
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        if ($csrfToken !== null && !Csrf::validar($csrfToken)) {
            $this->mostrarRecuperar('Sesión expirada (Token CSRF inválido). Intenta de nuevo.');
            return;
        }

        $identificador = trim($_POST['identificador'] ?? '');
        $codigo        = trim($_POST['codigo'] ?? '');

        if (empty($identificador) || empty($codigo)) {
            $this->mostrarRecuperar('Debes ingresar tu correo/documento y el código de 6 dígitos.');
            return;
        }

        $registro = UsuarioModel::validarCodigoRecuperacion($identificador, $codigo);
        if (!$registro) {
            $this->mostrarRecuperar('El código ingresado es incorrecto o ya ha expirado (validez de 30 minutos).');
            return;
        }

        header('Location: index.php?action=restablecer-password&token=' . urlencode($registro['token']));
        exit();
    }

    /**
     * Muestra el formulario para ingresar la nueva contraseña
     */
    public function mostrarRestablecer(?string $token = null, ?string $error = null): void {
        $token = $token ?? trim($_GET['token'] ?? '');
        if (empty($token)) {
            header('Location: index.php?action=login');
            exit();
        }

        $tokenValido = UsuarioModel::validarTokenRecuperacion($token);
        Csrf::getToken();
        require __DIR__ . '/../views/auth/restablecer.php';
    }

    /**
     * Procesa el cambio de contraseña con el token validado
     */
    public function procesarRestablecer(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=login');
            exit();
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        if ($csrfToken !== null && !Csrf::validar($csrfToken)) {
            $token = trim($_POST['token'] ?? '');
            $this->mostrarRestablecer($token, 'Sesión expirada (Token CSRF inválido). Recarga la página.');
            return;
        }

        $token          = trim($_POST['token'] ?? '');
        $password       = trim($_POST['password'] ?? '');
        $passwordConfirm= trim($_POST['password_confirm'] ?? '');

        if (empty($token)) {
            header('Location: index.php?action=login');
            exit();
        }

        if (strlen($password) < 6) {
            $this->mostrarRestablecer($token, 'La nueva contraseña debe tener al menos 6 caracteres.');
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->mostrarRestablecer($token, 'Las contraseñas ingresadas no coinciden.');
            return;
        }

        $actualizado = UsuarioModel::restablecerPasswordConToken($token, $password);
        if ($actualizado) {
            $_SESSION['mensaje'] = [
                'tipo' => 'success',
                'texto' => '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión con tu nueva clave.'
            ];
            header('Location: index.php?action=login');
            exit();
        } else {
            $this->mostrarRestablecer($token, 'El enlace para restablecer la contraseña ha expirado o ya fue utilizado. Por favor solicita uno nuevo.');
        }
    }

    /**
     * Endpoint AJAX para solicitar recuperación desde el Modal de Login
     */
    public function ajaxRecuperar(): void {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        if ($csrfToken !== null && !Csrf::validar($csrfToken)) {
            echo json_encode(['success' => false, 'error' => 'Sesión expirada (Token CSRF inválido). Recarga la página.']);
            return;
        }

        $identificador = trim($_POST['identificador'] ?? '');
        if (empty($identificador)) {
            echo json_encode(['success' => false, 'error' => 'Por favor ingresa tu correo institucional o número de documento.']);
            return;
        }

        require_once __DIR__ . '/../core/Mailer.php';

        $usuario = UsuarioModel::buscarPorUsuarioOEmail($identificador);

        if ($usuario && !empty($usuario['correo']) && filter_var($usuario['correo'], FILTER_VALIDATE_EMAIL)) {
            $tokenInfo = UsuarioModel::crearTokenRecuperacion((int)$usuario['id_usuario'], $usuario['correo']);

            if ($tokenInfo) {
                $nombreParaCorreo = $usuario['nombre'] ?? 'Usuario SENA';
                $resultado = Mailer::enviarRecuperacion(
                    $usuario['correo'],
                    $nombreParaCorreo,
                    $tokenInfo['token'],
                    $tokenInfo['codigo']
                );

                if (!$resultado['success']) {
                    error_log("Fallo al enviar correo a {$usuario['correo']}: " . ($resultado['error'] ?? ''));
                    echo json_encode(['success' => false, 'error' => 'Ocurrió un problema al enviar el correo. Por favor contacta al administrador.']);
                    return;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Si los datos coinciden con un usuario registrado, hemos enviado las instrucciones y el código de 6 dígitos a tu correo.'
        ]);
    }

    /**
     * Endpoint AJAX para restablecer la contraseña desde el Modal de Login
     */
    public function ajaxRestablecer(): void {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        if ($csrfToken !== null && !Csrf::validar($csrfToken)) {
            echo json_encode(['success' => false, 'error' => 'Sesión expirada (Token CSRF inválido). Recarga la página.']);
            return;
        }

        $token           = trim($_POST['token'] ?? '');
        $codigo          = trim($_POST['codigo'] ?? '');
        $identificador   = trim($_POST['identificador'] ?? '');
        $password        = trim($_POST['password'] ?? '');
        $passwordConfirm = trim($_POST['password_confirm'] ?? '');

        if (empty($token) && (empty($codigo) || empty($identificador))) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos de verificación (código o token requerido).']);
            return;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            return;
        }

        if ($password !== $passwordConfirm) {
            echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden.']);
            return;
        }

        if (!empty($token)) {
            $registro = UsuarioModel::validarTokenRecuperacion($token);
            if (!$registro) {
                echo json_encode(['success' => false, 'error' => 'El enlace de recuperación es inválido o ya ha expirado (validez de 30 minutos).']);
                return;
            }
            $tokenParaActualizar = $token;
        } else {
            $registro = UsuarioModel::validarCodigoRecuperacion($identificador, $codigo);
            if (!$registro) {
                echo json_encode(['success' => false, 'error' => 'El código de 6 dígitos es incorrecto o ya ha expirado.']);
                return;
            }
            $tokenParaActualizar = $registro['token'];
        }

        $actualizado = UsuarioModel::restablecerPasswordConToken($tokenParaActualizar, $password);
        if ($actualizado) {
            echo json_encode([
                'success' => true,
                'mensaje' => '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión con tu nueva clave.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo actualizar la contraseña. El token o código ya no es válido.'
            ]);
        }
    }
}
