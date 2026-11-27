# Integración del recorrido de simulacro (KISS)

## Alcance comprobado

El recorrido usa las rutas JSON de la misma aplicación, la sesión web existente y los servicios actuales. No añade tablas, dependencias, tokens ni servicios externos.

| Paso | Operación |
|---|---|
| Preparar el examen | Docente autenticado crea preguntas y configura un simulacro mediante las rutas existentes. |
| Ingresar | Estudiante inicia sesión por `/login`. |
| Elegir | Consulta `/api/simulacros`; el inicio comprueba actividad y fechas. |
| Iniciar | `POST /api/intentos` con `tipo=simulacro` y `simulacro_id`; sortea y guarda las preguntas antes de confirmar la transacción. |
| Reanudar | `GET /api/intentos/{id}` devuelve el intento propio, las respuestas guardadas y `data.preguntas` con enunciados y alternativas, sin solución ni clave correcta. |
| Responder | `POST /api/intentos/{id}/respuestas` guarda o cambia la alternativa; `null` borra la elección. |
| Finalizar | `POST /api/intentos/{id}/finalizar` cierra y llama a `ResultadoService::calificar` en la misma transacción. Repetir la petición no duplica resultados. |
| Consultar | `GET /api/resultados/{id}` devuelve los totales y el desglose por área. La ruta de calificación manual se conserva. |

Las solicitudes de escritura conservan la protección CSRF del grupo web. Las pruebas de integración hacen login real y atraviesan las rutas y sus permisos; las pruebas de autenticación existentes comprueban CSRF expresamente.

## Fallos detectados y corregidos

- Se creaba un intento sin filas en `respuestas_estudiante`: no había preguntas que responder.
- El envío final no llamaba al servicio de resultados.
- La calificación intentaba incrementar `correcta`/`incorrecta`, pero los contadores se llaman `correctas`/`incorrectas`.
- La respuesta tardía actualizaba el estado y después lanzaba una excepción dentro de la transacción: el cierre se revertía. Ahora el cierre y la calificación se confirman antes de devolver el error de tiempo agotado.
- El sorteo respeta cuotas de área y nivel sin duplicados, prioriza cuotas específicas antes de las mixtas y conserva la secuencia de bloques documentada. Solo admite preguntas, temas y áreas activos, con el número configurado de alternativas y una única correcta. Si falta banco, no crea un intento parcial.
- Las preguntas y la configuración de simulacros ya utilizados no se editan: se crea una nueva versión. Esto evita perder elecciones al reemplazar alternativas o cambiar puntajes históricos, sin introducir tablas de versionado.

## Tiempo y reglas utilizadas

El servidor calcula `fecha_limite = fecha_inicio del intento + tiempo_limite`. El rango de disponibilidad del simulacro se comprueba al iniciar; no recorta la duración de un intento que ya comenzó. En el instante exacto del límite ya no se guardan respuestas.

Un intento vencido se cierra y califica al consultarlo, responder o finalizar. Para cerrarlo aunque el navegador esté cerrado, existe el comando `intentos:cerrar-vencidos`, programado cada minuto mediante Laravel Scheduler. **La definición del horario no instala el cron del servidor.** En desarrollo se puede mantener `php artisan schedule:work` en otra terminal; al desplegar debe configurarse el scheduler de Laravel. El comando aislado `php artisan intentos:cerrar-vencidos` modifica los intentos vencidos de la base configurada y no debe usarse como una prueba inocua sobre datos reales.

La carrera del intento se copia del perfil. Si el simulacro tiene áreas de postulación configuradas, se comprueba que la carrera del perfil esté activa y pertenezca a un área permitida y activa. En coherencia con el campo opcional del contrato actual, un simulacro sin áreas de postulación configuradas se trata como abierto; no se introduce el requisito global de carrera para todos los simulacros.

## Cómo repetir la comprobación

```bash
php artisan test --filter=RecorridoSimulacroTest
```

`phpunit.xml` dirige estas pruebas a SQLite en memoria. Se crean usuarios, preguntas y simulacros desechables dentro de las pruebas; no se cargan seeders ni se modifica MariaDB. No hace falta ejecutar migraciones manualmente para esta prueba.

El caso principal usa tres preguntas: una correcta (+20), una incorrecta (-1.125) y una en blanco (0), con resultado esperado **18.875**. Otros casos comprueban banco insuficiente o inválido, fechas, cuotas, reanudación, aislamiento de estudiantes, modificación de respuestas, vencimiento, cierre programado, inmutabilidad de exámenes usados, rollback ante errores y puntajes negativos.

## Límites de esta comprobación

Se valida el recorrido de **simulacro**, no todos los requerimientos del sistema. Las prácticas por tema/área, la elección de carrera en una pantalla, las restricciones adicionales de visibilidad del catálogo, las importaciones/exportaciones, los reportes completos y el frontend del examen requieren su propio trabajo. La prueba usa SQLite; no demuestra comportamiento concurrente ni rendimiento en MariaDB. El diseño SQL original sigue siendo una referencia preliminar y contiene menciones históricas a Sanctum que ya fueron sustituidas por la decisión de sesiones web.

## Resultado de la ejecución del 20/10/2026

Suite completa: **59 pruebas aprobadas y 454 aserciones**, incluyendo 16 pruebas nuevas de recorrido. Se ejecutó con PHP 8.3 y SQLite en memoria. La validación estricta de Composer, el listado del scheduler y la revisión de espacios del diff también finalizaron correctamente. No se aplicaron migraciones ni se modificó la base local del equipo.
