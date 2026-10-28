/* Menú lateral común PR #30 · Accesible en las cuatro pantallas del alumno. */
(() => {
    function init() {
        const panel = document.querySelector('.cni-alumno');
        if (!panel) return;
        const sidebar = panel.querySelector('#cni-alumno-sidebar');
        const toggle = panel.querySelector('[data-sidebar-toggle]');
        const backdrop = panel.querySelector('[data-sidebar-backdrop]');
        if (!sidebar || !toggle || !backdrop) return;
        const mobile = window.matchMedia('(max-width: 680px)');
        let abierto = !mobile.matches;
        function actualizar() {
            panel.classList.toggle('is-sidebar-collapsed', !abierto);
            panel.classList.toggle('is-sidebar-open', abierto);
            toggle.setAttribute('aria-expanded', String(abierto));
            toggle.setAttribute('aria-label', abierto ? 'Cerrar menú lateral' : 'Abrir menú lateral');
            toggle.querySelector('.cni-alumno-sidebar-toggle-icon').textContent = abierto ? '❮' : '☰';
            backdrop.hidden = !mobile.matches || !abierto;
            sidebar.inert = !abierto;
        }
        toggle.addEventListener('click', () => { abierto = !abierto; actualizar(); });
        backdrop.addEventListener('click', () => { abierto = false; actualizar(); toggle.focus(); });
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && mobile.matches && abierto) {
                abierto = false; actualizar(); toggle.focus();
            }
        });
        sidebar.querySelectorAll('.cni-alumno-menu a').forEach(enlace => {
            enlace.addEventListener('click', () => {
                if (mobile.matches) { abierto = false; actualizar(); }
            });
        });
        mobile.addEventListener('change', () => { abierto = !mobile.matches; actualizar(); });
        actualizar();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});
    else init();
})();