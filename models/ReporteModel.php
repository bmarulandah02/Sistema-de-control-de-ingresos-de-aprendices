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
                    $whereAprendiz[] = "f.fk_usuario = :instructor_id";
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

                // 2. Generar lista de días en el rango (excluyendo sábados y domingos si es diurno/normal)
                $periodoFechas = [];
                $cursor = new DateTime($fechaInicio);
                $fin    = new DateTime($fechaFin);
                while ($cursor <= $fin) {
                    $periodoFechas[] = $cursor->format('Y-m-d');
                    $cursor->modify('+1 day');
                }

                // 3. Para cada aprendiz, calcular ingresos, retardos e inasistencias
                foreach ($aprendices as $app) {
                    $idAprendiz = (int)$app['id_aprendiz'];

                    // Obtener todos los ingresos en el rango para este aprendiz
                    $sqlIngresos = "SELECT fecha_registro, entrada, salida, estado_asistencia 
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
                                $minutosRetardoTotal += 15; // Estimación base de retardo si no especifica
                            }
                        } else if (str_contains($estadoStr, 'Puntual')) {
                            $conteoPuntuales++;
                        }
                    }

                    // Identificar inasistencias en los días transcurridos
                    $diasFaltados = [];
                    foreach ($periodoFechas as $fDia) {
                        // Si no hay marcación de asistencia registrada ese día
                        if (!isset($ingresosMap[$fDia])) {
                            // Verificar si el día ya pasó o es hoy
                            if ($fDia <= date('Y-m-d')) {
                                $diasFaltados[] = [
                                    'fecha'      => $fDia,
                                    'instructor' => !empty(trim($app['instructor_encargado'])) ? $app['instructor_encargado'] : 'Sin asignar'
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
}
