<?php
$pageTitle = 'Terminal RFID — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">

    <div class="page-header" style="text-align: center; justify-content: center; flex-direction: column;">
        <h1 class="page-header-title">Terminal de Registro RFID</h1>
        <div class="page-header-subtitle">Acerca la tarjeta/llavero RFID al lector para marcar ingreso o salida</div>
    </div>

    <!-- 🟢 ALERTAS DE RESPUESTA AL ESCANEAR RFID -->
    <?php if (!empty($mensaje)): ?>
    <div style="margin-bottom: 1.25rem;" class="alert-auto-dismiss">
        <div class="shadcn-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-left: 4px solid var(--sena-brand);">
            <i class="bi bi-info-circle-fill fs-5" style="color:var(--sena-brand);"></i>
            <span style="font-size:0.9rem; font-weight:500;"><?= htmlspecialchars($mensaje['texto'] ?? '') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- 🟢 FORMULARIO DE LECTURA RFID AUTOMÁTICO -->
    <div class="shadcn-card" style="margin-bottom: 1.5rem;">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-wifi me-2" style="color:var(--sena-brand);"></i>Lectura Automática RFID</h3>
            <span class="shadcn-badge badge-puntual"><i class="bi bi-dot"></i>Lector listo</span>
        </div>
        <div class="card-body-shadcn">
            <form method="POST" action="index.php?action=registrar-ingreso" id="rfidForm">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Código UID RFID</label>
                    <input type="text" id="rfid_uid" name="rfid_uid" class="shadcn-input"
                           placeholder="Esperando lectura de tarjeta..." autocomplete="off" autofocus
                           style="font-family: monospace; font-size: 1.1rem; padding: 0.75rem; text-align: center;">
                </div>
                <button type="submit" class="btn-shadcn btn-shadcn-primary" style="width: 100%; padding:0.75rem;">
                    <i class="bi bi-check-circle"></i>
                    <span>Registrar Ingreso / Salida</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 🟢 FORMULARIO DE INGRESO MANUAL -->
    <div class="shadcn-card">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-keyboard me-2"></i>Ingreso Manual por ID</h3>
        </div>
        <div class="card-body-shadcn">
            <form method="POST" action="index.php?action=registrar-ingreso">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">ID de Aprendiz</label>
                    <input type="number" name="id_aprendiz" class="shadcn-input" placeholder="Ej: 15" min="1">
                </div>
                <button type="submit" class="btn-shadcn btn-shadcn-outline" style="width: 100%;">
                    <i class="bi bi-person-check"></i>
                    <span>Registrar Manualmente</span>
                </button>
            </form>
        </div>
    </div>

</div>

<?php if (!empty($mensaje)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const esError = <?= json_encode(($mensaje['tipo'] ?? '') === 'error') ?>;
    const textoMsg = <?= json_encode($mensaje['texto'] ?? '') ?>;
    const tipoMensaje = <?= json_encode($mensaje['tipo'] ?? 'success') ?>;

//mapeo cada tipo guardado en la sesion a su icono y titulo correcto de SweetAlert
    const configPorTipo = {
        success: { icon: 'success', title: '¡Registro Exitoso!' },
        warning: { icon: 'warning', title: 'Atencion' },
        error:   { icon: 'error',   title: 'Ingreso no registrado' }
    };
    const config = configPorTipo[tipoMensaje] || configPorTipo.success;

    Swal.fire({
        icon: config.icon,
        title: config.title,
        text: textoMsg,
        timer: 4000,
        timerProgressBar: true,
        showConfirmButton: false
    });
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
