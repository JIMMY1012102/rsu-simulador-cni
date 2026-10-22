<div class="cni-workspace">
    <link rel="stylesheet" href="{{ asset('css/docente-v2.css') }}">
    @include('livewire.docente.partials.navegacion')
    <div class="cni-main">
        <div class="cni-topline"><span>Portal académico <span aria-hidden="true">/</span> Calificaciones</span><span class="cni-role">Docente · {{ $seccion && $seccion->activo ? '5.º '.$seccion->nombre : 'Sin sección' }}</span></div>
        <main class="cni-content">
            <div class="cni-page-heading"><div><a class="cni-back" href="{{ route('docente.panel') }}">← Volver al inicio</a><div class="cni-eyebrow">SEGUIMIENTO ACADÉMICO</div><h1>Calificaciones de mi salón</h1><p>Consulta el último resultado calificado de cada estudiante de tu sección.</p></div><span class="cni-section-label">{{ $seccion && $seccion->activo ? '5.º '.$seccion->nombre : 'Sin sección asignada' }}</span></div>
            <section class="cni-metrics cni-metrics-three" aria-label="Indicadores de calificaciones">
                <article class="cni-metric"><div class="cni-metric-symbol">👥</div><span>Estudiantes activos</span><strong>{{ $totalEstudiantes }}</strong><small>Alumnos de mi salón</small></article>
                <article class="cni-metric"><div class="cni-metric-symbol">📝</div><span>Evaluaciones calificadas</span><strong>{{ $totalEvaluaciones }}</strong><small>Intentos registrados</small></article>
                <article class="cni-metric"><div class="cni-metric-symbol">📈</div><span>Promedio general</span><strong>{{ $promedioSeccion === null ? '—' : number_format($promedioSeccion, 2) }}</strong><small>Promedio de evaluaciones</small></article>
            </section>
            @if(!$seccion || !$seccion->activo)
                <div class="cni-notice" role="status">Tu cuenta docente no tiene una sección activa asignada. Solicita la asignación al administrador.</div>
            @else
                <section class="cni-surface cni-student-panel"><div class="cni-surface-head"><div><div class="cni-eyebrow">DETALLE POR ESTUDIANTE</div><h2>Estudiantes de 5.º {{ $seccion->nombre }}</h2><p>Incluye alumnos que aún no han rendido simulacros.</p></div><span class="cni-tag">{{ $totalEstudiantes }} estudiantes</span></div>
                    <div class="cni-table-wrap"><table class="cni-table"><thead><tr><th scope="col">Estudiante</th><th scope="col">Último simulacro calificado</th><th scope="col">Puntaje</th><th scope="col">Fecha</th></tr></thead><tbody>
                        @forelse($alumnos as $alumno)
                            <tr wire:key="alumno-{{ $alumno->estudiante_id }}"><td><div class="cni-student-name"><span class="cni-student-icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($alumno->nombres, 0, 1)) }}</span><strong>{{ $alumno->apellidos }}, {{ $alumno->nombres }}</strong></div></td><td>{{ $alumno->simulacro ?? 'Sin simulacros rendidos' }}</td><td>@if($alumno->puntaje_total === null)<span class="cni-tag muted">Sin nota</span>@else<span class="cni-grade">{{ number_format((float) $alumno->puntaje_total, 3) }}</span>@endif</td><td>{{ $alumno->fecha_inicio ? \Illuminate\Support\Carbon::parse($alumno->fecha_inicio)->format('d/m/Y') : '—' }}</td></tr>
                        @empty
                            <tr><td colspan="4"><div class="cni-empty"><div class="cni-empty-icon">👥</div><strong>No hay estudiantes registrados</strong><p>Los alumnos aparecerán cuando se asignen a esta sección.</p></div></td></tr>
                        @endforelse
                    </tbody></table></div>
                </section>
            @endif
        </main>
    </div>
</div>