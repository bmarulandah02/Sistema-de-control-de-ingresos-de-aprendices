<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencias e Inasistencias SENA</title>
    <link rel="stylesheet" href="public/css/reporte_pdf.css">
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="background:#39a900; color:white; border:none; padding:8px 16px; border-radius:4px; font-size:12px; cursor:pointer;">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>

    <div class="header">
        <div>
            <h1>SERVICIO NACIONAL DE APRENDIZAJE — SENA</h1>
            <p>Reporte Consolidado de Asistencias, Retardos e Inasistencias</p>
        </div>
        <div style="text-align: right;">
            <strong>Fecha de emisión:</strong> <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <div class="info-box">
        <div>
            <strong>Rango de Evaluación:</strong> <?= htmlspecialchars($filtros['fecha_inicio']) ?> al <?= htmlspecialchars($filtros['fecha_fin']) ?><br>
            <strong>Generado por:</strong> <?= htmlspecialchars($_SESSION['nombre'] ?? 'Administrador') ?> (<?= htmlspecialchars($_SESSION['rol'] ?? 'Admin') ?>)
        </div>
        <div>
            <strong>Total Aprendices Evaluados:</strong> <?= count($reporte) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Aprendiz</th>
                <th>Documento</th>
                <th>Ficha / Programa</th>
                <th>Instructor Encargado</th>
                <th>Retardos Acumulados</th>
                <th>Días Inasistentes</th>
                <th>Fechas de Inasistencia</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reporte)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">No se encontraron registros para los filtros seleccionados.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($reporte as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['aprendiz']) ?></strong></td>
                <td><?= htmlspecialchars($r['documento']) ?></td>
                <td>
                    Ficha: <strong><?= htmlspecialchars($r['numero_ficha']) ?></strong><br>
                    <small><?= htmlspecialchars($r['programa']) ?></small>
                </td>
                <td><?= htmlspecialchars($r['instructor']) ?></td>
                <td>
                    <?php if ($r['minutos_retardo'] > 0): ?>
                    <span class="badge badge-warning"><?= $r['minutos_retardo'] ?> min (<?= $r['horas_retardo'] ?> hrs)</span>
                    <?php else: ?>
                    <span class="badge badge-success">Sin retardos</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['total_inasistencias'] > 0): ?>
                    <span class="badge badge-danger"><?= $r['total_inasistencias'] ?> día(s)</span>
                    <?php else: ?>
                    <span class="badge badge-success">0 días</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 11px;">
                    <?php if (!empty($r['dias_faltados'])): ?>
                        <?php foreach ($r['dias_faltados'] as $df): ?>
                        <div>• <strong><?= htmlspecialchars($df['fecha']) ?></strong> (Inst. <?= htmlspecialchars($df['instructor']) ?>)</div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color:#16a34a;">Asistencia Completa</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Sistema de Control de Ingresos y Asistencias de Aprendices — SENA ADSO
    </div>

    <script>
        // Lanzar diálogo de impresión automáticamente al abrir si se especifica
        window.onload = function() {
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        };
    </script>
</body>
</html>
