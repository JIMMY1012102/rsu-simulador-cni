@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
@endpush

@section('content')
<div class="cni-workspace">
    <aside class="cni-sidebar" id="cni-admin-sidebar" aria-label="Menú de administración">
        <button type="button" class="cni-admin-sidebar-close" data-admin-menu-close aria-label="Cerrar menú lateral">×</button>
        <a class="cni-brand" href="{{ route('admin.inicio') }}" aria-label="CNI · Ir al panel">
            <span class="cni-brand__seal"><img src="{{ asset('images/escudo-cni.png') }}" alt="" loading="eager"></span>
            <span class="cni-brand__copy"><strong>SIMULADOR CNI</strong><small>Colegio Nacional de Imperial</small></span>
        </a>
        <div class="cni-sidebar__scroll">
            <div class="cni-nav-caption">BANCO DE PREGUNTAS</div>
            <nav class="cni-nav" aria-label="Secciones del banco">
                @foreach([
                    ['admin.subtemas.index', 'Categorías', '▤', 'admin.subtemas.*'],
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

    <button type="button" class="cni-admin-backdrop" data-admin-menu-backdrop aria-label="Cerrar menú lateral" hidden></button>
    <div class="cni-stage">
        <header class="cni-topbar">
            <div class="cni-topbar__context"><button type="button" class="cni-admin-menu-toggle" data-admin-menu-toggle aria-controls="cni-admin-sidebar" aria-expanded="true" aria-label="Ocultar menú lateral"><span aria-hidden="true">☰</span></button><span class="cni-topbar__section">Banco de preguntas</span><span class="cni-topbar__separator">/</span><strong>@yield('admin-page', 'Resumen')</strong></div>
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
<script>
(() => {
    const workspace = document.querySelector('.cni-workspace');
    if (!workspace) return;
    const sidebar = workspace.querySelector('#cni-admin-sidebar');
    const toggle = workspace.querySelector('[data-admin-menu-toggle]');
    const close = workspace.querySelector('[data-admin-menu-close]');
    const backdrop = workspace.querySelector('[data-admin-menu-backdrop]');
    const mobile = window.matchMedia('(max-width: 900px)');
    let desktopOpen = true;
    try { desktopOpen = localStorage.getItem('cni.admin.menu') !== 'closed'; } catch {}
    let open = mobile.matches ? false : desktopOpen;
    const render = () => {
        workspace.classList.toggle('is-admin-menu-closed', !open);
        workspace.classList.toggle('is-admin-menu-open', open);
        sidebar.inert = !open;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Ocultar menú lateral' : 'Mostrar menú lateral');
        toggle.title = open ? 'Ocultar menú lateral' : 'Mostrar menú lateral';
        backdrop.hidden = !mobile.matches || !open;
    };
    const hide = () => { open = false; render(); toggle.focus(); };
    toggle.addEventListener('click', () => {
        open = !open;
        if (!mobile.matches) {
            desktopOpen = open;
            try { localStorage.setItem('cni.admin.menu', open ? 'open' : 'closed'); } catch {}
        }
        render();
    });
    close.addEventListener('click', hide);
    backdrop.addEventListener('click', hide);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && mobile.matches && open) hide();
    });
    mobile.addEventListener('change', () => { open = mobile.matches ? false : desktopOpen; render(); });
    render();
})();
</script>
@endsection
