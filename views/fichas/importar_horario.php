<?php
$pageTitle = 'Escáner de Horarios Excel — Control de Ingresos SENA';
require __DIR__ . '/../../views/layouts/header.php';

$idFichaDefault = (int)($_GET['ficha_id'] ?? 3234082);
$todasFichas = $todasFichas ?? [];
$fichaActual = null;
foreach ($todasFichas as $f) {
    if ((int)$f['id'] === $idFichaDefault) {
        $fichaActual = $f;
        break;
    }
}
if (!$fichaActual && !empty($todasFichas)) {
    $fichaActual = $todasFichas[0];
    $idFichaDefault = (int)$fichaActual['id'];
}

$rolSesion = $_SESSION['rol'] ?? '';
$usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
$esAdmin = ($rolSesion === 'Administrador');

$bloquesFichaActual = HorarioModel::contarBloquesHorarioFicha($idFichaDefault);
?>

<?php if (isset($_SESSION['mensaje'])): 
    $msg = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
    $esError = in_array($msg['tipo'] ?? '', ['error', 'danger']);
    $esWarn = ($msg['tipo'] ?? '') === 'warning';
    $bgColor = $esError ? 'rgba(239,68,68,0.1)' : ($esWarn ? 'rgba(245,158,11,0.1)' : 'rgba(16,185,129,0.1)');
    $borderColor = $esError ? '#ef4444' : ($esWarn ? '#f59e0b' : '#10b981');
    $textColor = $esError ? '#dc2626' : ($esWarn ? '#d97706' : '#059669');
    $icono = $esError ? 'bi-x-circle-fill' : ($esWarn ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');
?>
    <div style="background:<?= $bgColor ?>; border:1px solid <?= $borderColor ?>; color:<?= $textColor ?>; padding:0.875rem 1.25rem; border-radius:var(--radius-md); margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between; gap:1rem;">
        <div style="display:flex; align-items:center; gap:0.625rem; font-weight:500; font-size:0.9375rem;">
            <i class="bi <?= $icono ?>" style="font-size:1.25rem;"></i>
            <span><?= htmlspecialchars($msg['texto'] ?? '') ?></span>
        </div>
        <button type="button" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem; color:<?= $textColor ?>;" onclick="this.parentElement.remove()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
<?php endif; ?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem 0.5rem; font-size:0.8125rem;">
                <i class="bi bi-arrow-left me-1"></i>Fichas
            </a>
            <span style="color:var(--muted-foreground);">/</span>
            <span style="font-size:0.875rem; color:var(--muted-foreground);">Módulo de Horarios</span>
        </div>
        <h1 class="page-header-title" style="display:flex; align-items:center; gap:0.625rem;">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(16,185,129,0.12); color:#059669;">
                <i class="bi bi-file-earmark-spreadsheet-fill" style="font-size:1.375rem;"></i>
            </span>
            Escáner & Importador de Horario Excel
        </h1>
        <div class="page-header-subtitle">
            Carga o escanea el archivo Excel de la ficha para insertar los bloques y registrar automáticamente los instructores nuevos.
        </div>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="index.php?action=ficha-horario&id=<?= $idFichaDefault ?>" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-calendar3 me-1"></i>Ver Horario Actual
        </a>
    </div>
</div>

<!-- ── SELECCIÓN DE FICHA & CONFIGURACIÓN ────────────────────────────── -->
<div class="shadcn-card" style="margin-bottom:1.5rem; padding:1.5rem; border-left:4px solid #059669;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div style="flex:1; min-width:280px;">
            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem; color:var(--foreground);">
                <i class="bi bi-journal-bookmark me-1" style="color:#059669;"></i>Ficha de Formación Destino:
            </label>
            <select id="selectFichaDestino" class="shadcn-select" style="max-width:480px; font-weight:500;" onchange="actualizarFichaDestino(this.value)">
                <?php foreach ($todasFichas as $f): 
                    $selected = ((int)$f['id'] === $idFichaDefault) ? 'selected' : '';
                ?>
                    <option value="<?= $f['id'] ?>" <?= $selected ?>>
                        Ficha <?= htmlspecialchars($f['numero_ficha']) ?> — <?= htmlspecialchars($f['programa']) ?> [<?= htmlspecialchars($f['jornada']) ?>]
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:0.375rem;">
                <i class="bi bi-info-circle me-1"></i>Ficha seleccionada por defecto: <strong><?= $idFichaDefault ?></strong>. Todos los bloques se asociarán a este programa.
            </div>
        </div>

        <div style="display:flex; gap:1.5rem; align-items:center; background:var(--muted); padding:0.75rem 1.25rem; border-radius:var(--radius-md);">
            <div>
                <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Clave Instructores Nuevos</div>
                <div style="font-size:1rem; font-weight:700; color:#059669; font-family:monospace;">
                    <i class="bi bi-key-fill me-1"></i>12345
                </div>
            </div>
            <div style="height:2rem; width:1px; background:var(--border);"></div>
            <div>
                <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Correo Automático</div>
                <div style="font-size:0.875rem; font-weight:600; color:var(--foreground);">
                    <i class="bi bi-envelope-at me-1"></i>@sena.edu.co
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── ALERTA DE HORARIO PREVIO EXISTENTE EN ESTA FICHA ──────────────── -->
<?php if ($bloquesFichaActual > 0): ?>
<div class="shadcn-card" style="margin-bottom:1.5rem; padding:1.25rem 1.5rem; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.25); border-left:4px solid #ef4444; border-radius:var(--radius-lg);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:flex-start; gap:0.875rem; max-width:720px;">
            <div style="width:2.5rem; height:2.5rem; border-radius:50%; background:rgba(239,68,68,0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h4 style="font-size:1rem; font-weight:700; color:var(--foreground); margin:0 0 0.25rem 0;">
                    Esta Ficha ya tiene un Horario Vinculado
                </h4>
                <p style="font-size:0.875rem; color:var(--muted-foreground); margin:0; line-height:1.45;">
                    La ficha <strong><?= $idFichaDefault ?></strong> actualmente cuenta con <strong><?= $bloquesFichaActual ?></strong> bloques de clase registrados. Para proteger la información y evitar duplicados, <strong>no es posible subir otro horario</strong> encima.
                    <?php if ($esAdmin): ?>
                        <br><span style="color:#dc2626; font-weight:600;">Como Administrador, si se subió el horario de la ficha equivocada o se necesita reemplazar, puedes eliminarlo aquí para permitir una nueva carga limpia.</span>
                    <?php else: ?>
                        <br><span style="color:#d97706; font-weight:600;">Si este horario fue cargado por error en la ficha equivocada, únicamente el Administrador tiene la opción de eliminarlo.</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div style="display:flex; gap:0.5rem; align-items:center;">
            <a href="index.php?action=ficha-horario&id=<?= $idFichaDefault ?>" class="btn-shadcn btn-shadcn-outline">
                <i class="bi bi-calendar3 me-1"></i>Ver Horario Actual
            </a>
            <?php if ($esAdmin): ?>
                <button type="button" onclick="confirmarEliminarHorario(<?= $idFichaDefault ?>, '<?= htmlspecialchars($idFichaDefault, ENT_QUOTES) ?>')" class="btn-shadcn btn-shadcn-destructive" style="background:#dc2626; color:#fff;" title="Eliminar el horario completo de esta ficha (Solo Administrador)">
                    <i class="bi bi-trash3 me-1"></i>Eliminar Horario de la Ficha
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── PANEL DE CARGA DE HORARIO EXCEL ────────────────────────────────────── -->
<div class="shadcn-card" style="padding:1.75rem; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
        <div style="width:3rem; height:3rem; border-radius:0.75rem; background:rgba(37,99,235,0.15); color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
            <i class="bi bi-file-earmark-excel-fill"></i>
        </div>
        <div>
            <h3 style="font-size:1.125rem; font-weight:700; margin:0; color:var(--foreground);">Subir Horario en Formato Excel</h3>
            <div style="font-size:0.8125rem; color:var(--muted-foreground);">Formato oficial SENA (.xlsx) con distribución de instructores y bloques</div>
        </div>
    </div>

    <p style="font-size:0.875rem; color:var(--muted-foreground); line-height:1.5; margin-bottom:1.25rem;">
        Arrastra o selecciona el archivo Excel de la ficha para escanearlo e insertarlo automáticamente en el sistema. Los instructores y competencias se vincularán de forma inmediata.
    </p>

    <form id="formSubirExcel" enctype="multipart/form-data" onsubmit="event.preventDefault(); iniciarEscaneo(false);">
        <div id="dropzone" style="border:2px dashed var(--border); border-radius:var(--radius-lg); padding:2.5rem 1.5rem; text-align:center; cursor:pointer; transition:all 0.2s ease; background:var(--muted); margin-bottom:1rem;"
             onclick="document.getElementById('archivoExcelInput').click()"
             ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
            <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem; color:var(--muted-foreground); display:block; margin-bottom:0.75rem;"></i>
            <div id="dropzoneText" style="font-size:0.9375rem; font-weight:600; color:var(--foreground); margin-bottom:0.25rem;">
                Haz clic o arrastra tu archivo Excel aquí
            </div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                Solo archivos .xlsx (Máx. 10 MB)
            </div>
            <input type="file" id="archivoExcelInput" name="archivo_excel" accept=".xlsx" style="display:none;" onchange="handleFileSelect(this)">
        </div>

        <div id="fileInfoCard" style="display:none; align-items:center; justify-content:space-between; background:rgba(37,99,235,0.08); border:1px solid rgba(37,99,235,0.25); border-radius:var(--radius-md); padding:0.75rem 1rem; margin-bottom:1rem;">
            <div style="display:flex; align-items:center; gap:0.5rem; overflow:hidden;">
                <i class="bi bi-file-earmark-excel-fill" style="color:#2563eb; font-size:1.5rem;"></i>
                <span id="fileNameDisplay" style="font-size:0.875rem; font-weight:600; color:var(--foreground); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">archivo.xlsx</span>
            </div>
            <button type="button" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem; color:var(--muted-foreground);" onclick="removerArchivoSeleccionado(event)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </form>

    <div style="display:flex; justify-content:flex-end;">
        <button type="button" id="btnSubirPersonalizado" class="btn-shadcn btn-shadcn-primary" style="padding:0.75rem 1.5rem; font-size:0.9375rem; font-weight:600;" onclick="iniciarEscaneo(false)" disabled>
            <i class="bi bi-lightning-charge-fill me-2"></i>Escanear e Insertar Horario
        </button>
    </div>
</div>

<!-- ── PANEL DE SIMULACIÓN EN TIEMPO REAL ────────────────────────────── -->
<div id="panelSimulacion" class="shadcn-card" style="display:none; padding:1.75rem; margin-bottom:1.5rem; border:2px solid #059669; box-shadow:0 10px 30px rgba(5,150,105,0.12);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
        <div>
            <h3 style="font-size:1.25rem; font-weight:700; margin:0; display:flex; align-items:center; gap:0.5rem; color:var(--foreground);">
                <span class="spinner-border spinner-border-sm text-success" role="status" style="width:1.25rem; height:1.25rem; border-width:2px; display:inline-block; border-radius:50%; border-top-color:#059669; animation:spin 0.8s linear infinite;"></span>
                Simulación del Escáner en Tiempo Real
            </h3>
            <div style="font-size:0.875rem; color:var(--muted-foreground); margin-top:0.25rem;">
                Procesando el archivo Excel e interactuando con la base de datos MySQL en vivo...
            </div>
        </div>
        <div id="simulacionPorcentaje" style="font-size:1.5rem; font-weight:800; color:#059669; font-family:monospace;">
            0%
        </div>
    </div>

    <!-- Barra de progreso -->
    <div style="height:0.75rem; width:100%; background:var(--muted); border-radius:1rem; overflow:hidden; margin-bottom:1.5rem;">
        <div id="progressBar" style="height:100%; width:0%; background:linear-gradient(90deg, #10b981, #059669); transition:width 0.4s ease; border-radius:1rem;"></div>
    </div>

    <!-- Lista de pasos en tiempo real -->
    <div id="contenedorPasos" style="display:flex; flex-direction:column; gap:0.75rem;">
        <div class="paso-item" id="paso-1" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 1: Apertura y descompresión de estructura XLSX (ZipArchive & SimpleXML)</span>
        </div>
        <div class="paso-item" id="paso-2" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 2: Lectura de cadenas compartidas y escaneo de hojas de calendario semanal</span>
        </div>
        <div class="paso-item" id="paso-3" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 3: Identificación de instructores y validación con usuarios en base de datos</span>
        </div>
        <div class="paso-item" id="paso-4" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 4: Creación de instructores nuevos con credenciales (clave: 12345, rol: Instructor)</span>
        </div>
        <div class="paso-item" id="paso-5" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 5: Inserción de bloques en <code>horario_bloque</code> y materias en <code>ficha_asignatura</code></span>
        </div>
        <div class="paso-item" id="paso-6" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 6: Consolidación y verificación de integridad transaccional</span>
        </div>
    </div>
</div>

<!-- ── PANEL DE RESULTADOS TRAS EL ESCANEO ────────────────────────────── -->
<div id="panelResultados" class="shadcn-card" style="display:none; padding:1.75rem; margin-bottom:2rem; border-top:4px solid #059669;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <div style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.25rem 0.625rem; border-radius:1rem; background:rgba(16,185,129,0.12); color:#059669; font-size:0.75rem; font-weight:700; margin-bottom:0.5rem;">
                <i class="bi bi-check-circle-fill"></i>IMPORTACIÓN EXITOSA
            </div>
            <h2 style="font-size:1.5rem; font-weight:800; margin:0; color:var(--foreground);" id="resumenTitulo">
                Horario Insertado Correctamente
            </h2>
            <div style="font-size:0.875rem; color:var(--muted-foreground); margin-top:0.25rem;" id="resumenSubtitulo">
                Los datos se encuentran activos y vinculados a la ficha seleccionada.
            </div>
        </div>

        <div style="display:flex; gap:0.5rem;">
            <a href="index.php?action=ficha-horario&id=<?= $idFichaDefault ?>" id="btnVerHorarioResultado" class="btn-shadcn btn-shadcn-primary">
                <i class="bi bi-calendar-check me-1"></i>Ver Horario en Calendario
            </a>
            <button type="button" class="btn-shadcn btn-shadcn-outline" onclick="reiniciarEscaneo()">
                <i class="bi bi-arrow-repeat me-1"></i>Escanear de Nuevo
            </button>
        </div>
    </div>

    <!-- Tarjetas de métricas -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.75rem;">
        <div style="background:var(--muted); padding:1rem; border-radius:var(--radius-md); border-left:3px solid #059669;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Bloques Registrados</div>
            <div style="font-size:1.75rem; font-weight:800; color:var(--foreground);" id="statBloques">0</div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Franjas de 3 horas insertadas</div>
        </div>

        <div style="background:var(--muted); padding:1rem; border-radius:var(--radius-md); border-left:3px solid #2563eb;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Total Horas de Clase</div>
            <div style="font-size:1.75rem; font-weight:800; color:#2563eb;" id="statHoras">0</div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Horas de 60 min procesadas</div>
        </div>

        <div style="background:var(--muted); padding:1rem; border-radius:var(--radius-md); border-left:3px solid #8b5cf6;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Instructores Nuevos</div>
            <div style="font-size:1.75rem; font-weight:800; color:#8b5cf6;" id="statInstructoresNuevos">0</div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Usuarios creados (Clave: 12345)</div>
        </div>

        <div style="background:var(--muted); padding:1rem; border-radius:var(--radius-md); border-left:3px solid #f59e0b;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Periodo de Fechas</div>
            <div style="font-size:1rem; font-weight:700; color:var(--foreground); margin-top:0.375rem;" id="statPeriodo">—</div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">Rango cubierto en calendario</div>
        </div>
    </div>

    <!-- SECCIÓN: INSTRUCTORES NUEVOS CREADOS -->
    <div style="margin-bottom:1.75rem;" id="seccionInstructoresNuevos">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.875rem;">
            <h3 style="font-size:1.125rem; font-weight:700; margin:0; display:flex; align-items:center; gap:0.5rem; color:var(--foreground);">
                <i class="bi bi-person-plus-fill" style="color:#8b5cf6;"></i>
                Instructores Nuevos Creados en el Sistema
            </h3>
            <span class="shadcn-badge" style="background:rgba(139,92,246,0.15); color:#8b5cf6; font-weight:600;" id="badgeCantNuevos">0 nuevos</span>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1rem;" id="gridInstructoresNuevos">
            <!-- Dinámico por JS -->
        </div>
    </div>

    <!-- SECCIÓN: INSTRUCTORES EXISTENTES VINCULADOS -->
    <div style="margin-bottom:1.5rem;" id="seccionInstructoresExistentes">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.875rem;">
            <h3 style="font-size:1rem; font-weight:700; margin:0; display:flex; align-items:center; gap:0.5rem; color:var(--foreground);">
                <i class="bi bi-people-fill" style="color:#059669;"></i>
                Instructores Previamente Registrados (Vinculados)
            </h3>
            <span class="shadcn-badge badge-secondary" id="badgeCantExistentes">0 vinculados</span>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:0.5rem;" id="listaInstructoresExistentes">
            <!-- Dinámico por JS -->
        </div>
    </div>

    <!-- SECCIÓN: ASIGNATURAS DETECTADAS -->
    <div>
        <h4 style="font-size:0.875rem; font-weight:600; color:var(--muted-foreground); margin-bottom:0.5rem; text-transform:uppercase; letter-spacing:0.05em;">
            <i class="bi bi-book me-1"></i>Competencias y Asignaturas Procesadas
        </h4>
        <div style="display:flex; flex-wrap:wrap; gap:0.375rem;" id="listaMaterias">
            <!-- Dinámico por JS -->
        </div>
    </div>
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
.paso-activo {
    background: rgba(16,185,129,0.1) !important;
    border: 1px solid rgba(16,185,129,0.3) !important;
}
.paso-completado {
    background: rgba(16,185,129,0.15) !important;
    color: #059669 !important;
}
.instructor-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: transform 0.2s ease;
}
.instructor-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
</style>

<script>
let archivoSeleccionado = null;

function actualizarFichaDestino(idFicha) {
    window.location.href = 'index.php?action=ficha-horario-importar&ficha_id=' + idFicha;
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('dropzone').style.borderColor = '#2563eb';
    document.getElementById('dropzone').style.background = 'rgba(37,99,235,0.05)';
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('dropzone').style.borderColor = 'var(--border)';
    document.getElementById('dropzone').style.background = 'var(--muted)';
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    handleDragLeave(e);
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        setArchivo(files[0]);
    }
}

function handleFileSelect(input) {
    if (input.files.length > 0) {
        setArchivo(input.files[0]);
    }
}

function setArchivo(file) {
    if (!file.name.toLowerCase().endsWith('.xlsx')) {
        Swal.fire({
            icon: 'error',
            title: 'Formato no válido',
            text: 'Solo se admiten archivos en formato Excel (.xlsx).'
        });
        return;
    }
    archivoSeleccionado = file;
    document.getElementById('fileNameDisplay').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('fileInfoCard').style.display = 'flex';
    document.getElementById('btnSubirPersonalizado').disabled = false;
    document.getElementById('btnSubirPersonalizado').classList.remove('btn-shadcn-outline');
    document.getElementById('btnSubirPersonalizado').classList.add('btn-shadcn-primary');
}

function removerArchivoSeleccionado(e) {
    e.stopPropagation();
    archivoSeleccionado = null;
    document.getElementById('archivoExcelInput').value = '';
    document.getElementById('fileInfoCard').style.display = 'none';
    document.getElementById('btnSubirPersonalizado').disabled = true;
    document.getElementById('btnSubirPersonalizado').classList.add('btn-shadcn-outline');
    document.getElementById('btnSubirPersonalizado').classList.remove('btn-shadcn-primary');
}

async function iniciarEscaneo(esEjemplo) {
    const idFicha = document.getElementById('selectFichaDestino').value;
    if (!idFicha) {
        Swal.fire('Atención', 'Selecciona una ficha de formación válida.', 'warning');
        return;
    }

    if (!esEjemplo && !archivoSeleccionado) {
        Swal.fire('Atención', 'Selecciona un archivo Excel (.xlsx) para cargar.', 'warning');
        return;
    }

    document.getElementById('panelResultados').style.display = 'none';
    const panelSim = document.getElementById('panelSimulacion');
    panelSim.style.display = 'block';
    panelSim.scrollIntoView({ behavior: 'smooth' });

    const btnEjemplo = document.getElementById('btnEscanearEjemplo');
    if (btnEjemplo) btnEjemplo.disabled = true;
    document.getElementById('btnSubirPersonalizado').disabled = true;

    const progressBar = document.getElementById('progressBar');
    const porcentajeText = document.getElementById('simulacionPorcentaje');
    progressBar.style.width = '0%';
    porcentajeText.textContent = '0%';

    for (let i = 1; i <= 6; i++) {
        const p = document.getElementById('paso-' + i);
        p.className = 'paso-item';
        p.querySelector('i').className = 'bi bi-circle text-muted';
    }

    const activarPaso = (num, pct) => {
        return new Promise(resolve => {
            setTimeout(() => {
                const p = document.getElementById('paso-' + num);
                p.classList.add('paso-activo');
                p.querySelector('i').className = 'bi bi-arrow-repeat text-success';
                progressBar.style.width = pct + '%';
                porcentajeText.textContent = pct + '%';
                resolve();
            }, 300);
        });
    };

    const completarPaso = (num) => {
        const p = document.getElementById('paso-' + num);
        p.classList.remove('paso-activo');
        p.classList.add('paso-completado');
        p.querySelector('i').className = 'bi bi-check-circle-fill text-success';
    };

    await activarPaso(1, 15);
    completarPaso(1);

    await activarPaso(2, 35);
    completarPaso(2);

    await activarPaso(3, 55);

    const formData = new FormData();
    formData.append('ficha_id', idFicha);
    formData.append('ajax', '1');

    if (esEjemplo) {
        formData.append('usar_ejemplo', '1');
    } else {
        formData.append('archivo_excel', archivoSeleccionado);
    }

    try {
        const response = await fetch('index.php?action=ficha-horario-importar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        completarPaso(3);
        await activarPaso(4, 75);
        completarPaso(4);

        await activarPaso(5, 90);
        completarPaso(5);

        await activarPaso(6, 100);
        completarPaso(6);

        setTimeout(() => {
            if (data.exito) {
                mostrarResultados(data, idFicha);
            } else {
                panelSim.style.display = 'none';
                if (data.horario_existente) {
                    if (data.es_admin) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Horario Ya Registrado',
                            html: `<div style="text-align:left; font-size:0.9rem; line-height:1.5;">
                                <p>La <strong>Ficha ${data.ficha_id}</strong> ya tiene un horario vinculado con <strong>${data.bloques_existentes || 0}</strong> bloques de clase.</p>
                                <p style="color:#dc2626; font-weight:600;">Para evitar duplicidades o sobreescritura accidental, no es posible subir otro horario encima.</p>
                                <p>Como <strong>Administrador</strong>, si subiste el archivo por equivocación a la ficha equivocada o necesitas actualizarlo, puedes eliminar el horario actual para permitir la nueva subida.</p>
                            </div>`,
                            showCancelButton: true,
                            confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Eliminar Horario Anterior',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#64748b'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                ejecutarEliminacionHorario(data.ficha_id);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Horario Ya Vinculado',
                            html: `<div style="text-align:left; font-size:0.9rem; line-height:1.5;">
                                <p>La <strong>Ficha ${data.ficha_id}</strong> ya cuenta con un horario registrado con <strong>${data.bloques_existentes || 0}</strong> bloques de clase.</p>
                                <p style="color:#d97706; font-weight:600;">Para evitar molestias o sobreescrituras, el sistema no permite subir otro horario sobre esta ficha.</p>
                                <p>En caso de haberse subido un horario a la ficha equivocada, únicamente el <strong>Administrador</strong> tiene la potestad de eliminarlo.</p>
                            </div>`
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en el Escáner',
                        text: data.mensaje || 'Ocurrió un error al procesar el archivo.'
                    });
                }
            }
            const btnEj = document.getElementById('btnEscanearEjemplo');
            if (btnEj) btnEj.disabled = false;
            document.getElementById('btnSubirPersonalizado').disabled = !archivoSeleccionado;
        }, 500);

    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Error de Comunicación',
            text: 'No se pudo conectar con el servidor para procesar el horario.'
        });
        const btnEj = document.getElementById('btnEscanearEjemplo');
        if (btnEj) btnEj.disabled = false;
        document.getElementById('btnSubirPersonalizado').disabled = !archivoSeleccionado;
    }
}

function mostrarResultados(data, idFicha) {
    document.getElementById('panelSimulacion').style.display = 'none';
    const panelRes = document.getElementById('panelResultados');
    panelRes.style.display = 'block';
    panelRes.scrollIntoView({ behavior: 'smooth' });

    document.getElementById('statBloques').textContent = data.total_bloques_insertados || 0;
    document.getElementById('statHoras').textContent = (data.total_horas || 0) + ' hrs';

    const cantNuevos = Object.keys(data.instructores_nuevos || {}).length;
    document.getElementById('statInstructoresNuevos').textContent = cantNuevos;
    document.getElementById('badgeCantNuevos').textContent = cantNuevos + ' nuevos';

    if (data.rango_fechas && data.rango_fechas.inicio) {
        document.getElementById('statPeriodo').textContent = data.rango_fechas.inicio + ' al ' + data.rango_fechas.fin;
    } else {
        document.getElementById('statPeriodo').textContent = '2025';
    }

    document.getElementById('btnVerHorarioResultado').href = 'index.php?action=ficha-horario&id=' + idFicha;

    const gridNuevos = document.getElementById('gridInstructoresNuevos');
    gridNuevos.innerHTML = '';

    if (cantNuevos > 0) {
        document.getElementById('seccionInstructoresNuevos').style.display = 'block';
        for (const id in data.instructores_nuevos) {
            const inst = data.instructores_nuevos[id];
            const card = document.createElement('div');
            card.className = 'instructor-card';
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                    <div>
                        <div style="font-weight:700; font-size:0.9375rem; color:var(--foreground);">${inst.nombre}</div>
                        <div style="font-size:0.75rem; color:#8b5cf6; font-weight:600;"><i class="bi bi-person-badge me-1"></i>Instructor Nuevo Registrado</div>
                    </div>
                    <span class="shadcn-badge" style="background:rgba(16,185,129,0.15); color:#059669; font-size:0.6875rem;">ID #${inst.id}</span>
                </div>
                <div style="background:var(--muted); padding:0.625rem; border-radius:var(--radius-sm); font-size:0.8125rem; margin-bottom:0.5rem;">
                    <div style="margin-bottom:0.25rem;">
                        <span style="color:var(--muted-foreground);"><i class="bi bi-envelope me-1"></i>Correo:</span>
                        <strong style="color:var(--foreground);">${inst.correo}</strong>
                    </div>
                    <div>
                        <span style="color:var(--muted-foreground);"><i class="bi bi-key me-1"></i>Clave:</span>
                        <code style="background:var(--card); padding:0.125rem 0.375rem; border-radius:0.25rem; color:#059669; font-weight:700;">${inst.clave_inicial || '12345'}</code>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:var(--muted-foreground);">
                    <span>Doc: ${inst.identificacion || '—'}</span>
                    <button type="button" class="btn-shadcn btn-shadcn-ghost" style="padding:0.125rem 0.375rem; font-size:0.75rem; color:#2563eb;" onclick="copiarCredenciales('${inst.correo}', '${inst.clave_inicial || '12345'}')">
                        <i class="bi bi-clipboard me-1"></i>Copiar
                    </button>
                </div>
            `;
            gridNuevos.appendChild(card);
        }
    } else {
        document.getElementById('seccionInstructoresNuevos').style.display = 'none';
    }

    const listaExistentes = document.getElementById('listaInstructoresExistentes');
    listaExistentes.innerHTML = '';
    const cantExistentes = Object.keys(data.instructores_existentes || {}).length;
    document.getElementById('badgeCantExistentes').textContent = cantExistentes + ' vinculados';

    for (const id in data.instructores_existentes) {
        const inst = data.instructores_existentes[id];
        const span = document.createElement('span');
        span.className = 'shadcn-badge badge-secondary';
        span.style.padding = '0.375rem 0.75rem';
        span.style.fontSize = '0.8125rem';
        span.innerHTML = `<i class="bi bi-person-check me-1" style="color:#059669;"></i><strong>${inst.nombre}</strong> (${inst.correo})`;
        listaExistentes.appendChild(span);
    }

    const listaMaterias = document.getElementById('listaMaterias');
    listaMaterias.innerHTML = '';
    (data.materias_detectadas || []).forEach(m => {
        const badge = document.createElement('span');
        badge.className = 'shadcn-badge';
        badge.style.background = 'var(--muted)';
        badge.style.color = 'var(--foreground)';
        badge.style.border = '1px solid var(--border)';
        badge.style.fontSize = '0.75rem';
        badge.textContent = m;
        listaMaterias.appendChild(badge);
    });

    Swal.fire({
        icon: 'success',
        title: '¡Horario Importado!',
        html: `Se registraron <strong>${data.total_bloques_insertados}</strong> bloques y <strong>${cantNuevos}</strong> instructores nuevos con clave <code>12345</code> en la ficha <strong>${idFicha}</strong>.`,
        confirmButtonColor: '#059669',
        confirmButtonText: 'Ver Horario en Vivo'
    }).then(res => {
        if (res.isConfirmed) {
            window.location.href = 'index.php?action=ficha-horario&id=' + idFicha;
        }
    });
}

function copiarCredenciales(correo, clave) {
    const texto = `Usuario: ${correo}\nContraseña: ${clave}`;
    navigator.clipboard.writeText(texto).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Credenciales copiadas al portapapeles',
            showConfirmButton: false,
            timer: 2000
        });
    });
}

function reiniciarEscaneo() {
    document.getElementById('panelResultados').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function confirmarEliminarHorario(idFicha, numFicha) {
    Swal.fire({
        title: '¿Eliminar horario completo?',
        html: `Esta acción eliminará todos los bloques de horario registrados para la <strong>Ficha ${numFicha || idFicha}</strong>.<br><br>Permitirá cargar un horario nuevo en caso de haber subido el archivo a la ficha equivocada.<br><br><span style="color:#dc2626; font-weight:600;">Acción exclusiva para el Administrador.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Sí, eliminar horario',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarEliminacionHorario(idFicha);
        }
    });
}

function ejecutarEliminacionHorario(idFicha) {
    Swal.fire({
        title: 'Eliminando horario...',
        text: 'Por favor espera mientras se limpian los bloques.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData();
    formData.append('ficha_id', idFicha);
    formData.append('ajax', '1');

    fetch('index.php?action=ficha-horario-eliminar', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.exito) {
            Swal.fire({
                icon: 'success',
                title: 'Horario Eliminado',
                text: data.mensaje || 'Se eliminó el horario de la ficha correctamente.',
                confirmButtonColor: '#059669'
            }).then(() => {
                window.location.href = 'index.php?action=ficha-horario-importar&ficha_id=' + idFicha;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo eliminar',
                text: data.mensaje || 'Error al intentar eliminar el horario.'
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Error de Comunicación',
            text: 'Ocurrió un error al comunicarse con el servidor.'
        });
    });
}
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
