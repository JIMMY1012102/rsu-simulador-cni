@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/orientacion.css') }}">
@endpush
@section('content')
<section class="orientacion">
    <p class="eyebrow">TU ESPACIO DE PREPARACIÓN · ÁREA {{ $area->codigo }}</p>
    <h1>¡Hola, {{ $usuario->nombres }}! 👋</h1>
    <p>Estás en el panel de <strong>{{ $area->nombre }}</strong>.</p>
    @if(session('status')) <p class="mensaje-ok" role="status">{{ session('status') }}</p> @endif
    @unless ($carrera)
    <section class="bloque-orientacion">
        <h2>Elige tu carrera profesional</h2>
        <p>Tu elección determina los simulacros a los que tendrás acceso. Se conservará al volver a iniciar sesión.</p>
        <form method="POST" action="{{ route('alumno.carrera.guardar') }}">
            @csrf
            <label for="carrera_id">Carrera dentro de {{ $area->nombre }}</label>
            <select id="carrera_id" name="carrera_id" required>
                <option value="">Selecciona una carrera</option>
                @foreach($carreras as $opcion)
                    <option value="{{ $opcion->id }}" @selected(old('carrera_id') == $opcion->id)>{{ $opcion->nombre }}</option>
                @endforeach
            </select>
            @error('carrera_id')<p class="error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" @disabled($carreras->isEmpty())>Confirmar carrera</button>
        </form>
        <p><a href="{{ route('alumno.elegir-area') }}">← Cambiar de área</a></p>
    </section>
    @else
    <section class="bloque-orientacion">
        <h2>Tu preparación: {{ $carrera->nombre }}</h2>
        <p>Área {{ $area->codigo }} · {{ $area->nombre }}. Los exámenes disponibles están configurados para tu área de postulación.</p>
    </section>
    <section class="bloque-orientacion">
        <h2>Simulacros disponibles</h2>
        @forelse($simulacros as $simulacro)
            <article class="simulacro-fila">
                <div>
                    <h3>{{ $simulacro['nombre'] }}</h3>
                    <p>{{ $simulacro['total_preguntas'] }} preguntas · {{ $simulacro['tiempo_limite'] }} minutos</p>
                </div>
                <form method="POST" action="{{ route('examen.iniciar', ['simulacro' => $simulacro['id']]) }}">
                    @csrf
                    <button type="submit">Rendir examen</button>
                </form>
            </article>
        @empty
            <p>Aún no hay exámenes vigentes para tu área. Un docente debe configurar y publicar uno.</p>
        @endforelse
    </section>
    <section class="bloque-orientacion">
        <h2>Mis últimos resultados</h2>
        @forelse($intentos as $intento)
            <div class="simulacro-fila">
                <span>Intento #{{ $intento->id }} · {{ $intento->fecha_inicio }}</span>
                <a href="{{ route('alumno.resultados', ['intento' => $intento->id]) }}">Puntaje {{ $intento->puntaje_total }} · Ver resultados</a>
            </div>
        @empty <p>Todavía no has rendido un examen.</p>
        @endforelse
    </section>
    @endunless
</section>
@endsection
