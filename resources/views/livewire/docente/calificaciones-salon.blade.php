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


<!-- INDICADORES ACADÉMICOS -->
<section class="calificaciones-resumen">

    <article class="calificaciones-indicador">
        <span>👥 Estudiantes</span>
        <h2>—</h2>
        <p>Alumnos del salón</p>
    </article>

    <article class="calificaciones-indicador">
        <span>📝 Simulacros</span>
        <h2>—</h2>
        <p>Evaluaciones registradas</p>
    </article>

    <article class="calificaciones-indicador">
        <span>📊 Promedio general</span>
        <h2>—</h2>
        <p>Puntaje promedio del salón</p>
    </article>

    <article class="calificaciones-indicador">
        <span>📈 Rendimiento</span>
        <h2>—</h2>
        <p>Seguimiento académico</p>
    </article>

<!-- RESULTADOS DE LOS ESTUDIANTES -->
<section class="calificaciones-resultados">

    <div class="resultados-cabecera">
        <span class="resultados-etiqueta">
            SEGUIMIENTO ACADÉMICO
        </span>

        <h2>Resultados de los estudiantes</h2>
        <p>Consulta las calificaciones obtenidas en los simulacros.</p>
    </div>

    <!-- FILTROS -->
    <div class="resultados-filtros">
        <input type="search"
               placeholder="Buscar estudiante..."
               disabled>

        <select disabled>
            <option>Seleccionar sección</option>
        </select>

        <select disabled>
            <option>Seleccionar simulacro</option>
        </select>
    </div>

    <!-- TABLA DE CALIFICACIONES -->
    <div class="resultados-tabla-contenedor">
        <table class="resultados-tabla">
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Simulacro</th>
                    <th>Puntaje</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td colspan="4" class="resultados-vacio">
                        📊 Aún no se muestran resultados.
                        Las calificaciones aparecerán
                        cuando conectemos esta tabla al backend.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</section>





</div>