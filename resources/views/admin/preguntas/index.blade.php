@extends('admin.shell')
@section('admin-page', 'Banco de preguntas')
@section('admin-content')
<section class="cni-page-title">
    <div>
        <span class="cni-eyebrow">CONTENIDO ACADÉMICO</span>
        <h1>Banco de preguntas</h1>
        <p>Organiza, revisa y administra las preguntas que alimentarán cada simulacro.</p>
    </div>
    <a class="cni-button cni-button--primary" href="{{ route('admin.preguntas.create') }}"><span aria-hidden="true">＋</span> Nueva pregunta</a>
</section>
<div class="cni-mini-overview">
    <span class="cni-mini-overview__icon" aria-hidden="true">✎</span>
    <span><strong>{{ number_format($preguntas->total()) }} preguntas activas</strong><small>Las preguntas están agrupadas por asignatura y tema.</small></span>
</div>
<section class="cni-card cni-table-card" aria-label="Listado de preguntas">
    <div class="cni-table-card__head">
        <div><h2>Preguntas registradas</h2><p>Consulta el contenido disponible en tu banco.</p></div>
        <span class="cni-table-count">{{ $preguntas->total() }} en total</span>
    </div>
    <div class="cni-table-scroll">
        <table class="cni-table">
            <thead><tr><th scope="col">Pregunta</th><th scope="col">Asignatura</th><th scope="col">Tema</th><th scope="col">Nivel</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            @forelse($preguntas as $p)
                <tr>
                    <td class="cni-question-cell"><span class="cni-id">#{{ str_pad((string) $p->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <strong>{{ \Illuminate\Support\Str::limit($p->enunciado, 96) }}</strong>
                        @if($p->imagen)<span class="cni-attachment">◧ Imagen adjunta</span>@endif
                    </td>
                    <td><span class="cni-tag cni-tag--neutral">{{ $p->asignatura }}</span></td>
                    <td class="cni-td-muted">{{ $p->tema }}</td>
                    <td><span @class(['cni-tag','cni-tag--low' => $p->nivel === 'basico','cni-tag--medium' => $p->nivel === 'intermedio','cni-tag--high' => $p->nivel === 'avanzado'])>{{ ucfirst($p->nivel) }}</span></td>
                    <td><div class="cni-table-actions">
                        <a class="cni-action-link" href="{{ route('admin.preguntas.edit', $p->id) }}">Editar <span aria-hidden="true">↗</span></a>
                        <form action="{{ route('admin.preguntas.destroy', $p->id) }}" method="POST" onsubmit="return confirm('¿Desactivar esta pregunta?');">@csrf @method('DELETE')
                            <button class="cni-action-link cni-action-link--danger" type="submit">Desactivar</button>
                        </form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="cni-empty"><span class="cni-empty__icon" aria-hidden="true">✎</span>
                    <h3>Tu banco está listo para comenzar</h3><p>Aún no se han registrado preguntas. Empieza creando la primera y agrega sus cuatro alternativas.</p>
                    <a class="cni-button cni-button--primary" href="{{ route('admin.preguntas.create') }}">Crear primera pregunta <span aria-hidden="true">→</span></a>
                </div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($preguntas->hasPages())<div class="cni-pagination">{{ $preguntas->links() }}</div>@endif
</section>
@endsection
