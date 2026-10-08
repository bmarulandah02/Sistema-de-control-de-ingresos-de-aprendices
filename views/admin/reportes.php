<?php
$pageTitle = 'Reportes / Horarios — Control de Ingresos SENA';
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
$fichaActualHorario = $fichaActualHorario ?? null;
$bloquesJson = $bloquesJson ?? json_encode($bloquesHorario, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$tabActiva = $_GET['tab'] ?? (($_GET['action'] ?? '') === 'excusas-admin' ? 'asistencia' : 'asistencia');

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

<?php $styleHorarioVersion = file_exists(__DIR__ . '/../../public/css/stylehorario.css') ? filemtime(__DIR__ . '/../../public/css/stylehorario.css') : time(); ?>
<link rel="stylesheet" href="public/css/stylehorario.css?v=<?= $styleHorarioVersion ?>">

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-header-title" style="display:flex; align-items:center; gap:0.5rem;">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(37,99,235,0.12); color:#2563eb;">
                <i class="bi bi-file-earmark-bar-graph-fill" style="font-size:1.375rem;"></i>
            </span>
            Centro de Reportes / Horarios
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

    <!-- ── BARRA GUÍA DE JORNADAS Y LEYENDA DE COLORES ──────────────── -->
    <div class="shadcn-card" style="margin-bottom:1rem; padding:1rem 1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <span style="font-size:0.8125rem; font-weight:700; color:var(--foreground); display:flex; align-items:center; gap:0.35rem;">
                    <i class="bi bi-clock-history" style="color:#059669;"></i> Franjas SENA:
                </span>
                <span class="shadcn-badge" style="background:rgba(5,150,105,0.12); color:#059669; font-weight:600;">
                    🌅 Bloque 1: 06:00 am – 09:00 am
                </span>
                <span class="shadcn-badge" style="background:rgba(37,99,235,0.12); color:#2563eb; font-weight:600;">
                    ☀️ Bloque 2: 09:00 am – 12:00 pm
                </span>
                <span class="shadcn-badge" style="background:rgba(217,119,6,0.12); color:#d97706; font-weight:600;">
                    🌤️ Tarde: 12:00 pm – 06:00 pm
                </span>
                <span class="shadcn-badge" style="background:rgba(139,92,246,0.12); color:#8b5cf6; font-weight:600;">
                    🌙 Noche: 06:00 pm – 10:00 pm
                </span>
            </div>

            <!-- Leyenda de materias -->
            <div style="display:flex; gap:0.75rem; align-items:center; font-size:0.75rem; font-weight:600; flex-wrap:wrap;">
                <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#059669;"></span> Técnica</span>
                <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#2563eb;"></span> Bilingüismo</span>
                <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#d97706;"></span> Transversal</span>
                <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#8b5cf6;"></span> Social</span>
            </div>
        </div>
    </div>

    <!-- ── COMPONENTE PRINCIPAL GOOGLE CALENDAR (6:00 AM A 10:00 PM) ── -->
    <div class="gcal-container">
        
        <!-- Toolbar estilo Google Calendar -->
        <div class="gcal-toolbar">
            <div class="gcal-nav-group">
                <button type="button" class="gcal-btn-today" onclick="gcalIrAHoy()">Hoy</button>
                <button type="button" class="gcal-btn-circle" onclick="gcalNavegar(-1)" title="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button type="button" class="gcal-btn-circle" onclick="gcalNavegar(1)" title="Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <div id="gcalTitle" class="gcal-title">Cargando horario...</div>
            </div>

            <!-- Conmutador de Vistas y Botón de Alternar Tabla -->
            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                <div class="gcal-view-switcher">
                    <button type="button" class="gcal-view-btn active" id="btnViewSemana" onclick="gcalCambiarVista('semana')">
                        <i class="bi bi-columns-gap me-1"></i>Semana (Horario)
                    </button>
                    <button type="button" class="gcal-view-btn" id="btnViewMes" onclick="gcalCambiarVista('mes')">
                        <i class="bi bi-grid-3x3 me-1"></i>Mes
                    </button>
                    <button type="button" class="gcal-view-btn" id="btnViewDia" onclick="gcalCambiarVista('dia')">
                        <i class="bi bi-calendar-day me-1"></i>Día
                    </button>
                    <button type="button" class="gcal-view-btn" id="btnViewAgenda" onclick="gcalCambiarVista('agenda')">
                        <i class="bi bi-card-checklist me-1"></i>Agenda
                    </button>
                </div>

                <!-- Selector Desplegable para Móviles (< 768px) -->
                <select id="gcalViewSelectMobile" class="shadcn-select gcal-view-select-mobile" onchange="gcalCambiarVista(this.value)">
                    <option value="semana" selected>📅 Semana</option>
                    <option value="mes">🗓️ Mes</option>
                    <option value="dia">📆 Día</option>
                    <option value="agenda">📋 Agenda</option>
                </select>

                <button type="button" class="btn-shadcn btn-shadcn-outline" id="btnToggleTabla" onclick="toggleTablaDetalle()" style="padding:0.4rem 0.875rem; font-size:0.8125rem;">
                    <i class="bi bi-table me-1"></i><span id="txtToggleTabla">Ver Tabla Detallada</span>
                </button>
            </div>
        </div>

        <!-- Área dinámica de renderizado -->
        <div id="gcalViewArea" style="position:relative; min-height:550px;">
            <!-- Renderizado por JavaScript -->
        </div>

    </div>

    <!-- ── TABLA DE HORARIOS DETALLADA (EXPANDIBLE / COLAPSABLE) ──────── -->
    <div class="shadcn-card" id="contenedorTablaDetallada" style="display:none; margin-bottom:2rem;">
        <div class="card-header-shadcn">
            <h3>
                <i class="bi bi-table me-2"></i>
                Detalle del Horario en Registros — Ficha <?= $fichaHorarioId ?>
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

<!-- ── MODAL FLOTANTE DE DETALLES DEL EVENTO (ESTILO GOOGLE CALENDAR) ── -->
<div id="gcalModal" class="gcal-modal-backdrop" onclick="gcalCerrarModal(event)">
    <div class="gcal-modal" onclick="event.stopPropagation()">
        <div id="gcalModalStrip" class="gcal-modal-header-strip"></div>
        <div class="gcal-modal-body" style="padding:1.5rem;">
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                <div>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem; flex-wrap:wrap;">
                        <span id="gcalModalBadge" class="shadcn-badge badge-primary">Técnica</span>
                        <span id="gcalModalLiveIndicator" class="badge-en-curso-pulse" style="display:none;">
                            <i class="bi bi-broadcast"></i> EN CLASE AHORA
                        </span>
                    </div>
                    <h2 id="gcalModalMateria" style="font-size:1.25rem; font-weight:700; margin:0; color:var(--foreground); line-height:1.3;">
                        Nombre de la Materia
                    </h2>
                </div>
                <button type="button" onclick="gcalCerrarModal()" style="border:none; background:transparent; font-size:1.25rem; cursor:pointer; color:var(--muted-foreground);">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.875rem; font-size:0.875rem;">
                <!-- Fecha y Hora -->
                <div style="display:flex; align-items:center; gap:0.75rem; color:var(--foreground);">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(5,150,105,0.12); color:#059669; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div id="gcalModalFecha" style="font-weight:600;">Lunes, 14 de Julio de 2025</div>
                        <div id="gcalModalHorario" style="color:var(--muted-foreground); font-size:0.8125rem;">06:00 am – 09:00 am (3 horas lectivas) • Bloque 1</div>
                    </div>
                </div>

                <!-- Instructor -->
                <div style="display:flex; align-items:center; gap:0.75rem; color:var(--foreground);">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div id="gcalModalInstructor" style="font-weight:600;">Instructor Responsable</div>
                        <div id="gcalModalCorreo" style="color:var(--muted-foreground); font-size:0.8125rem;">correo@sena.edu.co</div>
                    </div>
                </div>

                <!-- Ficha y Programa -->
                <div style="display:flex; align-items:center; gap:0.75rem; color:var(--foreground);">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(245,158,11,0.12); color:#d97706; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <div id="gcalModalFicha" style="font-weight:600;">Ficha <?= $fichaHorarioId ?> — <?= htmlspecialchars($fichaActualHorario['programa'] ?? 'ADSO') ?></div>
                        <div style="color:var(--muted-foreground); font-size:0.8125rem;">Jornadas: 06:00 am – 10:00 pm (Según franja de formación)</div>
                    </div>
                </div>
            </div>

            <!-- Acciones del Modal -->
            <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem; border-top:1px solid var(--border); padding-top:1rem;">
                <button type="button" class="btn-shadcn btn-shadcn-ghost" onclick="gcalCerrarModal()">Cerrar</button>
                <a id="gcalModalBtnAsistencia" href="index.php?action=asistencia" class="btn-shadcn btn-shadcn-primary">
                    <i class="bi bi-qr-code-scan me-1"></i>Ir a Toma de Asistencia
                </a>
            </div>

        </div>
    </div>
</div>

<script>
// Datos del horario inyectados desde PHP
const GCAL_BLOQUES = <?= $bloquesJson ?: '[]' ?>;
const ID_FICHA_ACTUAL = <?= (int)$fichaHorarioId ?>;

// Estado del Calendario
let gcalEstado = {
    vista: 'semana', // 'semana', 'mes', 'dia', 'agenda'
    fechaActual: new Date(),
    filtroInstructor: ''
};

// Determinar fecha inicial inteligente
(function inicializarFechaPorDefecto() {
    if (GCAL_BLOQUES.length > 0) {
        const fechasOrdenadas = GCAL_BLOQUES.map(b => b.fecha).sort();
        const minFecha = fechasOrdenadas[0];
        const maxFecha = fechasOrdenadas[fechasOrdenadas.length - 1];

        const hoyStr = (new Date()).toISOString().split('T')[0];
        if (hoyStr >= minFecha && hoyStr <= maxFecha) {
            gcalEstado.fechaActual = new Date();
        } else {
            const partes = minFecha.split('-');
            gcalEstado.fechaActual = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        }
    }
})();

const NOMBRES_MESES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];
const NOMBRES_DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const NOMBRES_DIAS_CORTOS = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];

function formatearNombreMateria(materia) {
    if (!materia) return 'Formación Profesional';
    let nombre = materia;
    nombre = nombre.replace(/\s*-\s*PRINCIPAL\s*-\s*CONVERGENTES\s*4/gi, '');
    nombre = nombre.replace(/\s*-\s*CONVERGENTES\s*4/gi, '');
    nombre = nombre.replace(/\s*-\s*PRINCIPAL/gi, '');
    return nombre.trim();
}

function formatearHora12(horaStr) {
    if (!horaStr) return '';
    const partes = horaStr.split(':');
    let h = parseInt(partes[0]);
    const m = partes[1] || '00';
    const ampm = h >= 12 ? 'pm' : 'am';
    h = h % 12;
    h = h ? h : 12;
    return `${h < 10 ? '0' : ''}${h}:${m} ${ampm}`;
}

function obtenerClaseColor(materia) {
    const m = (materia || '').toUpperCase();
    if (m.includes('INGLÉS') || m.includes('INGLES') || m.includes('BILINGÜISMO')) {
        return { css: 'ev-bilinguismo', border: '#2563eb', label: 'Bilingüismo' };
    }
    if (m.includes('AMBIENTAL') || m.includes('SST') || m.includes('DERECHOS') || m.includes('COMUNICACIÓN') || m.includes('ÉTICA')) {
        return { css: 'ev-transversal', border: '#d97706', label: 'Transversal' };
    }
    if (m.includes('SOCIAL')) {
        return { css: 'ev-social', border: '#8b5cf6', label: 'Social' };
    }
    return { css: 'ev-tecnica', border: '#059669', label: 'Técnica' };
}

function obtenerBloquesFiltrados() {
    if (!gcalEstado.filtroInstructor) {
        return GCAL_BLOQUES;
    }
    const q = gcalEstado.filtroInstructor.toLowerCase();
    return GCAL_BLOQUES.filter(b => (b.instructor_nombre || '').toLowerCase().includes(q));
}

function estaEnCursoAhora(b) {
    if (!b || !b.fecha || !b.hora_inicio || !b.hora_fin) return false;
    const ahora = new Date();
    const y = ahora.getFullYear();
    const m = String(ahora.getMonth() + 1).padStart(2, '0');
    const d = String(ahora.getDate()).padStart(2, '0');
    const hoyStr = `${y}-${m}-${d}`;
    if (b.fecha !== hoyStr) return false;

    const minActual = (ahora.getHours() * 60) + ahora.getMinutes();
    const [iniH, iniM] = b.hora_inicio.substring(0, 5).split(':').map(Number);
    const [finH, finM] = b.hora_fin.substring(0, 5).split(':').map(Number);
    const minIni = (iniH * 60) + (iniM || 0);
    const minFin = (finH * 60) + (finM || 0);

    return (minActual >= minIni && minActual <= minFin);
}

function gcalCambiarVista(nuevaVista) {
    gcalEstado.vista = nuevaVista;
    document.querySelectorAll('.gcal-view-btn').forEach(btn => btn.classList.remove('active'));
    if (nuevaVista === 'semana') document.getElementById('btnViewSemana')?.classList.add('active');
    else if (nuevaVista === 'mes') document.getElementById('btnViewMes')?.classList.add('active');
    else if (nuevaVista === 'dia') document.getElementById('btnViewDia')?.classList.add('active');
    else if (nuevaVista === 'agenda') document.getElementById('btnViewAgenda')?.classList.add('active');

    const selMob = document.getElementById('gcalViewSelectMobile');
    if (selMob) selMob.value = nuevaVista;

    gcalRenderizar();
}

function gcalIrAHoy() {
    gcalEstado.fechaActual = new Date();
    gcalRenderizar();
}

function gcalNavegar(direccion) {
    const d = new Date(gcalEstado.fechaActual);
    if (gcalEstado.vista === 'mes') {
        d.setMonth(d.getMonth() + direccion);
    } else if (gcalEstado.vista === 'semana') {
        d.setDate(d.getDate() + (direccion * 7));
    } else if (gcalEstado.vista === 'dia') {
        d.setDate(d.getDate() + direccion);
    } else if (gcalEstado.vista === 'agenda') {
        d.setMonth(d.getMonth() + direccion);
    }
    gcalEstado.fechaActual = d;
    gcalRenderizar();
}

function gcalRenderizar() {
    const area = document.getElementById('gcalViewArea');
    const titleEl = document.getElementById('gcalTitle');
    if (!area || !titleEl) return;

    if (gcalEstado.vista === 'semana') {
        renderizarVistaSemana(area, titleEl);
    } else if (gcalEstado.vista === 'mes') {
        renderizarVistaMes(area, titleEl);
    } else if (gcalEstado.vista === 'dia') {
        renderizarVistaDia(area, titleEl);
    } else if (gcalEstado.vista === 'agenda') {
        renderizarVistaAgenda(area, titleEl);
    }
}

// 1. Vista Semana (06:00 am a 10:00 pm)
function renderizarVistaSemana(area, titleEl) {
    const cur = new Date(gcalEstado.fechaActual);
    let dayOfWeek = cur.getDay() - 1;
    if (dayOfWeek === -1) dayOfWeek = 6;

    const lunesSemana = new Date(cur);
    lunesSemana.setDate(cur.getDate() - dayOfWeek);

    const domingoSemana = new Date(lunesSemana);
    domingoSemana.setDate(lunesSemana.getDate() + 6);

    const mesIni = NOMBRES_MESES[lunesSemana.getMonth()];
    const mesFin = NOMBRES_MESES[domingoSemana.getMonth()];
    if (mesIni === mesFin) {
        titleEl.textContent = `${lunesSemana.getDate()} – ${domingoSemana.getDate()} de ${mesIni}, ${lunesSemana.getFullYear()}`;
    } else {
        titleEl.textContent = `${lunesSemana.getDate()} ${mesIni} – ${domingoSemana.getDate()} ${mesFin}, ${domingoSemana.getFullYear()}`;
    }

    const bloques = obtenerBloquesFiltrados();
    const hoyStr = (new Date()).toISOString().split('T')[0];

    const dias = [];
    const temp = new Date(lunesSemana);
    for (let i = 0; i < 7; i++) {
        dias.push(new Date(temp));
        temp.setDate(temp.getDate() + 1);
    }

    let html = `
        <div class="gcal-week-container">
            <div class="gcal-week-header-row">
                <div style="border-right:1px solid var(--border); padding:0.625rem 0.25rem; text-align:center; font-size:0.6875rem; font-weight:700; color:var(--muted-foreground);">
                    HORARIO
                </div>
    `;

    dias.forEach(d => {
        const fStr = d.toISOString().split('T')[0];
        const esHoy = (fStr === hoyStr);
        html += `
            <div class="gcal-week-header-col ${esHoy ? 'is-today' : ''}">
                <div class="gcal-week-day-title">${NOMBRES_DIAS_CORTOS[d.getDay()]}</div>
                <div class="gcal-week-day-circle">${d.getDate()}</div>
            </div>
        `;
    });

    html += `
            </div>
            <div class="gcal-week-body">
                <div class="gcal-time-col">
    `;

    for (let h = 6; h <= 22; h++) {
        const ampm = h >= 12 ? 'pm' : 'am';
        let h12 = h % 12;
        h12 = h12 ? h12 : 12;
        const labelHorario = `${h < 10 ? '0' : ''}${h}:00 (${h12} ${ampm})`;
        html += `<div class="gcal-time-slot-label">${labelHorario}</div>`;
    }

    html += `</div>`;

    dias.forEach(d => {
        const fStr = d.toISOString().split('T')[0];
        const clasesDia = bloques.filter(b => b.fecha === fStr);

        html += `<div class="gcal-week-day-col">`;

        for (let h = 6; h <= 22; h++) {
            html += `<div class="gcal-grid-hour-line"></div>`;
        }

        clasesDia.forEach(b => {
            const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
            const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';

            const [iniH, iniM] = hIni.split(':').map(Number);
            const [finH, finM] = hFin.split(':').map(Number);

            const minutosDesdeInicio = ((iniH - 6) * 60) + (iniM || 0);
            const duracionMinutos = ((finH - iniH) * 60) + ((finM || 0) - (iniM || 0));

            const topPx = Math.max(0, (minutosDesdeInicio / 60) * 56);
            const heightPx = Math.max(48, (duracionMinutos / 60) * 56 - 5);

            const colorInfo = obtenerClaseColor(b.materia);
            const nombreLimpio = formatearNombreMateria(b.materia);
            const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

            html += `
                <div class="gcal-week-card ${colorInfo.css}" 
                     style="top:${topPx}px; height:${heightPx}px;"
                     onclick="gcalAbrirModal(${b.id_horario_bloque})"
                     title="${hIni} a ${hFin}: ${b.materia}">
                    <div>
                        <div style="font-size:0.6875rem; font-weight:800; display:flex; justify-content:space-between; margin-bottom:0.125rem;">
                            <span>${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)}</span>
                        </div>
                        <div style="font-size:0.75rem; font-weight:700; line-height:1.25; margin-bottom:0.25rem;">
                            ${escaparHtml(nombreLimpio)}
                        </div>
                    </div>
                    <div style="font-size:0.6875rem; opacity:0.95; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500;">
                        👤 ${escaparHtml(b.instructor_nombre || 'Instructor')}
                    </div>
                </div>
            `;
        });

        html += `</div>`;
    });

    html += `
            </div>
        </div>
    `;

    area.innerHTML = html;
}

// 2. Vista Mes
function renderizarVistaMes(area, titleEl) {
    const anio = gcalEstado.fechaActual.getFullYear();
    const mes = gcalEstado.fechaActual.getMonth();

    titleEl.textContent = `${NOMBRES_MESES[mes]} de ${anio}`;

    const primerDiaMes = new Date(anio, mes, 1);
    let diaInicioSemana = primerDiaMes.getDay() - 1;
    if (diaInicioSemana === -1) diaInicioSemana = 6;

    const fechaInicioGrid = new Date(primerDiaMes);
    fechaInicioGrid.setDate(fechaInicioGrid.getDate() - diaInicioSemana);

    const bloques = obtenerBloquesFiltrados();
    const bloquesPorFecha = {};
    bloques.forEach(b => {
        if (!bloquesPorFecha[b.fecha]) bloquesPorFecha[b.fecha] = [];
        bloquesPorFecha[b.fecha].push(b);
    });

    const hoyStr = (new Date()).toISOString().split('T')[0];

    let html = `
        <div class="gcal-month-grid">
            <div class="gcal-month-header">Lunes</div>
            <div class="gcal-month-header">Martes</div>
            <div class="gcal-month-header">Miércoles</div>
            <div class="gcal-month-header">Jueves</div>
            <div class="gcal-month-header">Viernes</div>
            <div class="gcal-month-header">Sábado</div>
            <div class="gcal-month-header">Domingo</div>
        </div>
        <div class="gcal-month-body">
    `;

    const fechaIter = new Date(fechaInicioGrid);
    for (let c = 0; c < 35; c++) {
        const fechaStr = fechaIter.toISOString().split('T')[0];
        const numDia = fechaIter.getDate();
        const esMesActual = (fechaIter.getMonth() === mes);
        const esHoy = (fechaStr === hoyStr);

        const clasesDelDia = bloquesPorFecha[fechaStr] || [];

        html += `
            <div class="gcal-day-cell ${!esMesActual ? 'other-month' : ''} ${esHoy ? 'is-today' : ''}" 
                 onclick="gcalIrADia('${fechaStr}')" title="Clic para ver horario detallado del día ${numDia}">
                <div class="gcal-day-num">${numDia}</div>
                <div class="gcal-events-wrap">
        `;

        const maxVisibles = 3;
        for (let i = 0; i < Math.min(clasesDelDia.length, maxVisibles); i++) {
            const b = clasesDelDia[i];
            const colorInfo = obtenerClaseColor(b.materia);
            const horaIni = (b.hora_inicio || '06:00').substring(0, 5);
            const horaFin = (b.hora_fin || '09:00').substring(0, 5);
            const nombreLimpio = formatearNombreMateria(b.materia);
            const bTag = (b.bloque === 'bloque1') ? 'B1' : ((b.bloque === 'bloque2') ? 'B2' : 'B');

            html += `
                <div class="gcal-event-pill ${colorInfo.css}" 
                     onclick="event.stopPropagation(); gcalAbrirModal(${b.id_horario_bloque})"
                     title="${bTag} (${horaIni} a ${horaFin}): ${b.materia} - ${b.instructor_nombre}">
                    <span>[${bTag}] ${horaIni}</span> <strong>${escaparHtml(nombreLimpio)}</strong>
                </div>
            `;
        }

        if (clasesDelDia.length > maxVisibles) {
            const restantes = clasesDelDia.length - maxVisibles;
            html += `
                <div style="font-size:0.6875rem; color:#059669; font-weight:700; padding:0.125rem 0.25rem; cursor:pointer;"
                     onclick="event.stopPropagation(); gcalIrADia('${fechaStr}')">
                    +${restantes} más
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;

        fechaIter.setDate(fechaIter.getDate() + 1);
    }

    html += `</div>`;
    area.innerHTML = html;
}

// 3. Vista Día
function renderizarVistaDia(area, titleEl) {
    const cur = gcalEstado.fechaActual;
    const fStr = cur.toISOString().split('T')[0];
    const diaSemana = NOMBRES_DIAS[cur.getDay()];
    const diaNum = cur.getDate();
    const mesNombre = NOMBRES_MESES[cur.getMonth()];
    const anio = cur.getFullYear();

    titleEl.textContent = `${diaSemana}, ${diaNum} de ${mesNombre} de ${anio}`;

    const bloques = obtenerBloquesFiltrados();
    const clasesDia = bloques.filter(b => b.fecha === fStr);

    let html = `
        <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--muted);">
            <div>
                <span style="font-size:0.8125rem; font-weight:700; color:#059669; text-transform:uppercase;">Programación del Día (06:00 am – 10:00 pm)</span>
                <div style="font-size:1.125rem; font-weight:800; color:var(--foreground);">${diaSemana} ${diaNum} de ${mesNombre} de ${anio}</div>
            </div>
            <span class="shadcn-badge badge-secondary" style="font-size:0.8125rem;">
                ${clasesDia.length} bloque(s) programado(s)
            </span>
        </div>
        <div class="gcal-day-container">
            <div class="gcal-time-col">
    `;

    for (let h = 6; h <= 22; h++) {
        const ampm = h >= 12 ? 'pm' : 'am';
        let h12 = h % 12;
        h12 = h12 ? h12 : 12;
        const labelHorario = `${h < 10 ? '0' : ''}${h}:00 (${h12} ${ampm})`;
        html += `<div class="gcal-time-slot-label">${labelHorario}</div>`;
    }

    html += `</div><div style="position:relative; background:var(--card); height:952px;">`;

    for (let h = 6; h <= 22; h++) {
        html += `<div class="gcal-grid-hour-line"></div>`;
    }

    if (clasesDia.length === 0) {
        html += `
            <div style="position:absolute; top:80px; left:20px; right:20px; text-align:center; padding:3rem; background:rgba(0,0,0,0.02); border:1px dashed var(--border); border-radius:var(--radius-lg); color:var(--muted-foreground);">
                <i class="bi bi-calendar-check fs-2 d-block mb-2" style="color:#059669;"></i>
                <strong>No hay formación programada para este día en la ficha</strong>
                <p style="font-size:0.8125rem; margin:0.5rem 0 0 0;">Utiliza los botones de navegación o la vista de semana para revisar los días con clase.</p>
            </div>
        `;
    }

    clasesDia.forEach(b => {
        const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
        const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';

        const [iniH, iniM] = hIni.split(':').map(Number);
        const [finH, finM] = hFin.split(':').map(Number);

        const minutosDesdeInicio = ((iniH - 6) * 60) + (iniM || 0);
        const duracionMinutos = ((finH - iniH) * 60) + ((finM || 0) - (iniM || 0));

        const topPx = Math.max(0, (minutosDesdeInicio / 60) * 56);
        const heightPx = Math.max(60, (duracionMinutos / 60) * 56 - 6);

        const colorInfo = obtenerClaseColor(b.materia);
        const nombreLimpio = formatearNombreMateria(b.materia);
        const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

        html += `
            <div class="gcal-week-card ${colorInfo.css}" 
                 style="top:${topPx}px; height:${heightPx}px; left:16px; right:16px; padding:0.75rem 1.25rem;"
                 onclick="gcalAbrirModal(${b.id_horario_bloque})">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                    <span class="shadcn-badge" style="background:rgba(0,0,0,0.08); font-size:0.8125rem; font-weight:700;">
                        <i class="bi bi-clock me-1"></i>${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)} (${duracionMinutos / 60} horas)
                    </span>
                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">${colorInfo.label}</span>
                </div>
                <div style="font-size:1.0625rem; font-weight:800; line-height:1.3; margin-bottom:0.35rem;">
                    ${escaparHtml(nombreLimpio)}
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem;">
                    <span>👨‍🏫 <strong>${escaparHtml(b.instructor_nombre || 'Instructor')}</strong></span>
                    <span style="opacity:0.6;">•</span>
                    <span style="opacity:0.85;">${escaparHtml(b.instructor_correo || '')}</span>
                </div>
            </div>
        `;
    });

    html += `</div></div>`;
    area.innerHTML = html;
}

// 4. Vista Agenda
function renderizarVistaAgenda(area, titleEl) {
    const anio = gcalEstado.fechaActual.getFullYear();
    const mes = gcalEstado.fechaActual.getMonth();
    titleEl.textContent = `Agenda — ${NOMBRES_MESES[mes]} de ${anio}`;

    const mesStr = `${anio}-${(mes + 1 < 10 ? '0' : '') + (mes + 1)}`;
    const bloques = obtenerBloquesFiltrados().filter(b => (b.fecha || '').startsWith(mesStr));

    if (bloques.length === 0) {
        area.innerHTML = `
            <div style="text-align:center; padding:4rem 1rem; color:var(--muted-foreground);">
                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                <h3 style="font-weight:700;">No hay bloques programados en ${NOMBRES_MESES[mes]} ${anio}</h3>
                <p style="font-size:0.875rem;">Navega con las flechas o selecciona otro mes para explorar.</p>
            </div>
        `;
        return;
    }

    const bloquesPorFecha = {};
    bloques.forEach(b => {
        if (!bloquesPorFecha[b.fecha]) bloquesPorFecha[b.fecha] = [];
        bloquesPorFecha[b.fecha].push(b);
    });

    let html = `<div style="padding:1.25rem; display:flex; flex-direction:column; gap:1.25rem;">`;

    Object.keys(bloquesPorFecha).sort().forEach(fStr => {
        const clases = bloquesPorFecha[fStr];
        const partes = fStr.split('-');
        const dt = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        const diaNombre = NOMBRES_DIAS[dt.getDay()];
        const numDia = dt.getDate();

        html += `
            <div style="border:1px solid var(--border); border-radius:var(--radius-md); overflow:hidden; background:var(--card);">
                <div style="background:var(--muted); padding:0.625rem 1rem; font-weight:700; font-size:0.875rem; display:flex; justify-content:space-between; align-items:center;">
                    <span><i class="bi bi-calendar3 me-2" style="color:#059669;"></i>${diaNombre}, ${numDia} de ${NOMBRES_MESES[dt.getMonth()]} de ${dt.getFullYear()}</span>
                    <span class="shadcn-badge badge-secondary">${clases.length} clase(s)</span>
                </div>
                <div style="padding:0.75rem 1rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:0.75rem;">
        `;

        clases.forEach(b => {
            const colorInfo = obtenerClaseColor(b.materia);
            const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
            const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';
            const nombreLimpio = formatearNombreMateria(b.materia);
            const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

            html += `
                <div class="gcal-event-pill ${colorInfo.css}" 
                     style="padding:0.625rem 0.75rem; border-radius:6px; cursor:pointer;"
                     onclick="gcalAbrirModal(${b.id_horario_bloque})">
                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:0.25rem;">
                        <strong>${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)}</strong>
                        <span>${colorInfo.label}</span>
                    </div>
                    <div style="font-weight:700; font-size:0.875rem; line-height:1.3; margin-bottom:0.25rem;">
                        ${escaparHtml(nombreLimpio)}
                    </div>
                    <div style="font-size:0.75rem; opacity:0.9;">
                        👤 ${escaparHtml(b.instructor_nombre || 'Instructor')}
                    </div>
                </div>
            `;
        });

        html += `</div></div>`;
    });

    html += `</div>`;
    area.innerHTML = html;
}

function gcalIrADia(fechaStr) {
    const partes = fechaStr.split('-');
    gcalEstado.fechaActual = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    gcalCambiarVista('dia');
}

function gcalAbrirModal(idBloque) {
    const bloques = obtenerBloquesFiltrados();
    const b = bloques.find(item => item.id_horario_bloque == idBloque);
    if (!b) return;

    const colorInfo = obtenerClaseColor(b.materia);
    const nombreLimpio = formatearNombreMateria(b.materia);
    const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
    const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';
    const partes = (b.fecha || '').split('-');
    const dt = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    const fechaTexto = `${NOMBRES_DIAS[dt.getDay()]}, ${dt.getDate()} de ${NOMBRES_MESES[dt.getMonth()]} de ${dt.getFullYear()}`;
    const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

    document.getElementById('gcalModalStrip').style.background = colorInfo.border;
    document.getElementById('gcalModalBadge').textContent = colorInfo.label;
    document.getElementById('gcalModalBadge').style.background = colorInfo.border + '22';
    document.getElementById('gcalModalBadge').style.color = colorInfo.border;
    document.getElementById('gcalModalMateria').textContent = nombreLimpio;
    document.getElementById('gcalModalFecha').textContent = fechaTexto;
    document.getElementById('gcalModalHorario').textContent = `${formatearHora12(hIni)} – ${formatearHora12(hFin)} • ${nombreBloque}`;
    document.getElementById('gcalModalInstructor').textContent = b.instructor_nombre || 'Instructor Asignado';
    document.getElementById('gcalModalCorreo').textContent = b.instructor_correo || 'correo@sena.edu.co';

    const materiaCod = encodeURIComponent(b.materia + ' - Ficha ' + ID_FICHA_ACTUAL);
    const bloqueCod  = encodeURIComponent(hIni + '|' + hFin + '|' + (b.bloque || 'Bloque 1'));
    document.getElementById('gcalModalBtnAsistencia').href = `index.php?action=asistencia&materia=${materiaCod}&bloque=${bloqueCod}`;

    // Indicador en vivo dentro del modal
    const enCurso = estaEnCursoAhora(b);
    const liveIndicator = document.getElementById('gcalModalLiveIndicator');
    if (liveIndicator) {
        liveIndicator.style.display = enCurso ? 'inline-flex' : 'none';
    }

    const modal = document.getElementById('gcalModal');
    modal.style.display = 'flex';
}

function gcalCerrarModal(e) {
    if (!e || e.target.id === 'gcalModal' || e.target.closest('button')) {
        document.getElementById('gcalModal').style.display = 'none';
    }
}

// Cierre ágil del modal con la tecla Escape (ESC)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' || e.key === 'Esc') {
        const modal = document.getElementById('gcalModal');
        if (modal && modal.style.display === 'flex') {
            gcalCerrarModal();
        }
    }
});

function escaparHtml(texto) {
    if (!texto) return '';
    return texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function toggleTablaDetalle() {
    const cont = document.getElementById('contenedorTablaDetallada');
    const txt = document.getElementById('txtToggleTabla');
    if (!cont) return;
    if (cont.style.display === 'none') {
        cont.style.display = 'block';
        if (txt) txt.textContent = 'Ocultar Tabla Detallada';
        cont.scrollIntoView({ behavior: 'smooth' });
    } else {
        cont.style.display = 'none';
        if (txt) txt.textContent = 'Ver Tabla Detallada';
    }
}

// ─── TABS Y REPORTES GENERALES ───
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

        setTimeout(() => gcalRenderizar(), 50);
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

// Iniciar al cargar
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab === 'horarios') {
        switchTab('horarios');
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
