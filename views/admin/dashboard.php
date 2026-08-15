<?php
$pageTitle = 'Dashboard — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem;">
    <div>
        <h1 class="page-header-title">Dashboard</h1>
        <div class="page-header-subtitle">
            <?= date('d/m/Y') ?> — Resumen de ingresos y asistencia de aprendices.
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <!-- ── FILTRO DINÁMICO POR FICHA DE FORMACIÓN ───────────────── -->
        <form method="GET" action="index.php" style="display:flex; align-items:center; gap:0.5rem; background:var(--card); padding:0.375rem 0.75rem; border:1px solid var(--border); border-radius:var(--radius);">
            <input type="hidden" name="action" value="dashboard">
            <i class="bi bi-funnel-fill" style="color:var(--sena-brand);"></i>
            <span style="font-size:0.8125rem; font-weight:600; color:var(--muted-foreground);">Ficha:</span>
            <select name="ficha_id" class="shadcn-select" onchange="this.form.submit()" style="padding:0.25rem 0.5rem; font-size:0.875rem; border:none; background:transparent;">
                <option value="">— Todas las Fichas —</option>
                <?php foreach ($fichas ?? [] as $f): ?>
                <option value="<?= $f['id'] ?>" <?= (($fichaSeleccionada ?? '') == $f['id']) ? 'selected' : '' ?>>
                    Ficha <?= htmlspecialchars($f['numero_ficha']) ?> — <?= htmlspecialchars($f['programa']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="index.php?action=asistencia" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-qr-code-scan"></i>
            <span>Terminal RFID</span>
        </a>
        <!-- agrego el nuevo boton para cerrar la jornada es decir para eliminar los datos que cumplen con el horario en la tabla de ingresos -->
         <a href="index.php?action=cerrar-jornada" class="btn-shadcn btn-shadcn-outline"
           onclick="return confirm('¿Cerrar la jornada de hoy? Se eliminarán los registros de aprendices que su estado es  (puntuales y con salida a tiempo).');">
            <i class="bi bi-moon-stars"></i>
            <span>Cerrar Jornada</span>
        </a>
    </div>
</div>
<!-- alerta de mantenmiento en el sistema -->
 <?php if(!empty($mensaje)):?>
    <div style="margin-bottom: 1.25 rem;" class="alert-auto-dismiss">
           <div class="shadcn-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-left: 4px solid var(--sena-brand);">
        <i class="bi bi-info-circle-fill fs-5" style="color:var(--sena-brand);"></i>
        <span style="font-size:0.9rem; font-weight:500;"><?= htmlspecialchars($mensaje['texto'] ?? '') ?></span>
    </div>
    </div>
    <?php endif; ?>

<!-- ── TARJETAS DE MÉTRICAS (METRIC CARDS) ───────────────────── -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.75rem;">
    <div class="metric-card">
        <div class="metric-header">
            <span>Ingresos Hoy</span>
            <i class="bi bi-door-open metric-icon"></i>
        </div>
        <div class="metric-value"><?= (int) ($statsHoy['total'] ?? 0) ?></div>
        <div class="metric-footer">Registros marcados hoy</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span>A tiempo</span>
            <i class="bi bi-check-circle metric-icon" style="color:#16a34a;"></i>
        </div>
        <div class="metric-value" style="color:#16a34a;"><?= (int) ($statsHoy['puntuales'] ?? 0) ?></div>
        <div class="metric-footer">Ingresos sin retardo</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span>Retardos</span>
            <i class="bi bi-clock-history metric-icon" style="color:#d97706;"></i>
        </div>
        <div class="metric-value" style="color:#d97706;"><?= (int) ($statsHoy['retardos'] ?? 0) ?></div>
        <div class="metric-footer">Llegadas después del límite</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span>Aprendices Activos</span>
            <i class="bi bi-people metric-icon"></i>
        </div>
        <div class="metric-value"><?= (int) ($statsAprendiz['activos'] ?? 0) ?></div>
        <div class="metric-footer">Inscritos en formación</div>
    </div>
</div>

<!-- ── TABLA DE ÚLTIMOS MOVIMIENTOS ────────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <h3><i class="bi bi-activity me-2"></i>Últimos movimientos del día</h3>
            <?php if (!empty($fichaSeleccionada)): ?>
            <span class="shadcn-badge badge-secondary">Filtro Ficha Activa</span>
            <?php endif; ?>
        </div>
        <a href="index.php?action=historial" class="btn-shadcn btn-shadcn-outline" style="font-size:0.75rem; padding:0.25rem 0.625rem;">
            Ver todos
        </a>
    </div>

    <div class="shadcn-table-wrapper">
        <table class="shadcn-table">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Documento</th>
                    <th>Ficha</th>
                    <th>Hora entrada</th>
                    <th>Hora salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimos)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                        No hay ingresos registrados el día de hoy para esta selección.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach (array_slice($ultimos, 0, 8) as $r): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($r['aprendiz']) ?></td>
                    <td><?= htmlspecialchars($r['documento']) ?></td>
                    <td><span class="shadcn-badge badge-secondary"><?= htmlspecialchars($r['numero_ficha']) ?></span></td>
                    <td><?= htmlspecialchars($r['hora_entrada']) ?></td>
                    <td><?= htmlspecialchars($r['hora_salida'] ?? '—') ?></td>
                    <td>
                        <?php $badgeClass = ($r['estado'] === 'Puntual') ? 'badge-puntual' : 'badge-retardo'; ?>
                        <span class="shadcn-badge <?= $badgeClass ?>">
                            <i class="bi bi-dot"></i><?= htmlspecialchars($r['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
