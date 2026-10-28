@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-salud.css') }}?v=5">
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
<section class="cni-pagina-head"><div><span class="cni-alumno-section-kicker">TU PREPARACIÓN</span><h1>Historial de intentos</h1><p>Consulta todos tus intentos, resultados y evaluaciones en curso.</p></div><a href="{{ $esVistaPrevia ? route('alumno.salud-preview') : route('alumno.panel') }}" class="cni-pagina-outline">Volver al inicio</a></section>
<div class="cni-pagina-kpis" aria-label="Resumen de historial">
    <article><small>Intentos realizados</small><strong>{{ $totales['intentos'] }}</strong></article>
    <article><small>Mejor puntaje</small><strong>{{ $totales['mejor'] === null ? '—' : number_format($totales['mejor'], 2) }}</strong></article>
    <article><small>Último puntaje</small><strong>{{ $totales['ultimo'] === null ? '—' : number_format($totales['ultimo'], 2) }}</strong></article>
    <article><small>Promedio general</small><strong>{{ $totales['promedio'] === null ? '—' : number_format($totales['promedio'], 2) }}</strong></article>
</div>
<section class="cni-pagina-card" aria-labelledby="historial-tabla-titulo"><div class="cni-pagina-head"><h2 id="historial-tabla-titulo">Historial de simulacros</h2></div>
@if(!$esVistaPrevia)
    <form class="cni-filtros" method="GET" action="{{ route('alumno.historial') }}" aria-label="Filtrar historial">
        <label>Buscar intento <input name="buscar" type="search" maxlength="12" inputmode="numeric" value="{{ request('buscar') }}" placeholder="Número de intento"></label>
        <label>Estado <select name="estado"><option value="">Todos</option><option value="calificado" @selected(request('estado') === 'calificado')>Calificado</option><option value="en_curso" @selected(request('estado') === 'en_curso')>En curso</option></select></label>
        <label>Tipo <select name="tipo"><option value="">Todos</option><option value="simulacro" @selected(request('tipo') === 'simulacro')>Simulacro</option><option value="practica" @selected(request('tipo') === 'practica')>Práctica</option></select></label>
        <button type="submit" class="cni-alumno-primary">Filtrar</button>
    </form>
@endif
<div class="cni-tabla-scroll"><table class="cni-pagina-tabla"><thead><tr><th>Intento</th><th>Fecha</th><th>Tipo de examen</th><th>Puntaje</th><th>Estado</th><th>Evolución</th><th>Acciones</th></tr></thead><tbody>
@forelse($intentos as $intento)
    <tr><td><strong>#{{ $intento->id }}</strong></td><td>{{ \Illuminate\Support\Carbon::parse($intento->fecha_inicio)->format('d/m/Y') }}</td><td>{{ $intento->simulacro_nombre ?? ($intento->tipo === 'simulacro' ? 'Simulacro' : 'Práctica') }}</td><td>{{ $intento->puntaje_total === null ? '—' : number_format((float) $intento->puntaje_total, 2).' pts' }}</td><td><span @class(['cni-pagina-tag', 'pendiente' => $intento->puntaje_total === null])>{{ $intento->puntaje_total === null ? 'En curso / sin calificar' : 'Calificado' }}</span></td><td>@if($intento->puntaje_total !== null && isset($cambios[$intento->id]) && $cambios[$intento->id] !== null){{ $cambios[$intento->id] > 0 ? '↑ +' : ($cambios[$intento->id] < 0 ? '↓ ' : '= ') }}{{ number_format($cambios[$intento->id], 2) }}@else—@endif</td><td>@if($intento->puntaje_total !== null && !$esVistaPrevia)<a href="{{ route('alumno.resultados', ['intento' => $intento->id]) }}" class="cni-pagina-outline">Ver resultado</a>@elseif($intento->puntaje_total === null)<span class="cni-pagina-muted">Pendiente</span>@else<span class="cni-pagina-muted">Vista previa</span>@endif</td></tr>
@empty
    <tr><td colspan="7" class="cni-pagina-sin-datos">No se encontraron intentos con esos filtros.</td></tr>
@endforelse
</tbody></table></div>
@if(!$esVistaPrevia && method_exists($intentos, 'links'))<div class="cni-pagina-paginacion">{{ $intentos->links() }}</div>@endif
</section>
        </main>
    </div>
</div>
@endsection
