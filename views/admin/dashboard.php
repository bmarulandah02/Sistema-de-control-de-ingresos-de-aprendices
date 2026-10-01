<?php
$pageTitle = 'Dashboard — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem;">
    <div>
        <h1 class="page-header-title">Dashboard</h1>
        <div class="page-header-subtitle">
            <?= date('d/m/Y') ?> — <?= (($_SESSION['rol'] ?? '') === 'Instructor') ? 'Resumen de tus asistencias registradas como instructor.' : 'Resumen general de ingresos y asistencia de aprendices.' ?>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <!-- ── FILTRO DINÁMICO POR FICHA DE FORMACIÓN ───────────────── -->
        <form method="GET" action="index.php" style="display:flex; align-items:center; gap:0.5rem; background:var(--card); padding:0.375rem 0.75rem; border:1px solid var(--border); border-radius:var(--radius);">
            <input type="hidden" name="action" value="dashboard">
            <i class="bi bi-funnel-fill" style="color:var(--sena-brand);"></i>
            <span style="font-size:0.8125rem; font-weight:600; color:var(--muted-foreground);">Ficha:</span>
            <select name="ficha_id" class="shadcn-select" onchange="this.form.submit()" style="padding:0.25rem 0.5rem; font-size:0.875rem; border:none; background:transparent;">
                <option value="">— <?= (($_SESSION['rol'] ?? '') === 'Instructor') ? 'Todas tus fichas' : 'Todas las Fichas' ?> —</option>
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
        <!-- Botón para cerrar jornada: solo limpia los registros del instructor en sesión si es Instructor -->
         <a href="index.php?action=cerrar-jornada" class="btn-shadcn btn-shadcn-outline"
           onclick="return confirm('¿Cerrar la jornada de hoy? Se limpiarán los registros de aprendices correspondientes a hoy.');">
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
        <div class="metric-footer"><?= (($_SESSION['rol'] ?? '') === 'Instructor') ? 'Registrados por ti hoy' : 'Registros marcados hoy' ?></div>
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
        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <h3><i class="bi bi-activity me-2"></i>Últimos movimientos del día</h3>
            <?php if (($_SESSION['rol'] ?? '') === 'Instructor'): ?>
            <span class="shadcn-badge badge-puntual"><i class="bi bi-person-check me-1"></i>Tus Registros</span>
            <?php endif; ?>
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
                    <th>Materia / Bloque</th>
                    <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
                    <th>Instructor Registro</th>
                    <?php endif; ?>
                    <th>Hora entrada</th>
                    <th>Hora salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimos)): ?>
                <tr>
                    <td colspan="<?= (($_SESSION['rol'] ?? '') === 'Administrador') ? '8' : '7' ?>" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
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
                    <td>
                        <?php if (!empty($r['materia'])): ?>
                            <div style="font-size:0.8125rem; font-weight:600;"><?= htmlspecialchars($r['materia']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['bloque'])): ?>
                            <div style="font-size:0.75rem; color:var(--muted-foreground);"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($r['bloque']) ?></div>
                        <?php endif; ?>
                        <?php if (empty($r['materia']) && empty($r['bloque'])): ?>
                            <span style="color:var(--muted-foreground);">—</span>
                        <?php endif; ?>
                    </td>
                    <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
                    <td>
                        <span class="shadcn-badge badge-secondary" style="font-size:0.75rem;">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($r['instructor'] ?? '—') ?>
                        </span>
                    </td>
                    <?php endif; ?>
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
