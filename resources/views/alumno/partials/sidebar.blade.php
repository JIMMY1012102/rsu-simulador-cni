@php
    $esVistaPrevia = $esVistaPrevia ?? false;
    $paginaActiva = $paginaActiva ?? 'inicio';
    $nombreCompleto = trim($usuario->nombres.' '.($usuario->apellidos ?? ''));
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
@endphp
<aside class="cni-alumno-sidebar" aria-label="Menú del estudiante">
    <div class="cni-alumno-sidebar-top">
        <a class="cni-alumno-marca" href="{{ $esVistaPrevia ? route('alumno.salud-preview') : route('alumno.panel') }}" aria-label="Volver al inicio del simulador">
            <span class="cni-alumno-marca-icon" aria-hidden="true">✦</span>
            <span><strong>Simulador</strong><small>CNI · Imperial</small></span>
        </a>
        <span class="cni-alumno-area-chip"><span aria-hidden="true">{{ $area->codigo === 'A' ? '♥' : '◆' }}</span> {{ $area->nombre }}</span>
        <nav class="cni-alumno-menu" aria-label="Navegación principal">
            <a href="{{ $esVistaPrevia ? route('alumno.perfil-preview') : route('alumno.perfil') }}" @class(['is-active' => $paginaActiva === 'perfil']) @if($paginaActiva === 'perfil') aria-current="page" @endif><span aria-hidden="true">♙</span> Mi perfil</a>
            <a href="{{ $esVistaPrevia ? route('alumno.progreso-preview') : route('alumno.progreso') }}" @class(['is-active' => $paginaActiva === 'progreso']) @if($paginaActiva === 'progreso') aria-current="page" @endif><span aria-hidden="true">◇</span> Mi progreso</a>
            <a href="{{ $esVistaPrevia ? route('alumno.historial-preview') : route('alumno.historial') }}" @class(['is-active' => $paginaActiva === 'historial']) @if($paginaActiva === 'historial') aria-current="page" @endif><span aria-hidden="true">▤</span> Historial</a>
        </nav>
    </div>
    <div class="cni-alumno-sidebar-bottom">
        <div class="cni-alumno-side-art" aria-hidden="true">✦<span>✦</span>✦</div>
        <div class="cni-alumno-side-user"><span class="cni-alumno-avatar">{{ $iniciales }}</span><span><strong>{{ $nombreCompleto }}</strong><small>Estudiante · Área {{ $area->codigo }}</small></span></div>
        @unless($esVistaPrevia)
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="cni-alumno-salir" type="submit">↪ &nbsp; Cerrar sesión</button>
            </form>
        @endunless
    </div>
</aside>
