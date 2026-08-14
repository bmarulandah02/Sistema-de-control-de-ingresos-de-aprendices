<?php
$pageTitle = 'Historial de Asistencia — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Historial de Asistencias</h1>
        <div class="page-header-subtitle">Consulta y exporta el registro de ingresos de aprendices</div>
    </div>
    <div>
        <!-- 🟢 EDITAR AQUÍ: Enlace o acción para exportar el CSV/Excel -->
        <a href="index.php?action=reporte-excel&fecha_inicio=<?= htmlspecialchars($filtros['fecha_inicio']) ?>&fecha_fin=<?= htmlspecialchars($filtros['fecha_fin']) ?>"
           class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-filetype-csv"></i>
            <span>Exportar CSV</span>
        </a>
    </div>
</div>

<!-- ── 🟢 FILTROS DE BÚSQUEDA ─────────────────────────────────── -->
<div class="shadcn-card" style="margin-bottom: 1.5rem;">
    <div class="card-body-shadcn">
        <form method="GET" action="index.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
            <input type="hidden" name="action" value="historial">

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Ficha de Formación</label>
                <select name="ficha_id" class="shadcn-select">
                    <option value="">— <?= (($_SESSION['rol'] ?? '') === 'Instructor') ? 'Todas mis fichas' : 'Todas las fichas' ?> —</option>
                    <?php foreach ($fichas as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= (($filtros['ficha_id'] ?? '') == $f['id']) ? 'selected' : '' ?>>
                        Ficha <?= htmlspecialchars($f['numero_ficha']) ?> — <?= htmlspecialchars($f['programa']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="shadcn-input" value="<?= htmlspecialchars($filtros['fecha_inicio']) ?>">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="shadcn-input" value="<?= htmlspecialchars($filtros['fecha_fin']) ?>">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Estado</label>
                <select name="estado" class="shadcn-select">
                    <option value="">Todos los estados</option>
                    <option value="Puntual" <?= ($filtros['estado'] ?? '') === 'Puntual' ? 'selected' : '' ?>>Puntual</option>
                    <option value="Retardo" <?= ($filtros['estado'] ?? '') === 'Retardo' ? 'selected' : '' ?>>Retardo</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn-shadcn btn-shadcn-primary" style="width:100%;">
                    <i class="bi bi-funnel"></i>
                    <span>Filtrar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── 🟢 TABLA DE REGISTROS DE ASISTENCIA ────────────────────── -->
<div class="shadcn-card">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-table me-2"></i>Registros de Ingreso</h3>
        <span class="shadcn-badge badge-secondary"><?= count($registros) ?> registros</span>
    </div>

    <div class="shadcn-table-wrapper">
        <table class="shadcn-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Aprendiz</th>
                    <th>Documento</th>
                    <th>Ficha</th>
                    <th>Programa</th>
                    <th>Hora entrada</th>
                    <th>Hora salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <!-- 🟢 EDITAR AQUÍ: Itera sobre tus registros traídos de la base de datos -->
                <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding: 2.5rem; color:var(--muted-foreground);">
                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                        No existen registros de asistencia para los filtros seleccionados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($r['aprendiz']) ?></td>
                    <td><?= htmlspecialchars($r['documento']) ?></td>
                    <td><span class="shadcn-badge badge-secondary"><?= htmlspecialchars($r['numero_ficha']) ?></span></td>
                    <td><?= htmlspecialchars($r['programa']) ?></td>
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

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
