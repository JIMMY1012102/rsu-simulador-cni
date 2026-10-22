# Flujo del alumno: área → carrera → panel → examen → resultados

**Rama de implementación:** `feature/onboarding-areas-carreras-20261022` (PR a `develop`).
No altera las rutas del docente, el guard `web`, los datos del hosting ni la rama `main`.

## Flujo

1. Inicio de sesión de un estudiante sin `usuarios.carrera_id`: `GET /alumno/elegir-area` (cinco tarjetas).
2. `POST /alumno/elegir-area` registra **solo en sesión** la selección validada; `GET /alumno/panel` muestra un panel único parametrizado por esa área.
3. `POST /alumno/carrera` permite seleccionar **únicamente carreras activas del área elegida**. Se persiste en `usuarios.carrera_id` y se borra la elección transitoria de la sesión.
4. Los siguientes inicios de sesión van directamente a `GET /alumno/panel`. Cambiar de carrera está bloqueado por esta versión: no mutar perfil e historial durante intentos.
5. El panel lista únicamente simulacros **asociados explícitamente** a la correspondiente área de postulación. Cada simulacro tiene su propia distribución `simulacro_area` (cantidades por asignatura y nivel). `IntentoService` valida la compatibilidad al iniciar y sortea preguntas conforme a esa distribución.
6. El alumno resuelve el examen Livewire, lo envía o vence su tiempo y accede a `GET /alumno/resultados/{intento}`, que solo entrega resultados del propietario.

El panel es una **sola plantilla** para cinco áreas, no cinco sistemas duplicados.
La configuración actual es por **área de postulación**; dos carreras de la misma área comparten el perfil del simulacro. La variación por carrera individual requiere ampliar el modelo de asociación: NO está implementada.

## Catálogo A–E

Esta implementación sigue **base-de-datos.md, sección 4.1**:
- A Ciencias de la Salud
- B Ciencias Básicas
- C Ingenierías
- D Ciencias Económicas y de la Gestión
- E Humanidades y Ciencias Jurídicas y Sociales

El antiguo `AreaPostulacionSeeder` tenía solo cuatro áreas y códigos distintos; el nuevo seeder **detiene la operación ante conflictos**, en lugar de reasignar códigos/relaciones históricas.
En una base ya poblada se debe acordar una migración controlada de códigos, `carreras`, relaciones de simulacros y usuarios (con respaldo previo). No ejecutar `migrate:fresh` ni alterar el SQL en producción.

`CarreraSeeder` añade solo un catálogo inicial de demostración por área. Ajustar las carreras reales con el equipo.

## Configurar exámenes por área

El administrador/docente puede crear cinco (o más) simulacros distintos:
- Vincular cada uno a su `area_postulacion` en `area_postulacion_simulacro`.
- Configurar en `simulacro_area` las cuotas por asignatura/nivel (ej. más matemáticas en Ingeniería, más letras en Humanidades). La suma de cuotas debe coincidir con el total configurado.
- Mantener un banco suficiente con alternativas válidas, publicar y validar fechas.
- **No dejar vacía la asociación** si el examen debe ser específico por área. Los simulacros antiguos sin asociación mantienen compatibilidad JSON global en el servicio existente, pero el panel nuevo no los muestra.

La selección de las preguntas y los límites de tiempo se validan en el servidor; no se confía en una distribución enviada por el navegador. No se inventan pesos oficiales ni exámenes de carreras concretas.

## Integración con trabajo paralelo del equipo

El panel de perfil visual de la rama `feature/panel-alumno` (PR #13) permanece intacto; la plantilla `alumno.panel` sirve como panel de preparación del área. Al fusionar #13, adaptar su botón de iniciar simulador para enlazar los simulacros que ofrece este nuevo panel. La pantalla de examen existente de `feature/examen-alumno` se incorpora reutilizando sus recursos y componentes, con cambios de rutas hacia el resultado detallado.

## Pruebas sugeridas

```bash
php artisan view:cache
php artisan test --filter=OrientacionAlumnoTest
php artisan test --filter=ExamenPantallaTest
php artisan test
```

Las pruebas unitarias/HTTP corren con SQLite en memoria. Falta comprobar el flujo en navegador con MariaDB de desarrollo, además de una eventual migración de datos existentes. Los datos demo nunca se instalan automáticamente en producción.
