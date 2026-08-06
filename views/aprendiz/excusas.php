<?php
$pageTitle = 'Mis Excusas Médicas — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Mis Excusas Médicas</h1>
        <div class="page-header-subtitle">Radica excusas médicas para justificar inasistencias y hacer seguimiento a su estado</div>
    </div>
</div>

<!-- 🟢 EDITAR AQUÍ: Alertas de error o éxito -->
<?php if (!empty($error)): ?>
<div style="background-color:rgba(239,68,68,0.12); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.2);">
    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if (!empty($_GET['ok'])): ?>
<div style="background-color:rgba(34,197,94,0.12); color:#16a34a; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(34,197,94,0.2);">
    <i class="bi bi-check-circle-fill me-1"></i> Excusa enviada con éxito. Pendiente de aprobación por tu instructor.
</div>
<?php endif; ?>

<!-- ── 🟢 FORMULARIO DE RADICACIÓN DE EXCUSAS ───────────────────── -->
<div class="shadcn-card" style="margin-bottom: 1.75rem;">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-cloud-upload me-2"></i>Radicar Nueva Excusa Médica</h3>
    </div>
    <div class="card-body-shadcn">
        <!-- 🟢 EDITAR AQUÍ: Cambia action= por tu ruta de envío POST -->
        <form method="POST" action="index.php?action=excusa-subir" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Motivo de la Inasistencia *</label>
                    <textarea name="motivo" class="shadcn-input" rows="2" placeholder="Describe brevemente la incapacidad médica..." required></textarea>
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" class="shadcn-input" required>
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin *</label>
                    <input type="date" name="fecha_fin" class="shadcn-input" required>
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Adjunto (PDF, JPG, PNG) *</label>
                    <input type="file" name="archivo" class="shadcn-input" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>

            <button type="submit" class="btn-shadcn btn-shadcn-primary">
                <i class="bi bi-send"></i>
                <span>Enviar Excusa</span>
            </button>
        </form>
    </div>
</div>

<!-- ── 🟢 TABLA DE EXCUSAS RADICADAS ────────────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-file-earmark-medical me-2"></i>Historial de Excusas Radicadas</h3>
    </div>

    <div class="shadcn-table-wrapper">
        <table class="shadcn-table">
            <thead>
                <tr>
                    <th>Motivo</th>
                    <th>Período</th>
                    <th>Adjunto</th>
                    <th>Estado</th>
                    <th>Enviada el</th>
                    <th>Aprobada/Revisada por</th>
                </tr>
            </thead>
            <tbody>
                <!-- 🟢 EDITAR AQUÍ: Recorre con foreach($excusas as $e) los datos del aprendiz -->
                <?php if (empty($excusas)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:2.5rem; color:var(--muted-foreground);">
                        No has radicado excusas médicas aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($excusas as $e): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($e['motivo']) ?></td>
                    <td><?= htmlspecialchars($e['fecha_inicio']) ?> → <?= htmlspecialchars($e['fecha_fin']) ?></td>
                    <td>
                        <?php if ($e['archivo']): ?>
                        <a href="public/uploads/excusas/<?= htmlspecialchars($e['archivo']) ?>" target="_blank" class="btn-shadcn btn-shadcn-outline" style="padding:0.2rem 0.5rem; font-size:0.75rem;">
                            <i class="bi bi-paperclip"></i> Ver Documento
                        </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $bClass = 'badge-pendiente';
                        if ($e['estado'] === 'Aprobada') $bClass = 'badge-aprobada';
                        if ($e['estado'] === 'Rechazada') $bClass = 'badge-rechazada';
                        ?>
                        <span class="shadcn-badge <?= $bClass ?>"><i class="bi bi-dot"></i><?= htmlspecialchars($e['estado']) ?></span>
                    </td>
                    <td><?= htmlspecialchars(substr($e['created_at'], 0, 10)) ?></td>
                    <td><?= htmlspecialchars($e['aprobado_por'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
