@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/panel-alumno.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/alumno-paginas.css') }}?v=1">
@endpush
@section('content')
@php
    $esVistaPrevia = $esVistaPrevia ?? false;
    $temaArea = config('panel_alumno.areas.'.$area->codigo);
    $rutaMascota = $area->codigo === 'A'
        ? 'images/alumno/pinguino-medicina.webp'
        : (is_file(public_path($temaArea['mascota_webp']))
            ? $temaArea['mascota_webp']
            : $temaArea['mascota_svg']);
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
<section class="cni-pagina-hero" aria-labelledby="titulo-perfil">
    <div class="cni-pagina-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1)) }}</div>
    <div class="cni-pagina-hero-copy"><span class="cni-alumno-section-kicker">PORTAL ACADÉMICO · CNI</span><h1 id="titulo-perfil">Mi perfil</h1><p>{{ $nombreCompleto }}</p><span class="cni-pagina-estado">{{ $usuario->activo ? '● Cuenta activa' : '○ Cuenta inactiva' }}</span></div>
    <img class="cni-pagina-mascota" src="{{ asset($rutaMascota) }}" alt="Mascota del área de {{ $area->nombre }}">
</section>
<div class="cni-pagina-columns">
    <section class="cni-pagina-card" aria-labelledby="perfil-informacion"><h2 id="perfil-informacion">Información personal</h2>
        <dl class="cni-pagina-datos">
            <div><dt>Nombre completo</dt><dd>{{ $nombreCompleto }}</dd></div>
            <div><dt>Usuario de acceso</dt><dd>{{ $esVistaPrevia ? 'No disponible en vista previa' : $usuario->usuario }}</dd></div>
            <div><dt>Rol</dt><dd>Estudiante</dd></div>
            <div><dt>Estado de cuenta</dt><dd>{{ $usuario->activo ? 'Activa' : 'Inactiva' }}</dd></div>
        </dl>
    </section>
    <section class="cni-pagina-card" aria-labelledby="perfil-preparacion"><h2 id="perfil-preparacion">Mi preparación</h2>
        <div class="cni-pagina-preparacion"><span class="cni-pagina-icono">♥</span><div><small>Área de postulación</small><strong>{{ $area->nombre }}</strong></div></div>
        @if($carrera)
            <div class="cni-pagina-preparacion"><span class="cni-pagina-icono">✦</span><div><small>Carrera de interés</small><strong>{{ $carrera->nombre }}</strong></div></div>
        @elseif(!$esVistaPrevia)
            <p class="cni-pagina-muted">Todavía no has elegido una carrera de interés. Es opcional para rendir simulacros.</p>
            <form method="POST" action="{{ route('alumno.carrera.guardar') }}" class="cni-pagina-form">@csrf
                <label for="carrera_id">Elegir carrera</label>
                <select id="carrera_id" name="carrera_id" required><option value="">Selecciona una carrera</option>@foreach($carreras as $opcion)<option value="{{ $opcion->id }}" @selected(old('carrera_id') == $opcion->id)>{{ $opcion->nombre }}</option>@endforeach</select>
                <button type="submit" class="cni-alumno-primary" @disabled($carreras->isEmpty())>Guardar carrera</button>
                @error('carrera_id')<p role="alert">{{ $message }}</p>@enderror
            </form>
        @else
            <p class="cni-pagina-muted">Carrera de interés no disponible en vista previa.</p>
        @endif
    </section>
</div>
<div class="cni-pagina-footer"><a class="cni-alumno-primary" href="{{ $esVistaPrevia ? route('alumno.salud-preview') : route('alumno.panel') }}">Volver al inicio →</a></div>
        </main>
    </div>
</div>
@endsection
