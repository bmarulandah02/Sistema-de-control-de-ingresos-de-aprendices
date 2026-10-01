<?php
$ficha = $ficha ?? [];
$id = (int)($id ?? $ficha['id'] ?? $_GET['id'] ?? 3234082);
$pageTitle = 'Horario de Formación — Ficha ' . ($ficha['numero_ficha'] ?? $id);
require __DIR__ . '/../../views/layouts/header.php';
$bloques = $bloques ?? [];
$instructoresFicha = $instructoresFicha ?? [];
$mesesDisponibles = $mesesDisponibles ?? [];
$todasFichas = $todasFichas ?? [];
$mesFiltro = $mesFiltro ?? '';

// Agrupar bloques por fecha
$bloquesPorFecha = [];
foreach ($bloques as $b) {
    $f = $b['fecha'];
    if (!isset($bloquesPorFecha[$f])) {
        $bloquesPorFecha[$f] = [];
    }
    $bloquesPorFecha[$f][] = $b;
}

$diasEspanol = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
];
$mesesEspanol = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
    'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
];
?>

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
            <span style="display:inline-flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:0.5rem; background:rgba(37,99,235,0.12); color:#2563eb;">
                <i class="bi bi-calendar3" style="font-size:1.375rem;"></i>
            </span>
            Horario Ficha <?= htmlspecialchars($ficha['numero_ficha'] ?? $id) ?>
        </h1>
        <div class="page-header-subtitle">
            Programa: <strong><?= htmlspecialchars($ficha['programa'] ?? 'ADSO') ?></strong> — Jornada <?= htmlspecialchars($ficha['jornada'] ?? 'Mañana') ?>
        </div>
    </div>

    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <a href="index.php?action=ficha-horario-importar&ficha_id=<?= $id ?>" class="btn-shadcn btn-shadcn-primary">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Escanear / Importar Excel
        </a>
        <?php if (!empty($bloques)): ?>
        <a href="index.php?action=ficha-horario-imprimir&id=<?= $id ?><?= !empty($mesFiltro) ? '&mes='.urlencode($mesFiltro) : '' ?>" target="_blank" class="btn-shadcn btn-shadcn-outline">
            <i class="bi bi-printer me-1"></i>Imprimir Horario
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── SELECTOR DE FICHA Y FILTRO POR MES ───────────────────────────── -->
<div class="shadcn-card" style="margin-bottom:1.5rem; padding:1.25rem;">
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

        <!-- Pestañas de meses -->
        <?php if (!empty($mesesDisponibles)): ?>
        <div style="display:flex; flex-wrap:wrap; gap:0.375rem; background:var(--muted); padding:0.25rem; border-radius:var(--radius-md);">
            <a href="index.php?action=ficha-horario&id=<?= $id ?>" 
               class="btn-shadcn <?= empty($mesFiltro) ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
               style="padding:0.375rem 0.75rem; font-size:0.8125rem; text-decoration:none;">
                <i class="bi bi-calendar-range me-1"></i>Todos
            </a>
            <?php foreach ($mesesDisponibles as $m): 
                $activo = ($mesFiltro === $m['mes_anio']);
            ?>
                <a href="index.php?action=ficha-horario&id=<?= $id ?>&mes=<?= urlencode($m['mes_anio']) ?>" 
                   class="btn-shadcn <?= $activo ? 'btn-shadcn-primary' : 'btn-shadcn-ghost' ?>" 
                   style="padding:0.375rem 0.75rem; font-size:0.8125rem; text-decoration:none;">
                    <?= htmlspecialchars($m['label']) ?>
                    <span class="shadcn-badge" style="font-size:0.6875rem; padding:0.125rem 0.375rem; margin-left:0.25rem; background:rgba(0,0,0,0.1);"><?= $m['total_bloques'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ── TARJETAS RESUMEN DE LA FICHA ─────────────────────────────────── -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="shadcn-card" style="padding:1rem; border-left:3px solid #059669;">
        <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Bloques en Vista</div>
        <div style="font-size:1.75rem; font-weight:800; color:var(--foreground);"><?= count($bloques) ?></div>
        <div style="font-size:0.75rem; color:var(--muted-foreground);">Franjas horarias programadas</div>
    </div>

    <div class="shadcn-card" style="padding:1rem; border-left:3px solid #2563eb;">
        <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Instructores Asignados</div>
        <div style="font-size:1.75rem; font-weight:800; color:#2563eb;"><?= count($instructoresFicha) ?></div>
        <div style="font-size:0.75rem; color:var(--muted-foreground);">Registrados en esta ficha</div>
    </div>

    <div class="shadcn-card" style="padding:1rem; border-left:3px solid #8b5cf6;">
        <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Días de Formación</div>
        <div style="font-size:1.75rem; font-weight:800; color:#8b5cf6;"><?= count($bloquesPorFecha) ?></div>
        <div style="font-size:0.75rem; color:var(--muted-foreground);">Jornadas con clase</div>
    </div>

    <div class="shadcn-card" style="padding:1rem; border-left:3px solid #f59e0b;">
        <div style="font-size:0.75rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">Jornada Oficial</div>
        <div style="font-size:1.25rem; font-weight:700; color:var(--foreground); margin-top:0.375rem;">
            <?= htmlspecialchars($ficha['jornada'] ?? 'Mañana') ?>
        </div>
        <div style="font-size:0.75rem; color:var(--muted-foreground);">06:00 – 12:00</div>
    </div>
</div>

<!-- ── LISTA DE INSTRUCTORES DE LA FICHA ────────────────────────────── -->
<?php if (!empty($instructoresFicha)): ?>
<div class="shadcn-card" style="margin-bottom:1.5rem; padding:1.25rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
        <h3 style="font-size:0.9375rem; font-weight:700; margin:0; color:var(--foreground);">
            <i class="bi bi-people-fill me-2" style="color:#059669;"></i>Instructores Vinculados a la Ficha (<?= count($instructoresFicha) ?>)
        </h3>
        <span style="font-size:0.75rem; color:var(--muted-foreground);">Credenciales activas para terminal RFID y registro de asistencia</span>
    </div>
    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
        <?php foreach ($instructoresFicha as $inst): ?>
            <div style="background:var(--muted); border:1px solid var(--border); border-radius:var(--radius-md); padding:0.5rem 0.75rem; display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem;">
                <div style="width:1.75rem; height:1.75rem; border-radius:50%; background:rgba(5,150,105,0.15); color:#059669; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem;">
                    <?= strtoupper(substr($inst['nombre_completo'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight:600; color:var(--foreground);"><?= htmlspecialchars($inst['nombre_completo']) ?></div>
                    <div style="font-size:0.6875rem; color:var(--muted-foreground);"><?= htmlspecialchars($inst['correo']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── GRILLA DE HORARIOS POR FECHA ─────────────────────────────────── -->
<?php if (empty($bloquesPorFecha)): ?>
<div class="shadcn-card" style="padding:3.5rem 1.5rem; text-align:center;">
    <div style="width:4.5rem; height:4.5rem; border-radius:50%; background:rgba(5,150,105,0.1); color:#059669; display:flex; align-items:center; justify-content:center; font-size:2.25rem; margin:0 auto 1.25rem auto;">
        <i class="bi bi-calendar-x"></i>
    </div>
    <h3 style="font-size:1.25rem; font-weight:700; margin-bottom:0.5rem; color:var(--foreground);">No hay bloques de horario registrados</h3>
    <p style="font-size:0.875rem; color:var(--muted-foreground); max-width:480px; margin:0 auto 1.5rem auto; line-height:1.5;">
        Aún no se ha cargado el horario para esta ficha. Puedes importar automáticamente el horario desde el archivo Excel oficial de ejemplo en segundos.
    </p>
    <a href="index.php?action=ficha-horario-importar&ficha_id=<?= $id ?>" class="btn-shadcn btn-shadcn-primary" style="padding:0.75rem 1.5rem;">
        <i class="bi bi-file-earmark-arrow-up me-2"></i>Escanear e Importar Excel Ahora
    </a>
</div>
<?php else: ?>

<div style="display:flex; flex-direction:column; gap:1.25rem;">
    <?php foreach ($bloquesPorFecha as $fechaStr => $bloquesDelDia): 
        $dt = new DateTime($fechaStr);
        $diaIngles = $dt->format('l');
        $mesIngles = $dt->format('F');
        $diaTexto = $diasEspanol[$diaIngles] ?? $diaIngles;
        $mesTexto = $mesesEspanol[$mesIngles] ?? $mesIngles;
        $numDia = $dt->format('d');
        $anio = $dt->format('Y');

        $esHoy = ($fechaStr === date('Y-m-d'));
    ?>
    <div class="shadcn-card" style="padding:1.25rem; <?= $esHoy ? 'border:2px solid #059669; box-shadow:0 4px 16px rgba(5,150,105,0.15);' : '' ?>">
        
        <!-- Encabezado de la Fecha -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:0.75rem; margin-bottom:1rem;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <div style="width:2.5rem; height:2.5rem; border-radius:var(--radius-md); background:<?= $esHoy ? '#059669' : 'var(--muted)' ?>; color:<?= $esHoy ? '#fff' : 'var(--foreground)' ?>; display:flex; flex-direction:column; align-items:center; justify-content:center; font-weight:800; line-height:1;">
                    <span style="font-size:1.125rem;"><?= $numDia ?></span>
                    <span style="font-size:0.625rem; text-transform:uppercase; font-weight:600;"><?= substr($mesTexto, 0, 3) ?></span>
                </div>
                <div>
                    <div style="font-weight:700; font-size:1rem; color:var(--foreground);">
                        <?= $diaTexto ?>, <?= $numDia ?> de <?= $mesTexto ?> de <?= $anio ?>
                    </div>
                    <div style="font-size:0.75rem; color:var(--muted-foreground);">
                        <?= count($bloquesDelDia) ?> bloque(s) programado(s)
                    </div>
                </div>
            </div>

            <?php if ($esHoy): ?>
                <span class="shadcn-badge" style="background:#059669; color:#fff; font-weight:700; font-size:0.75rem;">
                    <i class="bi bi-clock-history me-1"></i>HOY
                </span>
            <?php endif; ?>
        </div>

        <!-- Grilla de bloques del día -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1rem;">
            <?php foreach ($bloquesDelDia as $b): 
                $ini = !empty($b['hora_inicio']) ? substr($b['hora_inicio'], 0, 5) : '06:00';
                $fin = !empty($b['hora_fin']) ? substr($b['hora_fin'], 0, 5) : '09:00';

                // Determinar badge de bloque
                $bNombre = 'Bloque 1 (06:00 - 09:00)';
                $badgeBg = 'rgba(5,150,105,0.12)';
                $badgeColor = '#059669';
                if ($b['bloque'] === 'bloque2') {
                    $bNombre = 'Bloque 2 (09:00 - 12:00)';
                    $badgeBg = 'rgba(37,99,235,0.12)';
                    $badgeColor = '#2563eb';
                } elseif (str_contains($b['bloque'], 't')) {
                    $bNombre = 'Jornada Tarde (' . $ini . ' - ' . $fin . ')';
                    $badgeBg = 'rgba(245,158,11,0.12)';
                    $badgeColor = '#d97706';
                }

                $materia = !empty($b['materia']) ? $b['materia'] : 'Formación Profesional Integral';
            ?>
            <div style="background:var(--muted); border:1px solid var(--border); border-radius:var(--radius-md); padding:1rem; display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <!-- Franja horaria y badge -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.625rem;">
                        <span class="shadcn-badge" style="background:<?= $badgeBg ?>; color:<?= $badgeColor ?>; font-weight:700; font-size:0.75rem;">
                            <i class="bi bi-clock me-1"></i><?= $ini ?> – <?= $fin ?>
                        </span>
                        <span style="font-size:0.6875rem; color:var(--muted-foreground); text-transform:uppercase; font-weight:600;">
                            <?= htmlspecialchars($b['bloque']) ?>
                        </span>
                    </div>

                    <!-- Materia / Asignatura -->
                    <div style="font-weight:700; font-size:0.9375rem; color:var(--foreground); margin-bottom:0.5rem; line-height:1.4;">
                        <?= htmlspecialchars($materia) ?>
                    </div>
                </div>

                <!-- Instructor responsable -->
                <div style="display:flex; align-items:center; gap:0.5rem; border-top:1px solid rgba(0,0,0,0.06); padding-top:0.625rem; margin-top:0.5rem;">
                    <div style="width:2rem; height:2rem; border-radius:50%; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:0.875rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div style="overflow:hidden;">
                        <div style="font-size:0.8125rem; font-weight:600; color:var(--foreground); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">
                            <?= htmlspecialchars($b['instructor_nombre'] ?: 'Instructor Asignado') ?>
                        </div>
                        <div style="font-size:0.6875rem; color:var(--muted-foreground); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">
                            <?= htmlspecialchars($b['instructor_correo'] ?: '') ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
