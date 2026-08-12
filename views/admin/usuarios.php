<?php
$pageTitle = 'Usuarios y Aprendices — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Gestión de Usuarios</h1>
        <div class="page-header-subtitle">Administra los Instructores, Aprendices y Usuarios registrados</div>
    </div>
    <div>
        <a href="index.php?action=usuario-crear" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-person-plus-fill"></i>
            <span>+ Nuevo Usuario</span>
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div style="background-color:rgba(34,197,94,0.1); color:#16a34a; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.5rem; border:1px solid rgba(34,197,94,0.2); display:flex; align-items:center; gap:0.5rem;">
    <i class="bi bi-check-circle-fill"></i>
    <span>Usuario registrado correctamente en la base de datos.</span>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                        <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                        No hay usuarios registrados en el sistema.
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
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
