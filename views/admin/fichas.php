<?php
$pageTitle = 'Fichas & Horarios — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Fichas de Formación</h1>
        <div class="page-header-subtitle">Gestión de cursos, horarios e instructores encargados</div>
    </div>
    <?php if (in_array($_SESSION['rol'] ?? '', ['Administrador', 'Instructor'])): ?>
    <div>
        <!-- 🟢 EDITAR AQUÍ: Botón para crear nueva ficha -->
        <a href="index.php?action=ficha-crear" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-plus-lg"></i>
            <span>Nueva Ficha</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- 🟢 EDITAR AQUÍ: Mensaje de confirmación -->
<?php if (!empty($_GET['ok'])): ?>
<div style="background-color:rgba(34,197,94,0.12); color:#16a34a; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(34,197,94,0.2);">
    <i class="bi bi-check-circle-fill me-1"></i> Operación completada con éxito.
</div>
<?php endif; ?>

<!-- ── 🟢 TABLA DE FICHAS REGISTRADAS ─────────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-journal-bookmark me-2"></i>Fichas Registradas</h3>
    </div>

    <div class="shadcn-table-wrapper">
        <table class="shadcn-table">
            <thead>
                <tr>
                    <th>Nº Ficha</th>
                    <th>Programa</th>
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
                <!-- 🟢 EDITAR AQUÍ: Recorre con foreach($fichas as $f) los registros de tu BD -->
                <?php if (empty($fichas)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:2.5rem; color:var(--muted-foreground);">
                        No existen fichas registradas aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($fichas as $f): ?>
                <tr>
                    <td style="font-weight:700; color:var(--sena-brand);"><?= htmlspecialchars($f['numero_ficha']) ?></td>
                    <td><?= htmlspecialchars($f['programa']) ?></td>
                    <td><?= htmlspecialchars($f['instructor'] ?? '—') ?></td>
                    <td>
                        <span class="shadcn-badge badge-secondary">
                            <?= (int) $f['total_aprendices'] ?> aprendices
                        </span>
                    </td>
                    <td><?= htmlspecialchars($f['fecha_inicio']) ?></td>
                    <td><?= htmlspecialchars($f['fecha_fin']) ?></td>
                    <td>
                        <?php $bClass = ($f['estado'] === 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>
                        <span class="shadcn-badge <?= $bClass ?>">
                            <i class="bi bi-dot"></i><?= htmlspecialchars($f['estado']) ?>
                        </span>
                    </td>
                    <?php if (in_array($_SESSION['rol'] ?? '', ['Administrador', 'Instructor'])): ?>
                    <td>
                        <div style="display:flex; gap:0.375rem;">
                            <!-- 🟢 EDITAR AQUÍ: Enlace para editar ficha -->
                            <a href="index.php?action=ficha-editar&id=<?= $f['id'] ?>"
                               class="btn-shadcn btn-shadcn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                            <!-- 🟢 EDITAR AQUÍ: Enlace para eliminar ficha -->
                            <a href="index.php?action=ficha-eliminar&id=<?= $f['id'] ?>"
                               class="btn-shadcn btn-shadcn-danger" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Eliminar"
                               onclick="return confirm('¿Eliminar la ficha <?= htmlspecialchars($f['numero_ficha']) ?>?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
