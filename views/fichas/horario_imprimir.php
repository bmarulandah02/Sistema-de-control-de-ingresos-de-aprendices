<?php
$ficha = $ficha ?? [];
$bloques = $bloques ?? [];
$instructoresFicha = $instructoresFicha ?? [];

$diasEspanol = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
];
$mesesEspanol = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
    'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
];

$bloquesPorFecha = [];
foreach ($bloques as $b) {
    $f = $b['fecha'];
    if (!isset($bloquesPorFecha[$f])) $bloquesPorFecha[$f] = [];
    $bloquesPorFecha[$f][] = $b;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horario Ficha <?= htmlspecialchars($ficha['numero_ficha'] ?? '') ?> — SENA</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        body { margin: 20px; font-size: 12px; color: #1e293b; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #059669; padding-bottom: 12px; margin-bottom: 20px; }
        .logo-area { display: flex; align-items: center; gap: 10px; }
        .logo-box { width: 36px; height: 36px; background: #059669; color: #fff; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .title { font-size: 18px; font-weight: bold; color: #0f172a; margin: 0; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 2px; }
        .badge { background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; margin-bottom: 20px; }
        .meta-item label { display: block; font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .meta-item span { font-weight: bold; font-size: 13px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #059669; color: #fff; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .day-header { background: #f1f5f9; font-weight: bold; color: #0f172a; }
        .btn-print { background: #059669; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="logo-area">
        <div class="logo-box">S</div>
        <div>
            <h1 class="title">Servicio Nacional de Aprendizaje — SENA</h1>
            <div class="subtitle">Horario Oficial de Formación Profesional Integral</div>
        </div>
    </div>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    </div>
</div>

<div class="meta-grid">
    <div class="meta-item">
        <label>Ficha</label>
        <span><?= htmlspecialchars($ficha['numero_ficha'] ?? '') ?></span>
    </div>
    <div class="meta-item">
        <label>Programa</label>
        <span><?= htmlspecialchars($ficha['programa'] ?? 'ADSO') ?></span>
    </div>
    <div class="meta-item">
        <label>Jornada</label>
        <span><?= htmlspecialchars($ficha['jornada'] ?? 'Mañana') ?> (06:00 - 12:00)</span>
    </div>
    <div class="meta-item">
        <label>Total Bloques</label>
        <span><?= count($bloques) ?> Registros</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:130px;">Fecha</th>
            <th style="width:130px;">Franja Horaria</th>
            <th>Competencia / Asignatura</th>
            <th style="width:220px;">Instructor Asignado</th>
            <th style="width:180px;">Correo SENA</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bloques as $b): 
            $dt = new DateTime($b['fecha']);
            $dia = $diasEspanol[$dt->format('l')] ?? $dt->format('l');
            $mes = $mesesEspanol[$dt->format('F')] ?? $dt->format('F');
            $ini = !empty($b['hora_inicio']) ? substr($b['hora_inicio'], 0, 5) : '06:00';
            $fin = !empty($b['hora_fin']) ? substr($b['hora_fin'], 0, 5) : '09:00';
        ?>
        <tr>
            <td>
                <strong><?= $dia ?></strong><br>
                <span style="color:#64748b;"><?= $dt->format('d/m/Y') ?></span>
            </td>
            <td>
                <span class="badge badge-green"><?= $ini ?> – <?= $fin ?></span><br>
                <span style="font-size:10px; color:#64748b;"><?= htmlspecialchars($b['bloque']) ?></span>
            </td>
            <td>
                <strong><?= htmlspecialchars($b['materia'] ?: 'Formación Integral') ?></strong>
            </td>
            <td>
                <strong><?= htmlspecialchars($b['instructor_nombre'] ?: 'Instructor') ?></strong>
            </td>
            <td style="color:#64748b; font-family:monospace; font-size:10px;">
                <?= htmlspecialchars($b['instructor_correo'] ?: '') ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top:30px; border-top:1px solid #cbd5e1; padding-top:15px; display:flex; justify-content:space-between; font-size:10px; color:#94a3b8;">
    <span>Sistema de Control de Ingresos SENA — Generado automáticamente</span>
    <span>Página 1 de 1</span>
</div>

</body>
</html>
