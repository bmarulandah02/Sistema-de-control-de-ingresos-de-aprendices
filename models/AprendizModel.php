<?php
// ──────────────────────────────────────────────
//  models/AprendizModel.php — Modelo de Aprendices
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class AprendizModel {

    /**
     * Cuenta el total de aprendices registrados en la BD (opcionalmente filtrando por ficha o por instructor)
     */
    public static function contarActivos(?int $idFicha = null, ?int $instructorId = null): int {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                if ($idFicha && $idFicha > 0) {
                    $stmt = $conexion->prepare("SELECT COUNT(*) FROM aprendiz WHERE fk_ficha = :ficha");
                    $stmt->execute([':ficha' => $idFicha]);
                    return (int) $stmt->fetchColumn();
                } else if ($instructorId && $instructorId > 0) {
                    $stmt = $conexion->prepare("SELECT COUNT(*) FROM aprendiz a JOIN ficha f ON a.fk_ficha = f.id_ficha WHERE f.fk_usuario = :instructor");
                    $stmt->execute([':instructor' => $instructorId]);
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
    public static function crearAprendiz(?string $codigoRfid, int $fkFicha, int $fkUsuario, string $estado = 'Activo'): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario, estado) VALUES (:rfid, :ficha, :usuario, :estado)";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':rfid'    => $codigoRfid,
                    ':ficha'   => $fkFicha,
                    ':usuario' => $fkUsuario,
                    ':estado'  => $estado
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return false;
    }

    /**
     * Actualiza o crea los datos de un aprendiz (RFID, Ficha y Estado)
     */
    public static function actualizarAprendiz(?string $codigoRfid, int $fkFicha, int $fkUsuario, string $estado = 'Activo'): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $check = (int) $conexion->query("SELECT COUNT(*) FROM aprendiz WHERE fk_usuario = " . (int)$fkUsuario)->fetchColumn();
                if ($check > 0) {
                    $sql = "UPDATE aprendiz SET codigo_rfid = :rfid, fk_ficha = :ficha, estado = :estado WHERE fk_usuario = :usuario";
                } else {
                    $sql = "INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario, estado) VALUES (:rfid, :ficha, :usuario, :estado)";
                }
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':rfid'    => $codigoRfid,
                    ':ficha'   => $fkFicha,
                    ':usuario' => $fkUsuario,
                    ':estado'  => $estado
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
                $sql = "SELECT a.id_aprendiz, a.codigo_rfid, a.fk_ficha, a.estado AS estado_aprendiz,
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
                        'estado'        => !empty($datos['estado_aprendiz']) ? $datos['estado_aprendiz'] : 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve null si falla
        }

        return null;
    }

    public function obtenerAprendiz($codigo)
    {
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion)
                {
                    $consulta="SELECT a.id_aprendiz, a.fk_ficha 
                               FROM aprendiz a
                               LEFT JOIN usuario u ON a.fk_usuario = u.id_usuario
                               WHERE a.codigo_rfid = :codigo OR u.identificacion = :codigo OR a.id_aprendiz = :codigo 
                               LIMIT 1";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindParam(':codigo',$codigo,pdo::PARAM_STR);
                    $stmt->execute();
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }

        }
        catch(PDOException $excepcion_error){
            error_log("Error en aprendiz Model: ". $excepcion_error->getMessage());
            return false;
        }
        return false;
    }

    public function obtenerAprendizPorId($id_aprendiz_manual)
    {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            
            if ($conexion) {
                $consulta = "SELECT a.id_aprendiz, a.fk_ficha 
                             FROM aprendiz a
                             LEFT JOIN usuario u ON a.fk_usuario = u.id_usuario
                             WHERE a.id_aprendiz = :id OR u.identificacion = :id OR a.codigo_rfid = :id 
                             LIMIT 1";
                $stmt = $conexion->prepare($consulta);
                $stmt->bindParam(':id', $id_aprendiz_manual, PDO::PARAM_STR);
                $stmt->execute();
                
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $excepcion_error) {
            error_log("Error al buscar aprendiz por ID: " . $excepcion_error->getMessage());
            return false;
        }
        return false;
    }
}
?>
