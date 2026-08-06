<?php
$pageTitle = 'Registrar Ingreso — Control de Ingresos';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container px-4 py-4" style="max-width:680px;">

    <h1 class="page-title mb-1">
        <i class="bi bi-person-check me-2 text-sena"></i>Registro de Ingreso
    </h1>
    <p class="text-muted mb-4">Escanea la tarjeta RFID o ingresa el ID del aprendiz manualmente.</p>

    <!-- ── Alerta de resultado ─────────────────────────────── -->
    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?= htmlspecialchars($mensaje['tipo']) ?> d-flex align-items-center gap-2 mb-4 fs-6">
        <i class="bi bi-info-circle-fill flex-shrink-0"></i>
        <span><?= $mensaje['texto'] ?></span>
    </div>
    <?php endif; ?>

    <!-- ── Formulario RFID ─────────────────────────────────── -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header fw-semibold">
            <i class="bi bi-wifi me-2"></i>Lectura RFID
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?action=registrar-ingreso" id="rfidForm">
                <div class="mb-3">
                    <label for="rfid_uid" class="form-label">UID de la tarjeta</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-credit-card-2-front"></i></span>
                        <input type="text" id="rfid_uid" name="rfid_uid"
                               class="form-control form-control-lg font-monospace"
                               placeholder="Esperando lectura..."
                               autocomplete="off" autofocus>
                    </div>
                    <div class="form-text">El sistema registra automáticamente al detectar el UID.</div>
                </div>
                <button type="submit" class="btn btn-sena btn-lg w-100">
                    <i class="bi bi-check-circle me-2"></i>Registrar
                </button>
            </form>
        </div>
    </div>

    <!-- ── Formulario manual ───────────────────────────────── -->
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">
            <i class="bi bi-keyboard me-2"></i>Registro manual
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?action=registrar-ingreso">
                <div class="mb-3">
                    <label for="aprendiz_id" class="form-label">ID del aprendiz</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="number" id="aprendiz_id" name="aprendiz_id"
                               class="form-control" placeholder="Ej: 42" min="1">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-sena w-100">
                    <i class="bi bi-check me-1"></i>Registrar manualmente
                </button>
            </form>
        </div>
    </div>

    <div class="mt-3 text-center">
        <a href="index.php?action=historial" class="btn btn-link text-muted">
            <i class="bi bi-clock-history me-1"></i>Ver historial del día
        </a>
    </div>
</div>

<script>
// Auto-submit al detectar RFID (cuando el lector llena el campo)
document.getElementById('rfid_uid').addEventListener('change', function() {
    if (this.value.trim().length > 0) {
        document.getElementById('rfidForm').submit();
    }
});
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
