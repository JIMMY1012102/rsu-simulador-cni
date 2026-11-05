<div>
    <p class="eyebrow">TU ESPACIO DE PREPARACIÓN</p>
    <h1>Bienvenido, {{ auth('web')->user()->nombres }}</h1>
    <section class="card">
        <h2>Simulacros</h2>
        @forelse ($simulacros as $simulacro)
            <article class="row" wire:key="simulacro-{{ $simulacro['id'] }}">
                <div><strong>{{ $simulacro['nombre'] }}</strong><p>{{ $simulacro['total_preguntas'] }} preguntas · {{ $simulacro['tiempo_limite'] }} minutos</p></div>
                <span>{{ $simulacro['activo'] ? 'Activo' : 'Inactivo' }}</span>
            </article>
        @empty
            <p>Todavía no hay simulacros disponibles.</p>
        @endforelse
    </section>
    <section class="card">
        <h2>Mis intentos y resultados</h2>
        @forelse ($intentos as $intento)
            <article class="row" wire:key="intento-{{ $intento->id }}">
                <div><strong>Intento #{{ $intento->id }}</strong><p>{{ $intento->fecha_inicio }} · {{ $intento->estado }}</p></div>
                <span>{{ $intento->puntaje_total === null ? 'Sin calificar' : 'Puntaje: '.$intento->puntaje_total }}</span>
            </article>
        @empty
            <p>Aún no tienes intentos registrados.</p>
        @endforelse
    </section>
</div>
