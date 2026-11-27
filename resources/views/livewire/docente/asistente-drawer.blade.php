@php($vistaDirectaAsistente = request()->is('docente-preview'))

<div class="cni-ai-widget" wire:keydown.escape="cerrarAsistente">
    <button type="button" class="cni-ai-launcher" wire:click="abrirAsistente" aria-controls="asistente-docente" aria-expanded="{{ $asistenteAbierto ? 'true' : 'false' }}">
        <span class="cni-ai-launcher-mascot">
            <img src="{{ asset('images/mascotas/asistente-siam-ilustracion-transparente.png') }}" alt="" aria-hidden="true">
        </span>
        <span class="cni-ai-launcher-label"><strong>Asistente IA</strong><small>Conversa con Davis</small></span>
        <span class="cni-ai-launcher-pulse" aria-hidden="true"></span>
    </button>

    @if($asistenteAbierto)
        <button type="button" class="cni-ai-backdrop" wire:click="cerrarAsistente" aria-label="Cerrar asistente"></button>
        <aside class="cni-ai-drawer" id="asistente-docente" role="dialog" aria-modal="true" aria-labelledby="asistente-titulo">
            <header class="cni-ai-drawer-header">
                <div class="cni-ai-drawer-brand">
                    <span class="cni-ai-drawer-avatar">
                        <img src="{{ asset('images/mascotas/asistente-siam-ilustracion-transparente.png') }}" alt="" aria-hidden="true">
                    </span>
                    <div class="cni-ai-drawer-copy">
                        <h2 id="asistente-titulo">Davis <span>· Asistente docente</span></h2>
                        <p class="cni-ai-header-greeting" aria-live="polite">¡Hola, soy Davis! ¿En qué te puedo ayudar?</p>
                    </div>
                </div>
                <div class="cni-ai-drawer-actions">
                    <button type="button" wire:click="nuevaConversacion" aria-label="Iniciar nueva conversación" title="Nueva conversación">＋</button>
                    <button type="button" wire:click="cerrarAsistente" aria-label="Cerrar asistente">×</button>
                </div>
            </header>

            <div class="cni-ai-drawer-description">
                @if($vistaDirectaAsistente)
                    Vista directa con Gemini: puedes hacer consultas pedagógicas, sin datos reales de estudiantes.
                @else
                    Puedo proponer actividades y estrategias y analizar los resultados académicos agregados de tu sección.
                @endif
            </div>

            <div class="cni-ai-messages" aria-live="polite" aria-relevant="additions">
                @forelse($mensajes as $mensaje)
                    @if($mensaje['role'] === 'user')
                        <article class="cni-ai-message is-user">
                            <strong>Tú</strong>
                            <p>{{ $mensaje['text'] }}</p>
                        </article>
                    @else
                        <article class="cni-ai-message is-assistant">
                            <span class="cni-ai-message-avatar" aria-hidden="true">
                                <span class="cni-ai-avatar-art"><img src="{{ asset('images/mascotas/asistente-davis-pensativo.png') }}" alt=""></span>
                                <span class="cni-ai-question-mark">?</span><span class="cni-ai-question-mark is-second">?</span>
                            </span>
                            <div class="cni-ai-message-body">
                                <div class="cni-ai-answer">{!! Illuminate\Support\Str::markdown($mensaje['text'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="cni-ai-welcome">
                        <img class="cni-ai-welcome-mascot" src="{{ asset('images/mascotas/asistente-siam-ilustracion-transparente.png') }}" alt="" aria-hidden="true">
                        <p>Puedo ayudarte con estrategias de enseñanza, actividades y reforzamiento académico.</p>
                    </div>
                @endforelse
                <div wire:loading wire:target="enviarPregunta" role="status" class="cni-ai-loading cni-ai-typing">
                    <span class="cni-ai-message-avatar" aria-hidden="true">
                        <span class="cni-ai-avatar-art"><img src="{{ asset('images/mascotas/asistente-davis-pensativo.png') }}" alt=""></span>
                        <span class="cni-ai-question-mark">?</span><span class="cni-ai-question-mark is-second">?</span>
                    </span>
                    <span><strong>Davis</strong><span class="cni-ai-typing-dots">Está pensando<span>…</span></span></span>
                </div>
            </div>

            <div class="cni-ai-suggestions" aria-label="Preguntas sugeridas">
                @foreach(($preguntasSugeridas ?? ['¿Qué actividad breve ayuda a reforzar comprensión lectora?', 'Propón una actividad de aprendizaje activo para mi sección.', '¿Cómo puedo analizar un simulacro y planificar el siguiente refuerzo?']) as $sugerencia)
                    <button type="button" wire:click="usarSugerencia(@js($sugerencia))">{{ $sugerencia }}</button>
                @endforeach
            </div>

            <form class="cni-ai-composer" wire:submit="enviarPregunta">
                <textarea wire:model="pregunta" wire:loading.attr="disabled" wire:target="enviarPregunta" rows="2" maxlength="2000" aria-label="Pregunta para el asistente IA" placeholder="Escribe tu pregunta..."></textarea>
                @error('pregunta') <p class="cni-ai-validation">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="enviarPregunta"><span wire:loading.remove wire:target="enviarPregunta">Enviar</span><span wire:loading wire:target="enviarPregunta">Pensando…</span><span aria-hidden="true">➤</span></button>
            </form>
            @if($errorAsistente)<p role="alert" class="cni-ai-error">{{ $errorAsistente }}</p>@endif
        </aside>
    @endif
</div>
