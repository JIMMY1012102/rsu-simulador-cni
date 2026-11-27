<div class="cni-asistente" data-asistente>
<style>
.cni-asistente{--cni-ink:#172554;--cni-blue:#174ea6;--cni-violet:#6757d9;position:fixed;right:26px;bottom:24px;z-index:10000;font-family:inherit;color:var(--cni-ink)}
.cni-asistente *{box-sizing:border-box}
.cni-asistente__abrir{display:flex;align-items:center;gap:11px;border:1px solid #ffffff55;border-radius:999px;padding:7px 18px 7px 7px;background:linear-gradient(120deg,#123e83,#6354d9);color:#fff;font:inherit;font-weight:750;cursor:pointer;box-shadow:0 10px 30px #183c8060;transition:transform .2s,box-shadow .2s}
.cni-asistente__abrir:hover{transform:translateY(-3px);box-shadow:0 14px 34px #183c8075}
.cni-asistente__abrir:focus-visible,.cni-asistente button:focus-visible,.cni-asistente input:focus-visible{outline:3px solid #8db8ff;outline-offset:3px}
.cni-asistente__lanzador-avatar{width:43px;height:43px;display:grid;place-items:center;overflow:hidden;border-radius:50%;background:#fff;border:2px solid #ffffffaa}
.cni-asistente__lanzador-avatar img{width:100%;height:100%;object-fit:contain}
.cni-asistente__lanzador-copy{display:flex;flex-direction:column;align-items:flex-start;gap:2px}
.cni-asistente__lanzador-copy small{font-size:10px;font-weight:500;opacity:.82}
.cni-asistente__ventana{display:none;position:absolute;right:0;bottom:72px;width:min(390px,calc(100vw - 28px));height:min(570px,calc(100dvh - 115px));min-height:390px;background:#fff;border:1px solid #dce5f2;border-radius:23px;box-shadow:0 24px 75px #102b5540;overflow:hidden;flex-direction:column}
.cni-asistente.is-abierto .cni-asistente__ventana{display:flex;animation:cni-chat-in .22s ease-out}
@keyframes cni-chat-in{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.cni-asistente__cabecera{position:relative;display:flex;align-items:center;gap:12px;padding:17px 17px 16px;background:linear-gradient(120deg,#123e83,#6555d8);color:#fff}
.cni-asistente__cabecera:after{content:"";position:absolute;width:130px;height:130px;right:35px;top:-85px;border:1px solid #ffffff24;border-radius:50%;box-shadow:0 0 0 18px #ffffff0c,0 0 0 36px #ffffff09;pointer-events:none}
.cni-asistente__avatar{width:48px;height:48px;flex:none;display:grid;place-items:center;overflow:hidden;border-radius:16px;background:#fff;border:1px solid #ffffff80;box-shadow:0 4px 12px #102b5533}
.cni-asistente__avatar img{width:100%;height:100%;object-fit:contain}
.cni-asistente__identidad{min-width:0;flex:1;display:flex;flex-direction:column;gap:4px}
.cni-asistente__identidad strong{font-size:15px;letter-spacing:.1px}
.cni-asistente__estado{display:flex;align-items:center;gap:6px;font-size:11px;color:#e0eaff}
.cni-asistente__estado:before{content:"";width:7px;height:7px;border-radius:50%;background:#5df0b0;box-shadow:0 0 0 3px #5df0b022}
.cni-asistente__cerrar{position:relative;z-index:1;width:34px;height:34px;border:1px solid #ffffff42;border-radius:11px;background:#ffffff18;color:#fff;font-size:22px;line-height:1;cursor:pointer}
.cni-asistente__mensajes{padding:18px 15px;overflow:auto;flex:1;display:flex;flex-direction:column;gap:11px;background:radial-gradient(ellipse at top left,#f0f5ff 0,transparent 52%),#f8faff}
.cni-asistente__bienvenida{display:flex;align-items:flex-start;gap:9px;max-width:96%}
.cni-asistente__mini-avatar{width:29px;height:29px;flex:none;display:grid;place-items:center;overflow:hidden;border-radius:10px;background:#fff;border:1px solid #e0e7f3}
.cni-asistente__mini-avatar img{width:100%;height:100%;object-fit:contain}
.cni-asistente__mensaje{max-width:88%;padding:11px 13px;border:1px solid #e5eaf3;border-radius:5px 15px 15px 15px;background:#fff;color:#263653;box-shadow:0 3px 10px #1d35570a;white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.55}
.cni-asistente__bienvenida .cni-asistente__mensaje{max-width:100%}
.cni-asistente__mensaje--usuario{align-self:flex-end;border-color:#d9e5ff;border-radius:15px 5px 15px 15px;background:#e9f0ff;color:#183b76}
.cni-asistente__mensaje--error{border-color:#f5d2d2;background:#fff7f7;color:#9b2525}
.cni-asistente__consultando{display:flex;align-items:center;gap:8px;color:#64748b;font-size:12px}
.cni-asistente__puntos{display:flex;gap:3px}
.cni-asistente__puntos i{width:5px;height:5px;border-radius:50%;background:#6879ce;animation:cni-dot 1s infinite ease-in-out}
.cni-asistente__puntos i:nth-child(2){animation-delay:.15s}.cni-asistente__puntos i:nth-child(3){animation-delay:.3s}
@keyframes cni-dot{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-4px);opacity:1}}
.cni-asistente__pie{padding:12px 13px 13px;background:#fff;border-top:1px solid #edf0f6}
.cni-asistente__form{display:flex;align-items:center;gap:8px;padding:5px 5px 5px 13px;border:1px solid #dce4f0;border-radius:15px;background:#f9fbff;transition:border-color .2s,box-shadow .2s}
.cni-asistente__form:focus-within{border-color:#8299ed;box-shadow:0 0 0 3px #6b7ee51a}
.cni-asistente__entrada{width:100%;min-width:0;flex:1;border:0!important;outline:0!important;background:transparent!important;color:#1e293b;font:inherit;font-size:13px;padding:9px 0!important;box-shadow:none!important}
.cni-asistente__entrada::placeholder{color:#94a3b8}
.cni-asistente__enviar{width:38px;height:38px;flex:none;display:grid;place-items:center;border:0;border-radius:11px;background:linear-gradient(135deg,#174ea6,#6555d8);color:#fff;cursor:pointer;font-size:17px;transition:transform .15s,opacity .15s}
.cni-asistente__enviar:hover{transform:translateY(-1px)}.cni-asistente__enviar:disabled{opacity:.55;cursor:wait}
.cni-asistente__nota{margin:8px 3px 0;text-align:center;color:#9aa5b7;font-size:10px}
@media(max-width:480px){.cni-asistente{right:13px;bottom:13px}.cni-asistente__ventana{right:-3px;bottom:65px;width:min(390px,calc(100vw - 20px));height:min(590px,calc(100dvh - 92px));min-height:350px}.cni-asistente__abrir{padding-right:14px}}
@media(prefers-reduced-motion:reduce){.cni-asistente *{animation:none!important;transition:none!important}}
</style>
<section class="cni-asistente__ventana" role="dialog" aria-label="Asistente de inteligencia artificial" aria-modal="false">
    <header class="cni-asistente__cabecera">
        <span class="cni-asistente__avatar"><img src="{{ asset('images/gatito-menu-admin.webp') }}" alt="Mascota del asistente" width="48" height="48"></span>
        <span class="cni-asistente__identidad"><strong>Asistente CNI</strong><span class="cni-asistente__estado">En línea · listo para ayudarte</span></span>
        <button class="cni-asistente__cerrar" type="button" data-cerrar aria-label="Cerrar chat">×</button>
    </header>
    <div class="cni-asistente__mensajes" data-mensajes aria-live="polite" aria-relevant="additions">
        <div class="cni-asistente__bienvenida"><span class="cni-asistente__mini-avatar"><img src="{{ asset('images/gatito-menu-admin.webp') }}" alt="" width="29" height="29"></span><div class="cni-asistente__mensaje">¡Hola! Soy tu asistente de inteligencia artificial. ¿En qué puedo ayudarte?</div></div>
    </div>
    <div class="cni-asistente__pie">
        <form class="cni-asistente__form" data-form>
            <input class="cni-asistente__entrada" name="mensaje" maxlength="500" required placeholder="Escribe tu pregunta…" autocomplete="off" aria-label="Escribe tu pregunta">
            <button class="cni-asistente__enviar" type="submit" aria-label="Enviar mensaje">➤</button>
        </form>
        <p class="cni-asistente__nota">Asistente inteligente · Simulador CNI</p>
    </div>
</section>
<button class="cni-asistente__abrir" type="button" data-abrir aria-expanded="false" aria-label="Abrir asistente CNI">
    <span class="cni-asistente__lanzador-avatar"><img src="{{ asset('images/gatito-menu-admin.webp') }}" alt="" width="43" height="43"></span>
    <span class="cni-asistente__lanzador-copy">Asistente IA<small>¿Necesitas ayuda?</small></span>
</button>
<script>
(() => {
    const root = document.currentScript.closest('[data-asistente]');
    if (!root || root.dataset.inicializado) return;
    root.dataset.inicializado = '1';
    const open = root.querySelector('[data-abrir]');
    const close = root.querySelector('[data-cerrar]');
    const messages = root.querySelector('[data-mensajes]');
    const form = root.querySelector('[data-form]');
    const input = form.querySelector('input');
    const send = form.querySelector('button');
    const session = window.crypto?.randomUUID ? crypto.randomUUID() : String(Date.now());
    const add = (value, kind = '') => {
        const el = document.createElement('div');
        el.className = 'cni-asistente__mensaje' + (kind ? ' cni-asistente__mensaje--' + kind : '');
        el.textContent = value;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    };
    const toggle = (visible) => {
        root.classList.toggle('is-abierto', visible);
        open.setAttribute('aria-expanded', String(visible));
        if (visible) input.focus();
        else open.focus();
    };
    open.addEventListener('click', () => toggle(true));
    close.addEventListener('click', () => toggle(false));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && root.classList.contains('is-abierto')) toggle(false);
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const mensaje = input.value.trim();
        if (!mensaje || mensaje.length > 500) return;
        add(mensaje, 'usuario');
        input.value = '';
        input.disabled = send.disabled = true;
        const wait = document.createElement('div');
        wait.className = 'cni-asistente__consultando';
        wait.innerHTML = '<span class="cni-asistente__puntos" aria-hidden="true"><i></i><i></i><i></i></span> Estoy pensando…';
        messages.appendChild(wait);
        messages.scrollTop = messages.scrollHeight;
        try {
            const response = await fetch(@json(route('asistente.chat')), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'},
                body: JSON.stringify({mensaje, sesion: session})
            });
            const data = await response.json();
            wait.remove();
            if (!response.ok) throw new Error(data.message || 'No se pudo procesar tu consulta.');
            add(data.respuesta || 'El asistente no está disponible en este momento.');
        } catch (_) {
            wait.remove();
            add('No pude conectarme en este momento. Inténtalo nuevamente en un minuto.', 'error');
        } finally {
            input.disabled = send.disabled = false;
            input.focus();
        }
    });
})();
</script>
</div>
