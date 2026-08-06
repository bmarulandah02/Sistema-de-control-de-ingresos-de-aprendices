<?php
$pageTitle = 'Reportes & Excusas — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Reportes & Excusas Médicas</h1>
        <div class="page-header-subtitle">Genera reportes de inasistencias y gestiona las excusas remitidas por aprendices</div>
    </div>
</div>

<!-- ── 🟢 GENERADOR DE REPORTES PDF / CSV ───────────────────────── -->
<div class="shadcn-card" style="margin-bottom: 1.75rem;">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-file-earmark-pdf me-2"></i>Generador de Reporte de Inasistencias</h3>
    </div>
    <div class="card-body-shadcn">
        <form method="GET" action="index.php" id="filtroForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
            <input type="hidden" name="action" value="reporte-pdf">

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="shadcn-input" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? date('Y-m-01')) ?>">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="shadcn-input" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? date('Y-m-d')) ?>">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Ficha</label>
                <!-- 🟢 EDITAR AQUÍ: Recorre dinámicamente las fichas registradas -->
                <select name="ficha_id" class="shadcn-select">
                    <option value="">Todas las Fichas</option>
                    <?php foreach ($fichas ?? [] as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= (($_GET['ficha_id'] ?? '') == $f['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['numero_ficha'] . ' — ' . $f['programa']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="btn-shadcn btn-shadcn-primary" style="flex:1;">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
                <button type="button" class="btn-shadcn btn-shadcn-outline" style="flex:1;" onclick="exportarCSV()">
                    <i class="bi bi-filetype-csv"></i> CSV
                </button>
            </div>
        </form>
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
                <!-- 🟢 EDITAR AQUÍ: Muestra las excusas pendientes de tu BD -->
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
                            <!-- 🟢 EDITAR AQUÍ: Acciones para aprobar o rechazar la excusa -->
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
function exportarCSV() {
    const form = document.getElementById('filtroForm');
    const url = new URL(form.action, window.location.href);
    url.searchParams.set('action', 'reporte-excel');
    for (const el of form.elements) {
        if (el.name && el.name !== 'action') {
            url.searchParams.set(el.name, el.value);
        }
    }
    window.location.href = url.toString();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
