{{--
    "Mis intentos": simulacros abiertos para rendir y próximos simulacros.
    Datos: App\Http\Controllers\Alumno\IntentosController.
--}}
@extends('layouts.alumno', ['variante' => 'derecho', 'title' => 'Mis intentos'])

@section('content')
<div class="pagina">
    <header class="pagina-encabezado">
        <h1>Mis intentos</h1>
        <p>Rinde los simulacros abiertos. Si dejaste uno a medias, puedes continuarlo mientras no se acabe el tiempo.</p>
    </header>

    @error('simulacro_id')
        <p class="derecho-aviso derecho-aviso--error" role="alert">{{ $message }}</p>
    @enderror
    @error('preguntas')
        <p class="derecho-aviso derecho-aviso--error" role="alert">{{ $message }}</p>
    @enderror
    @error('carrera_id')
        <p class="derecho-aviso derecho-aviso--error" role="alert">{{ $message }}</p>
    @enderror

    <section class="pagina-seccion" aria-labelledby="titulo-abiertos">
        <h2 id="titulo-abiertos">Simulacros disponibles</h2>

        @if ($simulacros->isEmpty())
            <p class="inicio-tarjeta pagina-vacio">Por ahora no hay simulacros abiertos. Te avisaremos en la campana cuando haya uno.</p>
        @else
            <ul class="simulacros">
                @foreach ($simulacros as $simulacro)
                    <li class="inicio-tarjeta simulacro">
                        <div class="simulacro__datos">
                            <h3>{{ $simulacro->nombre }}</h3>
                            <ul class="simulacro__detalles">
                                <li><x-alumno.icono nombre="intentos" :tamano="16" /> {{ $simulacro->total_preguntas }} preguntas</li>
                                <li><x-alumno.icono nombre="reloj" :tamano="16" /> {{ $simulacro->tiempo_limite }} minutos</li>
                                <li><x-alumno.icono nombre="calendario" :tamano="16" />
                                    Cierra {{ \Illuminate\Support\Carbon::parse($simulacro->fecha_fin)->translatedFormat('d \d\e F, H:i') }}</li>
                            </ul>
                            <p class="simulacro__previos">
                                @if ($simulacro->intentos > 0)
                                    Lo rendiste {{ $simulacro->intentos }} {{ $simulacro->intentos == 1 ? 'vez' : 'veces' }}.
                                @else
                                    Aún no lo has rendido.
                                @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('examen.iniciar', $simulacro->id) }}">
                            @csrf
                            <button type="submit" @class(['boton', 'boton--secundario' => $simulacro->en_curso > 0])>
                                <x-alumno.icono nombre="play" :tamano="16" />
                                {{ $simulacro->en_curso > 0 ? 'Continuar examen' : 'Iniciar simulacro' }}
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($proximos->isNotEmpty())
        <section class="pagina-seccion" aria-labelledby="titulo-proximos">
            <h2 id="titulo-proximos">Próximos simulacros</h2>
            <ul class="simulacros">
                @foreach ($proximos as $simulacro)
                    <li class="inicio-tarjeta simulacro simulacro--proximo">
                        <div class="simulacro__datos">
                            <h3>{{ $simulacro->nombre }}</h3>
                            <ul class="simulacro__detalles">
                                <li><x-alumno.icono nombre="intentos" :tamano="16" /> {{ $simulacro->total_preguntas }} preguntas</li>
                                <li><x-alumno.icono nombre="reloj" :tamano="16" /> {{ $simulacro->tiempo_limite }} minutos</li>
                            </ul>
                        </div>
                        <span class="etiqueta"><x-alumno.icono nombre="candado" :tamano="14" />
                            Abre {{ \Illuminate\Support\Carbon::parse($simulacro->fecha_inicio)->translatedFormat('d \d\e F, H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <p class="pagina-nota">¿Ya terminaste un examen? Revisa tu puntaje en <a href="{{ route('alumno.historial') }}">Historial</a>.</p>
</div>
@endsection
