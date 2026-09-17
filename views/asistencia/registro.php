<?php
$pageTitle = 'Terminal RFID — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">

    <div class="page-header" style="text-align: center; justify-content: center; flex-direction: column;">
        <h1 class="page-header-title">Terminal de Registro RFID</h1>
        <div class="page-header-subtitle">Acerca la tarjeta/llavero RFID al lector para marcar ingreso o salida</div>
    </div>

    <!--  ALERTAS DE RESPUESTA AL ESCANEAR RFID -->
    <?php if (!empty($mensaje)): ?>
    <div style="margin-bottom: 1.25rem;" class="alert-auto-dismiss">
        <div class="shadcn-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-left: 4px solid var(--sena-brand);">
            <i class="bi bi-info-circle-fill fs-5" style="color:var(--sena-brand);"></i>
            <span style="font-size:0.9rem; font-weight:500;"><?= htmlspecialchars($mensaje['texto'] ?? '') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    $horaApertura = $_SESSION['hora_apertura_asistencia'] ?? null;
    $segundosTranscurridos = $horaApertura ? (time() - (int)$horaApertura) : null;
    $segundosRestantes = ($horaApertura && $segundosTranscurridos < 300) ? (300 - $segundosTranscurridos) : 0;
    $aperturaActiva = ($horaApertura && $segundosRestantes > 0);
    ?>

    <!--  PANEL DE CONTROL DE APERTURA Y TEMPORIZADOR (5 MINUTOS) -->
    <div class="shadcn-card" style="margin-bottom: 1.5rem; padding: 1.25rem; border-left: 4px solid var(--sena-brand);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem;">
            <div>
                <div style="font-weight:700; font-size:1rem; display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                    <i class="bi bi-play-circle-fill text-primary fs-5"></i>
                    <span>Apertura de Asistencia por el Instructor</span>
                </div>
                <div style="font-size:0.8125rem; color:var(--muted-foreground);">
                    <?php if ($aperturaActiva): ?>
                        <span style="color:#16a34a; font-weight:600;"><i class="bi bi-check-circle me-1"></i>Ventana de 5 minutos Activa:</span> Los aprendices que escaneen ahora ingresarán como <strong>PUNTUALES</strong>.
                    <?php elseif ($horaApertura): ?>
                        <span style="color:#dc2626; font-weight:600;"><i class="bi bi-exclamation-triangle me-1"></i>Ventana Finalizada:</span> Los siguientes escaneos se marcarán como <strong>RETARDO</strong>.
                    <?php else: ?>
                        Haz clic en el botón para abrir el registro y otorgar 5 minutos de tolerancia puntual.
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:1rem;">
                <?php if ($aperturaActiva): ?>
                    <div style="text-align:center; background:var(--background); padding:0.5rem 1rem; border-radius:var(--radius); border:1px solid var(--border);">
                        <div style="font-size:0.6875rem; text-transform:uppercase; color:var(--muted-foreground); font-weight:600;">Tiempo Restante</div>
                        <div id="timer_display" style="font-size:1.75rem; font-weight:800; font-family:monospace; color:var(--sena-brand); line-height:1;">
                            <?= sprintf('%02d:%02d', floor($segundosRestantes / 60), $segundosRestantes % 60) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="index.php?action=abrir-sesion-asistencia" class="btn-shadcn btn-shadcn-primary" style="padding: 0.625rem 1.25rem;">
                    <i class="bi bi-play-fill me-1" style="font-size:1.1rem;"></i>
                    <span><?= $aperturaActiva ? 'Reiniciar 5 Minutos' : 'Abrir Registro (5 min Tolerancia)' ?></span>
                </a>
            </div>
        </div>
    </div>

    <!--  CONFIGURACIÓN DE SESIÓN (MATERIA Y BLOQUE HORARIO) -->
    <div class="shadcn-card" style="margin-bottom: 1.5rem;">
        <div class="card-header-shadcn" style="background: rgba(59, 130, 246, 0.05);">
            <h3><i class="bi bi-clock-history me-2" style="color:var(--sena-brand);"></i>Configuración de Clase / Franja Horaria</h3>
            <span class="shadcn-badge badge-outline"><i class="bi bi-journal-code me-1"></i>Sesión Activa</span>
        </div>
        <div class="card-body-shadcn" style="padding: 1.25rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <!-- Seleccionar Asignatura / Ficha del Instructor -->
                <div>
                    <label style="display:block; font-size:0.8125rem; font-weight:500; margin-bottom:0.375rem; color:var(--muted-foreground);">
                        <i class="bi bi-book me-1"></i>Materia / Asignatura
                    </label>
                    <select id="materia_select" class="shadcn-select" onchange="alCambiarMateria()">
                        <option value="">— Seleccionar Ficha / Materia —</option>
                        <?php 
                        $materiaGuardada = $_SESSION['materia_actual'] ?? '';
                        $materiaEsPersonalizada = true;
                        foreach ($fichas ?? [] as $f): 
                            $nombreOpcion = $f['programa'] . ' (Ficha ' . $f['numero_ficha'] . ')';
                            $valorOpcion  = $f['programa'] . ' - Ficha ' . $f['numero_ficha'];
                            $selected = ($materiaGuardada === $valorOpcion) ? 'selected' : '';
                            if ($selected) $materiaEsPersonalizada = false;
                        ?>
                            <option value="<?= htmlspecialchars($valorOpcion) ?>" 
                                    data-jornada="<?= htmlspecialchars($f['jornada'] ?? 'Mañana') ?>"
                                    <?= $selected ?>>
                                <?= htmlspecialchars($nombreOpcion) ?> [<?= htmlspecialchars($f['jornada'] ?? 'Mañana') ?>]
                            </option>
                        <?php endforeach; ?>
                        <option value="__custom__" <?= ($materiaGuardada && $materiaEsPersonalizada) ? 'selected' : '' ?>>
                             Otra Asignatura (Escribir libremente...)
                        </option>
                    </select>

                    <input type="text" id="materia_custom_input" class="shadcn-input" 
                           placeholder="Escribe el nombre de la materia..." 
                           value="<?= $materiaEsPersonalizada ? htmlspecialchars($materiaGuardada) : '' ?>"
                           style="display: <?= ($materiaGuardada && $materiaEsPersonalizada) ? 'block' : 'none' ?>; margin-top:0.5rem;" 
                           onchange="actualizarCamposSesion()" oninput="actualizarCamposSesion()">
                </div>

                <!-- Selector de Franja Horaria / Bloque -->
                <div>
                    <label style="display:block; font-size:0.8125rem; font-weight:500; margin-bottom:0.375rem; color:var(--muted-foreground);">
                        <i class="bi bi-clock me-1"></i>Franja Horaria / Bloque
                    </label>
                    <select id="bloque_select" class="shadcn-select" onchange="actualizarCamposSesion()">
                        <option value="">— Horario Estándar de la Ficha —</option>
                        <?php 
                        $bloqueGuardado = $_SESSION['bloque_actual'] ?? '';
                        foreach ($bloques ?? [] as $grupoNombre => $listaBloques): 
                        ?>
                            <optgroup label="<?= htmlspecialchars($grupoNombre) ?>" data-grupo="<?= htmlspecialchars($grupoNombre) ?>">
                                <?php foreach ($listaBloques as $b): 
                                    $val = $b['entrada'] . '|' . $b['salida'] . '|' . $b['nombre'];
                                    $sel = ($bloqueGuardado === $val) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>>
                                        <?= htmlspecialchars($b['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.75rem; display: flex; align-items: center; gap: 0.375rem;">
                <i class="bi bi-info-circle"></i>
                <span>Al seleccionar la asignatura, la franja de horario se adaptará automáticamente a su jornada correspondiente.</span>
            </div>
        </div>
    </div>

    <!--  FORMULARIO DE LECTURA RFID AUTOMÁTICO -->
    <div class="shadcn-card" style="margin-bottom: 1.5rem;">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-wifi me-2" style="color:var(--sena-brand);"></i>Lectura Automática RFID</h3>
            <span class="shadcn-badge badge-puntual"><i class="bi bi-dot"></i>Lector listo</span>
        </div>
        <div class="card-body-shadcn">
            <form method="POST" action="index.php?action=registrar-ingreso" id="rfidForm">
                <input type="hidden" name="materia" id="rfid_materia" value="<?= htmlspecialchars($_SESSION['materia_actual'] ?? '') ?>">
                <input type="hidden" name="bloque_horario" id="rfid_bloque" value="<?= htmlspecialchars($_SESSION['bloque_actual'] ?? '') ?>">

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

    <!--  FORMULARIO DE INGRESO MANUAL -->
    <div class="shadcn-card">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-keyboard me-2"></i>Ingreso Manual por ID</h3>
        </div>
        <div class="card-body-shadcn">
            <form method="POST" action="index.php?action=registrar-ingreso" id="manualForm">
                <input type="hidden" name="materia" id="manual_materia" value="<?= htmlspecialchars($_SESSION['materia_actual'] ?? '') ?>">
                <input type="hidden" name="bloque_horario" id="manual_bloque" value="<?= htmlspecialchars($_SESSION['bloque_actual'] ?? '') ?>">

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

<script>
function alCambiarMateria() {
    const sel = document.getElementById('materia_select');
    const customInput = document.getElementById('materia_custom_input');
    const bloqueSelect = document.getElementById('bloque_select');

    if (sel.value === '__custom__') {
        customInput.style.display = 'block';
        customInput.focus();
    } else {
        customInput.style.display = 'none';

        // Detectar jornada de la asignatura seleccionada
        const selectedOpt = sel.options[sel.selectedIndex];
        const jornada = selectedOpt ? selectedOpt.getAttribute('data-jornada') : '';

        if (jornada) {
            const jornadaNorm = jornada.toLowerCase();
            // Buscar la opción del bloque que corresponda a esa jornada y seleccionarla
            for (let i = 0; i < bloqueSelect.options.length; i++) {
                const opt = bloqueSelect.options[i];
                const text = opt.text.toLowerCase();
                const parentGroup = opt.parentElement ? (opt.parentElement.getAttribute('data-grupo') || '').toLowerCase() : '';

                if (parentGroup.includes(jornadaNorm) || text.includes(jornadaNorm)) {
                    bloqueSelect.selectedIndex = i;
                    break;
                }
            }
        }
    }

    actualizarCamposSesion();
}

function actualizarCamposSesion() {
    const sel = document.getElementById('materia_select');
    const customInput = document.getElementById('materia_custom_input');
    let mat = sel.value;

    if (mat === '__custom__') {
        mat = customInput.value;
    }

    const bloq = document.getElementById('bloque_select').value;

    document.getElementById('rfid_materia').value = mat;
    document.getElementById('rfid_bloque').value = bloq;

    document.getElementById('manual_materia').value = mat;
    document.getElementById('manual_bloque').value = bloq;
}

// Temporizador en tiempo real de 5 minutos
let segundosRestantes = <?= (int) $segundosRestantes ?>;
if (segundosRestantes > 0) {
    const timerElem = document.getElementById('timer_display');
    const interval = setInterval(() => {
        segundosRestantes--;
        if (segundosRestantes <= 0) {
            clearInterval(interval);
            if (timerElem) timerElem.textContent = '00:00';
            location.reload();
        } else {
            const mins = Math.floor(segundosRestantes / 60);
            const secs = segundosRestantes % 60;
            if (timerElem) {
                timerElem.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }
        }
    }, 1000);
}
</script>

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
