<?php
$app = $datosCitacion['aprendiz'] ?? [];
$riesgo = $datosCitacion['riesgo'] ?? [];
$fechaActual = $datosCitacion['fecha_actual'] ?? date('Y-m-d');
$fechaLimite = $datosCitacion['fecha_limite'] ?? date('Y-m-d', strtotime('+5 weekdays'));
$fechasFaltas = $datosCitacion['fechas_faltas'] ?? [];
$totalFaltas = $datosCitacion['total_faltas'] ?? count($fechasFaltas);
$consecutivas = $datosCitacion['consecutivas'] ?? 0;

$nombreCompleto = trim(($app['nombre'] ?? '') . ' ' . ($app['apellido'] ?? ''));
$documento = $app['documento'] ?? '—';
$numeroFicha = $app['numero_ficha'] ?? '—';
$programa = $app['programa'] ?? '—';
$jornada = $app['jornada'] ?? '—';
$correo = $app['correo'] ?? '—';
$telefono = $app['telefono'] ?? '—';
$instructorLider = $app['instructor_lider'] ?? 'Instructor Técnico';
$instructorCorreo = $app['instructor_correo'] ?? '—';

// Formato de fechas en español
function fechaEspCitacion($f) {
    if (empty($f)) return '—';
    $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
    $p = explode('-', $f);
    if (count($p) === 3) {
        return (int)$p[2] . ' de ' . ($meses[$p[1]] ?? $p[1]) . ' de ' . $p[0];
    }
    return $f;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicación de Inasistencias y Notificación de Deserción — <?= htmlspecialchars($nombreCompleto) ?> — SENA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sena-green: #39A900;
            --sena-dark: #00324D;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── BARRA DE ACCIONES SUPERIOR (NO IMPRIMIBLE) ─── */
        .no-print-bar {
            background: #00324D;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 50, 77, 0.25);
        }
        .no-print-bar .title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-print {
            background: #39A900;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #2e8600;
        }
        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* ─── HOJA MEMBRETE SENA ─── */
        .document-container {
            max-width: 850px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .paper-sheet {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 48px 56px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        /* Encabezado Institucional */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #00324D;
            margin-bottom: 28px;
        }
        .sena-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .sena-logo-box {
            width: 48px;
            height: 48px;
            background: #39A900;
            color: #ffffff;
            font-size: 22px;
            font-weight: 900;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -1px;
        }
        .sena-brand-text h1 {
            font-size: 16px;
            font-weight: 900;
            color: #00324D;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .sena-brand-text p {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .doc-radicado {
            text-align: right;
            font-size: 11px;
            color: var(--text-muted);
        }
        .doc-radicado strong {
            display: block;
            font-size: 13px;
            color: #00324D;
            font-weight: 800;
        }

        /* Título del Oficio */
        .doc-subject {
            background: #f8fafc;
            border-left: 4px solid <?= $consecutivas >= 3 ? '#dc2626' : '#ea580c' ?>;
            padding: 14px 18px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 24px;
        }
        .doc-subject .label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: <?= $consecutivas >= 3 ? '#dc2626' : '#ea580c' ?>;
        }
        .doc-subject .title {
            font-size: 15px;
            font-weight: 800;
            color: #00324D;
            margin-top: 4px;
        }

        /* Datos del Destinatario */
        .doc-metadata-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 12px;
        }
        .meta-field label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .meta-field span {
            font-weight: 700;
            color: #0f172a;
        }

        /* Cuerpo de la Carta */
        .doc-body {
            font-size: 13px;
            line-height: 1.7;
            color: #1e293b;
            text-align: justify;
            margin-bottom: 28px;
        }
        .doc-body p {
            margin-bottom: 14px;
        }

        /* Tabla de Inasistencias */
        .faltas-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 20px 0;
            font-size: 12px;
        }
        .faltas-table th {
            background: #00324D;
            color: #ffffff;
            padding: 8px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .faltas-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .faltas-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* Caja de Alerta Legal / Plazo */
        .legal-notice-box {
            background: <?= $consecutivas >= 3 ? '#fef2f2' : '#fffbeb' ?>;
            border: 1px solid <?= $consecutivas >= 3 ? '#fecaca' : '#fef3c7' ?>;
            border-left: 5px solid <?= $consecutivas >= 3 ? '#dc2626' : '#d97706' ?>;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 12.5px;
            line-height: 1.6;
        }
        .legal-notice-box strong {
            color: <?= $consecutivas >= 3 ? '#991b1b' : '#92400e' ?>;
            font-weight: 800;
        }

        /* Firmas */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 48px;
            padding-top: 24px;
        }
        .sig-block {
            text-align: center;
        }
        .sig-line {
            border-top: 1px solid #00324D;
            margin-bottom: 8px;
        }
        .sig-name {
            font-size: 12px;
            font-weight: 800;
            color: #00324D;
        }
        .sig-role {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Pie de página institucional */
        .doc-footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--text-muted);
        }

        /* ─── AJUSTES DE IMPRESIÓN (PRINT) ─── */
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .document-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .paper-sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 20px 25px !important;
                border-radius: 0 !important;
            }
            @page {
                size: letter portrait;
                margin: 1.5cm 1.5cm 1.5cm 1.5cm;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR PARA NAVEGACIÓN E IMPRESIÓN -->
    <div class="no-print-bar">
        <div class="title-group">
            <a href="index.php?action=desercion&ficha_id=<?= (int)$app['fk_ficha'] ?>" class="btn-action btn-back">
                <i class="bi bi-arrow-left"></i> Volver a Alertas de Deserción
            </a>
            <span style="font-size: 13px; font-weight: 600; opacity: 0.9;">
                Vista Previa Oficial — <?= htmlspecialchars($nombreCompleto) ?>
            </span>
        </div>
        <div>
            <button type="button" class="btn-action btn-print" onclick="window.print();">
                <i class="bi bi-printer-fill"></i> Imprimir Citación Oficial / Guardar PDF
            </button>
        </div>
    </div>

    <!-- DOCUMENTO OFICIAL FORMULARIO / MEMBRETE SENA -->
    <div class="document-container">
        <div class="paper-sheet">

            <!-- Encabezado Institucional -->
            <div class="doc-header">
                <div class="sena-brand">
                    <div class="sena-logo-box">S</div>
                    <div class="sena-brand-text">
                        <h1>Servicio Nacional de Aprendizaje — SENA</h1>
                        <p>Centro de Formación Profesional Integral • Coordinación Académica</p>
                    </div>
                </div>
                <div class="doc-radicado">
                    <strong>OFICIO REQ-<?= date('Y') ?>-<?= str_pad((string)$app['id_aprendiz'], 5, '0', STR_PAD_LEFT) ?></strong>
                    <span>Fecha de Emisión: <?= fechaEspCitacion($fechaActual) ?></span>
                </div>
            </div>

            <!-- Asunto y Estado -->
            <div class="doc-subject">
                <div class="label">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <?= $consecutivas >= 3 ? 'Causal de Deserción Inminente (Art. 22 Reglamento del Aprendiz)' : 'Requerimiento Formal por Inasistencias Injustificadas' ?>
                </div>
                <div class="title">
                    Notificación y Citación a Descargos por Inasistencias en Proceso de Formación Integral
                </div>
            </div>

            <!-- Metadatos del Aprendiz -->
            <div class="doc-metadata-grid">
                <div class="meta-field">
                    <label>Aprendiz:</label>
                    <span><?= htmlspecialchars($nombreCompleto) ?></span>
                </div>
                <div class="meta-field">
                    <label>Documento de Identidad:</label>
                    <span><?= htmlspecialchars($documento) ?></span>
                </div>
                <div class="meta-field">
                    <label>Ficha de Formación:</label>
                    <span><?= htmlspecialchars($numeroFicha) ?> — <?= htmlspecialchars($programa) ?></span>
                </div>
                <div class="meta-field">
                    <label>Jornada / Horario:</label>
                    <span><?= htmlspecialchars($jornada) ?></span>
                </div>
                <div class="meta-field">
                    <label>Correo Electrónico:</label>
                    <span><?= htmlspecialchars($correo) ?></span>
                </div>
                <div class="meta-field">
                    <label>Teléfono de Contacto:</label>
                    <span><?= htmlspecialchars($telefono ?: 'No registrado') ?></span>
                </div>
            </div>

            <!-- Cuerpo del Oficio -->
            <div class="doc-body">
                <p>
                    Apreciado(a) Aprendiz:
                </p>
                <p>
                    De conformidad con lo dispuesto en el <strong>Reglamento del Aprendiz SENA (Acuerdo 007 de 2012, Capítulo VII, Artículo 22)</strong>, 
                    se procede a realizar este requerimiento oficial en virtud de que en el Sistema de Control de Ingresos y Asistencias de la institución 
                    se registran <strong><?= (int)$totalFaltas ?> inasistencia(s) sin soporte médico ni justificación validada</strong>, de las cuales 
                    <strong><?= (int)$consecutivas ?> corresponden a días hábiles consecutivos</strong> en las sesiones programadas de formación.
                </p>

                <!-- Relación de Fechas con Faltas -->
                <p style="margin-bottom: 6px;"><strong>Relación detallada de fechas registradas con inasistencia:</strong></p>
                <table class="faltas-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Fecha de Formación</th>
                            <th style="width: 35%;">Sesión Programada</th>
                            <th style="width: 40%;">Estado en Sistema</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fechasFaltas as $idx => $fFecha): ?>
                        <tr>
                            <td><strong><?= fechaEspCitacion($fFecha) ?></strong></td>
                            <td>Jornada Habitual (<?= htmlspecialchars($jornada) ?>)</td>
                            <td>
                                <span style="color: #dc2626; font-weight: 700;">
                                    <i class="bi bi-x-circle-fill"></i> Inasistencia sin Excusa
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fechasFaltas)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted);">
                                No se encontraron registros de faltas en el rango seleccionado.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Fundamento Legal y Plazo de 5 días -->
                <div class="legal-notice-box">
                    <strong>TÉRMINO LEGAL PERENTORIO PARA PRESENTAR JUSTIFICACIÓN:</strong><br>
                    Según lo ordenado en el Artículo 22, numeral 1 del Reglamento del Aprendiz, usted cuenta con un término perentorio de 
                    <strong>cinco (5) días hábiles</strong>, contados a partir del recibo de esta comunicación, es decir hasta el 
                    <strong><?= fechaEspCitacion($fechaLimite) ?></strong>, para aportar a través de la plataforma institucional o ante el instructor 
                    líder los soportes médicos (EPS/IPS) o de fuerza mayor que justifiquen plenamente su ausencia.
                    <br><br>
                    <em>
                        "Vencido este término sin que el Aprendiz haya justificado plenamente sus inasistencias o en caso de que las justificaciones no sean 
                        conforme a la normatividad, el Subdirector de Centro ordenará mediante Acto Administrativo la Cancelación de la Matrícula por Deserción, 
                        implicando la sanción de no poder inscribirse en programas del SENA durante los seis (6) meses siguientes."
                    </em>
                </div>

                <p>
                    Le exhortamos a comunicarse de inmediato con su equipo de instructores o radicar sus documentos a través del módulo institucional de Excusas Médicas 
                    para garantizar el debido proceso y salvaguardar la continuidad de su proceso formativo.
                </p>
            </div>

            <!-- Bloque de Firmas -->
            <div class="signatures-grid">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-name"><?= htmlspecialchars($instructorLider) ?></div>
                    <div class="sig-role">Instructor / Vocero Responsable</div>
                    <div class="sig-role" style="font-size: 10px;"><?= htmlspecialchars($instructorCorreo) ?></div>
                </div>
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-name"><?= htmlspecialchars($nombreCompleto) ?></div>
                    <div class="sig-role">Firma Aprendiz Notificado / C.C. <?= htmlspecialchars($documento) ?></div>
                    <div class="sig-role" style="font-size: 10px;">Fecha Recibido: ____ / ____ / ________</div>
                </div>
            </div>

            <!-- Pie Institucional -->
            <div class="doc-footer">
                <span>Sistema de Control de Ingresos y Asistencias SENA • Versión Oficial 2.0</span>
                <span>Documento Generado el <?= date('d/m/Y H:i') ?> • Reserva de Ley</span>
            </div>

        </div>
    </div>

</body>
</html>
