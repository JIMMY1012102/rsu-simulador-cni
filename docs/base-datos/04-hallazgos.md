# Auditoría y recomendaciones (KISS / DRY)

> **Fuente:** `develop` (`eb82cd46c35567c4b7d840a524a339e28c0f18ca`) · **Fecha:** 26/10/2026. **Estado:** esquema esperado en código; MariaDB compartida no consultada en vivo.

## Normalización

- **1FN:** las columnas son atómicas y opciones, temas y calificaciones se separan en tablas. No hay listas de opciones dentro de una columna.
- **2FN:** asociaciones N:M se expresan en `area_postulacion_simulacro` y `simulacro_area` mediante filas, con UNIQUE compuestos que aseguran su grano lógico.
- **3FN:** nombres de carrera, tema y asignatura están en catálogos separados. **No se certifica formalmente 3FN de los datos reales**: agregados en `intentos`/`resultados_area` son redundancia intencional para historial/rendimiento; vínculos duplicables `area_postulacion_id` y `carrera_id` exigen consistencia.

## Hallazgos priorizados (NINGUNA propuesta aplicada)

| Prioridad | Observación | Propuesta documental / posterior |
|---|---|---|
| Alta | `base-de-datos.sql` define 14 tablas; migraciones definen 15 más dos columnas añadidas. | Usar migraciones como referencia objetivo; auditar esquema histórico en MariaDB antes de sincronizar. Nunca reimportar SQL sobre tablas pobladas. |
| Alta | Se ha utilizado catálogo de cuatro áreas anteriormente; el actual requiere A–E con nombres específicos. | Contar/contrastar códigos y relaciones; diseñar remapeo respaldado y aprobado si difieren. No ejecutar seeder correctivo automáticamente. |
| Alta | Las funciones nuevas del alumno y administrador están apagadas por defecto en `.env.example`. | Antes de habilitar, confirmar migraciones y catálogos en copia y hacer recorrido integral en navegador. |
| Media | Redundancia entre área del usuario y área inferible desde carrera. | Validar concordancia de filas; no eliminar `area_postulacion_id` porque el alumno puede elegir área sin carrera. |
| Media | `intentos` y `resultados_area` materializan puntajes/contadores. | Mantener datos históricos; realizar pruebas de reconciliación y no recalcular destructivamente. |
| Media | Alternativa e ID de pregunta tienen FK independientes. | Mantener validación en Laravel; evaluar restricción compuesta solo tras analizar datos reales e impacto de cambios. |
| Media | `personal_access_tokens` permanece aunque ya no se usa Sanctum en el flujo web. | Documentar como heredada; no borrarla sin revisar uso y migraciones antiguas. |
| Media | `ON DELETE SET NULL` en relaciones de histórico (por ej. carrera de intento). | Priorizar baja lógica y conservación de contexto; discutir política de borrado antes de cambios. |
| Media | No hay migración propia `password_reset_tokens`, pero `config/auth.php` la menciona. | Es configuración genérica, no entidad existente acreditada; no crear tabla sin requisito. |
| Baja | Posibles índices implícitos InnoDB duplicados con índices declarados. | Comparar `information_schema.STATISTICS` y planes `EXPLAIN`; no borrar a ciegas. |

## Seguridad y backend

`usuarios.rol` es ENUM `admin/docente/estudiante`. No existe tabla separada `superadmin` ni Spatie Laravel Permission en `composer.json`. El guard `web` usa sesiones (la plantilla de entorno selecciona almacenamiento file); `usuarios.password` se maneja con hash por cast Eloquent. Autorización por roles, cuenta activa, propiedad de intentos y aislamiento de docentes por `seccion_id` se aplican en middleware/servicios, **no por política de filas SQL**.

**Riesgo de ruptura si se renombra una tabla por estética:** los controladores, servicios, Blade, pruebas y seeders llaman directamente a `areas`, `temas`, `simulacro_area`, `intentos`, `respuestas_estudiante`, etc. La organización sugerida es visual/lógica, sin renombrar objetos físicos.

## Procedimiento obligatorio para propuestas estructurales futuras

1. Presentar DDL propuesto, problema reproducible y justificación.
2. Analizar modelos, `DB::table`, relaciones, factories/seeders, consultas e índices, y ruta de reversión en **copia**.
3. Respaldar y auditar datos reales, verificar migraciones y pruebas de MariaDB/SQLite.
4. Aprobar en el equipo; solo después implementar migración no destructiva en rama separada.

Esta revisión **no ejecutó sentencias de escritura ni consultó MariaDB en vivo**.
