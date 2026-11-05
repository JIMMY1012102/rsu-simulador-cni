# Diccionario de datos completo

> **Fuente:** `develop` (`eb82cd46c35567c4b7d840a524a339e28c0f18ca`) · **Fecha:** 26/10/2026. **Estado:** esquema esperado en código; MariaDB compartida no consultada en vivo.

Derivado de las declaraciones de las 17 migraciones. Tipos `bigint` y `smallint` corresponden a **UNSIGNED** en ID/FK y conteos; `timestamp` de timestamps Laravel es nullable. `ENUM(...)` señala valores admitidos. `NN` no nulo; `NULL` opcional. Las columnas `id` son PK autoincrementales; todas las tablas contienen `created_at` y `updated_at` nullable.

## secciones

Salones (grado y sección) de docentes y estudiantes.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `nombre` | `VARCHAR(10)` | No | — | — |
| `grado` | `VARCHAR(20)` | No | — | `5to` |
| `activo` | `BOOLEAN` | No | — | `true` |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(nombre, grado).

**Migración de origen:** [`database/migrations/2026_10_14_000001_create_secciones_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000001_create_secciones_table.php).

## areas_postulacion

Cinco áreas de postulación A–E, distintas de las asignaturas.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `codigo` | `CHAR(1)` | No | UQ | — |
| `nombre` | `VARCHAR(100)` | No | UQ | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** sin adicionales explícitas.

**Migración de origen:** [`database/migrations/2026_10_14_000002_create_areas_postulacion_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000002_create_areas_postulacion_table.php).

## carreras

Carreras pertenecientes a un área A–E.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `area_postulacion_id` | `BIGINT UNSIGNED` | No | FK → areas_postulacion.id | — |
| `nombre` | `VARCHAR(150)` | No | UQ | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** sin adicionales explícitas.

**Migración de origen:** [`database/migrations/2026_10_14_000003_create_carreras_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000003_create_carreras_table.php).

## usuarios

Identidades, hash de contraseña, rol y adscripción opcional.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `nombres` | `VARCHAR(100)` | No | — | — |
| `apellidos` | `VARCHAR(100)` | No | — | — |
| `usuario` | `VARCHAR(50)` | No | UQ | — |
| `password` | `VARCHAR(255)` | No | — | — |
| `rol` | `ENUM(admin, docente, estudiante)` | No | — | — |
| `seccion_id` | `BIGINT UNSIGNED` | Sí | FK → secciones.id | — |
| `carrera_id` | `BIGINT UNSIGNED` | Sí | FK → carreras.id | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `remember_token` | `VARCHAR(100)` | Sí | — | — |
| `area_postulacion_id` | `BIGINT UNSIGNED` | Sí | FK → areas_postulacion.id | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** INDEX(rol, activo).

**Migración de origen:** [`database/migrations/2026_10_14_000004_create_usuarios_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000004_create_usuarios_table.php) y `2026_10_22_000001_add_area_postulacion_id_to_usuarios_table.php`.

## areas

Asignaturas o áreas de contenido del examen, agrupadas por bloque.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `nombre` | `VARCHAR(100)` | No | UQ | — |
| `bloque` | `ENUM(habilidad_verbal, habilidad_logico_matematica, conocimientos)` | No | — | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** sin adicionales explícitas.

**Migración de origen:** [`database/migrations/2026_10_14_000005_create_areas_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000005_create_areas_table.php).

## temas

Temas de una asignatura.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `area_id` | `BIGINT UNSIGNED` | No | FK → areas.id | — |
| `nombre` | `VARCHAR(150)` | No | — | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(area_id, nombre).

**Migración de origen:** [`database/migrations/2026_10_14_000006_create_temas_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000006_create_temas_table.php).

## preguntas

Enunciados, niveles, imágenes opcionales, soluciones y autor.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `tema_id` | `BIGINT UNSIGNED` | No | FK → temas.id | — |
| `enunciado` | `TEXT` | No | — | — |
| `imagen` | `VARCHAR(255)` | Sí | — | — |
| `nivel` | `ENUM(basico, intermedio, avanzado)` | No | — | — |
| `solucion` | `TEXT` | No | — | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `creado_por` | `BIGINT UNSIGNED` | Sí | FK → usuarios.id | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** INDEX(tema_id, nivel, activo).

**Migración de origen:** [`database/migrations/2026_10_14_000007_create_preguntas_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000007_create_preguntas_table.php).

## alternativas

Opciones de respuesta de una pregunta, con marca de correcta.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `pregunta_id` | `BIGINT UNSIGNED` | No | FK → preguntas.id | — |
| `letra` | `CHAR(1)` | No | — | — |
| `texto` | `VARCHAR(500)` | No | — | — |
| `es_correcta` | `BOOLEAN` | No | — | `false` |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(pregunta_id, letra). **CHECK MariaDB:** chk_alternativas_letra: letra BETWEEN 'A' AND 'Z'. La migración MariaDB aplica charset ascii y collation ascii_bin a letra.

**Migración de origen:** [`database/migrations/2026_10_14_000008_create_alternativas_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000008_create_alternativas_table.php).

## simulacros

Exámenes configurados: tiempo, puntajes, disponibilidad y autor.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `nombre` | `VARCHAR(150)` | No | — | — |
| `tiempo_limite` | `SMALLINT UNSIGNED` | No | — | — |
| `total_preguntas` | `SMALLINT UNSIGNED` | No | — | `100` |
| `cantidad_alternativas` | `SMALLINT UNSIGNED` | No | — | `4` |
| `puntaje_correcta` | `DECIMAL(7,3)` | No | — | `20.000` |
| `puntaje_incorrecta` | `DECIMAL(7,3)` | No | — | `-1.125` |
| `puntaje_blanco` | `DECIMAL(7,3)` | No | — | `0.000` |
| `puntaje_maximo` | `DECIMAL(12,3)` | No | — | Generada STORED: total_preguntas × puntaje_correcta |
| `fecha_inicio` | `DATETIME` | No | — | — |
| `fecha_fin` | `DATETIME` | No | — | — |
| `activo` | `BOOLEAN` | No | — | `true` |
| `creado_por` | `BIGINT UNSIGNED` | Sí | FK → usuarios.id | — |
| `carrera_id` | `BIGINT UNSIGNED` | Sí | FK → carreras.id | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** INDEX(creado_por). **CHECK MariaDB:** chk_simulacros_tiempo (>0); chk_simulacros_total (>0); chk_simulacros_alternativas (2..26); chk_simulacros_fechas (fin > inicio); chk_simulacros_p_correcta (>0); chk_simulacros_p_incorrecta (<=0).

**Migración de origen:** [`database/migrations/2026_10_14_000009_create_simulacros_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000009_create_simulacros_table.php) y `2026_10_22_000002_add_carrera_id_to_simulacros_table.php`.

## area_postulacion_simulacro

Asociación N:M de simulacros con áreas de postulación.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `area_postulacion_id` | `BIGINT UNSIGNED` | No | FK → areas_postulacion.id | — |
| `simulacro_id` | `BIGINT UNSIGNED` | No | FK → simulacros.id | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(area_postulacion_id, simulacro_id); INDEX(simulacro_id).

**Migración de origen:** [`database/migrations/2026_10_14_000010_create_area_postulacion_simulacro_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000010_create_area_postulacion_simulacro_table.php).

## simulacro_area

Cuotas de preguntas de un simulacro por asignatura y nivel.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `simulacro_id` | `BIGINT UNSIGNED` | No | FK → simulacros.id | — |
| `area_id` | `BIGINT UNSIGNED` | No | FK → areas.id | — |
| `nivel` | `ENUM(basico, intermedio, avanzado, mixto)` | No | — | `mixto` |
| `cantidad_preguntas` | `SMALLINT UNSIGNED` | No | — | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(simulacro_id, area_id, nivel); INDEX(area_id). **CHECK MariaDB:** chk_simulacro_area_cantidad: cantidad_preguntas > 0.

**Migración de origen:** [`database/migrations/2026_10_14_000011_create_simulacro_area_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000011_create_simulacro_area_table.php).

## intentos

Historial de ejecuciones de un usuario, tiempos, estados y totales.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `usuario_id` | `BIGINT UNSIGNED` | No | FK → usuarios.id | — |
| `carrera_id` | `BIGINT UNSIGNED` | Sí | FK → carreras.id | — |
| `simulacro_id` | `BIGINT UNSIGNED` | Sí | FK → simulacros.id | — |
| `tipo` | `ENUM(simulacro, practica)` | No | — | — |
| `area_id` | `BIGINT UNSIGNED` | Sí | FK → areas.id | — |
| `tema_id` | `BIGINT UNSIGNED` | Sí | FK → temas.id | — |
| `fecha_inicio` | `DATETIME` | No | — | — |
| `fecha_limite` | `DATETIME` | Sí | — | — |
| `fecha_fin` | `DATETIME` | Sí | — | — |
| `estado` | `ENUM(en_curso, finalizado, enviado_auto)` | No | — | `en_curso` |
| `total_preguntas` | `SMALLINT UNSIGNED` | No | — | — |
| `puntaje_total` | `DECIMAL(8,3)` | Sí | — | — |
| `correctas` | `SMALLINT UNSIGNED` | Sí | — | — |
| `incorrectas` | `SMALLINT UNSIGNED` | Sí | — | — |
| `en_blanco` | `SMALLINT UNSIGNED` | Sí | — | — |
| `doble_marca` | `SMALLINT UNSIGNED` | Sí | — | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** INDEX(usuario_id, fecha_inicio); INDEX(simulacro_id, estado); INDEX(estado, fecha_limite); INDEX(carrera_id); INDEX(area_id); INDEX(tema_id).

**Migración de origen:** [`database/migrations/2026_10_14_000012_create_intentos_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000012_create_intentos_table.php).

## respuestas_estudiante

Preguntas sorteadas para un intento y respuestas individuales.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `intento_id` | `BIGINT UNSIGNED` | No | FK → intentos.id | — |
| `pregunta_id` | `BIGINT UNSIGNED` | No | FK → preguntas.id | — |
| `orden` | `SMALLINT UNSIGNED` | No | — | — |
| `alternativa_id` | `BIGINT UNSIGNED` | Sí | FK → alternativas.id | — |
| `estado_respuesta` | `ENUM(correcta, incorrecta, en_blanco, doble_marca)` | Sí | — | — |
| `puntaje_obtenido` | `DECIMAL(7,3)` | Sí | — | — |
| `respondida_en` | `DATETIME` | Sí | — | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(intento_id, pregunta_id); UNIQUE(intento_id, orden); INDEX(pregunta_id); INDEX(alternativa_id).

**Migración de origen:** [`database/migrations/2026_10_14_000013_create_respuestas_estudiante_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000013_create_respuestas_estudiante_table.php).

## resultados_area

Acumulados de puntaje y aciertos por asignatura en un intento.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `intento_id` | `BIGINT UNSIGNED` | No | FK → intentos.id | — |
| `area_id` | `BIGINT UNSIGNED` | No | FK → areas.id | — |
| `total_preguntas` | `SMALLINT UNSIGNED` | No | — | — |
| `aciertos` | `SMALLINT UNSIGNED` | No | — | — |
| `incorrectas` | `SMALLINT UNSIGNED` | No | — | — |
| `en_blanco` | `SMALLINT UNSIGNED` | No | — | — |
| `puntaje` | `DECIMAL(8,3)` | No | — | — |
| `porcentaje` | `DECIMAL(5,2)` | No | — | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** UNIQUE(intento_id, area_id); INDEX(area_id). **CHECK MariaDB:** chk_resultados_porcentaje: porcentaje BETWEEN 0 AND 100.

**Migración de origen:** [`database/migrations/2026_10_14_000014_create_resultados_area_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_14_000014_create_resultados_area_table.php).

## personal_access_tokens

Tabla técnica heredada de autenticación con tokens polimórficos; no se usa en sesión web actual.

| Columna | Tipo migración | NULL | Claves | Default / comentario |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | — |
| `tokenable_type` | `VARCHAR(255)` | No | — | — |
| `tokenable_id` | `BIGINT UNSIGNED` | No | — | — |
| `name` | `TEXT` | No | — | — |
| `token` | `VARCHAR(64)` | No | UQ | — |
| `abilities` | `TEXT` | Sí | — | — |
| `last_used_at` | `TIMESTAMP` | Sí | — | — |
| `expires_at` | `TIMESTAMP` | Sí | — | — |
| `created_at` | `TIMESTAMP` | Sí | — | — |
| `updated_at` | `TIMESTAMP` | Sí | — | — |

**Restricciones compuestas:** INDEX(tokenable_type, tokenable_id); INDEX(expires_at).

**Migración de origen:** [`database/migrations/2026_10_16_211319_create_personal_access_tokens_table.php`](https://github.com/JIMMY1012102/rsu-simulador-cni/blob/develop/database/migrations/2026_10_16_211319_create_personal_access_tokens_table.php).

## Precisión de tipos, índices y alcance

`personal_access_tokens.tokenable_type` y `tokenable_id` son campos polimórficos con índice compuesto, **no una FK**. `remember_token` es `VARCHAR(100) NULL`. `simulacros.puntaje_maximo` es una columna almacenada generada, no debe enviarse desde Eloquent. Los índices FK pueden ser generados implícitamente por InnoDB; el informe enumera solo los expresos en las migraciones, más PK/UQ. El estado aplicado en MariaDB se verifica por separado con `information_schema`.
