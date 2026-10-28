{{--
    "Banco de preguntas": cuántas preguntas hay por área, tema y nivel (sin enunciados ni respuestas).
    Datos: App\Http\Controllers\Alumno\BancoController.
--}}
@extends('layouts.alumno', ['variante' => 'derecho', 'title' => 'Banco de preguntas'])

@section('content')
<div class="pagina">
    <header class="pagina-encabezado">
        <h1>Banco de preguntas</h1>
        <p>{{ $total }} {{ $total === 1 ? 'pregunta disponible' : 'preguntas disponibles' }} para tus simulacros, organizadas por área, tema y nivel.</p>
    </header>

    <p class="derecho-aviso" role="note">
        <x-alumno.icono nombre="candado" :tamano="16" />
        La práctica libre por tema estará disponible pronto. Mientras tanto, practica con los
        <a href="{{ route('alumno.intentos') }}">simulacros abiertos</a>.
    </p>

    @forelse ($areas as $area)
        <section class="inicio-tarjeta banco-area" aria-labelledby="area-{{ $loop->index }}">
            <div class="banco-area__encabezado">
                <h2 id="area-{{ $loop->index }}">{{ $area['nombre'] }}</h2>
                <span class="etiqueta">{{ $area['total'] }} preguntas</span>
            </div>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Tema</th>
                            @foreach ($niveles as $etiqueta)
                                <th scope="col">{{ $etiqueta }}</th>
                            @endforeach
                            <th scope="col">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($area['temas'] as $tema)
                            <tr>
                                <td data-titulo="Tema"><strong>{{ $tema['nombre'] }}</strong></td>
                                @foreach ($niveles as $nivel => $etiqueta)
                                    <td data-titulo="{{ $etiqueta }}">{{ $tema['niveles'][$nivel] }}</td>
                                @endforeach
                                <td data-titulo="Total"><strong>{{ $tema['total'] }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="inicio-tarjeta pagina-vacio">Todavía no hay preguntas registradas.</p>
    @endforelse
</div>
@endsection
