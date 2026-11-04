<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulador CNI</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('estilos')
    @livewireStyles
</head>
<body>
    <header>
        <a href="{{ route('inicio') }}" class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3Z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82Z"/></svg>
            Simulador CNI
        </a>
        @auth('web')
            <nav aria-label="Navegación principal">
                <a href="{{ route('panel') }}">Mi panel</a>
                <span>{{ auth('web')->user()->nombres }} · {{ auth('web')->user()->rol }}</span>
                @hasSection('cabecera-accion')
                    @yield('cabecera-accion')
                @else
                    <form method="POST" action="{{ route('logout') }}">@csrf<button>Salir</button></form>
                @endif
            </nav>
        @endauth
    </header>
    <main>
        @if (session('status') && ! $__env->hasSection('sin-aviso'))
            <div class="alert alert-ok" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
