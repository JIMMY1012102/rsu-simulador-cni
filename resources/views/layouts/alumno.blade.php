{{--
    Layout del portal del alumno (Figma: "CNI · Simulador de examen", nodo 214:3).
    PROVISIONAL: cuando Jimmy publique la plantilla oficial, reemplazar este archivo
    (o hacer que extienda la oficial) manteniendo las mismas zonas:
      - $slot / @yield('content'): contenido de la página
      - $migas: último tramo de las migas de pan (por defecto "Examen en curso")
      - $seccionActiva: enlace resaltado del menú (por defecto "simulador")
    Usa su propia hoja public/css/alumno.css; no carga app.css para no mezclar estilos globales.
--}}
@php
    $usuario = auth('web')->user();
    $nombreCorto = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::before(trim($usuario->nombres), ' '));
    $iniciales = \Illuminate\Support\Str::upper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
    $perfil = $usuario->rol === 'estudiante' ? 'Alumno CNI' : ucfirst($usuario->rol);
    $seccionActiva ??= 'simulador';
    $menu = [
        'inicio' => ['Inicio', 'nav-inicio.svg', route('panel')],
        'perfil' => ['Mi perfil', 'nav-perfil.svg', '#'],
        'simulador' => ['Simulador', 'nav-simulador.svg', '#'],
        'oferta' => ['Oferta formativa', 'nav-oferta.svg', '#'],
        'recursos' => ['Recursos', 'nav-recursos.svg', '#'],
        'ayuda' => ['Ayuda y contacto', 'nav-ayuda.svg', '#'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Simulador de examen' }} · CNI</title>
    {{-- ?v= con la fecha del archivo: el navegador descarga la hoja nueva después de cada cambio. --}}
    <link rel="stylesheet" href="{{ asset('css/alumno.css').'?v='.filemtime(public_path('css/alumno.css')) }}">
    @livewireStyles
</head>
<body class="portal">
    <aside class="portal-lateral" x-data="{ abierto: false }" :class="{ 'is-abierto': abierto }"
        @keydown.escape.window="abierto = false">
        <div class="portal-lateral__principal">
            <div class="portal-lateral__cabecera">
                <a class="portal-identidad" href="{{ route('panel') }}">
                    <img class="portal-identidad__escudo" src="{{ asset('img/examen/escudo-cni.png') }}" alt="Escudo institucional CNI">
                    <span class="portal-identidad__nombre">
                        <strong>CNI</strong>
                        <small>Portal del alumno</small>
                    </span>
                </a>
                {{-- Solo visible en celular (< 768 px): despliega el menú y la sesión. --}}
                <button type="button" class="portal-menu-boton" aria-controls="portal-menu portal-lateral-pie"
                    aria-expanded="false" :aria-expanded="abierto ? 'true' : 'false'" @click="abierto = !abierto">
                    <span class="portal-menu-boton__icono" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span x-text="abierto ? 'Cerrar' : 'Menú'">Menú</span>
                </button>
            </div>

            <nav class="portal-menu" id="portal-menu" aria-label="Mi portal">
                <p class="portal-menu__titulo">MI PORTAL</p>
                @foreach ($menu as $clave => [$texto, $icono, $enlace])
                    {{-- Las rutas con "#" aún no existen en el proyecto. --}}
                    <a href="{{ $enlace }}" @class(['portal-menu__enlace', 'is-activo' => $clave === $seccionActiva])
                        @if ($clave === $seccionActiva) aria-current="page" @endif>
                        <img src="{{ asset('img/examen/'.$icono) }}" width="18" height="18" alt="">
                        {{ $texto }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="portal-lateral__pie" id="portal-lateral-pie">
            <div class="portal-ayuda">
                <span class="portal-ayuda__icono"><img src="{{ asset('img/examen/ayuda-tarjeta.svg') }}" width="14" height="12.9986" alt=""></span>
                <strong>Estamos para ayudarte</strong>
                <p>Resuelve tus dudas sobre el portal o tus próximos pasos.</p>
                <a href="#">Contactar con CNI →</a>
            </div>
            <hr class="portal-lateral__separador">
            <div class="portal-sesion">
                <span class="portal-avatar" aria-hidden="true">{{ $iniciales }}</span>
                <span class="portal-sesion__usuario">
                    <strong>{{ $nombreCorto }}</strong>
                    <small>{{ $perfil }}</small>
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="portal-salir">
                    <span class="portal-salir__icono"><img src="{{ asset('img/examen/cerrar-sesion.svg') }}" width="16.0521" height="15.7" alt=""></span>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <div class="portal-area">
        <div>
            <header class="portal-barra">
                <nav class="portal-migas" aria-label="Migas de pan">
                    <a href="{{ route('panel') }}">Portal del alumno</a>
                    <span aria-hidden="true">/</span>
                    <span>Simulador</span>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $migas ?? 'Examen en curso' }}</span>
                </nav>
                <div class="portal-cuenta">
                    <a class="portal-cuenta__ayuda" href="#">
                        <img src="{{ asset('img/examen/ayuda.svg') }}" width="15" height="15" alt="">
                        Ayuda
                    </a>
                    <span class="portal-cuenta__separador" aria-hidden="true"></span>
                    <span class="portal-cuenta__nombre">{{ $nombreCorto }}</span>
                    <span class="portal-avatar" aria-hidden="true">{{ $iniciales }}</span>
                </div>
            </header>

            <main class="portal-contenido">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>

        <footer class="portal-pie">
            <span>CNI · Portal del alumno</span>
            <span class="portal-pie__enlaces"><a href="#">Privacidad</a><a href="#">Términos y condiciones</a></span>
        </footer>
    </div>
    @stack('scripts')
    @livewireScripts
</body>
</html>
