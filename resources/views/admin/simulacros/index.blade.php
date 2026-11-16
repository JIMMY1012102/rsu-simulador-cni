@extends('admin.shell')
@section('admin-page', 'Simulacros')
@section('admin-content')
<section class="cni-page-title">
    <div><span class="cni-eyebrow">EVALUACIONES</span><h1>Simulacros</h1>
        <p>Gestiona los exámenes de preparación por área o carrera.</p></div>
    <a class="cni-button cni-button--primary" href="{{ route('admin.simulacros.create') }}"><span aria-hidden="true">＋</span> Crear simulacro</a>
</section>
<div class="cni-mini-overview"><span class="cni-mini-overview__icon cni-mini-overview__icon--teal" aria-hidden="true">▤</span>
    <span><strong>{{ number_format($simulacros->total()) }} simulacros registrados</strong><small>Los perfiles por carrera permiten adaptar la distribución de asignaturas.</small></span>
</div>
<section class="cni-card cni-table-card" aria-label="Listado de simulacros">
    <div class="cni-table-card__head"><div><h2>Exámenes configurados</h2><p>Revisa las evaluaciones existentes y su estado.</p></div>
    <span class="cni-table-count">{{ $simulacros->total() }} en total</span></div>
    <div class="cni-table-scroll">
        <table class="cni-table">
            <thead><tr><th scope="col">Simulacro</th><th scope="col">Perfil académico</th><th scope="col">Preguntas</th><th scope="col">Duración</th><th scope="col">Estado</th></tr></thead>
            <tbody>
            @forelse($simulacros as $s)
                <tr><td class="cni-question-cell"><span class="cni-id">#{{ str_pad((string) $s->id, 3, '0', STR_PAD_LEFT) }}</span><strong>{{ $s->nombre }}</strong></td>
                    <td><span class="cni-tag cni-tag--neutral">{{ $s->carrera ?? 'General de área' }}</span></td>
                    <td>{{ $s->total_preguntas }} preguntas</td><td class="cni-td-muted">{{ $s->tiempo_limite }} min</td>
                    <td><span @class(['cni-tag','cni-tag--low' => $s->activo,'cni-tag--neutral' => ! $s->activo])><span aria-hidden="true">●</span> {{ $s->activo ? 'Activo' : 'Inactivo' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="cni-empty"><span class="cni-empty__icon" aria-hidden="true">▤</span>
                    <h3>Aún no hay simulacros</h3><p>Cuando tengas suficientes preguntas, configura tu primera evaluación.</p>
                    <a class="cni-button cni-button--primary" href="{{ route('admin.simulacros.create') }}">Crear simulacro <span aria-hidden="true">→</span></a>
                </div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($simulacros->hasPages())<div class="cni-pagination">{{ $simulacros->links() }}</div>@endif
</section>
@endsection
