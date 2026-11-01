@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
@endpush

@section('content')
<div class="cni-workspace">
    <aside class="cni-sidebar" aria-label="Menú de administración">
        <a class="cni-brand" href="{{ route('admin.inicio') }}" aria-label="CNI · Ir al panel">
            <span class="cni-brand__seal"><img src="{{ asset('images/escudo-cni.png') }}" alt="" loading="eager"></span>
            <span class="cni-brand__copy"><strong>SIMULADOR CNI</strong><small>Colegio Nacional de Imperial</small></span>
        </a>
        <div class="cni-sidebar__scroll">
            <div class="cni-nav-caption">BANCO DE PREGUNTAS</div>
            <nav class="cni-nav" aria-label="Secciones del banco">
                @foreach([
                    ['admin.subtemas.index', 'Subtemas', '▤', 'admin.subtemas.*'],
                    ['admin.preguntas.index', 'Preguntas', '▢', 'admin.preguntas.*'],
                    ['admin.importar.index', 'Importar Excel', '⇧', 'admin.importar.*'],
                ] as [$ruta, $texto, $icono, $patron])
                    <a href="{{ route($ruta) }}" @class(['cni-nav__link', 'is-current' => request()->routeIs($patron)])
                       @if(request()->routeIs($patron)) aria-current="page" @endif>
                        <span class="cni-nav__icon" aria-hidden="true">{{ $icono }}</span><span>{{ $texto }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
        <div class="cni-sidebar__footer">
            <div class="cni-sidebar__notice"><span class="cni-live-dot"></span> Panel administrativo <strong>activo</strong></div>
            <div class="cni-sidebar__meta">Centro de preparación · CNI</div>
        </div>
    </aside>

    <div class="cni-stage">
        <header class="cni-topbar">
            <div class="cni-topbar__context"><span class="cni-topbar__section">Banco de preguntas</span><span class="cni-topbar__separator">/</span><strong>@yield('admin-page', 'Resumen')</strong></div>
            <div class="cni-topbar__right">
                <span class="cni-role">Superadministrador</span>
                <div class="cni-account" title="{{ auth('web')->user()->usuario }}">
                    <span class="cni-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth('web')->user()->nombres, 0, 1)) }}</span>
                    <span class="cni-account__text"><strong>{{ auth('web')->user()->nombres }}</strong><small>{{ auth('web')->user()->usuario }}</small></span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="cni-signout-form">@csrf<button type="submit" class="cni-signout" title="Cerrar sesión">Salir <span aria-hidden="true">↗</span></button></form>
            </div>
        </header>
        <main class="cni-content">
            @if(session('status'))
                <div class="cni-toast" role="status"><span aria-hidden="true">✓</span> {{ session('status') }}</div>
            @endif
            @yield('admin-content')
            <footer class="cni-footer"><span>© {{ date('Y') }} · Simulador CNI</span><span>Panel de gestión académica</span></footer>
        </main>
    </div>
</div>
@endsection
