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

    <!-- ──  FORMULARIO DE FICHA ──────────────────────────────────── -->
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

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Estado de la Ficha *</label>
                        <select name="estado" class="shadcn-select" required>
                            <option value="Activo" <?= (($ficha['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= (($ficha['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            <option value="Finalizado" <?= (($ficha['estado'] ?? '') === 'Finalizado') ? 'selected' : '' ?>>Finalizado</option>
                        </select>
                    </div>
                </div>

                <!-- ── SECCIÓN DE ASIGNATURAS E INSTRUCTORES (TÉCNICAS Y TRANSVERSALES) ── -->
                <div style="margin-top: 1.5rem; margin-bottom: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                        <div>
                            <h3 style="font-size: 0.9375rem; font-weight: 600; margin:0; display:flex; align-items:center; gap:0.5rem;">
                                <i class="bi bi-people" style="color:var(--sena-brand);"></i>Instructores y Asignaturas (Técnicas / Transversales)
                            </h3>
                            <p style="font-size:0.8125rem; color:var(--muted-foreground); margin:0.25rem 0 0 0;">Asigna asignaturas transversales (Inglés, Ética...) o técnicas y sus instructores a cargo.</p>
                        </div>
                        <button type="button" class="btn-shadcn btn-shadcn-outline" style="font-size:0.75rem; padding:0.25rem 0.625rem;" onclick="agregarFilaAsignatura()">
                            <i class="bi bi-plus-lg me-1"></i>+ Asignatura
                        </button>
                    </div>

                    <div id="contenedor_asignaturas" style="display:flex; flex-direction:column; gap:0.75rem;">
                        <?php 
                        $asignaturasExistentes = $ficha['asignaturas'] ?? [];
                        foreach ($asignaturasExistentes as $idx => $asig): 
                        ?>
                        <div class="fila-asignatura" style="display:grid; grid-template-columns: 2fr 1fr 2fr auto; gap:0.5rem; align-items:center; background:var(--muted); padding:0.625rem; border-radius:var(--radius-md);">
                            <div>
                                <input type="text" name="asignaturas[<?= $idx ?>][nombre_asignatura]" class="shadcn-input" placeholder="Ej: Inglés, Ética, BD..." value="<?= htmlspecialchars($asig['nombre_asignatura']) ?>" required>
                            </div>
                            <div>
                                <select name="asignaturas[<?= $idx ?>][tipo]" class="shadcn-select">
                                    <option value="Técnica" <?= ($asig['tipo'] === 'Técnica') ? 'selected' : '' ?>>Técnica</option>
                                    <option value="Transversal" <?= ($asig['tipo'] === 'Transversal') ? 'selected' : '' ?>>Transversal</option>
                                </select>
                            </div>
                            <div>
                                <select name="asignaturas[<?= $idx ?>][fk_usuario_instructor]" class="shadcn-select" required>
                                    <option value="">— Instructor —</option>
                                    <?php foreach ($instructores ?? [] as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($asig['fk_usuario_instructor'] == $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['nombre']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="button" class="btn-shadcn btn-shadcn-outline" style="padding:0.375rem; color:#dc2626;" onclick="eliminarFilaAsignatura(this)" title="Eliminar asignatura">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
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

<script>
let contadorAsignaturas = <?= count($ficha['asignaturas'] ?? []) ?>;
const instructoresOptionsHtml = `
    <option value="">— Instructor —</option>
    <?php foreach ($instructores ?? [] as $u): ?>
    <option value="<?= $u['id'] ?>"><?= htmlspecialchars(addslashes($u['nombre'])) ?></option>
    <?php endforeach; ?>
`;

function agregarFilaAsignatura(nombre = '', tipo = 'Transversal', instructorId = '') {
    const contenedor = document.getElementById('contenedor_asignaturas');
    const div = document.createElement('div');
    div.className = 'fila-asignatura';
    div.style.cssText = 'display:grid; grid-template-columns: 2fr 1fr 2fr auto; gap:0.5rem; align-items:center; background:var(--muted); padding:0.625rem; border-radius:var(--radius-md);';
    
    div.innerHTML = `
        <div>
            <input type="text" name="asignaturas[${contadorAsignaturas}][nombre_asignatura]" class="shadcn-input" placeholder="Ej: Inglés, Ética, BD..." value="${nombre}" required>
        </div>
        <div>
            <select name="asignaturas[${contadorAsignaturas}][tipo]" class="shadcn-select">
                <option value="Técnica" ${tipo === 'Técnica' ? 'selected' : ''}>Técnica</option>
                <option value="Transversal" ${tipo === 'Transversal' ? 'selected' : ''}>Transversal</option>
            </select>
        </div>
        <div>
            <select name="asignaturas[${contadorAsignaturas}][fk_usuario_instructor]" class="shadcn-select" required>
                ${instructoresOptionsHtml}
            </select>
        </div>
        <div>
            <button type="button" class="btn-shadcn btn-shadcn-outline" style="padding:0.375rem; color:#dc2626;" onclick="eliminarFilaAsignatura(this)" title="Eliminar asignatura">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    if (instructorId) {
        div.querySelector('select[name*="[fk_usuario_instructor]"]').value = instructorId;
    }
    contenedor.appendChild(div);
    contadorAsignaturas++;
}

function eliminarFilaAsignatura(btn) {
    const fila = btn.closest('.fila-asignatura');
    if (fila) fila.remove();
}
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
