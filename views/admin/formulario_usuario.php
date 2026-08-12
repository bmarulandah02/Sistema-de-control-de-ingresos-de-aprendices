<?php
$esEdicion = isset($usuarioEditar) && $usuarioEditar !== null;
$pageTitle = ($esEdicion ? 'Editar Usuario' : 'Registrar Usuario') . ' — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-header-title"><?= $esEdicion ? 'Editar Perfil de Usuario' : 'Registrar Nuevo Usuario' ?></h1>
        <div class="page-header-subtitle">
            <?= $esEdicion ? 'Modifica la información básica o cambia la contraseña del usuario' : 'Ingresa la información para registrar un Instructor o Aprendiz' ?>
        </div>
    </div>
    <div>
        <a href="index.php?action=usuarios" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-arrow-left"></i>
            <span>Volver a la lista</span>
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
<div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2); display:flex; align-items:center; gap:0.5rem;">
    <i class="bi bi-exclamation-circle-fill"></i>
    <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="shadcn-card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header-shadcn">
        <h3><i class="bi bi-person-badge me-2"></i><?= $esEdicion ? 'Editar Información' : 'Formulario de Registro' ?></h3>
    </div>

    <div class="card-body-shadcn" style="padding: 1.5rem;">
        <form method="POST" action="index.php?action=usuario-crear">
            <?php if ($esEdicion): ?>
            <input type="hidden" name="id_usuario" value="<?= (int) $usuarioEditar['id_usuario'] ?>">
            <?php endif; ?>
            
            <div style="margin-bottom: 1.25rem;">
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Rol del Usuario *</label>
                <select name="fk_rol" id="selectRol" class="shadcn-select" required onchange="toggleCamposAprendiz()">
                    <?php 
                        $soloUnRol = count($roles) === 1;
                        $rolSel = $_POST['fk_rol'] ?? ($usuarioEditar['fk_rol'] ?? '');
                        if (empty($rolSel) && $soloUnRol) {
                            $primerRol = reset($roles);
                            $rolSel = $primerRol['id_rol'];
                        }
                    ?>
                    <?php if (!$soloUnRol): ?>
                    <option value="">Selecciona un rol</option>
                    <?php endif; ?>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id_rol'] ?>" <?= ($rolSel == $r['id_rol']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['nombre_rol']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Nombres *</label>
                    <input type="text" name="nombre" class="shadcn-input" placeholder="ej. Camilo Andrés" required 
                           value="<?= htmlspecialchars($_POST['nombre'] ?? ($usuarioEditar['nombre'] ?? '')) ?>">
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Apellidos *</label>
                    <input type="text" name="apellido" class="shadcn-input" placeholder="ej. Pérez Gómez" required 
                           value="<?= htmlspecialchars($_POST['apellido'] ?? ($usuarioEditar['apellido'] ?? '')) ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Número de Identificación (CC/TI) *</label>
                    <input type="text" name="identificacion" class="shadcn-input" placeholder="1012345678" required 
                           value="<?= htmlspecialchars($_POST['identificacion'] ?? ($usuarioEditar['identificacion'] ?? '')) ?>">
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Teléfono de contacto</label>
                    <input type="text" name="telefono" class="shadcn-input" placeholder="3001234567" 
                           value="<?= htmlspecialchars($_POST['telefono'] ?? ($usuarioEditar['telefono'] ?? '')) ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Correo / Nombre de Usuario *</label>
                    <input type="text" name="correo" class="shadcn-input" placeholder="usuario@sena.edu.co" required 
                           value="<?= htmlspecialchars($_POST['correo'] ?? ($usuarioEditar['correo'] ?? '')) ?>">
                </div>

                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">
                        Contraseña <?= $esEdicion ? '(Opcional)' : '*' ?>
                    </label>
                    <input type="password" name="password" class="shadcn-input" 
                           placeholder="<?= $esEdicion ? 'Dejar en blanco para mantener la actual' : '••••••••' ?>" <?= $esEdicion ? '' : 'required' ?>>
                </div>
            </div>

            <!-- ── SECCIÓN EXCLUSIVA PARA APRENDIZ ───────────────────── -->
            <div id="seccionAprendiz" style="display: none; border-top: 1px solid var(--border); padding-top: 1.25rem; margin-top: 1.25rem;">
                <h4 style="font-size:0.95rem; font-weight:600; margin-bottom: 1rem; color: var(--primary);">
                    <i class="bi bi-qr-code-scan me-1"></i>Información del Aprendiz
                </h4>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Ficha de Formación *</label>
                        <select name="fk_ficha" id="selectFicha" class="shadcn-select">
                            <option value="">Selecciona una ficha</option>
                            <?php 
                                $fichaSel = $_POST['fk_ficha'] ?? ($usuarioEditar['fk_ficha'] ?? '');
                            ?>
                            <?php foreach ($fichas as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= ($fichaSel == $f['id']) ? 'selected' : '' ?>>
                                Ficha <?= htmlspecialchars($f['numero_ficha']) ?> — <?= htmlspecialchars($f['programa']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Código RFID (Tag o Tarjeta)</label>
                        <input type="number" name="codigo_rfid" class="shadcn-input" placeholder="Ej. 1029384" 
                               value="<?= htmlspecialchars($_POST['codigo_rfid'] ?? ($usuarioEditar['codigo_rfid'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.75rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="index.php?action=usuarios" class="btn-shadcn btn-shadcn-outline">Cancelar</a>
                <button type="submit" class="btn-shadcn btn-shadcn-primary">
                    <i class="bi bi-check2-circle"></i>
                    <span><?= $esEdicion ? 'Actualizar Usuario' : 'Guardar Usuario' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCamposAprendiz() {
    const selectRol = document.getElementById('selectRol');
    const seccionAprendiz = document.getElementById('seccionAprendiz');
    const selectFicha = document.getElementById('selectFicha');

    const textoRol = selectRol.options[selectRol.selectedIndex] ? selectRol.options[selectRol.selectedIndex].text : '';

    if (textoRol.includes('Aprendiz') || selectRol.value == '3') {
        seccionAprendiz.style.display = 'block';
        selectFicha.setAttribute('required', 'required');
    } else {
        seccionAprendiz.style.display = 'none';
        selectFicha.removeAttribute('required');
    }
}
document.addEventListener('DOMContentLoaded', toggleCamposAprendiz);
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
