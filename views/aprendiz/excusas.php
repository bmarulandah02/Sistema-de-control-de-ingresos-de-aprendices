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

<?php $errorGet = $_GET['error'] ?? ($error ?? ''); ?>
<?php if (!empty($errorGet)): ?>
<div style="background-color:rgba(239,68,68,0.12); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.2);">
    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($errorGet) ?>
</div>
<?php endif; ?>

<?php if (($_GET['ok'] ?? '') == '1'): ?>
<div style="background-color:rgba(34,197,94,0.12); color:#16a34a; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(34,197,94,0.2);">
    <i class="bi bi-check-circle-fill me-1"></i> Excusa enviada con éxito. Pendiente de aprobación por tu instructor.
</div>
<?php endif; ?>

<?php if (($_GET['ok'] ?? '') == '2'): ?>
<div style="background-color:rgba(34,197,94,0.12); color:#16a34a; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(34,197,94,0.2);">
    <i class="bi bi-check-circle-fill me-1"></i> Excusa actualizada correctamente.
</div>
<?php endif; ?>

<?php
$idUsuarioSesion = (int)($_SESSION['usuario_id'] ?? 0);
$instructoresFicha = ExcusaModel::obtenerInstructoresPorUsuarioAprendiz($idUsuarioSesion);
?>

<div class="shadcn-card" style="margin-bottom: 1.75rem;">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-cloud-upload me-2"></i>Radicar Nueva Excusa Médica</h3>
    </div>
    <div class="card-body-shadcn">
        <form method="POST" action="index.php?action=excusa-subir" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Motivo de la Inasistencia *</label>
                    <textarea name="motivo" class="shadcn-input" rows="2" placeholder="Describe brevemente la incapacidad médica..." required></textarea>
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" class="shadcn-input" value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>" required>
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin *</label>
                    <input type="date" name="fecha_fin" class="shadcn-input" value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>" required>
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">
                        <i class="bi bi-person-badge me-1" style="color:var(--primary);"></i>Instructor Destinatario *
                    </label>
                    <select name="instructor_id" class="shadcn-select" required>
                        <option value="" disabled selected>-- Selecciona a qué instructor dirigir la excusa --</option>
                        <option value="todos">👥 Todos los instructores de mi ficha</option>
                        <option value="auto">⚡ Detectar automáticamente según horario de clases</option>
                        <optgroup label="Enviar a un instructor específico:">
                            <?php foreach ($instructoresFicha as $inst): ?>
                            <option value="<?= $inst['id_usuario'] ?>">
                                Inst. <?= htmlspecialchars($inst['nombre'] . ' ' . $inst['apellido']) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <small style="display:block; margin-top:0.35rem; color:var(--muted-foreground); font-size:0.75rem;">
                        <i class="bi bi-info-circle me-1"></i>Puedes elegir un instructor específico (ej. Bladimir Silva), todos los instructores de la ficha, o detección automática.
                    </small>
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
                    <th>Estado General</th>
                    <th>Revisión por Instructor(es)</th>
                    <th>Radicada el</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($excusas)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:2.5rem; color:var(--muted-foreground);">
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
                        elseif ($e['estado'] === 'Rechazada') $bClass = 'badge-rechazada';
                        elseif ($e['estado'] === 'Revisión Mixta') $bClass = 'badge-secondary';
                        ?>
                        <span class="shadcn-badge <?= $bClass ?>"><i class="bi bi-dot"></i><?= htmlspecialchars($e['estado']) ?></span>
                    </td>
                    <td style="min-width: 260px;">
                        <?php if (!empty($e['revisiones'])): ?>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($e['revisiones'] as $rev): ?>
                                    <?php
                                    $revBadge = 'badge-pendiente';
                                    $revIcon = 'bi-hourglass-split';
                                    if ($rev['estado'] === 'Aprobada') {
                                        $revBadge = 'badge-aprobada';
                                        $revIcon = 'bi-check-circle-fill';
                                    } elseif ($rev['estado'] === 'Rechazada') {
                                        $revBadge = 'badge-rechazada';
                                        $revIcon = 'bi-x-circle-fill';
                                    }
                                    ?>
                                    <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.4rem 0.6rem;">
                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                                            <span style="font-size:0.8rem; font-weight:600;">
                                                <i class="bi bi-person-badge me-1" style="color:var(--primary);"></i><?= htmlspecialchars($rev['nombre_instructor'] ?? 'Instructor') ?>
                                            </span>
                                            <span class="shadcn-badge <?= $revBadge ?>" style="font-size:0.7rem; padding:0.1rem 0.4rem;">
                                                <i class="bi <?= $revIcon ?> me-1"></i><?= htmlspecialchars($rev['estado']) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($rev['detalle_clases'])): ?>
                                        <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:2px;">
                                            <i class="bi bi-clock me-1"></i><?= htmlspecialchars($rev['detalle_clases']) ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($rev['estado'] === 'Rechazada' && !empty($rev['motivo_rechazo'])): ?>
                                        <div style="margin-top:0.35rem; font-size:0.75rem; color:#dc2626; background:rgba(239,68,68,0.1); padding:0.35rem 0.5rem; border-radius:4px; border-left:3px solid #dc2626;">
                                            <strong>Motivo de rechazo:</strong> <?= htmlspecialchars($rev['motivo_rechazo']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--muted-foreground); font-size:0.8rem;">En espera de revisión</span>
                            <?php if ($e['estado'] === 'Rechazada' && !empty($e['motivo_rechazo'])): ?>
                            <div style="margin-top:0.25rem; font-size:0.75rem; color:#dc2626;">
                                <strong>Motivo:</strong> <?= htmlspecialchars($e['motivo_rechazo']) ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(substr($e['created_at'], 0, 10)) ?></td>
                    <td>
                        <?php if ($e['estado'] === 'Pendiente'): ?>
                        <button type="button" class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;"
                                onclick="abrirEdicion(<?= (int)$e['id'] ?>, '<?= htmlspecialchars(addslashes($e['motivo']), ENT_QUOTES) ?>', '<?= htmlspecialchars(substr($e['fecha_inicio'],0,10)) ?>', '<?= htmlspecialchars(substr($e['fecha_fin'],0,10)) ?>')">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalEditarExcusa" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1000; align-items:center; justify-content:center;">
    <div class="shadcn-card" style="width:100%; max-width:480px; margin:1rem;">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-pencil-square me-2"></i>Editar Excusa Médica</h3>
        </div>
        <div class="card-body-shadcn">
            <form method="POST" action="index.php?action=excusa-editar" enctype="multipart/form-data">
                <input type="hidden" name="id_excusa" id="edit_id_excusa">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Motivo de la Inasistencia *</label>
                    <textarea name="motivo" id="edit_motivo" class="shadcn-input" rows="2" required></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio *</label>
                        <input type="date" name="fecha_inicio" id="edit_fecha_inicio" class="shadcn-input" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin *</label>
                        <input type="date" name="fecha_fin" id="edit_fecha_fin" class="shadcn-input" required>
                    </div>
                </div>
                <div style="margin-bottom:1.25rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Reemplazar Adjunto (opcional)</label>
                    <input type="file" name="archivo" class="shadcn-input" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                    <button type="button" class="btn-shadcn btn-shadcn-outline" onclick="cerrarEdicion()">Cancelar</button>
                    <button type="submit" class="btn-shadcn btn-shadcn-primary">
                        <i class="bi bi-save"></i> <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirEdicion(id, motivo, fechaInicio, fechaFin) {
    document.getElementById('edit_id_excusa').value = id;
    document.getElementById('edit_motivo').value = motivo;
    document.getElementById('edit_fecha_inicio').value = fechaInicio;
    document.getElementById('edit_fecha_fin').value = fechaFin;
    document.getElementById('modalEditarExcusa').style.display = 'flex';
}
function cerrarEdicion() {
    document.getElementById('modalEditarExcusa').style.display = 'none';
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>