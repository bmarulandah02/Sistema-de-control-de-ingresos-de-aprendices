/**
 * main.js — Sistema de Control de Ingresos de Aprendices
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Auto-cerrar alertas de éxito después de 4s ──────────────
    document.querySelectorAll('.alert-success, .alert-info').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert?.close();
        }, 4000);
    });

    // ── Confirmación de eliminación ──────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            const msg = el.dataset.confirm || '¿Estás seguro?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Spinner en botones de submit ────────────────────────────
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('[type="submit"]').forEach(btn => {
                const txt = btn.innerHTML;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Procesando...`;
                btn.disabled = true;
                // Re-habilitar después de 8s (fallback)
                setTimeout(() => {
                    btn.innerHTML = txt;
                    btn.disabled  = false;
                }, 8000);
            });
        });
    });

    // ── Resaltar fila de tabla al hacer clic ────────────────────
    document.querySelectorAll('.table-hover tbody tr').forEach(row => {
        row.style.cursor = 'default';
    });

    // ── Tooltip de Bootstrap en elementos [title] ───────────────
    const tooltipEls = document.querySelectorAll('[title]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el, { trigger: 'hover' }));

    // ── RFID: auto-focus en campo rfid_uid si existe ─────────────
    const rfidInput = document.getElementById('rfid_uid');
    if (rfidInput) {
        rfidInput.focus();

        // Si el lector envía Enter al final, submit automático
        rfidInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (rfidInput.value.trim().length > 0) {
                    rfidInput.closest('form').submit();
                }
            }
        });
    }

    // ── Validación de fechas: fin >= inicio ──────────────────────
    const fInicio = document.querySelector('[name="fecha_inicio"]');
    const fFin    = document.querySelector('[name="fecha_fin"]');
    if (fInicio && fFin) {
        fFin.addEventListener('change', () => {
            if (fFin.value && fInicio.value && fFin.value < fInicio.value) {
                fFin.setCustomValidity('La fecha fin no puede ser anterior a la fecha inicio.');
                fFin.reportValidity();
            } else {
                fFin.setCustomValidity('');
            }
        });
    }

});
