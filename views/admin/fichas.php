<?php
$pageTitle = 'Fichas de Formación — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
$estadoFiltro = $_GET['estado'] ?? 'Activo';
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

<!-- ── BARRA DE FILTROS Y ESTADO ────────────────────────────────────── -->
<div class="shadcn-card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="index.php" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; justify-content:space-between;">
        <input type="hidden" name="action" value="fichas">

        <!-- Pestañas de Estado -->
        <div style="display:flex; gap:0.375rem; background:var(--muted); padding:0.25rem; border-radius:var(--radius-md);">
            <a href="index.php?action=fichas&estado=Activo<?= !empty($_GET['q']) ? '&q='.urlencode($_GET['q']) : '' ?>" 
               class="btn-shadcn <?= $estadoFiltro === 'Activo' ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
               style="padding:0.375rem 0.875rem; font-size:0.8125rem; text-decoration:none;">
                <i class="bi bi-check-circle me-1"></i>Activas
            </a>
            <a href="index.php?action=fichas&estado=Finalizado<?= !empty($_GET['q']) ? '&q='.urlencode($_GET['q']) : '' ?>" 
               class="btn-shadcn <?= $estadoFiltro === 'Finalizado' ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
               style="padding:0.375rem 0.875rem; font-size:0.8125rem; text-decoration:none;">
                <i class="bi bi-archive me-1"></i>Finalizadas / Ocultas
            </a>
            <a href="index.php?action=fichas&estado=Todos<?= !empty($_GET['q']) ? '&q='.urlencode($_GET['q']) : '' ?>" 
               class="btn-shadcn <?= $estadoFiltro === 'Todos' ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
               style="padding:0.375rem 0.875rem; font-size:0.8125rem; text-decoration:none;">
                <i class="bi bi-collection me-1"></i>Todas
            </a>
        </div>

        <input type="hidden" name="estado" value="<?= htmlspecialchars($estadoFiltro) ?>">

        <!-- Buscar -->
        <div style="display:flex; gap:0.5rem; flex:1; max-width:400px; align-items:flex-end;">
            <div style="flex:1;">
                <label style="display:block; font-size:0.8125rem; font-weight:500; margin-bottom:0.375rem; color:var(--muted-foreground);">
                    <i class="bi bi-search me-1"></i>Buscar Ficha, Programa o Instructor
                </label>
                <input type="text" name="q" class="shadcn-input" placeholder="Ej: 2554321, ADSO, Carlos..." 
                       value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-shadcn btn-shadcn-primary">
                <i class="bi bi-funnel-fill me-1"></i>Filtrar
            </button>
            <?php if (!empty($_GET['q'])): ?>
                <a href="index.php?action=fichas&estado=<?= urlencode($estadoFiltro) ?>" class="btn-shadcn btn-shadcn-outline" title="Limpiar filtro">
                    <i class="bi bi-x-circle me-1"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── TABLA DE FICHAS ───────────────────────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3>
            <i class="bi bi-journal-bookmark me-2"></i>
            <?php 
                if ($estadoFiltro === 'Finalizado') echo 'Fichas Finalizadas / Ocultas';
                elseif ($estadoFiltro === 'Todos') echo 'Todas las Fichas Registradas';
                else echo 'Fichas Activas';
            ?>
        </h3>
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
                        No se encontraron fichas <?= $estadoFiltro === 'Finalizado' ? 'finalizadas' : ($estadoFiltro === 'Activo' ? 'activas' : '') ?> con los criterios ingresados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($fichas as $f): ?>
                <tr>
                    <td style="font-weight:700; color:var(--sena-brand);"><?= htmlspecialchars($f['numero_ficha']) ?></td>
                    <td><?= htmlspecialchars($f['programa']) ?></td>
                    <td><span class="shadcn-badge badge-outline"><?= htmlspecialchars($f['jornada'] ?? 'Diurna') ?></span></td>
                    <td>
                        <?php if (!empty($f['instructor'])): ?>
                            <div style="font-weight: 600; line-height: 1.2;"><?= htmlspecialchars($f['instructor']) ?></div>
                            <?php if (!empty($f['instructor_identificacion']) || !empty($f['instructor_correo'])): ?>
                                <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:2px;">
                                    <?php if (!empty($f['instructor_identificacion'])): ?>
                                        <span><i class="bi bi-card-text me-1"></i><?= htmlspecialchars($f['instructor_identificacion']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($f['instructor_correo'])): ?>
                                        <span style="margin-left: 6px;"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($f['instructor_correo']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="shadcn-badge badge-outline" style="color:var(--muted-foreground);">Sin instructor</span>
                        <?php endif; ?>
                    </td>
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
                               class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Editar Ficha">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <?php if ($f['estado'] === 'Finalizado'): ?>
                                <button type="button"
                                        onclick="confirmarReactivarFicha(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['numero_ficha'])) ?>')"
                                        class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:#059669; border-color:rgba(5,150,105,0.3);" title="Reactivar Ficha">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reactivar
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        onclick="confirmarOcultarFicha(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['numero_ficha'])) ?>', <?= (int)$f['total_aprendices'] ?>)"
                                        class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:#dc2626; border-color:rgba(239,68,68,0.3);" title="Finalizar / Ocultar Ficha">
                                    <i class="bi bi-archive"></i> Ocultar
                                </button>
                            <?php endif; ?>
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
function confirmarOcultarFicha(id, numero, totalAprendices) {
    if (totalAprendices > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No se puede ocultar la ficha',
            html: `La ficha N° <strong>${numero}</strong> aún tiene <strong>${totalAprendices}</strong> aprendices registrados en el sistema.<br><br>Para mantener un registro seguro, las fichas con aprendices activos no pueden ser ocultadas.`,
            confirmButtonColor: '#059669',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    Swal.fire({
        title: '¿Finalizar y Ocultar Ficha?',
        text: `La ficha N° ${numero} pasará al apartado de "Fichas Finalizadas / Ocultas". Podrás consultar o reactivar su registro en cualquier momento.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, finalizar y ocultar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?action=ficha-eliminar&id=${id}`;
        }
    });
}

function confirmarReactivarFicha(id, numero) {
    Swal.fire({
        title: '¿Reactivar Ficha?',
        text: `La ficha N° ${numero} volverá a estar activa en el sistema.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?action=ficha-reactivar&id=${id}`;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const errorParam = urlParams.get('error');
    const okParam = urlParams.get('ok');
    const cantParam = urlParams.get('cant');
    const numParam = urlParams.get('num');

    if (errorParam === 'ficha_con_aprendices') {
        Swal.fire({
            icon: 'warning',
            title: 'No se puede ocultar la ficha',
            html: `La ficha N° <strong>${numParam || ''}</strong> aún tiene <strong>${cantParam || 'varios'}</strong> aprendices registrados.<br><br>Para mantener la trazabilidad e historial, no se puede ocultar mientras tenga aprendices vinculados.`,
            confirmButtonColor: '#059669',
            confirmButtonText: 'Entendido'
        });
    } else if (okParam === 'finalizada') {
        Swal.fire({
            icon: 'success',
            title: '¡Ficha Finalizada y Oculta!',
            text: 'La ficha ha sido trasladada al apartado de Fichas Finalizadas.',
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    } else if (okParam === 'reactivada') {
        Swal.fire({
            icon: 'success',
            title: '¡Ficha Reactivada!',
            text: 'La ficha vuelve a estar disponible entre las fichas activas.',
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    } else if (okParam === '1') {
        Swal.fire({
            icon: 'success',
            title: '¡Ficha Guardada!',
            text: 'La información de la ficha de formación ha sido guardada con éxito.',
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

