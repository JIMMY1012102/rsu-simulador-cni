@php($img = fn (string $archivo) => asset('images/alumno/ciencias-basicas/'.$archivo))
<div class="ini-contenido">
    <section class="ini-bienvenida" aria-labelledby="ini-saludo">
        <div class="ini-escena">
            <img src="{{ $img('escena-laboratorio@2x.png') }}" width="652" height="454"
                 alt="Mascota científica con libros de Matemática, Física, Química y Biología">
        </div>
        <div class="ini-mensaje">
            <h2 id="ini-saludo">¡Hola, <span>{{ $nombreCorto }}!</span></h2>
            <p class="ini-mensaje-sub">Bienvenido al área de {{ $area->nombre }}</p>
            <p class="ini-mensaje-desc">Fortalece tus conocimientos en Matemática, Física, Química y más, y prepárate para el examen CNI.</p>
            <blockquote class="ini-cita">
                <span aria-hidden="true">“</span>
                <p>La ciencia de hoy es<br>la tecnología del mañana.”</p>
            </blockquote>
        </div>
        <div class="ini-progreso">
            <div class="ini-progreso-avance">
                <span class="ini-emblema ini-emblema-grande"><img src="{{ $img('atom-progreso.svg') }}" width="47" height="47" alt=""></span>
                <div class="ini-progreso-barra">
                    <p>Tu progreso en {{ $area->nombre }}</p>
                    <div class="ini-progreso-fila">
                        <div class="ini-pista" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                             aria-valuenow="{{ $avance ?? 0 }}" aria-label="Avance sobre el banco de preguntas">
                            <span style="width: {{ $avance ?? 0 }}%"></span>
                        </div>
                        <strong>{{ $avance === null ? '—' : $avance.'%' }}</strong>
                    </div>
                </div>
            </div>
            <dl class="ini-resultados">
                <div>
                    <img src="{{ $img('file-question.svg') }}" width="29" height="29" alt="">
                    <div><dt>Preguntas resueltas</dt><dd>{{ $resueltas }}</dd></div>
                </div>
                <div>
                    <img src="{{ $img('star.svg') }}" width="29" height="29" alt="">
                    <div><dt>Respuestas correctas</dt><dd>{{ $correctas }}</dd></div>
                </div>
                <div>
                    <img src="{{ $img('chart-column-increasing.svg') }}" width="29" height="29" alt="">
                    <div><dt>Porcentaje de acierto</dt><dd>{{ $acierto === null ? '—' : $acierto.'%' }}</dd></div>
                </div>
            </dl>
        </div>
    </section>

    <section class="ini-practica">
        <article class="ini-simulador">
            <img class="ini-simulador-icono" src="{{ $img('file-pen-line.svg') }}" width="102" height="102" alt="">
            <div class="ini-simulador-texto">
                <h2>Iniciar<br>Simulador CNI</h2>
                <p>Responde preguntas tipo CNI del área de {{ $area->nombre }} y evalúa tu preparación.</p>
                @if ($aviso)
                    <p class="ini-simulador-aviso" role="status">{{ $aviso }}</p>
                @endif
            </div>
            {{-- Mismo inicio de examen que el panel principal: reanuda el intento abierto o crea uno. --}}
            <form method="POST" action="{{ $simulacro ? route('examen.iniciar', ['simulacro' => $simulacro['id']]) : '' }}">
                @csrf
                <button type="submit" class="ini-boton" @disabled(! $simulacro)>
                    {{ $enCurso ? 'Continuar simulacro' : 'Comenzar ahora' }}
                    <img src="{{ $img('arrow-right-blanco.svg') }}" width="25" height="25" alt="">
                </button>
            </form>
        </article>

        <article class="ini-racha">
            <div class="ini-racha-resumen">
                <img src="{{ $img('llama@2x.png') }}" width="45" height="45" alt="">
                <div>
                    <h2>Racha de estudio</h2>
                    <p class="ini-racha-dias">{{ $racha }} {{ $racha === 1 ? 'día' : 'días' }}</p>
                    <p class="ini-racha-motivo">{{ $racha ? '¡Sigue así!' : '¡Empieza hoy!' }}</p>
                </div>
            </div>
            <ol class="ini-semana">
                @foreach ($semana as $dia)
                    <li @class(['ini-dia-hecho' => $dia['hecho']])>
                        <span>{{ $dia['hecho'] ? '✓' : '' }}</span>{{ $dia['etiqueta'] }}
                    </li>
                @endforeach
            </ol>
        </article>
    </section>

    <section class="ini-temas" aria-labelledby="ini-temas-titulo">
        <div class="ini-temas-encabezado">
            <h2 id="ini-temas-titulo"><img src="{{ $img('libro@2x.png') }}" width="33" height="33" alt="">Temas de estudio</h2>
            @if ($hayMasMaterias)
                <button type="button" class="ini-ver-todos" wire:click="$toggle('verTodas')">{{ $verTodas ? 'Ver menos' : 'Ver todos' }}</button>
            @endif
        </div>
        @if ($materias->isEmpty())
            <p class="ini-vacio">Aún no hay materias registradas en el sistema. Aparecerán aquí cuando el docente las cree.</p>
        @endif
        <ul class="ini-materias">
            @foreach ($materias as $materia)
                <li class="ini-materia ini-tono-{{ $materia['tono'] }}" wire:key="materia-{{ $materia['id'] }}">
                    <span class="ini-materia-icono"><img src="{{ $img($materia['icono'].'.svg') }}" width="49" height="49" alt=""></span>
                    <div class="ini-materia-info">
                        <h3>{{ $materia['nombre'] }}</h3>
                        <p>{{ $materia['preguntas'] ? $materia['preguntas'].' preguntas' : 'Sin preguntas aún' }}</p>
                    </div>
                    <span class="ini-materia-abrir" title="El contenido de las materias aún no está disponible">
                        <img src="{{ $img('arrow-right-'.$materia['flecha'].'.svg') }}" width="23" height="23" alt="">
                    </span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
