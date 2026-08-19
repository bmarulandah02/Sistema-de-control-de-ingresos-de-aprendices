<?php
$pageTitle = 'Fichas de Formación — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Fichas de Formación</h1>
        <div class="page-header-subtitle">Gestión de fichas, programas e instructores encargados</div>
    </div>
    <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
    <div>
        <a href="index.php?action=ficha-crear" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-plus-lg"></i>
            <span>+ Nueva Ficha</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ── 🟢 TABLA DE FICHAS ───────────────────────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-journal-bookmark me-2"></i>Fichas Registradas</h3>
        <span class="shadcn-badge badge-secondary"><?= count($fichas) ?> fichas</span>
    </div>

    <div class="shadcn-table-wrapper">
        <table class="shadcn-table">
            <thead>
                <tr>
                    <th>Nº Ficha</th>
                    <th>Programa</th>
                    <th>Jornada</th>
                    <th>Instructor Encargado</th>
                    <th>Aprendices</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                    <?php if (in_array($_SESSION['rol'] ?? '', ['Administrador', 'Instructor'])): ?>
                    <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fichas)): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:2.5rem; color:var(--muted-foreground);">
                        No existen fichas registradas aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($fichas as $f): ?>
                <tr>
                    <td style="font-weight:700; color:var(--sena-brand);"><?= htmlspecialchars($f['numero_ficha']) ?></td>
                    <td><?= htmlspecialchars($f['programa']) ?></td>
                    <td><span class="shadcn-badge badge-outline"><?= htmlspecialchars($f['jornada'] ?? 'Diurna') ?></span></td>
                    <td><?= htmlspecialchars($f['instructor'] ?? '—') ?></td>
                    <td>
                        <span class="shadcn-badge badge-secondary">
                            <?= (int) $f['total_aprendices'] ?> aprendices
                        </span>
                    </td>
                    <td><?= htmlspecialchars($f['fecha_inicio'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($f['fecha_fin'] ?? '—') ?></td>
                    <td>
                        <?php 
                            $bClass = 'badge-activo';
                            if ($f['estado'] === 'Inactivo') $bClass = 'badge-inactivo';
                            elseif ($f['estado'] === 'Finalizado') $bClass = 'badge-secondary';
                        ?>
                        <span class="shadcn-badge <?= $bClass ?>">
                            <i class="bi bi-dot"></i><?= htmlspecialchars($f['estado']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
                        <div style="display:flex; gap:0.375rem;">
                            <a href="index.php?action=ficha-editar&id=<?= $f['id'] ?>"
                               class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button"
                                    onclick="confirmarEliminarFicha(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['numero_ficha'])) ?>')"
                                    class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:#dc2626; border-color:rgba(239,68,68,0.3);" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <span style="color:var(--muted-foreground); font-size:0.75rem;"><i class="bi bi-eye me-1"></i>Solo Lectura</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmarEliminarFicha(id, numero) {
    Swal.fire({
        title: '¿Eliminar Ficha?',
        text: `¿Estás seguro de eliminar la ficha N° ${numero}? Esta acción eliminará su registro.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?action=ficha-eliminar&id=${id}`;
        }
    });
}

<?php if (isset($_GET['ok'])): ?>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: 'success',
        title: '¡Ficha Guardada!',
        text: 'La información de la ficha de formación ha sido guardada con éxito.',
        timer: 3000,
        showConfirmButton: false,
        timerProgressBar: true
    });
});
<?php endif; ?>
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
