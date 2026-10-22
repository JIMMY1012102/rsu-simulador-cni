@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/orientacion.css') }}">
@endpush
@section('content')
<section class="orientacion">
    <p class="eyebrow">SIMULADOR CNI · RESULTADOS</p>
    <h1>Resultados de tu examen</h1>
    <p>¡Buen trabajo, {{ $usuario->nombres }}! Estos son tus resultados del intento #{{ $resultado['intento_id'] }}.</p>
    <div class="estadisticas-grid">
        <article class="stat"><span>Puntaje total</span><strong>{{ number_format($resultado['totales']['puntaje_total'], 3) }}</strong></article>
        <article class="stat"><span>Correctas</span><strong>{{ $resultado['totales']['correctas'] }}</strong></article>
        <article class="stat"><span>Incorrectas</span><strong>{{ $resultado['totales']['incorrectas'] }}</strong></article>
        <article class="stat"><span>En blanco</span><strong>{{ $resultado['totales']['en_blanco'] }}</strong></article>
    </div>
    <section class="bloque-orientacion">
        <h2>Resultados por asignatura</h2>
        <table class="resultados-tabla">
            <thead><tr><th>Asignatura</th><th>Aciertos</th><th>Preguntas</th><th>Porcentaje</th><th>Puntaje</th></tr></thead>
            <tbody>
            @foreach($resultado['resultados_area'] as $fila)
            <tr>
                <td>{{ $fila->area_nombre }}</td><td>{{ $fila->aciertos }}</td>
                <td>{{ $fila->total_preguntas }}</td><td>{{ $fila->porcentaje }}%</td>
                <td>{{ $fila->puntaje }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </section>
    <p><a href="{{ route('alumno.panel') }}">← Volver a mi panel</a></p>
</section>
@endsection
