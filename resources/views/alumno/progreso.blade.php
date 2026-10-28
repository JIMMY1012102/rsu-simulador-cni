@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/panel-alumno.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/alumno-paginas.css') }}?v=1">
@endpush
@section('content')
@php
    $esVistaPrevia = $esVistaPrevia ?? false;
    $nombreCompleto = trim($usuario->nombres.' '.($usuario->apellidos ?? ''));
@endphp
<div class="cni-alumno" data-area="{{ $area->codigo }}">
    @include('alumno.partials.sidebar')
    <div class="cni-alumno-workspace">
        @include('alumno.partials.topbar')
        <main class="cni-alumno-content cni-paginas">
            @if($esVistaPrevia)
                <p class="cni-alumno-alert" role="status">Vista previa local · Sin conexión a MySQL ni datos personales reales. Los indicadores de muestra permanecen vacíos.</p>
            @endif
<section class="cni-pagina-head"><div><span class="cni-alumno-section-kicker">RENDIMIENTO ACADÉMICO</span><h1>Mi progreso</h1><p>Consulta la evolución de tus simulacros calificados.</p></div><a href="{{ $esVistaPrevia ? route('alumno.salud-preview') : route('alumno.panel') }}" class="cni-pagina-outline">Volver al inicio</a></section>
<section class="cni-pagina-highlight"><div class="cni-pagina-donut" aria-label="Último puntaje"><span>{{ $resumen['ultimo'] === null ? '—' : number_format($resumen['ultimo'], 2) }}</span><small>pts</small></div><div><h2>Tu último intento calificado</h2><p>{{ $resumen['ultimo'] === null ? 'Todavía no tienes intentos calificados.' : 'Revisa tus avances y sigue practicando para mejorar tu puntaje.' }}</p><span>{{ $resumen['total'] }} {{ $resumen['total'] === 1 ? 'evaluación calificada' : 'evaluaciones calificadas' }}</span></div></section>
<div class="cni-pagina-kpis" aria-label="Indicadores de progreso">
    <article><small>Intentos calificados</small><strong>{{ $resumen['total'] }}</strong></article>
    <article><small>Mejor puntaje</small><strong>{{ $resumen['mejor'] === null ? '—' : number_format($resumen['mejor'], 2) }}</strong></article>
    <article><small>Último puntaje</small><strong>{{ $resumen['ultimo'] === null ? '—' : number_format($resumen['ultimo'], 2) }}</strong></article>
    <article><small>Promedio</small><strong>{{ $resumen['promedio'] === null ? '—' : number_format($resumen['promedio'], 2) }}</strong></article>
</div>
<section class="cni-pagina-card cni-grafico" aria-labelledby="evolucion-titulo"><div class="cni-pagina-head"><div><h2 id="evolucion-titulo">Evolución de tu puntaje</h2><p>Últimos {{ count($resumen['serie']) }} intentos calificados, ordenados cronológicamente.</p></div></div>
@if(count($resumen['serie']) > 0)
<svg class="cni-linea" viewBox="0 0 960 300" role="img" aria-label="Gráfico de evolución del puntaje por intento" preserveAspectRatio="xMidYMid meet">
    @foreach([0,1,2,3,4] as $linea)
        <line x1="50" y1="{{ 36 + 54 * $linea }}" x2="930" y2="{{ 36 + 54 * $linea }}" stroke="#e0e8f4" stroke-width="1"/>
        <text x="8" y="{{ 40 + 54 * $linea }}" fill="#657895" font-size="14">{{ number_format($resumen['maxEje'] - ($resumen['maxEje'] - $resumen['minEje']) * $linea / 4, 1) }}</text>
    @endforeach
    <polyline fill="none" stroke="#187adf" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round" points="{{ $resumen['puntos'] }}"/>
    @foreach($resumen['serie'] as $punto)
        <circle cx="{{ $punto['x'] }}" cy="{{ $punto['y'] }}" r="5" fill="#187adf" stroke="#ffffff" stroke-width="2"><title>Intento #{{ $punto['id'] }}: {{ number_format($punto['puntaje'], 2) }} puntos</title></circle>
    @endforeach
</svg>
<div class="cni-grafico-leyenda"><span>Primer intento #{{ $resumen['serie'][0]['id'] }}</span><span>Último intento #{{ $resumen['serie'][count($resumen['serie']) - 1]['id'] }}</span></div>
@else
<p class="cni-alumno-empty">Cuando termines un simulacro, aquí aparecerá tu evolución real.</p>
@endif
</section>
<div class="cni-pagina-footer"><a class="cni-alumno-primary" href="{{ $esVistaPrevia ? route('alumno.historial-preview') : route('alumno.historial') }}">Consultar historial →</a></div>
        </main>
    </div>
</div>
@endsection
