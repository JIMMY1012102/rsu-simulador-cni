@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
@endpush

@section('content')
<div class="cni-workspace">
    <aside class="cni-sidebar" aria-label="Menú de administración">
        <a class="cni-brand" href="{{ route('admin.inicio') }}" aria-label="CNI · Ir al panel">
            <span class="cni-brand__seal"><img src="{{ asset('images/escudo-cni.png') }}" alt="" loading="eager"></span>
            <span class="cni-brand__copy"><strong>SIMULADOR CNI</strong><small>Gestión académica</small></span>
        </a>
        <div class="cni-sidebar__scroll">
            <div class="cni-nav-caption">ESPACIO DE TRABAJO</div>
            <nav class="cni-nav" aria-label="Secciones">
                <a href="{{ route('admin.inicio') }}" @class(['cni-nav__link', 'is-current' => request()->routeIs('admin.inicio')]) @if(request()->routeIs('admin.inicio')) aria-current="page" @endif>
                    <span class="cni-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span>
                    <span>Resumen general</span>
                </a>
                <a href="{{ route('admin.preguntas.index') }}" @class(['cni-nav__link', 'is-current' => request()->routeIs('admin.preguntas.*')]) @if(request()->routeIs('admin.preguntas.*')) aria-current="page" @endif>
                    <span class="cni-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 5.5v15A2.5 2.5 0 0 1 6.5 18H20"/><path d="M8 8h8M8 12h6"/></svg></span>
                    <span>Banco de preguntas</span>
                </a>
                <a href="{{ route('admin.simulacros.index') }}" @class(['cni-nav__link', 'is-current' => request()->routeIs('admin.simulacros.*')]) @if(request()->routeIs('admin.simulacros.*')) aria-current="page" @endif>
                    <span class="cni-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 3h6l1 2h4v16H4V5h4z"/><path d="M9 12l2 2 4-4M8 18h8"/></svg></span>
                    <span>Simulacros</span>
                </a>
                <a href="{{ route('admin.inicio') }}#catalogos" class="cni-nav__link">
                    <span class="cni-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 2 8.5 5v10L12 22 3.5 17V7z"/><path d="m3.5 7 8.5 5 8.5-5M12 12v10"/></svg></span>
                    <span>Catálogos</span>
                </a>
            </nav>
            <div class="cni-nav-caption cni-nav-caption--second">ACCESOS RÁPIDOS</div>
            <nav class="cni-nav" aria-label="Accesos rápidos">
                <a class="cni-nav__link cni-nav__link--subtle" href="{{ route('admin.preguntas.create') }}">
                    <span class="cni-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 4v16M4 12h16"/></svg></span>
                    <span>Nueva pregunta</span>
                </a>
                <a class="cni-nav__link cni-nav__link--subtle" href="{{ route('admin.simulacros.create') }}">
                    <span class="cni-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 4v16M4 12h16"/></svg></span>
                    <span>Nuevo simulacro</span>
                </a>
            </nav>
        </div>
        <div class="cni-sidebar__footer">
            <div class="cni-sidebar__notice"><span class="cni-live-dot"></span> Panel administrativo <strong>activo</strong></div>
            <div class="cni-sidebar__meta">Centro de preparación · CNI</div>
        </div>
    </aside>

    <div class="cni-stage">
        <header class="cni-topbar">
            <div class="cni-topbar__context"><span class="cni-topbar__section">Área administrativa</span><span class="cni-topbar__separator">/</span><strong>@yield('admin-page', 'Resumen')</strong></div>
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
