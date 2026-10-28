{{--
    "Temas de estudio": todos los temas por área con el avance del alumno.
    Datos: App\Http\Controllers\Alumno\TemasController (ProgresoAlumnoService::temas).
--}}
@extends('layouts.alumno-derecho', ['variante' => 'derecho', 'title' => 'Temas de estudio'])

@section('content')
<div class="pagina">
    <header class="pagina-encabezado">
        <h1>Temas de estudio</h1>
        <p>Tu avance en cada tema: preguntas que ya respondiste en tus simulacros y cuántas acertaste.</p>
    </header>

    @forelse ($areas as $area => $temas)
        <section class="pagina-seccion inicio-temas" aria-labelledby="area-{{ $loop->index }}">
            <h2 id="area-{{ $loop->index }}">{{ $area }}</h2>
            <ul class="inicio-temas__lista">
                @foreach ($temas as $tema)
                    <li class="inicio-tema inicio-tema--sin-flecha" style="--tema-acento: {{ $tema['color']['acento'] }}; --tema-fondo: {{ $tema['color']['fondo'] }}">
                        <span class="inicio-tema__icono"><x-alumno.icono :nombre="$tema['icono']" :tamano="22" /></span>
                        <h3>{{ $tema['nombre'] }}</h3>
                        <div class="inicio-barra" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                            aria-valuenow="{{ $tema['porcentaje'] }}" aria-label="Avance en {{ $tema['nombre'] }}">
                            <span style="width: {{ $tema['porcentaje'] }}%"></span>
                        </div>
                        <p>{{ $tema['resueltas'] }}/{{ $tema['total'] }} preguntas
                            @if ($tema['resueltas'] > 0) · {{ $tema['acierto'] }}% de acierto @endif</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="inicio-tarjeta pagina-vacio">Todavía no hay temas registrados.</p>
    @endforelse
</div>
@endsection
