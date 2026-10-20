<?php
// ──────────────────────────────────────────────
//  models/ReporteModel.php — Modelo de Reportes de Asistencia
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class ReporteModel {

    /**
     * Obtiene el resumen de asistencia, retardos e inasistencias por aprendiz en un rango de fechas
     */
    public static function obtenerReporteConsolidado(array $filtros): array {
        $reporte = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $fechaInicio = !empty($filtros['fecha_inicio']) ? $filtros['fecha_inicio'] : date('Y-m-01');
                $fechaFin    = !empty($filtros['fecha_fin']) ? $filtros['fecha_fin'] : date('Y-m-d');
                $fichaId     = !empty($filtros['ficha_id']) ? (int)$filtros['ficha_id'] : null;
                $instructorId = !empty($filtros['instructor_id']) ? (int)$filtros['instructor_id'] : null;

                $nombreInstructorFiltro = '';
                if ($instructorId && $instructorId > 0) {
                    $stmtNomInst = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) FROM usuario WHERE id_usuario = :id");
                    $stmtNomInst->execute([':id' => $instructorId]);
                    $nombreInstructorFiltro = $stmtNomInst->fetchColumn() ?: '';
                }

                // 1. Obtener la lista de aprendices según filtros
                $whereAprendiz = [];
                $paramsAprendiz = [];

                // Excluir aprendices en estado Retirado (Deshabilitados) de las listas de asistencias
                $whereAprendiz[] = "(a.estado != 'Retirado' OR a.estado IS NULL)";

                if ($fichaId) {
                    $whereAprendiz[] = "a.fk_ficha = :ficha_id";
                    $paramsAprendiz[':ficha_id'] = $fichaId;
                }
                if ($instructorId) {
                    $whereAprendiz[] = "(
                        f.fk_usuario = :instructor_id
                        OR f.id_ficha IN (SELECT fk_ficha FROM ficha_instructor WHERE fk_usuario = :instructor_id)
                        OR f.id_ficha IN (SELECT fk_ficha FROM ficha_asignatura WHERE fk_usuario_instructor = :instructor_id)
                        OR f.id_ficha IN (SELECT fk_ficha FROM horario_bloque WHERE fk_usuario_instructor = :instructor_id)
                    )";
                    $paramsAprendiz[':instructor_id'] = $instructorId;
                }

                $whereSqlAprendiz = !empty($whereAprendiz) ? "WHERE " . implode(" AND ", $whereAprendiz) : "";

                $sqlAprendices = "SELECT a.id_aprendiz, a.codigo_rfid, a.fk_ficha,
                                         u.nombre, u.apellido, u.identificacion AS documento, u.nombre_usuario AS correo,
                                         f.id_ficha AS numero_ficha, f.nombre_programa AS programa, f.jornada, f.fk_usuario AS titular_instructor_id,
                                         CONCAT(inst.nombre, ' ', inst.apellido) AS instructor_encargado
                                  FROM aprendiz a
                                  JOIN usuario u ON a.fk_usuario = u.id_usuario
                                  LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                                  LEFT JOIN usuario inst ON f.fk_usuario = inst.id_usuario
                                  {$whereSqlAprendiz}
                                  ORDER BY f.id_ficha ASC, u.apellido ASC, u.nombre ASC";

                $stmtAprendices = $conexion->prepare($sqlAprendices);
                $stmtAprendices->execute($paramsAprendiz);
                $aprendices = $stmtAprendices->fetchAll(PDO::FETCH_ASSOC);

                // 2. Generar lista de días en el rango (excluyendo domingos)
                $periodoFechas = [];
                $cursor = new DateTime($fechaInicio);
                $fin    = new DateTime($fechaFin);
                while ($cursor <= $fin) {
                    if ($cursor->format('N') != 7) {
                        $periodoFechas[] = $cursor->format('Y-m-d');
                    }
                    $cursor->modify('+1 day');
                }

                // Cargar mapa de instructores reales programados por ficha y fecha desde horario_bloque
                $horarioMap = [];
                $fichasConHorario = [];
                try {
                    $stmtHb = $conexion->prepare("SELECT hb.fk_ficha, hb.fecha, hb.bloque, hb.fk_usuario_instructor,
                                                         CONCAT(u.nombre, ' ', u.apellido) AS nombre_instructor
                                                  FROM horario_bloque hb
                                                  JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                                                  WHERE hb.fecha BETWEEN :fInicio AND :fFin
                                                  ORDER BY hb.fecha ASC, hb.bloque ASC");
                    $stmtHb->execute([':fInicio' => $fechaInicio, ':fFin' => $fechaFin]);
                    while ($hb = $stmtHb->fetch(PDO::FETCH_ASSOC)) {
                        $fId  = (int)$hb['fk_ficha'];
                        $fFec = $hb['fecha'];
                        $uId  = (int)$hb['fk_usuario_instructor'];
                        $fichasConHorario[$fId] = true;

                        if (!isset($horarioMap[$fId][$fFec])) {
                            $horarioMap[$fId][$fFec] = [
                                'nombres' => [],
                                'ids'     => [],
                                'bloques' => []
                            ];
                        }
                        if (!in_array($hb['nombre_instructor'], $horarioMap[$fId][$fFec]['nombres'], true)) {
                            $horarioMap[$fId][$fFec]['nombres'][] = $hb['nombre_instructor'];
                        }
                        if (!in_array($uId, $horarioMap[$fId][$fFec]['ids'], true)) {
                            $horarioMap[$fId][$fFec]['ids'][] = $uId;
                        }
                        $horarioMap[$fId][$fFec]['bloques'][] = [
                            'bloque'         => $hb['bloque'],
                            'id_instructor'  => $uId,
                            'nom_instructor' => $hb['nombre_instructor']
                        ];
                    }
                } catch (Exception $e) {}

                // 3. Para cada aprendiz, calcular ingresos, retardos e inasistencias
                foreach ($aprendices as $app) {
                    $idAprendiz = (int)$app['id_aprendiz'];
                    $fIdApp = (int)($app['fk_ficha'] ?? 0);
                    $tieneHorario = !empty($fichasConHorario[$fIdApp]);

                    // Obtener todos los ingresos en el rango para este aprendiz
                    $sqlIngresos = "SELECT i.fecha_registro, i.entrada, i.salida, i.estado_asistencia, i.fk_usuario_instructor
                                    FROM ingresos i
                                    WHERE i.fk_aprendiz = :idAprendiz 
                                      AND i.fecha_registro BETWEEN :fInicio AND :fFin";
                    $paramsIng = [
                        ':idAprendiz' => $idAprendiz,
                        ':fInicio'    => $fechaInicio,
                        ':fFin'       => $fechaFin
                    ];
                    if ($instructorId && $instructorId > 0) {
                        $sqlIngresos .= " AND (i.fk_usuario_instructor = :instructorId OR i.fk_usuario_instructor IS NULL OR i.fk_usuario_instructor = 0)";
                        $paramsIng[':instructorId'] = $instructorId;
                    }
                    $stmtIng = $conexion->prepare($sqlIngresos);
                    $stmtIng->execute($paramsIng);
                    $ingresosMap = [];
                    $minutosRetardoTotal = 0;
                    $conteoPuntuales = 0;
                    $conteoRetardos = 0;

                    while ($ing = $stmtIng->fetch(PDO::FETCH_ASSOC)) {
                        $fechaReg = $ing['fecha_registro'];
                        $ingresosMap[$fechaReg] = $ing;

                        $estadoStr = $ing['estado_asistencia'] ?? '';

                        // Extraer minutos de retardo si existen en el texto o por cálculo
                        if (str_contains($estadoStr, 'Retardo')) {
                            $conteoRetardos++;
                            preg_match('/Retardo de (\d+) ?minutos/i', $estadoStr, $matches);
                            if (!empty($matches[1])) {
                                $minutosRetardoTotal += (int)$matches[1];
                            } else {
                                $minutosRetardoTotal += 15;
                            }
                        } else if (str_contains($estadoStr, 'Puntual')) {
                            $conteoPuntuales++;
                        }
                    }

                    // Obtener excusas APROBADAS del aprendiz en este rango de fechas
                    $instFiltro = !empty($filtros['instructor_id']) ? (int)$filtros['instructor_id'] : null;
                    if ($instFiltro) {
                        $stmtExcAprob = $conexion->prepare("SELECT e.fecha_inicio, e.fecha_fin 
                                                           FROM excusa e
                                                           LEFT JOIN excusa_instructor ei ON e.id_excusa = ei.fk_excusa
                                                           WHERE e.fk_aprendiz = :idAprendiz 
                                                             AND ((ei.estado = 'Aprobada' AND ei.fk_usuario_instructor = :instId) OR e.estado = 'Aprobada')
                                                             AND (e.fecha_inicio <= :fFin AND e.fecha_fin >= :fInicio)");
                        $stmtExcAprob->execute([
                            ':idAprendiz' => $idAprendiz,
                            ':fInicio'    => $fechaInicio,
                            ':fFin'       => $fechaFin,
                            ':instId'     => $instFiltro
                        ]);
                    } else {
                        $stmtExcAprob = $conexion->prepare("SELECT fecha_inicio, fecha_fin 
                                                           FROM excusa 
                                                           WHERE fk_aprendiz = :idAprendiz 
                                                             AND estado = 'Aprobada' 
                                                             AND (fecha_inicio <= :fFin AND fecha_fin >= :fInicio)");
                        $stmtExcAprob->execute([
                            ':idAprendiz' => $idAprendiz,
                            ':fInicio'    => $fechaInicio,
                            ':fFin'       => $fechaFin
                        ]);
                    }
                    $excusasAprobadas = $stmtExcAprob->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    // Identificar inasistencias en los días transcurridos
                    $diasFaltados = [];
                    foreach ($periodoFechas as $fDia) {
                        if ($fDia > date('Y-m-d')) {
                            continue;
                        }

                        if ($tieneHorario) {
                            // Si la ficha tiene programación en horario_bloque, solo se evalúan los días con clase programada
                            if (empty($horarioMap[$fIdApp][$fDia])) {
                                continue;
                            }

                            // Si se filtra por un instructor específico, solo se evalúan los días en que dicho instructor dio clase
                            if ($instructorId && !in_array($instructorId, $horarioMap[$fIdApp][$fDia]['ids'], true)) {
                                continue;
                            }

                            if ($instructorId) {
                                $blqsInst = [];
                                $nomInst = $nombreInstructorFiltro;
                                foreach ($horarioMap[$fIdApp][$fDia]['bloques'] as $b) {
                                    if ($b['id_instructor'] === $instructorId) {
                                        $nomBlq = ucfirst($b['bloque']);
                                        $nomBlq = preg_replace('/bloque(\d+)/i', 'Bloque $1', $nomBlq);
                                        $blqsInst[] = $nomBlq;
                                        if (!$nomInst) {
                                            $nomInst = $b['nom_instructor'];
                                        }
                                    }
                                }
                                $instructorTexto = $nomInst . (!empty($blqsInst) ? ' (' . implode(', ', $blqsInst) . ')' : '');
                            } else {
                                $instructorTexto = implode(' / ', $horarioMap[$fIdApp][$fDia]['nombres']);
                            }
                        } else {
                            // Ficha sin horario_bloque cargado
                            if ($instructorId) {
                                $esTitular = ((int)($app['titular_instructor_id'] ?? 0) === $instructorId);
                                if (!$esTitular && !empty($app['instructor_encargado']) && $nombreInstructorFiltro && $app['instructor_encargado'] !== $nombreInstructorFiltro) {
                                    continue;
                                }
                                $instructorTexto = $nombreInstructorFiltro ?: $app['instructor_encargado'];
                            } else {
                                $instructorTexto = !empty(trim($app['instructor_encargado'])) ? $app['instructor_encargado'] : 'Sin asignar';
                            }
                        }

                        $filaDelDia = $ingresosMap[$fDia] ?? null;
                        $asistio = ($filaDelDia !== null && ($filaDelDia['estado_asistencia'] === 'Puntual' || str_contains($filaDelDia['estado_asistencia'], 'Retardo')));

                        if ($asistio) {
                            continue;
                        }

                        // Verificar si existe una excusa APROBADA para este día
                        $diaExcusado = false;
                        foreach ($excusasAprobadas as $excA) {
                            if ($fDia >= $excA['fecha_inicio'] && $fDia <= $excA['fecha_fin']) {
                                $diaExcusado = true;
                                break;
                            }
                        }

                        // Si la excusa fue aprobada, la inasistencia desaparece del listado de faltas
                        if (!$diaExcusado) {
                            $diasFaltados[] = [
                                'fecha'      => $fDia,
                                'instructor' => $instructorTexto
                            ];
                        }
                    }

                    $reporte[] = [
                        'id_aprendiz'         => $idAprendiz,
                        'aprendiz'            => trim($app['nombre'] . ' ' . $app['apellido']),
                        'documento'           => $app['documento'],
                        'numero_ficha'        => $app['numero_ficha'] ?? 'N/A',
                        'programa'            => $app['programa'] ?? 'Sin programa',
                        'instructor'          => $instructorId ? ($nombreInstructorFiltro ?: $app['instructor_encargado']) : (!empty(trim($app['instructor_encargado'])) ? $app['instructor_encargado'] : 'Por asignar'),
                        'total_asistencias'   => count($ingresosMap),
                        'puntuales'           => $conteoPuntuales,
                        'retardos'            => $conteoRetardos,
                        'minutos_retardo'     => $minutosRetardoTotal,
                        'horas_retardo'       => round($minutosRetardoTotal / 60, 1),
                        'total_inasistencias' => count($diasFaltados),
                        'dias_faltados'       => $diasFaltados
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error en ReporteModel: " . $e->getMessage());
        }

        return $reporte;
    }

    /**
     * Obtiene las faltas / inasistencias mensuales de un aprendiz específico para generar excusa o PDF
     * Si una falta tiene una excusa aprobada por el instructor, desaparece de este listado.
     */
    public static function obtenerFaltasMesAprendiz(int $idAprendiz, ?string $anioMes = null): array {
        $resultado = [
            'aprendiz'             => null,
            'mes'                  => $anioMes ?: date('Y-m'),
            'total_faltas'         => 0,
            'faltas'               => [],
            'total_dias_evaluados' => 0
        ];

        if ($idAprendiz <= 0) {
            return $resultado;
        }

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $mesStr = $anioMes ?: date('Y-m');
                $fechaInicio = $mesStr . '-01';
                $fechaFinMes = date('Y-m-t', strtotime($fechaInicio));
                $hoy = date('Y-m-d');
                $fechaFin = ($fechaFinMes > $hoy) ? $hoy : $fechaFinMes;

                // 1. Obtener datos del aprendiz
                $sqlApp = "SELECT a.id_aprendiz, a.fk_ficha, a.estado AS estado_aprendiz,
                                  u.nombre, u.apellido, u.identificacion AS documento, u.telefono, u.nombre_usuario AS correo,
                                  f.id_ficha AS numero_ficha, f.nombre_programa AS programa, f.jornada,
                                  CONCAT(inst.nombre, ' ', inst.apellido) AS instructor_encargado
                           FROM aprendiz a
                           JOIN usuario u ON a.fk_usuario = u.id_usuario
                           LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                           LEFT JOIN usuario inst ON f.fk_usuario = inst.id_usuario
                           WHERE a.id_aprendiz = :idAprendiz
                           LIMIT 1";
                $stmtApp = $conexion->prepare($sqlApp);
                $stmtApp->execute([':idAprendiz' => $idAprendiz]);
                $app = $stmtApp->fetch(PDO::FETCH_ASSOC);
                if (!$app) {
                    return $resultado;
                }

                $resultado['aprendiz'] = [
                    'id_aprendiz'   => (int)$app['id_aprendiz'],
                    'nombre'        => trim($app['nombre'] . ' ' . $app['apellido']),
                    'documento'     => $app['documento'],
                    'telefono'      => $app['telefono'],
                    'correo'        => $app['correo'],
                    'numero_ficha'  => $app['numero_ficha'] ?? 'N/A',
                    'programa'      => $app['programa'] ?? 'Sin programa',
                    'jornada'       => $app['jornada'] ?? 'Diurna',
                    'instructor'    => !empty(trim($app['instructor_encargado'])) ? $app['instructor_encargado'] : 'Por asignar',
                    'estado'        => $app['estado_aprendiz'] ?? 'Activo'
                ];

                // 2. Generar días hábiles (Lunes a Sábado) en el rango evaluado
                $diasHabiles = [];
                $cursor = new DateTime($fechaInicio);
                $fin = new DateTime($fechaFin);
                while ($cursor <= $fin) {
                    if ($cursor->format('N') != 7) { // Excluir Domingos
                        $diasHabiles[] = $cursor->format('Y-m-d');
                    }
                    $cursor->modify('+1 day');
                }
                $resultado['total_dias_evaluados'] = count($diasHabiles);

                // 3. Obtener asistencias registradas para este aprendiz en el mes
                $sqlIngresos = "SELECT fecha_registro, estado_asistencia, entrada, salida
                                FROM ingresos
                                WHERE fk_aprendiz = :idAprendiz
                                  AND fecha_registro BETWEEN :fInicio AND :fFin";
                $stmtIng = $conexion->prepare($sqlIngresos);
                $stmtIng->execute([
                    ':idAprendiz' => $idAprendiz,
                    ':fInicio'    => $fechaInicio,
                    ':fFin'       => $fechaFin
                ]);
                $ingresosMap = [];
                while ($ing = $stmtIng->fetch(PDO::FETCH_ASSOC)) {
                    $ingresosMap[$ing['fecha_registro']] = $ing;
                }

                // 4. Obtener excusas del aprendiz que cubran fechas de este mes (Aprobadas y Pendientes)
                $sqlExcusas = "SELECT id_excusa, observacion, fecha_inicio, fecha_fin, estado
                               FROM excusa
                               WHERE fk_aprendiz = :idAprendiz
                                 AND estado IN ('Aprobada', 'Pendiente')
                                 AND (fecha_inicio <= :fFinMes AND fecha_fin >= :fInicio)";
                $stmtExc = $conexion->prepare($sqlExcusas);
                $stmtExc->execute([
                    ':idAprendiz' => $idAprendiz,
                    ':fInicio'    => $fechaInicio,
                    ':fFinMes'    => $fechaFinMes
                ]);
                $excusas = $stmtExc->fetchAll(PDO::FETCH_ASSOC) ?: [];

                // Cargar mapa de horarios de la ficha para el mes
                $horarioMesMap = [];
                try {
                    $stmtHbMes = $conexion->prepare("SELECT hb.fecha, CONCAT(u.nombre, ' ', u.apellido) AS nombre_instructor
                                                     FROM horario_bloque hb
                                                     JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                                                     WHERE hb.fk_ficha = :ficha AND hb.fecha BETWEEN :fInicio AND :fFin
                                                     ORDER BY hb.fecha ASC, hb.bloque ASC");
                    $stmtHbMes->execute([
                        ':ficha'   => $app['fk_ficha'],
                        ':fInicio' => $fechaInicio,
                        ':fFin'    => $fechaFin
                    ]);
                    while ($hbm = $stmtHbMes->fetch(PDO::FETCH_ASSOC)) {
                        $horarioMesMap[$hbm['fecha']][] = $hbm['nombre_instructor'];
                    }
                } catch (Exception $e) {}

                $diasFaltados = [];
                $nombresDias = [
                    'Monday'    => 'Lunes',
                    'Tuesday'   => 'Martes',
                    'Wednesday' => 'Miércoles',
                    'Thursday'  => 'Jueves',
                    'Friday'    => 'Viernes',
                    'Saturday'  => 'Sábado',
                    'Sunday'    => 'Domingo'
                ];

                foreach ($diasHabiles as $dia) {
                    $ingreso = $ingresosMap[$dia] ?? null;
                    $asistio = ($ingreso !== null && ($ingreso['estado_asistencia'] === 'Puntual' || str_contains($ingreso['estado_asistencia'], 'Retardo')));

                    if ($asistio) {
                        continue;
                    }

                    $excusaAprobada = null;
                    $excusaPendiente = null;

                    foreach ($excusas as $exc) {
                        if ($dia >= $exc['fecha_inicio'] && $dia <= $exc['fecha_fin']) {
                            if ($exc['estado'] === 'Aprobada') {
                                $excusaAprobada = $exc;
                                break;
                            } elseif ($exc['estado'] === 'Pendiente') {
                                $excusaPendiente = $exc;
                            }
                        }
                    }

                    // SI LA EXCUSA FUE APROBADA POR EL INSTRUCTOR, LA FALTA DESAPARECE COMPLETAMENTE
                    if ($excusaAprobada !== null) {
                        continue;
                    }

                    $diaSemanaIngles = date('l', strtotime($dia));
                    $diaSemanaEspanol = $nombresDias[$diaSemanaIngles] ?? $diaSemanaIngles;
                    $instructorFalta = !empty($horarioMesMap[$dia]) 
                        ? implode(' / ', array_unique($horarioMesMap[$dia])) 
                        : $resultado['aprendiz']['instructor'];

                    $diasFaltados[] = [
                        'fecha'         => $dia,
                        'dia_semana'    => $diaSemanaEspanol,
                        'estado_falta'  => $excusaPendiente ? 'Excusa en Revisión' : 'Injustificada',
                        'tiene_excusa'  => ($excusaPendiente !== null),
                        'excusa_id'     => $excusaPendiente['id_excusa'] ?? null,
                        'motivo_excusa' => $excusaPendiente['observacion'] ?? null,
                        'instructor'    => $instructorFalta
                    ];
                }

                $resultado['faltas'] = $diasFaltados;
                $resultado['total_faltas'] = count($diasFaltados);
            }
        } catch (Exception $e) {
            error_log("Error en obtenerFaltasMesAprendiz: " . $e->getMessage());
        }

        return $resultado;
    }
}
?>
