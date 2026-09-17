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
        try {
            $conexion->exec("ALTER TABLE ficha ADD COLUMN estado VARCHAR(20) DEFAULT 'Activo'");
        } catch (Exception $e) {}
    }

    /**
     * Retorna el catálogo de bloques u horarios separados según la jornada
     */
    public static function obtenerBloquesPorJornada(string $jornada = 'Mañana'): array {
        $jornadaNorm = mb_strtolower(trim($jornada));

        if (str_contains($jornadaNorm, 'mañana') || str_contains($jornadaNorm, 'diurna')) {
            return [
                'completa' => ['id' => '6-12', 'nombre' => 'Jornada Completa (06:00 - 12:00)', 'entrada' => '06:00:00', 'salida' => '12:00:00'],
                'bloque1'   => ['id' => '6-9',  'nombre' => 'Bloque 1 (06:00 - 09:00)',        'entrada' => '06:00:00', 'salida' => '09:00:00'],
                'bloque2'   => ['id' => '9-12', 'nombre' => 'Bloque 2 (09:00 - 12:00)',        'entrada' => '09:00:00', 'salida' => '12:00:00'],
            ];
        } elseif (str_contains($jornadaNorm, 'tarde') || str_contains($jornadaNorm, 'vespertina')) {
            return [
                'completa' => ['id' => '12-18', 'nombre' => 'Jornada Completa (12:00 - 18:00)', 'entrada' => '12:00:00', 'salida' => '18:00:00'],
                'bloque1'   => ['id' => '12-15', 'nombre' => 'Bloque 1 (12:00 - 15:00)',        'entrada' => '12:00:00', 'salida' => '15:00:00'],
                'bloque2'   => ['id' => '15-18', 'nombre' => 'Bloque 2 (15:00 - 18:00)',        'entrada' => '15:00:00', 'salida' => '18:00:00'],
            ];
        } elseif (str_contains($jornadaNorm, 'noche') || str_contains($jornadaNorm, 'nocturna')) {
            return [
                'completa' => ['id' => '18-22', 'nombre' => 'Jornada Completa (18:00 - 22:00)', 'entrada' => '18:00:00', 'salida' => '22:00:00'],
                'bloque1'   => ['id' => '18-20', 'nombre' => 'Bloque 1 (18:00 - 20:00)',        'entrada' => '18:00:00', 'salida' => '20:00:00'],
                'bloque2'   => ['id' => '20-22', 'nombre' => 'Bloque 2 (20:00 - 22:00)',        'entrada' => '20:00:00', 'salida' => '22:00:00'],
            ];
        } else {
            return [
                'completa' => ['id' => '7-17',  'nombre' => 'Jornada Completa (07:00 - 17:00)', 'entrada' => '07:00:00', 'salida' => '17:00:00'],
                'bloque1'   => ['id' => '7-12',  'nombre' => 'Bloque 1 (07:00 - 12:00)',        'entrada' => '07:00:00', 'salida' => '12:00:00'],
                'bloque2'   => ['id' => '12-17', 'nombre' => 'Bloque 2 (12:00 - 17:00)',        'entrada' => '12:00:00', 'salida' => '17:00:00'],
            ];
        }
    }

    /**
     * Devuelve la lista consolidada de todos los bloques posibles para seleccionar en la terminal
     */
    public static function obtenerTodosLosBloques(): array {
        return [
            'Mañana (Diurna)' => self::obtenerBloquesPorJornada('Mañana'),
            'Tarde (Vespertina)' => self::obtenerBloquesPorJornada('Tarde'),
            'Noche (Nocturna)' => self::obtenerBloquesPorJornada('Noche'),
            'Mixta / Especial' => self::obtenerBloquesPorJornada('Mixta'),
        ];
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
    /**
     * Obtiene el listado de fichas registradas en la base de datos con filtros dinámicos
     */
    public static function obtenerTodasFichas(array $filtros = []): array {
        $fichas = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $where = [];
                $params = [];

                if (!empty($filtros['q'])) {
                    $where[] = "(f.id_ficha LIKE :q OR f.nombre_programa LIKE :q OR u.nombre LIKE :q OR u.apellido LIKE :q OR CONCAT(u.nombre, ' ', u.apellido) LIKE :q OR u.identificacion LIKE :q)";
                    $params[':q'] = '%' . trim($filtros['q']) . '%';
                }

                $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                $sql = "SELECT f.id_ficha, f.nombre_programa, f.jornada, f.fk_usuario AS instructor_id,
                               f.fecha_inicio, f.fecha_fin, f.estado,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor,
                               u.identificacion AS instructor_identificacion,
                               u.nombre_usuario AS instructor_correo,
                               (SELECT COUNT(*) FROM aprendiz a WHERE a.fk_ficha = f.id_ficha) AS total_aprendices
                        FROM ficha f
                        LEFT JOIN usuario u ON f.fk_usuario = u.id_usuario
                        {$whereSql}
                        ORDER BY f.id_ficha DESC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fichas[] = [
                        'id'                       => $row['id_ficha'],
                        'numero_ficha'             => $row['id_ficha'],
                        'programa'                 => $row['nombre_programa'],
                        'jornada'                  => $row['jornada'] ?: 'Diurna',
                        'instructor_id'            => $row['instructor_id'],
                        'instructor'               => !empty(trim($row['instructor'])) ? $row['instructor'] : 'Por asignar',
                        'instructor_identificacion'=> $row['instructor_identificacion'] ?? '—',
                        'instructor_correo'        => $row['instructor_correo'] ?? '—',
                        'total_aprendices'         => (int) $row['total_aprendices'],
                        'fecha_inicio'             => (!empty($row['fecha_inicio']) && $row['fecha_inicio'] !== '0000-00-00') ? $row['fecha_inicio'] : '—',
                        'fecha_fin'                => (!empty($row['fecha_fin']) && $row['fecha_fin'] !== '0000-00-00') ? $row['fecha_fin'] : '—',
                        'estado'                   => $row['estado'] ?: 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $fichas;
    }

    /**
     * Obtiene el listado de fichas pertenecientes a un instructor específico
     */
    public static function obtenerFichasPorInstructor(int $instructorId, array $filtros = []): array {
        $fichas = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $where = ["f.fk_usuario = :instructorId"];
                $params = [':instructorId' => $instructorId];

                if (!empty($filtros['q'])) {
                    $where[] = "(f.id_ficha LIKE :q OR f.nombre_programa LIKE :q)";
                    $params[':q'] = '%' . trim($filtros['q']) . '%';
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $sql = "SELECT f.id_ficha, f.nombre_programa, f.jornada, f.fk_usuario AS instructor_id,
                               f.fecha_inicio, f.fecha_fin, f.estado,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor,
                               u.identificacion AS instructor_identificacion,
                               u.nombre_usuario AS instructor_correo,
                               (SELECT COUNT(*) FROM aprendiz a WHERE a.fk_ficha = f.id_ficha) AS total_aprendices
                        FROM ficha f
                        LEFT JOIN usuario u ON f.fk_usuario = u.id_usuario
                        {$whereSql}
                        ORDER BY f.id_ficha DESC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fichas[] = [
                        'id'                       => $row['id_ficha'],
                        'numero_ficha'             => $row['id_ficha'],
                        'programa'                 => $row['nombre_programa'],
                        'jornada'                  => $row['jornada'] ?: 'Diurna',
                        'instructor_id'            => $row['instructor_id'],
                        'instructor'               => !empty(trim($row['instructor'])) ? $row['instructor'] : 'Por asignar',
                        'instructor_identificacion'=> $row['instructor_identificacion'] ?? '—',
                        'instructor_correo'        => $row['instructor_correo'] ?? '—',
                        'total_aprendices'         => (int) $row['total_aprendices'],
                        'fecha_inicio'             => (!empty($row['fecha_inicio']) && $row['fecha_inicio'] !== '0000-00-00') ? $row['fecha_inicio'] : '—',
                        'fecha_fin'                => (!empty($row['fecha_fin']) && $row['fecha_fin'] !== '0000-00-00') ? $row['fecha_fin'] : '—',
                        'estado'                   => $row['estado'] ?: 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
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
                               f.fecha_inicio, f.fecha_fin, f.estado,
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
                        'estado'        => $row['estado'] ?: 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return null;
    }

    /**
     * Guarda o actualiza el horario asignado a una ficha en la tabla horario de MySQL según su jornada
     */
    private static function guardarHorarioPredeterminado($conexion, int $idFicha, string $jornada): void {
        try {
            $fechaActual = date('Y-m-d');
            switch ($jornada) {
                case 'Tarde':
                case 'Vespertina':
                    $horaEntrada = '12:00:00';
                    $horaSalida  = '18:00:00';
                    break;
                case 'Noche':
                case 'Nocturna':
                    $horaEntrada = '18:00:00';
                    $horaSalida  = '22:00:00';
                    break;
                case 'Mixta':
                    $horaEntrada = '07:00:00';
                    $horaSalida  = '17:00:00';
                    break;
                case 'Mañana':
                case 'Diurna':
                default:
                    $horaEntrada = '06:00:00';
                    $horaSalida  = '12:00:00';
                    break;
            }

            $entradaDT = $fechaActual . ' ' . $horaEntrada;
            $salidaDT  = $fechaActual . ' ' . $horaSalida;

            // Verificar si ya existe un registro en la tabla horario para esta ficha
            $stmtCheck = $conexion->prepare("SELECT id_horario FROM horario WHERE fk_ficha = :ficha ORDER BY id_horario DESC LIMIT 1");
            $stmtCheck->execute([':ficha' => $idFicha]);
            $idHorarioExistente = $stmtCheck->fetchColumn();

            if ($idHorarioExistente) {
                $stmtUpdate = $conexion->prepare("UPDATE horario SET entrada = :entrada, salida = :salida WHERE id_horario = :id");
                $stmtUpdate->execute([
                    ':entrada' => $entradaDT,
                    ':salida'  => $salidaDT,
                    ':id'      => $idHorarioExistente
                ]);
            } else {
                $stmtInsert = $conexion->prepare("INSERT INTO horario (entrada, salida, fk_ficha) VALUES (:entrada, :salida, :ficha)");
                $stmtInsert->execute([
                    ':entrada' => $entradaDT,
                    ':salida'  => $salidaDT,
                    ':ficha'   => $idFicha
                ]);
            }
        } catch (Exception $e) {
            error_log("Error al guardar horario en BD: " . $e->getMessage());
        }
    }

    /**
     * Inserta una nueva ficha en la base de datos guardando fechas de inicio/fin y creando su horario en la BD
     */
    public static function crearFicha(array $datos): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);

                $sql = "INSERT INTO ficha (id_ficha, nombre_programa, jornada, fk_usuario, fecha_inicio, fecha_fin, estado)
                        VALUES (:id_ficha, :nombre_programa, :jornada, :fk_usuario, :fecha_inicio, :fecha_fin, :estado)";
                $stmt = $conexion->prepare($sql);
                $exito = $stmt->execute([
                    ':id_ficha'        => (int) $datos['numero_ficha'],
                    ':nombre_programa' => $datos['programa'],
                    ':jornada'         => $datos['jornada'] ?? 'Mañana',
                    ':fk_usuario'      => (int) $datos['instructor_id'],
                    ':fecha_inicio'    => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null,
                    ':fecha_fin'       => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null,
                    ':estado'          => $datos['estado'] ?? 'Activo'
                ]);

                if ($exito) {
                    self::guardarHorarioPredeterminado($conexion, (int) $datos['numero_ficha'], $datos['jornada'] ?? 'Mañana');
                }

                return $exito;
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Actualiza una ficha existente guardando fechas de inicio/fin, estado y su horario en la BD
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
                            fecha_fin = :fecha_fin,
                            estado = :estado
                        WHERE id_ficha = :id_ficha";
                $stmt = $conexion->prepare($sql);
                $exito = $stmt->execute([
                    ':nombre_programa' => $datos['programa'],
                    ':jornada'         => $datos['jornada'] ?? 'Mañana',
                    ':fk_usuario'      => (int) $datos['instructor_id'],
                    ':fecha_inicio'    => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null,
                    ':fecha_fin'       => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null,
                    ':estado'          => $datos['estado'] ?? 'Activo',
                    ':id_ficha'        => (int) $datos['numero_ficha']
                ]);

                if ($exito) {
                    self::guardarHorarioPredeterminado($conexion, (int) $datos['numero_ficha'], $datos['jornada'] ?? 'Mañana');
                }

                return $exito;
            }
        } catch (Exception $e) {
            // Silencioso
        }
        return false;
    }

    /**
     * Elimina una ficha por su ID y borra sus horarios asociados
     */
    public static function eliminarFicha(int $idFicha): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $conexion->exec("DELETE FROM horario WHERE fk_ficha = " . (int)$idFicha);
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
            $consulta = "SELECT entrada, salida FROM horario 
                         WHERE fk_ficha = :identificadorFicha 
                         AND (DATE(entrada) = :fechaActual OR entrada IS NOT NULL)
                         ORDER BY id_horario DESC LIMIT 1";
                         
            $stmt = $conexion->prepare($consulta);
            
            $stmt->bindParam(':identificadorFicha', $identificadorFicha, PDO::PARAM_INT);
            $stmt->bindParam(':fechaActual', $fechaActual, PDO::PARAM_STR);
            
            $stmt->execute();
            $horario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fallback 1: Buscar cualquier horario registrado para la ficha
            if (!$horario) {
                $stmtFallback = $conexion->prepare("SELECT entrada, salida FROM horario WHERE fk_ficha = :identificadorFicha ORDER BY id_horario DESC LIMIT 1");
                $stmtFallback->bindParam(':identificadorFicha', $identificadorFicha, PDO::PARAM_INT);
                $stmtFallback->execute();
                $horario = $stmtFallback->fetch(PDO::FETCH_ASSOC);
            }

            // Fallback 2: Si no hay registros en la tabla horario, obtener el horario según la jornada de la ficha
            if (!$horario) {
                $stmtJornada = $conexion->prepare("SELECT jornada FROM ficha WHERE id_ficha = :identificadorFicha LIMIT 1");
                $stmtJornada->bindParam(':identificadorFicha', $identificadorFicha, PDO::PARAM_INT);
                $stmtJornada->execute();
                $ficha = $stmtJornada->fetch(PDO::FETCH_ASSOC);

                $jornada = $ficha['jornada'] ?? 'Mañana';
                switch ($jornada) {
                    case 'Tarde':
                    case 'Vespertina':
                        $horario = ['entrada' => '12:00:00', 'salida' => '18:00:00'];
                        break;
                    case 'Noche':
                    case 'Nocturna':
                        $horario = ['entrada' => '18:00:00', 'salida' => '22:00:00'];
                        break;
                    case 'Mixta':
                        $horario = ['entrada' => '07:00:00', 'salida' => '17:00:00'];
                        break;
                    case 'Mañana':
                    case 'Diurna':
                    default:
                        $horario = ['entrada' => '06:00:00', 'salida' => '12:00:00'];
                        break;
                }
            }

            return $horario;
        }
    }catch(PDOException $e)
    {
        error_log("Error en horarioModel: ". $e->getMessage());
        return ['entrada' => '07:00:00', 'salida' => '18:00:00'];
    }
    return ['entrada' => '07:00:00', 'salida' => '18:00:00'];
}
}
?>