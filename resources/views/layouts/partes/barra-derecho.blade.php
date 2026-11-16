{{--
    Barra superior de la variante "derecho": facultad, campana de avisos y menú del usuario.
    Los avisos de la campana salen de App\Services\AvisosAlumnoService en cada página.
--}}
@php
    $carreraBarra = $usuario->carrera?->nombre;
    $nombreCompleto = \Illuminate\Support\Str::title(trim($usuario->nombres.' '.\Illuminate\Support\Str::before(($usuario->apellidos ?? '').' ', ' ')));
    // La tabla usuarios aún no tiene foto; si alguien agrega la columna "foto" (ruta en storage), se mostrará.
    $foto = $usuario->getAttributes()['foto'] ?? null;
    $avisos = app(\App\Services\AvisosAlumnoService::class)->para($usuario);
@endphp
<header class="portal-barra derecho-barra">
    <div class="derecho-facultad">
        <span class="derecho-facultad__logo"><x-alumno.icono nombre="balanza" :tamano="22" /></span>
        <span class="derecho-facultad__texto">
            <strong>{{ $carreraBarra ? 'Facultad de '.$carreraBarra : 'Portal del alumno' }}</strong>
            <small>Simulador de Examen de Admisión CNI</small>
        </span>
    </div>

    <div class="portal-cuenta">
        <div class="derecho-avisos" x-data="{ abierto: false }" @click.outside="abierto = false" @keydown.escape="abierto = false">
            <button type="button" class="derecho-campana" aria-haspopup="true"
                aria-expanded="false" :aria-expanded="abierto ? 'true' : 'false'" @click="abierto = !abierto"
                aria-label="{{ count($avisos) ? 'Notificaciones: '.count($avisos).' sin revisar' : 'Notificaciones' }}">
                <x-alumno.icono nombre="campana" :tamano="20" />
                @if (count($avisos))
                    <span class="derecho-campana__punto" aria-hidden="true"></span>
                @endif
            </button>

            <div class="derecho-usuario__menu derecho-avisos__menu" x-show="abierto" x-cloak x-transition.opacity>
                <p class="derecho-avisos__titulo">Notificaciones</p>
                @forelse ($avisos as $aviso)
                    <a href="{{ $aviso['enlace'] }}" @class(['derecho-aviso-item', 'is-urgente' => $aviso['tipo'] === 'en_curso'])>
                        <x-alumno.icono :nombre="$aviso['tipo'] === 'en_curso' ? 'reloj' : 'intentos'" :tamano="16" />
                        <span><strong>{{ $aviso['texto'] }}</strong><small>{{ $aviso['detalle'] }}</small></span>
                    </a>
                @empty
                    <p class="derecho-avisos__vacio">No tienes notificaciones nuevas.</p>
                @endforelse
            </div>
        </div>

        <div class="derecho-usuario" x-data="{ abierto: false }" @click.outside="abierto = false" @keydown.escape="abierto = false">
            <button type="button" class="derecho-usuario__boton" aria-haspopup="true"
                aria-expanded="false" :aria-expanded="abierto ? 'true' : 'false'" @click="abierto = !abierto">
                @if ($foto)
                    <img class="portal-avatar derecho-avatar" src="{{ asset('storage/'.$foto) }}" alt="">
                @else
                    <span class="portal-avatar derecho-avatar" aria-hidden="true">{{ $iniciales }}</span>
                @endif
                <span class="derecho-usuario__datos">
                    <strong>{{ $nombreCompleto }}</strong>
                    <small>{{ $perfil }}</small>
                </span>
                <x-alumno.icono nombre="flecha-abajo" :tamano="16" />
            </button>

            <div class="derecho-usuario__menu" x-show="abierto" x-cloak x-transition.opacity>
                <a href="{{ route('alumno.perfil') }}"><x-alumno.icono nombre="perfil" :tamano="16" /> Mi perfil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><x-alumno.icono nombre="salir" :tamano="16" /> Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</header>
