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
                // Verificar si la tabla rol tiene registros, de lo contrario asegurar los 3 roles base
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
            // Roles por defecto
            $roles = [
                ['id_rol' => 1, 'nombre_rol' => 'Administrador'],
                ['id_rol' => 2, 'nombre_rol' => 'Instructor'],
                ['id_rol' => 3, 'nombre_rol' => 'Aprendiz']
            ];
        }
        return $roles;
    }

    /**
     * Verifica si ya existe un usuario con la misma identificación o correo/nombre_usuario
     */
    public static function existeIdentificacionOEmail(string $identificacion, string $email): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT COUNT(*) FROM usuario WHERE identificacion = :ident OR LOWER(nombre_usuario) = LOWER(:email)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':ident' => $identificacion, ':email' => $email]);
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
     * Obtiene el listado de todos los usuarios registrados
     */
    public static function obtenerTodosConRoles(): array {
        $usuarios = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.apellido, u.identificacion, u.telefono,
                               u.fk_rol, r.nombre_rol, a.codigo_rfid, f.id_ficha AS numero_ficha, f.nombre_programa
                        FROM usuario u
                        LEFT JOIN rol r ON u.fk_rol = r.id_rol
                        LEFT JOIN aprendiz a ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        ORDER BY u.id_usuario DESC";

                $stmt = $conexion->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $usuarios[] = [
                        'id_usuario'     => $row['id_usuario'],
                        'nombre_usuario' => $row['nombre_usuario'],
                        'nombre'         => trim($row['nombre'] . ' ' . $row['apellido']),
                        'identificacion' => $row['identificacion'],
                        'telefono'       => $row['telefono'],
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
}
