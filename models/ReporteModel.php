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

                // 1. Obtener la lista de aprendices según filtros
                $whereAprendiz = [];
                $paramsAprendiz = [];

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
                                         f.id_ficha AS numero_ficha, f.nombre_programa AS programa, f.jornada,
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

                // 2. Pre-cargar horario_bloque para las fichas evaluadas en el rango de fechas
                $whereHb = ["hb.fecha BETWEEN :fInicio AND :fFin"];
                $paramsHb = [
                    ':fInicio' => $fechaInicio,
                    ':fFin'    => $fechaFin
                ];
                if ($fichaId) {
                    $whereHb[] = "hb.fk_ficha = :hb_ficha";
                    $paramsHb[':hb_ficha'] = $fichaId;
                }
                if ($instructorId) {
                    $whereHb[] = "hb.fk_usuario_instructor = :hb_instructor";
                    $paramsHb[':hb_instructor'] = $instructorId;
                }
                $whereHbSql = implode(" AND ", $whereHb);

                $sqlHb = "SELECT hb.fk_ficha, hb.fecha, hb.bloque, hb.materia, hb.hora_inicio, hb.hora_fin,
                                 hb.fk_usuario_instructor,
                                 CONCAT(u.nombre, ' ', u.apellido) AS nombre_instructor
                          FROM horario_bloque hb
                          LEFT JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                          WHERE {$whereHbSql}
                          ORDER BY hb.fecha ASC, hb.hora_inicio ASC, hb.bloque ASC";
                $stmtHb = $conexion->prepare($sqlHb);
                $stmtHb->execute($paramsHb);
                $horarioPorFicha = [];
                while ($b = $stmtHb->fetch(PDO::FETCH_ASSOC)) {
                    $fId = (int)$b['fk_ficha'];
                    $horarioPorFicha[$fId][$b['fecha']][] = $b;
                }

                $hoy = date('Y-m-d');

                // 3. Para cada aprendiz, calcular ingresos, retardos e inasistencias
                foreach ($aprendices as $app) {
                    $idAprendiz = (int)$app['id_aprendiz'];
                    $idFichaApp = (int)($app['fk_ficha'] ?? 0);
                    $bloquesFicha = $horarioPorFicha[$idFichaApp] ?? [];

                    // Días evaluables para esta ficha/aprendiz
                    $diasEvaluables = [];
                    if (!empty($bloquesFicha)) {
                        // Si la ficha tiene programación en horario_bloque, se evalúan exclusivamente los días con clase
                        foreach (array_keys($bloquesFicha) as $fDia) {
                            if ($fDia <= $hoy) {
                                $diasEvaluables[] = $fDia;
                            }
                        }
                        sort($diasEvaluables);
                    } else {
                        // Fallback para fichas sin horario cargado: días hábiles (lunes a sábado)
                        $cursor = new DateTime($fechaInicio);
                        $fin    = new DateTime(min($fechaFin, $hoy));
                        while ($cursor <= $fin) {
                            if ($cursor->format('N') != 7) {
                                $diasEvaluables[] = $cursor->format('Y-m-d');
                            }
                            $cursor->modify('+1 day');
                        }
                    }

                    // Obtener todos los ingresos en el rango para este aprendiz
                    $sqlIngresos = "SELECT i.fecha_registro, i.entrada, i.salida, i.estado_asistencia, i.fk_usuario_instructor, i.bloque_horario
                                    FROM ingresos i
                                    WHERE i.fk_aprendiz = :idAprendiz 
                                      AND i.fecha_registro BETWEEN :fInicio AND :fFin";
                    $paramsIng = [
                        ':idAprendiz' => $idAprendiz,
                        ':fInicio'    => $fechaInicio,
                        ':fFin'       => $fechaFin
                    ];
                    if ($instructorId && $instructorId > 0) {
                        $sqlIngresos .= " AND i.fk_usuario_instructor = :instructorId";
                        $paramsIng[':instructorId'] = $instructorId;
                    }
                    $stmtIng = $conexion->prepare($sqlIngresos);
                    $stmtIng->execute($paramsIng);
                    $ingresosMap = [];
                    $ingresosPorFecha = [];
                    $minutosRetardoTotal = 0;
                    $conteoPuntuales = 0;
                    $conteoRetardos = 0;

                    while ($ing = $stmtIng->fetch(PDO::FETCH_ASSOC)) {
                        $fechaReg = $ing['fecha_registro'];
                        $ingresosMap[$fechaReg] = $ing;
                        $ingresosPorFecha[$fechaReg][] = $ing;

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
                    $excusasAprobadas = $stmtExcAprob->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    // Identificar inasistencias en los días transcurridos
                    $diasFaltados = [];
                    foreach ($diasEvaluables as $fDia) {
                        // Verificar si existe una excusa APROBADA para este día
                        $diaExcusado = false;
                        foreach ($excusasAprobadas as $excA) {
                            if ($fDia >= $excA['fecha_inicio'] && $fDia <= $excA['fecha_fin']) {
                                $diaExcusado = true;
                                break;
                            }
                        }

                        // Si la excusa fue aprobada, la inasistencia desaparece del listado de faltas
                        if ($diaExcusado) {
                            continue;
                        }

                        $bloquesDelDia = $bloquesFicha[$fDia] ?? [];
                        $ingresosDelDia = $ingresosPorFecha[$fDia] ?? [];

                        if (!empty($bloquesDelDia)) {
                            $bloquesFaltados = [];
                            foreach ($bloquesDelDia as $blq) {
                                if (!self::verificoAsistenciaBloque($blq, $ingresosDelDia)) {
                                    $bloquesFaltados[] = $blq;
                                }
                            }

                            if (!empty($bloquesFaltados)) {
                                $instructorTexto = self::resolverInstructorFaltas(
                                    $bloquesFaltados,
                                    $bloquesDelDia,
                                    $app['instructor_encargado'] ?? ''
                                );
                                $diasFaltados[] = [
                                    'fecha'      => $fDia,
                                    'instructor' => $instructorTexto
                                ];
                            }
                        } else {
                            $filaDelDia = $ingresosMap[$fDia] ?? null;
                            $asistio = ($filaDelDia !== null && ($filaDelDia['estado_asistencia'] === 'Puntual' || str_contains($filaDelDia['estado_asistencia'], 'Retardo')));

                            if (!$asistio) {
                                $diasFaltados[] = [
                                    'fecha'      => $fDia,
                                    'instructor' => !empty(trim($app['instructor_encargado'] ?? '')) ? $app['instructor_encargado'] : 'Sin asignar'
                                ];
                            }
                        }
                    }

                    $reporte[] = [
                        'id_aprendiz'         => $idAprendiz,
                        'aprendiz'            => trim($app['nombre'] . ' ' . $app['apellido']),
                        'documento'           => $app['documento'],
                        'numero_ficha'        => $app['numero_ficha'] ?? 'N/A',
                        'programa'            => $app['programa'] ?? 'Sin programa',
                        'instructor'          => !empty(trim($app['instructor_encargado'])) ? $app['instructor_encargado'] : 'Por asignar',
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

                $idFichaApp = (int)($app['fk_ficha'] ?? 0);

                // 2. Cargar horario de bloques para esta ficha en el mes
                $stmtHbMes = $conexion->prepare("SELECT hb.fecha, hb.bloque, hb.materia, hb.hora_inicio, hb.hora_fin,
                                                        hb.fk_usuario_instructor,
                                                        CONCAT(u.nombre, ' ', u.apellido) AS nombre_instructor
                                                 FROM horario_bloque hb
                                                 LEFT JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                                                 WHERE hb.fk_ficha = :ficha 
                                                   AND hb.fecha BETWEEN :fInicio AND :fFinMes
                                                 ORDER BY hb.fecha ASC, hb.hora_inicio ASC, hb.bloque ASC");
                $stmtHbMes->execute([
                    ':ficha'   => $idFichaApp,
                    ':fInicio' => $fechaInicio,
                    ':fFinMes' => $fechaFinMes
                ]);
                $bloquesMes = $stmtHbMes->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $horarioMesPorFecha = [];
                foreach ($bloquesMes as $bm) {
                    $horarioMesPorFecha[$bm['fecha']][] = $bm;
                }

                // Generar días evaluados (de horario_bloque si existe, o días hábiles)
                $diasEvaluados = [];
                if (!empty($horarioMesPorFecha)) {
                    foreach (array_keys($horarioMesPorFecha) as $fDia) {
                        if ($fDia <= $fechaFin) {
                            $diasEvaluados[] = $fDia;
                        }
                    }
                    sort($diasEvaluados);
                } else {
                    $cursor = new DateTime($fechaInicio);
                    $fin = new DateTime($fechaFin);
                    while ($cursor <= $fin) {
                        if ($cursor->format('N') != 7) {
                            $diasEvaluados[] = $cursor->format('Y-m-d');
                        }
                        $cursor->modify('+1 day');
                    }
                }
                $resultado['total_dias_evaluados'] = count($diasEvaluados);

                // 3. Obtener asistencias registradas para este aprendiz en el mes
                $sqlIngresos = "SELECT fecha_registro, estado_asistencia, entrada, salida, fk_usuario_instructor, bloque_horario
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
                $ingresosPorFecha = [];
                while ($ing = $stmtIng->fetch(PDO::FETCH_ASSOC)) {
                    $fReg = $ing['fecha_registro'];
                    $ingresosMap[$fReg] = $ing;
                    $ingresosPorFecha[$fReg][] = $ing;
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

                foreach ($diasEvaluados as $dia) {
                    $bloquesDelDia = $horarioMesPorFecha[$dia] ?? [];
                    $ingresosDelDia = $ingresosPorFecha[$dia] ?? [];

                    $faltoDia = false;
                    $instructorTexto = $resultado['aprendiz']['instructor'] ?? 'Sin asignar';

                    if (!empty($bloquesDelDia)) {
                        $bloquesFaltados = [];
                        foreach ($bloquesDelDia as $blq) {
                            if (!self::verificoAsistenciaBloque($blq, $ingresosDelDia)) {
                                $bloquesFaltados[] = $blq;
                            }
                        }
                        if (!empty($bloquesFaltados)) {
                            $faltoDia = true;
                            $instructorTexto = self::resolverInstructorFaltas(
                                $bloquesFaltados,
                                $bloquesDelDia,
                                $resultado['aprendiz']['instructor'] ?? ''
                            );
                        }
                    } else {
                        $ingreso = $ingresosMap[$dia] ?? null;
                        $asistio = ($ingreso !== null && ($ingreso['estado_asistencia'] === 'Puntual' || str_contains($ingreso['estado_asistencia'], 'Retardo')));
                        if (!$asistio) {
                            $faltoDia = true;
                        }
                    }

                    if (!$faltoDia) {
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

                    $diasFaltados[] = [
                        'fecha'         => $dia,
                        'dia_semana'    => $diaSemanaEspanol,
                        'estado_falta'  => $excusaPendiente ? 'Excusa en Revisión' : 'Injustificada',
                        'tiene_excusa'  => ($excusaPendiente !== null),
                        'excusa_id'     => $excusaPendiente['id_excusa'] ?? null,
                        'motivo_excusa' => $excusaPendiente['observacion'] ?? null,
                        'instructor'    => $instructorTexto
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

    /**
     * Resuelve el nombre del instructor o instructores asociados a los bloques faltados en una fecha
     */
    private static function resolverInstructorFaltas(array $bloquesFaltados, array $bloquesDelDia, string $instructorFallback): string {
        if (empty($bloquesFaltados)) {
            return $instructorFallback ?: 'Sin asignar';
        }

        $instFaltadosMap = [];
        foreach ($bloquesFaltados as $fb) {
            $nom = trim($fb['nombre_instructor'] ?? '');
            if (!$nom) {
                $nom = $instructorFallback ?: 'Sin asignar';
            }
            $nomBlqFormato = ucfirst($fb['bloque'] ?? '');
            $nomBlqFormato = preg_replace('/bloque(\d+)/i', 'Bloque $1', $nomBlqFormato);
            $instFaltadosMap[$nom][] = $nomBlqFormato ?: 'Bloque';
        }

        if (count($instFaltadosMap) === 1) {
            $nombreSolo = array_key_first($instFaltadosMap);
            // Si faltó a todos los bloques programados del día y es el mismo instructor, nombre directo
            if (count($bloquesFaltados) === count($bloquesDelDia)) {
                return $nombreSolo;
            }
            $bloquesStr = implode(', ', $instFaltadosMap[$nombreSolo]);
            return $nombreSolo . " ({$bloquesStr})";
        }

        $partes = [];
        foreach ($instFaltadosMap as $nom => $blqs) {
            $partes[] = $nom . ' (' . implode(', ', $blqs) . ')';
        }
        return implode(' | Inst. ', $partes);
    }

    /**
     * Determina si un aprendiz asistió a un bloque de horario específico basándose en los ingresos registrados
     */
    private static function verificoAsistenciaBloque(array $bloque, array $ingresosDelDia): bool {
        $idInstBlq = (int)($bloque['fk_usuario_instructor'] ?? 0);
        $nomBloque = strtolower(trim($bloque['bloque'] ?? ''));

        foreach ($ingresosDelDia as $ing) {
            $estado = $ing['estado_asistencia'] ?? '';
            if ($estado === 'Puntual' || str_contains($estado, 'Retardo')) {
                $ingInst = (int)($ing['fk_usuario_instructor'] ?? 0);
                $ingBlq  = strtolower(trim($ing['bloque_horario'] ?? ''));

                if ($ingInst > 0 && $idInstBlq > 0 && $ingInst === $idInstBlq) {
                    return true;
                }
                if (!empty($ingBlq) && !empty($nomBloque) && (str_contains($ingBlq, $nomBloque) || $ingBlq === $nomBloque)) {
                    return true;
                }
                if ($ingInst === 0 && empty($ingBlq)) {
                    return true;
                }
            }
        }
        return false;
    }
}
?>
