<?php
// ──────────────────────────────────────────────
//  models/UsuarioModel.php — Modelo de Autenticación
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

            // Consulta flexible por nombre_usuario o identificación
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

            // Fallback para tabla admin antigua si no existe fk_rol
            $sqlOld = "SELECT u.id_usuario, u.nombre_usuario, u.contrasena, 
                              COALESCE(CONCAT(a.nombre, ' ', a.apellido), u.nombre_usuario) AS nombre_completo
                       FROM usuario u
                       LEFT JOIN admin a ON u.fk_admin = a.id_admin
                       WHERE LOWER(u.nombre_usuario) = LOWER(:id) LIMIT 1";
            $stmtOld = $conexion->prepare($sqlOld);
            $stmtOld->execute([':id' => $identificador]);
            $usuarioOld = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if ($usuarioOld) {
                return [
                    'id_usuario'     => $usuarioOld['id_usuario'],
                    'nombre_usuario' => $usuarioOld['nombre_usuario'],
                    'contrasena'     => $usuarioOld['contrasena'],
                    'nombre'         => $usuarioOld['nombre_completo'],
                    'correo'         => $usuarioOld['nombre_usuario'],
                    'rol'            => 'Administrador'
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
        // 1. Hash con BCRYPT / Argon2 (password_verify)
        if (password_verify($passwordIngresada, $passwordHash)) {
            return true;
        }
        // 2. Hash MD5
        if (md5($passwordIngresada) === strtolower($passwordHash)) {
            return true;
        }
        // 3. Hash SHA1
        if (sha1($passwordIngresada) === strtolower($passwordHash)) {
            return true;
        }
        // 4. Comparación directa (Texto Plano)
        return $passwordIngresada === $passwordHash;
    }
}
