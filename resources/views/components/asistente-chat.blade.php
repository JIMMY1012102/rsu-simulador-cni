@php
    $usuarioAsistente = auth('web')->user();
    $codigoAreaAsistente = $usuarioAsistente?->areaPostulacion?->codigo;
    $mascotaAsistente = config('panel_alumno.areas.'.$codigoAreaAsistente.'.mascota_png');
    $nombreAsistente = config('panel_alumno.areas.'.$codigoAreaAsistente.'.nombre_asistente', 'Asistente CNI');
    $rutaMascotaAsistente = $mascotaAsistente && file_exists(public_path($mascotaAsistente))
        ? $mascotaAsistente
        : 'images/escudo-cni.png';
    $mascotaAsistenteUrl = asset($rutaMascotaAsistente).'?v='.filemtime(public_path($rutaMascotaAsistente));
@endphp
<div class="cni-asistente" data-asistente data-habilitado="{{ config('asistente.enabled') ? '1' : '0' }}">
<style>
.cni-asistente{--cni-blue:#2563eb;--cni-cyan:#0891b2;--cni-ink:#17233f;--cni-muted:#71809a;position:fixed;right:26px;bottom:24px;z-index:10000;font-family:inherit;color:var(--cni-ink)}
.cni-asistente *{box-sizing:border-box}
.cni-asistente__abrir{display:flex;align-items:center;gap:11px;border:1px solid #dce7f8;border-radius:999px;padding:7px 18px 7px 7px;background:#fff;color:#17233f;font:inherit;font-weight:750;cursor:pointer;box-shadow:0 10px 32px #24436b25;transition:transform .2s,box-shadow .2s}
.cni-asistente__abrir:hover{transform:translateY(-3px);box-shadow:0 15px 35px #24436b35}
.cni-asistente__abrir:focus-visible,.cni-asistente button:focus-visible,.cni-asistente input:focus-visible{outline:3px solid #93c5fd;outline-offset:3px}
.cni-asistente__lanzador-avatar,.cni-asistente__avatar,.cni-asistente__mini-avatar{position:relative;display:grid;place-items:center;overflow:hidden;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#cffafe);border:2px solid #fff;box-shadow:0 0 0 2px #b9d5ff}
.cni-asistente__lanzador-avatar{width:45px;height:45px;flex:none}
.cni-asistente__lanzador-avatar img,.cni-asistente__avatar img,.cni-asistente__mini-avatar img{width:100%;height:100%;object-fit:contain}
.cni-asistente__lanzador-copy{display:flex;flex-direction:column;align-items:flex-start;gap:2px}
.cni-asistente__lanzador-copy small{font-size:10px;font-weight:500;color:#71809a}
.cni-asistente__ventana{display:none;position:absolute;right:0;bottom:76px;width:min(420px,calc(100vw - 28px));height:min(660px,calc(100dvh - 110px));min-height:440px;background:#fff;border:1px solid #e3eaf5;border-radius:28px;box-shadow:0 28px 90px #18365b30;overflow:hidden;flex-direction:column}
.cni-asistente.is-abierto .cni-asistente__ventana{display:flex;animation:cni-chat-in .22s ease-out}
@keyframes cni-chat-in{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.cni-asistente__cabecera{position:relative;display:flex;align-items:center;gap:13px;padding:17px 17px 16px;background:linear-gradient(115deg,#fff 0%,#f4f8ff 58%,#effbff 100%);border-bottom:1px solid #e8eef7}
.cni-asistente__avatar{width:49px;height:49px;flex:none;padding:2px}
.cni-asistente__identidad{min-width:0;flex:1;display:flex;flex-direction:column;gap:4px}
.cni-asistente__nombre{display:flex;align-items:center;gap:8px;font-size:16px;font-weight:800;letter-spacing:-.3px;color:#172b4d}
.cni-asistente__insignia{padding:3px 8px;border-radius:999px;background:#e8f1ff;color:#2563eb;font-size:9px;font-weight:800;letter-spacing:1px}
.cni-asistente__estado{display:flex;align-items:center;gap:7px;font-size:11px;color:#71809a}
.cni-asistente__estado:before{content:"";width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px #dcfce7}
.cni-asistente__cerrar{width:36px;height:36px;border:1px solid #e1e8f2;border-radius:12px;background:#fff;color:#64748b;font-size:21px;line-height:1;cursor:pointer;transition:background .2s}
.cni-asistente__cerrar:hover{background:#f1f5f9}
.cni-asistente__mensajes{padding:18px 17px;overflow:auto;flex:1;display:flex;flex-direction:column;gap:14px;background:radial-gradient(ellipse at 8% 0%,#edf5ff 0,transparent 44%),radial-gradient(ellipse at 100% 100%,#effbff 0,transparent 40%),#fbfdff}
.cni-asistente__bienvenida{display:flex;flex-direction:column;align-items:center;text-align:center;padding:17px 5px 9px;gap:9px}
.cni-asistente__bienvenida-avatar{width:86px;height:86px;padding:4px;margin-bottom:7px;border-radius:28px;background:linear-gradient(135deg,#dbeafe,#cffafe);box-shadow:0 12px 28px #3b82f625}
.cni-asistente__bienvenida-avatar img{width:100%;height:100%;object-fit:contain}
.cni-asistente__eyebrow{font-size:10px;font-weight:800;letter-spacing:2px;color:#3b82f6}
.cni-asistente__bienvenida h3{margin:0;color:#172b4d;font-size:26px;line-height:1.15;font-weight:850;letter-spacing:-.8px}
.cni-asistente__bienvenida p{margin:0 0 9px;color:#71809a;font-size:13px;line-height:1.55;max-width:290px}
.cni-asistente__bienvenida{position:relative;isolation:isolate;min-height:100%;width:100%;justify-content:center;align-self:center;padding:22px 16px 20px;gap:11px}
.cni-asistente__bienvenida:before{content:"";position:absolute;z-index:-1;top:13%;left:50%;width:min(260px,78vw);height:min(260px,78vw);transform:translateX(-50%);border-radius:50%;background:radial-gradient(circle,rgba(96,165,250,.20),rgba(34,211,238,.09) 48%,transparent 72%);filter:blur(8px);pointer-events:none}
.cni-asistente__bienvenida-avatar{position:relative;width:92px;height:92px;padding:6px;margin:0 auto 5px;border-radius:29px;background:linear-gradient(145deg,#2563eb,#06b6d4 55%,#8b5cf6);box-shadow:0 18px 42px rgba(37,99,235,.22),0 0 0 9px rgba(219,234,254,.65);animation:cni-mascota-float 4s ease-in-out infinite}
.cni-asistente__bienvenida-avatar:after{content:"";position:absolute;inset:4px;border:1px solid rgba(255,255,255,.75);border-radius:24px;pointer-events:none}
.cni-asistente__bienvenida-avatar img{position:relative;z-index:1;border-radius:23px;background:linear-gradient(145deg,#fff,#eff7ff)}
.cni-asistente__eyebrow{padding:6px 12px;border:1px solid #dbeafe;border-radius:999px;background:rgba(239,246,255,.9);font-size:9px;letter-spacing:2.1px}
.cni-asistente__bienvenida h3{font-size:clamp(27px,7vw,34px);line-height:1.12;letter-spacing:-1.1px;background:linear-gradient(100deg,#102d63 10%,#2563eb 58%,#0891b2);-webkit-background-clip:text;background-clip:text;color:transparent}
.cni-asistente__bienvenida p{width:min(100%,300px);max-width:300px;margin:0 auto;color:#64748b;font-size:13px;line-height:1.65}
@keyframes cni-mascota-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
.cni-asistente__mensajes{background:radial-gradient(ellipse at 50% 0%,rgba(219,234,254,.75),transparent 48%),radial-gradient(ellipse at 100% 100%,rgba(207,250,254,.42),transparent 42%),#f8fbff}
.cni-asistente__cabecera{background:linear-gradient(115deg,#fff 0%,#f4f8ff 58%,#effbff 100%)}
.cni-asistente__avatar{padding:2px;border:2px solid #fff;box-shadow:0 0 0 2px #b8d5ff,0 5px 16px rgba(37,99,235,.15)}
.cni-asistente__pie{background:rgba(255,255,255,.96);backdrop-filter:blur(14px)}
.cni-asistente__form{border-radius:19px;background:#f7faff}
.cni-asistente__enviar{width:46px;height:46px;border-radius:15px;background:linear-gradient(135deg,#1d4ed8,#0891b2);box-shadow:0 9px 20px rgba(37,99,235,.28)}
.cni-asistente__nota{letter-spacing:.15px}
.cni-asistente__abrir{border-color:#d8e5f8;background:rgba(255,255,255,.96);box-shadow:0 12px 34px rgba(20,55,105,.16)}
.cni-asistente__lanzador-avatar{box-shadow:0 0 0 2px #b9d5ff,0 5px 14px rgba(37,99,235,.18)}
.cni-asistente__fila{display:flex;align-items:flex-end;gap:9px;max-width:96%}
.cni-asistente__fila--usuario{align-self:flex-end;justify-content:flex-end}
.cni-asistente__mini-avatar{width:31px;height:31px;flex:none;padding:1px;border-radius:11px;background:#fff}
.cni-asistente__mensaje{max-width:calc(100% - 40px);padding:11px 14px;border:1px solid #e5eaf3;border-radius:6px 17px 17px 17px;background:#fff;color:#263653;box-shadow:0 4px 14px #1d35570a;white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.58}
.cni-asistente__fila--usuario .cni-asistente__mensaje{max-width:86%;border-color:#cfe0ff;border-radius:17px 6px 17px 17px;background:linear-gradient(135deg,#eaf2ff,#f0f6ff);color:#1d4789}
.cni-asistente__mensaje--error{border-color:#f5d2d2;background:#fff7f7;color:#9b2525}
.cni-asistente__consultando{display:flex;align-items:center;gap:9px;color:#71809a;font-size:12px}
.cni-asistente__puntos{display:flex;gap:4px;padding:13px 15px;border:1px solid #e5eaf3;border-radius:6px 17px 17px 17px;background:#fff}
.cni-asistente__puntos i{width:6px;height:6px;border-radius:50%;background:#5790e8;animation:cni-dot 1s infinite ease-in-out}
.cni-asistente__puntos i:nth-child(2){animation-delay:.15s}.cni-asistente__puntos i:nth-child(3){animation-delay:.3s}
@keyframes cni-dot{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-4px);opacity:1}}
.cni-asistente__pie{padding:12px 14px 13px;background:#fff;border-top:1px solid #edf0f6}
.cni-asistente__form{display:flex;align-items:center;gap:9px;padding:5px 5px 5px 16px;border:1px solid #dce5f2;border-radius:18px;background:#f8faff;transition:border-color .2s,box-shadow .2s}
.cni-asistente__form:focus-within{border-color:#8bb4f4;box-shadow:0 0 0 4px #3b82f614}
.cni-asistente__entrada{width:100%;min-width:0;flex:1;border:0!important;outline:0!important;background:transparent!important;color:#1e293b;font:inherit;font-size:13px;padding:10px 0!important;box-shadow:none!important}
.cni-asistente__entrada::placeholder{color:#9aa8bd}
.cni-asistente__enviar{width:43px;height:43px;flex:none;display:grid;place-items:center;border:0;border-radius:14px;background:linear-gradient(135deg,#2563eb,#0891b2);color:#fff;cursor:pointer;font-size:19px;box-shadow:0 7px 15px #2563eb30;transition:transform .15s,opacity .15s}
.cni-asistente__enviar:hover{transform:translateY(-1px)}.cni-asistente__enviar:disabled{opacity:.55;cursor:wait}
.cni-asistente__nota{margin:8px 3px 0;text-align:center;color:#9aa5b7;font-size:10px}
@media(max-width:480px){.cni-asistente{right:13px;bottom:13px}.cni-asistente__ventana{right:-3px;bottom:66px;width:min(420px,calc(100vw - 20px));height:min(680px,calc(100dvh - 92px));min-height:360px}.cni-asistente__abrir{padding-right:14px}.cni-asistente__bienvenida{min-height:100%;padding:14px 12px 16px;gap:9px}.cni-asistente__bienvenida-avatar{width:76px;height:76px;padding:5px;margin-bottom:2px;border-radius:25px}.cni-asistente__bienvenida-avatar:after{border-radius:20px}.cni-asistente__bienvenida-avatar img{border-radius:19px}.cni-asistente__bienvenida h3{font-size:clamp(21px,6vw,25px)}.cni-asistente__bienvenida p{font-size:12px;line-height:1.55}}
@media(max-height:620px){.cni-asistente__bienvenida{min-height:auto;padding:12px 10px;gap:8px}.cni-asistente__bienvenida-avatar{width:68px;height:68px;padding:4px;margin-bottom:0;border-radius:22px}.cni-asistente__bienvenida-avatar:after{border-radius:18px}.cni-asistente__bienvenida-avatar img{border-radius:17px}.cni-asistente__bienvenida h3{font-size:22px}.cni-asistente__bienvenida p{font-size:12px;line-height:1.45}}
@media(prefers-reduced-motion:reduce){.cni-asistente *{animation:none!important;transition:none!important}}
</style>
<section class="cni-asistente__ventana" role="dialog" aria-label="Asistente de inteligencia artificial" aria-modal="false">
    <header class="cni-asistente__cabecera">
        <span class="cni-asistente__avatar"><img src="{{ $mascotaAsistenteUrl }}" alt="Mascota de {{ $nombreAsistente }}" width="49" height="49"></span>
        <span class="cni-asistente__identidad">
            <span class="cni-asistente__nombre">{{ $nombreAsistente }} <span class="cni-asistente__insignia">IA</span></span>
            <span class="cni-asistente__estado">{{ config('asistente.enabled') ? 'Servicio habilitado · listo para ayudarte' : 'Servicio desactivado' }}</span>
        </span>
        <button class="cni-asistente__cerrar" type="button" data-cerrar aria-label="Cerrar chat">×</button>
    </header>
    <div class="cni-asistente__mensajes" data-mensajes aria-live="polite" aria-relevant="additions">
        <div class="cni-asistente__bienvenida" data-bienvenida>
            <div class="cni-asistente__bienvenida-avatar"><img src="{{ $mascotaAsistenteUrl }}" alt="" width="78" height="78"></div>
            <span class="cni-asistente__eyebrow">ASISTENTE IA · CNI</span>
            <h3>¡Hola! Soy {{ $nombreAsistente }}</h3>
            <p>{{ config('asistente.enabled') ? 'Soy tu asistente de inteligencia artificial. Puedo ayudarte con tus resultados, rendimiento, progreso, simulacros y práctica académica.' : 'El asistente está desactivado en este momento. El chat seguirá visible, pero podrás enviar consultas cuando el servicio vuelva a estar habilitado.' }}</p>

        </div>
    </div>
    <div class="cni-asistente__pie">
        <form class="cni-asistente__form" data-form>
            <input class="cni-asistente__entrada" name="mensaje" maxlength="500" required placeholder="Escribe tu pregunta…" autocomplete="off" aria-label="Escribe tu pregunta" @disabled(!config('asistente.enabled'))>
            <button class="cni-asistente__enviar" type="submit" aria-label="Enviar mensaje" @disabled(!config('asistente.enabled'))>↑</button>
        </form>
        <p class="cni-asistente__nota">Asistente inteligente · Simulador CNI</p>
    </div>
</section>
<button class="cni-asistente__abrir" type="button" data-abrir aria-expanded="false" aria-label="Abrir asistente CNI">
    <span class="cni-asistente__lanzador-avatar"><img src="{{ $mascotaAsistenteUrl }}" alt="" width="45" height="45"></span>
    <span class="cni-asistente__lanzador-copy">{{ $nombreAsistente }}<small>Tu asistente IA · ¿Necesitas ayuda?</small></span>
</button>
<script>
(() => {
    const root = document.currentScript.closest('[data-asistente]');
    if (!root || root.dataset.inicializado) return;
    root.dataset.inicializado = '1';
    const open = root.querySelector('[data-abrir]');
    const close = root.querySelector('[data-cerrar]');
    const messages = root.querySelector('[data-mensajes]');
    const welcome = root.querySelector('[data-bienvenida]');
    const form = root.querySelector('[data-form]');
    const input = form.querySelector('input');
    const send = form.querySelector('button');
    const mascotUrl = @json($mascotaAsistenteUrl);
    const session = window.crypto?.randomUUID ? crypto.randomUUID() : String(Date.now());
    const scrollBottom = () => { messages.scrollTop = messages.scrollHeight; };
    const add = (value, kind = 'bot') => {
        const row = document.createElement('div');
        row.className = 'cni-asistente__fila' + (kind === 'usuario' ? ' cni-asistente__fila--usuario' : '');
        if (kind !== 'usuario') {
            const avatar = document.createElement('span');
            avatar.className = 'cni-asistente__mini-avatar';
            const img = document.createElement('img');
            img.src = mascotUrl;
            img.alt = '';
            avatar.appendChild(img);
            row.appendChild(avatar);
        }
        const bubble = document.createElement('div');
        bubble.className = 'cni-asistente__mensaje' + (kind === 'error' ? ' cni-asistente__mensaje--error' : '');
        bubble.textContent = value;
        row.appendChild(bubble);
        messages.appendChild(row);
        scrollBottom();
        return row;
    };
    const toggle = visible => {
        root.classList.toggle('is-abierto', visible);
        open.setAttribute('aria-expanded', String(visible));
        if (visible) input.focus();
        else open.focus();
    };
    const sendMessage = async raw => {
        const mensaje = (raw || '').trim();
        if (!mensaje || mensaje.length > 500 || send.disabled) return;
        welcome?.remove();
        add(mensaje, 'usuario');
        input.value = '';
        input.disabled = send.disabled = true;
        const waiting = document.createElement('div');
        waiting.className = 'cni-asistente__consultando';
        const avatar = document.createElement('span');
        avatar.className = 'cni-asistente__mini-avatar';
        const img = document.createElement('img');
        img.src = mascotUrl;
        img.alt = '';
        avatar.appendChild(img);
        const dots = document.createElement('span');
        dots.className = 'cni-asistente__puntos';
        dots.innerHTML = '<i></i><i></i><i></i>';
        waiting.append(avatar, dots);
        messages.appendChild(waiting);
        scrollBottom();
        try {
            const response = await fetch(@json(route('asistente.chat')), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token())},
                body: JSON.stringify({mensaje, sesion: session})
            });
            const data = await response.json();
            waiting.remove();
            if (!response.ok) throw new Error(data.message || 'No se pudo procesar tu consulta.');
            add(data.respuesta || 'El asistente no está disponible en este momento.');
        } catch (_) {
            waiting.remove();
            add('No pude conectarme en este momento. Inténtalo nuevamente en un minuto.', 'error');
        } finally {
            input.disabled = send.disabled = false;
            input.focus();
        }
    };
    open.addEventListener('click', () => toggle(true));
    close.addEventListener('click', () => toggle(false));
        document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && root.classList.contains('is-abierto')) toggle(false);
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        sendMessage(input.value);
    });
})();
</script>
</div>
