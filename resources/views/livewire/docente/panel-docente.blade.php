<div class="cni-workspace">
    <link rel="stylesheet" href="{{ asset('css/docente-v2.css') }}">
    @include('livewire.docente.partials.navegacion')
    <div class="cni-main">
        <div class="cni-topline"><span>Portal académico <span aria-hidden="true">/</span> Inicio</span><span class="cni-role">Docente · {{ $seccion && $seccion->activo ? '5.º '.$seccion->nombre : 'Sin sección' }}</span></div>
        <main class="cni-content">
            <section class="cni-hero">
                <div><div class="cni-eyebrow">COLEGIO NACIONAL DE IMPERIAL</div><h1>¡Bienvenido al portal docente!</h1><p>Organiza el seguimiento académico de tu salón desde un solo lugar.</p>
                    <a class="cni-hero-action" href="{{ route('docente.calificaciones') }}">Ver calificaciones <span aria-hidden="true">↗</span></a>
                </div><img src="{{ asset('img/escudo-cni.png') }}" alt="Escudo del Colegio Nacional de Imperial">
            </section>
            @if(!$seccion || !$seccion->activo)
                <div class="cni-notice" role="status">Tu cuenta no tiene una sección activa asignada. Solicita la asignación al administrador.</div>
            @endif
            <div class="cni-section-heading"><div><div class="cni-eyebrow">VISTA GENERAL</div><h2>Resumen de mi sección</h2></div><span class="cni-caption">Datos actualizados del sistema</span></div>
            <section class="cni-metrics" aria-label="Indicadores del salón">
                <article class="cni-metric"><div class="cni-metric-symbol">👥</div><span>Estudiantes activos</span><strong>{{ $totalEstudiantes }}</strong><small>Registrados en mi sección</small></article>
                <article class="cni-metric"><div class="cni-metric-symbol">📝</div><span>Evaluaciones calificadas</span><strong>{{ $totalEvaluaciones }}</strong><small>Intentos con nota</small></article>
                <article class="cni-metric"><div class="cni-metric-symbol">📚</div><span>Simulacros evaluados</span><strong>{{ $totalSimulacros }}</strong><small>Simulacros distintos</small></article>
                <article class="cni-metric"><div class="cni-metric-symbol">📈</div><span>Promedio del salón</span><strong>{{ $promedioSeccion === null ? '—' : number_format($promedioSeccion, 2) }}</strong><small>Sobre evaluaciones calificadas</small></article>
            </section>
            <div class="cni-section-heading cni-section-heading-second"><div><div class="cni-eyebrow">SEGUIMIENTO</div><h2>Actividad académica</h2></div><a class="cni-text-link" href="{{ route('docente.calificaciones') }}">Ver todo el salón →</a></div>
            <div class="cni-dashboard-grid">
                <section class="cni-surface" aria-label="Últimas calificaciones"><div class="cni-surface-head"><h3>Últimos resultados</h3><span class="cni-tag">Mi sección</span></div>
                    @forelse($calificaciones as $calificacion)
                        <div class="cni-result-item"><div class="cni-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($calificacion->nombres, 0, 1)) }}</div><div class="cni-result-body"><strong>{{ $calificacion->nombres }} {{ $calificacion->apellidos }}</strong><small>{{ $calificacion->simulacro }} · {{ $calificacion->fecha_inicio ? \Illuminate\Support\Carbon::parse($calificacion->fecha_inicio)->format('d/m/Y') : 'Sin fecha' }}</small></div><span class="cni-score">{{ number_format((float) $calificacion->puntaje_total, 2) }}</span></div>
                    @empty
                        <div class="cni-empty"><div class="cni-empty-icon">📋</div><strong>Sin resultados todavía</strong><p>Las calificaciones aparecerán aquí cuando tus estudiantes finalicen un simulacro.</p></div>
                    @endforelse
                </section>
                <section class="cni-surface cni-tools"><div class="cni-surface-head"><h3>Accesos del docente</h3></div>
                    <a href="{{ route('docente.calificaciones') }}" class="cni-tool"><span class="cni-tool-icon">▤</span><span><strong>Calificaciones de mi salón</strong><small>Consultar los resultados registrados</small></span><span aria-hidden="true">↗</span></a>
                    <div class="cni-tool is-pending"><span class="cni-tool-icon">▧</span><span><strong>Banco de preguntas</strong><small>Interfaz pendiente de habilitación</small></span><span class="cni-pending">Próximamente</span></div>
                    <div class="cni-tool is-pending"><span class="cni-tool-icon">◇</span><span><strong>Gestión de simulacros</strong><small>Interfaz pendiente de habilitación</small></span><span class="cni-pending">Próximamente</span></div>
                </section>
            </div>
        </main>
    </div>
</div>