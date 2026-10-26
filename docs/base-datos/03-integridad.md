# Relaciones, cardinalidades y restricciones

> **Fuente:** `develop` (`eb82cd46c35567c4b7d840a524a339e28c0f18ca`) · **Fecha:** 26/10/2026. **Estado:** esquema esperado en código; MariaDB compartida no consultada en vivo.

Hay 24 claves foráneas declaradas. Cada registro hijo tiene una relación obligatoria (FK NN) u opcional (FK NULL) hacia **a lo sumo un padre**; cada padre puede estar vinculado a cero o muchas filas hijas. Todas tienen ON UPDATE CASCADE.

| Hija | Columna FK | Padre | Nulabilidad | ON DELETE |
|---|---|---|---|---|
| `carreras` | `area_postulacion_id` | `areas_postulacion.id` | NN | RESTRICT |
| `usuarios` | `seccion_id` | `secciones.id` | NULL | RESTRICT |
| `usuarios` | `carrera_id` | `carreras.id` | NULL | SET NULL |
| `usuarios` | `area_postulacion_id` | `areas_postulacion.id` | NULL | RESTRICT |
| `temas` | `area_id` | `areas.id` | NN | RESTRICT |
| `preguntas` | `tema_id` | `temas.id` | NN | RESTRICT |
| `preguntas` | `creado_por` | `usuarios.id` | NULL | SET NULL |
| `alternativas` | `pregunta_id` | `preguntas.id` | NN | CASCADE |
| `simulacros` | `creado_por` | `usuarios.id` | NULL | SET NULL |
| `simulacros` | `carrera_id` | `carreras.id` | NULL | RESTRICT |
| `area_postulacion_simulacro` | `area_postulacion_id` | `areas_postulacion.id` | NN | CASCADE |
| `area_postulacion_simulacro` | `simulacro_id` | `simulacros.id` | NN | CASCADE |
| `simulacro_area` | `simulacro_id` | `simulacros.id` | NN | CASCADE |
| `simulacro_area` | `area_id` | `areas.id` | NN | RESTRICT |
| `intentos` | `usuario_id` | `usuarios.id` | NN | RESTRICT |
| `intentos` | `carrera_id` | `carreras.id` | NULL | SET NULL |
| `intentos` | `simulacro_id` | `simulacros.id` | NULL | RESTRICT |
| `intentos` | `area_id` | `areas.id` | NULL | SET NULL |
| `intentos` | `tema_id` | `temas.id` | NULL | SET NULL |
| `respuestas_estudiante` | `intento_id` | `intentos.id` | NN | CASCADE |
| `respuestas_estudiante` | `pregunta_id` | `preguntas.id` | NN | RESTRICT |
| `respuestas_estudiante` | `alternativa_id` | `alternativas.id` | NULL | SET NULL |
| `resultados_area` | `intento_id` | `intentos.id` | NN | CASCADE |
| `resultados_area` | `area_id` | `areas.id` | NN | RESTRICT |

## Índices y UNIQUE explícitos

| Tabla | UNIQUE (además de PK) | INDEX expresos |
|---|---|---|
| `secciones` | `(nombre, grado)` | — |
| `areas_postulacion` | `(codigo)`, `(nombre)` | — |
| `carreras` | `(nombre)` | — |
| `usuarios` | `(usuario)` | `(rol, activo)` |
| `areas` | `(nombre)` | — |
| `temas` | `(area_id, nombre)` | — |
| `preguntas` | — | `(tema_id, nivel, activo)` |
| `alternativas` | `(pregunta_id, letra)` | — |
| `simulacros` | — | `(creado_por)` |
| `area_postulacion_simulacro` | `(area_postulacion_id, simulacro_id)` | `(simulacro_id)` |
| `simulacro_area` | `(simulacro_id, area_id, nivel)` | `(area_id)` |
| `intentos` | — | `(usuario_id, fecha_inicio)`, `(simulacro_id, estado)`, `(estado, fecha_limite)`, `(carrera_id)`, `(area_id)`, `(tema_id)` |
| `respuestas_estudiante` | `(intento_id, pregunta_id)`, `(intento_id, orden)` | `(pregunta_id)`, `(alternativa_id)` |
| `resultados_area` | `(intento_id, area_id)` | `(area_id)` |
| `personal_access_tokens` | `(token)` | `(tokenable_type, tokenable_id)`, `(expires_at)` |

## CHECK condicionales en MariaDB

- **alternativas:** chk_alternativas_letra: letra BETWEEN 'A' AND 'Z'. La migración MariaDB aplica charset ascii y collation ascii_bin a letra.
- **simulacros:** chk_simulacros_tiempo (>0); chk_simulacros_total (>0); chk_simulacros_alternativas (2..26); chk_simulacros_fechas (fin > inicio); chk_simulacros_p_correcta (>0); chk_simulacros_p_incorrecta (<=0).
- **simulacro_area:** chk_simulacro_area_cantidad: cantidad_preguntas > 0.
- **resultados_area:** chk_resultados_porcentaje: porcentaje BETWEEN 0 AND 100.

## Integridad de aplicación (no impuesta por SQL)

- Una alternativa marcada en `respuestas_estudiante` debe pertenecer a `pregunta_id`: dos FKs independientes **no prueban** esa correspondencia; se valida en `IntentoService`.
- Un banco útil requiere cuatro alternativas y una sola correcta (interfaz actual), comprobado al guardar/publicar/iniciar; no hay CHECK interfilas para esta regla.
- La carrera asignada a un usuario y su área explícita deben concordar. `usuarios.area_postulacion_id` y `carreras.area_postulacion_id` no están ligados mediante FK compuesta.
- La suma de cuotas `simulacro_area.cantidad_preguntas` debe igualar el total del simulacro; regla verificada por Laravel, no por una columna CHECK.
- Los resultados históricos se persisten en `intentos`, `respuestas_estudiante`, `resultados_area`; el código califica de forma transaccional e idempotente.
- `personal_access_tokens` tiene índice morfológico `(tokenable_type, tokenable_id)`, pero **no FK física** hacia `usuarios`.

**No crear índices ni restricciones sin medir el estado de InnoDB real**, porque algunas FKs ya inducen índices implícitos y puede haber solapamiento con UNIQUE/INDEX declarados.
