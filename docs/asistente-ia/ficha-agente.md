# Ficha de los asistentes con IA

## Asistente del estudiante

| Campo | Definición |
|---|---|
| Identidad | Laravel envía rol e ID desde la sesión autenticada; Gemini no elige identidad. |
| Herramientas MCP | Cinco herramientas de solo lectura: resultados, rendimiento, progreso, simulacros y banco académico. |
| Datos propios | Solo intentos y respuestas del usuario autenticado. |
| Catálogo académico | Solo asignaturas vinculadas a simulacros asociados al área de postulación/carrera del estudiante. |
| Preguntas | Solo preguntas activas, temas activos y áreas activas. |
| Soluciones | No se incluyen por defecto. Solo se consultan cuando el estudiante pide explicación o revisión. |
| Seguridad SQL | SELECT exclusivamente, parámetros enlazados y LIMIT en cada consulta. Sin SELECT *, INSERT, UPDATE, DELETE, DDL ni herramientas de escritura. |
| Respuesta | Español, máximo cuatro líneas, sin cifras ni fechas inventadas. |
| Memoria | Contexto conversacional temporal por sesión y usuario; no es memoria persistente. |

### Herramientas del estudiante

1. `consultar_mis_resultados(limite)`: últimos intentos calificados propios, con puntaje y desglose.
2. `consultar_mi_rendimiento()`: promedios por asignatura y cantidades de respuestas.
3. `consultar_mi_progreso(limite)`: temas de su área, preguntas disponibles/respondidas y aciertos.
4. `consultar_mis_simulacros(estado, limite)`: `disponibles`, `proximos` o `historial`, limitado al área/carrera del estudiante.
5. `consultar_banco_academico(tema, nivel, buscar, incluir_solucion, limite)`: preguntas y alternativas; la solución y clave se omiten salvo solicitud de explicación.

## Configuración de modelos Gemini

- `MODELO`: modelo principal. Si no se define, se usa `gemini-3.5-flash-lite`.
- `MODELOS_RESPALDO`: lista opcional separada por comas y en orden de prioridad. Valor predeterminado: `gemini-3.6-flash,gemini-3.5-flash`.
- `MODELO_FALLBACK`: variable heredada de compatibilidad; se usa como único respaldo cuando `MODELOS_RESPALDO` no está definido.
- El agente recorre los modelos en orden solo ante errores transitorios HTTP 429, 500, 502, 503 o 504. Los demás errores se propagan sin probar otros modelos.
- La misma configuración, historial y declaraciones de herramientas MCP se conservan al cambiar de modelo, tanto en la respuesta inicial como después de cada llamada a una herramienta.

## Asistente del superadministrador

Se conserva el asistente admin existente y sus cinco herramientas: `resumen_general`, `estado_del_banco_de_preguntas`, `simulacros_recientes`, `rendimiento_por_seccion` y `preguntas_mas_falladas`. Todas son SELECT de solo lectura y no consultan contraseñas.

## Roles pendientes de definición

El asistente docente no se implementa en este cambio. Su alcance debe confirmarse con la profesora: por ejemplo, si cada docente puede consultar solo sus secciones y qué datos de estudiantes puede visualizar. No se habilita acceso docente por suposición.

## Límites y controles

- Laravel autentica y envía `rol`, `usuario_id` y `sesion`; el cliente web no elige el usuario consultado.
- El servidor MCP fija rol e ID mediante variables de entorno del proceso.
- El usuario SQL de agente debe permanecer de solo lectura en MariaDB.
- El modelo no recibe contraseña, remember token ni datos de autenticación.
- Las herramientas limitan resultados y parametrizan los valores recibidos.
