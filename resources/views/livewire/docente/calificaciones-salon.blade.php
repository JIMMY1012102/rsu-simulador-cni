<div class="docente-calificaciones">
    <link rel="stylesheet" href="{{ asset('css/docente.css') }}">

    <!-- ENCABEZADO -->
    <header class="calificaciones-encabezado">

        <div class="calificaciones-encabezado-texto">

          <a href="{{ request()->is('docente/calificaciones-preview')
    ? url('/docente-preview')
    : route('docente.panel') }}"
   class="calificaciones-volver">
    ← Volver al Panel Docente
</a>
          
            <h1>Calificaciones de mi salón</h1>

            <p>
                Consulta los resultados de los simulacros
                y el progreso académico de tus estudiantes.
            </p>

        </div>

        <img
            src="{{ asset('img/escudo-cni.png') }}"
            alt="Escudo del CNI"
            class="calificaciones-escudo"
        >

    </header>

<!-- RESUMEN ACADÉMICO -->
<section class="calificaciones-resumen">

    <article class="calificaciones-indicador">
        <span>👥 Estudiantes</span>
        <h2>—</h2>
        <p>Alumnos de mi salón</p>
    </article>

    <article class="calificaciones-indicador">
        <span>📝 Simulacros</span>
        <h2>—</h2>
        <p>Evaluaciones realizadas</p>
    </article>

    <article class="calificaciones-indicador">
        <span>📊 Promedio general</span>
        <h2>—</h2>
        <p>Rendimiento del salón</p>
    </article>

</section>


</div>