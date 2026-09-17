/**
 * main.js — Sistema de Control de Ingresos de Aprendices
 */

document.addEventListener('DOMContentLoaded', () => {

    // ──  MODO OSCURO / CLARO (THEME TOGGLE) ─────────────────────
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

    // ──  PAGINACIÓN INTELIGENTE DE TABLAS (10 REGISTROS POR PÁGINA) ────
    function inicializarPaginacionTablas(itemsPorPagina = 10) {
        const tablas = document.querySelectorAll('.shadcn-table');

        tablas.forEach(tabla => {
            const tbody = tabla.querySelector('tbody');
            if (!tbody) return;

            // Obtener filas de datos (ignorando filas vacías o de sin resultados)
            const filas = Array.from(tbody.querySelectorAll('tr')).filter(row => {
                const td = row.querySelector('td[colspan]');
                return !td;
            });

            const totalFilas = filas.length;
            let paginadorWrapper = tabla.closest('.shadcn-card')?.querySelector('.shadcn-pagination-bar');

            // Si hay 10 o menos filas, se muestran todas y no se crea barra de botones
            if (totalFilas <= itemsPorPagina) {
                filas.forEach(f => f.style.display = '');
                if (paginadorWrapper) paginadorWrapper.remove();
                return;
            }

            // Si sobresalen de 10 personas, se crean/reutilizan los botones de paginación
            const totalPaginas = Math.ceil(totalFilas / itemsPorPagina);
            let paginaActual = 1;

            if (!paginadorWrapper) {
                paginadorWrapper = document.createElement('div');
                paginadorWrapper.className = 'shadcn-pagination-bar';
                paginadorWrapper.style.cssText = 'display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; padding:0.875rem 1.25rem; border-top:1px solid var(--border); background:var(--card); font-size:0.8125rem; gap:0.75rem;';
                
                const card = tabla.closest('.shadcn-card');
                if (card) {
                    card.appendChild(paginadorWrapper);
                } else {
                    tabla.parentElement.appendChild(paginadorWrapper);
                }
            }

            function renderizarPagina(pagina) {
                paginaActual = Math.max(1, Math.min(pagina, totalPaginas));
                const inicio = (paginaActual - 1) * itemsPorPagina;
                const fin = inicio + itemsPorPagina;

                filas.forEach((row, idx) => {
                    if (idx >= inicio && idx < fin) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                const mostrandoDesde = totalFilas > 0 ? inicio + 1 : 0;
                const mostrandoHasta = Math.min(fin, totalFilas);

                // Renderizar HTML del paginador
                let btnsHtml = '';
                for (let i = 1; i <= totalPaginas; i++) {
                    const btnClass = (i === paginaActual) ? 'btn-shadcn btn-shadcn-primary' : 'btn-shadcn btn-shadcn-outline';
                    btnsHtml += `<button type="button" class="${btnClass} btn-pag" data-page="${i}" style="padding:0.25rem 0.625rem; font-size:0.75rem;">${i}</button>`;
                }

                paginadorWrapper.innerHTML = `
                    <div style="color:var(--muted-foreground); font-weight:500;">
                        Mostrando <strong style="color:var(--foreground);">${mostrandoDesde}–${mostrandoHasta}</strong> de <strong style="color:var(--foreground);">${totalFilas}</strong> registros
                    </div>
                    <div style="display:flex; gap:0.375rem; align-items:center;">
                        <button type="button" class="btn-shadcn btn-shadcn-outline btn-prev" ${paginaActual === 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''} style="padding:0.25rem 0.5rem; font-size:0.75rem;">
                            <i class="bi bi-chevron-left me-1"></i>Anterior
                        </button>
                        <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                            ${btnsHtml}
                        </div>
                        <button type="button" class="btn-shadcn btn-shadcn-outline btn-next" ${paginaActual === totalPaginas ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''} style="padding:0.25rem 0.5rem; font-size:0.75rem;">
                            Siguiente<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                `;

                // Listeners de los botones
                paginadorWrapper.querySelector('.btn-prev')?.addEventListener('click', () => renderizarPagina(paginaActual - 1));
                paginadorWrapper.querySelector('.btn-next')?.addEventListener('click', () => renderizarPagina(paginaActual + 1));
                paginadorWrapper.querySelectorAll('.btn-pag').forEach(btn => {
                    btn.addEventListener('click', () => renderizarPagina(parseInt(btn.getAttribute('data-page'))));
                });
            }

            renderizarPagina(1);
        });
    }

    inicializarPaginacionTablas(10);

});
