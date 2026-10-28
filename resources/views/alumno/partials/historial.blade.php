{{-- La misma pantalla de Historial funciona para las cinco áreas, con datos reales del usuario. --}}
@php
    $numeroHistorial = static function ($valor): string {
        $valor = (float) $valor;
        return abs($valor - round($valor)) < 0.005
            ? number_format($valor, 0, ',', '.')
            : rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    };
    $textoPuntaje = static function (?array $registro) use ($numeroHistorial): string {
        if ($registro === null) {
            return '—';
        }
        if ($registro['puntaje'] === null || $registro['maximo'] === null) {
            return $registro['porcentaje'] !== null
                ? $numeroHistorial($registro['porcentaje']).'%'
                : '—';
        }
        return $numeroHistorial($registro['puntaje']).' / '.$numeroHistorial($registro['maximo']);
    };
@endphp

<div class="cni-historial" data-historial>
    <div class="cni-historial-metricas" aria-label="Resumen de intentos">
        <article class="cni-historial-metrica">
            <span class="cni-historial-icono" aria-hidden="true">▤</span>
            <div><strong>{{ $historial['total'] }}</strong><span>Intentos realizados</span></div>
        </article>
        <article class="cni-historial-metrica">
            <span class="cni-historial-icono" aria-hidden="true">🏆</span>
            <div><strong>{{ $textoPuntaje($historial['mejor']) }}</strong><span>Mejor puntaje</span></div>
        </article>
        <article class="cni-historial-metrica">
            <span class="cni-historial-icono" aria-hidden="true">▥</span>
            <div><strong>{{ $textoPuntaje($historial['ultimo']) }}</strong><span>Último puntaje</span></div>
        </article>
        <article class="cni-historial-metrica">
            <span class="cni-historial-icono" aria-hidden="true">♟</span>
            <div><strong>{{ $textoPuntaje($historial['promedio']) }}</strong><span>Promedio general</span></div>
        </article>
    </div>

    <section class="cni-historial-panel" aria-labelledby="historial-titulo">
        <div class="cni-historial-encabezado">
            <h2 id="historial-titulo">Historial de simulacros</h2>
            <div class="cni-historial-controles">
                <label class="cni-historial-sr" for="cni-historial-buscar">Buscar intento</label>
                <input id="cni-historial-buscar" type="search" placeholder="⌕  Buscar intento" autocomplete="off"
                       data-historial-buscar>
                <label class="cni-historial-sr" for="cni-historial-filtrar">Filtrar por nivel</label>
                <select id="cni-historial-filtrar" data-historial-filtrar>
                    <option value="todos">Todos los niveles</option>
                    <option value="sin-nivel">Sin nivel</option>
                    <option value="en-curso">En curso</option>
                </select>
                <label class="cni-historial-sr" for="cni-historial-ordenar">Ordenar intentos</label>
                <select id="cni-historial-ordenar" data-historial-ordenar>
                    <option value="reciente">Ordenar: más reciente</option>
                    <option value="antiguo">Ordenar: más antiguo</option>
                    <option value="puntaje">Ordenar: mejor puntaje</option>
                </select>
            </div>
        </div>

        <div class="cni-historial-scroll">
            <table class="cni-historial-tabla" aria-label="Historial de simulacros del estudiante">
                <thead><tr>
                    <th scope="col">Intento</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">Tipo de examen</th>
                    <th scope="col">Puntaje</th>
                    <th scope="col">Nivel alcanzado</th>
                    <th scope="col">Evolución</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody data-historial-filas>
                    @foreach($historial['registros'] as $registro)
                        <tr data-historial-fila
                            data-id="{{ $registro['id'] }}"
                            data-fecha="{{ $registro['fecha_orden'] }}"
                            data-nivel="{{ $registro['nivel'] }}"
                            data-puntaje="{{ $registro['porcentaje'] ?? -100000 }}"
                            data-busqueda="{{ '#'.$registro['id'].' '.$registro['tipo'].' '.$registro['nombre'].' '.$registro['fecha'] }}">
                            <td class="cni-historial-id">#{{ str_pad((string) $registro['id'], 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $registro['fecha'] }}</td>
                            <td title="{{ $registro['nombre'] }}">{{ $registro['tipo'] }}</td>
                            <td class="cni-historial-puntaje">
                                @if($registro['puntaje'] !== null)
                                    {{ $numeroHistorial($registro['puntaje']) }}
                                    @if($registro['maximo'] !== null)
                                        / {{ $numeroHistorial($registro['maximo']) }}
                                    @endif
                                @else
                                    <span class="cni-historial-guion">—</span>
                                @endif
                            </td>
                            <td>
                                <span @class(['cni-historial-estado', 'cni-historial-estado-curso' => $registro['nivel'] === 'en-curso'])>
                                    {{ $registro['nivel'] === 'en-curso' ? 'En curso' : 'Sin nivel' }}
                                </span>
                            </td>
                            <td>
                                @if($registro['evolucion'] === null)
                                    <span class="cni-historial-guion">—</span>
                                @else
                                    <span @class([
                                        'cni-historial-evolucion',
                                        'cni-historial-evolucion-sube' => $registro['evolucion'] > 0,
                                        'cni-historial-evolucion-baja' => $registro['evolucion'] < 0
                                    ])>
                                        {{ $registro['evolucion'] > 0 ? '↑ +' : ($registro['evolucion'] < 0 ? '↓ ' : '→ ') }}{{ $numeroHistorial($registro['evolucion']) }}
                                        {{ $registro['unidad_evolucion'] === 'pp' ? 'pp' : '' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($registro['puntaje'] !== null && ! $esVistaPrevia)
                                    <a class="cni-historial-resultado" href="{{ route('alumno.resultados', ['intento' => $registro['id']]) }}">
                                        Ver resultado
                                    </a>
                                @elseif($registro['nivel'] === 'en-curso')
                                    <span class="cni-historial-guion">En curso</span>
                                @else
                                    <span class="cni-historial-guion">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="cni-historial-sin-resultados" data-historial-vacio hidden>No se encontraron intentos con esos filtros.</p>
        @if($historial['total'] === 0)
            <p class="cni-historial-sin-datos">Aún no tienes simulacros registrados. Cuando inicies uno, aparecerá aquí.</p>
        @endif
        <div class="cni-historial-pie">
            <span data-historial-conteo aria-live="polite">{{ $historial['total'] }} de {{ $historial['total'] }} intentos</span>
            <span>Los niveles de admisión aún no están definidos; se muestra «Sin nivel».</span>
        </div>
    </section>
</div>
<script>
(() => {
    const raiz = document.querySelector('[data-historial]');
    if (!raiz) return;
    const tabla = raiz.querySelector('[data-historial-filas]');
    const filas = [...tabla.querySelectorAll('[data-historial-fila]')];
    const buscar = raiz.querySelector('[data-historial-buscar]');
    const filtro = raiz.querySelector('[data-historial-filtrar]');
    const orden = raiz.querySelector('[data-historial-ordenar]');
    const conteo = raiz.querySelector('[data-historial-conteo]');
    const vacio = raiz.querySelector('[data-historial-vacio]');

    const actualizar = () => {
        const termino = buscar.value.trim().toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const nivel = filtro.value;
        let visibles = 0;
        const ordenadas = [...filas].sort((a, b) => {
            if (orden.value === 'puntaje') {
                return Number(b.dataset.puntaje) - Number(a.dataset.puntaje)
                    || Number(b.dataset.id) - Number(a.dataset.id);
            }
            const diferencia = a.dataset.fecha.localeCompare(b.dataset.fecha)
                || Number(a.dataset.id) - Number(b.dataset.id);
            return orden.value === 'antiguo' ? diferencia : -diferencia;
        });
        ordenadas.forEach(fila => {
            const texto = fila.dataset.busqueda.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            fila.hidden = (nivel !== 'todos' && fila.dataset.nivel !== nivel)
                || !texto.includes(termino);
            if (!fila.hidden) visibles++;
            tabla.appendChild(fila);
        });
        conteo.textContent = visibles + ' de ' + filas.length + ' intentos';
        vacio.hidden = visibles !== 0 || filas.length === 0;
    };

    buscar.addEventListener('input', actualizar);
    filtro.addEventListener('change', actualizar);
    orden.addEventListener('change', actualizar);
    actualizar();
})();
</script>
