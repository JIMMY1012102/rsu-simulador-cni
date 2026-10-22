@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}">@endpush
@section('content')
<div class="admin">
    <div class="admin-head"><div><a href="{{ route('admin.inicio') }}">← Administración</a><h1>Simulacros</h1><p>Un simulacro general por área o exclusivo de una carrera.</p></div>
    <a class="admin-btn" href="{{ route('admin.simulacros.create') }}">+ Crear simulacro</a></div>
    @if(session('status'))<p class="admin-success" role="status">{{ session('status') }}</p>@endif
    <section class="admin-card"><div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Nombre</th><th>Perfil</th><th>Preguntas</th><th>Duración</th><th>Estado</th></tr></thead><tbody>
    @forelse($simulacros as $s)<tr>
        <td>{{ $s->nombre }}</td><td>{{ $s->carrera ?? 'General de área' }}</td>
        <td>{{ $s->total_preguntas }}</td><td>{{ $s->tiempo_limite }} min</td><td>{{ $s->activo ? 'Activo' : 'Inactivo' }}</td>
    </tr>@empty<tr><td colspan="5">Todavía no hay simulacros.</td></tr>@endforelse
    </tbody></table></div>{{ $simulacros->links() }}</section>
</div>
@endsection
