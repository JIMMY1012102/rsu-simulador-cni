@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/panel-alumno.css') }}?v={{ filemtime(public_path('css/panel-alumno.css')) }}">
@endpush
@section('content')
@php
    $d = $dashboard;
    $esVistaPrevia = $esVistaPrevia ?? false;
    // Una sola plantilla para cinco áreas; los datos y la carrera vienen de MariaDB.
    $temaArea = config('panel_alumno.areas.'.$area->codigo);
    $rutaMascota = is_file(public_path($temaArea['mascota_webp']))
        ? $temaArea['mascota_webp']
        : $temaArea['mascota_svg'];
    $nombreCompleto = trim($usuario->nombres.' '.($usuario->apellidos ?? ''));
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
    $primeraPalabra = explode(' ', trim($usuario->nombres))[0];
@endphp
<div class="cni-alumno" data-area="{{ $area->codigo }}" id="inicio">
    <button type="button" class="cni-alumno-sidebar-toggle"
            data-sidebar-toggle aria-controls="cni-alumno-sidebar"
            aria-expanded="true" aria-label="Cerrar menú lateral">
        <span class="cni-alumno-sidebar-toggle-icon" aria-hidden="true">❮</span>
        <span class="cni-alumno-sidebar-toggle-text">Menú</span>
    </button>
    <button type="button" class="cni-alumno-sidebar-backdrop" data-sidebar-backdrop
            aria-label="Cerrar menú lateral" hidden></button>
    <aside class="cni-alumno-sidebar" id="cni-alumno-sidebar" aria-label="Menú del estudiante">
        <div class="cni-alumno-sidebar-top">
            <a class="cni-alumno-marca" href="{{ route('alumno.panel') }}">
                <span class="cni-alumno-marca-icon" aria-hidden="true">✦</span>
                <span><strong>Simulador</strong><small>CNI · Imperial</small></span>
            </a>
            <span class="cni-alumno-area-chip"><span aria-hidden="true">{{ $temaArea['icono'] }}</span> {{ $area->nombre }}</span>
            <nav class="cni-alumno-menu" aria-label="Navegación del alumno">
                <a href="#perfil"><span aria-hidden="true">♙</span> Mi perfil</a>
                <a href="#intentos"><span aria-hidden="true">▤</span> Mis intentos</a>
                <a href="#historial"><span aria-hidden="true">◷</span> Historial</a>
            </nav>
        </div>
        <div class="cni-alumno-sidebar-bottom">
            <div class="cni-alumno-side-art" aria-hidden="true">✦<span>✦</span>✦</div>
            <div class="cni-alumno-side-user"><span class="cni-alumno-avatar">{{ $iniciales }}</span><span><strong>{{ $nombreCompleto }}</strong><small>Estudiante · Área {{ $area->codigo }}</small></span></div>
            @unless($esVistaPrevia)
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="cni-alumno-salir" type="submit">↪ &nbsp; Cerrar sesión</button>
                </form>
            @endunless
        </div>
    </aside>

    <div class="cni-alumno-workspace">
        <header class="cni-alumno-topbar">
            <div class="cni-alumno-top-title"><span class="cni-alumno-topicon" aria-hidden="true">{{ $temaArea['icono'] }}</span><span><strong>{{ $area->nombre }}</strong><small>Simulador de Examen de Admisión CNI</small></span></div>
            <div class="cni-alumno-top-profile"><span class="cni-alumno-avatar">{{ $iniciales }}</span><span><strong>{{ $nombreCompleto }}</strong><small>Estudiante</small></span></div>
        </header>

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
                     alt="Mascota del área de {{ $area->nombre }}">
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

            <section class="cni-alumno-exams" id="intentos" aria-labelledby="simulacros-titulo">
                <span id="simulacros-disponibles" class="cni-alumno-anchor" aria-hidden="true"></span>
                <div class="cni-alumno-section-head"><div><span class="cni-alumno-section-kicker">PREPARACIÓN</span><h2 id="simulacros-titulo">Mis intentos · Iniciar Simulador CNI</h2></div><span class="cni-alumno-count">{{ $simulacros->count() }} disponibles</span></div>
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

            <section class="cni-alumno-history" id="historial" aria-labelledby="historial-titulo">
                <span id="mis-intentos" class="cni-alumno-anchor" aria-hidden="true"></span>
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
<script>
(() => {
    const panel = document.querySelector('.cni-alumno');
    if (!panel) return;
    const sidebar = panel.querySelector('#cni-alumno-sidebar');
    const toggle = panel.querySelector('[data-sidebar-toggle]');
    const backdrop = panel.querySelector('[data-sidebar-backdrop]');
    const links = [...sidebar.querySelectorAll('.cni-alumno-menu a')];
    const mobile = window.matchMedia('(max-width: 680px)');
    let abierto = !mobile.matches;

    const actualizar = () => {
        panel.classList.toggle('is-sidebar-collapsed', !abierto);
        panel.classList.toggle('is-sidebar-open', abierto);
        toggle.setAttribute('aria-expanded', String(abierto));
        toggle.setAttribute('aria-label', abierto ? 'Cerrar menú lateral' : 'Abrir menú lateral');
        toggle.querySelector('.cni-alumno-sidebar-toggle-icon').textContent = abierto ? '❮' : '☰';
        backdrop.hidden = !mobile.matches || !abierto;
        sidebar.inert = !abierto;
    };

    toggle.addEventListener('click', () => {
        abierto = !abierto;
        actualizar();
    });
    backdrop.addEventListener('click', () => {
        abierto = false;
        actualizar();
        toggle.focus();
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && mobile.matches && abierto) {
            abierto = false;
            actualizar();
            toggle.focus();
        }
    });
    links.forEach((enlace) => enlace.addEventListener('click', () => {
        if (!mobile.matches) return;
        abierto = false;
        actualizar();
    }));
    mobile.addEventListener('change', () => {
        abierto = !mobile.matches;
        actualizar();
    });
    actualizar();
})();
</script>
@endsection
