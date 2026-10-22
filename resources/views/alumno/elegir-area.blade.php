@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/orientacion.css') }}">
@endpush
@section('content')
<section class="orientacion selector-areas">
    <p class="eyebrow">BIENVENIDO AL SIMULADOR CNI</p>
    <h1>¿A qué área deseas postular?</h1>
    <p>Selecciona tu área de preparación. La próxima vez que ingreses irás directamente a tu panel.</p>
    <div class="areas-grid" aria-label="Cinco áreas de postulación">
        @forelse ($areas as $area)
        <form method="POST" action="{{ route('alumno.area.guardar') }}" class="area-tarjeta">
            @csrf
            <input type="hidden" name="area_id" value="{{ $area->id }}">
            <span class="area-letra">{{ $area->codigo }}</span>
            <h2>{{ $area->nombre }}</h2>
            <p>Prepara tu examen de admisión según esta área.</p>
            <button type="submit">Seleccionar área <span aria-hidden="true">→</span></button>
        </form>
        @empty
        <p>No hay áreas habilitadas. Comunícate con el administrador.</p>
        @endforelse
    </div>
</section>
@endsection
