        </main>

        <!-- Footer Institucional SENA -->
        <footer class="shadcn-footer" style="padding: 1.25rem 2rem; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; font-size: 0.8125rem; color: var(--muted-foreground); flex-wrap: wrap; gap: 0.75rem; background: var(--card); margin-top: auto;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <img src="public/img/sena-logo.svg" alt="SENA" style="width: 20px; height: 20px; object-fit: contain;">
                <span><strong>SENA</strong> • Centro de Tecnologías Agroindustriales — CTA Cartago</span>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span>Control de Ingreso & Asistencia</span>
                <span style="opacity: 0.4;">|</span>
                <span style="color: var(--sena-brand); font-weight: 600;">SENA</span>
            </div>
        </footer>

    </div><!-- /.shadcn-main -->
</div><!-- /.shadcn-app -->

<!-- SCRIPTS DE LA PLANTILLA SHADCN (Modo oscuro, menú responsivo, etc.) -->
<script src="public/js/main.js"></script>
<script>

    
(function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (!token) return;

    document.querySelectorAll('form').forEach(function (f) {
        if ((f.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        if (f.querySelector('input[name="csrf_token"]')) return;
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'csrf_token'; i.value = token;
        f.appendChild(i);
    });

    const _fetch = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        const url = new URL(input instanceof Request ? input.url : input, location.href);
        const metodo = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
        if (url.origin === location.origin && metodo !== 'GET' && metodo !== 'HEAD') {
            const h = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
            if (!h.has('X-CSRF-Token')) h.set('X-CSRF-Token', token);
            init.headers = h;
        }
        return _fetch.call(this, input, init);
    };
})();
</script>

</body>
</html>

