<?php
$pageTitle = 'Usuarios y Aprendices — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Gestión de Usuarios</h1>
        <div class="page-header-subtitle">Administra los Instructores, Aprendices y Usuarios registrados</div>
    </div>
    <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
    <div>
        <a href="index.php?action=usuario-crear" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-person-plus-fill"></i>
            <span>+ Nuevo Usuario</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'sin_permiso'): ?>
<div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2); display:flex; align-items:center; gap:0.5rem;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>No tienes permisos para realizar esta acción sobre este perfil.</span>
</div>
<?php endif; ?>

<!-- ── TABLA DE USUARIOS ────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-people me-2"></i>Usuarios Registrados</h3>
        <span class="shadcn-badge badge-secondary"><?= count($usuarios) ?> registros</span>
    </div>

    <div class="shadcn-table-wrapper">
        <table class="shadcn-table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Identificación</th>
                    <th>Correo / Usuario</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Ficha / RFID</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                        <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                        No hay usuarios registrados visibles.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['identificacion']) ?></td>
                    <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                    <td><?= htmlspecialchars($u['telefono'] ?: '—') ?></td>
                    <td>
                        <?php 
                            $badgeStyle = 'badge-secondary';
                            if ($u['rol'] === 'Administrador') $badgeStyle = 'badge-puntual';
                            elseif ($u['rol'] === 'Instructor') $badgeStyle = 'badge-retardo';
                        ?>
                        <span class="shadcn-badge <?= $badgeStyle ?>">
                            <i class="bi bi-shield-lock me-1"></i><?= htmlspecialchars($u['rol']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['rol'] === 'Aprendiz'): ?>
                            <span class="shadcn-badge badge-secondary" title="Ficha">
                                Ficha: <?= htmlspecialchars($u['numero_ficha'] ?? 'N/A') ?>
                            </span>
                            <?php if (!empty($u['codigo_rfid'])): ?>
                            <span class="shadcn-badge badge-outline" title="Código RFID Tag">
                                RFID: <?= htmlspecialchars($u['codigo_rfid']) ?>
                            </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--muted-foreground); font-size:0.8125rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $esAdmin = (($_SESSION['rol'] ?? '') === 'Administrador'); ?>
                        <?php if ($esAdmin): ?>
                        <div style="display:flex; gap:0.375rem;">
                            <a href="index.php?action=usuario-editar&id=<?= $u['id_usuario'] ?>" 
                               class="btn-shadcn btn-shadcn-outline" 
                               style="padding:0.25rem 0.5rem; font-size:0.75rem;" 
                               title="Editar Usuario">
                                <i class="bi bi-pencil"></i>
                                <span>Editar</span>
                            </a>
                            <?php if ($u['id_usuario'] != $_SESSION['usuario_id']): ?>
                            <button type="button" 
                                    onclick="confirmarEliminarUsuario(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>')" 
                                    class="btn-shadcn btn-shadcn-outline" 
                                    style="padding:0.25rem 0.5rem; font-size:0.75rem; color:#dc2626; border-color:rgba(239,68,68,0.3);" 
                                    title="Eliminar Usuario">
                                <i class="bi bi-trash"></i>
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
function confirmarEliminarUsuario(id, nombre) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: `¿Estás seguro de eliminar a "${nombre}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?action=usuario-eliminar&id=${id}`;
        }
    });
}

<?php if (isset($_GET['success'])): ?>
document.addEventListener('DOMContentLoaded', () => {
    let msg = 'Operación realizada con éxito.';
    let title = '¡Éxito!';
    <?php if ($_GET['success'] === 'registrado'): ?>
    msg = 'El usuario ha sido registrado correctamente en el sistema.';
    title = '¡Usuario Creado!';
    <?php elseif ($_GET['success'] === 'actualizado'): ?>
    msg = 'Los datos del usuario han sido actualizados con éxito.';
    title = '¡Usuario Actualizado!';
    <?php elseif ($_GET['success'] === 'eliminado'): ?>
    msg = 'El usuario ha sido eliminado correctamente.';
    title = '¡Usuario Eliminado!';
    <?php endif; ?>

    Swal.fire({
        icon: 'success',
        title: title,
        text: msg,
        timer: 3000,
        showConfirmButton: false,
        timerProgressBar: true
    });
});
<?php endif; ?>
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
