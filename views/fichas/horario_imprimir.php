<?php
$ficha = $ficha ?? [];
$idFicha = (int)($ficha['id'] ?? $ficha['id_ficha'] ?? $_GET['ficha_id'] ?? $_GET['id'] ?? 3234082);
$numFicha = $ficha['numero_ficha'] ?? $ficha['id_ficha'] ?? $idFicha;
$programa = $ficha['programa'] ?? $ficha['nombre_programa'] ?? 'ADSO';
$jornada = $ficha['jornada'] ?? 'Mañana (06:00 - 12:00)';

$bloques = $bloques ?? [];
$mesesDisponibles = $mesesDisponibles ?? [];
if (empty($mesesDisponibles) && !empty($idFicha)) {
    $mesesDisponibles = HorarioModel::obtenerMesesDisponiblesHorario($idFicha);
}

// Mes seleccionado por GET o mostrar todos
$mesFiltro = trim($_GET['mes'] ?? '');

// Si no hay filtro y hay meses, mostrar todos
$mesesAMostrar = [];
if (!empty($mesFiltro)) {
    foreach ($mesesDisponibles as $m) {
        if ($m['mes_anio'] === $mesFiltro) {
            $mesesAMostrar[] = $m;
            break;
        }
    }
}
if (empty($mesesAMostrar)) {
    $mesesAMostrar = $mesesDisponibles;
}

// Si sigue vacío pero hay bloques, deducir meses de los bloques
if (empty($mesesAMostrar) && !empty($bloques)) {
    $mesesMap = [];
    foreach ($bloques as $b) {
        $mStr = substr($b['fecha'], 0, 7);
        $mesesMap[$mStr] = true;
    }
    ksort($mesesMap);
    $nombresM = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];
    foreach (array_keys($mesesMap) as $mKey) {
        $partes = explode('-', $mKey);
        $mesesAMostrar[] = [
            'mes_anio' => $mKey,
            'label' => ($nombresM[$partes[1] ?? '01'] ?? 'Mes') . ' ' . ($partes[0] ?? '')
        ];
    }
}

// Agrupar bloques por fecha
$bloquesPorFecha = [];
foreach ($bloques as $b) {
    $f = $b['fecha'];
    if (!isset($bloquesPorFecha[$f])) $bloquesPorFecha[$f] = [];
    $bloquesPorFecha[$f][] = $b;
}

// Funciones de formateo estético
function limpiarNombreMateriaImprimir(string $materia): string {
    $nombre = $materia;
    $nombre = preg_replace('/\s*-\s*PRINCIPAL\s*-\s*CONVERGENTES\s*4/i', '', $nombre);
    $nombre = preg_replace('/\s*-\s*CONVERGENTES\s*4/i', '', $nombre);
    $nombre = preg_replace('/\s*-\s*PRINCIPAL/i', '', $nombre);
    return trim($nombre) ?: 'Formación Integral';
}

function obtenerColorMateriaImprimir(string $materia): array {
    $m = mb_strtoupper($materia);
    if (str_contains($m, 'INGLÉS') || str_contains($m, 'INGLES') || str_contains($m, 'BILINGÜISMO')) {
        return ['bg' => '#eff6ff', 'border' => '#2563eb', 'text' => '#1e40af', 'badge' => 'Bilingüismo'];
    }
    if (str_contains($m, 'AMBIENTAL') || str_contains($m, 'SST') || str_contains($m, 'DERECHOS') || str_contains($m, 'COMUNICACIÓN') || str_contains($m, 'ÉTICA')) {
        return ['bg' => '#fffbeb', 'border' => '#d97706', 'text' => '#92400e', 'badge' => 'Transversal'];
    }
    if (str_contains($m, 'SOCIAL')) {
        return ['bg' => '#faf5ff', 'border' => '#8b5cf6', 'text' => '#6b21a8', 'badge' => 'Social'];
    }
    return ['bg' => '#f0fdf4', 'border' => '#059669', 'text' => '#065f46', 'badge' => 'Técnica'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario Mensual por Bloques — Ficha <?= htmlspecialchars($numFicha) ?> — SENA</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ─── BARRA DE ACCIÓN (NO-PRINT) ─── */
        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-print {
            background: #059669;
            color: #fff;
        }
        .btn-print:hover { background: #047857; }
        .btn-excel {
            background: #10b981;
            color: #fff;
        }
        .btn-excel:hover { background: #059669; }
        .btn-dark {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }
        .btn-dark:hover { background: rgba(255,255,255,0.2); }

        .filter-select {
            padding: 0.45rem 0.85rem;
            background: #1e293b;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        /* ─── CONTENEDOR DE HOJAS MENSUALES ─── */
        .page-container {
            max-width: 1400px;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }

        .hoja-mes {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 18px 22px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            page-break-after: always;
            break-after: page;
        }
        .hoja-mes:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        /* ─── ENCABEZADO INSTITUCIONAL SENA ─── */
        .sena-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #059669;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .sena-logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sena-badge-logo {
            width: 38px;
            height: 38px;
            background: #059669;
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -1px;
        }
        .sena-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .sena-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        .meta-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 14px;
        }
        .meta-item label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.05em;
        }
        .meta-item span {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Título del Mes */
        .mes-banner {
            background: #059669;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px 6px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ─── GRILLA DE CALENDARIO MENSUAL ─── */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border-left: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .cal-head-day {
            background: #f1f5f9;
            border-top: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            padding: 6px;
            text-align: center;
            font-weight: 800;
            font-size: 10px;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cal-cell {
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #cbd5e1;
            min-height: 98px;
            padding: 5px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            background: #ffffff;
        }
        .cal-cell.is-empty {
            background: #f8fafc;
            opacity: 0.5;
        }

        .cal-cell-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }
        .cal-day-num {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f5f9;
        }
        .cal-cell.has-classes .cal-day-num {
            background: #059669;
            color: #ffffff;
        }

        /* Tarjeta de bloque horario dentro del día */
        .cal-event-card {
            border-left: 3px solid;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 9px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border-top: 1px solid rgba(0,0,0,0.04);
            border-right: 1px solid rgba(0,0,0,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .cal-event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            font-size: 8.5px;
        }
        .cal-event-bloque {
            text-transform: uppercase;
        }
        .cal-event-hora {
            opacity: 0.9;
        }
        .cal-event-title {
            font-weight: 700;
            line-height: 1.25;
            color: #0f172a;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .cal-event-inst {
            font-size: 8px;
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
        }

        /* Pie de página de cada hoja */
        .hoja-footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: #94a3b8;
        }

        /* ─── REGLAS DE IMPRESIÓN Y PDF ─── */
        @media print {
            .no-print-bar { display: none !important; }
            body { background: #fff !important; margin: 0; padding: 0; font-size: 9.5px; }
            .page-container { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .hoja-mes {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: 100vh;
                page-break-after: always !important;
                break-after: page !important;
            }
            .hoja-mes:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .cal-cell {
                min-height: 90px;
                padding: 3px;
            }
            .cal-event-card {
                padding: 3px 5px;
                font-size: 8.5px;
            }
        }

        @page {
            size: landscape;
            margin: 7mm 9mm;
        }
    </style>
</head>
<body>

<!-- ── BARRA SUPERIOR DE ACCIONES (OCULTA EN IMPRESIÓN / PDF) ──────── -->
<div class="no-print-bar">
    <div style="display:flex; align-items:center; gap:1rem;">
        <a href="index.php?action=ficha-horario&id=<?= $idFicha ?>" class="btn-action btn-dark">
            <i class="bi bi-arrow-left"></i>Volver al Horario
        </a>
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="bi bi-calendar-month text-emerald-400"></i>
            <span style="font-weight:700; font-size:0.875rem;">Impresión y Exportación — Ficha <?= htmlspecialchars($numFicha) ?></span>
        </div>
    </div>

    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
        <!-- Selector de Mes para filtrar o ver todo el semestre -->
        <select class="filter-select" onchange="window.location.href='index.php?action=reporte-horario-pdf&ficha_id=<?= $idFicha ?>&mes=' + this.value">
            <option value="">— Ver Todo el Semestre (Todos los Meses) —</option>
            <?php foreach ($mesesDisponibles as $m): ?>
                <option value="<?= $m['mes_anio'] ?>" <?= ($mesFiltro === $m['mes_anio']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['label']) ?> (<?= $m['total_bloques'] ?? 0 ?> bloques)
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Descargar Excel como se subió -->
        <a href="index.php?action=reporte-horario-excel&ficha_id=<?= $idFicha ?>&tipo=original" 
           class="btn-action btn-excel" 
           title="Descarga el archivo Excel oficial (.xlsx) tal como se subió al sistema">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Bajar Excel Original (.xlsx)
        </a>

        <!-- Descargar Excel Mensual por Bloques -->
        <a href="index.php?action=reporte-horario-excel&ficha_id=<?= $idFicha ?>&tipo=mensual<?= !empty($mesFiltro) ? '&mes=' . urlencode($mesFiltro) : '' ?>" 
           class="btn-action btn-dark" 
           title="Descarga la plantilla Excel (.xls) estructurada por meses y bloques">
            <i class="bi bi-calendar2-range me-1"></i>Excel Mensual (.xls)
        </a>

        <!-- Botón Imprimir / PDF -->
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir / Guardar en PDF
        </button>
    </div>
</div>

<div class="page-container">

    <?php if (empty($mesesAMostrar)): ?>
        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:3rem; text-align:center; color:#64748b;">
            <i class="bi bi-calendar-x fs-1 d-block mb-2" style="color:#059669;"></i>
            <h3 style="margin:0 0 0.5rem 0; color:#0f172a;">No se encontraron bloques de horario registrados</h3>
            <p>La ficha <?= htmlspecialchars($numFicha) ?> aún no tiene programación de formación registrada.</p>
            <a href="index.php?action=ficha-horario-importar&ficha_id=<?= $idFicha ?>" class="btn-action btn-print" style="margin-top:1rem;">
                <i class="bi bi-file-earmark-arrow-up me-1"></i>Importar Horario desde Excel
            </a>
        </div>
    <?php endif; ?>

    <?php 
    $numHoja = 1;
    $totalHojas = count($mesesAMostrar);

    foreach ($mesesAMostrar as $mInfo): 
        $mesAnio = $mInfo['mes_anio'];
        $labelMes = $mInfo['label'] ?? $mesAnio;

        $dtMes = new DateTime($mesAnio . '-01');
        $anio = (int)$dtMes->format('Y');
        $mesNum = (int)$dtMes->format('m');
        $totalDias = (int)$dtMes->format('t');
        $primerDiaSemana = (int)$dtMes->format('N'); // 1 = Lunes, 7 = Domingo

        // Construir la estructura exacta de semanas
        $semanas = [];
        $semanaActual = [];

        // Celdas vacías al inicio (lunes hasta primer día)
        for ($i = 1; $i < $primerDiaSemana; $i++) {
            $semanaActual[] = null;
        }

        for ($d = 1; $d <= $totalDias; $d++) {
            $fechaStr = sprintf('%04d-%02d-%02d', $anio, $mesNum, $d);
            $semanaActual[] = [
                'dia' => $d,
                'fecha' => $fechaStr,
                'clases' => $bloquesPorFecha[$fechaStr] ?? []
            ];

            if (count($semanaActual) === 7) {
                $semanas[] = $semanaActual;
                $semanaActual = [];
            }
        }

        // Celdas vacías al final
        if (!empty($semanaActual)) {
            while (count($semanaActual) < 7) {
                $semanaActual[] = null;
            }
            $semanas[] = $semanaActual;
        }

        // Contar clases del mes
        $clasesEnMes = 0;
        foreach ($semanas as $sem) {
            foreach ($sem as $diaCell) {
                if ($diaCell && !empty($diaCell['clases'])) {
                    $clasesEnMes += count($diaCell['clases']);
                }
            }
        }
    ?>

    <div class="hoja-mes">
        
        <!-- ENCABEZADO INSTITUCIONAL -->
        <div class="sena-header">
            <div class="sena-logo-box">
                <div class="sena-badge-logo">S</div>
                <div>
                    <h1 class="sena-title">Servicio Nacional de Aprendizaje — SENA</h1>
                    <div class="sena-subtitle">Sistema de Control de Ingresos de Aprendices • Horario Oficial de Formación Profesional</div>
                </div>
            </div>
            <div style="text-align:right;">
                <span style="display:inline-block; background:#ecfdf5; color:#065f46; border:1px solid #10b981; border-radius:4px; padding:3px 8px; font-weight:700; font-size:10px;">
                    Jornada: <?= htmlspecialchars($jornada) ?>
                </span>
            </div>
        </div>

        <!-- METADATOS DE LA FICHA -->
        <div class="meta-bar">
            <div class="meta-item">
                <label>Ficha</label>
                <span><?= htmlspecialchars($numFicha) ?></span>
            </div>
            <div class="meta-item">
                <label>Programa de Formación</label>
                <span><?= htmlspecialchars($programa) ?></span>
            </div>
            <div class="meta-item">
                <label>Mes de Programación</label>
                <span style="color:#059669;"><?= mb_strtoupper($labelMes) ?></span>
            </div>
            <div class="meta-item">
                <label>Bloques Programados</label>
                <span><?= $clasesEnMes ?> Bloques (<?= $clasesEnMes * 3 ?> Horas)</span>
            </div>
        </div>

        <!-- BANNER DEL MES -->
        <div class="mes-banner">
            <div>
                <i class="bi bi-calendar-check me-2"></i><?= mb_strtoupper($labelMes) ?>
            </div>
            <div style="font-size:10px; font-weight:600; opacity:0.95;">
                Franjas: Bloque 1 (06:00 am – 09:00 am) • Bloque 2 (09:00 am – 12:00 pm)
            </div>
        </div>

        <!-- GRILLA DE CALENDARIO -->
        <div class="cal-grid">
            <div class="cal-head-day">Lunes</div>
            <div class="cal-head-day">Martes</div>
            <div class="cal-head-day">Miércoles</div>
            <div class="cal-head-day">Jueves</div>
            <div class="cal-head-day">Viernes</div>
            <div class="cal-head-day">Sábado</div>
            <div class="cal-head-day">Domingo</div>

            <?php foreach ($semanas as $sem): ?>
                <?php foreach ($sem as $diaCell): ?>
                    <?php if ($diaCell === null): ?>
                        <div class="cal-cell is-empty"></div>
                    <?php else: 
                        $hasClasses = !empty($diaCell['clases']);
                    ?>
                        <div class="cal-cell <?= $hasClasses ? 'has-classes' : '' ?>">
                            <div class="cal-cell-header">
                                <span class="cal-day-num"><?= $diaCell['dia'] ?></span>
                                <?php if ($hasClasses): ?>
                                    <span style="font-size:8px; font-weight:700; color:#059669;">
                                        <?= count($diaCell['clases']) ?> <?= count($diaCell['clases']) === 1 ? 'bloque' : 'bloques' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($hasClasses): ?>
                                <?php foreach ($diaCell['clases'] as $b): 
                                    $horaIni = !empty($b['hora_inicio']) ? substr($b['hora_inicio'], 0, 5) : '06:00';
                                    $horaFin = !empty($b['hora_fin']) ? substr($b['hora_fin'], 0, 5) : '09:00';
                                    $nombreBloque = ($b['bloque'] === 'bloque1') ? 'Bloque 1' : (($b['bloque'] === 'bloque2') ? 'Bloque 2' : ($b['bloque'] ?: 'Bloque'));
                                    $color = obtenerColorMateriaImprimir($b['materia']);
                                    $materiaLimpia = limpiarNombreMateriaImprimir($b['materia']);
                                ?>
                                    <div class="cal-event-card" style="border-left-color: <?= $color['border'] ?>; background: <?= $color['bg'] ?>;">
                                        <div class="cal-event-header" style="color: <?= $color['text'] ?>;">
                                            <span class="cal-event-bloque"><?= $nombreBloque ?></span>
                                            <span class="cal-event-hora"><?= $horaIni ?> – <?= $horaFin ?></span>
                                        </div>
                                        <div class="cal-event-title" title="<?= htmlspecialchars($b['materia']) ?>">
                                            <?= htmlspecialchars($materiaLimpia) ?>
                                        </div>
                                        <div class="cal-event-inst">
                                            👤 <?= htmlspecialchars($b['instructor_nombre'] ?: 'Instructor') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#cbd5e1; font-size:10px;">
                                    <span>—</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <!-- PIE DE CADA HOJA -->
        <div class="hoja-footer">
            <div>
                <strong>SENA</strong> — Formación Profesional Integral • Generado el <?= date('d/m/Y H:i') ?>
            </div>
            <div>
                Mes <?= $numHoja ?> de <?= $totalHojas ?>
            </div>
        </div>

    </div>

    <?php 
        $numHoja++;
    endforeach; 
    ?>

</div>

</body>
</html>
