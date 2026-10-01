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

                if (!empty($filtros['estado'])) {
                    if ($filtros['estado'] === 'Activo') {
                        $where[] = "(f.estado = 'Activo' OR f.estado IS NULL OR f.estado = '')";
                    } elseif ($filtros['estado'] === 'Finalizado') {
                        $where[] = "f.estado IN ('Finalizado', 'Inactivo')";
                    }
                } else {
                    $where[] = "(f.estado = 'Activo' OR f.estado IS NULL OR f.estado = '')";
                }

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
     * Obtiene el listado de fichas pertenecientes o asignadas a un instructor específico
     */
    public static function obtenerFichasPorInstructor(int $instructorId, array $filtros = []): array {
        $fichas = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);
                self::asegurarTablaFichaAsignatura($conexion);

                $where = ["(f.fk_usuario = :instructorId OR f.id_ficha IN (SELECT fk_ficha FROM ficha_asignatura WHERE fk_usuario_instructor = :instructorId))"];
                $params = [':instructorId' => $instructorId];

                if (!empty($filtros['estado'])) {
                    if ($filtros['estado'] === 'Activo') {
                        $where[] = "(f.estado = 'Activo' OR f.estado IS NULL OR f.estado = '')";
                    } elseif ($filtros['estado'] === 'Finalizado') {
                        $where[] = "f.estado IN ('Finalizado', 'Inactivo')";
                    }
                } else {
                    $where[] = "(f.estado = 'Activo' OR f.estado IS NULL OR f.estado = '')";
                }

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
     * Obtiene los datos de una ficha específica por su ID/Número (incluyendo sus asignaturas/instructores)
     */
    public static function obtenerFichaPorId(int $idFicha): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnasFechas($conexion);
                self::asegurarTablaFichaAsignatura($conexion);

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
                        'estado'        => $row['estado'] ?: 'Activo',
                        'asignaturas'   => self::obtenerAsignaturasPorFicha($idFicha)
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
     * Cuenta el número de aprendices asociados a una ficha
     */
    public static function contarAprendicesEnFicha(int $idFicha): int {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $stmt = $conexion->prepare("SELECT COUNT(*) FROM aprendiz WHERE fk_ficha = :id");
                $stmt->execute([':id' => $idFicha]);
                return (int) $stmt->fetchColumn();
            }
        } catch (Exception $e) {}
        return 0;
    }

    /**
     * Finaliza (oculta/soft-delete) una ficha por su ID cambiando su estado a Finalizado
     */
    public static function finalizarFicha(int $idFicha): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $sql = "UPDATE ficha SET estado = 'Finalizado' WHERE id_ficha = :id";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([':id' => $idFicha]);
            }
        } catch (Exception $e) {}
        return false;
    }

    /**
     * Reactiva una ficha cambiándola a estado Activo
     */
    public static function reactivarFicha(int $idFicha): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $sql = "UPDATE ficha SET estado = 'Activo' WHERE id_ficha = :id";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([':id' => $idFicha]);
            }
        } catch (Exception $e) {}
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
    } catch (PDOException $e) {
        error_log("Error en horarioModel: " . $e->getMessage());
        return ['entrada' => '07:00:00', 'salida' => '18:00:00'];
    }
    return ['entrada' => '07:00:00', 'salida' => '18:00:00'];
}

    /**
     * Asegura la creación de la tabla ficha_asignatura para soportar múltiples instructores y materias
     */
    private static function asegurarTablaFichaAsignatura($conexion): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS ficha_asignatura (
                id_ficha_asignatura INT AUTO_INCREMENT PRIMARY KEY,
                fk_ficha INT NOT NULL,
                fk_usuario_instructor INT NOT NULL,
                nombre_asignatura VARCHAR(100) NOT NULL,
                tipo VARCHAR(30) DEFAULT 'Técnica',
                UNIQUE KEY uq_fa_ficha_materia_instructor (fk_ficha, fk_usuario_instructor, nombre_asignatura),
                KEY fk_fa_ficha (fk_ficha),
                KEY fk_fa_usuario (fk_usuario_instructor)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $conexion->exec($sql);
        } catch (Exception $e) {}
    }

    /**
     * Obtiene el listado de asignaturas registradas para una ficha específica (con opción de filtrar por instructor)
     * Deduplica resultados para evitar asignaturas repetidas.
     */
    public static function obtenerAsignaturasPorFicha(int $idFicha, ?int $soloInstructorId = null): array {
        $asignaturas = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablaFichaAsignatura($conexion);

                $where = ["fa.fk_ficha = :idFicha"];
                $params = [':idFicha' => $idFicha];

                if ($soloInstructorId !== null && $soloInstructorId > 0) {
                    $where[] = "fa.fk_usuario_instructor = :instructorId";
                    $params[':instructorId'] = $soloInstructorId;
                }

                $whereSql = implode(" AND ", $where);
                $sql = "SELECT MIN(fa.id_ficha_asignatura) AS id_ficha_asignatura,
                               fa.fk_ficha,
                               fa.fk_usuario_instructor,
                               fa.nombre_asignatura,
                               MAX(fa.tipo) AS tipo,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre,
                               u.nombre_usuario AS instructor_correo
                        FROM ficha_asignatura fa
                        LEFT JOIN usuario u ON fa.fk_usuario_instructor = u.id_usuario
                        WHERE {$whereSql}
                        GROUP BY fa.fk_ficha, fa.fk_usuario_instructor, fa.nombre_asignatura, u.nombre, u.apellido, u.nombre_usuario
                        ORDER BY tipo ASC, fa.nombre_asignatura ASC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                $asignaturas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Exception $e) {}
        return $asignaturas;
    }

    /**
     * Obtiene la clase y el instructor programados en el horario para la fecha y hora actuales (o especificadas)
     */
    public static function obtenerClaseDelMomento(?int $idFicha = null, ?string $fecha = null, ?string $hora = null): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablasHorario($conexion);
                $fecha = $fecha ?? date('Y-m-d');
                $hora  = $hora ?? date('H:i:s');

                // 1. Buscar coincidencia exacta de hora dentro del bloque
                $sql = "SELECT hb.*,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre,
                               u.nombre_usuario AS instructor_correo
                        FROM horario_bloque hb
                        LEFT JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                        WHERE hb.fecha = :fecha
                          AND (
                            (hb.hora_inicio <= :hora AND hb.hora_fin >= :hora)
                            OR :hora BETWEEN hb.hora_inicio AND hb.hora_fin
                          )";
                $params = [':fecha' => $fecha, ':hora' => $hora];
                if (!empty($idFicha)) {
                    $sql .= " AND hb.fk_ficha = :ficha";
                    $params[':ficha'] = $idFicha;
                }
                $sql .= " ORDER BY hb.hora_inicio ASC LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res) {
                    $res['es_hora_exacta'] = true;
                    return $res;
                }

                // 2. Si no coincide con la hora exacta pero hoy hay clase programada en la fecha
                $sqlHoy = "SELECT hb.*,
                                  CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre,
                                  u.nombre_usuario AS instructor_correo
                           FROM horario_bloque hb
                           LEFT JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                           WHERE hb.fecha = :fecha";
                $paramsHoy = [':fecha' => $fecha];
                if (!empty($idFicha)) {
                    $sqlHoy .= " AND hb.fk_ficha = :ficha";
                    $paramsHoy[':ficha'] = $idFicha;
                }
                $sqlHoy .= " ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(COALESCE(hb.hora_inicio, '06:00:00'), :hora))) ASC LIMIT 1";
                $paramsHoy[':hora'] = $hora;

                $stmtHoy = $conexion->prepare($sqlHoy);
                $stmtHoy->execute($paramsHoy);
                $resHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
                if ($resHoy) {
                    $resHoy['es_hora_exacta'] = false;
                    return $resHoy;
                }
            }
        } catch (Exception $e) {}
        return null;
    }

    /**
     * Guarda / actualiza las asignaturas e instructores asignados a una ficha
     */
    public static function guardarAsignaturasFicha(int $idFicha, array $asignaturas): void {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablaFichaAsignatura($conexion);

                // Eliminar asignaturas previas
                $stmtDel = $conexion->prepare("DELETE FROM ficha_asignatura WHERE fk_ficha = :idFicha");
                $stmtDel->execute([':idFicha' => $idFicha]);

                // Insertar nuevas
                $stmtIns = $conexion->prepare("INSERT INTO ficha_asignatura (fk_ficha, fk_usuario_instructor, nombre_asignatura, tipo) VALUES (:fk_ficha, :fk_usuario_instructor, :nombre_asignatura, :tipo)");
                foreach ($asignaturas as $asig) {
                    $nombre = trim($asig['nombre_asignatura'] ?? '');
                    $instructorId = (int)($asig['fk_usuario_instructor'] ?? $asig['instructor_id'] ?? 0);
                    $tipo = trim($asig['tipo'] ?? 'Técnica');
                    if (!empty($nombre) && $instructorId > 0) {
                        $stmtIns->execute([
                            ':fk_ficha' => $idFicha,
                            ':fk_usuario_instructor' => $instructorId,
                            ':nombre_asignatura' => $nombre,
                            ':tipo' => $tipo
                        ]);
                    }
                }
            }
        } catch (Exception $e) {}
    }

    /**
     * Obtiene todas las asignaturas asignadas a un instructor para sus distintas fichas
     */
    public static function obtenerAsignaturasParaInstructor(int $instructorId): array {
        $lista = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablaFichaAsignatura($conexion);
                $sql = "SELECT fa.nombre_asignatura, fa.tipo, f.id_ficha, f.nombre_programa, f.jornada
                        FROM ficha_asignatura fa
                        INNER JOIN ficha f ON fa.fk_ficha = f.id_ficha
                        WHERE fa.fk_usuario_instructor = :instructorId
                        ORDER BY f.id_ficha DESC, fa.nombre_asignatura ASC";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':instructorId' => $instructorId]);
                $lista = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Exception $e) {}
        return $lista;
    }

    /**
     * Asegura la creación de las tablas ficha_instructor y horario_bloque
     */
    public static function asegurarTablasHorario($conexion = null): void {
        $cerrar = false;
        if (!$conexion) {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            $cerrar = true;
        }
        if (!$conexion) return;

        try {
            $sql1 = "CREATE TABLE IF NOT EXISTS ficha_instructor (
                fk_ficha INT NOT NULL,
                fk_usuario INT NOT NULL,
                PRIMARY KEY (fk_ficha, fk_usuario),
                KEY idx_fi_usuario (fk_usuario),
                CONSTRAINT fk_fi_ficha FOREIGN KEY (fk_ficha) REFERENCES ficha (id_ficha) ON DELETE CASCADE,
                CONSTRAINT fk_fi_usuario FOREIGN KEY (fk_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $conexion->exec($sql1);
        } catch (Exception $e) {}

        try {
            $sql2 = "CREATE TABLE IF NOT EXISTS horario_bloque (
                id_horario_bloque INT AUTO_INCREMENT PRIMARY KEY,
                fk_ficha INT NOT NULL,
                fecha DATE NOT NULL,
                bloque VARCHAR(20) NOT NULL,
                fk_usuario_instructor INT NOT NULL,
                materia VARCHAR(150) NULL,
                hora_inicio TIME NULL,
                hora_fin TIME NULL,
                UNIQUE KEY uq_ficha_fecha_bloque (fk_ficha, fecha, bloque),
                KEY idx_hb_instructor (fk_usuario_instructor),
                CONSTRAINT fk_hb_ficha FOREIGN KEY (fk_ficha) REFERENCES ficha (id_ficha) ON DELETE CASCADE,
                CONSTRAINT fk_hb_instructor FOREIGN KEY (fk_usuario_instructor) REFERENCES usuario (id_usuario) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $conexion->exec($sql2);
        } catch (Exception $e) {}
    }

    /**
     * Normaliza un texto removiendo acentos y convirtiendo a minúsculas
     */
    public static function normalizarTexto(string $str): string {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $reemplazos = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n', 'ü'=>'u',
            'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ñ'=>'n', 'Ü'=>'u'
        ];
        return strtr($str, $reemplazos);
    }

    /**
     * Limpia un slug alfanumérico
     */
    public static function limpiarSlug(string $str): string {
        $norm = self::normalizarTexto($str);
        return preg_replace('/[^a-z0-9]/', '', $norm);
    }

    /**
     * Sincroniza un instructor encontrado en el horario: busca coincidencia o crea un usuario nuevo
     * con correo generado, rol Instructor y contraseña '12345'
     */
    public static function sincronizarInstructor(PDO $conexion, string $nombreCompleto, int $idFicha, array &$resumen): int {
        $nombreCompleto = trim($nombreCompleto);
        if (empty($nombreCompleto)) return 0;

        $stmt = $conexion->prepare("SELECT id_usuario, nombre, apellido, nombre_usuario FROM usuario WHERE fk_rol = 2");
        $stmt->execute();
        $instructoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $normBuscado = self::normalizarTexto($nombreCompleto);
        $idEncontrado = null;
        $instructorExistente = null;

        foreach ($instructoresExistentes as $inst) {
            $fullNameInst = self::normalizarTexto(trim($inst['nombre'] . ' ' . $inst['apellido']));
            if ($fullNameInst === $normBuscado || str_contains($fullNameInst, $normBuscado) || str_contains($normBuscado, $fullNameInst)) {
                $idEncontrado = (int)$inst['id_usuario'];
                $instructorExistente = $inst;
                break;
            }
            $p1 = explode(' ', $normBuscado);
            $p2 = explode(' ', $fullNameInst);
            $inter = array_intersect($p1, $p2);
            if (count($inter) >= 2) {
                $idEncontrado = (int)$inst['id_usuario'];
                $instructorExistente = $inst;
                break;
            }
        }

        if ($idEncontrado) {
            if (!isset($resumen['instructores_existentes'][$idEncontrado])) {
                $resumen['instructores_existentes'][$idEncontrado] = [
                    'id' => $idEncontrado,
                    'nombre' => $instructorExistente['nombre'] . ' ' . $instructorExistente['apellido'],
                    'correo' => $instructorExistente['nombre_usuario']
                ];
            }
        } else {
            // Crear instructor nuevo
            $partes = preg_split('/\s+/', $nombreCompleto);
            if (count($partes) === 1) {
                $nombre = $partes[0];
                $apellido = 'Instructor';
            } elseif (count($partes) === 2) {
                $nombre = $partes[0];
                $apellido = $partes[1];
            } elseif (count($partes) === 3) {
                $nombre = $partes[0];
                $apellido = $partes[1] . ' ' . $partes[2];
            } else {
                $nombre = $partes[0] . ' ' . $partes[1];
                $apellido = implode(' ', array_slice($partes, 2));
            }

            $slugNombre = self::limpiarSlug($partes[0]);
            $slugApellido = self::limpiarSlug($partes[count($partes) - 1]);
            $baseEmail = strtolower($slugNombre . '.' . $slugApellido);
            $correo = $baseEmail . '@sena.edu.co';

            // Validar unicidad del correo
            $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM usuario WHERE LOWER(nombre_usuario) = LOWER(:email)");
            $stmtCheck->execute([':email' => $correo]);
            $sufijo = 1;
            while ((int)$stmtCheck->fetchColumn() > 0) {
                $sufijo++;
                $correo = $baseEmail . $sufijo . '@sena.edu.co';
                $stmtCheck->execute([':email' => $correo]);
            }

            // Clave '12345'
            $hashPass = password_hash('12345', PASSWORD_BCRYPT);

            // Generar identificación única
            $identificacion = '10' . str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $stmtIdent = $conexion->prepare("SELECT COUNT(*) FROM usuario WHERE identificacion = :ident");
            $stmtIdent->execute([':ident' => $identificacion]);
            while ((int)$stmtIdent->fetchColumn() > 0) {
                $identificacion = '10' . str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $stmtIdent->execute([':ident' => $identificacion]);
            }

            $telefono = '3' . str_pad((string)mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

            $stmtIns = $conexion->prepare("INSERT INTO usuario (nombre_usuario, contrasena, nombre, apellido, identificacion, telefono, fk_rol)
                                           VALUES (:correo, :pass, :nombre, :apellido, :ident, :tel, 2)");
            $stmtIns->execute([
                ':correo' => $correo,
                ':pass' => $hashPass,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':ident' => $identificacion,
                ':tel' => $telefono
            ]);
            $idEncontrado = (int)$conexion->lastInsertId();

            $resumen['instructores_nuevos'][$idEncontrado] = [
                'id' => $idEncontrado,
                'nombre' => $nombre . ' ' . $apellido,
                'correo' => $correo,
                'identificacion' => $identificacion,
                'clave_inicial' => '12345'
            ];
        }

        // Asociar a ficha_instructor
        try {
            $stmtLink = $conexion->prepare("INSERT IGNORE INTO ficha_instructor (fk_ficha, fk_usuario) VALUES (:ficha, :usuario)");
            $stmtLink->execute([':ficha' => $idFicha, ':usuario' => $idEncontrado]);
        } catch (Exception $e) {}

        return $idEncontrado;
    }

    /**
     * Parsea un archivo Excel de horarios (.xlsx) utilizando ZipArchive y SimpleXML nativos
     */
    public static function parsearExcelHorario(string $filePath): array {
        if (!file_exists($filePath)) {
            return ['exito' => false, 'mensaje' => 'El archivo Excel no existe en la ruta especificada.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            return ['exito' => false, 'mensaje' => 'No se pudo abrir el archivo Excel (.xlsx).'];
        }

        // 1. Cargar cadenas compartidas
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $xml = simplexml_load_string($ssXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string)$r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Cargar hoja de cálculo sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $zip->close();
            return ['exito' => false, 'mensaje' => 'No se encontró la hoja de trabajo principal en el archivo Excel.'];
        }

        $xml = simplexml_load_string($sheetXml);
        $sheetRows = [];
        if ($xml && isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $row) {
                $rNum = (int)$row['r'];
                $rowData = [];
                foreach ($row->c as $c) {
                    $ref = (string)$c['r'];
                    $col = preg_replace('/[0-9]/', '', $ref);
                    $type = (string)$c['t'];
                    $val = (string)$c->v;
                    if ($type === 's') {
                        $val = $sharedStrings[(int)$val] ?? $val;
                    }
                    $rowData[$col] = trim($val);
                }
                $sheetRows[$rNum] = $rowData;
            }
        }
        $zip->close();

        if (empty($sheetRows)) {
            return ['exito' => false, 'mensaje' => 'El archivo Excel está vacío o no contiene filas con datos.'];
        }

        $monthMap = [
            'ENERO' => '01', 'FEBRERO' => '02', 'MARZO' => '03', 'ABRIL' => '04',
            'MAYO' => '05', 'JUNIO' => '06', 'JULIO' => '07', 'AGOSTO' => '08',
            'SEPTIEMBRE' => '09', 'OCTUBRE' => '10', 'NOVIEMBRE' => '11', 'DICIEMBRE' => '12'
        ];

        $colDays = [
            'D' => 'Lunes', 'F' => 'Martes', 'H' => 'Miércoles',
            'J' => 'Jueves', 'L' => 'Viernes', 'N' => 'Sábado'
        ];

        $rawSlots = [];
        $instructores = [];
        $maxRow = max(array_keys($sheetRows));

        for ($r = 1; $r <= $maxRow; $r++) {
            if (!isset($sheetRows[$r])) continue;
            $row = $sheetRows[$r];

            if (isset($row['D']) && mb_strtoupper($row['D']) === 'LUNES') {
                $monthRow = $sheetRows[$r - 1] ?? [];
                if (empty(array_filter($monthRow)) && isset($sheetRows[$r - 2])) {
                    $monthRow = $sheetRows[$r - 2];
                }

                $dayNumRow = $sheetRows[$r + 1] ?? [];
                $colMonths = [];
                $activeM = null;

                foreach (['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'] as $cL) {
                    if (!empty($monthRow[$cL])) {
                        $mN = mb_strtoupper($monthRow[$cL]);
                        foreach ($monthMap as $mK => $mV) {
                            if (str_contains($mN, $mK)) {
                                $activeM = $mV;
                                break;
                            }
                        }
                    }
                    if ($activeM) $colMonths[$cL] = $activeM;
                }

                $daysInWeek = [];
                foreach ($colDays as $col => $dayName) {
                    $dayNum = isset($dayNumRow[$col]) ? (int)$dayNumRow[$col] : 0;
                    if ($dayNum > 0) {
                        $m = $colMonths[$col] ?? $activeM ?? '07';
                        $daysInWeek[$col] = [
                            'date' => sprintf('%04d-%02d-%02d', 2025, $m, $dayNum),
                            'dayName' => $dayName
                        ];
                    }
                }

                for ($hrRow = $r + 2; $hrRow < $r + 23 && $hrRow <= $maxRow; $hrRow++) {
                    if (!isset($sheetRows[$hrRow])) continue;
                    $hRow = $sheetRows[$hrRow];
                    $startHour = isset($hRow['B']) ? trim($hRow['B']) : '';
                    $endHour   = isset($hRow['C']) ? trim($hRow['C']) : '';
                    if (!is_numeric($startHour)) continue;

                    $hStart = (int)$startHour;
                    $hEnd   = (int)$endHour;

                    foreach ($daysInWeek as $col => $dayInfo) {
                        $cellVal = $hRow[$col] ?? '';
                        if (!empty($cellVal) && mb_strtoupper($cellVal) !== 'FESTIVO') {
                            $lines = explode("\n", $cellVal);
                            $instName = trim($lines[0]);
                            $subject = trim($lines[1] ?? '');
                            if (!empty($instName)) {
                                $rawSlots[] = [
                                    'date' => $dayInfo['date'],
                                    'day' => $dayInfo['dayName'],
                                    'start' => $hStart,
                                    'end' => $hEnd,
                                    'instructor' => $instName,
                                    'subject' => $subject
                                ];
                                $instructores[$instName] = ($instructores[$instName] ?? 0) + 1;
                            }
                        }
                    }
                }
            }
        }

        return [
            'exito' => true,
            'slots' => $rawSlots,
            'instructores' => $instructores
        ];
    }

    /**
     * Importa completamente el horario desde un archivo Excel para la ficha especificada
     */
    public static function importarHorarioExcel(string $filePath, int $idFicha): array {
        $resultado = [
            'exito' => false,
            'mensaje' => '',
            'total_bloques_insertados' => 0,
            'total_horas' => 0,
            'instructores_nuevos' => [],
            'instructores_existentes' => [],
            'materias_detectadas' => [],
            'ficha' => null,
            'rango_fechas' => ['inicio' => null, 'fin' => null]
        ];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $pdo = $mysql->getConexion();
            if (!$pdo) {
                $resultado['mensaje'] = 'Error al conectar con la base de datos MySQL.';
                return $resultado;
            }

            self::asegurarTablasHorario($pdo);
            self::asegurarTablaFichaAsignatura($pdo);

            $stmtFicha = $pdo->prepare("SELECT id_ficha, nombre_programa, jornada FROM ficha WHERE id_ficha = :id LIMIT 1");
            $stmtFicha->execute([':id' => $idFicha]);
            $fichaData = $stmtFicha->fetch(PDO::FETCH_ASSOC);
            if (!$fichaData) {
                $resultado['mensaje'] = "La ficha {$idFicha} no existe en la base de datos.";
                return $resultado;
            }
            $resultado['ficha'] = $fichaData;

            $parseResult = self::parsearExcelHorario($filePath);
            if (!$parseResult['exito']) {
                $resultado['mensaje'] = $parseResult['mensaje'];
                return $resultado;
            }

            $rawSlots = $parseResult['slots'];
            if (empty($rawSlots)) {
                $resultado['mensaje'] = 'No se encontraron bloques ni horarios válidos en el archivo Excel.';
                return $resultado;
            }

            // Sincronizar instructores
            $instructorMap = [];
            foreach ($parseResult['instructores'] as $instNombre => $cnt) {
                $idUsuario = self::sincronizarInstructor($pdo, $instNombre, $idFicha, $resultado);
                if ($idUsuario > 0) {
                    $instructorMap[$instNombre] = $idUsuario;
                }
            }

            // Agrupar en bloques estándar
            $groupedBlocks = [];
            $fechas = [];
            $materias = [];

            foreach ($rawSlots as $slot) {
                $date = $slot['date'];
                $fechas[] = $date;
                $hStart = $slot['start'];
                $instNombre = $slot['instructor'];
                $subject = $slot['subject'];
                if (!empty($subject)) {
                    $materias[$subject] = ($materias[$subject] ?? 0) + 1;
                }

                if ($hStart >= 6 && $hStart < 9) {
                    $bloqueKey = 'bloque1';
                    $hIni = '06:00:00';
                    $hFin = '09:00:00';
                } elseif ($hStart >= 9 && $hStart < 12) {
                    $bloqueKey = 'bloque2';
                    $hIni = '09:00:00';
                    $hFin = '12:00:00';
                } elseif ($hStart >= 12 && $hStart < 15) {
                    $bloqueKey = 'bloque1_t';
                    $hIni = '12:00:00';
                    $hFin = '15:00:00';
                } elseif ($hStart >= 15 && $hStart < 18) {
                    $bloqueKey = 'bloque2_t';
                    $hIni = '15:00:00';
                    $hFin = '18:00:00';
                } else {
                    $bloqueKey = 'bloque_n';
                    $hIni = sprintf('%02d:00:00', $hStart);
                    $hFin = sprintf('%02d:00:00', $slot['end']);
                }

                $key = $date . '|' . $bloqueKey;
                if (!isset($groupedBlocks[$key])) {
                    $groupedBlocks[$key] = [
                        'fecha' => $date,
                        'bloque' => $bloqueKey,
                        'fk_usuario_instructor' => $instructorMap[$instNombre] ?? 0,
                        'materia' => $subject,
                        'hora_inicio' => $hIni,
                        'hora_fin' => $hFin,
                        'horas_count' => 1
                    ];
                } else {
                    $groupedBlocks[$key]['horas_count']++;
                    if (empty($groupedBlocks[$key]['materia']) && !empty($subject)) {
                        $groupedBlocks[$key]['materia'] = $subject;
                    }
                }
            }

            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("INSERT INTO horario_bloque 
                (fk_ficha, fecha, bloque, fk_usuario_instructor, materia, hora_inicio, hora_fin)
                VALUES (:fk_ficha, :fecha, :bloque, :fk_usuario_instructor, :materia, :hora_inicio, :hora_fin)
                ON DUPLICATE KEY UPDATE 
                    fk_usuario_instructor = VALUES(fk_usuario_instructor),
                    materia = VALUES(materia),
                    hora_inicio = VALUES(hora_inicio),
                    hora_fin = VALUES(hora_fin)");

            $insertados = 0;
            foreach ($groupedBlocks as $b) {
                if ($b['fk_usuario_instructor'] > 0) {
                    $stmtIns->execute([
                        ':fk_ficha' => $idFicha,
                        ':fecha' => $b['fecha'],
                        ':bloque' => $b['bloque'],
                        ':fk_usuario_instructor' => $b['fk_usuario_instructor'],
                        ':materia' => $b['materia'],
                        ':hora_inicio' => $b['hora_inicio'],
                        ':hora_fin' => $b['hora_fin']
                    ]);
                    $insertados++;
                }
            }

            $stmtAsig = $pdo->prepare("INSERT IGNORE INTO ficha_asignatura (fk_ficha, fk_usuario_instructor, nombre_asignatura, tipo)
                                       VALUES (:ficha, :instructor, :materia, :tipo)");
            foreach ($materias as $matNombre => $count) {
                foreach ($groupedBlocks as $gb) {
                    if ($gb['materia'] === $matNombre && $gb['fk_usuario_instructor'] > 0) {
                        $tipo = 'Técnica';
                        $matUpper = mb_strtoupper($matNombre);
                        if (str_contains($matUpper, 'INGLÉS') || str_contains($matUpper, 'INGLES')) $tipo = 'Bilingüismo';
                        elseif (str_contains($matUpper, 'AMBIENTAL') || str_contains($matUpper, 'SST') || str_contains($matUpper, 'DERECHOS') || str_contains($matUpper, 'COMUNICACIÓN') || str_contains($matUpper, 'ÉTICA')) $tipo = 'Transversal';

                        $stmtAsig->execute([
                            ':ficha' => $idFicha,
                            ':instructor' => $gb['fk_usuario_instructor'],
                            ':materia' => mb_substr($matNombre, 0, 100),
                            ':tipo' => $tipo
                        ]);
                        break;
                    }
                }
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            sort($fechas);
            $resultado['exito'] = true;
            $resultado['total_bloques_insertados'] = $insertados;
            $resultado['total_horas'] = count($rawSlots);
            $resultado['rango_fechas'] = [
                'inicio' => $fechas[0] ?? null,
                'fin' => end($fechas) ?: null
            ];
            $resultado['materias_detectadas'] = array_keys($materias);
            $resultado['mensaje'] = "Horario importado exitosamente: {$insertados} bloques y " . count($rawSlots) . " horas procesadas.";

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $resultado['exito'] = false;
            $resultado['mensaje'] = 'Error al procesar el archivo: ' . $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Obtiene los bloques de horario registrados para una ficha, opcionalmente filtrados por mes (YYYY-MM)
     */
    public static function obtenerHorarioBloquesFicha(int $idFicha, ?string $mesAnio = null): array {
        $bloques = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablasHorario($conexion);
                $sql = "SELECT hb.*, 
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre,
                               u.nombre_usuario AS instructor_correo,
                               u.identificacion AS instructor_identificacion
                        FROM horario_bloque hb
                        LEFT JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                        WHERE hb.fk_ficha = :idFicha";
                $params = [':idFicha' => $idFicha];
                if (!empty($mesAnio)) {
                    $sql .= " AND DATE_FORMAT(hb.fecha, '%Y-%m') = :mesAnio";
                    $params[':mesAnio'] = $mesAnio;
                }
                $sql .= " ORDER BY hb.fecha ASC, hb.hora_inicio ASC";
                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Exception $e) {}
        return $bloques;
    }

    /**
     * Obtiene los meses disponibles con bloques registrados para una ficha
     */
    public static function obtenerMesesDisponiblesHorario(int $idFicha): array {
        $meses = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablasHorario($conexion);
                $sql = "SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') AS mes_anio, 
                               COUNT(*) as total_bloques
                        FROM horario_bloque
                        WHERE fk_ficha = :idFicha
                        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                        ORDER BY mes_anio ASC";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':idFicha' => $idFicha]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $nombresMes = [
                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                ];

                foreach ($rows as $r) {
                    $parts = explode('-', $r['mes_anio']);
                    $nombreM = $nombresMes[$parts[1] ?? '01'] ?? 'Mes';
                    $meses[] = [
                        'mes_anio' => $r['mes_anio'],
                        'label' => $nombreM . ' ' . ($parts[0] ?? ''),
                        'total_bloques' => (int)$r['total_bloques']
                    ];
                }
            }
        } catch (Exception $e) {}
        return $meses;
    }

    /**
     * Obtiene todos los instructores asociados a una ficha a través de ficha_instructor
     */
    public static function obtenerInstructoresDeFicha(int $idFicha): array {
        $instructores = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarTablasHorario($conexion);
                $sql = "SELECT u.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
                               u.nombre_usuario AS correo, u.identificacion, u.telefono
                        FROM ficha_instructor fi
                        INNER JOIN usuario u ON fi.fk_usuario = u.id_usuario
                        WHERE fi.fk_ficha = :idFicha
                        ORDER BY u.nombre ASC";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':idFicha' => $idFicha]);
                $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Exception $e) {}
        return $instructores;
    }
}
?>