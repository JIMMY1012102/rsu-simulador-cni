# Inventario del esquema de RSU-CNI

> **Fuente:** `develop` (`eb82cd46c35567c4b7d840a524a339e28c0f18ca`) · **Fecha:** 26/10/2026. **Estado:** esquema esperado en código; MariaDB compartida no consultada en vivo.

## Tablas identificadas

| Dominio | Tabla | Columnas | Función |
|---|---|---:|---|
| Identidad y usuarios | `secciones` | 6 | Salones (grado y sección) de docentes y estudiantes. |
| Identidad y usuarios | `usuarios` | 13 | Identidades, hash de contraseña, rol y adscripción opcional. |
| Catálogo académico | `areas_postulacion` | 6 | Cinco áreas de postulación A–E, distintas de las asignaturas. |
| Catálogo académico | `carreras` | 6 | Carreras pertenecientes a un área A–E. |
| Catálogo académico | `areas` | 6 | Asignaturas o áreas de contenido del examen, agrupadas por bloque. |
| Catálogo académico | `temas` | 6 | Temas de una asignatura. |
| Banco de preguntas | `preguntas` | 10 | Enunciados, niveles, imágenes opcionales, soluciones y autor. |
| Banco de preguntas | `alternativas` | 7 | Opciones de respuesta de una pregunta, con marca de correcta. |
| Configuración de evaluaciones | `simulacros` | 16 | Exámenes configurados: tiempo, puntajes, disponibilidad y autor. |
| Configuración de evaluaciones | `area_postulacion_simulacro` | 5 | Asociación N:M de simulacros con áreas de postulación. |
| Configuración de evaluaciones | `simulacro_area` | 7 | Cuotas de preguntas de un simulacro por asignatura y nivel. |
| Rendición e historial | `intentos` | 19 | Historial de ejecuciones de un usuario, tiempos, estados y totales. |
| Rendición e historial | `respuestas_estudiante` | 10 | Preguntas sorteadas para un intento y respuestas individuales. |
| Rendición e historial | `resultados_area` | 11 | Acumulados de puntaje y aciertos por asignatura en un intento. |
| Infraestructura histórica | `personal_access_tokens` | 10 | Tabla técnica heredada de autenticación con tokens polimórficos; no se usa en sesión web actual. |

**Resumen:** 15 tablas declaradas en 17 archivos de migración: 14 de negocio y una técnica histórica. La tabla técnica `migrations` puede existir cuando Laravel inicializa la base, pero no se declara mediante `Schema::create` en estos archivos. Las tablas `sessions`, `cache` y `password_reset_tokens` aparecen en opciones del framework, **sin migraciones propias de creación**.

## Fuentes e interpretación

- Migraciones: [database/migrations](https://github.com/JIMMY1012102/rsu-simulador-cni/tree/develop/database/migrations), fuente de estructura objetivo.
- Modelos Eloquent: `Usuario`, `Seccion`, `AreaPostulacion`, `Carrera`, `Simulacro`, `SimulacroArea`. Las tablas sin modelo se consultan con `DB::table` y servicios.
- [base-de-datos.sql](../../base-de-datos.sql): versión inicial de 14 tablas con semillas. **No reimportar** sobre MariaDB con registros.
- Diferencias del SQL inicial: migración `personal_access_tokens` (16/10), `usuarios.area_postulacion_id` y `simulacros.carrera_id` (22/10). El SQL viejo no las contiene.
- [DatabaseSeeder](../../database/seeders/DatabaseSeeder.php) registra cuentas demo desde `UsuarioSeeder`: no ejecutarlo en producción. La carpeta de factories no contiene factories propias.
- `areas_postulacion` = áreas A–E para carreras/participantes; `areas` = asignaturas/contenidos. No fusionarlas ni renombrarlas.

## Reglas de nomenclatura

Los nombres reales se conservan en español snake_case por compatibilidad con Laravel. Las agrupaciones son **lógicas para diagramas**, no nuevos esquemas ni movimientos físicos. El rol `admin` representa al superadministrador en la interfaz; no existe tabla adicional de roles ni Spatie Permission.
