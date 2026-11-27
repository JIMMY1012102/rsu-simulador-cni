{{-- Evolución del puntaje del alumno; colores según su área. --}}
<section class="cni-progreso-grafica" aria-labelledby="progreso-titulo">
        <div class="cni-progreso-grafica-cabecera">
            <div>
                <h2 id="progreso-titulo">Evolución de tu puntaje</h2>
                <p>Porcentaje del puntaje máximo de cada simulacro, para comparar pruebas con escalas distintas.</p>
            </div>
            <span class="cni-progreso-chip">↗ {{ $progreso['cantidad'] }} {{ $progreso['cantidad'] === 1 ? 'intento' : 'intentos' }}</span>
        </div>
    @if($progreso['ultimo'])
        <div class="cni-progreso-grafica-marco" role="group" aria-label="Gráfica de evolución de simulacros calificados">
            <svg viewBox="0 0 920 298" class="cni-progreso-svg" role="img"
                 aria-labelledby="progreso-titulo evolucion-descripcion">
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
        </div>
        @if($progreso['truncado'])
            <p class="cni-progreso-nota">Se muestran los 60 simulacros calificados más recientes.</p>
        @endif
    @else
        <div class="cni-progreso-vacio">
            <p>Completa tu primer simulacro para ver tu progreso.</p>
        </div>
    @endif
</section>
