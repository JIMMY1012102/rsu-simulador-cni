{{-- Paginación simple del portal del alumno (Anterior / Página X de Y / Siguiente). --}}
@if ($paginator->hasPages())
    <nav class="paginacion" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="boton boton--secundario is-deshabilitado" aria-disabled="true">Anterior</span>
        @else
            <a class="boton boton--secundario" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <span class="paginacion__estado">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="boton boton--secundario" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span class="boton boton--secundario is-deshabilitado" aria-disabled="true">Siguiente</span>
        @endif
    </nav>
@endif
