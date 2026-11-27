# Guía operativa del Super_Admin — Simulador CNI

## Objetivo

El asistente debe funcionar como copiloto de supervisión: mostrar qué está ocurriendo, detectar señales que requieren atención, localizar cuentas y explicar qué módulo de la plataforma debe utilizarse para resolverlas.

El agente recibe la identidad desde la sesión Laravel. El servidor MCP verifica el rol `admin` y confirma que el usuario autenticado sea exactamente `Super_Admin`. El modelo no puede elegir otro usuario ni cambiar la identidad de consulta.

## Herramientas disponibles

### Panel y diagnóstico general
- `panel_superadmin`: panorama ejecutivo de usuarios, catálogo, banco, simulacros, intentos y actividad de los últimos siete días.
- `actividad_plataforma(dias, limite)`: tendencia diaria de intentos y participación.
- `intentos_atrapados(horas, limite)`: intentos que permanecen en curso más tiempo del umbral.

### Usuarios y acompañamiento
- `buscar_usuarios(buscar, rol, estado, limite)`: localiza cuentas por nombre, apellido, usuario o ID.
- `ficha_estudiante(identificador)`: ficha académica por ID o usuario exacto, últimos resultados y resumen de intentos.
- `estudiantes_sin_actividad(dias, limite)`: estudiantes activos que nunca han rendido o no tienen actividad reciente.
- `estudiantes_en_riesgo(umbral_puntaje, limite)`: estudiantes cuyo último simulacro calificado está por debajo del umbral.
- `resumen_docentes(limite)`: docentes, sección asignada y estudiantes de esa sección.

### Rendimiento académico
- `rendimiento_por_asignatura`: promedios porcentuales y participación por asignatura.
- `rendimiento_por_seccion`: promedio del último simulacro calificado por sección.
- `preguntas_mas_falladas(limite)`: preguntas con menor porcentaje de acierto, con al menos cinco respuestas calificadas.
- `resumen_por_area_postulacion`: estudiantes, carreras y simulacros asociados a cada área A–E.

### Banco y catálogo
- `cobertura_catalogo_academico`: temas y preguntas activas y preguntas estructuralmente válidas por asignatura.
- `estado_del_banco_de_preguntas`: preguntas activas agrupadas por asignatura y dificultad.
- `auditar_banco_preguntas(limite)`: detecta preguntas que no tienen exactamente cuatro alternativas y una correcta.

### Simulacros
- `simulacros_operativos(limite)`: vigencia, áreas asociadas, cuotas, intentos y promedio.
- `detalle_simulacro(simulacro_id)`: configuración, áreas de postulación y distribución de preguntas.
- `simulacros_recientes(limite)`: resumen de simulacros recientes.

## Flujo recomendado para no perderse

1. **Inicio del día:** pedir «Dame el panorama ejecutivo y dime qué debo atender primero». El asistente debe consultar `panel_superadmin`.
2. **Priorizar:** pedir detalle solo de las alertas detectadas: intentos atrapados, estudiantes sin actividad, estudiantes en riesgo, banco inválido o simulacros próximos.
3. **Investigar:** localizar usuarios o simulacros con su herramienta específica, sin solicitar datos de autenticación.
4. **Resolver:** entrar al panel administrativo de Laravel en `/administracion`. Las operaciones de escritura siguen ejecutándose en los formularios y controladores existentes.
5. **Verificar:** volver a consultar las herramientas MCP y comprobar que el cambio se refleje en la base de datos.

## Preguntas listas para usar

- «Dame el estado general de la plataforma y ordéname los pendientes por prioridad».
- «¿Qué estudiantes activos llevan 14 días sin rendir?».
- «Identifica estudiantes cuyo último simulacro esté por debajo de 50 puntos».
- «¿Qué asignaturas tienen menos preguntas válidas para publicar simulacros?».
- «Audita el banco y dime qué preguntas no tienen cuatro alternativas y una correcta».
- «¿Qué simulacros están vigentes o próximos y cuántos intentos tienen?».
- «Revisa si hay intentos que llevan más de tres horas en curso».
- «Busca al estudiante por su usuario exacto y resume sus últimos resultados».
- «Compara el rendimiento por asignatura y por sección».
- «¿Qué preguntas tienen el menor porcentaje de acierto?».

## Seguridad y límites

- Todas las herramientas MCP de este conjunto son **solo lectura** y usan consultas SELECT parametrizadas con límites.
- No se consultan contraseñas, hashes, tokens de sesión ni claves API.
- La conexión de base de datos del agente debe conservar permisos SELECT mínimos. El script `crear_usuario_solo_lectura.sql` incluye únicamente las columnas de usuario necesarias.
- El asistente no puede importar alumnos, crear o editar preguntas, activar usuarios ni publicar simulacros. Debe guiar al Super_Admin al módulo correspondiente de `/administracion`, nunca fingir que ejecutó el cambio.
- No se deben exponer datos personales completos en informes generales. La ficha individual solo se consulta cuando el administrador identifica a un estudiante concreto.
- Una alerta estructural no demuestra que el contenido pedagógico de una pregunta sea incorrecto; requiere revisión humana.

## Instalación y actualización local

Esta mejora está en la rama `feat/superadmin-mcp-operativo`. Descarga esa rama o intégrala en tu copia local antes de reiniciar Uvicorn. No copies archivos `.env` al repositorio ni reemplaces tus claves locales.

Si el usuario MariaDB `agente` ya fue creado con la versión anterior de permisos, vuelve a ejecutar la sentencia GRANT actualizada del archivo `crear_usuario_solo_lectura.sql` (ajustando el nombre real de la base y sin cambiar la contraseña existente).
