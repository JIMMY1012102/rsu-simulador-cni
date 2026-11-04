@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/orientacion.css') }}">
<link rel="stylesheet" href="{{ asset('css/resultados.css') }}?v=20261104">
@endpush

@section('content')
@php
    // Dos columnas equilibradas sin eliminar ninguna asignatura.
    $filas = collect($resultado['resultados_area'])->values();
    $columnas = $filas->isEmpty() ? collect() : $filas->chunk((int) ceil($filas->count() / 2));
@endphp
<section class="orientacion resultados-pagina">
    <div class="resultados-cabecera">
        <div>
            <p class="eyebrow">SIMULADOR CNI · RESULTADOS</p>
            <h1>Resultados de tu examen</h1>
            <p class="resultados-intento">Intento #{{ $resultado['intento_id'] }} · {{ $usuario->nombres }}</p>
        </div>
        <a class="resultados-regresar" href="{{ route('alumno.panel') }}">← Regresar a mi panel</a>
    </div>

    <div class="estadisticas-grid" aria-label="Resumen de resultados">
        <article class="stat stat--puntaje">
            <span>Puntaje total</span>
            <strong>{{ number_format($resultado['totales']['puntaje_total'], 3) }}</strong>
        </article>
        <article class="stat stat--correctas">
            <span>Correctas</span>
            <strong>{{ $resultado['totales']['correctas'] }}</strong>
        </article>
        <article class="stat stat--incorrectas">
            <span>Incorrectas</span>
            <strong>{{ $resultado['totales']['incorrectas'] }}</strong>
        </article>
        <article class="stat stat--blanco">
            <span>En blanco</span>
            <strong>{{ $resultado['totales']['en_blanco'] }}</strong>
        </article>
    </div>

    <section class="bloque-orientacion resultados-bloque" aria-labelledby="resultados-asignaturas">
        <div class="resultados-seccion-cabecera">
            <h2 id="resultados-asignaturas">Resultados por asignatura</h2>
            <span>{{ $filas->count() }} asignaturas</span>
        </div>

        <div class="resultados-columnas">
            @forelse($columnas as $indice => $columna)
                <table class="resultados-tabla">
                    <caption class="resultados-solo-lector">Resultados por asignatura, columna {{ $indice + 1 }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Asignatura</th>
                            <th scope="col">Aciertos</th>
                            <th scope="col">%</th>
                            <th scope="col">Puntaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($columna as $fila)
                            <tr>
                                <td>{{ $fila->area_nombre }}</td>
                                <td>{{ $fila->aciertos }}<span class="resultado-separador">/</span>{{ $fila->total_preguntas }}</td>
                                <td>{{ $fila->porcentaje }}%</td>
                                <td>{{ $fila->puntaje }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @empty
                <p class="resultados-vacio">Aún no hay resultados por asignatura para este intento.</p>
            @endforelse
        </div>
    </section>
</section>
@endsection
