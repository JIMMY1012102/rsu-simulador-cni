<aside class="cni-sidebar" aria-label="Navegación del docente">
    <a class="cni-brand" href="{{ route('docente.panel') }}">
        <img src="{{ asset('img/escudo-cni.png') }}" alt="" class="cni-brand-logo">
        <span><strong>SIMULADOR CNI</strong><small>Portal docente</small></span>
    </a>
    <div class="cni-sidebar-caption">ESPACIO DE TRABAJO</div>
    <nav class="cni-menu" aria-label="Secciones del portal">
        <a href="{{ route('docente.panel') }}" class="cni-nav-link {{ request()->routeIs('docente.panel') ? 'is-active' : '' }}" @if(request()->routeIs('docente.panel')) aria-current="page" @endif><span aria-hidden="true">⌂</span> Inicio</a>
        <a href="{{ route('docente.calificaciones') }}" class="cni-nav-link {{ request()->routeIs('docente.calificaciones') ? 'is-active' : '' }}" @if(request()->routeIs('docente.calificaciones')) aria-current="page" @endif><span aria-hidden="true">▤</span> Calificaciones</a>
        <span class="cni-nav-link is-disabled" aria-disabled="true"><span aria-hidden="true">▧</span> Banco de preguntas <small>Próximamente</small></span>
        <span class="cni-nav-link is-disabled" aria-disabled="true"><span aria-hidden="true">◇</span> Simulacros <small>Próximamente</small></span>
    </nav>
    <div class="cni-sidebar-bottom">
        <div class="cni-section-pill"><span class="cni-section-dot"></span><span>Mi sección<strong>{{ $seccion && $seccion->activo ? '5.º '.$seccion->nombre : 'Sin asignar' }}</strong></span></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="cni-logout" type="submit">↪ <span>Cerrar sesión</span></button></form>
    </div>
</aside>