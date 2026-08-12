<?php
$pageTitle = 'Ficha — ' . ($ficha ? 'Editar' : 'Nueva');
require __DIR__ . '/../../views/layouts/header.php';
$esEdicion = isset($ficha) && $ficha !== null;
?>

<div style="max-width: 680px; margin: 0 auto;">

    <div class="page-header">
        <div>
            <h1 class="page-header-title"><?= $esEdicion ? 'Editar Ficha de Formación' : 'Crear Nueva Ficha' ?></h1>
            <div class="page-header-subtitle">Ingresa la información básica y asigna al instructor responsable</div>
        </div>
        <div>
            <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-outline">
                <i class="bi bi-arrow-left"></i>
                <span>Volver a Fichas</span>
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.2); display:flex; align-items:center; gap:0.5rem;">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- ── 🟢 FORMULARIO DE FICHA ──────────────────────────────────── -->
    <div class="shadcn-card">
        <div class="card-body-shadcn" style="padding: 1.5rem;">
            <form method="POST" action="index.php?action=ficha-guardar">
                <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= (int) $ficha['id'] ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Número de Ficha *</label>
                        <input type="text" name="numero_ficha" class="shadcn-input"
                               value="<?= htmlspecialchars($ficha['numero_ficha'] ?? '') ?>" placeholder="Ej: 2978456" required <?= $esEdicion ? 'readonly' : '' ?>>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Programa de Formación *</label>
                        <input type="text" name="programa" class="shadcn-input"
                               value="<?= htmlspecialchars($ficha['programa'] ?? '') ?>" placeholder="Ej: ADSO" required>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Instructor Encargado *</label>
                        <select name="instructor_id" class="shadcn-select" required>
                            <option value="">— Seleccionar Instructor —</option>
                            <?php foreach ($instructores ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (($ficha['instructor_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Jornada *</label>
                        <select name="jornada" class="shadcn-select" required>
                            <option value="Mañana" <?= (($ficha['jornada'] ?? 'Mañana') === 'Mañana') ? 'selected' : '' ?>>Mañana (Diurna)</option>
                            <option value="Tarde" <?= (($ficha['jornada'] ?? '') === 'Tarde') ? 'selected' : '' ?>>Tarde (Vespertina)</option>
                            <option value="Noche" <?= (($ficha['jornada'] ?? '') === 'Noche') ? 'selected' : '' ?>>Noche (Nocturna)</option>
                            <option value="Mixta" <?= (($ficha['jornada'] ?? '') === 'Mixta') ? 'selected' : '' ?>>Mixta</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="shadcn-input" value="<?= htmlspecialchars($ficha['fecha_inicio'] ?? '') ?>">
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="shadcn-input" value="<?= htmlspecialchars($ficha['fecha_fin'] ?? '') ?>">
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border); padding-top:1.25rem;">
                    <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-outline">Cancelar</a>
                    <button type="submit" class="btn-shadcn btn-shadcn-primary">
                        <i class="bi bi-check-lg"></i>
                        <span><?= $esEdicion ? 'Guardar Cambios' : 'Crear Ficha' ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
