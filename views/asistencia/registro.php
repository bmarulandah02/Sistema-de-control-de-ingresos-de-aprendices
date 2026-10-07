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
                    <?php if (empty($claseMomento)): ?>
                        <span style="color:#d97706; font-weight:600;"><i class="bi bi-exclamation-triangle me-1"></i>Sin clase programada hoy:</span> La apertura se habilitará el día y franja que tengas formación.
                    <?php elseif ($aperturaActiva): ?>
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

                <?php if (!empty($claseMomento)): ?>
                    <a href="index.php?action=abrir-sesion-asistencia" class="btn-shadcn btn-shadcn-primary" style="padding: 0.625rem 1.25rem;">
                        <i class="bi bi-play-fill me-1" style="font-size:1.1rem;"></i>
                        <span><?= $aperturaActiva ? 'Reiniciar 5 Minutos' : 'Abrir Registro (5 min Tolerancia)' ?></span>
                    </a>
                <?php else: ?>
                    <button type="button" class="btn-shadcn btn-shadcn-outline" style="padding: 0.625rem 1.25rem; opacity: 0.55; cursor: not-allowed;" disabled title="Sin clase programada hoy">
                        <i class="bi bi-slash-circle me-1"></i>
                        <span>Sin Clase Hoy</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!--  INFORMACIÓN DE LA CLASE EN CURSO (AUTOMÁTICA POR HORARIO) -->
    <div class="shadcn-card" style="margin-bottom: 1.5rem;">
        <?php if (!empty($claseMomento)): 
            $horaIni = !empty($claseMomento['hora_inicio']) ? substr($claseMomento['hora_inicio'], 0, 5) : '06:00';
            $horaFin = !empty($claseMomento['hora_fin']) ? substr($claseMomento['hora_fin'], 0, 5) : '12:00';
            $nombreFichaProg = !empty($claseMomento['nombre_programa']) ? $claseMomento['nombre_programa'] : 'Programa';
            $numFicha = $claseMomento['fk_ficha'] ?? '';
            $bloqueTxt = !empty($claseMomento['bloque']) ? ucfirst(str_replace('_', ' ', $claseMomento['bloque'])) : 'Franja Asignada';
            $instNombre = !empty($claseMomento['instructor_nombre']) ? $claseMomento['instructor_nombre'] : ($_SESSION['usuario_nombre'] ?? 'Instructor Asignado');
        ?>
            <div class="card-header-shadcn" style="background: rgba(59, 130, 246, 0.05); display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-clock-history" style="color:var(--sena-brand); font-size: 1.15rem;"></i>
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">Clase Programada Activa</h3>
                </div>
                <?php if (!empty($claseMomento['es_hora_exacta'])): ?>
                    <span class="shadcn-badge badge-puntual"><i class="bi bi-broadcast me-1"></i>En Horario Activo</span>
                <?php else: ?>
                    <span class="shadcn-badge badge-outline"><i class="bi bi-journal-code me-1"></i>Sesión Programada Hoy</span>
                <?php endif; ?>
            </div>
            <div class="card-body-shadcn" style="padding: 1.25rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                    <div style="background: var(--background); padding: 0.75rem 1rem; border-radius: var(--radius); border: 1px solid var(--border);">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted-foreground); font-weight: 600; margin-bottom: 0.25rem;">
                            <i class="bi bi-mortarboard me-1" style="color: var(--sena-brand);"></i>Ficha y Programa
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--foreground);">
                            Ficha <?= htmlspecialchars((string)$numFicha) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--muted-foreground); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <?= htmlspecialchars((string)$nombreFichaProg) ?>
                        </div>
                    </div>

                    <div style="background: var(--background); padding: 0.75rem 1rem; border-radius: var(--radius); border: 1px solid var(--border);">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted-foreground); font-weight: 600; margin-bottom: 0.25rem;">
                            <i class="bi bi-book me-1" style="color: var(--sena-brand);"></i>Materia / Asignatura
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--foreground); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= htmlspecialchars((string)($claseMomento['materia'] ?? 'Formación')) ?>">
                            <?= htmlspecialchars((string)($claseMomento['materia'] ?? 'Formación')) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--muted-foreground);">
                            Instructor: <?= htmlspecialchars((string)$instNombre) ?>
                        </div>
                    </div>

                    <div style="background: var(--background); padding: 0.75rem 1rem; border-radius: var(--radius); border: 1px solid var(--border); grid-column: 1 / -1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted-foreground); font-weight: 600;">
                                    <i class="bi bi-clock-history me-1" style="color: var(--sena-brand);"></i>Horario Asignado
                                </div>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--sena-brand); font-family: monospace;">
                                    <?= htmlspecialchars($horaIni) ?> – <?= htmlspecialchars($horaFin) ?> <span style="font-size: 0.85rem; font-family: inherit; font-weight: 600; color: var(--foreground); margin-left: 0.25rem;">(<?= htmlspecialchars($bloqueTxt) ?>)</span>
                                </div>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.35rem;">
                                <i class="bi bi-shield-check" style="color: #10b981;"></i>
                                <span>Vinculado automáticamente desde tu horario</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card-header-shadcn" style="background: rgba(245, 158, 11, 0.05); display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-calendar2-x" style="color:#d97706; font-size: 1.15rem;"></i>
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">Sin Clase Programada Hoy</h3>
                </div>
                <span class="shadcn-badge badge-outline" style="color:#d97706; border-color:rgba(245, 158, 11, 0.4);"><i class="bi bi-calendar-event me-1"></i>Día sin formación</span>
            </div>
            <div class="card-body-shadcn" style="padding: 1.75rem 1.25rem; text-align: center;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(59, 130, 246, 0.08); color: var(--sena-brand); display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h4 style="margin: 0 0 0.35rem 0; font-size: 1.05rem; font-weight: 700;">No tienes clases programadas para el día de hoy</h4>
                <p style="margin: 0 0 1.25rem 0; font-size: 0.85rem; color: var(--muted-foreground); max-width: 440px; margin-inline: auto;">
                    Tu horario institucional no tiene asignada ninguna sesión de formación con aprendices hoy.
                </p>

                <?php if (!empty($proximaClase)): 
                    $fechaProxObj = !empty($proximaClase['fecha']) ? strtotime($proximaClase['fecha']) : null;
                    $diasSemana = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
                    $meses = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
                    $diaEsp = $fechaProxObj ? ($diasSemana[date('l', $fechaProxObj)] ?? date('l', $fechaProxObj)) : '';
                    $diaNum = $fechaProxObj ? date('j', $fechaProxObj) : '';
                    $mesEsp = $fechaProxObj ? ($meses[(int)date('n', $fechaProxObj)] ?? date('F', $fechaProxObj)) : '';
                    $horaIniProx = substr($proximaClase['hora_inicio'] ?? '00:00', 0, 5);
                    $horaFinProx = substr($proximaClase['hora_fin'] ?? '00:00', 0, 5);
                    $bloqueProxTxt = !empty($proximaClase['bloque']) ? ucfirst(str_replace('_', ' ', $proximaClase['bloque'])) : 'Franja Asignada';
                    $esManana = ($fechaProxObj && date('Y-m-d', $fechaProxObj) === date('Y-m-d', strtotime('+1 day')));
                ?>
                    <div style="background: var(--background); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem 1.25rem; display: inline-flex; flex-direction: column; gap: 0.4rem; text-align: left; max-width: 100%;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                            <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--sena-brand); font-weight: 700;">
                                <i class="bi bi-clock-history me-1"></i>Próxima Clase Programada:
                            </span>
                            <?php if ($esManana): ?>
                                <span class="shadcn-badge badge-puntual" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;"><i class="bi bi-lightning-charge me-1"></i>Mañana</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--foreground);">
                            <?= $diaEsp ?>, <?= $diaNum ?> de <?= $mesEsp ?> &bull; <span style="color:var(--sena-brand);"><?= $horaIniProx ?> – <?= $horaFinProx ?></span> <span style="font-size:0.85rem; font-weight:600; color:var(--muted-foreground);">(<?= htmlspecialchars($bloqueProxTxt) ?>)</span>
                        </div>
                        <div style="font-size: 0.8125rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span><i class="bi bi-mortarboard me-1"></i>Ficha <?= htmlspecialchars((string)($proximaClase['fk_ficha'] ?? '')) ?> (<?= htmlspecialchars((string)($proximaClase['nombre_programa'] ?? 'ADSO')) ?>)</span>
                            <span>&bull;</span>
                            <span><i class="bi bi-book me-1"></i><?= htmlspecialchars((string)($proximaClase['materia'] ?? 'Formación')) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!--  FORMULARIO DE LECTURA RFID AUTOMÁTICO -->
    <div class="shadcn-card" style="margin-bottom: 1.5rem; <?= empty($claseMomento) ? 'opacity: 0.85;' : '' ?>">
        <div class="card-header-shadcn">
            <h3><i class="bi bi-wifi me-2" style="color:<?= empty($claseMomento) ? 'var(--muted-foreground)' : 'var(--sena-brand)' ?>;"></i>Lectura Automática RFID</h3>
            <?php if (!empty($claseMomento)): ?>
                <span class="shadcn-badge badge-puntual"><i class="bi bi-dot"></i>Lector listo</span>
            <?php else: ?>
                <span class="shadcn-badge badge-outline"><i class="bi bi-pause-circle me-1"></i>Lector inactivo</span>
            <?php endif; ?>
        </div>
        <div class="card-body-shadcn">
            <?php if (!empty($claseMomento)): ?>
                <form method="POST" action="index.php?action=registrar-ingreso" id="rfidForm">
                    <input type="hidden" name="materia" id="rfid_materia" value="<?= htmlspecialchars($_SESSION['materia_actual'] ?? '') ?>">
                    <input type="hidden" name="bloque_horario" id="rfid_bloque" value="<?= htmlspecialchars($_SESSION['bloque_actual'] ?? '') ?>">
                    <input type="hidden" name="ficha_id" id="rfid_ficha" value="<?= htmlspecialchars($_SESSION['ficha_actual'] ?? '') ?>">

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
            <?php else: ?>
                <div style="text-align: center; padding: 1.5rem 1rem;">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--background); border: 1px solid var(--border); color: var(--muted-foreground); display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div style="font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem;">Registro no disponible hoy</div>
                    <div style="font-size: 0.85rem; color: var(--muted-foreground); max-width: 400px; margin-inline: auto;">
                        El lector de tarjetas RFID se habilitará automáticamente durante el horario y día de tu clase programada.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
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
