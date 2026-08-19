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
        $excusas = ExcusaModel::obtenerTodas();

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

        // Nombre del archivo descargable
        $filename = "Reporte_Asistencias_SENA_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 para que Microsoft Excel reconozca tildes y caracteres especiales
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados del archivo Excel
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

        // Renderizar plantilla de impresión PDF
        require __DIR__ . '/../views/admin/reporte_pdf.php';
        exit();
    }
}
