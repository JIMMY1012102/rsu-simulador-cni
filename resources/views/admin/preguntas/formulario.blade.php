@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}">@endpush
@section('content')
<div class="admin admin-narrow">
    <a href="{{ route('admin.preguntas.index') }}">← Banco de preguntas</a>
    <h1>{{ $pregunta ? 'Editar pregunta #'.$pregunta->id : 'Nueva pregunta' }}</h1>
    <p>Las preguntas usadas en un examen no pueden editarse; crea otra versión cuando corresponda.</p>
    @if(session('status'))<p class="admin-success" role="status">{{ session('status') }}</p>@endif
    <section class="admin-card">
        <form method="POST" enctype="multipart/form-data" action="{{ $pregunta ? route('admin.preguntas.update', $pregunta->id) : route('admin.preguntas.store') }}">
            @csrf @if($pregunta)@method('PUT')@endif
            <label>Tema / asignatura<select name="tema_id" required>
                <option value="">Selecciona un tema</option>
                @foreach($temas as $t)<option value="{{ $t->id }}" @selected(old('tema_id', $pregunta->tema_id ?? '') == $t->id)>{{ $t->asignatura }} — {{ $t->nombre }}</option>@endforeach
            </select></label>
            <label>Enunciado<textarea name="enunciado" required rows="4" maxlength="15000">{{ old('enunciado', $pregunta->enunciado ?? '') }}</textarea></label>
            <label>Nivel<select name="nivel" required>@foreach(['basico'=>'Básico','intermedio'=>'Intermedio','avanzado'=>'Avanzado'] as $v=>$n)
                <option value="{{ $v }}" @selected(old('nivel', $pregunta->nivel ?? 'basico') === $v)>{{ $n }}</option>
            @endforeach</select></label>
            <label>Imagen de apoyo (JPG, PNG o WebP, máximo 2 MB)<input type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp"></label>
            @if($pregunta?->imagen)<p>Imagen actual:</p><img style="max-width:360px;max-height:220px" src="{{ asset($pregunta->imagen) }}" alt="Imagen actual de la pregunta">@endif
            <fieldset class="admin-fieldset"><legend>Cuatro alternativas</legend>
                @foreach(['A','B','C','D'] as $letra)
                    <label>{{ $letra }}<input name="alternativas[{{ $letra }}]" required maxlength="500" value="{{ old('alternativas.'.$letra, $alternativas[$letra] ?? '') }}"></label>
                @endforeach
            </fieldset>
            <label>Respuesta correcta<select name="correcta" required>
                @foreach(['A','B','C','D'] as $letra)<option value="{{ $letra }}" @selected(old('correcta', $correcta ?? 'A') === $letra)>{{ $letra }}</option>@endforeach
            </select></label>
            <label>Solución o explicación<textarea name="solucion" required rows="3" maxlength="15000">{{ old('solucion', $pregunta->solucion ?? '') }}</textarea></label>
            <button type="submit" class="admin-btn">{{ $pregunta ? 'Guardar cambios' : 'Registrar pregunta' }}</button>
        </form>
    </section>
</div>
@endsection
