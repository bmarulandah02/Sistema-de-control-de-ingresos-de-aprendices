/**
 * main.js — Sistema de Control de Ingresos de Aprendices
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── 🔴 MODO OSCURO / CLARO (THEME TOGGLE) ─────────────────────
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon      = document.getElementById('themeIcon');

    function actualizarIconoTema(theme) {
        if (!themeIcon) return;
        if (theme === 'dark') {
            themeIcon.className = 'bi bi-sun-fill text-warning';
        } else {
            themeIcon.className = 'bi bi-moon-stars';
        }
    }

    // Inicializar ícono según tema actual
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    actualizarIconoTema(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            actualizarIconoTema(newTheme);
        });
    }

    // ── 🔴 MENU SIDEBAR MOBILE TOGGLE ──────────────────────────────
    const sidebarToggle  = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebar        = document.getElementById('shadcnSidebar');

    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('open');
            sidebar.classList.toggle('mobile-open');
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active');
            sidebarOverlay.classList.toggle('mobile-open');
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // ── Auto-cerrar alertas de éxito después de 4s ──────────────
    document.querySelectorAll('.alert-success, .alert-info').forEach(alert => {
        setTimeout(() => {
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert?.close();
            } else {
                alert.style.display = 'none';
            }
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
                setTimeout(() => {
                    btn.innerHTML = txt;
                    btn.disabled  = false;
                }, 8000);
            });
        });
    });

    // ── RFID: auto-focus en campo rfid_uid si existe ─────────────
    const rfidInput = document.getElementById('rfid_uid');
    if (rfidInput) {
        rfidInput.focus();

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
