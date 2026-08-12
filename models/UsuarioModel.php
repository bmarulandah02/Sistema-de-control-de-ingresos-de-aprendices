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
                               u.fk_rol, r.nombre_rol, a.codigo_rfid, a.fk_ficha
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
                        'fk_ficha'       => $row['fk_ficha']
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Obtiene el listado de usuarios filtrando Administradores si el rol en sesión es Instructor
     */
    public static function obtenerTodosConRoles(?string $rolSesion = null): array {
        $usuarios = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $where = "";
                // Si la persona en sesión es Instructor, OCULTAR los Administradores
                if ($rolSesion === 'Instructor') {
                    $where = "WHERE r.nombre_rol != 'Administrador' AND u.fk_rol != 1";
                }

                $sql = "SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.apellido, u.identificacion, u.telefono,
                               u.fk_rol, r.nombre_rol, a.codigo_rfid, f.id_ficha AS numero_ficha, f.nombre_programa
                        FROM usuario u
                        LEFT JOIN rol r ON u.fk_rol = r.id_rol
                        LEFT JOIN aprendiz a ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        {$where}
                        ORDER BY u.id_usuario DESC";

                $stmt = $conexion->query($sql);
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
     * Elimina un usuario por su ID (y sus registros asociados)
     */
    public static function eliminarUsuario(int $idUsuario): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $conexion->exec("DELETE FROM aprendiz WHERE fk_usuario = " . (int)$idUsuario);
                $stmt = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = :id");
                return $stmt->execute([':id' => $idUsuario]);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }
}
