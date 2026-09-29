<?php
// ──────────────────────────────────────────────
//  views/fichas/horario.php — Armar el horario del mes de una ficha
//  Variables que llegan desde FichaController::horario():
//  $ficha, $mes, $dias, $instructoresFicha, $horarioMes, $bloques
// ──────────────────────────────────────────────
$pageTitle = 'Horario del mes — Ficha ' . $ficha['numero_ficha'];
require __DIR__ . '/../../views/layouts/header.php';

$nombresMes = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
               '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
$nombresDia = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$tituloMes  = $nombresMes[substr($mes, 5, 2)] . ' ' . substr($mes, 0, 4);
$idFicha    = (int) $ficha['id'];
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Horario de <?= $tituloMes ?></h1>
        <div class="page-header-subtitle">
            Ficha <?= $idFicha ?> · <?= htmlspecialchars($ficha['programa'] ?? '') ?> · Jornada <?= htmlspecialchars($ficha['jornada'] ?? '') ?>
        </div>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="index.php?action=ficha-horario-imprimir&id=<?= $idFicha ?>&mes=<?= urlencode($mes) ?>" target="_blank" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-printer"></i>
            <span>Imprimir</span>
        </a>
        <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-arrow-left"></i>
            <span>Volver a Fichas</span>
        </a>
    </div>
</div>

<?php if (isset($_GET['ok'])): ?>
<div style="background-color:rgba(5,150,105,0.1); color:#059669; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(5,150,105,0.2);">
    <i class="bi bi-check-circle-fill"></i> El horario se guardó correctamente.
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.2);">
    <i class="bi bi-exclamation-circle-fill"></i> No se pudo guardar el horario. Inténtalo de nuevo.
</div>
<?php endif; ?>

<?php if (empty($instructoresFicha)): ?>
<div style="background-color:rgba(245,158,11,0.1); color:#b45309; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(245,158,11,0.3);">
    <i class="bi bi-exclamation-triangle-fill"></i> Esta ficha no tiene instructores vinculados. Edita la ficha y vincula al menos uno.
</div>
<?php endif; ?>

<!-- ── Selector de mes ── -->
<div class="shadcn-card" style="margin-bottom:1rem;">
    <div class="card-body-shadcn" style="padding:1rem;">
        <form method="GET" action="index.php" style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="action" value="ficha-horario">
            <input type="hidden" name="id" value="<?= $idFicha ?>">
            <div>
                <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Mes</label>
                <input type="month" name="mes" class="shadcn-input" value="<?= htmlspecialchars($mes) ?>" required>
            </div>
            <button type="submit" class="btn-shadcn btn-shadcn-outline">
                <i class="bi bi-calendar3"></i>
                <span>Ver mes</span>
            </button>
        </form>
        <small style="color:var(--muted-foreground);">Si cambias de mes sin guardar, se pierden los cambios que no hayas guardado.</small>
    </div>
</div>

<!-- ── Horario del mes ── -->
<form method="POST" action="index.php?action=ficha-horario-guardar">
    <input type="hidden" name="id" value="<?= $idFicha ?>">
    <input type="hidden" name="mes" value="<?= htmlspecialchars($mes) ?>">

    <div class="shadcn-card">
        <div class="shadcn-table-wrapper" style="max-height:65vh; overflow:auto;">
            <table class="shadcn-table">
                <thead>
                    <tr>
                        <th style="position:sticky; top:0; background:var(--muted);">Día</th>
                        <?php foreach (['bloque1', 'bloque2'] as $b): ?>
                        <th style="position:sticky; top:0; background:var(--muted);">
                            <?= htmlspecialchars($bloques[$b]['nombre']) ?>
                            <select class="shadcn-select" style="margin-top:0.375rem; font-size:0.75rem;"
                                    onchange="aplicarColumna('<?= $b ?>', this)">
                                <option value="">Aplicar a todos…</option>
                                <?php foreach ($instructoresFicha as $i): ?>
                                <option value="<?= (int) $i['id'] ?>"><?= htmlspecialchars($i['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dias as $fecha): ?>
                    <?php
                        $marca    = strtotime($fecha);
                        $etiqueta = $nombresDia[(int) date('w', $marca)] . ' ' . date('d/m', $marca);
                    ?>
                    <tr>
                        <td style="font-weight:500; white-space:nowrap;"><?= $etiqueta ?></td>
                        <?php foreach (['bloque1', 'bloque2'] as $b): ?>
                        <?php $asignado = (int) ($horarioMes[$fecha][$b]['id'] ?? 0); ?>
                        <td>
                            <select name="asignaciones[<?= $fecha ?>][<?= $b ?>]" class="shadcn-select" data-bloque="<?= $b ?>">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($instructoresFicha as $i): ?>
                                <option value="<?= (int) $i['id'] ?>" <?= ((int) $i['id'] === $asignado) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($i['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1rem;">
        <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-outline">Cancelar</a>
        <button type="submit" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-check-lg"></i>
            <span>Guardar horario</span>
        </button>
    </div>
</form>

<script>
// Pone el mismo instructor en todos los días de una columna (Bloque 1 o Bloque 2)
function aplicarColumna(bloque, selectorMaestro) {
    const valor = selectorMaestro.value;
    if (valor === '') return;
    document.querySelectorAll('select[data-bloque="' + bloque + '"]').forEach(function (s) {
        s.value = valor;
    });
    selectorMaestro.value = '';
}
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>