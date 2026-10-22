@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/orientacion.css') }}">
@endpush
@section('content')
<section class="orientacion panel-personalizado">
    <p class="eyebrow">TU ESPACIO DE PREPARACIÓN · ÁREA {{ $area->codigo }}</p>
    <h1>¡Bienvenido, {{ $usuario->nombres }}! 👋</h1>
    <p>Tu área elegida es <strong>{{ $area->nombre }}</strong>.</p>
    @if(session('status')) <p class="mensaje-ok" role="status">{{ session('status') }}</p> @endif

    <section class="bloque-orientacion panel-destacado">
        <span class="area-letra" aria-hidden="true">{{ $area->codigo }}</span>
        <div>
            <p class="eyebrow">PANEL PERSONAL DEL ALUMNO</p>
            <h2>{{ $area->nombre }}</h2>
            <p>{{ $carrera ? 'Carrera: '.$carrera->nombre : 'Ya puedes comenzar a practicar en los simulacros de tu área.' }}</p>
            <a class="boton-examen" href="#simulacros">Dar examen simulador →</a>
        </div>
    </section>

    <section class="bloque-orientacion" id="simulacros">
        <h2>Exámenes de tu área</h2>
        @forelse($simulacros as $simulacro)
            <article class="simulacro-fila">
                <div>
                    <h3>{{ $simulacro['nombre'] }}</h3>
                    <p>{{ $simulacro['total_preguntas'] }} preguntas · {{ $simulacro['tiempo_limite'] }} minutos</p>
                </div>
                <form method="POST" action="{{ route('examen.iniciar', ['simulacro' => $simulacro['id']]) }}">
                    @csrf
                    <button type="submit">Iniciar examen →</button>
                </form>
            </article>
        @empty
            <p>Aún no hay simulacros publicados para esta área. Cuando un docente habilite uno, podrás iniciarlo desde aquí.</p>
        @endforelse
    </section>

    @unless($carrera)
    <section class="bloque-orientacion">
        <h2>Carrera de interés (opcional)</h2>
        <p>Puedes indicar tu carrera dentro de {{ $area->nombre }}. No es necesario para rendir el examen de tu área.</p>
        <form method="POST" action="{{ route('alumno.carrera.guardar') }}">
            @csrf
            <label for="carrera_id">Carrera</label>
            <select id="carrera_id" name="carrera_id" required>
                <option value="">Selecciona una carrera</option>
                @foreach($carreras as $opcion)
                    <option value="{{ $opcion->id }}" @selected(old('carrera_id') == $opcion->id)>{{ $opcion->nombre }}</option>
                @endforeach
            </select>
            @error('carrera_id')<p class="error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" @disabled($carreras->isEmpty())>Guardar carrera</button>
        </form>
    </section>
    @endunless

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
</section>
@endsection
