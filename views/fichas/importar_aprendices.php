<?php
$pageTitle = 'Escáner & Importador de Aprendices Excel — Control de Ingresos SENA';
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
$esAdmin = ($rolSesion === 'Administrador');
$archivosDetectados = $archivosDetectados ?? [];
?>

<script src="public/js/xlsx.full.min.js"></script>

<style>
.badge-null {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.3);
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-sm, 0.375rem);
    font-size: 0.725rem;
    font-weight: 700;
    font-family: monospace;
}
.badge-ready {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-sm, 0.375rem);
    font-size: 0.725rem;
    font-weight: 600;
}
.badge-error {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-sm, 0.375rem);
    font-size: 0.725rem;
    font-weight: 600;
}
.archivo-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md, 0.5rem);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    transition: all 0.2s ease;
}
.archivo-card:hover {
    border-color: #059669;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<!-- ── ENCABEZADO DE PÁGINA ────────────────────────────────────────── -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem 0.5rem; font-size:0.8125rem;">
                <i class="bi bi-arrow-left me-1"></i>Fichas
            </a>
            <span style="color:var(--muted-foreground);">/</span>
            <span style="font-size:0.875rem; color:var(--muted-foreground);">Módulo de Aprendices</span>
        </div>
        <h1 class="page-header-title" style="display:flex; align-items:center; gap:0.625rem;">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(5,150,105,0.12); color:#059669;">
                <i class="bi bi-people-fill" style="font-size:1.375rem;"></i>
            </span>
            Escáner & Importador de Aprendices Excel
        </h1>
        <div class="page-header-subtitle">
            Escanea listados de aprendices en formato Excel (.xls / .xlsx), crea sus perfiles automáticos con contraseña <code>sena2025</code> y deja el código RFID en <code>NULL</code> para asignación administrativa.
        </div>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="index.php?action=usuarios&ficha_id=<?= $idFichaDefault ?>" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-people me-1"></i>Ver Aprendices de la Ficha
        </a>
    </div>
</div>

<!-- ── SELECCIÓN DE FICHA & CONFIGURACIÓN DE REGLAS ────────────────── -->
<div class="shadcn-card" style="margin-bottom:1.5rem; padding:1.5rem; border-left:4px solid #059669;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem;">
        <div style="flex:1; min-width:300px;">
            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem; color:var(--foreground);">
                <i class="bi bi-journal-bookmark me-1" style="color:#059669;"></i>Ficha de Formación Destino:
            </label>
            <select id="selectFichaDestino" class="shadcn-select" style="max-width:520px; font-weight:600;" onchange="actualizarFichaDestino(this.value)">
                <?php foreach ($todasFichas as $f): 
                    $selected = ((int)$f['id'] === $idFichaDefault) ? 'selected' : '';
                ?>
                    <option value="<?= $f['id'] ?>" <?= $selected ?>>
                        Ficha <?= htmlspecialchars($f['numero_ficha']) ?> — <?= htmlspecialchars($f['programa']) ?> [<?= htmlspecialchars($f['jornada'] ?? 'Diurna') ?>]
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:0.375rem;">
                <i class="bi bi-info-circle me-1"></i>Los aprendices importados se registrarán y vincularán automáticamente a esta ficha seleccionada.
            </div>
        </div>

        <div style="display:flex; gap:1.25rem; align-items:center; background:var(--muted); padding:0.875rem 1.25rem; border-radius:var(--radius-md); flex-wrap:wrap;">
            <div>
                <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Contraseña Predeterminada</div>
                <div style="font-size:0.95rem; font-weight:700; color:#059669; font-family:monospace;">
                    <i class="bi bi-key-fill me-1"></i>sena2025
                </div>
            </div>
            <div style="height:2rem; width:1px; background:var(--border);"></div>
            <div>
                <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Usuario de Ingreso</div>
                <div style="font-size:0.875rem; font-weight:600; color:var(--foreground);">
                    <i class="bi bi-envelope-at me-1"></i>Correo del aprendiz
                </div>
            </div>
            <div style="height:2rem; width:1px; background:var(--border);"></div>
            <div>
                <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Código Tarjeta RFID</div>
                <div style="font-size:0.875rem; font-weight:700; color:#d97706;">
                    <i class="bi bi-upc-scan me-1"></i>NULL (Admin asigna)
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── SECCIÓN 1: ARCHIVOS DETECTADOS EN LA CARPETA (public/uploads/Aprendices/) ─ -->
<div class="shadcn-card" style="padding:1.5rem; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem;">
        <div style="display:flex; align-items:center; gap:0.625rem;">
            <div style="width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.25rem;">
                <i class="bi bi-folder2-open"></i>
            </div>
            <div>
                <h3 style="font-size:1rem; font-weight:700; margin:0; color:var(--foreground);">Archivos en Carpeta del Servidor</h3>
                <div style="font-size:0.75rem; color:var(--muted-foreground); font-family:monospace;">public/uploads/Aprendices/</div>
            </div>
        </div>
        <span class="shadcn-badge badge-secondary"><?= count($archivosDetectados) ?> archivo(s) disponible(s)</span>
    </div>

    <?php if (empty($archivosDetectados)): ?>
        <div style="padding:1.25rem; background:var(--muted); border-radius:var(--radius-md); text-align:center; color:var(--muted-foreground); font-size:0.875rem;">
            <i class="bi bi-inbox me-1"></i>No hay archivos en la carpeta <code>public/uploads/Aprendices/</code> aún. Puedes arrastrar uno abajo.
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1rem;">
            <?php foreach ($archivosDetectados as $arch): ?>
                <div class="archivo-card">
                    <div style="display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
                        <i class="bi bi-file-earmark-excel-fill" style="font-size:2rem; color:#059669; flex-shrink:0;"></i>
                        <div style="overflow:hidden;">
                            <div style="font-weight:700; font-size:0.875rem; color:var(--foreground); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= htmlspecialchars($arch['nombre']) ?>">
                                <?= htmlspecialchars($arch['nombre']) ?>
                            </div>
                            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                                <span><?= $arch['tamano_kb'] ?> KB</span> • <span><?= $arch['fecha'] ?></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-shadcn btn-shadcn-primary" style="padding:0.4rem 0.875rem; font-size:0.8125rem; white-space:nowrap;"
                            onclick="cargarArchivoServidor('<?= htmlspecialchars($arch['ruta_relativa']) ?>', '<?= htmlspecialchars(addslashes($arch['nombre'])) ?>')">
                        <i class="bi bi-lightning-charge me-1"></i>Escanear
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ── SECCIÓN 2: DROPZONE PARA CARGAR ARCHIVO NUEVO ──────────────── -->
<div class="shadcn-card" style="padding:1.75rem; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
        <div style="width:3rem; height:3rem; border-radius:0.75rem; background:rgba(16,185,129,0.15); color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
        </div>
        <div>
            <h3 style="font-size:1.125rem; font-weight:700; margin:0; color:var(--foreground);">Subir o Arrastrar Archivo de Aprendices</h3>
            <div style="font-size:0.8125rem; color:var(--muted-foreground);">Compatible con formatos Excel oficiales SENA (.xls, .xlsx) y CSV</div>
        </div>
    </div>

    <div id="dropzone" style="border:2px dashed var(--border); border-radius:var(--radius-lg); padding:2.5rem 1.5rem; text-align:center; cursor:pointer; transition:all 0.2s ease; background:var(--muted); margin-bottom:1rem;"
         onclick="document.getElementById('archivoExcelInput').click()"
         ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
        <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem; color:#059669; display:block; margin-bottom:0.75rem;"></i>
        <div id="dropzoneText" style="font-size:0.9375rem; font-weight:600; color:var(--foreground); margin-bottom:0.25rem;">
            Haz clic o arrastra tu archivo Excel de aprendices aquí
        </div>
        <div style="font-size:0.75rem; color:var(--muted-foreground);">
            Archivos compatibles: .xls (Excel 97-2003), .xlsx (Excel moderno), .csv (Máx. 15 MB)
        </div>
        <input type="file" id="archivoExcelInput" accept=".xls,.xlsx,.csv" style="display:none;" onchange="handleFileSelect(this)">
    </div>

    <div id="fileInfoCard" style="display:none; align-items:center; justify-content:space-between; background:rgba(5,150,105,0.08); border:1px solid rgba(5,150,105,0.25); border-radius:var(--radius-md); padding:0.75rem 1rem; margin-bottom:1rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; overflow:hidden;">
            <i class="bi bi-file-earmark-excel-fill" style="color:#059669; font-size:1.5rem;"></i>
            <span id="fileNameDisplay" style="font-size:0.875rem; font-weight:600; color:var(--foreground); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">archivo.xls</span>
        </div>
        <button type="button" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem; color:var(--muted-foreground);" onclick="removerArchivoSeleccionado(event)">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

<!-- ── ALERTA DE COLUMNAS FALTANTES (SE MUESTRA SI FALTA ALGO EN EL EXCEL) ─ -->
<div id="alertaColumnasFaltantes" style="display:none; margin-bottom:1.5rem; padding:1.25rem 1.5rem; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.3); border-left:4px solid #ef4444; border-radius:var(--radius-md);">
    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
        <i class="bi bi-exclamation-octagon-fill" style="color:#dc2626; font-size:1.5rem; margin-top:0.125rem;"></i>
        <div>
            <h4 style="font-size:0.95rem; font-weight:700; color:#dc2626; margin:0 0 0.25rem 0;">¡Atención! Al archivo Excel le faltan columnas requeridas</h4>
            <div id="mensajeColumnasFaltantes" style="font-size:0.875rem; color:var(--foreground); line-height:1.45;"></div>
            <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:0.375rem;">
                Las columnas obligatorias son: <strong>Número de Identificación</strong>, <strong>Nombres</strong>, <strong>Apellidos</strong> y <strong>Correo Electrónico</strong>.
            </div>
        </div>
    </div>
</div>

<!-- ── PANEL DE PREVISUALIZACIÓN DE APRENDICES ESCANEADOS ───────────────── -->
<div id="panelPrevisualizacion" class="shadcn-card" style="display:none; padding:1.75rem; margin-bottom:1.5rem; border-top:4px solid #059669;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
        <div>
            <h3 style="font-size:1.125rem; font-weight:700; margin:0; color:var(--foreground); display:flex; align-items:center; gap:0.5rem;">
                <i class="bi bi-eye-fill" style="color:#059669;"></i>
                Previsualización de Aprendices Detectados
            </h3>
            <div style="font-size:0.8125rem; color:var(--muted-foreground); margin-top:0.25rem;">
                Revisa los datos antes de procesar el registro en la base de datos. Los campos vacíos opcionales se guardarán como <code>NULL</code>.
            </div>
        </div>

        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
            <span class="shadcn-badge" style="background:var(--muted); color:var(--foreground); padding:0.4rem 0.75rem; font-size:0.8125rem;">
                Total: <strong id="chipTotal" style="margin-left:0.25rem;">0</strong>
            </span>
            <span class="shadcn-badge badge-activo" style="padding:0.4rem 0.75rem; font-size:0.8125rem;">
                Listos: <strong id="chipListos" style="margin-left:0.25rem;">0</strong>
            </span>
            <span class="shadcn-badge" style="background:rgba(245,158,11,0.15); color:#d97706; padding:0.4rem 0.75rem; font-size:0.8125rem;">
                Con NULLs: <strong id="chipNulls" style="margin-left:0.25rem;">0</strong>
            </span>
            <span class="shadcn-badge" style="background:rgba(239,68,68,0.15); color:#dc2626; padding:0.4rem 0.75rem; font-size:0.8125rem;">
                Incompletos: <strong id="chipIncompletos" style="margin-left:0.25rem;">0</strong>
            </span>
        </div>
    </div>

    <!-- Tabla interactiva de filas escaneadas -->
    <div class="shadcn-table-wrapper" style="max-height:420px; overflow-y:auto; margin-bottom:1.25rem; border:1px solid var(--border); border-radius:var(--radius-md);">
        <table class="shadcn-table" style="font-size:0.8125rem;">
            <thead style="position:sticky; top:0; background:var(--muted); z-index:2;">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Correo (Usuario)</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado / Alerta</th>
                </tr>
            </thead>
            <tbody id="tablaCuerpoPrevisualizacion">
                <!-- Inyectado dinámicamente -->
            </tbody>
        </table>
    </div>

    <!-- Botón de Confirmación y Procesamiento Masivo -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div style="font-size:0.8125rem; color:var(--muted-foreground);">
            <i class="bi bi-shield-lock me-1" style="color:#059669;"></i>
            Se asignará la clave predeterminada <strong>sena2025</strong> y el código RFID quedará en <strong>NULL</strong> para el Administrador.
        </div>
        <div style="display:flex; gap:0.5rem;">
            <button type="button" class="btn-shadcn btn-shadcn-ghost" onclick="cancelarPrevisualizacion()">
                <i class="bi bi-x-circle me-1"></i>Descartar
            </button>
            <button type="button" id="btnConfirmarImportacion" class="btn-shadcn btn-shadcn-primary" style="padding:0.625rem 1.5rem; font-size:0.9375rem; font-weight:700;" onclick="ejecutarImportacion()">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i>Confirmar & Registrar Aprendices
            </button>
        </div>
    </div>
</div>

<!-- ── PANEL DE SIMULACIÓN EN TIEMPO REAL ────────────────────────────── -->
<div id="panelSimulacion" class="shadcn-card" style="display:none; padding:1.75rem; margin-bottom:1.5rem; border:2px solid #059669; box-shadow:0 10px 30px rgba(5,150,105,0.12);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
        <div>
            <h3 style="font-size:1.25rem; font-weight:700; margin:0; display:flex; align-items:center; gap:0.5rem; color:var(--foreground);">
                <span class="spinner-border spinner-border-sm text-success" role="status" style="width:1.25rem; height:1.25rem; border-width:2px; display:inline-block; border-radius:50%; border-top-color:#059669; animation:spin 0.8s linear infinite;"></span>
                Procesando Importación de Aprendices
            </h3>
            <div style="font-size:0.875rem; color:var(--muted-foreground); margin-top:0.25rem;">
                Registrando perfiles de usuario, generando credenciales y actualizando la ficha en MySQL...
            </div>
        </div>
        <div id="simulacionPorcentaje" style="font-size:1.5rem; font-weight:800; color:#059669; font-family:monospace;">
            0%
        </div>
    </div>

    <!-- Barra de progreso -->
    <div style="height:0.75rem; width:100%; background:var(--muted); border-radius:1rem; overflow:hidden; margin-bottom:1.5rem;">
        <div id="progressBar" style="height:100%; width:0%; background:linear-gradient(90deg, #10b981, #059669); transition:width 0.3s ease; border-radius:1rem;"></div>
    </div>

    <!-- Lista de pasos en tiempo real -->
    <div id="contenedorPasos" style="display:flex; flex-direction:column; gap:0.75rem;">
        <div class="paso-item" id="paso-1" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 1: Validación y sanitización de registros de aprendices desde el archivo</span>
        </div>
        <div class="paso-item" id="paso-2" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 2: Asignación de valores <code>NULL</code> a campos opcionales vacíos (teléfono, dirección)</span>
        </div>
        <div class="paso-item" id="paso-3" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 3: Cifrado BCRYPT de la contraseña predeterminada (<code>sena2025</code>)</span>
        </div>
        <div class="paso-item" id="paso-4" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 4: Comprobación de duplicados y asociación con Ficha #<span class="spanFichaPaso"><?= $idFichaDefault ?></span></span>
        </div>
        <div class="paso-item" id="paso-5" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 5: Inserción en <code>usuario</code> (rol Aprendiz) y <code>aprendiz</code> (RFID en NULL)</span>
        </div>
        <div class="paso-item" id="paso-6" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; border-radius:var(--radius-md); background:var(--muted); transition:all 0.3s ease;">
            <i class="bi bi-circle text-muted" style="font-size:1rem;"></i>
            <span style="font-size:0.875rem; font-weight:500;">Paso 6: Consolidación y verificación de integridad transaccional</span>
        </div>
    </div>
</div>

<!-- ── PANEL DE RESULTADOS TRAS LA IMPORTACIÓN ────────────────────────── -->
<div id="panelResultados" class="shadcn-card" style="display:none; padding:1.75rem; margin-bottom:2rem; border-top:4px solid #059669;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                <span class="shadcn-badge badge-activo" style="padding:0.25rem 0.625rem; font-size:0.75rem;">
                    <i class="bi bi-check2-all me-1"></i>Importación Exitosa
                </span>
                <span style="font-size:0.8125rem; color:var(--muted-foreground);">Ficha Destino: <strong id="resFichaId"><?= $idFichaDefault ?></strong></span>
            </div>
            <h2 style="font-size:1.375rem; font-weight:800; margin:0; color:var(--foreground);">Resumen de Aprendices Procesados</h2>
            <p id="resMensajeGeneral" style="font-size:0.875rem; color:var(--muted-foreground); margin:0.25rem 0 0 0;"></p>
        </div>

        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button type="button" class="btn-shadcn btn-shadcn-outline" onclick="reiniciarEscaneo()">
                <i class="bi bi-arrow-repeat me-1"></i>Importar Otro Archivo
            </button>
            <a id="btnVerAprendicesResultado" href="index.php?action=usuarios&ficha_id=<?= $idFichaDefault ?>" class="btn-shadcn btn-shadcn-primary">
                <i class="bi bi-people-fill me-1"></i>Ver Aprendices de la Ficha
            </a>
            <?php if ($esAdmin): ?>
            <a id="btnAsignarRfidResultado" href="index.php?action=usuarios&ficha_id=<?= $idFichaDefault ?>" class="btn-shadcn btn-shadcn-outline" style="color:#d97706; border-color:rgba(245,158,11,0.4);" title="Asignar tarjetas RFID a los aprendices recién registrados">
                <i class="bi bi-upc-scan me-1"></i>Asignar Tarjetas RFID
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Métricas estadísticas -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:var(--muted); padding:1rem; border-radius:var(--radius-md); text-align:center;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Total Procesados</div>
            <div id="statTotal" style="font-size:1.75rem; font-weight:800; color:var(--foreground); font-family:monospace;">0</div>
        </div>
        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); padding:1rem; border-radius:var(--radius-md); text-align:center;">
            <div style="font-size:0.75rem; color:#059669; text-transform:uppercase; font-weight:600;">Nuevos Creados</div>
            <div id="statNuevos" style="font-size:1.75rem; font-weight:800; color:#059669; font-family:monospace;">0</div>
            <div style="font-size:0.7rem; color:var(--muted-foreground);">clave: sena2025</div>
        </div>
        <div style="background:var(--muted); padding:1rem; border-radius:var(--radius-md); text-align:center;">
            <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Ya Existían (Omitidos)</div>
            <div id="statExistentes" style="font-size:1.75rem; font-weight:800; color:var(--foreground); font-family:monospace;">0</div>
        </div>
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); padding:1rem; border-radius:var(--radius-md); text-align:center;">
            <div style="font-size:0.75rem; color:#d97706; text-transform:uppercase; font-weight:600;">Con Campos NULL</div>
            <div id="statNulls" style="font-size:1.75rem; font-weight:800; color:#d97706; font-family:monospace;">0</div>
            <div style="font-size:0.7rem; color:var(--muted-foreground);">teléfono / dirección</div>
        </div>
        <div style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); padding:1rem; border-radius:var(--radius-md); text-align:center;">
            <div style="font-size:0.75rem; color:#dc2626; text-transform:uppercase; font-weight:600;">Incompletos (Omitidos)</div>
            <div id="statIncompletos" style="font-size:1.75rem; font-weight:800; color:#dc2626; font-family:monospace;">0</div>
        </div>
    </div>

    <!-- Detalle fila por fila de la importación -->
    <div style="margin-top:1.5rem;">
        <h4 style="font-size:0.9375rem; font-weight:700; color:var(--foreground); margin-bottom:0.75rem;">
            <i class="bi bi-list-check me-1" style="color:#059669;"></i>Detalle de Registro y Credenciales
        </h4>
        <div class="shadcn-table-wrapper" style="max-height:400px; overflow-y:auto; border:1px solid var(--border); border-radius:var(--radius-md);">
            <table class="shadcn-table" style="font-size:0.8125rem;">
                <thead style="position:sticky; top:0; background:var(--muted); z-index:2;">
                    <tr>
                        <th>#</th>
                        <th>Documento</th>
                        <th>Aprendiz</th>
                        <th>Usuario (Correo)</th>
                        <th>Clave Predeterminada</th>
                        <th>RFID</th>
                        <th>Estado / Nota</th>
                    </tr>
                </thead>
                <tbody id="tablaDetallesResultados">
                    <!-- Filas inyectadas -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── SCRIPTS DEL ESCÁNER DE APRENDICES ─────────────────────────────── -->
<script>
let aprendicesDetectados = [];
let archivoActualNombre = '';

function actualizarFichaDestino(id) {
    document.querySelectorAll('.spanFichaPaso').forEach(el => el.textContent = id);
    const btnVer = document.getElementById('btnVerAprendicesResultado');
    if (btnVer) btnVer.href = 'index.php?action=usuarios&ficha_id=' + id;
    const btnRfid = document.getElementById('btnAsignarRfidResultado');
    if (btnRfid) btnRfid.href = 'index.php?action=usuarios&ficha_id=' + id;
}

// ── MANEJO DE DRAG & DROP ─────────────────────────────────────────────
function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('dropzone').style.borderColor = '#059669';
    document.getElementById('dropzone').style.background = 'rgba(5,150,105,0.06)';
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
    document.getElementById('dropzone').style.borderColor = 'var(--border)';
    document.getElementById('dropzone').style.background = 'var(--muted)';
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        procesarArchivoLocal(e.dataTransfer.files[0]);
    }
}

function handleFileSelect(input) {
    if (input.files && input.files.length > 0) {
        procesarArchivoLocal(input.files[0]);
    }
}

function removerArchivoSeleccionado(e) {
    e.stopPropagation();
    document.getElementById('archivoExcelInput').value = '';
    document.getElementById('fileInfoCard').style.display = 'none';
    cancelarPrevisualizacion();
}

function cancelarPrevisualizacion() {
    aprendicesDetectados = [];
    document.getElementById('panelPrevisualizacion').style.display = 'none';
    document.getElementById('alertaColumnasFaltantes').style.display = 'none';
    document.getElementById('fileInfoCard').style.display = 'none';
}

// ── CARGAR ARCHIVO DESDE LA CARPETA DEL SERVIDOR ─────────────────────
async function cargarArchivoServidor(rutaRelativa, nombreArchivo) {
    Swal.fire({
        title: 'Cargando archivo del servidor...',
        text: nombreArchivo,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await fetch(rutaRelativa);
        if (!response.ok) {
            throw new Error('No se pudo acceder al archivo en el servidor (' + response.status + ')');
        }
        const arrayBuffer = await response.arrayBuffer();
        Swal.close();
        procesarBufferExcel(arrayBuffer, nombreArchivo);
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Error al abrir archivo',
            text: err.message || 'No se pudo leer el archivo desde el servidor.'
        });
    }
}

// ── PROCESAR ARCHIVO LOCAL SELECCIONADO POR EL USUARIO ────────────────
function procesarArchivoLocal(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xls', 'xlsx', 'csv'].includes(ext)) {
        Swal.fire({
            icon: 'error',
            title: 'Formato no soportado',
            text: 'Por favor selecciona un archivo con extensión .xls, .xlsx o .csv'
        });
        return;
    }

    document.getElementById('fileNameDisplay').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('fileInfoCard').style.display = 'flex';

    const reader = new FileReader();
    reader.onload = function(e) {
        procesarBufferExcel(e.target.result, file.name);
    };
    reader.readAsArrayBuffer(file);
}

// ── PARSEO DE SHEETJS Y DETECCIÓN INTELIGENTE DE COLUMNAS ─────────────
function procesarBufferExcel(buffer, nombreArchivo) {
    archivoActualNombre = nombreArchivo;
    try {
        const workbook = XLSX.read(buffer, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        // Convertir hoja a JSON crudo (array de arrays) para ubicar fila de cabeceras
        const rawRows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
        if (!rawRows || rawRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Archivo Vacío',
                text: 'El archivo Excel no contiene filas o datos legibles.'
            });
            return;
        }

        // Buscar fila que contenga cabeceras como 'identificacion', 'documento', 'nombre'
        let headerRowIndex = -1;
        let colMap = {};

        for (let r = 0; r < Math.min(rawRows.length, 10); r++) {
            const row = rawRows[r].map(c => String(c).trim().toLowerCase());
            const hasIdent = row.some(c => c.includes('identifica') || c.includes('documento') || c === 'cc' || c === 'ti');
            const hasNombre = row.some(c => c.includes('nombre'));
            if (hasIdent && hasNombre) {
                headerRowIndex = r;
                // Mapear columnas
                row.forEach((colName, idx) => {
                    if (colName.includes('identifica') || colName.includes('documento') || colName === 'cc' || colName === 'ti') {
                        if (!colMap.identificacion) colMap.identificacion = idx;
                    } else if (colName.includes('apellido')) {
                        colMap.apellidos = idx;
                    } else if (colName.includes('nombre')) {
                        colMap.nombres = idx;
                    } else if (colName.includes('correo') || colName.includes('email') || colName.includes('e-mail')) {
                        colMap.correo = idx;
                    } else if (colName.includes('telefono') || colName.includes('teléfono') || colName.includes('celular') || colName.includes('movil')) {
                        colMap.telefono = idx;
                    } else if (colName.includes('direccion') || colName.includes('dirección') || colName.includes('domicilio')) {
                        colMap.direccion = idx;
                    }
                });
                break;
            }
        }

        // Si no se encontró por fila especial, asumir la primera fila con nombres
        if (headerRowIndex === -1) {
            headerRowIndex = 0;
            const row0 = rawRows[0].map(c => String(c).trim().toLowerCase());
            row0.forEach((colName, idx) => {
                if (colName.includes('identifica') || colName.includes('documento')) colMap.identificacion = idx;
                if (colName.includes('nombre')) colMap.nombres = idx;
                if (colName.includes('apellido')) colMap.apellidos = idx;
                if (colName.includes('correo') || colName.includes('email')) colMap.correo = idx;
                if (colName.includes('telefono') || colName.includes('celular')) colMap.telefono = idx;
                if (colName.includes('direccion')) colMap.direccion = idx;
            });
        }

        // Validar si hacen falta columnas obligatorias
        const faltantes = [];
        if (colMap.identificacion === undefined) faltantes.push('Número de Identificación / Documento');
        if (colMap.nombres === undefined) faltantes.push('Nombres');
        if (colMap.apellidos === undefined && colMap.nombres === undefined) faltantes.push('Apellidos');
        if (colMap.correo === undefined) faltantes.push('Correo electrónico');

        const alertaDiv = document.getElementById('alertaColumnasFaltantes');
        if (faltantes.length > 0) {
            alertaDiv.style.display = 'block';
            document.getElementById('mensajeColumnasFaltantes').innerHTML = 
                'No se pudieron identificar automáticamente las siguientes columnas esenciales: <br><strong style="color:#dc2626;">' + 
                faltantes.join(', ') + '</strong>.<br>Por favor verifica que la hoja de cálculo incluya estas columnas con sus respectivos encabezados.';
        } else {
            alertaDiv.style.display = 'none';
        }

        // Extraer los aprendices
        aprendicesDetectados = [];
        let contNulls = 0;
        let contIncompletos = 0;
        let contListos = 0;

        for (let r = headerRowIndex + 1; r < rawRows.length; r++) {
            const row = rawRows[r];
            if (!row || row.length === 0) continue;

            // Extraer valores o dejar en null
            const rawIdent = colMap.identificacion !== undefined ? String(row[colMap.identificacion] || '').trim() : '';
            const rawNombres = colMap.nombres !== undefined ? String(row[colMap.nombres] || '').trim() : '';
            const rawApellidos = colMap.apellidos !== undefined ? String(row[colMap.apellidos] || '').trim() : '';
            const rawCorreo = colMap.correo !== undefined ? String(row[colMap.correo] || '').trim().toLowerCase() : '';
            const rawTel = colMap.telefono !== undefined ? String(row[colMap.telefono] || '').trim() : '';
            const rawDir = colMap.direccion !== undefined ? String(row[colMap.direccion] || '').trim() : '';

            // Si toda la fila está vacía, ignorarla
            if (!rawIdent && !rawNombres && !rawApellidos && !rawCorreo) continue;

            const telefonoVal = rawTel !== '' ? rawTel : null;
            const direccionVal = rawDir !== '' ? rawDir : null;

            const tieneNulls = (telefonoVal === null || direccionVal === null);
            const esIncompleto = (!rawIdent || !rawNombres || !rawCorreo);

            if (esIncompleto) {
                contIncompletos++;
            } else {
                contListos++;
            }
            if (tieneNulls) contNulls++;

            aprendicesDetectados.push({
                identificacion: rawIdent,
                nombres: rawNombres,
                apellidos: rawApellidos,
                correo: rawCorreo,
                telefono: telefonoVal,
                direccion: direccionVal,
                esIncompleto: esIncompleto,
                tieneNulls: tieneNulls
            });
        }

        renderizarPrevisualizacion(contListos, contNulls, contIncompletos);

    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Error al procesar Excel',
            text: 'No se pudo leer la estructura del archivo. ' + err.message
        });
    }
}

// ── RENDERIZAR TABLA DE PREVISUALIZACIÓN ──────────────────────────────
function renderizarPrevisualizacion(listos, nulls, incompletos) {
    document.getElementById('chipTotal').textContent = aprendicesDetectados.length;
    document.getElementById('chipListos').textContent = listos;
    document.getElementById('chipNulls').textContent = nulls;
    document.getElementById('chipIncompletos').textContent = incompletos;

    const tbody = document.getElementById('tablaCuerpoPrevisualizacion');
    tbody.innerHTML = '';

    aprendicesDetectados.forEach((a, idx) => {
        const tr = document.createElement('tr');

        const telHtml = a.telefono 
            ? `<span>${a.telefono}</span>` 
            : `<span class="badge-null" title="Campo vacío: Se registrará como NULL en la base de datos">NULL</span>`;

        const dirHtml = a.direccion 
            ? `<span>${a.direccion}</span>` 
            : `<span class="badge-null" title="Campo vacío: Se registrará como NULL en la base de datos">NULL</span>`;

        let estadoHtml = '';
        if (a.esIncompleto) {
            estadoHtml = `<span class="badge-error"><i class="bi bi-x-circle me-1"></i>Incompleto</span>`;
        } else if (a.tieneNulls) {
            estadoHtml = `<span class="badge-ready"><i class="bi bi-check2 me-1"></i>Listo</span> <span class="badge-null" style="font-size:0.6875rem;">(NULLs)</span>`;
        } else {
            estadoHtml = `<span class="badge-ready"><i class="bi bi-check-circle-fill me-1"></i>Listo</span>`;
        }

        const nombreCompleto = (a.nombres + ' ' + a.apellidos).trim() || '—';

        tr.innerHTML = `
            <td style="color:var(--muted-foreground);">${idx + 1}</td>
            <td style="font-weight:700; color:var(--foreground);">${a.identificacion || '<span class="text-danger">—</span>'}</td>
            <td><strong>${nombreCompleto}</strong></td>
            <td style="font-family:monospace; color:var(--foreground);">${a.correo || '<span class="text-danger">—</span>'}</td>
            <td>${telHtml}</td>
            <td>${dirHtml}</td>
            <td>${estadoHtml}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('panelPrevisualizacion').style.display = 'block';
    document.getElementById('panelPrevisualizacion').scrollIntoView({ behavior: 'smooth' });
}

// ── EJECUTAR IMPORTACIÓN CON ANIMACIÓN DE 6 PASOS ─────────────────────
function activarPaso(numero, porcentaje) {
    return new Promise(resolve => {
        setTimeout(() => {
            const paso = document.getElementById('paso-' + numero);
            if (paso) {
                paso.style.background = 'rgba(5, 150, 105, 0.12)';
                paso.style.borderLeft = '3px solid #059669';
                const icon = paso.querySelector('i');
                if (icon) {
                    icon.className = 'bi bi-arrow-repeat text-success';
                    icon.style.animation = 'spin 0.8s linear infinite';
                    icon.style.display = 'inline-block';
                }
            }
            document.getElementById('progressBar').style.width = porcentaje + '%';
            document.getElementById('simulacionPorcentaje').textContent = porcentaje + '%';
            resolve();
        }, 300);
    });
}

function completarPaso(numero) {
    const paso = document.getElementById('paso-' + numero);
    if (paso) {
        paso.style.background = 'rgba(16, 185, 129, 0.08)';
        paso.style.borderLeft = '3px solid #10b981';
        const icon = paso.querySelector('i');
        if (icon) {
            icon.className = 'bi bi-check-circle-fill';
            icon.style.color = '#059669';
            icon.style.animation = 'none';
        }
    }
}

async function ejecutarImportacion() {
    if (!aprendicesDetectados || aprendicesDetectados.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'No hay aprendices válidos para importar.'
        });
        return;
    }

    const idFicha = parseInt(document.getElementById('selectFichaDestino').value) || <?= $idFichaDefault ?>;

    // Ocultar previsualización y mostrar simulación en tiempo real
    document.getElementById('panelPrevisualizacion').style.display = 'none';
    const panelSim = document.getElementById('panelSimulacion');
    panelSim.style.display = 'block';
    panelSim.scrollIntoView({ behavior: 'smooth' });

    // Iniciar animación de pasos
    await activarPaso(1, 15);
    completarPaso(1);

    await activarPaso(2, 35);
    completarPaso(2);

    await activarPaso(3, 50);

    try {
        const response = await fetch('index.php?action=ficha-aprendices-importar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                ficha_id: idFicha,
                aprendices: aprendicesDetectados
            })
        });

        const data = await response.json();

        completarPaso(3);
        await activarPaso(4, 70);
        completarPaso(4);

        await activarPaso(5, 88);
        completarPaso(5);

        await activarPaso(6, 100);
        completarPaso(6);

        setTimeout(() => {
            if (data.exito) {
                mostrarResultados(data, idFicha);
            } else {
                panelSim.style.display = 'none';
                Swal.fire({
                    icon: 'error',
                    title: 'Error al importar',
                    text: data.mensaje || 'Ocurrió un error al procesar los aprendices.'
                });
            }
        }, 500);

    } catch (err) {
        console.error(err);
        panelSim.style.display = 'none';
        Swal.fire({
            icon: 'error',
            title: 'Error de Red / Comunicación',
            text: 'No se pudo comunicar con el servidor para registrar los aprendices.'
        });
    }
}

// ── MOSTRAR RESULTADOS TRAS EL REGISTRO ────────────────────────────────
function mostrarResultados(data, idFicha) {
    document.getElementById('panelSimulacion').style.display = 'none';
    const panelRes = document.getElementById('panelResultados');
    panelRes.style.display = 'block';
    panelRes.scrollIntoView({ behavior: 'smooth' });

    document.getElementById('resFichaId').textContent = idFicha;
    document.getElementById('resMensajeGeneral').textContent = data.mensaje;

    document.getElementById('statTotal').textContent = data.total_procesados || 0;
    document.getElementById('statNuevos').textContent = data.nuevos_creados || 0;
    document.getElementById('statExistentes').textContent = data.existentes_omitidos || 0;
    document.getElementById('statNulls').textContent = data.con_datos_null || 0;
    document.getElementById('statIncompletos').textContent = data.incompletos_omitidos || 0;

    const btnVer = document.getElementById('btnVerAprendicesResultado');
    if (btnVer) btnVer.href = 'index.php?action=usuarios&ficha_id=' + idFicha;
    const btnRfid = document.getElementById('btnAsignarRfidResultado');
    if (btnRfid) btnRfid.href = 'index.php?action=usuarios&ficha_id=' + idFicha;

    const tbody = document.getElementById('tablaDetallesResultados');
    tbody.innerHTML = '';

    (data.detalles || []).forEach(d => {
        const tr = document.createElement('tr');
        
        let badgeEstado = '';
        if (d.estado === 'Creado') {
            badgeEstado = `<span class="badge-ready"><i class="bi bi-person-plus-fill me-1"></i>Perfil Creado</span>`;
        } else if (d.estado === 'Existente') {
            badgeEstado = `<span class="shadcn-badge badge-secondary"><i class="bi bi-info-circle me-1"></i>Existente</span>`;
        } else if (d.estado === 'Actualizado' || d.estado === 'Vinculado') {
            badgeEstado = `<span class="badge-ready"><i class="bi bi-arrow-repeat me-1"></i>${d.estado}</span>`;
        } else {
            badgeEstado = `<span class="badge-error"><i class="bi bi-x-circle me-1"></i>${d.estado}</span>`;
        }

        const telBadge = d.telefono ? d.telefono : '<span class="badge-null">NULL</span>';
        const dirBadge = d.direccion ? d.direccion : '<span class="badge-null">NULL</span>';

        tr.innerHTML = `
            <td style="color:var(--muted-foreground);">${d.fila}</td>
            <td style="font-weight:700;">${d.identificacion}</td>
            <td><strong>${d.nombre_completo}</strong></td>
            <td style="font-family:monospace;">${d.correo}</td>
            <td><code style="background:var(--muted); padding:0.15rem 0.35rem; border-radius:0.25rem; color:#059669; font-weight:700;">sena2025</code></td>
            <td><span class="badge-null" style="color:#d97706; border-color:rgba(245,158,11,0.4);" title="Solo el Administrador ingresa el RFID">NULL (Pendiente)</span></td>
            <td>
                ${badgeEstado}
                <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:2px;">${d.mensaje}</div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    Swal.fire({
        icon: 'success',
        title: '¡Aprendices Registrados con Éxito!',
        html: `<div style="text-align:left; font-size:0.9rem; line-height:1.5;">
            <p>Se procesaron <strong>${data.total_procesados}</strong> registros en la <strong>Ficha ${idFicha}</strong>.</p>
            <ul style="margin:0; padding-left:1.25rem;">
                <li><strong>${data.nuevos_creados}</strong> perfiles nuevos creados con contraseña predeterminada <code>sena2025</code>.</li>
                <li><strong>${data.existentes_omitidos}</strong> aprendices ya estaban vinculados (omitidos para evitar duplicados).</li>
                <li><strong>${data.con_datos_null}</strong> registros tenían campos opcionales vacíos y se guardaron como <code>NULL</code>.</li>
                <li>Tarjetas RFID configuradas en <code>NULL</code> para que el Administrador las asigne manualmente.</li>
            </ul>
        </div>`,
        confirmButtonColor: '#059669',
        confirmButtonText: '<i class="bi bi-people-fill me-1"></i>Ver Aprendices en la Ficha'
    }).then(res => {
        if (res.isConfirmed) {
            window.location.href = 'index.php?action=usuarios&ficha_id=' + idFicha;
        }
    });
}

function reiniciarEscaneo() {
    aprendicesDetectados = [];
    document.getElementById('panelResultados').style.display = 'none';
    document.getElementById('panelPrevisualizacion').style.display = 'none';
    document.getElementById('fileInfoCard').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
