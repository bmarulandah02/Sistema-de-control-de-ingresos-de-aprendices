<?php 
require __DIR__ . '/../../views/layouts/header.php'; 
?>

<div class="shadcn-container">

    <!-- ── ENCABEZADO DE PÁGINA ────────────────────────────────────────── -->
    <div class="page-header-shadcn">
        <div>
            <div class="page-header-pre">
                <i class="bi bi-shield-exclamation me-1" style="color:var(--destructive, #ef4444);"></i>
                <span>Gestión Académica & Seguimiento</span>
            </div>
            <h1 class="page-header-title">Alertas de Deserción & Inasistencias</h1>
            <div class="page-header-subtitle">
                Monitoreo de aprendices con faltas sin justificar y emisión oficial de anuncios de deserción (Reglamento SENA — Art. 22).
            </div>
        </div>

        <div class="page-header-actions" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <a href="index.php?action=desercion-exportar&ficha_id=<?= $idFichaFiltro ?>&periodo=<?= urlencode($periodoPreset) ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>" 
               class="btn-shadcn btn-shadcn-outline" title="Descargar reporte en Excel">
                <i class="bi bi-filetype-csv me-1" style="color:#059669;"></i>Exportar Reporte CSV
            </a>
            <a href="index.php?action=excusas-admin" class="btn-shadcn btn-shadcn-outline">
                <i class="bi bi-file-medical me-1"></i>Ver Excusas Médicas
            </a>
        </div>
    </div>

    <!-- ── BANNER NORMATIVO REGLAMENTO SENA ────────────────────────────── -->
    <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-left:4px solid #dc2626; border-radius:var(--radius-md); padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:flex-start; gap:0.875rem;">
        <i class="bi bi-exclamation-octagon-fill" style="font-size:1.5rem; color:#dc2626; line-height:1; margin-top:0.125rem;"></i>
        <div style="font-size:0.8125rem; color:var(--foreground); line-height:1.5;">
            <strong style="color:#b91c1c; font-size:0.875rem;">Marco Normativo — Acuerdo 007 de 2012 (Reglamento del Aprendiz SENA, Art. 22):</strong><br>
            Se considera causal de <strong>Deserción</strong> cuando el aprendiz no se presenta de forma injustificada por <strong>tres (3) días consecutivos</strong>. 
            El procedimiento exige enviar una comunicación oficial otorgando un término improrrogable de <strong>cinco (5) días hábiles</strong> para aportar los descargos o justificaciones válidas antes de proceder con la cancelación de matrícula.
        </div>
    </div>

    <!-- ── TARJETAS DE MÉTRICAS / KPIS ─────────────────────────────────── -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        
        <!-- Causal de Deserción -->
        <div class="shadcn-card" style="padding:1.25rem; border-left:4px solid #dc2626; background:linear-gradient(to bottom right, rgba(220,38,38,0.03), transparent);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.05em; color:#dc2626;">Causal de Deserción</span>
                <span class="shadcn-badge badge-danger"><i class="bi bi-alarm-fill me-1"></i>Crítico</span>
            </div>
            <div style="font-size:2rem; font-weight:800; color:#dc2626; line-height:1; margin-bottom:0.25rem;">
                <?= $totales['en_desercion'] ?>
            </div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                Aprendices con <strong>3 o más faltas consecutivas</strong> sin excusa
            </div>
        </div>

        <!-- Riesgo Alto -->
        <div class="shadcn-card" style="padding:1.25rem; border-left:4px solid #ea580c;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.05em; color:#ea580c;">Riesgo Alto</span>
                <span class="shadcn-badge" style="background:rgba(234,88,12,0.15); color:#ea580c; font-weight:600;">2 seguidas / 3+ total</span>
            </div>
            <div style="font-size:2rem; font-weight:800; color:#ea580c; line-height:1; margin-bottom:0.25rem;">
                <?= $totales['en_riesgo_alto'] ?>
            </div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                Aprendices propensos a entrar en causal de deserción
            </div>
        </div>

        <!-- Alerta Preventiva -->
        <div class="shadcn-card" style="padding:1.25rem; border-left:4px solid #f59e0b;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.05em; color:#d97706;">Alerta Preventiva</span>
                <span class="shadcn-badge badge-warning">1 falta</span>
            </div>
            <div style="font-size:2rem; font-weight:800; color:#d97706; line-height:1; margin-bottom:0.25rem;">
                <?= $totales['en_alerta'] ?>
            </div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                Inasistencia reciente sin soporte médico radicado
            </div>
        </div>

        <!-- Total Inasistencias Sin Excusa -->
        <div class="shadcn-card" style="padding:1.25rem; border-left:4px solid #2563eb;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.05em; color:#2563eb;">Total Faltas Sin Excusa</span>
                <i class="bi bi-calendar-x text-primary fs-5"></i>
            </div>
            <div style="font-size:2rem; font-weight:800; color:#2563eb; line-height:1; margin-bottom:0.25rem;">
                <?= $totales['total_faltas'] ?>
            </div>
            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                Días de ausencia no justificados en el periodo evaluado
            </div>
        </div>

    </div>

    <!-- ── BARRA DE FILTROS ────────────────────────────────────────────── -->
    <div class="shadcn-card" style="padding:1.25rem; margin-bottom:1.5rem;">
        <form method="GET" action="index.php" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="action" value="desercion">

            <!-- Filtro de Ficha -->
            <div style="flex:1; min-width:240px;">
                <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:var(--foreground);">
                    <i class="bi bi-journal-bookmark me-1" style="color:var(--sena-brand);"></i>Ficha de Formación
                </label>
                <select name="ficha_id" class="shadcn-select" onchange="this.form.submit()">
                    <?php foreach ($fichas as $f): 
                        $sel = ((int)$f['id'] === $idFichaFiltro) ? 'selected' : '';
                    ?>
                        <option value="<?= $f['id'] ?>" <?= $sel ?>>
                            Ficha <?= htmlspecialchars($f['numero_ficha']) ?> — <?= htmlspecialchars($f['programa']) ?> [<?= htmlspecialchars($f['jornada']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro de Periodo -->
            <div style="width:200px;">
                <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:var(--foreground);">
                    <i class="bi bi-calendar-range me-1"></i>Periodo de Evaluación
                </label>
                <select name="periodo" class="shadcn-select" onchange="this.form.submit()">
                    <option value="mes_actual" <?= ($periodoPreset === 'mes_actual') ? 'selected' : '' ?>>Mes Actual (<?= date('F Y') ?>)</option>
                    <option value="hoy" <?= ($periodoPreset === 'hoy') ? 'selected' : '' ?>>Solo Hoy (<?= date('d/m/Y') ?>)</option>
                    <option value="ultimos_15" <?= ($periodoPreset === 'ultimos_15') ? 'selected' : '' ?>>Últimos 15 días</option>
                    <option value="ultimos_30" <?= ($periodoPreset === 'ultimos_30') ? 'selected' : '' ?>>Últimos 30 días</option>
                    <option value="completo_2026" <?= ($periodoPreset === 'completo_2026') ? 'selected' : '' ?>>Trimestre IV 2026</option>
                </select>
            </div>

            <!-- Filtro de Nivel de Riesgo -->
            <div style="width:200px;">
                <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:var(--foreground);">
                    <i class="bi bi-funnel me-1"></i>Nivel de Alerta
                </label>
                <select name="nivel" class="shadcn-select" onchange="this.form.submit()">
                    <option value="Todos" <?= ($nivelFiltro === 'Todos') ? 'selected' : '' ?>>Todos los niveles</option>
                    <option value="Causal de Deserción" <?= ($nivelFiltro === 'Causal de Deserción') ? 'selected' : '' ?>>🚨 Causal de Deserción (3+)</option>
                    <option value="Riesgo Alto" <?= ($nivelFiltro === 'Riesgo Alto') ? 'selected' : '' ?>>⚠️ Riesgo Alto</option>
                    <option value="Alerta" <?= ($nivelFiltro === 'Alerta') ? 'selected' : '' ?>>🟡 Alerta Preventiva</option>
                </select>
            </div>

            <button type="submit" class="btn-shadcn btn-shadcn-primary" style="height:2.625rem;">
                <i class="bi bi-search me-1"></i>Filtrar
            </button>
        </form>
    </div>

    <!-- ── LISTADO DE APRENDICES EN RIESGO / DESERCIÓN ─────────────────── -->
    <div class="shadcn-card" style="margin-bottom:2rem;">
        <div class="card-header-shadcn" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
            <div>
                <h3 style="margin:0; font-size:1.125rem; font-weight:700; color:var(--foreground);">
                    <i class="bi bi-people-fill me-2" style="color:var(--sena-brand);"></i>Aprendices con Inasistencias Sin Justificar
                </h3>
                <div style="font-size:0.75rem; color:var(--muted-foreground); margin-top:0.25rem;">
                    Ficha <?= htmlspecialchars($fichaActual['numero_ficha'] ?? (string)$idFichaFiltro) ?> — <?= htmlspecialchars($fichaActual['programa'] ?? '') ?> | Periodo: <?= htmlspecialchars($fechaInicio) ?> al <?= htmlspecialchars($fechaFin) ?>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:0.5rem;">
                <span class="shadcn-badge" style="background:var(--muted); color:var(--foreground); font-weight:600;">
                    <?= count($aprendicesRiesgo) ?> aprendices con faltas
                </span>
            </div>
        </div>

        <div class="card-body-shadcn" style="padding:0;">
            <?php if (empty($aprendicesRiesgo)): ?>
                <div style="padding:3.5rem 1.5rem; text-align:center;">
                    <div style="width:4rem; height:4rem; border-radius:50%; background:rgba(16,185,129,0.15); color:#059669; display:inline-flex; align-items:center; justify-content:center; font-size:2rem; margin-bottom:1rem;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <h4 style="font-size:1.125rem; font-weight:700; color:var(--foreground); margin-bottom:0.25rem;">¡Excelente! Ningún aprendiz en riesgo</h4>
                    <p style="font-size:0.875rem; color:var(--muted-foreground); max-width:480px; margin:0 auto;">
                        No se registran inasistencias injustificadas para los aprendices de esta ficha en el período seleccionado.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="shadcn-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Aprendiz</th>
                                <th>Documento</th>
                                <th style="text-align:center;">Faltas Consecutivas</th>
                                <th style="text-align:center;">Total Inasistencias</th>
                                <th>Fechas Sin Excusa</th>
                                <th>Nivel de Riesgo</th>
                                <th>Estado de Trámite</th>
                                <th style="text-align:right;">Acciones de Anuncio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aprendicesRiesgo as $app): 
                                $esCritico = ($app['nivel_riesgo'] === 'Causal de Deserción');
                                $bgFila = $esCritico ? 'rgba(239,68,68,0.03)' : 'transparent';
                            ?>
                            <tr style="background:<?= $bgFila ?>;">
                                <!-- Datos del Aprendiz -->
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <div style="width:2.5rem; height:2.5rem; border-radius:50%; background:<?= $esCritico ? 'rgba(220,38,38,0.15)' : 'rgba(37,99,235,0.1)' ?>; color:<?= $esCritico ? '#dc2626' : '#2563eb' ?>; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.875rem;">
                                            <?= strtoupper(substr($app['nombre_completo'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; color:var(--foreground); font-size:0.875rem;">
                                                <?= htmlspecialchars($app['nombre_completo']) ?>
                                            </div>
                                            <div style="font-size:0.75rem; color:var(--muted-foreground);">
                                                Ficha <?= htmlspecialchars($app['numero_ficha']) ?> • <?= htmlspecialchars($app['jornada']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Documento -->
                                <td style="font-family:monospace; font-size:0.875rem; color:var(--foreground);">
                                    <?= htmlspecialchars($app['documento']) ?>
                                </td>

                                <!-- Faltas Consecutivas -->
                                <td style="text-align:center;">
                                    <?php if ($app['faltas_consecutivas'] >= 3): ?>
                                        <span class="shadcn-badge" style="background:#dc2626; color:#fff; font-weight:800; font-size:0.8125rem; padding:0.3125rem 0.625rem; box-shadow:0 2px 8px rgba(220,38,38,0.3);">
                                            <i class="bi bi-alarm-fill me-1"></i><?= $app['faltas_consecutivas'] ?> días seguidos
                                        </span>
                                    <?php elseif ($app['faltas_consecutivas'] == 2): ?>
                                        <span class="shadcn-badge" style="background:#ea580c; color:#fff; font-weight:700; font-size:0.8125rem;">
                                            <?= $app['faltas_consecutivas'] ?> días seguidos
                                        </span>
                                    <?php else: ?>
                                        <span class="shadcn-badge" style="background:var(--muted); color:var(--foreground); font-weight:600;">
                                            <?= $app['faltas_consecutivas'] ?> día
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Total Inasistencias -->
                                <td style="text-align:center;">
                                    <strong style="font-size:1rem; color:<?= $esCritico ? '#dc2626' : 'var(--foreground)' ?>;">
                                        <?= $app['total_faltas'] ?>
                                    </strong>
                                    <span style="font-size:0.75rem; color:var(--muted-foreground);"> / <?= $app['total_dias_evaluados'] ?> días</span>
                                </td>

                                <!-- Fechas de Inasistencia -->
                                <td>
                                    <div style="display:flex; flex-wrap:wrap; gap:0.25rem; max-width:260px;">
                                        <?php 
                                        $fechas = $app['fechas_faltas'];
                                        $mostradas = array_slice($fechas, 0, 4);
                                        foreach ($mostradas as $fch): 
                                        ?>
                                            <span style="background:rgba(239,68,68,0.1); color:#b91c1c; border:1px solid rgba(239,68,68,0.25); border-radius:0.25rem; font-size:0.6875rem; font-weight:600; padding:0.125rem 0.375rem; font-family:monospace;">
                                                <?= date('d/m', strtotime($fch)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($fechas) > 4): ?>
                                            <span style="font-size:0.6875rem; color:var(--muted-foreground); font-weight:600;">
                                                +<?= count($fechas) - 4 ?> más
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Nivel de Riesgo -->
                                <td>
                                    <span class="shadcn-badge <?= $app['badge_riesgo'] ?>" style="font-size:0.75rem; font-weight:700;">
                                        <?php if ($esCritico): ?>
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <?php else: ?>
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($app['nivel_riesgo']) ?>
                                    </span>
                                </td>

                                <!-- Estado de Trámite / Último Aviso -->
                                <td>
                                    <?php if (!empty($app['ultimo_aviso'])): ?>
                                        <div style="font-size:0.75rem;">
                                            <span class="shadcn-badge" style="background:rgba(37,99,235,0.12); color:#2563eb; font-weight:600;">
                                                <?= htmlspecialchars($app['ultimo_aviso']['estado_tramite']) ?>
                                            </span>
                                            <div style="color:var(--muted-foreground); font-size:0.6875rem; margin-top:0.25rem;">
                                                Límite: <strong><?= date('d/m/Y', strtotime($app['ultimo_aviso']['fecha_limite_descargos'])) ?></strong>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size:0.75rem; color:var(--muted-foreground); font-style:italic;">
                                            Sin aviso formal
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:0.375rem; align-items:center;">
                                        
                                        <!-- Botón Comunicación Oficial / Citación PDF -->
                                        <a href="index.php?action=desercion-citacion&id_aprendiz=<?= $app['id_aprendiz'] ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>" 
                                           target="_blank"
                                           class="btn-shadcn <?= $esCritico ? 'btn-shadcn-primary' : 'btn-shadcn-outline' ?>" 
                                           style="padding:0.375rem 0.625rem; font-size:0.75rem; <?= $esCritico ? 'background:#dc2626; border-color:#dc2626;' : '' ?>"
                                           title="Generar Anuncio / Citación Oficial de Deserción en PDF">
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                            <span>Citación</span>
                                        </a>

                                        <!-- Botón Registrar Notificación -->
                                        <button type="button" class="btn-shadcn btn-shadcn-outline" 
                                                style="padding:0.375rem 0.5rem; font-size:0.75rem;" 
                                                title="Registrar aviso de deserción en el sistema"
                                                onclick="abrirModalAviso(<?= htmlspecialchars(json_encode($app)) ?>)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Botón WhatsApp Directo -->
                                        <?php if (!empty($app['telefono'])): 
                                            $telLimpio = preg_replace('/[^0-9]/', '', $app['telefono']);
                                            if (strlen($telLimpio) === 10 && str_starts_with($telLimpio, '3')) {
                                                $telLimpio = '57' . $telLimpio;
                                            }
                                            $msgWhatsapp = "Cordial saludo aprendiz {$app['nombre_completo']}. Desde la coordinación del SENA le informamos que registra {$app['total_faltas']} inasistencia(s) sin justificar (días: " . implode(', ', $app['fechas_faltas']) . "). Lo invitamos a radicar sus justificaciones o comunicarse con su instructor a la mayor brevedad para evitar proceso de deserción.";
                                            $urlWa = "https://api.whatsapp.com/send?phone=" . urlencode($telLimpio) . "&text=" . urlencode($msgWhatsapp);
                                        ?>
                                            <a href="<?= $urlWa ?>" target="_blank" class="btn-shadcn btn-shadcn-ghost" 
                                               style="padding:0.375rem 0.5rem; color:#16a34a;" title="Enviar recordatorio por WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Botón Correo -->
                                        <?php if (!empty($app['correo'])): 
                                            $asuntoCorreo = "AVISO URGENTE: Inasistencias sin justificar en Ficha {$app['numero_ficha']} — SENA";
                                            $cuerpoCorreo = "Estimado(a) aprendiz {$app['nombre_completo']},\n\nLe notificamos que registra {$app['total_faltas']} inasistencia(s) injustificada(s) en su proceso formativo de la Ficha {$app['numero_ficha']} ({$app['programa']}).\n\nFechas: " . implode(', ', $app['fechas_faltas']) . "\n\nDe conformidad con el Artículo 22 del Reglamento del Aprendiz SENA, le solicitamos radicar sus justificaciones o comunicarse de inmediato.\n\nAtentamente,\nCentro de Formación SENA";
                                            $mailtoUrl = "mailto:" . urlencode($app['correo']) . "?subject=" . rawurlencode($asuntoCorreo) . "&body=" . rawurlencode($cuerpoCorreo);
                                        ?>
                                            <a href="<?= $mailtoUrl ?>" class="btn-shadcn btn-shadcn-ghost" 
                                               style="padding:0.375rem 0.5rem; color:#2563eb;" title="Enviar correo de notificación">
                                                <i class="bi bi-envelope"></i>
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── MODAL: REGISTRAR AVISO FORMAL DE DESERCIÓN ───────────────────────── -->
<div id="modalAvisoDesercion" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:1rem; backdrop-filter:blur(3px);">
    <div class="shadcn-card" style="width:100%; max-width:540px; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1px solid var(--border); overflow:hidden;">
        
        <div class="card-header-shadcn" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.125rem; font-weight:700; color:var(--foreground);">
                <i class="bi bi-megaphone-fill me-2" style="color:#dc2626;"></i>Registrar Aviso de Deserción
            </h3>
            <button type="button" class="btn-shadcn btn-shadcn-ghost" style="padding:0.25rem 0.5rem;" onclick="cerrarModalAviso()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="index.php?action=desercion-aviso-guardar">
            <div class="card-body-shadcn" style="padding:1.25rem;">
                <input type="hidden" name="id_aprendiz" id="modal_id_aprendiz">
                <input type="hidden" name="faltas_acumuladas" id="modal_faltas_acumuladas">
                <input type="hidden" name="fechas_faltas" id="modal_fechas_faltas">

                <!-- Info Aprendiz -->
                <div style="background:var(--muted); border-radius:var(--radius-md); padding:0.75rem 1rem; margin-bottom:1rem;">
                    <div style="font-weight:700; font-size:0.9375rem; color:var(--foreground);" id="modal_nombre_aprendiz">—</div>
                    <div style="font-size:0.75rem; color:var(--muted-foreground);" id="modal_info_aprendiz">—</div>
                    <div style="font-size:0.75rem; color:#dc2626; font-weight:600; margin-top:0.25rem;" id="modal_faltas_texto">—</div>
                </div>

                <!-- Medio de Notificación -->
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem;">
                        Medio Utilizado para el Aviso *
                    </label>
                    <select name="medio_notificacion" class="shadcn-select" required>
                        <option value="Comunicación Escrita (Física)">Comunicación Escrita (Física / Impresa)</option>
                        <option value="Correo Institucional SENA">Correo Electrónico Institucional</option>
                        <option value="Notificación Presencial">Notificación Presencial en Aula</option>
                        <option value="Llamada Telefónica / WhatsApp">Llamada Telefónica / WhatsApp</option>
                    </select>
                </div>

                <!-- Estado del Trámite -->
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem;">
                        Estado del Proceso de Deserción
                    </label>
                    <select name="estado_tramite" class="shadcn-select">
                        <option value="Notificado">1. Notificado al Aprendiz (Inicio de 5 días hábiles)</option>
                        <option value="En Descargos">2. En espera de Descargos / Justificación</option>
                        <option value="Remitido a Comité">3. Remitido a Comité de Evaluación y Seguimiento</option>
                        <option value="Cancelación de Matrícula">4. En trámite de Cancelación de Matrícula</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem;">
                        Observaciones / Detalle del Requerimiento
                    </label>
                    <textarea name="observacion" rows="3" class="shadcn-input" style="width:100%; height:auto;"
                              placeholder="Ej: Se entregó citación formal solicitando justificar ausencias de los días 01 y 02 de octubre."></textarea>
                </div>

                <div style="background:rgba(37,99,235,0.06); border:1px solid rgba(37,99,235,0.2); border-radius:var(--radius-md); padding:0.625rem 0.875rem; font-size:0.75rem; color:var(--muted-foreground);">
                    <i class="bi bi-clock-history me-1" style="color:#2563eb;"></i>
                    El sistema calculará automáticamente la <strong>fecha límite de 5 días hábiles</strong> para que el aprendiz presente descargos conforme a la ley.
                </div>
            </div>

            <div style="padding:1rem 1.25rem; background:var(--muted); display:flex; justify-content:flex-end; gap:0.5rem; border-top:1px solid var(--border);">
                <button type="button" class="btn-shadcn btn-shadcn-outline" onclick="cerrarModalAviso()">Cancelar</button>
                <button type="submit" class="btn-shadcn btn-shadcn-primary">
                    <i class="bi bi-check-circle me-1"></i>Registrar Aviso Formal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAviso(app) {
    document.getElementById('modal_id_aprendiz').value = app.id_aprendiz;
    document.getElementById('modal_faltas_acumuladas').value = app.total_faltas;
    document.getElementById('modal_fechas_faltas').value = app.fechas_faltas.join(', ');

    document.getElementById('modal_nombre_aprendiz').textContent = app.nombre_completo;
    document.getElementById('modal_info_aprendiz').textContent = 'Doc: ' + app.documento + ' • Ficha ' + app.numero_ficha + ' (' + app.programa + ')';
    document.getElementById('modal_faltas_texto').textContent = '⚠️ Registra ' + app.total_faltas + ' inasistencia(s) sin excusa (' + app.faltas_consecutivas + ' consecutivas). Fechas: ' + app.fechas_faltas.join(', ');

    const modal = document.getElementById('modalAvisoDesercion');
    modal.style.display = 'flex';
}

function cerrarModalAviso() {
    document.getElementById('modalAvisoDesercion').style.display = 'none';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarModalAviso();
});
</script>

<?php if (!empty($mensaje)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: <?= json_encode($mensaje['tipo'] ?? 'success') ?>,
        title: <?= json_encode(($mensaje['tipo'] ?? '') === 'error' ? 'Error' : 'Operación Exitosa') ?>,
        text: <?= json_encode($mensaje['texto'] ?? '') ?>,
        timer: 4000,
        timerProgressBar: true
    });
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
