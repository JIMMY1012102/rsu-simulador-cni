
<div class="docente-inicio">
   <link rel="stylesheet" href="/css/docente.css?v=2">
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- BIENVENIDA -->
    <!-- BIENVENIDA -->

<!-- BIENVENIDA INSTITUCIONAL -->
<section class="docente-bienvenida">

    <!-- ESCUDO DEL COLEGIO -->
    <img
        src="{{ asset('img/escudo-cni.png') }}"
        alt="Escudo del CNI"
        class="docente-escudo"
    >

    <!-- MENSAJE DE BIENVENIDA -->
    <div class="docente-bienvenida-contenido">
        <span class="docente-etiqueta">
            COLEGIO NACIONAL DE IMPERIAL
        </span>

        <h1>¡Bienvenido, docente!</h1>

        <p>
            Un espacio para acompañar el progreso
            académico de tus estudiantes.
        </p>
    </div>

    <!-- FOTOGRAFÍA DEL PERSONAL -->
    <div class="docente-bienvenida-foto">
        <img
            src="{{ asset('img/foto-personal-cni.png') }}"
            alt="Personal del Colegio Nacional de Imperial"
        >
    </div>

</section>
    <!-- MENÚ PRINCIPAL -->
    <section class="docente-intro">
        <h2>¿Qué deseas hacer hoy?</h2>
        <p>Accede rápidamente a las herramientas del portal docente.</p>
    </section>

    <section class="docente-tarjetas">

        <article class="docente-tarjeta docente-destacada">
            <h3>Calificaciones de mi salón</h3>
            <p>
                Consulta las notas de tus alumnos,
                observa su evolución e identifica
                quiénes necesitan refuerzo.
            </p>
          <a href="{{ request()->is('docente-preview')
    ? url('/docente/calificaciones-preview')
    : route('docente.calificaciones') }}"
   class="docente-enlace">
    Ver calificaciones →
</a>
        
        
        
        </article>

        <article class="docente-tarjeta">
            <h3>Simulacros</h3>
            @if(isset($simulacros))
    @forelse($simulacros as $simulacro)
        <p>📘 {{ $simulacro['nombre'] }}</p>
    @empty
        <p>No hay simulacros registrados.</p>
    @endforelse
@else
    <p>Los simulacros aparecerán en el panel docente real.</p>
@endif
            <p>
                Revisa los simulacros disponibles
                para los estudiantes.
            </p>
            <span class="docente-enlace">
                Ir a simulacros →
            </span>
        </article>

        <article class="docente-tarjeta">
            
            <h3>Banco de preguntas</h3>
            <p>
                Explora las preguntas organizadas
                por áreas académicas.
            </p>
            <span class="docente-enlace">
                Ver preguntas →
            </span>
        </article>

    </section>

    <!-- OPCIONES ADICIONALES -->
    <section class="docente-secundarias">

        <article class="docente-tarjeta">
            <h3>Guía y ayuda</h3>
            <p>Instrucciones del portal docente.</p>
        </article>

        
        <article class="docente-tarjeta">
            <h3> Información</h3>
            <p>
                Los resultados se mostrarán
                al conectar los servicios del sistema.
            </p>
        </article>

    </section>

</div>


