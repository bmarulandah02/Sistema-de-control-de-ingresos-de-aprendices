<?php
// ──────────────────────────────────────────────
//  models/HorarioModel.php — Modelo de Fichas y Horarios
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class HorarioModel {

    /**
     * Asegura que las columnas fecha_inicio y fecha_fin existan en la tabla ficha
     */
    private static function asegurarColumnasFechas($conexion): void {
        try {
            $conexion->exec("ALTER TABLE ficha ADD COLUMN fecha_inicio DATE NULL");
        } catch (Exception $e) {}
        try {
            $conexion->exec("ALTER TABLE ficha ADD COLUMN fecha_fin DATE NULL");
        } catch (Exception $e) {}
    }

    /**
     * Obtiene el listado de Instructores registrados en la base de datos para el combo box
     */
    public static function obtenerInstructores(): array {
        $instructores = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT u.id_usuario AS id, CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.identificacion
                        FROM usuario u
                        LEFT JOIN rol r ON u.fk_rol = r.id_rol
                        WHERE r.nombre_rol = 'Instructor' OR u.fk_rol = 2
                        ORDER BY u.nombre ASC";

                $stmt = $conexion->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $instructores[] = [
                        'id'     => $row['id'],
                        'nombre' => trim($row['nombre']) ?: 'Usuario #' . $row['id']
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return $instructores;
    }

    /**
     * Obtiene el listado de fichas registradas en la base de datos
     */
    public static function obtenerTodasFichas(): array {
        $fichas = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $sql = "SELECT f.id_ficha, f.nombre_programa, f.jornada, f.fk_usuario AS instructor_id,
                               f.fecha_inicio, f.fecha_fin,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor,
                               (SELECT COUNT(*) FROM aprendiz a WHERE a.fk_ficha = f.id_ficha) AS total_aprendices
                        FROM ficha f
                        LEFT JOIN usuario u ON f.fk_usuario = u.id_usuario
                        ORDER BY f.id_ficha DESC";

                $stmt = $conexion->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fichas[] = [
                        'id'               => $row['id_ficha'],
                        'numero_ficha'     => $row['id_ficha'],
                        'programa'         => $row['nombre_programa'],
                        'jornada'          => $row['jornada'] ?: 'Diurna',
                        'instructor_id'    => $row['instructor_id'],
                        'instructor'       => !empty(trim($row['instructor'])) ? $row['instructor'] : 'Por asignar',
                        'total_aprendices' => (int) $row['total_aprendices'],
                        'fecha_inicio'     => (!empty($row['fecha_inicio']) && $row['fecha_inicio'] !== '0000-00-00') ? $row['fecha_inicio'] : '—',
                        'fecha_fin'        => (!empty($row['fecha_fin']) && $row['fecha_fin'] !== '0000-00-00') ? $row['fecha_fin'] : '—',
                        'estado'           => 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $fichas;
    }

    /**
     * Obtiene los datos de una ficha específica por su ID/Número
     */
    public static function obtenerFichaPorId(int $idFicha): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $sql = "SELECT f.id_ficha, f.nombre_programa, f.jornada, f.fk_usuario AS instructor_id,
                               f.fecha_inicio, f.fecha_fin,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor
                        FROM ficha f
                        LEFT JOIN usuario u ON f.fk_usuario = u.id_usuario
                        WHERE f.id_ficha = :id LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([':id' => $idFicha]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    return [
                        'id'            => $row['id_ficha'],
                        'numero_ficha'  => $row['id_ficha'],
                        'programa'      => $row['nombre_programa'],
                        'jornada'       => $row['jornada'],
                        'instructor_id' => $row['instructor_id'],
                        'instructor'    => $row['instructor'],
                        'fecha_inicio'  => $row['fecha_inicio'],
                        'fecha_fin'     => $row['fecha_fin'],
                        'estado'        => 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Inserta una nueva ficha en la base de datos guardando fechas de inicio y fin
     */
    public static function crearFicha(array $datos): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $sql = "INSERT INTO ficha (id_ficha, nombre_programa, jornada, fk_usuario, fecha_inicio, fecha_fin)
                        VALUES (:id_ficha, :nombre_programa, :jornada, :fk_usuario, :fecha_inicio, :fecha_fin)";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':id_ficha'        => (int) $datos['numero_ficha'],
                    ':nombre_programa' => $datos['programa'],
                    ':jornada'         => $datos['jornada'] ?? 'Mañana',
                    ':fk_usuario'      => (int) $datos['instructor_id'],
                    ':fecha_inicio'    => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null,
                    ':fecha_fin'       => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Actualiza una ficha existente guardando fechas de inicio y fin
     */
    public static function actualizarFicha(array $datos): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $sql = "UPDATE ficha 
                        SET nombre_programa = :nombre_programa, 
                            jornada = :jornada, 
                            fk_usuario = :fk_usuario,
                            fecha_inicio = :fecha_inicio,
                            fecha_fin = :fecha_fin
                        WHERE id_ficha = :id_ficha";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':nombre_programa' => $datos['programa'],
                    ':jornada'         => $datos['jornada'] ?? 'Mañana',
                    ':fk_usuario'      => (int) $datos['instructor_id'],
                    ':fecha_inicio'    => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null,
                    ':fecha_fin'       => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null,
                    ':id_ficha'        => (int) $datos['numero_ficha']
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Elimina una ficha por su ID
     */
    public static function eliminarFicha(int $idFicha): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "DELETE FROM ficha WHERE id_ficha = :id";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([':id' => $idFicha]);
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

public function obtenerHorarioFicha($identificadorFicha, $fechaActual)
{
    $identificadorFicha = intval($identificadorFicha);

    if($identificadorFicha <= 0 || empty($fechaActual))
    {
        return false;
    }

    try{
        $mysql = new MySQL();
        $mysql->conectarBD();
        $conexion = $mysql->getConexion();
        if($conexion)
        {
            // Marcadores :identificadorFicha y :fechaActual
            $consulta = "SELECT entrada, salida FROM horario 
                         WHERE fk_ficha = :identificadorFicha 
                         AND DATE(fecha) = :fechaActual 
                         LIMIT 1";
                         
            $stmt = $conexion->prepare($consulta);
            
            // Enlazamos exactamente los mismos nombres
            $stmt->bindParam(':identificadorFicha', $identificadorFicha, PDO::PARAM_INT);
            $stmt->bindParam(':fechaActual', $fechaActual, PDO::PARAM_STR);
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }catch(PDOException $e)
    {
        error_log("Error en horarioModel: ". $e->getMessage());
        return false;
    }
    return false;
}
}
?>