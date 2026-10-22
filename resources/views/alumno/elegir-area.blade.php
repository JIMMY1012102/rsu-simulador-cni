@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/orientacion.css') }}">
@endpush
@section('content')
<section class="orientacion">
    <p class="eyebrow">PRIMER INGRESO · SIMULADOR CNI</p>
    <h1>¿En qué área deseas prepararte?</h1>
    <p>Elige una de las cinco áreas de postulación. Después podrás seleccionar tu carrera y acceder al panel correspondiente.</p>
    @if ($errors->any())
        <div class="alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="areas-grid">
        @forelse ($areas as $area)
        <form method="POST" action="{{ route('alumno.area.guardar') }}" class="area-tarjeta">
            @csrf
            <input type="hidden" name="area_id" value="{{ $area->id }}">
            <span class="area-letra">{{ $area->codigo }}</span>
            <h2>{{ $area->nombre }}</h2>
            <button type="submit">Elegir esta área →</button>
        </form>
        @empty
        <p>No hay áreas habilitadas. Comunícate con el administrador.</p>
        @endforelse
    </div>
</section>
@endsection
