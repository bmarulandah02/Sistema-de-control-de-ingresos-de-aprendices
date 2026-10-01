<?php
$pageTitle = 'Reportes PDF / Excel — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';

$fichas = $fichas ?? [];
$filtros = $filtros ?? [];
$reporteConsolidado = $reporteConsolidado ?? [];
$excusas = $excusas ?? [];

$fichaHorarioId = (int)($fichaHorarioId ?? 3234082);
$mesHorario = $mesHorario ?? '';
$instructorHorarioId = $instructorHorarioId ?? null;
$mesesHorarioDisponibles = $mesesHorarioDisponibles ?? [];
$bloquesHorario = $bloquesHorario ?? [];
$instructoresFichaHorario = $instructoresFichaHorario ?? [];

$tabActiva = $_GET['tab'] ?? 'asistencia';

$diasEspanol = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
];
$mesesEspanol = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
    'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
];
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-header-title" style="display:flex; align-items:center; gap:0.5rem;">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(37,99,235,0.12); color:#2563eb;">
                <i class="bi bi-file-earmark-bar-graph-fill" style="font-size:1.375rem;"></i>
            </span>
            Centro de Reportes PDF / Excel
        </h1>
        <div class="page-header-subtitle">
            Genera, visualiza y exporta reportes de asistencias, inasistencias, excusas médicas y horarios de formación por ficha.
        </div>
    </div>

    <!-- Pestañas Principales de Reporte -->
    <div style="display:flex; gap:0.375rem; background:var(--muted); padding:0.375rem; border-radius:var(--radius-lg); border:1px solid var(--border);">
        <button type="button" id="tabBtnAsistencia" 
                class="btn-shadcn <?= $tabActiva === 'asistencia' ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
                style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:600;" 
                onclick="switchTab('asistencia')">
            <i class="bi bi-clock-history me-1"></i>Asistencias & Excusas
        </button>
        <button type="button" id="tabBtnHorarios" 
                class="btn-shadcn <?= $tabActiva === 'horarios' ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
                style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:600;" 
                onclick="switchTab('horarios')">
            <i class="bi bi-calendar3 me-1"></i>Horarios de Formación
        </button>
    </div>
</div>

<!-- ==================================================================== -->
<!-- ── SECCIÓN 1: REPORTES DE ASISTENCIAS & EXCUSAS ──────────────────── -->
<!-- ==================================================================== -->
<div id="seccionAsistencias" style="display: <?= $tabActiva === 'asistencia' ? 'block' : 'none' ?>;">

    <!-- FILTROS RÁPIDOS Y GENERADOR DE REPORTES DE ASISTENCIA -->
    <div class="shadcn-card card-mb-lg" style="margin-bottom:1.5rem;">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-funnel me-2"></i>Filtro del Reporte de Asistencia</h3>
        </div>
        <div class="card-body-shadcn" style="padding:1.25rem;">
            <form method="GET" action="index.php" id="filtroForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; align-items: end;">
                <input type="hidden" name="action" value="reportes">
                <input type="hidden" name="tab" value="asistencia">

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Período Rápido</label>
                    <select name="periodo" class="shadcn-select" onchange="cambiarPeriodo(this.value)">
                        <option value="dia" <?= (($filtros['periodo'] ?? '') === 'dia') ? 'selected' : '' ?>>Día (Hoy)</option>
                        <option value="semana" <?= (($filtros['periodo'] ?? '') === 'semana') ? 'selected' : '' ?>>Semana Actual</option>
                        <option value="mes" <?= (($filtros['periodo'] ?? '') === 'mes') ? 'selected' : '' ?>>Mes Actual</option>
                        <option value="personalizado" <?= (($filtros['periodo'] ?? '') === 'personalizado') ? 'selected' : '' ?>>Rango Personalizado</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="shadcn-input" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? date('Y-m-01')) ?>">
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="shadcn-input" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? date('Y-m-d')) ?>">
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Ficha de Formación</label>
                    <select name="ficha_id" class="shadcn-select">
                        <option value="">Todas las Fichas</option>
                        <?php foreach ($fichas ?? [] as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= (($filtros['ficha_id'] ?? '') == $f['id']) ? 'selected' : '' ?>>
                            Ficha <?= htmlspecialchars($f['numero_ficha'] . ' — ' . $f['programa']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; gap:0.5rem; grid-column: 1 / -1; justify-content: flex-end; margin-top:0.5rem; flex-wrap:wrap;">
                    <button type="submit" class="btn-shadcn btn-shadcn-outline">
                        <i class="bi bi-search me-1"></i>Consultar
                    </button>
                    <button type="button" class="btn-shadcn btn-shadcn-primary" onclick="exportarPDF()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                    </button>
                    <button type="button" class="btn-shadcn btn-shadcn-primary" style="background:#16a34a;" onclick="exportarExcel()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel (CSV)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CONSOLIDADO DE INASISTENCIAS Y RETARDOS -->
    <div class="shadcn-card" style="margin-bottom: 1.75rem;">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-calculator me-2"></i>Consolidado de Inasistencias y Retardos</h3>
            <span class="shadcn-badge badge-secondary"><?= count($reporteConsolidado ?? []) ?> aprendices evaluados</span>
        </div>

        <div class="shadcn-table-wrapper">
            <table class="shadcn-table">
                <thead>
                    <tr>
                        <th>Aprendiz</th>
                        <th>Documento</th>
                        <th>Ficha / Programa</th>
                        <th>Instructor Encargado</th>
                        <th>Minutos Retardo</th>
                        <th>Inasistencias</th>
                        <th>Detalle de Faltas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporteConsolidado)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                            <i class="bi bi-info-circle fs-3 d-block mb-1"></i>
                            No se encontraron aprendices ni datos para el período seleccionado.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reporteConsolidado as $r): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['aprendiz']) ?></td>
                        <td><?= htmlspecialchars($r['documento']) ?></td>
                        <td>
                            <span class="shadcn-badge badge-secondary">Ficha <?= htmlspecialchars($r['numero_ficha']) ?></span><br>
                            <small style="color:var(--muted-foreground);"><?= htmlspecialchars($r['programa']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($r['instructor']) ?></td>
                        <td>
                            <?php if ($r['minutos_retardo'] > 0): ?>
                            <span class="shadcn-badge badge-retardo">
                                <i class="bi bi-clock-history me-1"></i><?= $r['minutos_retardo'] ?> min (<?= $r['horas_retardo'] ?> hrs)
                            </span>
                            <?php else: ?>
                            <span class="shadcn-badge badge-puntual"><i class="bi bi-check-circle me-1"></i>0 min</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['total_inasistencias'] > 0): ?>
                            <span class="shadcn-badge" style="background:#fee2e2; color:#b91c1c;">
                                <i class="bi bi-x-circle me-1"></i><?= $r['total_inasistencias'] ?> día(s)
                            </span>
                            <?php else: ?>
                            <span class="shadcn-badge badge-puntual">0 días</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.8125rem;">
                            <?php if (!empty($r['dias_faltados'])): ?>
                                <?php foreach ($r['dias_faltados'] as $df): ?>
                                <div style="margin-bottom:0.25rem;">
                                    <i class="bi bi-calendar-x text-danger me-1"></i>
                                    <strong><?= htmlspecialchars($df['fecha']) ?></strong> — Inst. <?= htmlspecialchars($df['instructor']) ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:#16a34a; font-weight:500;"><i class="bi bi-check2-all me-1"></i>Asistencia completa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- COLA DE APROBACIÓN DE EXCUSAS MÉDICAS -->
    <div class="shadcn-card">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-file-medical me-2"></i>Excusas Médicas Pendientes de Revisión</h3>
            <span class="shadcn-badge badge-pendiente"><?= count($excusas ?? []) ?> pendientes</span>
        </div>

        <div class="shadcn-table-wrapper">
            <table class="shadcn-table">
                <thead>
                    <tr>
                        <th>Aprendiz</th>
                        <th>Documento</th>
                        <th>Ficha</th>
                        <th>Motivo</th>
                        <th>Período</th>
                        <th>Adjunto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($excusas)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                            <i class="bi bi-check-circle fs-3 d-block mb-1" style="color:#16a34a;"></i>
                            No hay excusas médicas pendientes de aprobación.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($excusas as $e): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($e['aprendiz']) ?></td>
                        <td><?= htmlspecialchars($e['documento']) ?></td>
                        <td><span class="shadcn-badge badge-secondary"><?= htmlspecialchars($e['numero_ficha'] ?? '—') ?></span></td>
                        <td><?= htmlspecialchars($e['motivo']) ?></td>
                        <td><?= htmlspecialchars($e['fecha_inicio']) ?> → <?= htmlspecialchars($e['fecha_fin']) ?></td>
                        <td>
                            <?php if ($e['archivo']): ?>
                            <a href="public/uploads/excusas/<?= htmlspecialchars($e['archivo']) ?>" target="_blank" class="btn-shadcn btn-shadcn-outline" style="padding:0.2rem 0.5rem; font-size:0.75rem;">
                                <i class="bi bi-paperclip"></i> Ver Documento
                            </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.375rem;">
                                <a href="index.php?action=excusa-aprobar&id=<?= $e['id'] ?>" class="btn-shadcn btn-shadcn-primary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Aprobar" onclick="return confirm('¿Aprobar esta excusa médica?')">
                                    <i class="bi bi-check-lg"></i> Aprobar
                                </a>
                                <a href="index.php?action=excusa-rechazar&id=<?= $e['id'] ?>" class="btn-shadcn btn-shadcn-danger" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Rechazar" onclick="return confirm('¿Rechazar esta excusa?')">
                                    <i class="bi bi-x-lg"></i> Rechazar
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==================================================================== -->
<!-- ── SECCIÓN 2: REPORTES DE HORARIOS DE FORMACIÓN ───────────────────── -->
<!-- ==================================================================== -->
<div id="seccionHorarios" style="display: <?= $tabActiva === 'horarios' ? 'block' : 'none' ?>;">

    <!-- FILTRO Y EXPORTADOR DE HORARIOS -->
    <div class="shadcn-card" style="margin-bottom:1.5rem; padding:1.25rem; border-left:4px solid #2563eb;">
        <div class="card-header-shadcn" style="padding:0; margin-bottom:1rem; border:none;">
            <h3><i class="bi bi-calendar-range me-2" style="color:#2563eb;"></i>Filtro y Generador de Reporte de Horarios</h3>
        </div>

        <form method="GET" action="index.php" id="filtroHorarioForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
            <input type="hidden" name="action" value="reportes">
            <input type="hidden" name="tab" value="horarios">

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Ficha de Formación</label>
                <select name="ficha_horario_id" id="ficha_horario_id" class="shadcn-select" onchange="document.getElementById('filtroHorarioForm').submit()">
                    <?php foreach ($fichas ?? [] as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($fichaHorarioId == $f['id']) ? 'selected' : '' ?>>
                        Ficha <?= htmlspecialchars($f['numero_ficha'] . ' — ' . $f['programa']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Mes / Periodo</label>
                <select name="mes_horario" id="mes_horario" class="shadcn-select" onchange="document.getElementById('filtroHorarioForm').submit()">
                    <option value="">— Todo el Semestre / Todos los Meses —</option>
                    <?php foreach ($mesesHorarioDisponibles ?? [] as $mh): ?>
                    <option value="<?= $mh['mes_anio'] ?>" <?= ($mesHorario === $mh['mes_anio']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mh['label']) ?> (<?= $mh['total_bloques'] ?> bloques)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Instructor Encargado</label>
                <select name="instructor_horario_id" id="instructor_horario_id" class="shadcn-select" onchange="document.getElementById('filtroHorarioForm').submit()">
                    <option value="">— Todos los Instructores —</option>
                    <?php foreach ($instructoresFichaHorario ?? [] as $ih): ?>
                    <option value="<?= $ih['id_usuario'] ?>" <?= ($instructorHorarioId == $ih['id_usuario']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ih['nombre_completo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:0.5rem; grid-column: 1 / -1; justify-content: flex-end; margin-top:0.5rem; flex-wrap:wrap;">
                <button type="submit" class="btn-shadcn btn-shadcn-outline">
                    <i class="bi bi-search me-1"></i>Consultar
                </button>
                <button type="button" class="btn-shadcn btn-shadcn-primary" onclick="exportarHorarioPDF()">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF Horario
                </button>
                <button type="button" class="btn-shadcn btn-shadcn-primary" style="background:#059669;" onclick="exportarHorarioExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel Horario (CSV)
                </button>
                <a href="index.php?action=ficha-horario&id=<?= $fichaHorarioId ?>" class="btn-shadcn btn-shadcn-ghost" style="color:#2563eb;">
                    <i class="bi bi-calendar-week me-1"></i>Ver en Calendario Interactivo
                </a>
            </div>
        </form>
    </div>

    <!-- TARJETAS DE MÉTRICAS DEL HORARIO -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div class="shadcn-card" style="padding:1rem; border-left:3px solid #2563eb;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Bloques Registrados</div>
            <div style="font-size:1.75rem; font-weight:800; color:#2563eb;"><?= count($bloquesHorario) ?></div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Franjas de clase filtradas</div>
        </div>

        <div class="shadcn-card" style="padding:1rem; border-left:3px solid #059669;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Total Horas Formación</div>
            <div style="font-size:1.75rem; font-weight:800; color:#059669;"><?= count($bloquesHorario) * 3 ?> hrs</div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Horas lectivas programadas</div>
        </div>

        <div class="shadcn-card" style="padding:1rem; border-left:3px solid #8b5cf6;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Instructores Asignados</div>
            <div style="font-size:1.75rem; font-weight:800; color:#8b5cf6;"><?= count($instructoresFichaHorario) ?></div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">En la ficha seleccionada</div>
        </div>

        <div class="shadcn-card" style="padding:1rem; border-left:3px solid #f59e0b;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Periodo Consultado</div>
            <div style="font-size:1rem; font-weight:700; color:var(--foreground); margin-top:0.375rem;">
                <?= !empty($mesHorario) ? htmlspecialchars($mesHorario) : 'Semestre Completo' ?>
            </div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Ficha <?= $fichaHorarioId ?></div>
        </div>
    </div>

    <!-- TABLA DE HORARIOS DETALLADA -->
    <div class="shadcn-card" style="margin-bottom:2rem;">
        <div class="card-header-shadcn">
            <h3>
                <i class="bi bi-calendar3 me-2"></i>
                Detalle del Horario — Ficha <?= $fichaHorarioId ?>
            </h3>
            <span class="shadcn-badge badge-secondary"><?= count($bloquesHorario) ?> registros</span>
        </div>

        <div class="shadcn-table-wrapper">
            <table class="shadcn-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Día</th>
                        <th>Franja Horaria</th>
                        <th>Bloque</th>
                        <th>Instructor Responsable</th>
                        <th>Correo SENA</th>
                        <th>Competencia / Asignatura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bloquesHorario)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                            <i class="bi bi-calendar-x fs-3 d-block mb-1"></i>
                            No se encontraron bloques de horario para los filtros seleccionados.
                            <div style="margin-top:0.5rem;">
                                <a href="index.php?action=ficha-horario-importar&ficha_id=<?= $fichaHorarioId ?>" class="btn-shadcn btn-shadcn-primary" style="padding:0.375rem 0.875rem; font-size:0.8125rem;">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Escanear e Importar Excel para Ficha <?= $fichaHorarioId ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($bloquesHorario as $b): 
                        $dt = new DateTime($b['fecha']);
                        $dia = $diasEspanol[$dt->format('l')] ?? $dt->format('l');
                        $ini = !empty($b['hora_inicio']) ? substr($b['hora_inicio'], 0, 5) : '06:00';
                        $fin = !empty($b['hora_fin']) ? substr($b['hora_fin'], 0, 5) : '09:00';

                        $badgeColor = '#059669';
                        $badgeBg = 'rgba(5,150,105,0.12)';
                        if ($b['bloque'] === 'bloque2') {
                            $badgeColor = '#2563eb';
                            $badgeBg = 'rgba(37,99,235,0.12)';
                        }
                    ?>
                    <tr>
                        <td style="font-weight:600;"><?= $dt->format('d/m/Y') ?></td>
                        <td>
                            <span class="shadcn-badge badge-secondary"><?= $dia ?></span>
                        </td>
                        <td>
                            <span class="shadcn-badge" style="background:<?= $badgeBg ?>; color:<?= $badgeColor ?>; font-weight:700;">
                                <i class="bi bi-clock me-1"></i><?= $ini ?> – <?= $fin ?>
                            </span>
                        </td>
                        <td style="font-size:0.75rem; text-transform:uppercase; color:var(--muted-foreground); font-weight:600;">
                            <?= htmlspecialchars($b['bloque']) ?>
                        </td>
                        <td style="font-weight:600;">
                            <i class="bi bi-person-badge me-1" style="color:#059669;"></i>
                            <?= htmlspecialchars($b['instructor_nombre'] ?: 'Instructor Asignado') ?>
                        </td>
                        <td style="font-family:monospace; font-size:0.8125rem; color:var(--muted-foreground);">
                            <?= htmlspecialchars($b['instructor_correo'] ?: '—') ?>
                        </td>
                        <td style="font-size:0.8125rem; font-weight:500;">
                            <?= htmlspecialchars($b['materia'] ?: 'Formación Profesional Integral') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function switchTab(tabName) {
    const seccAsistencia = document.getElementById('seccionAsistencias');
    const seccHorarios = document.getElementById('seccionHorarios');
    const btnAsistencia = document.getElementById('tabBtnAsistencia');
    const btnHorarios = document.getElementById('tabBtnHorarios');

    if (tabName === 'horarios') {
        seccAsistencia.style.display = 'none';
        seccHorarios.style.display = 'block';

        btnAsistencia.classList.remove('btn-shadcn-primary');
        btnAsistencia.classList.add('btn-shadcn-ghost');

        btnHorarios.classList.remove('btn-shadcn-ghost');
        btnHorarios.classList.add('btn-shadcn-primary');
    } else {
        seccHorarios.style.display = 'none';
        seccAsistencia.style.display = 'block';

        btnHorarios.classList.remove('btn-shadcn-primary');
        btnHorarios.classList.add('btn-shadcn-ghost');

        btnAsistencia.classList.remove('btn-shadcn-ghost');
        btnAsistencia.classList.add('btn-shadcn-primary');
    }
}

function cambiarPeriodo(val) {
    const fInicio = document.getElementById('fecha_inicio');
    const fFin = document.getElementById('fecha_fin');
    const hoy = new Date().toISOString().split('T')[0];

    if (val === 'dia') {
        fInicio.value = hoy;
        fFin.value = hoy;
    } else if (val === 'semana') {
        const d = new Date();
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        const monday = new Date(d.setDate(diff)).toISOString().split('T')[0];
        fInicio.value = monday;
        fFin.value = hoy;
    } else if (val === 'mes') {
        const d = new Date();
        const firstDay = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0];
        fInicio.value = firstDay;
        fFin.value = hoy;
    }
}

function exportarPDF() {
    const form = document.getElementById('filtroForm');
    const params = new URLSearchParams(new FormData(form));
    params.set('action', 'reporte-pdf');
    window.open('index.php?' + params.toString(), '_blank');
}

function exportarExcel() {
    const form = document.getElementById('filtroForm');
    const params = new URLSearchParams(new FormData(form));
    params.set('action', 'reporte-excel');
    window.location.href = 'index.php?' + params.toString();
}

function exportarHorarioPDF() {
    const fichaId = document.getElementById('ficha_horario_id').value;
    const mes = document.getElementById('mes_horario').value;
    const url = 'index.php?action=reporte-horario-pdf&ficha_id=' + encodeURIComponent(fichaId) + '&mes=' + encodeURIComponent(mes);
    window.open(url, '_blank');
}

function exportarHorarioExcel() {
    const fichaId = document.getElementById('ficha_horario_id').value;
    const mes = document.getElementById('mes_horario').value;
    const url = 'index.php?action=reporte-horario-excel&ficha_id=' + encodeURIComponent(fichaId) + '&mes=' + encodeURIComponent(mes);
    window.location.href = url;
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
