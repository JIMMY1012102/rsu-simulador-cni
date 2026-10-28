{{--
    "Historial": exámenes terminados con su puntaje.
    Datos: App\Http\Controllers\Alumno\HistorialController@index.
--}}
@extends('layouts.alumno-derecho', ['variante' => 'derecho', 'title' => 'Historial'])

@section('content')
<div class="pagina">
    <header class="pagina-encabezado">
        <h1>Historial</h1>
        <p>Todos tus exámenes terminados. Abre uno para ver tu resultado por área.</p>
    </header>

    @if ($intentos->isEmpty())
        <p class="inicio-tarjeta pagina-vacio">
            Todavía no terminaste ningún examen. Empieza uno en <a href="{{ route('alumno.intentos') }}">Mis intentos</a>.
        </p>
    @else
        <div class="inicio-tarjeta tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Examen</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Correctas</th>
                        <th scope="col">Incorrectas</th>
                        <th scope="col">En blanco</th>
                        <th scope="col">Puntaje</th>
                        <th scope="col"><span class="sr-only">Detalle</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($intentos as $intento)
                        <tr>
                            <td data-titulo="Examen">
                                <strong>{{ $intento->simulacro ?? 'Práctica' }}</strong>
                                @if ($intento->estado === 'enviado_auto')
                                    <span class="etiqueta etiqueta--aviso">Enviado por tiempo</span>
                                @endif
                            </td>
                            <td data-titulo="Fecha">{{ \Illuminate\Support\Carbon::parse($intento->fecha_inicio)->translatedFormat('d/m/Y H:i') }}</td>
                            <td data-titulo="Correctas">{{ $intento->correctas ?? '—' }}</td>
                            <td data-titulo="Incorrectas">{{ $intento->incorrectas ?? '—' }}</td>
                            <td data-titulo="En blanco">{{ $intento->en_blanco ?? '—' }}</td>
                            <td data-titulo="Puntaje">
                                <strong class="tabla__puntaje">{{ $intento->puntaje_total === null ? 'Sin calificar' : number_format((float) $intento->puntaje_total, 3) }}</strong>
                            </td>
                            <td>
                                <a class="tabla__enlace" href="{{ route('alumno.historial.detalle', $intento->id) }}">
                                    Ver detalle <x-alumno.icono nombre="flecha-derecha" :tamano="14" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $intentos->links('alumno.paginacion') }}
    @endif
</div>
@endsection
