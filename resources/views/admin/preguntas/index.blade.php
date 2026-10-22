@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}">@endpush
@section('content')
<div class="admin">
    <div class="admin-head"><div><a href="{{ route('admin.inicio') }}">← Administración</a><h1>Banco de preguntas</h1><p>Preguntas válidas, imágenes y alternativas.</p></div>
        <a class="admin-btn" href="{{ route('admin.preguntas.create') }}">+ Nueva pregunta</a></div>
    @if(session('status'))<p class="admin-success" role="status">{{ session('status') }}</p>@endif
    <div class="admin-card">
        <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>ID</th><th>Asignatura</th><th>Tema</th><th>Pregunta</th><th>Nivel</th><th>Acciones</th></tr></thead><tbody>
        @forelse($preguntas as $p)
            <tr><td>#{{ $p->id }}</td><td>{{ $p->asignatura }}</td><td>{{ $p->tema }}</td>
                <td>{{ \Illuminate\Support\Str::limit($p->enunciado, 110) }} @if($p->imagen)📷@endif</td>
                <td>{{ $p->nivel }}</td><td class="admin-actions">
                    <a href="{{ route('admin.preguntas.edit', $p->id) }}">Editar</a>
                    <form method="POST" action="{{ route('admin.preguntas.destroy', $p->id) }}" onsubmit="return confirm('¿Desactivar esta pregunta?');">@csrf @method('DELETE')<button type="submit" class="admin-link">Desactivar</button></form>
                </td></tr>
        @empty<tr><td colspan="6">Aún no se han registrado preguntas.</td></tr>@endforelse
        </tbody></table></div>
        {{ $preguntas->links() }}
    </div>
</div>
@endsection
