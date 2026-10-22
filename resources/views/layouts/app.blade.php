<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulador CNI</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
    @livewireStyles
</head>
<body class="{{ request()->routeIs('login') ? 'login-screen' : '' }}">
    @unless (request()->routeIs('login'))
        <header>
            <a href="{{ route('inicio') }}" class="brand">Simulador CNI</a>
            @auth('web')
                <nav aria-label="Navegación principal">
                    <a href="{{ route(auth('web')->user()->rol === 'docente' ? 'docente.panel' : ((config('cni.alumno_areas_enabled') && auth('web')->user()->rol === 'estudiante') ? 'alumno.panel' : 'panel')) }}">Mi panel</a>
                    <span>{{ auth('web')->user()->nombres }} · {{ auth('web')->user()->rol }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button>Salir</button></form>
                </nav>
            @endauth
        </header>
    @endunless
    <main>
        @if ($errors->any())
            <div class="alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
