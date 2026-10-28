@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/panel-alumno.css') }}?v=1">
@endpush
@section('content')
@php
    $paginaActiva = '';
    $tituloPagina = 'Detalle de tema';
    $esVistaPrevia = false;
@endphp
<div class="cni-alumno cni-alumno-topic-detail" data-area="{{ $area->codigo }}">
    @include('alumno.partials.sidebar')
    <div class="cni-alumno-workspace">
        @include('alumno.partials.topbar')
        <main class="cni-alumno-content">
            <p><a class="cni-alumno-text-link" href="{{ route('alumno.panel') }}#temas">← Volver a temas de estudio</a></p>
            <section class="cni-alumno-detail-card"><span class="cni-alumno-section-kicker">BANCO DE PREGUNTAS · {{ $tema->asignatura }}</span><h1>{{ $tema->nombre }}</h1>
                <p>Consulta tu avance real en este tema del banco académico CNI.</p>
                <div class="cni-alumno-detail-stats"><article><strong>{{ $tema->total }}</strong><span>Preguntas disponibles</span></article><article><strong>{{ $tema->realizadas }}</strong><span>Preguntas vistas</span></article><article><strong>{{ $tema->avance }}%</strong><span>Avance del tema</span></article></div>
                <div class="cni-alumno-progress-track" role="progressbar" aria-label="Avance de {{ $tema->nombre }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $tema->avance }}"><span style="width:{{ $tema->avance }}%"></span></div>
                <p class="cni-alumno-progress-note">Las preguntas se contabilizan una sola vez aunque aparezcan en distintos intentos.</p>
                <a class="cni-alumno-primary cni-alumno-detail-action" href="{{ route('alumno.panel') }}#simulacros-disponibles">Ver simulacros de mi área →</a>
            </section>
        </main>
    </div>
</div>
@endsection
