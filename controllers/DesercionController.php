<?php
// ────────────────────────────────────────────────────────────
//  controllers/DesercionController.php — Controlador de Deserción e Inasistencias
// ────────────────────────────────────────────────────────────

require_once __DIR__ . '/../models/DesercionModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';

class DesercionController {

    /**
     * Vista principal del panel de control de inasistencias y alertas de deserción
     */
    public function index(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        if ($rolSesion !== 'Administrador' && $rolSesion !== 'Instructor') {
            header('Location: index.php?action=dashboard&error=sin_permiso');
            exit();
        }

        // Obtener fichas según rol
        if ($rolSesion === 'Instructor') {
            $fichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion);
            $soloInstructorId = $usuarioIdSesion;
        } else {
            $fichas = HorarioModel::obtenerTodasFichas(['estado' => 'Activo']);
            $soloInstructorId = null;
        }

        $idFichaFiltro = !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : (!empty($fichas[0]['id']) ? (int)$fichas[0]['id'] : 3234082);
        $nivelFiltro   = trim($_GET['nivel'] ?? 'Todos');

        // Rango de fechas / Periodo
        $hoy = date('Y-m-d');
        $periodoPreset = trim($_GET['periodo'] ?? 'mes_actual');
        $fechaInicio   = trim($_GET['fecha_inicio'] ?? '');
        $fechaFin      = trim($_GET['fecha_fin'] ?? '');

        if ($periodoPreset === 'hoy') {
            $fechaInicio = $hoy;
            $fechaFin    = $hoy;
        } elseif ($periodoPreset === 'ultimos_15') {
            $fechaInicio = date('Y-m-d', strtotime('-15 days'));
            $fechaFin    = $hoy;
        } elseif ($periodoPreset === 'ultimos_30') {
            $fechaInicio = date('Y-m-d', strtotime('-30 days'));
            $fechaFin    = $hoy;
        } elseif ($periodoPreset === 'completo_2026') {
            $fechaInicio = '2026-10-01';
            $fechaFin    = '2026-12-31';
        } else {
            // mes actual
            $periodoPreset = 'mes_actual';
            $fechaInicio = date('Y-m-01');
            $fechaFin    = date('Y-m-t');
        }

        // Consultar aprendices con inasistencias sin excusa
        $aprendicesRiesgo = DesercionModel::obtenerAprendicesEnRiesgo(
            $idFichaFiltro,
            $fechaInicio,
            $fechaFin,
            $soloInstructorId,
            $nivelFiltro
        );

        // Métricas consolidables
        $totales = [
            'en_desercion'   => 0, // 3+ consecutivas
            'en_riesgo_alto' => 0, // 2 consecutivas o 3+ acumuladas
            'en_alerta'      => 0, // 1 falta
            'total_faltas'   => 0
        ];

        foreach ($aprendicesRiesgo as $ar) {
            if ($ar['nivel_riesgo'] === 'Causal de Deserción') {
                $totales['en_desercion']++;
            } elseif ($ar['nivel_riesgo'] === 'Riesgo Alto') {
                $totales['en_riesgo_alto']++;
            } else {
                $totales['en_alerta']++;
            }
            $totales['total_faltas'] += $ar['total_faltas'];
        }

        // Ficha seleccionada
        $fichaActual = null;
        foreach ($fichas as $f) {
            if ((int)$f['id'] === $idFichaFiltro) {
                $fichaActual = $f;
                break;
            }
        }
        if (!$fichaActual && !empty($fichas)) {
            $fichaActual = $fichas[0];
        }

        // Mensajes de sesión
        $mensaje = $_SESSION['mensaje'] ?? null;
        unset($_SESSION['mensaje']);

        require __DIR__ . '/../views/desercion/index.php';
    }

    /**
     * Genera la Comunicación Oficial de Requerimiento de Inasistencia / Pre-Deserción (PDF / Impresión)
     */
    public function citacion(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        if ($rolSesion !== 'Administrador' && $rolSesion !== 'Instructor') {
            header('Location: index.php?action=dashboard&error=sin_permiso');
            exit();
        }

        $idAprendiz  = (int)($_GET['id_aprendiz'] ?? 0);
        $fechaInicio = trim($_GET['fecha_inicio'] ?? date('Y-m-01'));
        $fechaFin    = trim($_GET['fecha_fin'] ?? date('Y-m-d'));

        if ($idAprendiz <= 0) {
            header('Location: index.php?action=desercion');
            exit();
        }

        $datosCitacion = DesercionModel::obtenerDatosCitacion($idAprendiz, $fechaInicio, $fechaFin);
        if (!$datosCitacion) {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se encontraron datos del aprendiz para emitir la citación.'];
            header('Location: index.php?action=desercion');
            exit();
        }

        require __DIR__ . '/../views/desercion/citacion.php';
    }

    /**
     * Registra en la base de datos que se realizó el aviso o requerimiento de deserción
     */
    public function registrarAviso(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($rolSesion !== 'Administrador' && $rolSesion !== 'Instructor')) {
            header('Location: index.php?action=desercion');
            exit();
        }

        $idAprendiz   = (int)($_POST['id_aprendiz'] ?? 0);
        $faltas       = (int)($_POST['faltas_acumuladas'] ?? 3);
        $fechasFaltas = trim($_POST['fechas_faltas'] ?? '');
        $observacion  = trim($_POST['observacion'] ?? '');
        $medio        = trim($_POST['medio_notificacion'] ?? 'Comunicación Escrita');
        $estado       = trim($_POST['estado_tramite'] ?? 'Notificado');

        if ($idAprendiz <= 0) {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Aprendiz no válido para registrar el aviso.'];
            header('Location: index.php?action=desercion');
            exit();
        }

        $ok = DesercionModel::registrarAviso([
            'id_aprendiz'        => $idAprendiz,
            'id_instructor'      => $usuarioIdSesion,
            'faltas_acumuladas'  => $faltas,
            'fechas_faltas'      => $fechasFaltas,
            'observacion'        => $observacion,
            'medio_notificacion' => $medio,
            'estado_tramite'     => $estado
        ]);

        if ($ok) {
            $_SESSION['mensaje'] = [
                'tipo' => 'success',
                'texto' => 'Aviso de deserción registrado correctamente. Se ha establecido el plazo legal de 5 días hábiles para la justificación del aprendiz.'
            ];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Ocurrió un error al registrar el aviso de deserción.'];
        }

        header('Location: index.php?action=desercion');
        exit();
    }

    /**
     * Exporta el reporte de inasistencias y deserción a CSV/Excel
     */
    public function exportarExcel(): void {
        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        if ($rolSesion !== 'Administrador' && $rolSesion !== 'Instructor') {
            header('Location: index.php?action=dashboard');
            exit();
        }

        $idFicha = !empty($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : 3234082;
        $soloInstructorId = ($rolSesion === 'Instructor') ? $usuarioIdSesion : null;
        $fechaInicio = trim($_GET['fecha_inicio'] ?? date('Y-m-01'));
        $fechaFin    = trim($_GET['fecha_fin'] ?? date('Y-m-d'));

        $aprendices = DesercionModel::obtenerAprendicesEnRiesgo($idFicha, $fechaInicio, $fechaFin, $soloInstructorId);

        $filename = "Reporte_Desercion_Inasistencias_Ficha_{$idFicha}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'Ficha', 'Programa', 'Documento', 'Aprendiz', 'Teléfono', 'Correo',
            'Nivel de Riesgo', 'Faltas Consecutivas', 'Total Inasistencias Sin Justificar',
            'Fechas de Inasistencia', 'Último Trámite / Aviso', 'Fecha Límite Descargos'
        ], ';');

        foreach ($aprendices as $a) {
            $ultimoAvisoTxt = !empty($a['ultimo_aviso']) ? $a['ultimo_aviso']['estado_tramite'] . ' (' . substr($a['ultimo_aviso']['fecha_aviso'], 0, 10) . ')' : 'Sin aviso formal';
            $fechaLimite = !empty($a['ultimo_aviso']['fecha_limite_descargos']) ? $a['ultimo_aviso']['fecha_limite_descargos'] : '—';

            fputcsv($out, [
                $a['numero_ficha'],
                $a['programa'],
                $a['documento'],
                $a['nombre_completo'],
                $a['telefono'],
                $a['correo'],
                $a['nivel_riesgo'],
                $a['faltas_consecutivas'],
                $a['total_faltas'],
                implode(', ', $a['fechas_faltas']),
                $ultimoAvisoTxt,
                $fechaLimite
            ], ';');
        }

        fclose($out);
        exit();
    }
}
?>
