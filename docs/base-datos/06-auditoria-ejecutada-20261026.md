# Evidencia de auditoría ejecutada · 26/10/2026

> **Aclaración confirmada por el equipo:** el sistema **solo utiliza cuentas y exámenes DEMO**; no hay alumnos reales ni una MariaDB compartida. Las pruebas CI corresponden al entorno temporal. La validación de un futuro servidor central **no es un pendiente de este momento**.

> **Fuentes verificadas:** repositorio `JIMMY1012102/rsu-simulador-cni`, rama `develop` (`eb82cd46c35567c4b7d840a524a339e28c0f18ca`), rama documental PR #22 (`f4f6aaff1ba2020ac770b2694f619f6b67d043ad`) y registros oficiales del workflow [40334772227](https://github.com/JIMMY1012102/rsu-simulador-cni/actions/runs/40334772227).

## Alcance efectivamente comprobado

| Control | Resultado comprobado | Fuente | Certifica instancia compartida |
|---|---|---|---|
| Versionado de esquema | 17 migraciones: 14 `CREATE TABLE` de negocio, 1 tabla `personal_access_tokens` y 2 ampliaciones de columnas | `database/migrations/` | **No existe base compartida** |
| Modelo esperado | 15 tablas con `Schema::create`, 138 columnas esperadas, 24 FKs previstas | Migraciones e inventario en este PR | **No** |
| SQL de referencia antiguo | `base-de-datos.sql` contiene 14 `CREATE TABLE`, no incluye tabla histórica de tokens ni adiciones del 22/10 | `base-de-datos.sql` | **No** |
| MariaDB 11.4 de CI | Instalación temporal desde migraciones y 114 pruebas aprobadas, 835 aserciones | Job `MariaDB - integración de Laravel`, ejecución `40334772227` | **No** |
| SQLite de CI | 114 pruebas aprobadas, 835 aserciones | Job `SQLite - pruebas de Laravel`, ejecución `40334772227` | **No** |
| Catálogo A–E, selección de área, flujo de examen y autorización | Pruebas de integración automatizadas superadas en la base efímera | `AuditoriaCatalogoTest`, `CincoAreasMariaDBTest`, `OrientacionAlumnoTest`, `RecorridoSimulacroTest` | **No** |
| Comando seguro `cni:auditar-areas` | Código verificado: únicamente consulta catálogo, verifica presencia de `usuarios.area_postulacion_id` y cuenta usuarios; informa errores sin escritura | `app/Console/Commands/AuditarAreasCni.php` | No existe base compartida |
| Acceso y permisos | `auth:web` usa `Usuario` y sesión, `.env.example` usa `SESSION_DRIVER=file`; roles `admin/docente/estudiante` | `config/auth.php`, `app/Models/Usuario.php`, `.env.example` | Configuración real **desconocida** |
| Backup / export del equipo | No apareció dump, snapshot SQL de datos instalados o salida de `information_schema` en árbol `develop`; el run de CI no adjuntó artefactos | Árbol GitHub y artefactos del run | **Ausente** |
| Contraste de datos existentes | No se recibieron metadatos de la instancia compartida ni se abrió una conexión SQL | Alcance de GitHub | **Pendiente** |

> **Precisión:** CI utilizó `php artisan migrate:fresh` **únicamente en un contenedor de MariaDB temporal descartado al concluir el job**. El comando nunca se ejecutó sobre la MariaDB del equipo desde esta auditoría. No replicarlo manualmente.

## Hallazgos preventivos y riesgo para el backend

Los tres casos de incoherencia de carrera↔área, alternativa↔pregunta y suma de cuotas **ya tienen validaciones en los servicios Laravel**; no se detectaron filas de estudiantes reales corruptas. El [PR #23](https://github.com/JIMMY1012102/rsu-simulador-cni/pull/23) fortalece las validaciones de simulacros y corrige fixtures con códigos antiguos.

1. **Prioridad de prevención — versiones locales podrían divergir.** Si el equipo instaló el SQL antiguo, pueden faltar `personal_access_tokens`, `usuarios.area_postulacion_id` y `simulacros.carrera_id`. La ausencia de la tabla de tokens no afecta el login web actual, pero faltas de las dos columnas sí afectan las funcionalidades nuevas.
2. **Catálogo A–E: confirmado para la DEMO; prevención de legado.** El seeder exige A=Salud, B=Ciencias Básicas, C=Ingenierías, D=Económicas/Gestión, E=Humanidades. Un catálogo legado con significados diferentes puede provocar selección incorrecta o impedir el despliegue.
3. **Prioridad MEDIA — integridad lógica.** La FK `respuestas_estudiante.alternativa_id` no garantiza por sí sola que la alternativa corresponda a `pregunta_id`. El backend lo valida; hay que comprobar datos históricos con agregados de lectura.
4. **Prioridad MEDIA — adscripción doble.** `usuarios.area_postulacion_id` y `usuarios.carrera_id` admiten estados distintos por flujo, pero la carrera seleccionada nunca debería pertenecer a otra área A–E.
5. **Prioridad MEDIA — consultas e índices reales.** GitHub documenta índices declarados, pero no puede conocer índices implícitos de InnoDB ni cambios manuales en el servidor.
6. **Prioridad MEDIA — disparidad entre `.env.example` y despliegue.** La plantilla guarda sesiones y caché en archivos. La instancia compartida puede tener otra configuración; no se puede inferir si necesita tablas técnicas `sessions` o `cache` sin examinar configuración efectiva de forma segura.

## Protocolo opcional cuando exista un entorno local que se quiera contrastar

**Si posteriormente se desea auditar una instalación local**, identificar en la terminal del proyecto la conexión a su base **DEMO** (no a una base compartida del equipo), **sin compartir credenciales ni archivos `.env`**. Verificar que NO sea producción. Ejecutar exclusivamente:

```bash
php artisan migrate:status
php artisan cni:auditar-areas
```

Ambos comandos son diagnósticos; el segundo se examinó directamente y no escribe registros. Si faltan tablas o la conexión está mal, detenerse, sin lanzar `migrate` o `db:seed`.

**Después**, con usuario MariaDB de **solo lectura**, ejecutar las consultas SQL de [05-auditoria-mariadb.md](05-auditoria-mariadb.md), especialmente `information_schema.TABLES`, `information_schema.COLUMNS`, `KEY_COLUMN_USAGE`, `STATISTICS` y `areas_postulacion`. Compartir solamente metadatos del esquema y resultados agregados; jamás `password`, tokens, secretos ni información personal.

## Dictamen actual

**Arquitectura reproducible en MariaDB 11.4: APROBADA POR PRUEBAS CI.**

**Compatibilidad con MariaDB compartida del equipo: NO APLICA (no existe).**

**Apto para ejecutar modificaciones de esquema, borrar o renombrar tablas, mover datos o habilitar funcionalidades en la instancia compartida: NO AUTORIZADO con esta evidencia.**

No se modificó ningún archivo de `app/`, `database/`, `routes/` ni ningún servidor. Solo se agrega este informe documental al PR #22.
