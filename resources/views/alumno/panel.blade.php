@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-salud.css') }}?v=1">
@endpush
@section('content')
@php
    $d = $dashboard;
    $esVistaPrevia = $esVistaPrevia ?? false;
    $nombreArea = $area->nombre;
    $esSalud = $area->codigo === 'A';
    $disponible = $simulacros->isNotEmpty();
    $iniciales = mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1);
@endphp
<div class="cni-estudiante" id="inicio">
    <aside class="cni-est-sidebar" aria-label="Portal del estudiante">
        <a class="cni-est-logo" href="{{ $esVistaPrevia ? route('alumno.salud-preview') : route('alumno.panel') }}">
            <span class="cni-est-logo-mascot">✦</span>
            <span>Simulador<br>CNI</span>
        </a>
        <nav class="cni-est-nav" aria-label="Navegación del alumno">
            <a class="selected" href="#inicio" aria-current="page">⌂ <span>Inicio</span></a>
            <a href="#mis-intentos">▤ <span>Mis intentos</span></a>
            <a href="#mis-intentos">◷ <span>Historial</span></a>
            <a href="#carrera">♙ <span>Mi perfil</span></a>
        </nav>
        <div class="cni-est-sidebar-end">
            <span class="cni-est-small-paws" aria-hidden="true">✦　✦　✦</span>
            <div class="cni-est-sidebar-bottom">
                <div class="cni-est-area-info">Área elegida: <strong>{{ $area->nombre }}</strong></div>
                @unless($esVistaPrevia)
                <form action="{{ route('logout') }}" method="POST">@csrf
                    <button class="cni-est-logout" type="submit">⇥ Cerrar sesión</button>
                </form>
                @endunless
            </div>
        </div>
    </aside>
    <div class="cni-est-main">
        <header class="cni-est-topbar">
            <div class="cni-est-top-label"><span class="cni-est-topicon">{{ $esSalud ? '♥' : '✦' }}</span><strong>{{ $nombreArea }}</strong></div>
            <div class="cni-est-account"><span class="cni-est-avatar">{{ mb_strtoupper($iniciales) }}</span><span><strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong><small>Estudiante</small></span></div>
        </header>
        <main class="cni-est-content">
            @if($esVistaPrevia)
                <div class="cni-est-notice" role="status">Vista previa local · sin cuenta de alumno ni conexión a la base de datos. Las cifras están en cero hasta que existan resultados reales.</div>
            @endif
            @if(session('status'))<div class="cni-est-notice" role="status">{{ session('status') }}</div>@endif
            <section class="cni-est-hero" aria-labelledby="cni-est-bienvenida">
                <div class="cni-est-hero-copy">
                    <h1 id="cni-est-bienvenida">¡Hola, {{ $usuario->nombres }}!</h1>
                    <h2>Bienvenido al área de {{ $nombreArea }}</h2>
                    <p>Prepárate con preguntas tipo CNI, mejora tus conocimientos y alcanza tu meta.</p>
                    <blockquote>“La disciplina de hoy es el resultado de mañana”.</blockquote>
                </div>
                @if($esSalud)
                    <img class="cni-est-hero-mascot" src="{{ asset('images/alumno/pinguino-medicina.webp') }}" alt="Mascota del simulador CNI">
                @else
                    <div class="cni-est-area-letter" aria-hidden="true">{{ $area->codigo }}</div>
                @endif
                <article class="cni-est-progress">
                    <div class="cni-est-progress-line">
                        <div class="cni-est-progress-icon" aria-hidden="true">♡</div>
                        <div class="cni-est-progress-meter">
                            <div class="cni-est-meter-label"><strong>Tu progreso en {{ $nombreArea }}</strong><strong>{{ $d['progreso'] }}%</strong></div>
                            <div class="cni-est-meter-track" role="progressbar" aria-valuenow="{{ $d['progreso'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Simulacros disponibles completados"><span style="width:{{ $d['progreso'] }}%"></span></div>
                            <small>{{ $d['simulacrosCompletados'] }} de {{ $d['simulacrosDisponibles'] }} simulacros disponibles completados</small>
                        </div>
                    </div>
                    <div class="cni-est-stats">
                        <div><span>▤</span><strong>{{ $d['resueltas'] }}</strong><small>Preguntas evaluadas</small></div>
                        <div><span>★</span><strong>{{ $d['correctas'] }}</strong><small>Respuestas correctas</small></div>
                        <div><span>▥</span><strong>{{ $d['porcentajeAcierto'] }}%</strong><small>Porcentaje de acierto</small></div>
                    </div>
                </article>
            </section>
            <div class="cni-est-lower-grid">
                <section class="cni-est-start" id="simulacros">
                    <div class="cni-est-illustration" aria-hidden="true">▤</div>
                    <div><h2>Iniciar<br>Simulador CNI</h2><p>Responde preguntas tipo CNI del área de {{ $nombreArea }} y evalúa tu preparación.</p></div>
                    <div class="cni-est-start-actions">
                        @if($disponible)
                            <a class="cni-est-primary" href="#simulacros-disponibles">Comenzar ahora →</a>
                        @else
                            <span class="cni-est-primary cni-est-inactive">Sin simulacros disponibles</span>
                        @endif
                    </div>
                </section>
                <section class="cni-est-streak" aria-label="Racha de estudio"><div class="cni-est-streak-top"><span class="cni-est-flame">♨</span><div><strong>Racha de estudio</strong><h2>{{ $d['racha'] }} {{ $d['racha'] === 1 ? 'día' : 'días' }}</h2><small>¡Sigue así!</small></div></div><div class="cni-est-week">@foreach($d['semana'] as $dia)<div><span class="{{ $dia['actividad'] ? 'done' : '' }}" title="{{ $dia['fecha'] }}">{{ $dia['actividad'] ? '✓' : '○' }}</span><small>{{ $dia['dia'] }}</small></div>@endforeach</div></section>
            </div>
            <section class="cni-est-topics" id="temas">
                <div class="cni-est-section-title"><h2><span>▣</span> Temas de estudio</h2><small>Contenidos reales de los simulacros de tu área</small></div>
                @if($d['temas']->isNotEmpty())
                    <div class="cni-est-topic-grid">
                        @foreach($d['temas'] as $index => $tema)
                        <article class="cni-est-topic cni-est-tone-{{ $index % 6 }}"><span class="cni-est-topic-icon">{{ ['✚','✦','◇','⌘','◎','▣'][$index % 6] }}</span><div class="cni-est-topic-text"><strong>{{ $tema->nombre }}</strong><small>{{ $tema->asignatura }}</small><div class="cni-est-topic-bar"><span style="width:{{ $tema->avance }}%"></span></div><small>{{ $tema->realizadas }}/{{ $tema->total }} preguntas</small></div><span class="cni-est-topic-arrow" aria-hidden="true">→</span></article>
                        @endforeach
                    </div>
                @else
                    <div class="cni-est-empty">Todavía no hay temas con preguntas publicadas para los simulacros de tu área.</div>
                @endif
            </section>
            <section class="cni-est-exams" id="simulacros-disponibles"><div class="cni-est-section-title"><h2>Exámenes de tu área</h2></div><p class="cni-est-exam-help">Dar examen simulador · Elige un simulacro para comenzar.</p>
                @forelse($simulacros as $simulacro)
                    <article class="cni-est-exam-row"><div><strong>{{ $simulacro['nombre'] }}</strong><p>{{ $simulacro['total_preguntas'] }} preguntas · {{ $simulacro['tiempo_limite'] }} minutos</p></div><form method="POST" action="{{ route('examen.iniciar', ['simulacro' => $simulacro['id']]) }}">@csrf<button type="submit">Iniciar examen →</button></form></article>
                @empty
                    <p class="cni-est-empty">Por ahora no hay simulacros abiertos. Cuando el docente habilite uno, aparecerá aquí.</p>
                @endforelse
            </section>
            <section class="cni-est-exams" id="mis-intentos"><div class="cni-est-section-title"><h2>Mis últimos resultados</h2></div>
                @forelse($intentos as $intento)<article class="cni-est-exam-row"><div><strong>Intento #{{ $intento->id }}</strong><p>{{ $intento->fecha_inicio }}</p></div><a href="{{ route('alumno.resultados', ['intento' => $intento->id]) }}">Ver resultados · {{ $intento->puntaje_total }}</a></article>
                @empty<p class="cni-est-empty">Todavía no tienes exámenes calificados.</p>@endforelse
            </section>
            @unless($esVistaPrevia)
            <section class="cni-est-career" id="carrera">
                @if($carrera)<h2>Mi carrera de interés</h2><p>{{ $carrera->nombre }}</p>
                @else<h2>Carrera de interés (opcional)</h2><p>No necesitas elegir una carrera para rendir los simulacros de tu área.</p><form method="POST" action="{{ route('alumno.carrera.guardar') }}">@csrf<label for="carrera_id">Seleccionar carrera</label><select id="carrera_id" name="carrera_id" required><option value="">Selecciona una carrera</option>@foreach($carreras as $opcion)<option value="{{ $opcion->id }}" @selected(old('carrera_id') == $opcion->id)>{{ $opcion->nombre }}</option>@endforeach</select><button type="submit" @disabled($carreras->isEmpty())>Guardar carrera</button>@error('carrera_id')<p role="alert">{{ $message }}</p>@enderror</form>@endif
            </section>
            @endunless
        </main>
    </div>
</div>
@endsection
