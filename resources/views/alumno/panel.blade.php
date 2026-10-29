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
                <a href="#perfil" class="is-active" aria-current="location"><span aria-hidden="true">♙</span> Mi perfil</a>
                <a href="#progreso"><span aria-hidden="true">▤</span> Mi progreso</a>
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

            <section class="cni-alumno-vista cni-alumno-vista-perfil" id="perfil" data-panel-view="perfil" aria-label="Mi perfil">
                <div class="cni-alumno-banner" aria-labelledby="bienvenida-titulo">
                <div class="cni-alumno-welcome">
                    <span class="cni-alumno-overline">TU ESPACIO DE PREPARACIÓN · ÁREA {{ $area->codigo }}</span>
                    <h1 id="bienvenida-titulo">¡Hola, {{ $primeraPalabra }}!</h1>
                    <h2>Bienvenido a {{ $area->nombre }}</h2>
                    <p>{{ $temaArea['descripcion'] }}</p>
                    <blockquote>“{{ $temaArea['lema'] }}”</blockquote>

                    {{-- Botón real hacia el examen. Se habilita sólo para simulacros de su área. --}}
                    <div class="cni-alumno-exam-cta">
                        @if($esVistaPrevia)
                            <span class="cni-alumno-exam-unavailable">Los exámenes están disponibles al iniciar sesión.</span>
                        @elseif($simulacros->count() === 1)
                            @php $simulacroDirecto = $simulacros->first(); @endphp
                            <form method="POST" action="{{ route('examen.iniciar', ['simulacro' => $simulacroDirecto['id']]) }}">
                                @csrf
                                <button type="submit" class="cni-alumno-exam-button">
                                    <span aria-hidden="true">▶</span> Dar examen
                                    <span class="cni-alumno-exam-button-arrow" aria-hidden="true">→</span>
                                </button>
                                <small>{{ $simulacroDirecto['nombre'] }} · {{ $simulacroDirecto['total_preguntas'] }} preguntas</small>
                            </form>
                        @elseif($simulacros->isNotEmpty())
                            <button type="button" class="cni-alumno-exam-button" data-go-panel="#intentos">
                                <span aria-hidden="true">▶</span> Elegir simulacro
                                <span class="cni-alumno-exam-button-arrow" aria-hidden="true">→</span>
                            </button>
                            <small>{{ $simulacros->count() }} simulacros disponibles de tu área</small>
                        @else
                            <span class="cni-alumno-exam-unavailable" role="status">
                                Aún no hay simulacros disponibles para tu área.
                            </span>
                        @endif
                    </div>
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
            </section>

            <section class="cni-alumno-vista cni-alumno-vista-progreso" id="progreso" data-panel-view="progreso" aria-labelledby="progreso-titulo" hidden>
                @include('alumno.partials.progreso')
            </section>

            <section class="cni-alumno-vista cni-alumno-exams" id="intentos" data-panel-view="intentos" aria-labelledby="simulacros-titulo" hidden>
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

                @unless($carrera)
                    <div class="cni-alumno-profile cni-alumno-carrera-opcional" aria-label="Carrera de interés">
                        <h3>Carrera de interés</h3>
                        <p>Si tu simulacro requiere una carrera, puedes elegirla aquí. La elección es opcional para los exámenes generales del área.</p>
                        @unless($esVistaPrevia)
                            <form method="POST" action="{{ route('alumno.carrera.guardar') }}">@csrf
                                <label for="carrera_id">Selecciona una carrera de {{ $area->nombre }}</label>
                                <div class="cni-alumno-form-row">
                                    <select id="carrera_id" name="carrera_id" required>
                                        <option value="">Selecciona una carrera</option>
                                        @foreach($carreras as $opcion)
                                            <option value="{{ $opcion->id }}" @selected(old('carrera_id') == $opcion->id)>{{ $opcion->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="cni-alumno-primary" @disabled($carreras->isEmpty())>Guardar carrera</button>
                                </div>
                                @error('carrera_id')<p class="cni-alumno-error" role="alert">{{ $message }}</p>@enderror
                            </form>
                        @endunless
                    </div>
                @endunless
            </section>

            <section class="cni-alumno-vista cni-alumno-history" id="historial" data-panel-view="historial" aria-labelledby="historial-titulo" hidden>
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
    // Sólo una sección visible a la vez. Por defecto: portada + dos tarjetas.
    const vistas = [...panel.querySelectorAll('[data-panel-view]')];
    // #intentos es una vista secundaria accesible desde el botón "Rendir simulacro".
    const destinos = ['#perfil', '#progreso', '#historial', '#intentos'];
    const mostrarSeccion = (destino, navegar = false) => {
        const seleccion = destinos.includes(destino) ? destino : '#perfil';
        vistas.forEach((vista) => {
            vista.hidden = '#' + vista.id !== seleccion;
        });
        links.forEach((enlace) => {
            const activa = enlace.getAttribute('href') === seleccion;
            enlace.classList.toggle('is-active', activa);
            if (activa) enlace.setAttribute('aria-current', 'location');
            else enlace.removeAttribute('aria-current');
        });
        if (navegar) window.history.pushState(null, '', seleccion);
        window.scrollTo(0, 0);
    };
    links.forEach((enlace) => enlace.addEventListener('click', (evento) => {
        evento.preventDefault();
        mostrarSeccion(enlace.getAttribute('href'), true);
        if (mobile.matches) {
            abierto = false;
            actualizar();
            toggle.focus();
        }
    }));
    panel.querySelectorAll('[data-go-panel]').forEach((boton) => {
        boton.addEventListener('click', () => mostrarSeccion(boton.getAttribute('data-go-panel'), true));
    });
    window.addEventListener('popstate', () => mostrarSeccion(window.location.hash));
    window.addEventListener('hashchange', () => mostrarSeccion(window.location.hash));
    mostrarSeccion(window.location.hash);
    mobile.addEventListener('change', () => {
        abierto = !mobile.matches;
        actualizar();
    });
    actualizar();
})();
</script>
@endsection
