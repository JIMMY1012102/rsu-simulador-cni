@php
    $img = fn (string $archivo) => asset('images/alumno/ciencias-basicas/'.$archivo);
    $campana ??= false;
    // Solo enlaza pantallas que ya existen; «Mi perfil» aparece cuando se integre su ruta (PR #13).
    $enlaces = array_filter([
        'inicio' => ['Inicio', route('alumno.ciencias-basicas'), true],
        'simulacros' => ['Simulacros', route('alumno.panel').'#simulacros', false],
        'perfil' => \Illuminate\Support\Facades\Route::has('alumno.perfil') ? ['Mi perfil', route('alumno.perfil'), false] : null,
    ]);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ciencias Básicas · Simulador CNI</title>
    <link rel="stylesheet" href="{{ asset('css/alumno/ciencias-basicas.css') }}">
    @livewireStyles
</head>
<body class="ini-body">
    <a class="ini-skip" href="#contenido-inicio">Saltar al contenido</a>
    <div class="ini">
        <aside class="ini-lateral" aria-label="Navegación del simulador">
            <div class="ini-identidad">
                <span class="ini-identidad-portal">Simulador</span>
                <span class="ini-identidad-cni">CNI</span>
            </div>
            <nav class="ini-menu">
                @foreach ($enlaces as [$etiqueta, $url, $actual])
                    <a href="{{ $url }}" @if ($actual) aria-current="page" @endif>{{ $etiqueta }}</a>
                @endforeach
            </nav>
            <img class="ini-paisaje" src="{{ $img('paisaje-lateral@2x.png') }}" width="155" height="530" alt="">
            <div class="ini-lateral-pie">
                <a href="{{ route('alumno.panel') }}">Panel principal</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        </aside>

        <main class="ini-principal" id="contenido-inicio">
            <header class="ini-barra">
                <div class="ini-area">
                    <span class="ini-emblema"><img src="{{ $img('atom-encabezado.svg') }}" width="42" height="42" alt=""></span>
                    <h1>{{ $area->nombre }}</h1>
                </div>
                <div class="ini-cuenta">
                    <span class="ini-avisos" role="img"
                          aria-label="{{ $campana ? 'Hay simulacros disponibles para tu área' : 'Sin avisos nuevos' }}"
                          title="{{ $campana ? 'Hay simulacros disponibles para tu área' : 'Sin avisos nuevos' }}">
                        <img src="{{ $img('bell.svg') }}" width="30" height="30" alt="">
                        @if ($campana)<span class="ini-avisos-punto"></span>@endif
                    </span>
                    <span class="ini-avatar"><img src="{{ $img('user-round.svg') }}" width="35" height="35" alt=""></span>
                    <div class="ini-alumno">
                        <strong>{{ $nombre }}</strong>
                        <span>Estudiante</span>
                    </div>
                </div>
            </header>
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
