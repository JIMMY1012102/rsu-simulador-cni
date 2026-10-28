@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/panel-alumno.css') }}?v=1">
@endpush
@section('content')
@php
    $d = $dashboard;
    $paginaActiva = 'inicio';
    $esVistaPrevia = $esVistaPrevia ?? false;
    $esSalud = $area->codigo === 'A';
    $temaArea = config('panel_alumno.areas.'.$area->codigo);
    $rutaMascota = $esSalud
        ? 'images/alumno/pinguino-medicina.webp'
        : (is_file(public_path($temaArea['mascota_webp']))
            ? $temaArea['mascota_webp']
            : $temaArea['mascota_svg']);
    $nombreCompleto = trim($usuario->nombres.' '.($usuario->apellidos ?? ''));
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
    $primeraPalabra = explode(' ', trim($usuario->nombres))[0];
@endphp
<div class="cni-alumno" data-area="{{ $area->codigo }}" id="inicio">
    @include('alumno.partials.sidebar')

    <div class="cni-alumno-workspace">
        @include('alumno.partials.topbar')

        <main class="cni-alumno-content">
            @if($esVistaPrevia)
                <div class="cni-alumno-alert" role="status">Vista previa local · Sin inicio de sesión ni consultas a MySQL. Los indicadores están en cero porque no contienen datos ficticios.</div>
            @endif
            @if(session('status')) <div class="cni-alumno-alert cni-alumno-alert-ok" role="status">{{ session('status') }}</div> @endif

            <section class="cni-alumno-banner" aria-labelledby="bienvenida-titulo">
                <div class="cni-alumno-welcome">
                    <span class="cni-alumno-overline">TU ESPACIO DE PREPARACIÓN · ÁREA {{ $area->codigo }}</span>
                    <h1 id="bienvenida-titulo">¡Hola, {{ $primeraPalabra }}!</h1>
                    <h2>Bienvenido a {{ $area->nombre }}</h2>
                    <p>{{ $temaArea['descripcion'] }}</p>
                    <blockquote>“{{ $temaArea['lema'] }}”</blockquote>
                </div>
                <div class="cni-alumno-banner-pattern" aria-hidden="true"></div>
                <img class="cni-alumno-mascota"
                     src="{{ asset($rutaMascota) }}"
                     width="520" height="520"
                     alt="{{ $esSalud ? 'Pingüino médico azul del área Ciencias de la Salud' : 'Mascota del área de '.$area->nombre }}">
            </section>

            <div class="cni-alumno-metric-grid" aria-label="Resumen académico">
                <section class="cni-alumno-progress-card">
                    <div class="cni-alumno-card-heading"><span class="cni-alumno-circle" aria-hidden="true">▥</span><div><h2>Tu progreso en {{ $area->nombre }}</h2><p>Simulacros disponibles completados</p></div><strong class="cni-alumno-metric-value">{{ $d['progreso'] }}%</strong></div>
                    <div class="cni-alumno-progress-track" role="progressbar" aria-label="Progreso de simulacros" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $d['progreso'] }}"><span style="width:{{ $d['progreso'] }}%"></span></div>
                    <p class="cni-alumno-progress-note">{{ $d['simulacrosCompletados'] }} de {{ $d['simulacrosDisponibles'] }} simulacros abiertos completados</p>
                    <div class="cni-alumno-mini-stats">
                        <div><span class="blue" aria-hidden="true">▤</span><strong>{{ $d['resueltas'] }}</strong><small>Preguntas evaluadas</small></div>
                        <div><span class="mint" aria-hidden="true">★</span><strong>{{ $d['correctas'] }}</strong><small>Respuestas correctas</small></div>
                        <div><span class="indigo" aria-hidden="true">▥</span><strong>{{ $d['porcentajeAcierto'] }}%</strong><small>Porcentaje de acierto</small></div>
                    </div>
                </section>
                <section class="cni-alumno-streak-card" aria-label="Racha de estudio">
                    <div class="cni-alumno-streak-title"><span class="cni-alumno-fire" aria-hidden="true">♨</span><div><h2>Racha de estudio</h2><p><strong>{{ $d['racha'] }}</strong> {{ $d['racha'] === 1 ? 'día seguido' : 'días seguidos' }}</p></div></div>
                    <div class="cni-alumno-week" aria-label="Actividad de la semana">@foreach($d['semana'] as $dia)
                        <div><span @class(['done' => $dia['actividad']]) title="{{ $dia['fecha'] }}">{{ $dia['actividad'] ? '✓' : '○' }}</span><small>{{ $dia['dia'] }}</small></div>
                    @endforeach</div>
                    <p class="cni-alumno-streak-note">La racha se calcula con exámenes finalizados y calificados.</p>
                </section>
            </div>

            <section class="cni-alumno-topics" id="temas" aria-labelledby="temas-titulo">
                <div class="cni-alumno-section-head"><div><span class="cni-alumno-section-kicker">BANCO DE PREGUNTAS</span><h2 id="temas-titulo">Temas de estudio</h2></div><span class="cni-alumno-count">{{ $d['temas']->count() }} temas disponibles</span></div>
                @if($d['temas']->isNotEmpty())
                    <div class="cni-alumno-topic-grid">
                    @foreach($d['temas'] as $indice => $tema)
                        <article class="cni-alumno-topic tone-{{ $indice % 6 }}">
                            <div class="cni-alumno-topic-row"><span class="cni-alumno-topic-icon" aria-hidden="true">{{ ['✚','✦','◇','▤','◎','▣'][$indice % 6] }}</span><span class="cni-alumno-topic-name"><strong>{{ $tema->nombre }}</strong><small>{{ $tema->asignatura }}</small></span></div>
                            <div class="cni-alumno-topic-bar" role="progressbar" aria-label="Avance en {{ $tema->nombre }}" aria-valuenow="{{ $tema->avance }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $tema->avance }}%"></span></div>
                            <div class="cni-alumno-topic-foot"><span>{{ $tema->realizadas }}/{{ $tema->total }} preguntas vistas</span>
                                @if($esVistaPrevia)
                                    <span class="cni-alumno-topic-more" aria-label="Detalles no disponibles en vista previa">→</span>
                                @else
                                    <a class="cni-alumno-topic-more" href="{{ route('alumno.tema', ['tema' => $tema->id]) }}" aria-label="Ver detalles de {{ $tema->nombre }}">→</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                    </div>
                @else
                    <div class="cni-alumno-empty">No hay temas disponibles para los simulacros publicados de tu área. Cuando el colegio habilite preguntas, aparecerán aquí.</div>
                @endif
            </section>

            <section class="cni-alumno-exams" id="simulacros-disponibles" aria-labelledby="simulacros-titulo">
                <div class="cni-alumno-section-head"><div><span class="cni-alumno-section-kicker">PREPARACIÓN</span><h2 id="simulacros-titulo">Iniciar Simulador CNI</h2></div><span class="cni-alumno-count">{{ $simulacros->count() }} disponibles</span></div>
                <p class="cni-alumno-section-description">Exámenes de tu área · Dar examen simulador. Elige una evaluación activa de {{ $area->nombre }} para comenzar.</p>
                @forelse($simulacros as $simulacro)
                    <article class="cni-alumno-exam"><span class="cni-alumno-exam-icon" aria-hidden="true">▤</span><div><strong>{{ $simulacro['nombre'] }}</strong><small>{{ $simulacro['total_preguntas'] }} preguntas · {{ $simulacro['tiempo_limite'] }} minutos</small></div>
                        @if($esVistaPrevia)
                            <span class="cni-alumno-disabled">Solo en sesión real</span>
                        @else
                            <form method="POST" action="{{ route('examen.iniciar', ['simulacro' => $simulacro['id']]) }}">@csrf<button type="submit" class="cni-alumno-primary">Iniciar examen <span aria-hidden="true">→</span></button></form>
                        @endif
                    </article>
                @empty
                    <div class="cni-alumno-empty">No hay simulacros abiertos en este momento. Cuando el docente habilite uno, podrás iniciarlo desde aquí.</div>
                @endforelse
            </section>

            <section class="cni-alumno-history" id="mis-intentos" aria-labelledby="historial-titulo">
                <div class="cni-alumno-section-head"><div><span class="cni-alumno-section-kicker">TU HISTORIAL</span><h2 id="historial-titulo">Mis últimos resultados</h2></div></div>
                @forelse($intentos as $intento)
                    <article class="cni-alumno-history-row"><div><strong>Intento #{{ $intento->id }}</strong><small>{{ $intento->fecha_inicio }}</small></div><span class="cni-alumno-history-score">{{ number_format((float) $intento->puntaje_total, 2) }} pts</span>
                        @if($esVistaPrevia)
                            <span class="cni-alumno-disabled">Vista previa</span>
                        @else
                            <a href="{{ route('alumno.resultados', ['intento' => $intento->id]) }}">Ver resultados →</a>
                        @endif
                    </article>
                @empty
                    <div class="cni-alumno-empty">Aún no tienes evaluaciones calificadas. Tus resultados aparecerán aquí al terminar tus simulacros.</div>
                @endforelse
            </section>

            <section class="cni-alumno-profile" id="perfil" aria-labelledby="perfil-titulo"><div class="cni-alumno-section-head"><div><span class="cni-alumno-section-kicker">MI CUENTA</span><h2 id="perfil-titulo">Mi perfil académico</h2></div></div>
                <p><strong>Estudiante:</strong> {{ $nombreCompleto }}</p><p><strong>Área de postulación:</strong> {{ $area->nombre }}</p>
                @if($carrera)
                    <p><strong>Carrera de interés:</strong> {{ $carrera->nombre }}</p>
                @elseif(!$esVistaPrevia)
                    <p>Si deseas, puedes indicar una carrera de interés dentro de tu área. Es opcional para rendir exámenes.</p>
                    <form method="POST" action="{{ route('alumno.carrera.guardar') }}">@csrf
                        <label for="carrera_id">Carrera</label>
                        <div class="cni-alumno-form-row"><select id="carrera_id" name="carrera_id" required><option value="">Elige una carrera</option>@foreach($carreras as $opcion)<option value="{{ $opcion->id }}" @selected(old('carrera_id') == $opcion->id)>{{ $opcion->nombre }}</option>@endforeach</select><button type="submit" class="cni-alumno-primary" @disabled($carreras->isEmpty())>Guardar carrera</button></div>
                        @error('carrera_id')<p class="cni-alumno-error" role="alert">{{ $message }}</p>@enderror
                    </form>
                @endif
            </section>
        </main>
    </div>
</div>
@endsection
