# Flujo personalizado del alumno: primera selección de área → panel → examen → resultados

**Estado de entrega:** integrado al código, apagado por defecto mediante `CNI_ALUMNO_AREAS_ENABLED=false` hasta auditar y migrar la base del equipo.

**Cambio solicitado:** seleccionar **una de cinco áreas** solo la primera vez que se ingresa. Mostrar cinco tarjetas verticales en escritorio y adaptarlas a tablet/móvil. **No exigir carrera para comenzar el simulador.**

## Flujo implementado
1. `POST /login` verifica el rol. Para estudiante sin `usuarios.area_postulacion_id` ni `carrera_id`, redirige a `GET /alumno/elegir-area`. Docentes y administradores conservan el acceso propio.
2. La pantalla de bienvenida presenta cinco tarjetas altas (A–E); `POST /alumno/elegir-area` valida que el área esté activa y la guarda en `usuarios.area_postulacion_id` (nueva migración), **no solo en sesión**.
3. El alumno es redirigido inmediatamente a `GET /alumno/panel`, con nombre y área seleccionada, un botón visible «Dar examen simulador» y una lista de exámenes disponibles.
4. El botón lleva a los exámenes del área. «Iniciar examen» crea o reanuda un intento con la sesión web, y abre la pantalla Livewire del simulador; terminarlo abre la vista de resultados.
5. En sesiones futuras vuelve directamente al mismo panel. La elección no puede cambiarse desde el formulario de primer ingreso.
6. La carrera profesional puede guardarse **opcionalmente** en el panel; debe pertenecer a la misma área y no bloquea rendir simulacros. Las cuentas antiguas con solo `carrera_id` siguen pudiendo obtener su área de esa relación.

## Datos y compatibilidad
- Migración nueva: `database/migrations/2026_10_22_000001_add_area_postulacion_id_to_usuarios_table.php`, nullable, FK a `areas_postulacion`.
- Los simulacros visibles deben estar vinculados explícitamente al área del alumno mediante `area_postulacion_simulacro`.
- Cada examen puede tener distintas cuotas por asignatura en `simulacro_area`, con banco suficiente. Si no hay exámenes publicados, el panel informa de ello y no inventa preguntas.
- `IntentoService` usa `area_postulacion_id` o, por compatibilidad histórica, el área obtenida de `carrera_id`. Rechaza exámenes asociados a un área distinta.
- La selección actual personaliza **por área de postulación**, no por carrera específica dentro del área.
- No se modificó el servidor ni se ejecutaron migraciones en ninguna base de datos externa.

## Catálogo A–E
A — Ciencias de la Salud; B — Ciencias Básicas; C — Ingenierías; D — Ciencias Económicas y de la Gestión; E — Humanidades y Ciencias Jurídicas y Sociales.

El catálogo previo de seeders usaba cuatro áreas y códigos incompatibles. **No aplicar seeders ni migraciones destructivas a una base poblada sin respaldo y plan de remapeo**. La nueva migración solo añade un campo; el seeder de áreas detiene el proceso ante conflicto.

## Colaboración y validación
- PR #18 apunta a `develop` y se mantiene en borrador. `main` y `develop` no se alteran automáticamente.
- PR #13 (perfil visual del alumno) continúa independiente; su diseño podrá incorporarse sin sustituir el panel de preparación basado en área.
- CI ejecuta `php artisan route:list`, `php artisan view:cache` y `php artisan test` con SQLite en memoria.
- Antes del merge: comprobar migración y recorrido en MariaDB de desarrollo, datos reales, asociación de cinco exámenes y revisión visual responsive.
