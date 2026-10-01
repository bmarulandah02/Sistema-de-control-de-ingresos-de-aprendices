<?php
// ──────────────────────────────────────────────
//  controllers/ReporteController.php — Controlador de Reportes
// ──────────────────────────────────────────────

require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';
require_once __DIR__ . '/../models/ExcusaModel.php';

class ReporteController {

    /**
     * Muestra la vista de reportes con filtros por período (Día, Semana, Rango) y Ficha
     */
    public function index(): void {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        // Periodo preseteado: hoy, semana, mes, o rango personalizado
        $periodo = $_GET['periodo'] ?? 'mes';
        $fechaInicio = $_GET['fecha_inicio'] ?? '';
        $fechaFin    = $_GET['fecha_fin'] ?? '';

        if ($periodo === 'dia') {
            $fechaInicio = date('Y-m-d');
            $fechaFin    = date('Y-m-d');
        } else if ($periodo === 'semana') {
            $fechaInicio = date('Y-m-d', strtotime('monday this week'));
            $fechaFin    = date('Y-m-d', strtotime('sunday this week'));
        } else if ($periodo === 'mes' && empty($fechaInicio)) {
            $fechaInicio = date('Y-m-01');
            $fechaFin    = date('Y-m-d');
        }

        $filtros = [
            'fecha_inicio'  => $fechaInicio,
            'fecha_fin'     => $fechaFin,
            'periodo'       => $periodo,
            'ficha_id'      => !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : null,
            'instructor_id' => ($rolSesion === 'Instructor') ? $usuarioIdSesion : null
        ];

        // Obtener fichas disponibles según rol
        if ($rolSesion === 'Instructor') {
            $fichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion);
        } else {
            $fichas = HorarioModel::obtenerTodasFichas();
        }

        $reporteConsolidado = ReporteModel::obtenerReporteConsolidado($filtros);
        if ($rolSesion === 'Instructor') {
            $excusas = ExcusaModel::obtenerPorInstructor($usuarioIdSesion, 'Pendiente');
        } else {
            $excusas = ExcusaModel::obtenerTodas();
        }

        // Parámetros y datos para el reporte de Horarios
        $fichaHorarioId = (int)($_GET['ficha_horario_id'] ?? (!empty($fichas[0]['id']) ? $fichas[0]['id'] : 3234082));
        $mesHorario = trim($_GET['mes_horario'] ?? '');
        $instructorHorarioId = !empty($_GET['instructor_horario_id']) ? (int)$_GET['instructor_horario_id'] : null;

        $mesesHorarioDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($fichaHorarioId);
        $bloquesHorario = HorarioModel::obtenerHorarioBloquesFicha($fichaHorarioId, $mesHorario ?: null);
        $instructoresFichaHorario = HorarioModel::obtenerInstructoresDeFicha($fichaHorarioId);

        if ($instructorHorarioId && $instructorHorarioId > 0) {
            $bloquesHorario = array_values(array_filter($bloquesHorario, function($b) use ($instructorHorarioId) {
                return (int)($b['fk_usuario_instructor'] ?? 0) === $instructorHorarioId;
            }));
        }

        require __DIR__ . '/../views/admin/reportes.php';
    }

    /**
     * Exporta el reporte de inasistencias y retardos a formato Excel/CSV
     */
    public function exportarExcel(): void {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        $filtros = [
            'fecha_inicio'  => $_GET['fecha_inicio'] ?? date('Y-m-01'),
            'fecha_fin'     => $_GET['fecha_fin'] ?? date('Y-m-d'),
            'ficha_id'      => !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : null,
            'instructor_id' => ($rolSesion === 'Instructor') ? $usuarioIdSesion : null
        ];

        $reporte = ReporteModel::obtenerReporteConsolidado($filtros);

        $filename = "Reporte_Asistencias_SENA_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Aprendiz',
            'Documento',
            'Ficha',
            'Programa de Formación',
            'Instructor Encargado',
            'Total Asistencias',
            'Entradas a Tiempo',
            'Llegadas con Retardo',
            'Total Minutos Retardo',
            'Días de Inasistencia',
            'Fechas Específicas de Inasistencia'
        ], ';');

        foreach ($reporte as $r) {
            $fechasFaltadasStr = '';
            if (!empty($r['dias_faltados'])) {
                $fechas = array_column($r['dias_faltados'], 'fecha');
                $fechasFaltadasStr = implode(', ', $fechas);
            } else {
                $fechasFaltadasStr = 'Ninguna';
            }

            fputcsv($output, [
                $r['aprendiz'],
                $r['documento'],
                $r['numero_ficha'],
                $r['programa'],
                $r['instructor'],
                $r['total_asistencias'],
                $r['puntuales'],
                $r['retardos'],
                $r['minutos_retardo'] . ' min (' . $r['horas_retardo'] . ' hrs)',
                $r['total_inasistencias'],
                $fechasFaltadasStr
            ], ';');
        }

        fclose($output);
        exit();
    }

    /**
     * Genera la vista para descargar/imprimir el reporte en PDF
     */
    public function exportarPDF(): void {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        $filtros = [
            'fecha_inicio'  => $_GET['fecha_inicio'] ?? date('Y-m-01'),
            'fecha_fin'     => $_GET['fecha_fin'] ?? date('Y-m-d'),
            'ficha_id'      => !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : null,
            'instructor_id' => ($rolSesion === 'Instructor') ? $usuarioIdSesion : null
        ];

        $reporte = ReporteModel::obtenerReporteConsolidado($filtros);

        require __DIR__ . '/../views/admin/reporte_pdf.php';
        exit();
    }

    /**
     * Exporta el horario a Excel.
     * Permite descargar el archivo original .xlsx subido ("como se subió") o generar una plantilla mensual por bloques.
     */
    public function exportarHorarioExcel(): void {
        $idFicha = (int)($_GET['ficha_id'] ?? $_GET['id'] ?? 3234082);
        $mes = trim($_GET['mes'] ?? '');
        $tipo = trim($_GET['tipo'] ?? 'original'); // 'original', 'mensual', 'csv'
        $ficha = HorarioModel::obtenerFichaPorId($idFicha);
        $numFicha = $ficha['numero_ficha'] ?? $idFicha;

        // 1. Descargar el archivo Excel original .xlsx ("como se subió")
        if ($tipo === 'original') {
            $rutaOriginal = HorarioModel::obtenerRutaArchivoExcelFicha($idFicha);
            if ($rutaOriginal && file_exists($rutaOriginal)) {
                $filename = "Horario_Oficial_Ficha_{$numFicha}.xlsx";
                header('Content-Description: File Transfer');
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($rutaOriginal));
                readfile($rutaOriginal);
                exit();
            }
        }

        // 2. Descargar formato Excel (.xls) estructurado mensualmente por bloques (calendario)
        if ($tipo === 'mensual' || $tipo === 'original') {
            self::generarExcelMensualBloques($idFicha, $ficha, $mes);
            exit();
        }

        // 3. Fallback a CSV tabular si se solicita explícitamente
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($idFicha, $mes ?: null);
        $nombreMes = !empty($mes) ? str_replace('-', '_', $mes) : 'completo';
        $filename = "Reporte_Horario_Ficha_{$numFicha}_{$nombreMes}_" . date('Ymd') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Ficha', 'Programa', 'Jornada', 'Fecha', 'Día', 'Hora Inicio', 'Hora Fin',
            'Bloque', 'Instructor Encargado', 'Correo SENA', 'Identificación', 'Competencia / Asignatura'
        ], ';');

        $diasEspanol = [
            'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
        ];

        foreach ($bloques as $b) {
            $dt = new DateTime($b['fecha']);
            $dia = $diasEspanol[$dt->format('l')] ?? $dt->format('l');
            $hIni = !empty($b['hora_inicio']) ? substr($b['hora_inicio'], 0, 5) : '06:00';
            $hFin = !empty($b['hora_fin']) ? substr($b['hora_fin'], 0, 5) : '09:00';

            fputcsv($output, [
                $numFicha,
                $ficha['programa'] ?? 'ADSO',
                $ficha['jornada'] ?? 'Mañana',
                $b['fecha'],
                $dia,
                $hIni,
                $hFin,
                $b['bloque'],
                $b['instructor_nombre'] ?? 'Sin asignar',
                $b['instructor_correo'] ?? '',
                $b['instructor_identificacion'] ?? '',
                $b['materia'] ?? 'Formación Integral'
            ], ';');
        }

        fclose($output);
        exit();
    }

    /**
     * Genera un archivo Excel (.xls) estructurado mensualmente en formato de calendario por bloques
     */
    private static function generarExcelMensualBloques(int $idFicha, ?array $ficha, string $mesFiltro): void {
        $numFicha = $ficha['numero_ficha'] ?? $idFicha;
        $programa = $ficha['programa'] ?? 'ADSO';
        $jornada  = $ficha['jornada'] ?? 'Mañana (06:00 - 12:00)';

        $meses = HorarioModel::obtenerMesesDisponiblesHorario($idFicha);
        if (!empty($mesFiltro)) {
            $meses = array_values(array_filter($meses, fn($m) => $m['mes_anio'] === $mesFiltro));
        }

        $filename = "Horario_Mensual_Ficha_{$numFicha}_" . date('Ymd') . ".xls";
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo chr(0xEF).chr(0xBB).chr(0xBF); // UTF-8 BOM
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #1e293b; }
                .titulo-sena { background-color: #059669; color: #ffffff; font-size: 14pt; font-weight: bold; text-align: center; }
                .subtitulo { background-color: #ecfdf5; color: #065f46; font-size: 10.5pt; font-weight: bold; }
                .mes-header { background-color: #047857; color: #ffffff; font-size: 12pt; font-weight: bold; text-align: center; }
                .dia-header { background-color: #f1f5f9; color: #0f172a; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; font-size: 10pt; }
                .dia-num { background-color: #f8fafc; font-weight: bold; font-size: 11pt; text-align: center; border: 1px solid #cbd5e1; }
                .bloque-cell { vertical-align: top; padding: 6px; border: 1px solid #cbd5e1; font-size: 9pt; }
                .bloque-b1 { background-color: #f0fdf4; border-left: 3px solid #059669; }
                .bloque-b2 { background-color: #eff6ff; border-left: 3px solid #2563eb; }
                .vacio { background-color: #fafafa; border: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
        <?php foreach ($meses as $mInfo): 
            $mesAnio = $mInfo['mes_anio'];
            $bloquesMes = HorarioModel::obtenerHorarioBloquesFicha($idFicha, $mesAnio);
            $bloquesPorFecha = [];
            foreach ($bloquesMes as $b) {
                $bloquesPorFecha[$b['fecha']][] = $b;
            }

            $dtMes = new DateTime($mesAnio . '-01');
            $anio = (int)$dtMes->format('Y');
            $mesNum = (int)$dtMes->format('m');
            $totalDias = (int)$dtMes->format('t');
            $primerDiaSemana = (int)$dtMes->format('N'); // 1 = Lunes, 7 = Domingo

            $semanas = [];
            $semanaActual = [];
            for ($i = 1; $i < $primerDiaSemana; $i++) {
                $semanaActual[] = null;
            }
            for ($d = 1; $d <= $totalDias; $d++) {
                $fechaStr = sprintf('%04d-%02d-%02d', $anio, $mesNum, $d);
                $semanaActual[] = [
                    'dia' => $d,
                    'fecha' => $fechaStr,
                    'clases' => $bloquesPorFecha[$fechaStr] ?? []
                ];
                if (count($semanaActual) === 7) {
                    $semanas[] = $semanaActual;
                    $semanaActual = [];
                }
            }
            if (!empty($semanaActual)) {
                while (count($semanaActual) < 7) {
                    $semanaActual[] = null;
                }
                $semanas[] = $semanaActual;
            }
        ?>
            <table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom:30px;">
                <tr>
                    <td colspan="7" class="titulo-sena" height="35">SERVICIO NACIONAL DE APRENDIZAJE — SENA</td>
                </tr>
                <tr>
                    <td colspan="7" class="subtitulo" height="24">
                        FICHA: <?= htmlspecialchars($numFicha) ?> — PROGRAMA: <?= htmlspecialchars($programa) ?> — JORNADA: <?= htmlspecialchars($jornada) ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="mes-header" height="28">
                        HORARIO DE FORMACIÓN: <?= mb_strtoupper($mInfo['label']) ?>
                    </td>
                </tr>
                <tr>
                    <th class="dia-header" width="14%">LUNES</th>
                    <th class="dia-header" width="14%">MARTES</th>
                    <th class="dia-header" width="14%">MIÉRCOLES</th>
                    <th class="dia-header" width="14%">JUEVES</th>
                    <th class="dia-header" width="14%">VIERNES</th>
                    <th class="dia-header" width="14%">SÁBADO</th>
                    <th class="dia-header" width="14%">DOMINGO</th>
                </tr>

                <?php foreach ($semanas as $sem): ?>
                    <!-- Fila de número de día -->
                    <tr>
                        <?php foreach ($sem as $diaCell): ?>
                            <?php if ($diaCell === null): ?>
                                <td class="vacio"></td>
                            <?php else: ?>
                                <td class="dia-num"><?= $diaCell['dia'] ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Fila de Bloque 1 (06:00 - 09:00) -->
                    <tr>
                        <?php foreach ($sem as $diaCell): ?>
                            <?php if ($diaCell === null): ?>
                                <td class="vacio"></td>
                            <?php else: 
                                $b1 = null;
                                foreach ($diaCell['clases'] as $c) {
                                    if ($c['bloque'] === 'bloque1' || str_starts_with($c['hora_inicio'] ?? '', '06')) {
                                        $b1 = $c;
                                        break;
                                    }
                                }
                            ?>
                                <td class="bloque-cell <?= $b1 ? 'bloque-b1' : 'vacio' ?>">
                                    <?php if ($b1): ?>
                                        <strong>[B1] 06:00 – 09:00</strong><br>
                                        <b><?= htmlspecialchars(preg_replace('/\s*-\s*PRINCIPAL.*$/i', '', $b1['materia'])) ?></b><br>
                                        <span style="color:#475569;">👨‍🏫 <?= htmlspecialchars($b1['instructor_nombre'] ?? 'Instructor') ?></span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Fila de Bloque 2 (09:00 - 12:00) -->
                    <tr>
                        <?php foreach ($sem as $diaCell): ?>
                            <?php if ($diaCell === null): ?>
                                <td class="vacio"></td>
                            <?php else: 
                                $b2 = null;
                                foreach ($diaCell['clases'] as $c) {
                                    if ($c['bloque'] === 'bloque2' || str_starts_with($c['hora_inicio'] ?? '', '09')) {
                                        $b2 = $c;
                                        break;
                                    }
                                }
                            ?>
                                <td class="bloque-cell <?= $b2 ? 'bloque-b2' : 'vacio' ?>">
                                    <?php if ($b2): ?>
                                        <strong>[B2] 09:00 – 12:00</strong><br>
                                        <b><?= htmlspecialchars(preg_replace('/\s*-\s*PRINCIPAL.*$/i', '', $b2['materia'])) ?></b><br>
                                        <span style="color:#475569;">👨‍🏫 <?= htmlspecialchars($b2['instructor_nombre'] ?? 'Instructor') ?></span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
            <br>
        <?php endforeach; ?>
        </body>
        </html>
        <?php
    }

    /**
     * Genera la vista imprimible en PDF del horario
     */
    public function exportarHorarioPDF(): void {
        $id = (int)($_GET['ficha_id'] ?? $_GET['id'] ?? 3234082);
        $mes = trim($_GET['mes'] ?? '');
        $ficha = HorarioModel::obtenerFichaPorId($id);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mes ?: null);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($id);
        $mesesDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($id);

        require __DIR__ . '/../views/fichas/horario_imprimir.php';
        exit();
    }
}
