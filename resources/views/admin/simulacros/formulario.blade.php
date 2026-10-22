@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}">@endpush
@section('content')
<div class="admin admin-narrow">
    <a href="{{ route('admin.simulacros.index') }}">← Simulacros</a><h1>Configurar nuevo simulacro</h1>
    <p>Selecciona el área y, si corresponde, una carrera específica. Define las preguntas de cada asignatura según el banco disponible.</p>
    <section class="admin-card">
        <form method="POST" action="{{ route('admin.simulacros.store') }}">@csrf
            <label>Nombre<input name="nombre" required maxlength="150" value="{{ old('nombre') }}" placeholder="Simulacro Ingeniería de Sistemas"></label>
            <label>Área de postulación<select name="area_postulacion_id" required><option value="">Selecciona...</option>
                @foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('area_postulacion_id') == $area->id)>{{ $area->codigo }} — {{ $area->nombre }}</option>@endforeach
            </select></label>
            <label>Carrera (opcional: en blanco es un examen general del área)<select name="carrera_id">
                <option value="">Examen general del área</option>
                @foreach($carreras as $c)<option value="{{ $c->id }}" @selected(old('carrera_id') == $c->id)>{{ $c->nombre }}</option>@endforeach
            </select></label>
            <label>Duración en minutos<input type="number" name="tiempo_limite" min="1" max="65535" required value="{{ old('tiempo_limite', 120) }}"></label>
            <div class="admin-grid-dos">
                <label>Fecha y hora de inicio<input type="datetime-local" name="fecha_inicio" required value="{{ old('fecha_inicio', now()->format('Y-m-d\TH:i')) }}"></label>
                <label>Fecha y hora de fin<input type="datetime-local" name="fecha_fin" required value="{{ old('fecha_fin', now()->addDays(30)->format('Y-m-d\TH:i')) }}"></label>
            </div>
            <fieldset class="admin-fieldset"><legend>Cantidad de preguntas por asignatura</legend>
            @forelse($asignaturas as $a)<label>{{ $a->nombre }} <small>({{ $banco[$a->id] ?? 0 }} válidas disponibles)</small>
                <input name="distribucion[{{ $a->id }}]" type="number" min="0" max="1000" required value="{{ old('distribucion.'.$a->id, 0) }}"></label>
            @empty<p>Antes de crear el examen, registra asignaturas, temas y preguntas.</p>@endforelse
            </fieldset>
            <button class="admin-btn" type="submit" @disabled($asignaturas->isEmpty())>Publicar simulacro</button>
        </form>
    </section>
</div>
@endsection
