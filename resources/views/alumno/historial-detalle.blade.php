{{--
    Detalle de un examen terminado: totales y resultado por área (tabla resultados_area).
    Datos: App\Http\Controllers\Alumno\HistorialController@show.
--}}
@extends('layouts.alumno', ['variante' => 'derecho', 'title' => 'Resultado del examen'])

@section('content')
<div class="pagina">
    <a class="pagina-volver" href="{{ route('alumno.historial') }}">
        <x-alumno.icono nombre="flecha-izquierda" :tamano="16" /> Volver al historial
    </a>

    <header class="pagina-encabezado">
        <h1>{{ $intento->simulacro ?? 'Práctica' }}</h1>
        <p>
            Rendido el {{ \Illuminate\Support\Carbon::parse($intento->fecha_inicio)->translatedFormat('d \d\e F \d\e Y, H:i') }}
            @if ($intento->estado === 'enviado_auto') · se envió automáticamente al acabarse el tiempo @endif
        </p>
    </header>

    @if ($intento->puntaje_total === null)
        <p class="inicio-tarjeta pagina-vacio">Este examen todavía no tiene calificación.</p>
    @else
        <ul class="inicio-cifras resultado-cifras">
            <li>
                <span class="inicio-cifras__icono"><x-alumno.icono nombre="diana" :tamano="20" /></span>
                <span><strong>{{ number_format((float) $intento->puntaje_total, 3) }}</strong>
                    <small>Puntaje{{ $intento->puntaje_maximo ? ' de '.number_format((float) $intento->puntaje_maximo, 0) : '' }}</small></span>
            </li>
            <li>
                <span class="inicio-cifras__icono"><x-alumno.icono nombre="check-circulo" :tamano="20" /></span>
                <span><strong>{{ $intento->correctas }}</strong><small>Correctas</small></span>
            </li>
            <li>
                <span class="inicio-cifras__icono"><x-alumno.icono nombre="porcentaje" :tamano="20" /></span>
                <span><strong>{{ $intento->incorrectas }}</strong><small>Incorrectas</small></span>
            </li>
            <li>
                <span class="inicio-cifras__icono"><x-alumno.icono nombre="intentos" :tamano="20" /></span>
                <span><strong>{{ (int) $intento->en_blanco + (int) $intento->doble_marca }}</strong><small>En blanco o doble marca</small></span>
            </li>
        </ul>

        <section class="inicio-tarjeta" aria-labelledby="titulo-areas">
            <h2 id="titulo-areas">Resultado por área</h2>
            @if ($areas->isEmpty())
                <p class="pagina-vacio">No hay detalle por área para este examen.</p>
            @else
                <ul class="areas-resultado">
                    @foreach ($areas as $area)
                        <li>
                            <div class="areas-resultado__fila">
                                <strong>{{ $area->nombre }}</strong>
                                <span>{{ $area->aciertos }}/{{ $area->total_preguntas }} correctas · {{ number_format((float) $area->puntaje, 3) }} pts</span>
                            </div>
                            <div class="inicio-barra" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                aria-valuenow="{{ (int) round($area->porcentaje) }}" aria-label="Aciertos en {{ $area->nombre }}">
                                <span style="width: {{ (float) $area->porcentaje }}%"></span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
@endsection
