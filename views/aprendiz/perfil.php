<?php
$pageTitle = 'Mi Perfil — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Mi Perfil de Aprendiz</h1>
        <div class="page-header-subtitle">Historial personal de asistencia, ingresos e inasistencias</div>
    </div>
</div>

<!-- 🟢 EDITAR AQUÍ: Valida si la variable $aprendiz existe -->
<?php if (!isset($aprendiz)): ?>
<div style="background-color:rgba(245,158,11,0.12); color:#d97706; padding:1rem; border-radius:var(--radius); border:1px solid rgba(245,158,11,0.2);">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> Tu usuario de sesión no está vinculado a una ficha de aprendiz.
</div>
<?php else: ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
    <!-- ── 🟢 TARJETA DE INFORMACIÓN DEL APRENDIZ ───────────────────── -->
    <div class="shadcn-card" style="align-self: start;">
        <div class="card-body-shadcn" style="text-align: center;">
            <div style="width:72px; height:72px; border-radius:50%; background:var(--sena-brand-subtle); color:var(--sena-brand); display:flex; align-items:center; justify-content:center; font-size:2rem; margin: 0 auto 1rem; font-weight:700;">
                <?= strtoupper(substr($aprendiz['nombre'], 0, 1)) ?>
            </div>
            <!-- 🟢 EDITAR AQUÍ: Nombre y documento del aprendiz -->
            <h3 style="font-size:1.1rem; font-weight:700; margin:0;"><?= htmlspecialchars($aprendiz['nombre']) ?></h3>
            <div style="font-size:0.875rem; color:var(--muted-foreground); margin-top:0.25rem;"><?= htmlspecialchars($aprendiz['documento']) ?></div>

            <div style="margin-top:0.75rem;">
                <span class="shadcn-badge badge-activo"><i class="bi bi-dot"></i><?= htmlspecialchars($aprendiz['estado']) ?></span>
            </div>

            <hr style="border:0; border-top:1px solid var(--border); margin:1.25rem 0;">

            <!-- 🟢 EDITAR AQUÍ: Ficha, programa, correo y teléfono -->
            <div style="text-align:left; font-size:0.875rem; display:flex; flex-direction:column; gap:0.625rem;">
                <div><strong>Ficha:</strong> <?= htmlspecialchars($aprendiz['numero_ficha'] ?? '—') ?></div>
                <div><strong>Programa:</strong> <?= htmlspecialchars($aprendiz['programa'] ?? '—') ?></div>
                <div><strong>Correo:</strong> <?= htmlspecialchars($aprendiz['correo']) ?></div>
                <div><strong>Teléfono:</strong> <?= htmlspecialchars($aprendiz['telefono'] ?? '—') ?></div>
            </div>
        </div>
    </div>

    <!-- ── 🟢 TABLA DE ASISTENCIAS RECIENTES ────────────────────────── -->
    <div class="shadcn-card">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-clock-history me-2"></i>Mis Asistencias Recientes</h3>
        </div>

        <div class="shadcn-table-wrapper">
            <table class="shadcn-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 🟢 EDITAR AQUÍ: Recorre con foreach($asistencias as $a) los registros -->
                    <?php if (empty($asistencias)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:2rem; color:var(--muted-foreground);">
                            No tienes asistencias registradas aún.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($asistencias as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['fecha']) ?></td>
                        <td><?= htmlspecialchars($a['hora_entrada']) ?></td>
                        <td><?= htmlspecialchars($a['hora_salida'] ?? '—') ?></td>
                        <td>
                            <?php $bClass = ($a['estado'] === 'Puntual') ? 'badge-puntual' : 'badge-retardo'; ?>
                            <span class="shadcn-badge <?= $bClass ?>"><i class="bi bi-dot"></i><?= htmlspecialchars($a['estado']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
