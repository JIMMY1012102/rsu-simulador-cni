@php
    $dos = fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $porcentaje = $total > 0 ? (int) round($respondidas * 100 / $total) : 0;
    $elegida = $respuestas[$pregunta->id] ?? null;
    $tiempoInicial = $segundosRestantes === null ? null
        : sprintf('%02d:%02d:%02d', intdiv($segundosRestantes, 3600), intdiv($segundosRestantes % 3600, 60), $segundosRestantes % 60);
    $sinResponder = $preguntas->filter(fn ($p) => ($respuestas[$p->id] ?? null) === null);
    // Mientras viaja cualquiera de estas acciones, no se puede responder ni navegar.
    $ocupado = 'responder,siguiente,anterior,ir';
@endphp
@push('scripts')
    <script src="{{ asset('js/examen.js').'?v='.filemtime(public_path('js/examen.js')) }}"></script>
@endpush

{{-- x-data solo recibe valores fijos del intento: si cambiara en cada render, Alpine
     reiniciaría el estado y el reloj y el modal quedarían atados a una copia vieja. --}}
<div class="examen" x-data="examen({{ $intentoId }}, @js($limiteMs))" data-ahora="{{ $ahoraMs }}"
    @keydown.escape.window="confirmar = false">
    {{-- Encabezado de página --}}
    <section class="examen-encabezado">
        <div>
            <h1>Simulador de examen</h1>
            <p>{{ $simulacro ? 'Simulacro de admisión · '.$simulacro->nombre : 'Práctica · '.($pregunta->area ?? 'Por área') }} · Intento {{ $dos($numeroIntento) }}</p>
        </div>
        <span class="examen-estado">
            <img src="{{ asset('img/examen/indicador.svg') }}" width="6" height="6" alt="">
            Examen en curso
        </span>
    </section>

    {{-- Progreso y tiempo --}}
    <section class="examen-resumen" aria-label="Progreso y tiempo">
        <div class="examen-resumen__ficha">
            <span class="examen-resumen__simbolo"><img src="{{ asset('img/examen/clipboard-list.svg') }}" width="22" height="22" alt=""></span>
            <div>
                <strong>{{ $simulacro->nombre ?? 'Práctica' }}</strong>
                <small>{{ $total }} preguntas{{ $simulacro ? ' · '.$simulacro->tiempo_limite.' minutos' : '' }}</small>
            </div>
        </div>
        <span class="examen-separador" aria-hidden="true"></span>
        <div class="examen-progreso">
            <div class="examen-progreso__detalle">
                <span>{{ $respondidas }} de {{ $total }} respondidas</span>
                <strong>{{ $porcentaje }} %</strong>
            </div>
            <div class="examen-progreso__barra" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}"
                aria-valuenow="{{ $respondidas }}" aria-label="Preguntas respondidas">
                <span style="width: {{ $porcentaje }}%"></span>
            </div>
        </div>
        <span class="examen-separador" aria-hidden="true"></span>
        <div class="examen-tiempo">
            <img src="{{ asset('img/examen/timer.svg') }}" width="25" height="25" alt="">
            <div>
                <small>Tiempo restante</small>
                @if ($tiempoInicial)
                    <strong wire:ignore x-text="tiempo" role="timer" aria-live="off">{{ $tiempoInicial }}</strong>
                @else
                    <strong class="examen-tiempo__libre">Sin límite</strong>
                @endif
            </div>
        </div>
    </section>

    <div class="examen-columnas">
        {{-- Pregunta del examen --}}
        <section class="examen-pregunta" wire:key="pregunta-{{ $pregunta->id }}">
            <header class="examen-pregunta__encabezado">
                <h2>Pregunta {{ $actual + 1 }} <span>de {{ $total }}</span></h2>
                <span class="examen-etiqueta" title="{{ $pregunta->tema }}">
                    @if ($pregunta->bloque === 'habilidad_logico_matematica')
                        <img src="{{ asset('img/examen/calculator.svg') }}" width="14" height="14" alt="">
                    @endif
                    {{ $pregunta->area }}
                </span>
            </header>

            <div class="examen-pregunta__contenido">
                <div class="examen-enunciado">
                    <p>Lee con atención y selecciona una sola respuesta.</p>
                    <p class="examen-enunciado__texto" id="enunciado-{{ $pregunta->id }}">{!! nl2br(e($pregunta->enunciado)) !!}</p>
                    @if ($pregunta->imagen)
                        <img class="examen-enunciado__imagen" src="{{ asset($pregunta->imagen) }}" alt="Imagen de la pregunta {{ $actual + 1 }}">
                    @endif
                </div>

                <div class="examen-opciones" role="radiogroup" aria-labelledby="enunciado-{{ $pregunta->id }}">
                    @foreach ($pregunta->alternativas as $alternativa)
                        @php $marcada = $elegida === (int) $alternativa->id; @endphp
                        <button type="button" role="radio" aria-checked="{{ $marcada ? 'true' : 'false' }}"
                            @class(['examen-opcion', 'is-elegida' => $marcada])
                            wire:key="alternativa-{{ $alternativa->id }}"
                            wire:click="responder({{ $pregunta->id }}, {{ $alternativa->id }})"
                            wire:loading.attr="disabled" wire:target="{{ $ocupado }}">
                            <span class="examen-opcion__selector" aria-hidden="true"></span>
                            <span class="examen-opcion__letra">{{ $alternativa->letra }}.</span>
                            <span class="examen-opcion__texto">{{ $alternativa->texto }}</span>
                            @if ($marcada)
                                <img src="{{ asset('img/examen/check.svg') }}" width="18" height="18" alt="">
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="examen-revision">
                    <label class="examen-revisar">
                        <input type="checkbox" :checked="marcada({{ $pregunta->orden }})" @change="alternar({{ $pregunta->orden }})">
                        <span class="examen-revisar__casilla" aria-hidden="true"></span>
                        <img src="{{ asset('img/examen/flag.svg') }}" width="16" height="16" alt="">
                        Marcar para revisar
                    </label>
                    <span class="examen-guardado" aria-live="polite">
                        <span wire:loading wire:target="responder">Guardando…</span>
                        <span wire:loading.remove wire:target="responder" class="examen-guardado__estado">
                            @if ($elegida)
                                <img src="{{ asset('img/examen/cloud-check.svg') }}" width="16" height="16" alt="">
                                Respuesta guardada
                            @else
                                Sin responder
                            @endif
                        </span>
                    </span>
                </div>
            </div>

            <footer class="examen-navegacion">
                <button type="button" class="examen-boton" wire:click="anterior" @disabled($actual === 0)
                    wire:loading.attr="disabled" wire:target="{{ $ocupado }}">
                    <img src="{{ asset('img/examen/arrow-left.svg') }}" width="16" height="16" alt="">
                    Anterior
                </button>
                <div class="examen-navegacion__avanzar">
                    @if ($actual < $total - 1)
                        <button type="button" class="examen-omitir" wire:click="siguiente"
                            wire:loading.attr="disabled" wire:target="{{ $ocupado }}">Omitir pregunta</button>
                        <button type="button" class="examen-boton examen-boton--primario" wire:click="siguiente"
                            wire:loading.attr="disabled" wire:target="{{ $ocupado }}">
                            Siguiente
                            <img src="{{ asset('img/examen/arrow-right.svg') }}" width="16" height="16" alt="">
                        </button>
                    @else
                        <button type="button" class="examen-boton examen-boton--primario" @click="confirmar = true">
                            Revisar y enviar
                            <img src="{{ asset('img/examen/arrow-right.svg') }}" width="16" height="16" alt="">
                        </button>
                    @endif
                </div>
            </footer>
        </section>

        {{-- Panel de preguntas --}}
        <aside class="examen-panel">
            <section class="examen-mapa">
                <header class="examen-mapa__encabezado">
                    <div>
                        <h2>Mapa de preguntas</h2>
                        <img src="{{ asset('img/examen/layout-grid.svg') }}" width="18" height="18" alt="">
                    </div>
                    <p>Selecciona un número para ir a la pregunta.</p>
                </header>

                <nav class="examen-mapa__preguntas" aria-label="Mapa de preguntas">
                    @foreach ($preguntas as $indice => $item)
                        @php $respondida = ($respuestas[$item->id] ?? null) !== null; @endphp
                        <button type="button" wire:key="mapa-{{ $item->id }}" wire:click="ir({{ $indice }})"
                            wire:loading.attr="disabled" wire:target="{{ $ocupado }}"
                            @class(['examen-mapa__numero', 'is-respondida' => $respondida, 'is-actual' => $indice === $actual])
                            :class="{ 'is-revisar': marcada({{ $item->orden }}) }"
                            @if ($indice === $actual) aria-current="step" @endif
                            aria-label="Pregunta {{ $indice + 1 }}{{ $respondida ? ', respondida' : ', sin responder' }}">
                            {{ $dos($indice + 1) }}
                            <span class="examen-mapa__marca" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </nav>

                <p class="examen-mapa__actual"><span aria-hidden="true"></span>Pregunta actual: {{ $actual + 1 }}</p>
                <hr class="examen-mapa__separador">

                <ul class="examen-leyenda">
                    <li><span class="examen-leyenda__indicador is-respondida"></span>Respondidas<strong>{{ $dos($respondidas) }}</strong></li>
                    <li><span class="examen-leyenda__indicador is-revisar"></span>Para revisar<strong x-text="dosCifras(revisar.length)">00</strong></li>
                    <li><span class="examen-leyenda__indicador"></span>Sin responder<strong>{{ $dos($total - $respondidas) }}</strong></li>
                </ul>
            </section>

            <div class="examen-finalizar">
                <button type="button" class="examen-boton examen-boton--bloque" @click="confirmar = true">
                    Finalizar examen
                    <img src="{{ asset('img/examen/log-out.svg') }}" width="16" height="16" alt="">
                </button>
                <p>Podrás revisar tus respuestas antes de enviar el examen.</p>
            </div>
        </aside>
    </div>

    {{-- Confirmación (no está en Figma; usa los mismos estilos del frame) --}}
    <div class="examen-modal" x-show="confirmar" x-cloak x-transition.opacity @click.self="confirmar = false">
        <div class="examen-modal__caja" role="dialog" aria-modal="true" aria-labelledby="titulo-confirmar">
            <h2 id="titulo-confirmar">¿Enviar el examen?</h2>
            <p>Después de enviarlo ya no podrás cambiar tus respuestas.</p>

            <ul class="examen-leyenda">
                <li><span class="examen-leyenda__indicador is-respondida"></span>Respondidas<strong>{{ $respondidas }} de {{ $total }}</strong></li>
                <li><span class="examen-leyenda__indicador"></span>Sin responder<strong>{{ $total - $respondidas }}</strong></li>
                <li><span class="examen-leyenda__indicador is-revisar"></span>Para revisar<strong x-text="revisar.length">0</strong></li>
            </ul>

            @if ($sinResponder->isNotEmpty())
                <div class="examen-modal__grupo">
                    <p class="examen-modal__subtitulo">Preguntas sin responder</p>
                    <div class="examen-modal__numeros">
                        @foreach ($sinResponder as $indice => $item)
                            <button type="button" class="examen-mapa__numero" wire:click="ir({{ $indice }})" @click="confirmar = false">{{ $dos($indice + 1) }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
            <template x-if="revisar.length">
                <div class="examen-modal__grupo">
                    <p class="examen-modal__subtitulo">Marcadas para revisar</p>
                    <div class="examen-modal__numeros">
                        <template x-for="orden in revisar" :key="orden">
                            <button type="button" class="examen-mapa__numero is-revisar" x-text="dosCifras(orden)"
                                @click="$wire.ir(orden - 1); confirmar = false"></button>
                        </template>
                    </div>
                </div>
            </template>

            <div class="examen-modal__acciones">
                <button type="button" class="examen-boton" @click="confirmar = false">Volver al examen</button>
                <button type="button" class="examen-boton examen-boton--primario" wire:click="finalizar" wire:loading.attr="disabled" wire:target="finalizar">
                    <span wire:loading.remove wire:target="finalizar">Enviar examen</span>
                    <span wire:loading wire:target="finalizar">Enviando…</span>
                </button>
            </div>
        </div>
    </div>
</div>
