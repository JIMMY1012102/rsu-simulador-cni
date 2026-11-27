# Ficha de los asistentes con IA

| Campo | Estudiante | Superadministrador |
|---|---|---|
| Nombre | Asistente CNI del estudiante | Asistente CNI admin |
| Usuario | Sesión autenticada con rol estudiante | Sesión autenticada con usuarios.rol = 'admin' |
| Preguntas que resuelve | Resultados recientes; rendimiento por área; temas a reforzar; simulacros disponibles | Resumen global; banco por área y nivel; simulacros recientes; rendimiento por sección; preguntas más falladas |
| Tablas que necesita | usuarios, intentos, simulacros, resultados_area, respuestas_estudiante, preguntas, temas, areas | usuarios, secciones, simulacros, intentos, preguntas, respuestas_estudiante, temas, areas |
| Lo que no hace | No consulta otros estudiantes ni modifica datos o exámenes | No modifica usuarios, preguntas, simulacros ni resultados; no consulta contraseñas |
| Tono | Cercano, claro y motivador | Profesional, claro y conciso |
| Acción de escritura | Ninguna (solo lectura) | Ninguna (solo lectura) |

Laravel obtiene rol e identificador de la sesión; el modelo no decide la identidad. Cada rol registra únicamente sus propias herramientas MCP.
