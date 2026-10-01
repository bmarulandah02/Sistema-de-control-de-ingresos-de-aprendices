<?php
// ────────────────────────────────────────────────────────────
//  models/DesercionModel.php — Modelo de Inasistencias y Deserción
// ────────────────────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/HorarioModel.php';

class DesercionModel {

    /**
     * Asegura la creación de la tabla para registrar avisos y trámites de deserción
     */
    public static function asegurarTabla(PDO $conexion): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS desercion_aviso (
                id_aviso INT AUTO_INCREMENT PRIMARY KEY,
                fk_aprendiz INT NOT NULL,
                fk_usuario_instructor INT NOT NULL,
                fecha_aviso DATETIME DEFAULT CURRENT_TIMESTAMP,
                faltas_acumuladas INT NOT NULL DEFAULT 3,
                fechas_faltas TEXT NULL,
                observacion TEXT NULL,
                medio_notificacion VARCHAR(50) DEFAULT 'Comunicación Escrita',
                estado_tramite VARCHAR(50) DEFAULT 'Notificado',
                fecha_limite_descargos DATE NULL,
                KEY idx_da_aprendiz (fk_aprendiz),
                KEY idx_da_instructor (fk_usuario_instructor)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $conexion->exec($sql);
        } catch (Exception $e) {
            error_log("Error al asegurar tabla desercion_aviso: " . $e->getMessage());
        }
    }

    /**
     * Obtiene los aprendices con faltas/inasistencias sin excusa y clasifica su riesgo de deserción
     */
    public static function obtenerAprendicesEnRiesgo(
        ?int $idFicha = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        ?int $soloInstructorId = null,
        ?string $filtroNivel = null
    ): array {
        $aprendicesRiesgo = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if (!$conexion) return [];
            self::asegurarTabla($conexion);

            $hoy = date('Y-m-d');
            if (empty($fechaFin)) $fechaFin = $hoy;
            if (empty($fechaInicio)) {
                // Por defecto, evaluar desde el primer día del mes actual o 30 días atrás
                $fechaInicio = date('Y-m-01');
                if ($fechaInicio > $hoy) {
                    $fechaInicio = date('Y-m-d', strtotime('-30 days'));
                }
            }

            // 1. Obtener aprendices según filtros de ficha / instructor
            $whereApp = ["(a.estado = 'Activo' OR a.estado IS NULL OR a.estado = '')"];
            $paramsApp = [];

            if ($idFicha && $idFicha > 0) {
                $whereApp[] = "a.fk_ficha = :idFicha";
                $paramsApp[':idFicha'] = $idFicha;
            }

            if ($soloInstructorId && $soloInstructorId > 0) {
                $whereApp[] = "(f.fk_usuario = :instId 
                               OR f.id_ficha IN (SELECT fk_ficha FROM ficha_instructor WHERE fk_usuario = :instId)
                               OR f.id_ficha IN (SELECT fk_ficha FROM ficha_asignatura WHERE fk_usuario_instructor = :instId)
                               OR f.id_ficha IN (SELECT fk_ficha FROM horario_bloque WHERE fk_usuario_instructor = :instId))";
                $paramsApp[':instId'] = $soloInstructorId;
            }

            $whereSql = "WHERE " . implode(" AND ", $whereApp);

            $sqlAprendices = "SELECT a.id_aprendiz, a.codigo_rfid, a.fk_ficha, a.estado AS estado_aprendiz,
                                     u.id_usuario, u.nombre, u.apellido, u.identificacion AS documento,
                                     u.telefono, u.nombre_usuario AS correo,
                                     f.id_ficha AS numero_ficha, f.nombre_programa AS programa, f.jornada,
                                     CONCAT(inst.nombre, ' ', inst.apellido) AS instructor_lider
                              FROM aprendiz a
                              JOIN usuario u ON a.fk_usuario = u.id_usuario
                              LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                              LEFT JOIN usuario inst ON f.fk_usuario = inst.id_usuario
                              {$whereSql}
                              ORDER BY f.id_ficha ASC, u.apellido ASC, u.nombre ASC";

            $stmtApp = $conexion->prepare($sqlAprendices);
            $stmtApp->execute($paramsApp);
            $aprendices = $stmtApp->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 2. Cachear fechas de clase por ficha
            $diasClasePorFicha = [];

            foreach ($aprendices as $app) {
                $fichaId = (int)$app['fk_ficha'];
                $idAprendiz = (int)$app['id_aprendiz'];

                if (!isset($diasClasePorFicha[$fichaId])) {
                    // Verificar si la ficha tiene programación en horario_bloque
                    $stmtHb = $conexion->prepare("SELECT DISTINCT fecha 
                                                  FROM horario_bloque 
                                                  WHERE fk_ficha = :ficha 
                                                    AND fecha BETWEEN :fIni AND :fFin 
                                                    AND fecha <= :hoy 
                                                  ORDER BY fecha ASC");
                    $stmtHb->execute([
                        ':ficha' => $fichaId,
                        ':fIni'  => $fechaInicio,
                        ':fFin'  => $fechaFin,
                        ':hoy'   => $hoy
                    ]);
                    $fechasHb = $stmtHb->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    if (!empty($fechasHb)) {
                        $diasClasePorFicha[$fichaId] = $fechasHb;
                    } else {
                        // Si no hay horario cargado, evaluar días hábiles (lunes a sábado)
                        $cursor = new DateTime($fechaInicio);
                        $limite = new DateTime(min($fechaFin, $hoy));
                        $habiles = [];
                        while ($cursor <= $limite) {
                            if ($cursor->format('N') != 7) { // Excluir domingos
                                $habiles[] = $cursor->format('Y-m-d');
                            }
                            $cursor->modify('+1 day');
                        }
                        $diasClasePorFicha[$fichaId] = $habiles;
                    }
                }

                $diasProgramados = $diasClasePorFicha[$fichaId];
                if (empty($diasProgramados)) {
                    continue;
                }

                // 3. Obtener asistencias registradas del aprendiz en el rango
                $stmtIng = $conexion->prepare("SELECT fecha_registro, estado_asistencia, entrada, salida
                                               FROM ingresos
                                               WHERE fk_aprendiz = :idAprendiz
                                                 AND fecha_registro BETWEEN :fIni AND :fFin");
                $stmtIng->execute([
                    ':idAprendiz' => $idAprendiz,
                    ':fIni'       => $fechaInicio,
                    ':fFin'       => $fechaFin
                ]);
                $asistenciasMap = [];
                while ($ing = $stmtIng->fetch(PDO::FETCH_ASSOC)) {
                    $asistenciasMap[$ing['fecha_registro']] = $ing;
                }

                // 4. Obtener excusas APROBADAS del aprendiz
                $stmtExc = $conexion->prepare("SELECT fecha_inicio, fecha_fin, estado
                                               FROM excusa
                                               WHERE fk_aprendiz = :idAprendiz
                                                 AND estado = 'Aprobada'
                                                 AND (fecha_inicio <= :fFin AND fecha_fin >= :fIni)");
                $stmtExc->execute([
                    ':idAprendiz' => $idAprendiz,
                    ':fIni'       => $fechaInicio,
                    ':fFin'       => $fechaFin
                ]);
                $excusasAprobadas = $stmtExc->fetchAll(PDO::FETCH_ASSOC) ?: [];

                // 5. Evaluar día a día las faltas injustificadas
                $faltasSinExcusa = [];
                $consecutivasActuales = 0;
                $maxConsecutivas = 0;

                foreach ($diasProgramados as $dia) {
                    $asistio = isset($asistenciasMap[$dia]);
                    if ($asistio) {
                        $consecutivasActuales = 0;
                        continue;
                    }

                    // Verificar si está cubierto por excusa aprobada
                    $tieneExcusa = false;
                    foreach ($excusasAprobadas as $exc) {
                        if ($dia >= $exc['fecha_inicio'] && $dia <= $exc['fecha_fin']) {
                            $tieneExcusa = true;
                            break;
                        }
                    }

                    if ($tieneExcusa) {
                        $consecutivasActuales = 0;
                    } else {
                        // ¡Falta Injustificada / Sin Excusa!
                        $consecutivasActuales++;
                        if ($consecutivasActuales > $maxConsecutivas) {
                            $maxConsecutivas = $consecutivasActuales;
                        }
                        $faltasSinExcusa[] = $dia;
                    }
                }

                $totalFaltas = count($faltasSinExcusa);

                // Si tiene al menos una falta sin excusa, clasificar nivel de riesgo
                if ($totalFaltas > 0) {
                    $nivel = 'Alerta'; // 1 falta
                    $color = '#f59e0b';
                    $badgeClass = 'badge-warning';

                    if ($maxConsecutivas >= 3) {
                        $nivel = 'Causal de Deserción'; // Art. 22 SENA: 3 días consecutivos
                        $color = '#dc2626';
                        $badgeClass = 'badge-danger';
                    } elseif ($maxConsecutivas >= 2 || $totalFaltas >= 3) {
                        $nivel = 'Riesgo Alto';
                        $color = '#ea580c';
                        $badgeClass = 'badge-warning';
                    }

                    // Filtro de nivel si fue especificado
                    if ($filtroNivel && $filtroNivel !== 'Todos' && $filtroNivel !== $nivel) {
                        continue;
                    }

                    // 6. Consultar último aviso o trámite registrado
                    $stmtUltimoAviso = $conexion->prepare("SELECT id_aviso, fecha_aviso, estado_tramite, medio_notificacion, observacion, fecha_limite_descargos
                                                           FROM desercion_aviso
                                                           WHERE fk_aprendiz = :idAprendiz
                                                           ORDER BY id_aviso DESC
                                                           LIMIT 1");
                    $stmtUltimoAviso->execute([':idAprendiz' => $idAprendiz]);
                    $ultimoAviso = $stmtUltimoAviso->fetch(PDO::FETCH_ASSOC) ?: null;

                    $aprendicesRiesgo[] = [
                        'id_aprendiz'           => $idAprendiz,
                        'id_usuario'            => (int)$app['id_usuario'],
                        'nombre_completo'       => trim($app['nombre'] . ' ' . $app['apellido']),
                        'documento'             => $app['documento'],
                        'telefono'              => $app['telefono'] ?? '',
                        'correo'                => $app['correo'] ?? '',
                        'numero_ficha'          => $app['numero_ficha'],
                        'programa'              => $app['programa'],
                        'jornada'               => $app['jornada'],
                        'instructor_lider'      => $app['instructor_lider'] ?? 'Por asignar',
                        'total_dias_evaluados'  => count($diasProgramados),
                        'total_asistencias'     => count($asistenciasMap),
                        'total_faltas'          => $totalFaltas,
                        'faltas_consecutivas'   => $maxConsecutivas,
                        'fechas_faltas'         => $faltasSinExcusa,
                        'nivel_riesgo'          => $nivel,
                        'color_riesgo'          => $color,
                        'badge_riesgo'          => $badgeClass,
                        'ultimo_aviso'          => $ultimoAviso
                    ];
                }
            }

            // Ordenar: primero los que están en causal de deserción (3+ consecutivas), luego mayor cantidad de faltas
            usort($aprendicesRiesgo, function($a, $b) {
                if ($a['faltas_consecutivas'] !== $b['faltas_consecutivas']) {
                    return $b['faltas_consecutivas'] - $a['faltas_consecutivas'];
                }
                return $b['total_faltas'] - $a['total_faltas'];
            });

        } catch (Exception $e) {
            error_log("Error en DesercionModel::obtenerAprendicesEnRiesgo: " . $e->getMessage());
        }

        return $aprendicesRiesgo;
    }

    /**
     * Registra un aviso formal o citación de deserción para un aprendiz
     */
    public static function registrarAviso(array $datos): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if (!$conexion) return false;

            self::asegurarTabla($conexion);

            // Calcular fecha límite de descargos: 5 días hábiles siguientes (Reglamento SENA)
            $fechaAviso = date('Y-m-d');
            $diasAgregados = 0;
            $cursor = new DateTime($fechaAviso);
            while ($diasAgregados < 5) {
                $cursor->modify('+1 day');
                if ($cursor->format('N') < 6) { // Lunes a Viernes
                    $diasAgregados++;
                }
            }
            $fechaLimite = $cursor->format('Y-m-d');

            $sql = "INSERT INTO desercion_aviso 
                    (fk_aprendiz, fk_usuario_instructor, fecha_aviso, faltas_acumuladas, fechas_faltas, observacion, medio_notificacion, estado_tramite, fecha_limite_descargos)
                    VALUES (:aprendiz, :instructor, NOW(), :faltas, :fechas, :observacion, :medio, :estado, :fechaLimite)";

            $stmt = $conexion->prepare($sql);
            return $stmt->execute([
                ':aprendiz'    => (int)$datos['id_aprendiz'],
                ':instructor'  => (int)$datos['id_instructor'],
                ':faltas'      => (int)$datos['faltas_acumuladas'],
                ':fechas'      => is_array($datos['fechas_faltas']) ? implode(', ', $datos['fechas_faltas']) : (string)$datos['fechas_faltas'],
                ':observacion' => trim($datos['observacion'] ?? 'Requerimiento oficial por inasistencias sin justificar'),
                ':medio'       => $datos['medio_notificacion'] ?? 'Comunicación Escrita',
                ':estado'      => $datos['estado_tramite'] ?? 'Notificado',
                ':fechaLimite' => $fechaLimite
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar aviso de deserción: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los datos detallados de un aprendiz y sus inasistencias para generar la citación oficial en PDF
     */
    public static function obtenerDatosCitacion(int $idAprendiz, ?string $fechaInicio = null, ?string $fechaFin = null): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if (!$conexion) return null;

            self::asegurarTabla($conexion);

            $sql = "SELECT a.id_aprendiz, a.fk_ficha,
                           u.nombre, u.apellido, u.identificacion AS documento, u.telefono, u.nombre_usuario AS correo,
                           f.id_ficha AS numero_ficha, f.nombre_programa AS programa, f.jornada,
                           CONCAT(inst.nombre, ' ', inst.apellido) AS instructor_lider,
                           inst.nombre_usuario AS instructor_correo
                    FROM aprendiz a
                    JOIN usuario u ON a.fk_usuario = u.id_usuario
                    LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                    LEFT JOIN usuario inst ON f.fk_usuario = inst.id_usuario
                    WHERE a.id_aprendiz = :idAprendiz
                    LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':idAprendiz' => $idAprendiz]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$app) return null;

            // Obtener inasistencias en el rango
            $hoy = date('Y-m-d');
            if (empty($fechaFin)) $fechaFin = $hoy;
            if (empty($fechaInicio)) $fechaInicio = date('Y-m-01');

            $aprendicesRiesgo = self::obtenerAprendicesEnRiesgo((int)$app['fk_ficha'], $fechaInicio, $fechaFin);
            $datosRiesgo = null;
            foreach ($aprendicesRiesgo as $ar) {
                if ($ar['id_aprendiz'] === $idAprendiz) {
                    $datosRiesgo = $ar;
                    break;
                }
            }

            // Calcular 5 días hábiles para descargos
            $cursor = new DateTime($hoy);
            $diasAgregados = 0;
            while ($diasAgregados < 5) {
                $cursor->modify('+1 day');
                if ($cursor->format('N') < 6) {
                    $diasAgregados++;
                }
            }
            $fechaLimiteDescargos = $cursor->format('Y-m-d');

            return [
                'aprendiz'          => $app,
                'riesgo'            => $datosRiesgo,
                'fecha_actual'      => $hoy,
                'fecha_limite'      => $fechaLimiteDescargos,
                'fechas_faltas'     => $datosRiesgo['fechas_faltas'] ?? [],
                'total_faltas'      => $datosRiesgo['total_faltas'] ?? 0,
                'consecutivas'      => $datosRiesgo['faltas_consecutivas'] ?? 0
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerDatosCitacion: " . $e->getMessage());
            return null;
        }
    }
}
?>
