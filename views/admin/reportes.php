<?php
$pageTitle = 'Reportes de Asistencia & Excusas — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Reportes de Asistencia</h1>
        <div class="page-header-subtitle">Genera reportes de retardos, inasistencias por aprendiz y gestiona excusas médicas</div>
    </div>
</div>

<!-- ── 🟢 FILTROS RÁPIDOS Y GENERADOR DE REPORTES ───────────────────────── -->
<div class="shadcn-card" style="margin-bottom: 1.75rem;">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-funnel me-2"></i>Generador y Filtro de Reportes</h3>
    </div>
    <div class="card-body-shadcn">
        <form method="GET" action="index.php" id="filtroForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; align-items: end;">
            <input type="hidden" name="action" value="reportes">

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

            <div style="display:flex; gap:0.5rem; grid-column: 1 / -1; justify-content: flex-end; margin-top:0.5rem;">
                <button type="submit" class="btn-shadcn btn-shadcn-outline">
                    <i class="bi bi-search"></i> Consultar
                </button>
                <button type="button" class="btn-shadcn btn-shadcn-primary" onclick="exportarPDF()">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                </button>
                <button type="button" class="btn-shadcn btn-shadcn-primary" style="background:#16a34a;" onclick="exportarExcel()">
                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel (CSV)
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── 🟢 CONSOLIDADO EN PANTALLA: RETARDOS E INASISTENCIAS ───────────────── -->
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
                    <th>Minutos de Retardo</th>
                    <th>Días Inasistentes</th>
                    <th>Días Específicos Faltados & Instructor</th>
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

<!-- ── 🟢 COLA DE APROBACIÓN DE EXCUSAS MÉDICAS ────────────────── -->
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

<script>
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
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
