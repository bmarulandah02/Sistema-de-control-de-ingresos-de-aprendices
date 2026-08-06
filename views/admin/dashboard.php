<?php
$pageTitle = 'Dashboard — Shadcn Admin SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Dashboard</h1>
        <div class="page-header-subtitle">
            <!-- 🟢 EDITAR AQUÍ: Puedes cambiar el mensaje de bienvenida -->
            <?= date('l, d \d\e F \d\e Y') ?> — Resumen de ingresos y asistencia de aprendices.
        </div>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="index.php?action=asistencia" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-qr-code-scan"></i>
            <span>Terminal RFID</span>
        </a>
    </div>
</div>

<!-- ── 🟢 TARJETAS DE MÉTRICAS (METRIC CARDS) ───────────────────── -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.75rem;">
    <!-- 🟢 EDITAR AQUÍ: Reemplaza $statsHoy['total'] por tu variable o consulta SQL -->
    <div class="metric-card">
        <div class="metric-header">
            <span>Ingresos Hoy</span>
            <i class="bi bi-door-open metric-icon"></i>
        </div>
        <div class="metric-value"><?= (int) ($statsHoy['total'] ?? 0) ?></div>
        <div class="metric-footer">Registros marcados hoy</div>
    </div>

    <!-- 🟢 EDITAR AQUÍ: Reemplaza $statsHoy['puntuales'] por tu variable -->
    <div class="metric-card">
        <div class="metric-header">
            <span>A tiempo</span>
            <i class="bi bi-check-circle metric-icon" style="color:#16a34a;"></i>
        </div>
        <div class="metric-value" style="color:#16a34a;"><?= (int) ($statsHoy['puntuales'] ?? 0) ?></div>
        <div class="metric-footer">Ingresos sin retardo</div>
    </div>

    <!-- 🟢 EDITAR AQUÍ: Reemplaza $statsHoy['retardos'] por tu variable -->
    <div class="metric-card">
        <div class="metric-header">
            <span>Retardos</span>
            <i class="bi bi-clock-history metric-icon" style="color:#d97706;"></i>
        </div>
        <div class="metric-value" style="color:#d97706;"><?= (int) ($statsHoy['retardos'] ?? 0) ?></div>
        <div class="metric-footer">Llegadas después del límite</div>
    </div>

    <!-- 🟢 EDITAR AQUÍ: Reemplaza $statsAprendiz['activos'] por tu variable -->
    <div class="metric-card">
        <div class="metric-header">
            <span>Aprendices Activos</span>
            <i class="bi bi-people metric-icon"></i>
        </div>
        <div class="metric-value"><?= (int) ($statsAprendiz['activos'] ?? 0) ?></div>
        <div class="metric-footer">Inscritos en formación</div>
    </div>
</div>

<!-- ── 🟢 TABLA DE ÚLTIMOS MOVIMIENTOS ────────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-activity me-2"></i>Últimos movimientos del día</h3>
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
                <!-- 🟢 EDITAR AQUÍ: Aquí recorres con foreach($ultimos as $r) los datos de tu BD -->
                <?php if (empty($ultimos)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 2rem; color:var(--muted-foreground);">
                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                        No hay ingresos registrados el día de hoy.
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
