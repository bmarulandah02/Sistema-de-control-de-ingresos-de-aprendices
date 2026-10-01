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
     * Exporta el reporte de horarios a Excel (CSV compatible con Excel)
     */
    public function exportarHorarioExcel(): void {
        $idFicha = (int)($_GET['ficha_id'] ?? 3234082);
        $mes = trim($_GET['mes'] ?? '');
        $ficha = HorarioModel::obtenerFichaPorId($idFicha);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($idFicha, $mes ?: null);

        $numFicha = $ficha['numero_ficha'] ?? $idFicha;
        $nombreMes = !empty($mes) ? str_replace('-', '_', $mes) : 'completo';
        $filename = "Reporte_Horario_Ficha_{$numFicha}_{$nombreMes}_" . date('Ymd') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Ficha',
            'Programa de Formación',
            'Jornada',
            'Fecha',
            'Día de la Semana',
            'Hora Inicio',
            'Hora Fin',
            'Bloque',
            'Instructor Encargado',
            'Correo SENA Instructor',
            'Identificación Instructor',
            'Competencia / Asignatura'
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
     * Genera la vista imprimible en PDF del horario
     */
    public function exportarHorarioPDF(): void {
        $id = (int)($_GET['ficha_id'] ?? 3234082);
        $mes = trim($_GET['mes'] ?? '');
        $ficha = HorarioModel::obtenerFichaPorId($id);
        $bloques = HorarioModel::obtenerHorarioBloquesFicha($id, $mes ?: null);
        $instructoresFicha = HorarioModel::obtenerInstructoresDeFicha($id);

        require __DIR__ . '/../views/fichas/horario_imprimir.php';
        exit();
    }
}
