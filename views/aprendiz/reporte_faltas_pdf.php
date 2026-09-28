<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inasistencias — <?= htmlspecialchars($reporteFaltas['aprendiz']['nombre'] ?? 'Aprendiz') ?></title>
    <link rel="stylesheet" href="public/css/reporte_pdf.css">
    <style>
        .page-actions-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #39a900;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-back {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
        }
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-box {
            border-top: 1px solid #0f172a;
            padding-top: 8px;
            text-align: center;
            font-size: 12px;
        }
        .notice-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 25px;
            font-size: 11px;
            line-height: 1.5;
            color: #475569;
        }
        @media print {
            .page-actions-bar { display: none !important; }
            body { margin: 0; padding: 15px; }
        }
    </style>
</head>
<body>

    <div class="page-actions-bar no-print">
        <a href="index.php?action=mi-perfil" class="btn-back">
            ← Volver a Mi Perfil
        </a>
        <button onclick="window.print()" class="btn-print">
            🖨️ Imprimir / Guardar como PDF
        </button>
    </div>

    <!-- ── ENCABEZADO OFICIAL SENA ───────────────────────────────── -->
    <div class="header">
        <div>
            <h1>SERVICIO NACIONAL DE APRENDIZAJE — SENA</h1>
            <p>Reporte de Inasistencias y Formato de Justificación Mensual</p>
        </div>
        <div style="text-align: right;">
            <strong>Fecha de Emisión:</strong> <?= date('d/m/Y H:i') ?><br>
            <span style="font-size: 11px; color:#64748b;">Sistema de Control de Ingresos</span>
        </div>
    </div>

    <?php 
    $app = $reporteFaltas['aprendiz'] ?? [];
    $mesStr = $reporteFaltas['mes'] ?? date('Y-m');
    $nombresMeses = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];
    $partesMes = explode('-', $mesStr);
    $nombreMesTexto = ($nombresMeses[$partesMes[1] ?? ''] ?? $mesStr) . ' ' . ($partesMes[0] ?? '');
    ?>

    <!-- ── DATOS DEL APRENDIZ ────────────────────────────────────── -->
    <div class="info-box">
        <div>
            <strong>Aprendiz:</strong> <?= htmlspecialchars($app['nombre'] ?? '—') ?><br>
            <strong>Documento de Identidad:</strong> <?= htmlspecialchars($app['documento'] ?? '—') ?><br>
            <strong>Programa de Formación:</strong> <?= htmlspecialchars($app['programa'] ?? '—') ?>
        </div>
        <div style="text-align: right;">
            <strong>Ficha N°:</strong> <?= htmlspecialchars($app['numero_ficha'] ?? '—') ?><br>
            <strong>Jornada:</strong> <?= htmlspecialchars($app['jornada'] ?? 'Diurna') ?><br>
            <strong>Instructor Líder:</strong> <?= htmlspecialchars($app['instructor'] ?? '—') ?><br>
            <strong>Mes Evaluado:</strong> <span style="color:#39a900; font-weight:700;"><?= htmlspecialchars($nombreMesTexto) ?></span>
        </div>
    </div>

    <!-- ── TABLA DE FALTAS REGISTRADAS ───────────────────────────── -->
    <h3 style="font-size: 13px; text-transform: uppercase; margin: 18px 0 8px 0; color: #0f172a; border-bottom: 2px solid #39a900; padding-bottom: 4px;">
        Relación de Inasistencias Injustificadas del Mes (<?= (int)$reporteFaltas['total_faltas'] ?>)
    </h3>

    <table>
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">N°</th>
                <th style="width: 110px;">Fecha</th>
                <th style="width: 100px;">Día</th>
                <th>Instructor Encargado</th>
                <th style="width: 140px;">Estado de Justificación</th>
                <th>Observación / Motivo Diligenciado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reporteFaltas['faltas'])): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 25px; color: #16a34a; font-weight: 600;">
                    ✓ El aprendiz no registra inasistencias pendientes de justificación en el período <?= htmlspecialchars($nombreMesTexto) ?>.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($reporteFaltas['faltas'] as $i => $f): ?>
            <tr>
                <td style="text-align: center; font-weight: 600;"><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($f['fecha']) ?></strong></td>
                <td><?= htmlspecialchars($f['dia_semana']) ?></td>
                <td><?= htmlspecialchars($f['instructor']) ?></td>
                <td>
                    <?php if ($f['tiene_excusa']): ?>
                        <span class="badge badge-warning">Excusa en Revisión</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Sin Justificar</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 11px; color: #475569;">
                    <?= !empty($f['motivo_excusa']) ? htmlspecialchars($f['motivo_excusa']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ── CONSTANCIA Y REGLAMENTO SENA ──────────────────────────── -->
    <div class="notice-card">
        <strong>Nota Institucional (Reglamento del Aprendiz SENA):</strong><br>
        El aprendiz cuenta con los plazos estipulados en el reglamento para radicar y justificar sus inasistencias adjuntando los soportes válidos (incapacidades médicas EPS/IPS o calamidad doméstica). Una vez la excusa sea revisada y aprobada por el instructor encargado, la falta será removida de este registro.
    </div>

    <!-- ── ESPACIO DE FIRMAS ─────────────────────────────────────── -->
    <div class="signatures-grid">
        <div class="signature-box">
            <strong><?= htmlspecialchars($app['nombre'] ?? 'Firma del Aprendiz') ?></strong><br>
            <span>Aprendiz en Formación</span><br>
            <span>Doc: <?= htmlspecialchars($app['documento'] ?? '—') ?></span>
        </div>
        <div class="signature-box">
            <strong><?= htmlspecialchars($app['instructor'] ?? 'Firma del Instructor') ?></strong><br>
            <span>Instructor Líder / Encargado</span><br>
            <span>Ficha: <?= htmlspecialchars($app['numero_ficha'] ?? '—') ?></span>
        </div>
    </div>

    <div class="footer">
        Servicio Nacional de Aprendizaje SENA — Control de Ingresos y Gestión de Asistencias
    </div>

    <script>
        window.onload = function() {
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        };
    </script>
</body>
</html>
