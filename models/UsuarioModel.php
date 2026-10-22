<?php
// ──────────────────────────────────────────────
//  models/UsuarioModel.php — Modelo de Autenticación y Gestión de Usuarios
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class UsuarioModel {

    /**
     * Comprueba el estado de la conexión a la BD.
     */
    public static function probarConexion(): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            return $mysql->getConexion() !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene el listado de roles disponibles en la BD
     */
    public static function obtenerRoles(): array {
        $roles = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $count = (int) $conexion->query("SELECT COUNT(*) FROM rol")->fetchColumn();
                if ($count === 0) {
                    $conexion->exec("INSERT INTO rol (id_rol, nombre_rol) VALUES 
                                     (1, 'Administrador'), (2, 'Instructor'), (3, 'Aprendiz')");
                }

                $stmt = $conexion->query("SELECT id_rol, nombre_rol FROM rol ORDER BY id_rol ASC");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $roles[] = $row;
                }
            }
        } catch (Exception $e) {
            $roles = [
                ['id_rol' => 1, 'nombre_rol' => 'Administrador'],
                ['id_rol' => 2, 'nombre_rol' => 'Instructor'],
                ['id_rol' => 3, 'nombre_rol' => 'Aprendiz']
            ];
        }
        return $roles;
    }

    /**
     * Verifica si ya existe un usuario con la misma identificación o correo/nombre_usuario (excluyendo el id enviado si es edición)
     */
    public static function existeIdentificacionOEmail(string $identificacion, string $email, ?int $excluirId = null): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT COUNT(*) FROM usuario WHERE (identificacion = :ident OR LOWER(nombre_usuario) = LOWER(:email))";
                $params = [':ident' => $identificacion, ':email' => $email];

                if ($excluirId) {
                    $sql .= " AND id_usuario != :excluirId";
                    $params[':excluirId'] = $excluirId;
                }

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                return ((int) $stmt->fetchColumn()) > 0;
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Inserta un nuevo usuario en la base de datos y retorna su id_usuario
     */
    public static function crearUsuario(array $datos): ?int {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "INSERT INTO usuario (nombre_usuario, contrasena, nombre, apellido, identificacion, telefono, fk_rol)
                        VALUES (:nombre_usuario, :contrasena, :nombre, :apellido, :identificacion, :telefono, :fk_rol)";
                
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':nombre_usuario' => $datos['nombre_usuario'],
                    ':contrasena'     => password_hash($datos['contrasena'], PASSWORD_BCRYPT),
                    ':nombre'         => $datos['nombre'],
                    ':apellido'       => $datos['apellido'],
                    ':identificacion' => $datos['identificacion'],
                    ':telefono'       => $datos['telefono'] ?? '',
                    ':fk_rol'         => (int) $datos['fk_rol']
                ]);

                return (int) $conexion->lastInsertId();
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Actualiza un usuario existente en la base de datos
     */
    public static function actualizarUsuario(int $idUsuario, array $datos): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $params = [
                    ':nombre'         => $datos['nombre'],
                    ':apellido'       => $datos['apellido'],
                    ':identificacion' => $datos['identificacion'],
                    ':telefono'       => $datos['telefono'] ?? '',
                    ':nombre_usuario' => $datos['nombre_usuario'],
                    ':fk_rol'         => (int) $datos['fk_rol'],
                    ':id'             => $idUsuario
                ];

                $sqlPass = "";
                if (!empty($datos['contrasena'])) {
                    $sqlPass = ", contrasena = :contrasena";
                    $params[':contrasena'] = password_hash($datos['contrasena'], PASSWORD_BCRYPT);
                }

                $sql = "UPDATE usuario 
                        SET nombre = :nombre,
                            apellido = :apellido,
                            identificacion = :identificacion,
                            telefono = :telefono,
                            nombre_usuario = :nombre_usuario,
                            fk_rol = :fk_rol
                            {$sqlPass}
                        WHERE id_usuario = :id";

                $stmt = $conexion->prepare($sql);
                return $stmt->execute($params);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Actualiza teléfono y contraseña del perfil propio del usuario conectado (Mi Perfil)
     */
    public static function actualizarPerfilPersonal(int $idUsuario, string $telefono, ?string $nuevaPassword = null): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $params = [
                    ':telefono' => $telefono,
                    ':id'       => $idUsuario
                ];

                $sqlPass = "";
                if (!empty($nuevaPassword)) {
                    $sqlPass = ", contrasena = :contrasena";
                    $params[':contrasena'] = password_hash($nuevaPassword, PASSWORD_BCRYPT);
                }

                $sql = "UPDATE usuario SET telefono = :telefono {$sqlPass} WHERE id_usuario = :id";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute($params);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Obtiene los datos de un usuario por su ID
     */
    public static function obtenerPorId(int $idUsuario): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.apellido, u.identificacion, u.telefono,
                               u.fk_rol, r.nombre_rol, a.codigo_rfid, a.fk_ficha, a.estado AS estado_aprendiz
                        FROM usuario u
                        LEFT JOIN rol r ON u.fk_rol = r.id_rol
                        LEFT JOIN aprendiz a ON a.fk_usuario = u.id_usuario
                        WHERE u.id_usuario = :id LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([':id' => $idUsuario]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    return [
                        'id_usuario'     => $row['id_usuario'],
                        'nombre'         => $row['nombre'],
                        'apellido'       => $row['apellido'],
                        'identificacion' => $row['identificacion'],
                        'telefono'       => $row['telefono'],
                        'correo'         => $row['nombre_usuario'],
                        'nombre_usuario' => $row['nombre_usuario'],
                        'fk_rol'         => $row['fk_rol'],
                        'rol'            => $row['nombre_rol'] ?? 'Administrador',
                        'codigo_rfid'    => $row['codigo_rfid'],
                        'fk_ficha'       => $row['fk_ficha'],
                        'estado_aprendiz' => !empty($row['estado_aprendiz']) ? $row['estado_aprendiz'] : 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Obtiene el listado de usuarios filtrando aprendices por instructor si el rol en sesión es Instructor, y aplicando filtros dinámicos
     */
    public static function obtenerTodosConRoles(?string $rolSesion = null, ?int $usuarioIdSesion = null, array $filtros = []): array {
        $usuarios = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $where = [];
                $params = [];

                // Si la persona en sesión es Instructor, ve a todos los aprendices de las fichas/carreras donde esté vinculado (titular, ficha_instructor, ficha_asignatura o horario_bloque) y su propio usuario
                if ($rolSesion === 'Instructor' && $usuarioIdSesion && $usuarioIdSesion > 0) {
                    $where[] = "( (r.nombre_rol = 'Aprendiz' AND (
                        f.fk_usuario = :instId 
                        OR f.id_ficha IN (SELECT fk_ficha FROM ficha_instructor WHERE fk_usuario = :instId)
                        OR f.id_ficha IN (SELECT fk_ficha FROM ficha_asignatura WHERE fk_usuario_instructor = :instId)
                        OR f.id_ficha IN (SELECT fk_ficha FROM horario_bloque WHERE fk_usuario_instructor = :instId)
                    )) OR u.id_usuario = :instSelf )";
                    $params[':instId']   = $usuarioIdSesion;
                    $params[':instSelf'] = $usuarioIdSesion;
                }

                // Filtro por texto de búsqueda (nombre, apellido, identificacion, correo, rfid)
                if (!empty($filtros['q'])) {
                    $where[] = "(u.nombre LIKE :q OR u.apellido LIKE :q OR CONCAT(u.nombre, ' ', u.apellido) LIKE :q OR u.identificacion LIKE :q OR u.nombre_usuario LIKE :q OR a.codigo_rfid LIKE :q)";
                    $params[':q'] = '%' . trim($filtros['q']) . '%';
                }

                // Filtro por Ficha
                if (!empty($filtros['ficha_id'])) {
                    $where[] = "f.id_ficha = :fichaId";
                    $params[':fichaId'] = (int)$filtros['ficha_id'];
                }

                // Filtro por Rol
                if (!empty($filtros['rol'])) {
                    $where[] = "r.nombre_rol = :nombreRol";
                    $params[':nombreRol'] = trim($filtros['rol']);
                }

                // Filtro por Estado de Aprendiz
                if (!empty($filtros['estado'])) {
                    $where[] = "a.estado = :estadoAprendiz";
                    $params[':estadoAprendiz'] = trim($filtros['estado']);
                }

                $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                $sql = "SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.apellido, u.identificacion, u.telefono,
                               u.fk_rol, r.nombre_rol, a.codigo_rfid, a.estado AS estado_aprendiz, f.id_ficha AS numero_ficha, f.nombre_programa
                        FROM usuario u
                        LEFT JOIN rol r ON u.fk_rol = r.id_rol
                        LEFT JOIN aprendiz a ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        {$whereSql}
                        ORDER BY u.id_usuario DESC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $usuarios[] = [
                        'id_usuario'     => $row['id_usuario'],
                        'nombre_usuario' => $row['nombre_usuario'],
                        'nombre'         => trim($row['nombre'] . ' ' . $row['apellido']),
                        'nombre_solo'    => $row['nombre'],
                        'apellido_solo'  => $row['apellido'],
                        'identificacion' => $row['identificacion'],
                        'telefono'       => $row['telefono'],
                        'fk_rol'         => $row['fk_rol'],
                        'rol'            => $row['nombre_rol'] ?? 'Administrador',
                        'codigo_rfid'    => $row['codigo_rfid'] ?? null,
                        'estado_aprendiz' => !empty($row['estado_aprendiz']) ? $row['estado_aprendiz'] : 'Activo',
                        'numero_ficha'   => $row['numero_ficha'] ?? null,
                        'programa'       => $row['nombre_programa'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return $usuarios;
    }

    /**
     * Busca un usuario EXCLUSIVAMENTE en la base de datos MySQL.
     */
    public static function buscarPorUsuarioOEmail(string $identificador): ?array {
        $identificador = trim($identificador);

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if (!$conexion) {
                return null;
            }

            $sql = "SELECT u.*, r.nombre_rol
                    FROM usuario u
                    LEFT JOIN rol r ON u.fk_rol = r.id_rol
                    WHERE LOWER(u.nombre_usuario) = LOWER(:id1) 
                       OR u.identificacion = :id2 LIMIT 1";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([':id1' => $identificador, ':id2' => $identificador]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
                if (empty($nombreCompleto)) {
                    $nombreCompleto = $usuario['nombre_usuario'];
                }

                $rol = $usuario['nombre_rol'] ?? 'Administrador';

                return [
                    'id_usuario'     => $usuario['id_usuario'],
                    'nombre_usuario' => $usuario['nombre_usuario'],
                    'contrasena'     => $usuario['contrasena'],
                    'nombre'         => $nombreCompleto,
                    'correo'         => $usuario['nombre_usuario'],
                    'rol'            => $rol
                ];
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Verifica si la contraseña coincide (password_verify, MD5, SHA1 o texto plano).
     */
    public static function verificarPassword(string $passwordIngresada, string $passwordHash): bool {
        if (password_verify($passwordIngresada, $passwordHash)) {
            return true;
        }
        if (md5($passwordIngresada) === strtolower($passwordHash)) {
            return true;
        }
        if (sha1($passwordIngresada) === strtolower($passwordHash)) {
            return true;
        }
        return $passwordIngresada === $passwordHash;
    }

    /**
     * Elimina un usuario o lo retira si es aprendiz
     */
    public static function eliminarUsuario(int $idUsuario): string {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                // Verificar si el usuario es un Aprendiz
                $stmtCheck = $conexion->prepare("SELECT fk_rol FROM usuario WHERE id_usuario = :id LIMIT 1");
                $stmtCheck->execute([':id' => $idUsuario]);
                $rolId = (int)$stmtCheck->fetchColumn();

                $stmtAprendiz = $conexion->prepare("SELECT id_aprendiz FROM aprendiz WHERE fk_usuario = :id LIMIT 1");
                $stmtAprendiz->execute([':id' => $idUsuario]);
                $esAprendiz = ($rolId === 3 || $stmtAprendiz->fetchColumn());

                if ($esAprendiz) {
                    // NO BORRAR: Marcar al aprendiz como Retirado (Deshabilitado)
                    $stmtUpdate = $conexion->prepare("UPDATE aprendiz SET estado = 'Retirado' WHERE fk_usuario = :id");
                    $stmtUpdate->execute([':id' => $idUsuario]);
                    return 'retirado';
                } else {
                    // Si es otro rol (Instructor, etc.) se elimina
                    $conexion->exec("DELETE FROM aprendiz WHERE fk_usuario = " . (int)$idUsuario);
                    $stmt = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = :id");
                    $stmt->execute([':id' => $idUsuario]);
                    return 'eliminado';
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return 'error';
    }

    /**
     * Reactiva a un aprendiz retirado cambiándolo a estado Activo
     */
    public static function reactivarAprendiz(int $idUsuario): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $stmt = $conexion->prepare("UPDATE aprendiz SET estado = 'Activo' WHERE fk_usuario = :id");
                return $stmt->execute([':id' => $idUsuario]);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Asegura la existencia de la tabla de recuperación de contraseña
     */
    public static function asegurarTablaRecuperacion(): void {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $conexion->exec("CREATE TABLE IF NOT EXISTS recuperacion_contrasena (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fk_usuario INT NOT NULL,
                    correo VARCHAR(150) NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    codigo VARCHAR(6) NOT NULL,
                    expira_en DATETIME NOT NULL,
                    usado TINYINT(1) DEFAULT 0,
                    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_token (token),
                    KEY idx_codigo (codigo),
                    KEY idx_fk_usuario (fk_usuario)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) {
            // Silencioso
        }
    }

    /**
     * Crea un token de recuperación y código numérico para un usuario (válido por 30 minutos)
     */
    public static function crearTokenRecuperacion(int $idUsuario, string $correo): ?array {
        self::asegurarTablaRecuperacion();
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                // Invalidar tokens previos no usados de este usuario
                $stmtOld = $conexion->prepare("UPDATE recuperacion_contrasena SET usado = 1 WHERE fk_usuario = :id");
                $stmtOld->execute([':id' => $idUsuario]);

                $token = bin2hex(random_bytes(32));
                $codigo = (string) random_int(100000, 999999);

                $stmt = $conexion->prepare("INSERT INTO recuperacion_contrasena (fk_usuario, correo, token, codigo, expira_en, usado) 
                    VALUES (:id, :correo, :token, :codigo, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 0)");
                $stmt->execute([
                    ':id'     => $idUsuario,
                    ':correo' => $correo,
                    ':token'  => $token,
                    ':codigo' => $codigo
                ]);

                return ['token' => $token, 'codigo' => $codigo];
            }
        } catch (Exception $e) {
            error_log("Error al crear token de recuperación: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Valida si un token existe, no ha sido usado y aún no expira
     */
    public static function validarTokenRecuperacion(string $token): ?array {
        self::asegurarTablaRecuperacion();
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $stmt = $conexion->prepare("SELECT r.*, u.nombre, u.apellido, u.nombre_usuario 
                    FROM recuperacion_contrasena r
                    JOIN usuario u ON r.fk_usuario = u.id_usuario
                    WHERE r.token = :token AND r.usado = 0 AND r.expira_en > NOW()
                    LIMIT 1");
                $stmt->execute([':token' => $token]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Valida un código numérico de 6 dígitos asociado al correo o identificador del usuario
     */
    public static function validarCodigoRecuperacion(string $correoOIdentificador, string $codigo): ?array {
        self::asegurarTablaRecuperacion();
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $stmt = $conexion->prepare("SELECT r.*, u.nombre, u.apellido, u.nombre_usuario 
                    FROM recuperacion_contrasena r
                    JOIN usuario u ON r.fk_usuario = u.id_usuario
                    WHERE (LOWER(r.correo) = LOWER(:correo) OR u.identificacion = :id)
                      AND r.codigo = :codigo AND r.usado = 0 AND r.expira_en > NOW()
                    ORDER BY r.id DESC LIMIT 1");
                $stmt->execute([
                    ':correo' => trim($correoOIdentificador),
                    ':id'     => trim($correoOIdentificador),
                    ':codigo' => trim($codigo)
                ]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Actualiza la contraseña del usuario y marca el token como usado
     */
    public static function restablecerPasswordConToken(string $token, string $nuevaPassword): bool {
        self::asegurarTablaRecuperacion();
        try {
            $tokenInfo = self::validarTokenRecuperacion($token);
            if (!$tokenInfo) {
                return false;
            }

            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

                $stmtUpdate = $conexion->prepare("UPDATE usuario SET contrasena = :pass WHERE id_usuario = :id");
                $stmtUpdate->execute([
                    ':pass' => $passwordHash,
                    ':id'   => $tokenInfo['fk_usuario']
                ]);

                $stmtMarcar = $conexion->prepare("UPDATE recuperacion_contrasena SET usado = 1 WHERE id = :id");
                $stmtMarcar->execute([':id' => $tokenInfo['id']]);

                return true;
            }
        } catch (Exception $e) {
            error_log("Error al restablecer contraseña: " . $e->getMessage());
        }
        return false;
    }
}
