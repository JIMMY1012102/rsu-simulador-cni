<div class="cni-asistente" data-asistente>
    <style>
        .cni-asistente {
            --cni-ia-ink: #14233f;
            --cni-ia-muted: #71809a;
            --cni-ia-primary: #2857c5;
            --cni-ia-accent: #7b61e8;
            position: fixed;
            right: 26px;
            bottom: 26px;
            z-index: 10000;
            color: var(--cni-ia-ink);
            font-family: inherit;
        }
        .cni-asistente *,
        .cni-asistente *::before,
        .cni-asistente *::after { box-sizing: border-box; }
        .cni-asistente__abrir {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            min-height: 62px;
            padding: 7px 20px 7px 8px;
            border: 1px solid rgba(255,255,255,.32);
            border-radius: 999px;
            color: #fff;
            background: linear-gradient(120deg, #173b75 0%, #2857c5 58%, #7358dc 100%);
            box-shadow: 0 12px 34px rgba(31, 65, 139, .32), inset 0 1px 0 rgba(255,255,255,.22);
            font: inherit;
            font-weight: 750;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .cni-asistente__abrir:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 38px rgba(31, 65, 139, .4), inset 0 1px 0 rgba(255,255,255,.22);
        }
        .cni-asistente__abrir:active { transform: translateY(-1px); }
        .cni-asistente__abrir-avatar {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            overflow: hidden;
            flex: 0 0 46px;
            border: 2px solid rgba(255,255,255,.8);
            border-radius: 50%;
            background: #fff;
        }
        .cni-asistente__abrir-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cni-asistente__abrir-texto { display: grid; gap: 2px; text-align: left; }
        .cni-asistente__abrir-texto strong { font-size: 14px; line-height: 1.2; }
        .cni-asistente__abrir-texto small { color: rgba(255,255,255,.78); font-size: 11px; font-weight: 500; }
        .cni-asistente__estado-punto {
            width: 8px;
            height: 8px;
            display: inline-block;
            margin-left: 4px;
            border: 1px solid rgba(255,255,255,.9);
            border-radius: 50%;
            background: #53e0a0;
            vertical-align: 1px;
        }
        .cni-asistente__ventana {
            position: absolute;
            right: 0;
            bottom: 78px;
            display: none;
            width: min(390px, calc(100vw - 32px));
            height: min(590px, calc(100dvh - 125px));
            min-height: 390px;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(220,228,244,.95);
            border-radius: 25px;
            background: #f7f9fe;
            box-shadow: 0 28px 85px rgba(20,35,63,.25), 0 4px 14px rgba(20,35,63,.08);
            transform-origin: bottom right;
        }
        .cni-asistente.is-abierto .cni-asistente__ventana {
            display: flex;
            animation: cni-ia-entrada .22s ease-out both;
        }
        @keyframes cni-ia-entrada {
            from { opacity: 0; transform: translateY(12px) scale(.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .cni-asistente__cabecera {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 17px 17px 16px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 0%, rgba(163,145,255,.42), transparent 34%),
                linear-gradient(125deg, #142f61 0%, #2454b6 62%, #6751ce 100%);
        }
        .cni-asistente__cabecera-avatar {
            width: 49px;
            height: 49px;
            flex: 0 0 49px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,.8);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(5,20,55,.2);
        }
        .cni-asistente__cabecera-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cni-asistente__cabecera-info { min-width: 0; flex: 1; }
        .cni-asistente__cabecera-info strong { display: block; font-size: 15px; letter-spacing: -.2px; }
        .cni-asistente__cabecera-info span {
            display: block;
            margin-top: 4px;
            color: rgba(255,255,255,.78);
            font-size: 11px;
        }
        .cni-asistente__cabecera-info span::before {
            content: "";
            width: 7px;
            height: 7px;
            display: inline-block;
            margin-right: 6px;
            border-radius: 50%;
            background: #55e0a1;
            box-shadow: 0 0 0 3px rgba(85,224,161,.15);
            vertical-align: 1px;
        }
        .cni-asistente__cerrar {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 11px;
            color: #fff;
            background: rgba(255,255,255,.12);
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            transition: background .18s ease;
        }
        .cni-asistente__cerrar:hover { background: rgba(255,255,255,.23); }
        .cni-asistente__mensajes {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 15px;
            overflow: auto;
            padding: 19px 15px 16px;
            background:
                radial-gradient(circle at 100% 100%, rgba(111,133,218,.07), transparent 35%),
                #f7f9fe;
            scrollbar-color: #c6d1e7 transparent;
            scrollbar-width: thin;
        }
        .cni-asistente__fila {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            max-width: 94%;
        }
        .cni-asistente__fila--usuario { align-self: flex-end; justify-content: flex-end; }
        .cni-asistente__mini-avatar {
            width: 29px;
            height: 29px;
            flex: 0 0 29px;
            overflow: hidden;
            border: 1px solid #e0e6f3;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(25,48,91,.08);
        }
        .cni-asistente__mini-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cni-asistente__mensaje {
            max-width: 100%;
            padding: 12px 14px;
            border: 1px solid #e5eaf4;
            border-radius: 17px 17px 17px 5px;
            color: #263653;
            background: #fff;
            box-shadow: 0 3px 12px rgba(30,52,91,.045);
            font-size: 13px;
            line-height: 1.55;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        .cni-asistente__fila--usuario .cni-asistente__mensaje {
            border-color: transparent;
            border-radius: 17px 17px 5px 17px;
            color: #fff;
            background: linear-gradient(135deg, #2857c5, #6556d5);
            box-shadow: 0 5px 14px rgba(52,79,177,.17);
        }
        .cni-asistente__escribiendo { display: inline-flex; align-items: center; gap: 4px; min-height: 18px; }
        .cni-asistente__escribiendo i {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #8392b0;
            animation: cni-ia-punto 1s ease-in-out infinite;
        }
        .cni-asistente__escribiendo i:nth-child(2) { animation-delay: .15s; }
        .cni-asistente__escribiendo i:nth-child(3) { animation-delay: .3s; }
        @keyframes cni-ia-punto {
            0%, 60%, 100% { opacity: .45; transform: translateY(0); }
            30% { opacity: 1; transform: translateY(-4px); }
        }
        .cni-asistente__pie {
            padding: 12px 13px 13px;
            border-top: 1px solid #e7ecf5;
            background: rgba(255,255,255,.96);
        }
        .cni-asistente__form {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 5px 5px 14px;
            border: 1px solid #dce4f1;
            border-radius: 16px;
            background: #f8faff;
            transition: border-color .18s ease, box-shadow .18s ease;
        }
        .cni-asistente__form:focus-within {
            border-color: #829ce1;
            box-shadow: 0 0 0 3px rgba(67,104,198,.1);
        }
        .cni-asistente__entrada {
            width: 100%;
            min-width: 0;
            padding: 9px 0;
            border: 0;
            outline: 0;
            color: #1c2d4b;
            background: transparent;
            font: inherit;
            font-size: 13px;
        }
        .cni-asistente__entrada::placeholder { color: #8b98ad; }
        .cni-asistente__enviar {
            width: 39px;
            height: 39px;
            display: grid;
            flex: 0 0 39px;
            place-items: center;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, #2857c5, #7058db);
            cursor: pointer;
            transition: transform .18s ease, opacity .18s ease;
        }
        .cni-asistente__enviar:hover:not(:disabled) { transform: translateY(-1px); }
        .cni-asistente__enviar:disabled { opacity: .55; cursor: wait; }
        .cni-asistente__enviar svg { width: 18px; height: 18px; }
        .cni-asistente__nota {
            margin: 8px 3px 0;
            color: #94a0b3;
            font-size: 10px;
            text-align: center;
        }
        .cni-asistente button:focus-visible,
        .cni-asistente input:focus-visible {
            outline: 3px solid #91a9ed;
            outline-offset: 3px;
        }
        @media (max-width: 520px) {
            .cni-asistente { right: 14px; bottom: 14px; }
            .cni-asistente__ventana {
                position: fixed;
                right: 10px;
                bottom: 88px;
                width: calc(100vw - 20px);
                height: min(620px, calc(100dvh - 112px));
                min-height: 360px;
                border-radius: 22px;
            }
            .cni-asistente__abrir { min-height: 56px; padding-right: 16px; }
            .cni-asistente__abrir-avatar { width: 42px; height: 42px; flex-basis: 42px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cni-asistente *,
            .cni-asistente *::before,
            .cni-asistente *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>

    @php($mascotaAsistente = asset('images/gatito-menu-admin.webp'))
    <section class="cni-asistente__ventana" role="dialog" aria-label="Asistente de inteligencia artificial" aria-hidden="true">
        <header class="cni-asistente__cabecera">
            <span class="cni-asistente__cabecera-avatar">
                <img src="{{ $mascotaAsistente }}" alt="Mascota asistente CNI" width="49" height="49">
            </span>
            <span class="cni-asistente__cabecera-info">
                <strong>Asistente CNI</strong>
                <span>Tu compañero de preparación · En línea</span>
            </span>
            <button class="cni-asistente__cerrar" type="button" data-cerrar aria-label="Cerrar chat">×</button>
        </header>

        <div class="cni-asistente__mensajes" data-mensajes aria-live="polite" aria-relevant="additions">
            <div class="cni-asistente__fila">
                <span class="cni-asistente__mini-avatar"><img src="{{ $mascotaAsistente }}" alt="" width="29" height="29"></span>
                <div class="cni-asistente__mensaje">¡Hola! 👋 Soy tu asistente CNI. Estoy aquí para ayudarte con tu rendimiento, tus resultados y tu preparación. ¿Qué te gustaría consultar?</div>
            </div>
        </div>

        <div class="cni-asistente__pie">
            <form class="cni-asistente__form" data-form>
                <input class="cni-asistente__entrada" name="mensaje" maxlength="500" required
                       placeholder="Escribe tu pregunta..." autocomplete="off" aria-label="Escribe tu pregunta">
                <button class="cni-asistente__enviar" type="submit" aria-label="Enviar mensaje">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 3 10.8 13.2M21 3l-6.5 18-3.7-7.8L3 9.5 21 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
            <p class="cni-asistente__nota">Asistente inteligente · Simulador CNI</p>
        </div>
    </section>

    <button class="cni-asistente__abrir" type="button" data-abrir aria-expanded="false" aria-label="Abrir asistente CNI">
        <span class="cni-asistente__abrir-avatar">
            <img src="{{ $mascotaAsistente }}" alt="" width="46" height="46" loading="lazy">
        </span>
        <span class="cni-asistente__abrir-texto">
            <strong>Asistente IA <span class="cni-asistente__estado-punto"></span></strong>
            <small>¿Necesitas ayuda?</small>
        </span>
    </button>

    <script>
        (() => {
            const root = document.currentScript.closest('[data-asistente]');
            if (!root || root.dataset.inicializado) return;
            root.dataset.inicializado = '1';

            const open = root.querySelector('[data-abrir]');
            const close = root.querySelector('[data-cerrar]');
            const windowChat = root.querySelector('.cni-asistente__ventana');
            const messages = root.querySelector('[data-mensajes]');
            const form = root.querySelector('[data-form]');
            const input = form.querySelector('input');
            const send = form.querySelector('button');
            const avatarUrl = @json($mascotaAsistente);
            const chatUrl = @json(route('asistente.chat'));
            const csrfToken = @json(csrf_token());
            const session = window.crypto?.randomUUID ? crypto.randomUUID() : String(Date.now());

            const addMessage = (value, kind = 'asistente') => {
                const row = document.createElement('div');
                row.className = 'cni-asistente__fila' + (kind === 'usuario' ? ' cni-asistente__fila--usuario' : '');

                if (kind !== 'usuario') {
                    const avatar = document.createElement('span');
                    avatar.className = 'cni-asistente__mini-avatar';
                    const image = document.createElement('img');
                    image.src = avatarUrl;
                    image.alt = '';
                    image.width = 29;
                    image.height = 29;
                    avatar.appendChild(image);
                    row.appendChild(avatar);
                }

                const bubble = document.createElement('div');
                bubble.className = 'cni-asistente__mensaje';
                bubble.textContent = value;
                row.appendChild(bubble);
                messages.appendChild(row);
                messages.scrollTop = messages.scrollHeight;
                return row;
            };

            const setOpen = (state) => {
                root.classList.toggle('is-abierto', state);
                open.setAttribute('aria-expanded', String(state));
                open.setAttribute('aria-label', state ? 'Cerrar asistente CNI' : 'Abrir asistente CNI');
                windowChat.setAttribute('aria-hidden', String(!state));
                if (state) input.focus();
                else open.focus();
            };

            open.addEventListener('click', () => setOpen(!root.classList.contains('is-abierto')));
            close.addEventListener('click', () => setOpen(false));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && root.classList.contains('is-abierto')) setOpen(false);
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const mensaje = input.value.trim();
                if (!mensaje || mensaje.length > 500 || send.disabled) return;

                addMessage(mensaje, 'usuario');
                input.value = '';
                input.disabled = true;
                send.disabled = true;

                const waiting = addMessage('');
                const waitingBubble = waiting.querySelector('.cni-asistente__mensaje');
                waitingBubble.setAttribute('aria-label', 'El asistente está escribiendo');
                waitingBubble.innerHTML = '<span class="cni-asistente__escribiendo" aria-hidden="true"><i></i><i></i><i></i></span>';

                try {
                    const response = await fetch(chatUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ mensaje, sesion: session })
                    });
                    const data = await response.json();
                    waiting.remove();

                    if (!response.ok) {
                        throw new Error(data.message || 'No se pudo completar la consulta.');
                    }
                    addMessage(data.respuesta || 'No recibí una respuesta. Inténtalo nuevamente.');
                } catch (_) {
                    waiting.remove();
                    addMessage('No pude conectarme en este momento. Inténtalo nuevamente en un minuto.');
                } finally {
                    input.disabled = false;
                    send.disabled = false;
                    input.focus();
                }
            });
        })();
    </script>
</div>
