<?php
// ──────────────────────────────────────────────
//  models/AprendizModel.php — Modelo de Aprendices
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class AprendizModel {

    /**
     * Cuenta el total de aprendices registrados en la BD (opcionalmente filtrando por ficha)
     */
    public static function contarActivos(?int $idFicha = null): int {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                if ($idFicha && $idFicha > 0) {
                    $stmt = $conexion->prepare("SELECT COUNT(*) FROM aprendiz WHERE fk_ficha = :ficha");
                    $stmt->execute([':ficha' => $idFicha]);
                    return (int) $stmt->fetchColumn();
                }
                return (int) $conexion->query("SELECT COUNT(*) FROM aprendiz")->fetchColumn();
            }
        } catch (Exception $e) {
            // Devuelve 0 si falla la conexión
        }

        return 0;
    }

    /**
     * Inserta un nuevo registro en la tabla aprendiz asociando código RFID, ficha y usuario
     */
    public static function crearAprendiz(?int $codigoRfid, int $fkFicha, int $fkUsuario): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario) VALUES (:rfid, :ficha, :usuario)";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':rfid'    => $codigoRfid,
                    ':ficha'   => $fkFicha,
                    ':usuario' => $fkUsuario
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return false;
    }

    /**
     * Actualiza o crea los datos de un aprendiz (RFID y Ficha)
     */
    public static function actualizarAprendiz(?int $codigoRfid, int $fkFicha, int $fkUsuario): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $check = (int) $conexion->query("SELECT COUNT(*) FROM aprendiz WHERE fk_usuario = " . (int)$fkUsuario)->fetchColumn();
                if ($check > 0) {
                    $sql = "UPDATE aprendiz SET codigo_rfid = :rfid, fk_ficha = :ficha WHERE fk_usuario = :usuario";
                } else {
                    $sql = "INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario) VALUES (:rfid, :ficha, :usuario)";
                }
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':rfid'    => $codigoRfid,
                    ':ficha'   => $fkFicha,
                    ':usuario' => $fkUsuario
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return false;
    }

    /**
     * Obtiene la información del perfil del aprendiz por su fk_usuario
     */
    public static function obtenerPorUsuarioId(int $usuarioId): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT a.id_aprendiz, a.codigo_rfid, a.fk_ficha,
                               u.id_usuario, u.nombre, u.apellido, u.identificacion, u.telefono, u.nombre_usuario AS correo,
                               f.id_ficha AS numero_ficha, f.nombre_programa AS programa
                        FROM aprendiz a
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        WHERE a.fk_usuario = :id LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([':id' => $usuarioId]);
                $datos = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($datos) {
                    return [
                        'id_aprendiz'   => $datos['id_aprendiz'],
                        'nombre'        => trim($datos['nombre'] . ' ' . $datos['apellido']),
                        'documento'     => $datos['identificacion'],
                        'telefono'      => $datos['telefono'],
                        'correo'        => $datos['correo'],
                        'numero_ficha'  => $datos['numero_ficha'] ?? 'N/A',
                        'programa'      => $datos['programa'] ?? 'Sin programa',
                        'estado'        => 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve null si falla
        }

        return null;
    }
}
