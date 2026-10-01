<?php
$ficha = $ficha ?? [];
$id = (int)($id ?? $ficha['id'] ?? $_GET['id'] ?? 3234082);
$pageTitle = 'Horario de Formación (6am a 10pm) — Ficha ' . ($ficha['numero_ficha'] ?? $id);
require __DIR__ . '/../../views/layouts/header.php';

$bloques = $bloques ?? [];
$todosLosBloques = $todosLosBloques ?? $bloques ?? [];
$bloquesJson = $bloquesJson ?? json_encode($todosLosBloques, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$instructoresFicha = $instructoresFicha ?? [];
$mesesDisponibles = $mesesDisponibles ?? [];
$todasFichas = $todasFichas ?? [];
$mesFiltro = $mesFiltro ?? '';

// Contadores rápidos
$totalHorasSemestre = count($todosLosBloques) * 3;
?>

<style>
/* ─── ESTILOS CALENDARIO ESTILO GOOGLE CALENDAR (6 AM A 10 PM) ─── */
.gcal-container {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 2rem;
}

/* Barra superior de control */
.gcal-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    background: var(--card);
    flex-wrap: wrap;
    gap: 1rem;
}

.gcal-nav-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.gcal-btn-circle {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    border: 1px solid var(--border);
    background: var(--background);
    color: var(--foreground);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.9375rem;
    transition: all 0.15s ease;
}
.gcal-btn-circle:hover {
    background: var(--muted);
    border-color: var(--sena-brand);
    color: var(--sena-brand);
}

.gcal-btn-today {
    padding: 0.4rem 0.875rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: var(--background);
    color: var(--foreground);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.gcal-btn-today:hover {
    background: var(--muted);
    border-color: var(--primary);
}

.gcal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin-left: 0.5rem;
    min-width: 220px;
}

/* Conmutador de vistas (Semana, Mes, Día, Agenda) */
.gcal-view-switcher {
    display: flex;
    background: var(--muted);
    padding: 0.25rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    gap: 0.25rem;
}

.gcal-view-btn {
    border: none;
    background: transparent;
    padding: 0.4rem 0.875rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--muted-foreground);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.15s ease;
}
.gcal-view-btn.active {
    background: var(--background);
    color: var(--foreground);
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.gcal-view-btn:hover:not(.active) {
    color: var(--foreground);
}

/* ─── VISTA MES ─── */
.gcal-month-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-bottom: 1px solid var(--border);
}
.gcal-month-header {
    background: var(--muted);
    padding: 0.625rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-foreground);
    border-right: 1px solid var(--border);
}
.gcal-month-header:last-child {
    border-right: none;
}

.gcal-month-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: var(--border);
    gap: 1px;
}
.gcal-day-cell {
    background: var(--card);
    min-height: 120px;
    padding: 0.375rem 0.5rem;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: background 0.15s ease;
}
.gcal-day-cell.other-month {
    background: rgba(0, 0, 0, 0.03);
    opacity: 0.4;
}
.gcal-day-cell.is-today {
    background: rgba(5, 150, 105, 0.06);
}

.gcal-day-num {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted-foreground);
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-bottom: 0.25rem;
}
.gcal-day-cell.is-today .gcal-day-num {
    background: var(--sena-brand);
    color: #fff;
}

.gcal-events-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    overflow: hidden;
}

.gcal-event-pill {
    padding: 0.25rem 0.45rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
    border-left: 3px solid transparent;
}
.gcal-event-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* Colores de materias en Tema Claro */
.ev-tecnica {
    background: #ecfdf5;
    color: #065f46;
    border-left-color: #059669;
}
.ev-bilinguismo {
    background: #eff6ff;
    color: #1e40af;
    border-left-color: #2563eb;
}
.ev-transversal {
    background: #fef3c7;
    color: #92400e;
    border-left-color: #d97706;
}
.ev-social {
    background: #f3e8ff;
    color: #6b21a8;
    border-left-color: #8b5cf6;
}

/* Colores de materias en Tema Oscuro (Modo Dark SENA) */
[data-theme="dark"] .ev-tecnica {
    background: rgba(5, 150, 105, 0.22);
    color: #6ee7b7;
    border-left-color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.35);
}
[data-theme="dark"] .ev-bilinguismo {
    background: rgba(37, 99, 235, 0.22);
    color: #93c5fd;
    border-left-color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.35);
}
[data-theme="dark"] .ev-transversal {
    background: rgba(217, 119, 6, 0.22);
    color: #fcd34d;
    border-left-color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.35);
}
[data-theme="dark"] .ev-social {
    background: rgba(139, 92, 246, 0.22);
    color: #c4b5fd;
    border-left-color: #8b5cf6;
    border: 1px solid rgba(139, 92, 246, 0.35);
}

/* ─── VISTA SEMANA (HORAS 6:00 AM A 10:00 PM) ─── */
.gcal-week-container {
    display: flex;
    flex-direction: column;
    overflow-x: auto;
    max-height: 760px;
    overflow-y: auto;
    position: relative;
}

.gcal-week-header-row {
    display: grid;
    grid-template-columns: 80px repeat(7, minmax(130px, 1fr));
    border-bottom: 1px solid var(--border);
    background: var(--muted);
    position: sticky;
    top: 0;
    z-index: 20;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.gcal-week-header-col {
    padding: 0.625rem 0.25rem;
    text-align: center;
    border-right: 1px solid var(--border);
    font-size: 0.75rem;
}
.gcal-week-header-col:last-child {
    border-right: none;
}
.gcal-week-day-title {
    font-weight: 700;
    color: var(--muted-foreground);
    text-transform: uppercase;
    font-size: 0.6875rem;
}
.gcal-week-day-circle {
    font-size: 1.125rem;
    font-weight: 800;
    color: var(--foreground);
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    margin: 0.125rem auto 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gcal-week-header-col.is-today .gcal-week-day-circle {
    background: var(--sena-brand);
    color: #fff;
}

.gcal-week-body {
    display: grid;
    grid-template-columns: 80px repeat(7, minmax(130px, 1fr));
    position: relative;
    /* 17 franjas horarias (6 a 22) * 56px = 952px */
    height: 952px;
}
.gcal-time-col {
    border-right: 1px solid var(--border);
    background: var(--background);
    user-select: none;
}
.gcal-time-slot-label {
    height: 56px;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-align: right;
    padding-right: 0.5rem;
    transform: translateY(-8px);
    box-sizing: border-box;
}

.gcal-week-day-col {
    border-right: 1px solid var(--border);
    position: relative;
    background: var(--card);
}
.gcal-week-day-col:last-child {
    border-right: none;
}
.gcal-grid-hour-line {
    height: 56px;
    border-bottom: 1px dashed rgba(125, 125, 125, 0.18);
    box-sizing: border-box;
}

/* Tarjeta de bloque horario con horas visibles */
.gcal-week-card {
    position: absolute;
    left: 4px;
    right: 4px;
    border-radius: 6px;
    padding: 0.4rem 0.55rem;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.15s ease;
    border-left: 3px solid;
    z-index: 5;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.gcal-week-card:hover {
    z-index: 15;
    box-shadow: 0 6px 16px rgba(0,0,0,0.22);
    transform: translateY(-1px);
}

/* ─── VISTA DÍA (HORAS 6:00 AM A 10:00 PM) ─── */
.gcal-day-container {
    display: grid;
    grid-template-columns: 90px 1fr;
    position: relative;
    height: 952px;
    max-height: 760px;
    overflow-y: auto;
}

/* ─── MODAL DETALLES DEL EVENTO ─── */
.gcal-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.gcal-modal {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: gcalModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes gcalModalIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.gcal-modal-header-strip {
    height: 8px;
    background: #059669;
}
.gcal-modal-body {
    padding: 1.5rem;
}

/* Barra de referencia rápida de franjas horarias */
.jornada-badge-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    background: var(--muted);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 0.5rem 0.875rem;
    margin-bottom: 1rem;
    font-size: 0.8125rem;
}
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <a href="index.php?action=fichas" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem 0.5rem; font-size:0.8125rem;">
                <i class="bi bi-arrow-left me-1"></i>Fichas
            </a>
            <span style="color:var(--muted-foreground);">/</span>
            <span style="font-size:0.875rem; color:var(--muted-foreground);">Horario de Formación</span>
        </div>
        <h1 class="page-header-title" style="display:flex; align-items:center; gap:0.625rem;">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(5,150,105,0.12); color:#059669;">
                <i class="bi bi-calendar3" style="font-size:1.375rem;"></i>
            </span>
            Horario Oficial de Formación — Ficha <?= htmlspecialchars($ficha['numero_ficha'] ?? $id) ?>
        </h1>
        <div class="page-header-subtitle">
            Programa: <strong><?= htmlspecialchars($ficha['programa'] ?? 'ADSO') ?></strong> — Jornadas: <strong>Mañana, Tarde y Noche (06:00 am – 10:00 pm)</strong>
        </div>
    </div>

    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <a href="index.php?action=ficha-horario-importar&ficha_id=<?= $id ?>" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Escanear / Importar Excel
        </a>
        <a href="index.php?action=reporte-horario-pdf&ficha_id=<?= $id ?>" target="_blank" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-printer me-1"></i>Imprimir PDF
        </a>
        <a href="index.php?action=reporte-horario-excel&ficha_id=<?= $id ?>" class="btn-shadcn btn-shadcn-outline" style="color:#059669;">
            <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel (CSV)
        </a>
    </div>
</div>

<!-- ── SELECTOR DE FICHA Y FILTRO POR INSTRUCTOR ───────────────────── -->
<div class="shadcn-card" style="margin-bottom:1rem; padding:1.25rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        
        <!-- Cambiar de ficha -->
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <label style="font-size:0.875rem; font-weight:600; color:var(--foreground); white-space:nowrap;">
                <i class="bi bi-journal-bookmark me-1" style="color:#059669;"></i>Ficha:
            </label>
            <select class="shadcn-select" style="min-width:280px; font-weight:600;" onchange="window.location.href='index.php?action=ficha-horario&id=' + this.value">
                <?php foreach ($todasFichas as $tf): ?>
                    <option value="<?= $tf['id'] ?>" <?= ((int)$tf['id'] === (int)$id) ? 'selected' : '' ?>>
                        Ficha <?= htmlspecialchars($tf['numero_ficha']) ?> (<?= htmlspecialchars($tf['programa']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro Rápido por Instructor en el Calendario -->
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <label style="font-size:0.8125rem; font-weight:600; color:var(--muted-foreground); white-space:nowrap;">
                <i class="bi bi-funnel me-1"></i>Instructor:
            </label>
            <select id="filtroInstructorCal" class="shadcn-select" style="min-width:220px;" onchange="alCambiarFiltroInstructor(this.value)">
                <option value="">— Todos los Instructores —</option>
                <?php foreach ($instructoresFicha as $inst): ?>
                    <option value="<?= htmlspecialchars($inst['nombre_completo']) ?>">
                        <?= htmlspecialchars($inst['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Leyenda de colores -->
        <div style="display:flex; gap:0.75rem; align-items:center; font-size:0.75rem; font-weight:600; flex-wrap:wrap;">
            <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#059669;"></span> Técnica</span>
            <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#2563eb;"></span> Bilingüismo</span>
            <span style="display:flex; align-items:center; gap:0.3rem;"><span style="width:10px; height:10px; border-radius:50%; background:#d97706;"></span> Transversal</span>
        </div>

    </div>
</div>

<!-- ── BARRA GUÍA DE JORNADAS Y BLOQUES (6 AM A 10 PM) ──────────────── -->
<div class="jornada-badge-bar">
    <div style="font-weight:700; color:var(--foreground); display:flex; align-items:center; gap:0.35rem;">
        <i class="bi bi-clock-history text-primary" style="color:var(--sena-brand);"></i>
        <span>Franjas Horarias SENA:</span>
    </div>
    <span class="shadcn-badge" style="background:rgba(5,150,105,0.12); color:#059669; font-weight:600;">
        🌅 Bloque 1: 06:00 am – 09:00 am
    </span>
    <span class="shadcn-badge" style="background:rgba(37,99,235,0.12); color:#2563eb; font-weight:600;">
        ☀️ Bloque 2: 09:00 am – 12:00 pm
    </span>
    <span class="shadcn-badge" style="background:rgba(217,119,6,0.12); color:#d97706; font-weight:600;">
        🌤️ Tarde: 12:00 pm – 06:00 pm
    </span>
    <span class="shadcn-badge" style="background:rgba(139,92,246,0.12); color:#8b5cf6; font-weight:600;">
        🌙 Noche: 06:00 pm – 10:00 pm
    </span>
</div>

<!-- ── COMPONENTE PRINCIPAL GOOGLE CALENDAR ─────────────────────────── -->
<div class="gcal-container">
    
    <!-- Barra Superior / Toolbar estilo Google Calendar -->
    <div class="gcal-toolbar">
        <div class="gcal-nav-group">
            <button type="button" class="gcal-btn-today" onclick="gcalIrAHoy()">Hoy</button>
            <button type="button" class="gcal-btn-circle" onclick="gcalNavegar(-1)" title="Anterior">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="gcal-btn-circle" onclick="gcalNavegar(1)" title="Siguiente">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div id="gcalTitle" class="gcal-title">Cargando horario...</div>
        </div>

        <!-- Conmutador de Vistas (Semana seleccionada por defecto para ver horas 6am a 10pm) -->
        <div class="gcal-view-switcher">
            <button type="button" class="gcal-view-btn active" id="btnViewSemana" onclick="gcalCambiarVista('semana')">
                <i class="bi bi-columns-gap me-1"></i>Semana (Horario)
            </button>
            <button type="button" class="gcal-view-btn" id="btnViewMes" onclick="gcalCambiarVista('mes')">
                <i class="bi bi-grid-3x3 me-1"></i>Mes
            </button>
            <button type="button" class="gcal-view-btn" id="btnViewDia" onclick="gcalCambiarVista('dia')">
                <i class="bi bi-calendar-day me-1"></i>Día
            </button>
            <button type="button" class="gcal-view-btn" id="btnViewAgenda" onclick="gcalCambiarVista('agenda')">
                <i class="bi bi-card-checklist me-1"></i>Agenda
            </button>
        </div>
    </div>

    <!-- Contenedor dinámico donde se renderizan las vistas -->
    <div id="gcalViewArea" style="position:relative; min-height:550px;">
        <!-- Inyectado por JavaScript -->
    </div>

</div>

<!-- ── MODAL FLOTANTE DE DETALLES DEL EVENTO (ESTILO GOOGLE CALENDAR) ── -->
<div id="gcalModal" class="gcal-modal-backdrop" onclick="gcalCerrarModal(event)">
    <div class="gcal-modal" onclick="event.stopPropagation()">
        <div id="gcalModalStrip" class="gcal-modal-header-strip"></div>
        <div class="gcal-modal-body">
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                <div>
                    <span id="gcalModalBadge" class="shadcn-badge badge-primary" style="margin-bottom:0.5rem; display:inline-block;">Técnica</span>
                    <h2 id="gcalModalMateria" style="font-size:1.25rem; font-weight:700; margin:0; color:var(--foreground); line-height:1.3;">
                        Nombre de la Materia
                    </h2>
                </div>
                <button type="button" onclick="gcalCerrarModal()" style="border:none; background:transparent; font-size:1.25rem; cursor:pointer; color:var(--muted-foreground);">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.875rem; font-size:0.875rem;">
                
                <!-- Fecha y Hora -->
                <div style="display:flex; align-items:center; gap:0.75rem; color:var(--foreground);">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(5,150,105,0.12); color:#059669; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div id="gcalModalFecha" style="font-weight:600;">Lunes, 14 de Julio de 2025</div>
                        <div id="gcalModalHorario" style="color:var(--muted-foreground); font-size:0.8125rem;">06:00 am – 09:00 am (3 horas lectivas) • Bloque 1</div>
                    </div>
                </div>

                <!-- Instructor -->
                <div style="display:flex; align-items:center; gap:0.75rem; color:var(--foreground);">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div id="gcalModalInstructor" style="font-weight:600;">Instructor Responsable</div>
                        <div id="gcalModalCorreo" style="color:var(--muted-foreground); font-size:0.8125rem;">correo@sena.edu.co</div>
                    </div>
                </div>

                <!-- Ficha y Jornada -->
                <div style="display:flex; align-items:center; gap:0.75rem; color:var(--foreground);">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(245,158,11,0.12); color:#d97706; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;">Ficha <?= htmlspecialchars($ficha['numero_ficha'] ?? $id) ?> — <?= htmlspecialchars($ficha['programa'] ?? 'ADSO') ?></div>
                        <div style="color:var(--muted-foreground); font-size:0.8125rem;">Jornada: 06:00 am – 10:00 pm (Según franja asignada)</div>
                    </div>
                </div>

            </div>

            <!-- Acciones del Modal -->
            <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem; border-top:1px solid var(--border); padding-top:1rem;">
                <button type="button" class="btn-shadcn btn-shadcn-ghost" onclick="gcalCerrarModal()">Cerrar</button>
                <a id="gcalModalBtnAsistencia" href="index.php?action=asistencia" class="btn-shadcn btn-shadcn-primary">
                    <i class="bi bi-camera-video me-1"></i>Ir a Toma de Asistencia
                </a>
            </div>

        </div>
    </div>
</div>

<!-- ── SCRIPT MOTOR GOOGLE CALENDAR (6 AM A 10 PM) ──────────────────── -->
<script>
// Datos completos del horario transferidos desde el controlador
const GCAL_BLOQUES = <?= $bloquesJson ?: '[]' ?>;
const ID_FICHA_ACTUAL = <?= (int)$id ?>;

// Estado del Calendario (Semana activada por defecto para ver la grilla horaria 6am a 10pm)
let gcalEstado = {
    vista: 'semana', // 'semana', 'mes', 'dia', 'agenda'
    fechaActual: new Date(),
    filtroInstructor: ''
};

// Determinar fecha inicial inteligente: si hoy no cae dentro de los bloques,
// abrir en la fecha donde hay clases (ej. Julio 2025) para que el horario se vea completo
(function inicializarFechaPorDefecto() {
    if (GCAL_BLOQUES.length > 0) {
        const fechasOrdenadas = GCAL_BLOQUES.map(b => b.fecha).sort();
        const minFecha = fechasOrdenadas[0];
        const maxFecha = fechasOrdenadas[fechasOrdenadas.length - 1];

        const hoyStr = (new Date()).toISOString().split('T')[0];
        if (hoyStr >= minFecha && hoyStr <= maxFecha) {
            gcalEstado.fechaActual = new Date();
        } else {
            const partes = minFecha.split('-');
            gcalEstado.fechaActual = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        }
    }
})();

const NOMBRES_MESES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];
const NOMBRES_DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const NOMBRES_DIAS_CORTOS = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];

// Limpiar nombres largos de materias para que se lean hermosas y claras
function formatearNombreMateria(materia) {
    if (!materia) return 'Formación Profesional';
    let nombre = materia;
    // Quitar boilerplates redundantes
    nombre = nombre.replace(/\s*-\s*PRINCIPAL\s*-\s*CONVERGENTES\s*4/gi, '');
    nombre = nombre.replace(/\s*-\s*CONVERGENTES\s*4/gi, '');
    nombre = nombre.replace(/\s*-\s*PRINCIPAL/gi, '');
    return nombre.trim();
}

// Formatear hora estilo 12h (am / pm)
function formatearHora12(horaStr) {
    if (!horaStr) return '';
    const partes = horaStr.split(':');
    let h = parseInt(partes[0]);
    const m = partes[1] || '00';
    const ampm = h >= 12 ? 'pm' : 'am';
    h = h % 12;
    h = h ? h : 12;
    return `${h < 10 ? '0' : ''}${h}:${m} ${ampm}`;
}

// ─── CLASIFICACIÓN DE COLORES DE MATERIA ───
function obtenerClaseColor(materia) {
    const m = (materia || '').toUpperCase();
    if (m.includes('INGLÉS') || m.includes('INGLES') || m.includes('BILINGÜISMO')) {
        return { css: 'ev-bilinguismo', border: '#2563eb', label: 'Bilingüismo' };
    }
    if (m.includes('AMBIENTAL') || m.includes('SST') || m.includes('DERECHOS') || m.includes('COMUNICACIÓN') || m.includes('ÉTICA')) {
        return { css: 'ev-transversal', border: '#d97706', label: 'Transversal' };
    }
    if (m.includes('SOCIAL')) {
        return { css: 'ev-social', border: '#8b5cf6', label: 'Social' };
    }
    return { css: 'ev-tecnica', border: '#059669', label: 'Técnica' };
}

// ─── FILTRADO DE EVENTOS ───
function obtenerBloquesFiltrados() {
    if (!gcalEstado.filtroInstructor) {
        return GCAL_BLOQUES;
    }
    const q = gcalEstado.filtroInstructor.toLowerCase();
    return GCAL_BLOQUES.filter(b => (b.instructor_nombre || '').toLowerCase().includes(q));
}

// ─── CONTROLADOR DE VISTAS ───
function gcalCambiarVista(nuevaVista) {
    gcalEstado.vista = nuevaVista;

    // Actualizar botones de conmutador
    document.querySelectorAll('.gcal-view-btn').forEach(btn => btn.classList.remove('active'));
    if (nuevaVista === 'semana') document.getElementById('btnViewSemana').classList.add('active');
    else if (nuevaVista === 'mes') document.getElementById('btnViewMes').classList.add('active');
    else if (nuevaVista === 'dia') document.getElementById('btnViewDia').classList.add('active');
    else if (nuevaVista === 'agenda') document.getElementById('btnViewAgenda').classList.add('active');

    gcalRenderizar();
}

function gcalIrAHoy() {
    gcalEstado.fechaActual = new Date();
    gcalRenderizar();
}

function gcalNavegar(direccion) {
    const d = new Date(gcalEstado.fechaActual);
    if (gcalEstado.vista === 'mes') {
        d.setMonth(d.getMonth() + direccion);
    } else if (gcalEstado.vista === 'semana') {
        d.setDate(d.getDate() + (direccion * 7));
    } else if (gcalEstado.vista === 'dia') {
        d.setDate(d.getDate() + direccion);
    } else if (gcalEstado.vista === 'agenda') {
        d.setMonth(d.getMonth() + direccion);
    }
    gcalEstado.fechaActual = d;
    gcalRenderizar();
}

function alCambiarFiltroInstructor(val) {
    gcalEstado.filtroInstructor = val.trim();
    gcalRenderizar();
}

// ─── RENDERIZADOR GENERAL ───
function gcalRenderizar() {
    const area = document.getElementById('gcalViewArea');
    const titleEl = document.getElementById('gcalTitle');

    if (gcalEstado.vista === 'semana') {
        renderizarVistaSemana(area, titleEl);
    } else if (gcalEstado.vista === 'mes') {
        renderizarVistaMes(area, titleEl);
    } else if (gcalEstado.vista === 'dia') {
        renderizarVistaDia(area, titleEl);
    } else if (gcalEstado.vista === 'agenda') {
        renderizarVistaAgenda(area, titleEl);
    }
}

// ─── 1. VISTA SEMANA COMPLETA (6:00 AM A 10:00 PM) ───
function renderizarVistaSemana(area, titleEl) {
    const cur = new Date(gcalEstado.fechaActual);
    let dayOfWeek = cur.getDay() - 1;
    if (dayOfWeek === -1) dayOfWeek = 6;

    const lunesSemana = new Date(cur);
    lunesSemana.setDate(cur.getDate() - dayOfWeek);

    const domingoSemana = new Date(lunesSemana);
    domingoSemana.setDate(lunesSemana.getDate() + 6);

    const mesIni = NOMBRES_MESES[lunesSemana.getMonth()];
    const mesFin = NOMBRES_MESES[domingoSemana.getMonth()];
    if (mesIni === mesFin) {
        titleEl.textContent = `${lunesSemana.getDate()} – ${domingoSemana.getDate()} de ${mesIni}, ${lunesSemana.getFullYear()}`;
    } else {
        titleEl.textContent = `${lunesSemana.getDate()} ${mesIni} – ${domingoSemana.getDate()} ${mesFin}, ${domingoSemana.getFullYear()}`;
    }

    const bloques = obtenerBloquesFiltrados();
    const hoyStr = (new Date()).toISOString().split('T')[0];

    // Días de la semana
    const dias = [];
    const temp = new Date(lunesSemana);
    for (let i = 0; i < 7; i++) {
        dias.push(new Date(temp));
        temp.setDate(temp.getDate() + 1);
    }

    let html = `
        <div class="gcal-week-container">
            <div class="gcal-week-header-row">
                <div style="border-right:1px solid var(--border); padding:0.625rem 0.25rem; text-align:center; font-size:0.6875rem; font-weight:700; color:var(--muted-foreground);">
                    HORARIO
                </div>
    `;

    dias.forEach(d => {
        const fStr = d.toISOString().split('T')[0];
        const esHoy = (fStr === hoyStr);
        html += `
            <div class="gcal-week-header-col ${esHoy ? 'is-today' : ''}">
                <div class="gcal-week-day-title">${NOMBRES_DIAS_CORTOS[d.getDay()]}</div>
                <div class="gcal-week-day-circle">${d.getDate()}</div>
            </div>
        `;
    });

    html += `
            </div>
            <div class="gcal-week-body">
                <div class="gcal-time-col">
    `;

    // Horario exacto solicitado: Desde las 6:00 am hasta las 10:00 pm (h = 6 a h = 22)
    for (let h = 6; h <= 22; h++) {
        const ampm = h >= 12 ? 'pm' : 'am';
        let h12 = h % 12;
        h12 = h12 ? h12 : 12;
        const labelHorario = `${h < 10 ? '0' : ''}${h}:00 (${h12} ${ampm})`;
        html += `<div class="gcal-time-slot-label">${labelHorario}</div>`;
    }

    html += `</div>`;

    // Columnas para cada día de la semana
    dias.forEach(d => {
        const fStr = d.toISOString().split('T')[0];
        const clasesDia = bloques.filter(b => b.fecha === fStr);

        html += `<div class="gcal-week-day-col">`;

        // 17 líneas guía de 56px cada una
        for (let h = 6; h <= 22; h++) {
            html += `<div class="gcal-grid-hour-line"></div>`;
        }

        // Renderizar bloques en la columna del día
        clasesDia.forEach(b => {
            const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
            const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';

            const [iniH, iniM] = hIni.split(':').map(Number);
            const [finH, finM] = hFin.split(':').map(Number);

            // Calcular posición top y altura basada en las 6:00 am (56px por hora)
            const minutosDesdeInicio = ((iniH - 6) * 60) + (iniM || 0);
            const duracionMinutos = ((finH - iniH) * 60) + ((finM || 0) - (iniM || 0));

            const topPx = Math.max(0, (minutosDesdeInicio / 60) * 56);
            const heightPx = Math.max(48, (duracionMinutos / 60) * 56 - 5);

            const colorInfo = obtenerClaseColor(b.materia);
            const nombreLimpio = formatearNombreMateria(b.materia);
            const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

            html += `
                <div class="gcal-week-card ${colorInfo.css}" 
                     style="top:${topPx}px; height:${heightPx}px;"
                     onclick="gcalAbrirModal(${b.id_horario_bloque})"
                     title="${hIni} a ${hFin}: ${b.materia}">
                    <div>
                        <div style="font-size:0.6875rem; font-weight:800; display:flex; justify-content:space-between; margin-bottom:0.125rem;">
                            <span>${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)}</span>
                        </div>
                        <div style="font-size:0.75rem; font-weight:700; line-height:1.25; margin-bottom:0.25rem;">
                            ${escaparHtml(nombreLimpio)}
                        </div>
                    </div>
                    <div style="font-size:0.6875rem; opacity:0.95; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500;">
                        👤 ${escaparHtml(b.instructor_nombre || 'Instructor')}
                    </div>
                </div>
            `;
        });

        html += `</div>`;
    });

    html += `
            </div>
        </div>
    `;

    area.innerHTML = html;
}

// ─── 2. VISTA MES ESTILO GOOGLE CALENDAR ADAPTADA ───
function renderizarVistaMes(area, titleEl) {
    const anio = gcalEstado.fechaActual.getFullYear();
    const mes = gcalEstado.fechaActual.getMonth();

    titleEl.textContent = `${NOMBRES_MESES[mes]} de ${anio}`;

    const primerDiaMes = new Date(anio, mes, 1);
    let diaInicioSemana = primerDiaMes.getDay() - 1;
    if (diaInicioSemana === -1) diaInicioSemana = 6;

    const fechaInicioGrid = new Date(primerDiaMes);
    fechaInicioGrid.setDate(fechaInicioGrid.getDate() - diaInicioSemana);

    const bloques = obtenerBloquesFiltrados();
    const bloquesPorFecha = {};
    bloques.forEach(b => {
        if (!bloquesPorFecha[b.fecha]) bloquesPorFecha[b.fecha] = [];
        bloquesPorFecha[b.fecha].push(b);
    });

    const hoyStr = (new Date()).toISOString().split('T')[0];

    let html = `
        <div class="gcal-month-grid">
            <div class="gcal-month-header">Lunes</div>
            <div class="gcal-month-header">Martes</div>
            <div class="gcal-month-header">Miércoles</div>
            <div class="gcal-month-header">Jueves</div>
            <div class="gcal-month-header">Viernes</div>
            <div class="gcal-month-header">Sábado</div>
            <div class="gcal-month-header">Domingo</div>
        </div>
        <div class="gcal-month-body">
    `;

    const fechaIter = new Date(fechaInicioGrid);
    for (let c = 0; c < 35; c++) {
        const fechaStr = fechaIter.toISOString().split('T')[0];
        const numDia = fechaIter.getDate();
        const esMesActual = (fechaIter.getMonth() === mes);
        const esHoy = (fechaStr === hoyStr);

        const clasesDelDia = bloquesPorFecha[fechaStr] || [];

        html += `
            <div class="gcal-day-cell ${!esMesActual ? 'other-month' : ''} ${esHoy ? 'is-today' : ''}" 
                 onclick="gcalIrADia('${fechaStr}')" title="Clic para ver horario detallado del día ${numDia}">
                <div class="gcal-day-num">${numDia}</div>
                <div class="gcal-events-wrap">
        `;

        const maxVisibles = 3;
        for (let i = 0; i < Math.min(clasesDelDia.length, maxVisibles); i++) {
            const b = clasesDelDia[i];
            const colorInfo = obtenerClaseColor(b.materia);
            const horaIni = (b.hora_inicio || '06:00').substring(0, 5);
            const horaFin = (b.hora_fin || '09:00').substring(0, 5);
            const nombreLimpio = formatearNombreMateria(b.materia);
            const bTag = (b.bloque === 'bloque1') ? 'B1' : ((b.bloque === 'bloque2') ? 'B2' : 'B');

            html += `
                <div class="gcal-event-pill ${colorInfo.css}" 
                     onclick="event.stopPropagation(); gcalAbrirModal(${b.id_horario_bloque})"
                     title="${bTag} (${horaIni} a ${horaFin}): ${b.materia} - ${b.instructor_nombre}">
                    <span>[${bTag}] ${horaIni}</span> <strong>${escaparHtml(nombreLimpio)}</strong>
                </div>
            `;
        }

        if (clasesDelDia.length > maxVisibles) {
            const restantes = clasesDelDia.length - maxVisibles;
            html += `
                <div style="font-size:0.6875rem; color:var(--sena-brand); font-weight:700; padding:0.125rem 0.25rem; cursor:pointer;"
                     onclick="event.stopPropagation(); gcalIrADia('${fechaStr}')">
                    +${restantes} más
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;

        fechaIter.setDate(fechaIter.getDate() + 1);
    }

    html += `</div>`;
    area.innerHTML = html;
}

// ─── 3. VISTA DÍA COMPLETA (6:00 AM A 10:00 PM) ───
function renderizarVistaDia(area, titleEl) {
    const cur = gcalEstado.fechaActual;
    const fStr = cur.toISOString().split('T')[0];
    const diaSemana = NOMBRES_DIAS[cur.getDay()];
    const diaNum = cur.getDate();
    const mesNombre = NOMBRES_MESES[cur.getMonth()];
    const anio = cur.getFullYear();

    titleEl.textContent = `${diaSemana}, ${diaNum} de ${mesNombre} de ${anio}`;

    const bloques = obtenerBloquesFiltrados();
    const clasesDia = bloques.filter(b => b.fecha === fStr);

    let html = `
        <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--muted);">
            <div>
                <span style="font-size:0.8125rem; font-weight:700; color:var(--sena-brand); text-transform:uppercase;">Programación del Día (06:00 am – 10:00 pm)</span>
                <div style="font-size:1.125rem; font-weight:800; color:var(--foreground);">${diaSemana} ${diaNum} de ${mesNombre} de ${anio}</div>
            </div>
            <span class="shadcn-badge badge-secondary" style="font-size:0.8125rem;">
                ${clasesDia.length} bloque(s) programado(s)
            </span>
        </div>
        <div class="gcal-day-container">
            <div class="gcal-time-col">
    `;

    // 17 franjas de 6:00 am a 10:00 pm
    for (let h = 6; h <= 22; h++) {
        const ampm = h >= 12 ? 'pm' : 'am';
        let h12 = h % 12;
        h12 = h12 ? h12 : 12;
        const labelHorario = `${h < 10 ? '0' : ''}${h}:00 (${h12} ${ampm})`;
        html += `<div class="gcal-time-slot-label">${labelHorario}</div>`;
    }

    html += `</div><div style="position:relative; background:var(--card); height:952px;">`;

    for (let h = 6; h <= 22; h++) {
        html += `<div class="gcal-grid-hour-line"></div>`;
    }

    if (clasesDia.length === 0) {
        html += `
            <div style="position:absolute; top:80px; left:20px; right:20px; text-align:center; padding:3rem; background:rgba(0,0,0,0.02); border:1px dashed var(--border); border-radius:var(--radius-lg); color:var(--muted-foreground);">
                <i class="bi bi-calendar-check fs-2 d-block mb-2" style="color:var(--sena-brand);"></i>
                <strong>No hay formación programada para este día en la ficha</strong>
                <p style="font-size:0.8125rem; margin:0.5rem 0 0 0;">Utiliza los botones de navegación o la vista de semana para revisar los días con clase.</p>
            </div>
        `;
    }

    clasesDia.forEach(b => {
        const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
        const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';

        const [iniH, iniM] = hIni.split(':').map(Number);
        const [finH, finM] = hFin.split(':').map(Number);

        const minutosDesdeInicio = ((iniH - 6) * 60) + (iniM || 0);
        const duracionMinutos = ((finH - iniH) * 60) + ((finM || 0) - (iniM || 0));

        const topPx = Math.max(0, (minutosDesdeInicio / 60) * 56);
        const heightPx = Math.max(60, (duracionMinutos / 60) * 56 - 6);

        const colorInfo = obtenerClaseColor(b.materia);
        const nombreLimpio = formatearNombreMateria(b.materia);
        const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

        html += `
            <div class="gcal-week-card ${colorInfo.css}" 
                 style="top:${topPx}px; height:${heightPx}px; left:16px; right:16px; padding:0.75rem 1.25rem;"
                 onclick="gcalAbrirModal(${b.id_horario_bloque})">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                    <span class="shadcn-badge" style="background:rgba(0,0,0,0.08); font-size:0.8125rem; font-weight:700;">
                        <i class="bi bi-clock me-1"></i>${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)} (${duracionMinutos / 60} horas)
                    </span>
                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">${colorInfo.label}</span>
                </div>
                <div style="font-size:1.0625rem; font-weight:800; line-height:1.3; margin-bottom:0.35rem;">
                    ${escaparHtml(nombreLimpio)}
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem;">
                    <span>👨‍🏫 <strong>${escaparHtml(b.instructor_nombre || 'Instructor')}</strong></span>
                    <span style="opacity:0.6;">•</span>
                    <span style="opacity:0.85;">${escaparHtml(b.instructor_correo || '')}</span>
                </div>
            </div>
        `;
    });

    html += `</div></div>`;
    area.innerHTML = html;
}

// ─── 4. VISTA AGENDA / LISTA CRONOLÓGICA ───
function renderizarVistaAgenda(area, titleEl) {
    const anio = gcalEstado.fechaActual.getFullYear();
    const mes = gcalEstado.fechaActual.getMonth();
    titleEl.textContent = `Agenda — ${NOMBRES_MESES[mes]} de ${anio}`;

    const mesStr = `${anio}-${(mes + 1 < 10 ? '0' : '') + (mes + 1)}`;
    const bloques = obtenerBloquesFiltrados().filter(b => (b.fecha || '').startsWith(mesStr));

    if (bloques.length === 0) {
        area.innerHTML = `
            <div style="text-align:center; padding:4rem 1rem; color:var(--muted-foreground);">
                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                <h3 style="font-weight:700;">No hay bloques programados en ${NOMBRES_MESES[mes]} ${anio}</h3>
                <p style="font-size:0.875rem;">Navega con las flechas o selecciona otro mes para explorar.</p>
            </div>
        `;
        return;
    }

    const bloquesPorFecha = {};
    bloques.forEach(b => {
        if (!bloquesPorFecha[b.fecha]) bloquesPorFecha[b.fecha] = [];
        bloquesPorFecha[b.fecha].push(b);
    });

    let html = `<div style="padding:1.25rem; display:flex; flex-direction:column; gap:1.25rem;">`;

    Object.keys(bloquesPorFecha).sort().forEach(fStr => {
        const clases = bloquesPorFecha[fStr];
        const partes = fStr.split('-');
        const dt = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        const diaNombre = NOMBRES_DIAS[dt.getDay()];
        const numDia = dt.getDate();

        html += `
            <div style="border:1px solid var(--border); border-radius:var(--radius-md); overflow:hidden; background:var(--card);">
                <div style="background:var(--muted); padding:0.625rem 1rem; font-weight:700; font-size:0.875rem; display:flex; justify-content:space-between; align-items:center;">
                    <span><i class="bi bi-calendar3 me-2" style="color:var(--sena-brand);"></i>${diaNombre}, ${numDia} de ${NOMBRES_MESES[dt.getMonth()]} de ${dt.getFullYear()}</span>
                    <span class="shadcn-badge badge-secondary">${clases.length} clase(s)</span>
                </div>
                <div style="padding:0.75rem 1rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:0.75rem;">
        `;

        clases.forEach(b => {
            const colorInfo = obtenerClaseColor(b.materia);
            const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
            const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';
            const nombreLimpio = formatearNombreMateria(b.materia);
            const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

            html += `
                <div class="gcal-event-pill ${colorInfo.css}" 
                     style="padding:0.625rem 0.75rem; border-radius:6px; cursor:pointer;"
                     onclick="gcalAbrirModal(${b.id_horario_bloque})">
                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:0.25rem;">
                        <strong>${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)}</strong>
                        <span>${colorInfo.label}</span>
                    </div>
                    <div style="font-weight:700; font-size:0.875rem; line-height:1.3; margin-bottom:0.25rem;">
                        ${escaparHtml(nombreLimpio)}
                    </div>
                    <div style="font-size:0.75rem; opacity:0.9;">
                        👤 ${escaparHtml(b.instructor_nombre || 'Instructor')}
                    </div>
                </div>
            `;
        });

        html += `</div></div>`;
    });

    html += `</div>`;
    area.innerHTML = html;
}

// ─── ACCIÓN: SALTAR A UN DÍA ESPECÍFICO ───
function gcalIrADia(fechaStr) {
    const partes = fechaStr.split('-');
    gcalEstado.fechaActual = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    gcalCambiarVista('dia');
}

// ─── MODAL DE DETALLES DEL EVENTO ───
function gcalAbrirModal(idBloque) {
    const b = GCAL_BLOQUES.find(x => parseInt(x.id_horario_bloque) === parseInt(idBloque));
    if (!b) return;

    const colorInfo = obtenerClaseColor(b.materia);
    document.getElementById('gcalModalStrip').style.background = colorInfo.border;
    document.getElementById('gcalModalBadge').textContent = colorInfo.label;
    document.getElementById('gcalModalBadge').className = 'shadcn-badge ' + (colorInfo.label === 'Bilingüismo' ? 'badge-primary' : (colorInfo.label === 'Técnica' ? 'badge-secondary' : 'badge-outline'));

    document.getElementById('gcalModalMateria').textContent = b.materia;

    const partes = b.fecha.split('-');
    const dt = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    document.getElementById('gcalModalFecha').textContent = `${NOMBRES_DIAS[dt.getDay()]}, ${dt.getDate()} de ${NOMBRES_MESES[dt.getMonth()]} de ${dt.getFullYear()}`;

    const hIni = b.hora_inicio ? b.hora_inicio.substring(0, 5) : '06:00';
    const hFin = b.hora_fin ? b.hora_fin.substring(0, 5) : '09:00';
    const nombreBloque = (b.bloque === 'bloque1') ? 'Bloque 1' : ((b.bloque === 'bloque2') ? 'Bloque 2' : (b.bloque || 'Bloque'));

    document.getElementById('gcalModalHorario').textContent = `${nombreBloque} • ${formatearHora12(hIni)} – ${formatearHora12(hFin)} (3 horas lectivas)`;

    document.getElementById('gcalModalInstructor').textContent = b.instructor_nombre || 'Instructor no asignado';
    document.getElementById('gcalModalCorreo').textContent = b.instructor_correo || 'Sin correo registrado';

    // Configurar enlace directo a asistencia
    const materiaCod = encodeURIComponent(b.materia + ' - Ficha ' + ID_FICHA_ACTUAL);
    const bloqueCod  = encodeURIComponent(hIni + '|' + hFin + '|' + (b.bloque || 'Bloque 1'));
    document.getElementById('gcalModalBtnAsistencia').href = `index.php?action=asistencia&materia=${materiaCod}&bloque=${bloqueCod}`;

    const modal = document.getElementById('gcalModal');
    modal.style.display = 'flex';
}

function gcalCerrarModal(e) {
    if (!e || e.target.id === 'gcalModal' || e.target.closest('button')) {
        document.getElementById('gcalModal').style.display = 'none';
    }
}

function escaparHtml(texto) {
    if (!texto) return '';
    return texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Iniciar al cargar
document.addEventListener('DOMContentLoaded', () => {
    gcalRenderizar();
});
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
