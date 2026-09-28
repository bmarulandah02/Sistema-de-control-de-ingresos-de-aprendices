<?php
$pageTitle = 'Mi Perfil — Control de Ingresos SENA';
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Mi Perfil</h1>
        <div class="page-header-subtitle">Información personal, datos de contacto y actualización de clave</div>
    </div>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'perfil_actualizado'): ?>
<div style="background-color:rgba(34,197,94,0.1); color:#16a34a; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.5rem; border:1px solid rgba(34,197,94,0.2); display:flex; align-items:center; gap:0.5rem;">
    <i class="bi bi-check-circle-fill"></i>
    <span>Tus datos de perfil y contraseña se han actualizado correctamente.</span>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- ── TARJETA DE INFORMACIÓN DEL USUARIO ───────────────────── -->
    <div class="shadcn-card" style="align-self: start;">
        <div class="card-body-shadcn" style="text-align: center; padding:1.5rem;">
            <div style="width:72px; height:72px; border-radius:50%; background:var(--sena-brand-subtle); color:var(--sena-brand); display:flex; align-items:center; justify-content:center; font-size:2rem; margin: 0 auto 1rem; font-weight:700;">
                <?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <h3 style="font-size:1.1rem; font-weight:700; margin:0;"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></h3>
            <div style="font-size:0.875rem; color:var(--muted-foreground); margin-top:0.25rem;"><?= htmlspecialchars($_SESSION['correo'] ?? '') ?></div>

            <div style="margin-top:0.75rem; display:flex; justify-content:center; gap:0.375rem; align-items:center;">
                <span class="shadcn-badge badge-activo"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?></span>
                <?php if (isset($aprendiz)): ?>
                    <?php 
                        $estAp = $aprendiz['estado'] ?? 'Activo';
                        $stBadgeAp = 'badge-puntual';
                        if ($estAp === 'Aplazado') $stBadgeAp = 'badge-retardo';
                        elseif ($estAp === 'Suspendido') $stBadgeAp = 'badge-secondary';
                        elseif ($estAp === 'Retirado') $stBadgeAp = 'badge-destructive';
                    ?>
                    <span class="shadcn-badge <?= $stBadgeAp ?>"><i class="bi bi-dot"></i><?= htmlspecialchars($estAp) ?></span>
                <?php endif; ?>
            </div>

            <?php if (isset($aprendiz)): ?>
            <hr style="border:0; border-top:1px solid var(--border); margin:1.25rem 0;">
            <div style="text-align:left; font-size:0.875rem; display:flex; flex-direction:column; gap:0.625rem;">
                <div><strong>Ficha:</strong> <?= htmlspecialchars($aprendiz['numero_ficha'] ?? '—') ?></div>
                <div><strong>Programa:</strong> <?= htmlspecialchars($aprendiz['programa'] ?? '—') ?></div>
                <div><strong>Documento:</strong> <?= htmlspecialchars($aprendiz['documento'] ?? '—') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── FORMULARIO PARA ACTUALIZAR TELÉFONO Y CLAVE ───────────── -->
    <div class="shadcn-card">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-gear me-2"></i>Editar Datos de Contacto y Clave</h3>
        </div>

        <div class="card-body-shadcn" style="padding: 1.5rem;">
            <form method="POST" action="index.php?action=mi-perfil-guardar">
                <div style="margin-bottom: 1.25rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Número de Teléfono</label>
                    <input type="text" name="telefono" class="shadcn-input" placeholder="3001234567" 
                           value="<?= htmlspecialchars($aprendiz['telefono'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Cambiar Contraseña (Opcional)</label>
                    <input type="password" name="password" class="shadcn-input" placeholder="Ingresa tu nueva contraseña si deseas cambiarla">
                    <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:0.25rem;">
                        Deja este campo en blanco si no deseas modificar tu clave actual.
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn-shadcn btn-shadcn-primary">
                        <i class="bi bi-check2-circle"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── CONTENEDOR 2 COLUMNAS EN PANTALLAS GRANDES: FALTAS Y ASISTENCIAS ── -->
<?php if (isset($aprendiz)): ?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 520px), 1fr)); gap: 1.5rem; margin-bottom: 2rem; align-items: start;">

    <!-- ── COLUMNA 1: MIS INASISTENCIAS DEL MES ─────────────── -->
    <div class="shadcn-card" style="display: flex; flex-direction: column;">
        <div class="card-header-shadcn" style="flex-wrap: wrap; gap: 0.75rem; justify-content: space-between;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <h3><i class="bi bi-calendar-x me-2" style="color:var(--destructive, #ef4444);"></i>Inasistencias del Mes</h3>
                <?php 
                    $cantFaltas = (int)($reporteFaltas['total_faltas'] ?? 0);
                    $badgeFaltaClass = ($cantFaltas > 0) ? 'badge-destructive' : 'badge-puntual';
                ?>
                <span class="shadcn-badge <?= $badgeFaltaClass ?>">
                    <?= $cantFaltas ?> <?= ($cantFaltas === 1) ? 'falta' : 'faltas' ?>
                </span>
            </div>

            <!-- Selector de Mes y Botón PDF -->
            <div style="display:flex; gap:0.375rem; align-items:center; flex-wrap:wrap;">
                <form method="GET" action="index.php" style="display:flex; gap:0.25rem; align-items:center;">
                    <input type="hidden" name="action" value="mi-perfil">
                    <input type="month" name="mes" class="shadcn-input" style="padding:0.2rem 0.4rem; font-size:0.75rem;" 
                           value="<?= htmlspecialchars($reporteFaltas['mes'] ?? date('Y-m')) ?>" onchange="this.form.submit()">
                </form>

                <a href="index.php?action=reporte-faltas-aprendiz&mes=<?= urlencode($reporteFaltas['mes'] ?? date('Y-m')) ?>" 
                   target="_blank" class="btn-shadcn btn-shadcn-outline" style="font-size:0.75rem; padding:0.25rem 0.5rem;" title="Generar PDF de Faltas">
                    <i class="bi bi-file-earmark-pdf me-1" style="color:#ef4444;"></i>
                    <span>PDF</span>
                </a>
            </div>
        </div>

        <div class="shadcn-table-wrapper" style="flex: 1;">
            <table class="shadcn-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Día</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporteFaltas['faltas'])): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:2.5rem; color:var(--muted-foreground);">
                            <i class="bi bi-check-circle-fill me-1" style="color:#16a34a; font-size:1.25rem; vertical-align:middle;"></i>
                            <span style="font-weight:600; color:#16a34a;">¡Al día!</span> No tienes inasistencias pendientes en este mes.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reporteFaltas['faltas'] as $f): ?>
                    <tr>
                        <td style="font-weight:700; color:var(--foreground);"><?= htmlspecialchars($f['fecha']) ?></td>
                        <td><?= htmlspecialchars($f['dia_semana']) ?></td>
                        <td>
                            <?php if ($f['tiene_excusa']): ?>
                                <span class="shadcn-badge badge-pendiente"><i class="bi bi-hourglass-split me-1"></i>En Revisión</span>
                            <?php else: ?>
                                <span class="shadcn-badge badge-destructive"><i class="bi bi-exclamation-circle me-1"></i>Sin Justificar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($f['tiene_excusa']): ?>
                                <a href="index.php?action=mis-excusas" class="btn-shadcn btn-shadcn-ghost" style="padding:0.2rem 0.4rem; font-size:0.75rem;" title="Ver Excusa">
                                    <i class="bi bi-eye"></i>
                                </a>
                            <?php else: ?>
                                <a href="index.php?action=mis-excusas&fecha=<?= urlencode($f['fecha']) ?>" class="btn-shadcn btn-shadcn-primary" style="padding:0.2rem 0.5rem; font-size:0.75rem;" title="Radicar Excusa">
                                    <i class="bi bi-file-earmark-medical me-1"></i>Radicar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── COLUMNA 2: MIS ASISTENCIAS RECIENTES ────────────────── -->
    <div class="shadcn-card" style="display: flex; flex-direction: column;">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-clock-history me-2"></i>Mis Asistencias Recientes</h3>
            <span class="shadcn-badge badge-secondary"><?= count($asistencias) ?> registros</span>
        </div>

        <div class="shadcn-table-wrapper" style="flex: 1;">
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
                    <?php if (empty($asistencias)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:2.5rem; color:var(--muted-foreground);">
                            No tienes asistencias registradas aún.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($asistencias as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['fecha']) ?></strong></td>
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
