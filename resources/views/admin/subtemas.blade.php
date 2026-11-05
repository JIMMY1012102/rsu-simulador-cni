@extends('admin.shell')
@section('admin-page', 'Categorías')
@section('admin-content')
<section class="cni-page-title cni-page-title--compact">
    <div><h1>Banco de preguntas</h1><p>CNI · Panel administrativo</p></div>
</section>
<section class="cni-card cni-table-card" aria-labelledby="subtemas-titulo">
    <div class="cni-table-card__head"><h2 id="subtemas-titulo">Categorías ({{ $temas->total() }})</h2></div>
    <div class="cni-table-scroll">
        <table class="cni-table">
            <thead><tr><th scope="col">Categoría</th><th scope="col">Curso</th><th scope="col">Preguntas activas</th><th scope="col">Estado</th></tr></thead>
            <tbody>
            @forelse($temas as $tema)
                <tr>
                    <td>{{ $tema->nombre }}</td><td>{{ $tema->curso }}</td><td>{{ $tema->preguntas_activas }}</td>
                    <td><span @class(['cni-bank-estado', 'is-listo' => $tema->activo && $tema->curso_activo && $tema->preguntas_activas > 0])>
                        {{ ! $tema->activo || ! $tema->curso_activo ? 'Inactivo' : ($tema->preguntas_activas > 0 ? 'Con preguntas' : 'Sin preguntas') }}
                    </span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="cni-bank-vacio">Aún no hay categorías registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($temas->hasPages())<div class="cni-pagination">{{ $temas->links() }}</div>@endif
</section>
@endsection
