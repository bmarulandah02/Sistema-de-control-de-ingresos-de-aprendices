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
    </div>

    <!-- ── 🟢 FORMULARIO DE FICHA ──────────────────────────────────── -->
    <div class="shadcn-card">
        <div class="card-body-shadcn">
            <!-- 🟢 EDITAR AQUÍ: Modifica action= según la ruta de tu controlador -->
            <form method="POST" action="index.php?action=<?= $esEdicion ? 'ficha-actualizar' : 'ficha-guardar' ?>">
                <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= (int) $ficha['id'] ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Número de Ficha *</label>
                        <input type="text" name="numero_ficha" class="shadcn-input"
                               value="<?= htmlspecialchars($ficha['numero_ficha'] ?? '') ?>" placeholder="Ej: 2978456" required>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Programa de Formación *</label>
                        <input type="text" name="programa" class="shadcn-input"
                               value="<?= htmlspecialchars($ficha['programa'] ?? '') ?>" placeholder="Ej: ADSO" required>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Instructor Encargado *</label>
                        <!-- 🟢 EDITAR AQUÍ: Recorre la lista de instructores -->
                        <select name="instructor_id" class="shadcn-select" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($instructores ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (($ficha['instructor_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Estado</label>
                        <select name="estado" class="shadcn-select">
                            <option value="Activo" <?= (($ficha['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= (($ficha['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
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
