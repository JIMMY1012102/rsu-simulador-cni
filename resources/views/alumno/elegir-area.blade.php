@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/selector-areas.css') }}">
@endpush

@section('content')
@php
    // Solo cambia la presentación visual: los códigos A-E y los IDs reales se conservan.
    // Orden de las tarjetas de izquierda a derecha según la referencia.
    $ordenVisual = ['B' => 1, 'A' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
    $areasOrdenadas = $areas->sortBy(fn ($area) => $ordenVisual[$area->codigo] ?? 99);

    $presentaciones = [
        'B' => [
            'titulo' => 'Ciencias Básicas',
            'descripcion' => 'Explora el mundo a través de la ciencia y el conocimiento.',
        ],
        'A' => [
            'titulo' => 'Ciencias de la Salud',
            'descripcion' => 'Vocación para cuidar vidas y transformar realidades.',
        ],
        'C' => [
            'titulo' => 'Ingenierías',
            'descripcion' => 'Diseña soluciones para un mejor mañana.',
        ],
        'D' => [
            'titulo' => 'Ciencias Económicas y de Gestión',
            'descripcion' => 'Impulsa ideas para un mundo con más oportunidades.',
        ],
        'E' => [
            'titulo' => 'Humanidades',
            'descripcion' => 'Comprende, conecta y construye una sociedad más humana.',
        ],
    ];
@endphp

<section class="selector-page" aria-labelledby="selector-titulo">
    <form class="selector-page__salir" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>

    <div class="selector-page__encabezado">
        <p class="selector-page__eyebrow">PRIMERA VEZ <strong>EN EL SIMULADOR</strong></p>
        <h1 id="selector-titulo">¿A qué área deseas postular?</h1>
        <p class="selector-page__subtitulo">Selecciona una opción para personalizar tu panel de estudio</p>
    </div>

    <div class="selector-page__grid" role="group" aria-label="Cinco áreas de postulación">
        @forelse ($areasOrdenadas as $area)
            @php
                $presentacion = $presentaciones[$area->codigo] ?? [
                    'titulo' => $area->nombre,
                    'descripcion' => 'Prepárate para tu examen de admisión.',
                ];
            @endphp
            <form class="area-opcion" data-area="{{ $area->codigo }}"
                  method="POST" action="{{ route('alumno.area.guardar') }}"
                  aria-labelledby="area-titulo-{{ $area->id }}">
                @csrf
                <input type="hidden" name="area_id" value="{{ $area->id }}">

                {{-- Zona deliberadamente libre de imágenes: aquí se insertará cada mascota. --}}
                <div class="area-opcion__ilustracion" aria-hidden="true">
                    <div class="area-opcion__mascota">
                        <span>Espacio para mascota</span>
                    </div>
                </div>

                <div class="area-opcion__info">
                    <span class="area-opcion__icono" aria-hidden="true">
                        @switch($area->codigo)
                            @case('B')
                                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <ellipse cx="16" cy="16" rx="13" ry="5.8"/>
                                    <ellipse cx="16" cy="16" rx="13" ry="5.8" transform="rotate(60 16 16)"/>
                                    <ellipse cx="16" cy="16" rx="13" ry="5.8" transform="rotate(120 16 16)"/>
                                    <circle cx="16" cy="16" r="2.5" fill="currentColor" stroke="none"/>
                                </svg>
                                @break
                            @case('A')
                                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 17h6l4-8 5 15 4-7h7"/>
                                </svg>
                                @break
                            @case('C')
                                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round">
                                    <path d="M13 3h6l1 4.1 3 1.3 3.6-2.2 4.2 4.2-2.2 3.6 1.3 3 4.1 1v6l-4.1 1-1.3 3-3.6 2.2-4.2-4.2-3 1.3-1 4.1h-6l-1-4.1-3-1.3-3.6 2.2L.2 24l2.2-3.6-1.3-3L-3 16v-6l4.1-1 1.3-3 3.6-2.2L10.2 8l3-1.3z" transform="translate(3.8 1.5) scale(.75)"/>
                                    <circle cx="16" cy="16" r="5"/>
                                </svg>
                                @break
                            @case('D')
                                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 28V16h5v12M14 28V11h5v17M24 28V6h5v22"/>
                                    <path d="m4 13 9-7 6 3 9-7"/>
                                </svg>
                                @break
                            @case('E')
                                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 8C12 5 8 5 3 6v19c5-1 9-1 13 2V8Zm0 0c4-3 8-3 13-2v19c-5-1-9-1-13 2"/>
                                    <path d="M16 8v19"/>
                                </svg>
                                @break
                            @default
                                <span>{{ $area->codigo }}</span>
                        @endswitch
                    </span>

                    <h2 id="area-titulo-{{ $area->id }}" title="{{ $area->nombre }}">{{ $presentacion['titulo'] }}</h2>
                    <p class="area-opcion__descripcion">{{ $presentacion['descripcion'] }}</p>
                    <button class="area-opcion__seleccionar" type="submit"
                            aria-label="Seleccionar {{ $area->nombre }}">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 12h16m-7-7 7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </form>
        @empty
            <p class="selector-page__sin-areas">No hay áreas habilitadas. Comunícate con el administrador.</p>
        @endforelse
    </div>

    <p class="selector-page__nota">Tu selección se guardará y en tu próximo ingreso accederás directamente a tu panel.</p>
</section>
@endsection
