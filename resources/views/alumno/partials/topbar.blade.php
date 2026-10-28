@php
    $nombreCompleto = trim($usuario->nombres.' '.($usuario->apellidos ?? ''));
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
@endphp
<header class="cni-alumno-topbar">
    <div class="cni-alumno-top-title"><span class="cni-alumno-topicon" aria-hidden="true">{{ $area->codigo === 'A' ? '♥' : '◆' }}</span><span><strong>{{ $tituloPagina ?? $area->nombre }}</strong><small>Simulador de Examen de Admisión CNI</small></span></div>
    <div class="cni-alumno-top-profile"><span class="cni-alumno-avatar">{{ $iniciales }}</span><span><strong>{{ $nombreCompleto }}</strong><small>Estudiante</small></span></div>
</header>
