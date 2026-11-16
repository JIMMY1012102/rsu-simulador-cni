<div class="cni-historial-tarjeta">
    <div class="cni-historial-cabecera">
        <h2 id="historial-titulo">Historial de simulacros</h2>
        <form class="cni-historial-filtros" method="GET" action="{{ route('alumno.panel') }}#historial" role="search" aria-label="Filtrar historial">
            <input type="search" name="buscar" value="{{ is_string(request('buscar')) ? mb_substr(request('buscar'), 0, 100) : '' }}" maxlength="100"
                   placeholder="Buscar intento" aria-label="Buscar por número de intento o simulacro">
            <select name="estado" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="en_curso" @selected(request('estado') === 'en_curso')>En curso</option>
                <option value="calificado" @selected(request('estado') === 'calificado')>Calificado</option>
                <option value="pendiente" @selected(request('estado') === 'pendiente')>Sin calificar</option>
            </select>
            <select name="orden" aria-label="Ordenar intentos">
                <option value="reciente" @selected(request('orden', 'reciente') === 'reciente')>Ordenar: más reciente</option>
                <option value="antiguo" @selected(request('orden') === 'antiguo')>Ordenar: más antiguo</option>
                <option value="puntaje" @selected(request('orden') === 'puntaje')>Ordenar: mayor puntaje</option>
            </select>
            <noscript><button type="submit" class="cni-historial-aplicar">Filtrar</button></noscript>
        </form>
    </div>
    <div class="cni-historial-tabla-marco" tabindex="0" role="region" aria-label="Tabla del historial de simulacros">
        <table class="cni-historial-tabla">
            <thead><tr>
                <th scope="col">Intento</th><th scope="col">Fecha</th>
                <th scope="col">Tipo de examen</th><th scope="col">Puntaje</th>
                <th scope="col">Estado</th><th scope="col">Acciones</th>
            </tr></thead>
            <tbody>
                @forelse($intentos as $intento)
                    @php
                        $enCurso = ($intento->estado ?? '') === 'en_curso';
                        $calificado = ! $enCurso && $intento->puntaje_total !== null;
                        $maximo = (int) ($intento->total_preguntas ?? 0) * (float) ($intento->puntaje_correcta ?? 0);
                    @endphp
                    <tr>
                        <th scope="row">#{{ str_pad((string) $intento->id, 3, '0', STR_PAD_LEFT) }}</th>
                        <td>{{ $intento->fecha_inicio ? \Illuminate\Support\Carbon::parse($intento->fecha_inicio)->format('d/m/Y') : '—' }}</td>
                        <td>{{ $intento->simulacro_nombre ?? 'Simulacro general' }}</td>
                        <td class="cni-historial-puntaje">
                            @if($calificado)
                                {{ number_format((float) $intento->puntaje_total, 2, ',', '.') }}
                                @if($maximo > 0) / {{ number_format($maximo, 2, ',', '.') }} @endif
                            @else — @endif
                        </td>
                        <td><span class="cni-historial-estado">{{ $enCurso ? 'En curso' : ($calificado ? 'Calificado' : 'Sin calificar') }}</span></td>
                        <td>
                            @if($esVistaPrevia)
                                <span>Vista previa</span>
                            @elseif($calificado)
                                <a class="cni-historial-resultado" href="{{ route('alumno.resultados', ['intento' => $intento->id]) }}">Ver resultado</a>
                            @else
                                <span>{{ $enCurso ? 'En curso' : 'Pendiente' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="cni-historial-vacio">No hay simulacros que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($intentos, 'hasPages') && $intentos->hasPages())
        <nav class="cni-historial-paginacion" aria-label="Páginas del historial">
            @if($intentos->previousPageUrl()) <a href="{{ $intentos->previousPageUrl() }}">← Anterior</a> @endif
            <span>Página {{ $intentos->currentPage() }} de {{ $intentos->lastPage() }}</span>
            @if($intentos->nextPageUrl()) <a href="{{ $intentos->nextPageUrl() }}">Siguiente →</a> @endif
        </nav>
    @endif
</div>

<script>
(() => {
    const form = document.querySelector('.cni-historial-filtros');
    if (!form) return;
    form.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => form.requestSubmit());
    });
    form.querySelector('input[type="search"]').addEventListener('search', () => form.requestSubmit());
})();
</script>
