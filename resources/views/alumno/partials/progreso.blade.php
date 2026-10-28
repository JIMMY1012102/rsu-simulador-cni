{{-- Una misma vista para las cinco áreas. Todos los valores llegan de ProgresoAlumnoService. --}}
@php
    $ultimo = $progreso['ultimo'];
    $diferencia = $progreso['diferencia'];
@endphp

<header class="cni-progreso-encabezado">
    <div>
        <span class="cni-alumno-section-kicker">TU RENDIMIENTO · {{ $area->nombre }}</span>
        <h1 id="progreso-titulo">Mi progreso</h1>
        <p>Consulta tus puntajes reales y cómo evolucionas con cada simulacro.</p>
    </div>
    <button type="button" class="cni-progreso-accion" data-go-panel="#intentos">Rendir simulacro <span aria-hidden="true">↗</span></button>
</header>

@if($ultimo)
    <section class="cni-progreso-resumen" aria-label="Resumen del último intento">
        <div class="cni-progreso-anillo" role="img" aria-label="Último puntaje: {{ number_format($ultimo['porcentaje'], 1, ',', '.') }} por ciento del máximo">
            <svg viewBox="0 0 120 120" aria-hidden="true" focusable="false">
                <circle cx="60" cy="60" r="47" class="cni-progreso-anillo-fondo" />
                <circle cx="60" cy="60" r="47" class="cni-progreso-anillo-avance"
                        stroke-dasharray="{{ round($ultimo['porcentaje'] * 2.9531, 2) }} 295.31"
                        transform="rotate(-90 60 60)" />
            </svg>
            <div><strong>{{ number_format($ultimo['porcentaje'], 1, ',', '.') }}%</strong><span>del máximo</span></div>
        </div>
        <div class="cni-progreso-resumen-datos">
            <span class="cni-alumno-section-kicker">ÚLTIMO SIMULACRO CALIFICADO</span>
            <h2>Tu último intento</h2>
            <p class="cni-progreso-puntaje">{{ number_format($ultimo['puntaje'], 2, ',', '.') }} puntos</p>
            <p class="cni-progreso-nombre">{{ $ultimo['nombre'] }}</p>
            <div class="cni-progreso-barra" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                 aria-valuenow="{{ $ultimo['porcentaje'] }}" aria-label="Porcentaje del puntaje máximo">
                <span style="width:{{ $ultimo['porcentaje'] }}%"></span>
            </div>
            <div class="cni-progreso-barra-etiquetas"><span>0</span><strong>{{ number_format($ultimo['puntaje'], 2, ',', '.') }} / {{ number_format($ultimo['maximo'], 2, ',', '.') }} puntos</strong><span>{{ number_format($ultimo['maximo'], 2, ',', '.') }}</span></div>
        </div>
        <aside class="cni-progreso-mensaje" aria-label="Comparación con el intento anterior">
            <span class="cni-progreso-mensaje-icono" aria-hidden="true">✦</span>
            <div>
                <h3>
                    @if($diferencia === null) ¡Tu primer resultado!
                    @elseif($diferencia > 0) ¡Vas mejorando!
                    @elseif($diferencia < 0) ¡Sigue practicando!
                    @else ¡Mantienes tu rendimiento!
                    @endif
                </h3>
                <p>
                    @if($diferencia === null)
                        Este es tu primer simulacro calificado. A medida que rindas más, verás tu evolución.
                    @elseif($diferencia > 0)
                        Subiste {{ number_format($diferencia, 1, ',', '.') }} puntos porcentuales respecto a tu intento anterior.
                    @elseif($diferencia < 0)
                        Bajaste {{ number_format(abs($diferencia), 1, ',', '.') }} puntos porcentuales respecto a tu intento anterior.
                    @else
                        Obtuviste el mismo porcentaje de puntaje que en tu intento anterior.
                    @endif
                </p>
            </div>
        </aside>
    </section>

    <section class="cni-progreso-grafica" aria-labelledby="evolucion-titulo">
        <div class="cni-progreso-grafica-cabecera">
            <div>
                <h2 id="evolucion-titulo">Evolución de tu puntaje</h2>
                <p>Porcentaje del puntaje máximo de cada simulacro, para comparar pruebas con escalas distintas.</p>
            </div>
            <span class="cni-progreso-chip">↗ {{ $progreso['cantidad'] }} {{ $progreso['cantidad'] === 1 ? 'intento' : 'intentos' }}</span>
        </div>
        <div class="cni-progreso-grafica-marco" role="group" aria-label="Gráfica de evolución de simulacros calificados">
            <svg viewBox="0 0 920 298" class="cni-progreso-svg" role="img"
                 aria-labelledby="evolucion-titulo evolucion-descripcion">
                <desc id="evolucion-descripcion">Evolución cronológica en porcentaje de puntaje máximo. Los puntos corresponden a simulacros calificados del estudiante.</desc>
                @foreach([100, 75, 50, 25, 0] as $nivel)
                    @php $y = 242 - $nivel * 1.9; @endphp
                    <line x1="52" y1="{{ $y }}" x2="868" y2="{{ $y }}" class="cni-progreso-linea-guia" />
                    <text x="42" y="{{ $y + 4 }}" text-anchor="end" class="cni-progreso-eje">{{ $nivel }}%</text>
                @endforeach
                @if(count($progreso['puntos']) > 1)
                    <path class="cni-progreso-linea" d="{{ $progreso['trayectoria'] }}" />
                @endif
                @foreach($progreso['puntos'] as $indice => $punto)
                    <circle class="cni-progreso-punto" cx="{{ $punto['x'] }}" cy="{{ $punto['y'] }}" r="5">
                        <title>Intento {{ $indice + 1 }} · {{ $punto['nombre'] }} · {{ number_format($punto['puntaje'], 2, ',', '.') }} de {{ number_format($punto['maximo'], 2, ',', '.') }} puntos · {{ $punto['porcentaje'] }}%</title>
                    </circle>
                    @if(count($progreso['puntos']) <= 12 || $indice === 0 || $indice === count($progreso['puntos']) - 1 || $indice % 5 === 0)
                        <text x="{{ $punto['x'] }}" y="267" text-anchor="middle" class="cni-progreso-eje-intento">{{ $indice + 1 }}</text>
                    @endif
                @endforeach
                <text x="460" y="290" text-anchor="middle" class="cni-progreso-eje-pie">Intentos calificados (orden cronológico)</text>
            </svg>
        </div>
        <div class="cni-progreso-leyenda">
            <span><i aria-hidden="true"></i> Puntaje obtenido respecto al máximo posible</span>
            <span>El gráfico no representa niveles de admisión ni puntajes de corte.</span>
        </div>
        @if($progreso['truncado'])
            <p class="cni-progreso-nota">Se muestran los 60 simulacros calificados más recientes.</p>
        @endif
        @if($diferencia !== null)
            <div class="cni-progreso-comparacion">
                @if($diferencia > 0) ↗ Tu último resultado mejoró {{ number_format($diferencia, 1, ',', '.') }} puntos porcentuales frente al anterior.
                @elseif($diferencia < 0) ↘ Tu último resultado fue {{ number_format(abs($diferencia), 1, ',', '.') }} puntos porcentuales menor que el anterior.
                @else → Tus dos últimos resultados tuvieron el mismo porcentaje.
                @endif
            </div>
        @endif
    </section>
@else
    <div class="cni-progreso-vacio">
        <span aria-hidden="true">▥</span>
        <h2>Aún no hay intentos calificados</h2>
        <p>Cuando termines tu primer simulacro y se registre su puntaje, aparecerán aquí tu porcentaje real y tu gráfica de progreso.</p>
        <button type="button" class="cni-progreso-accion" data-go-panel="#intentos">Ver simulacros disponibles</button>
    </div>
@endif
