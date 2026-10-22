# Validación del nuevo flujo RSU-CNI (PR #18)

## Estado de las pruebas
GitHub Actions ejecuta dos trabajos independientes: **SQLite** y **MariaDB 11.4**.
El trabajo MariaDB utiliza un contenedor temporal con datos ficticios que se descartan al terminar.
No accede a ninguna instancia instalada en ordenadores, hosting u Oracle Cloud.

## Qué puede hacer el equipo antes de fusionar
1. Tener respaldo verificado de la base actual.
2. En una copia separada de desarrollo que apunte a MariaDB, ejecutar:
   ```bash
   php artisan migrate:status
   php artisan cni:auditar-areas
   ```
   El comando **solo consulta datos**, nunca los altera.
3. Si el diagnóstico muestra el catálogo anterior (4 áreas, otros códigos), **detenerse**. Es necesario documentar un remapeo por IDs que preserve las relaciones de `carreras`, `usuarios` y `area_postulacion_simulacro`. No usar `migrate:fresh` sobre datos reales.
4. Una vez aprobado y aplicado ese remapeo **en la copia de desarrollo**, ejecutar:
   ```bash
   php artisan migrate --force
   php artisan cni:auditar-areas
   ```
5. Con la base de prueba y las cinco áreas correctas, activar el simulador de demostración **solo si `APP_ENV=local` o `APP_ENV=testing`**, y nunca en producción:
   ```bash
   php artisan db:seed --class=SimulacrosCincoAreasDemoSeeder
   php artisan test --filter=CincoAreasMariaDBTest
   ```
6. Probar en navegador con usuario de prueba: login → escoger área C → panel → iniciar examen C → responder → finalizar → resultados → cerrar sesión y regresar al mismo panel. Repetir con área E y comprobar bloqueo cruzado.
7. Confirmar integración del diseño visual del PR #13 y compatibilidad con las rutas actuales de los docentes.

## Nota académica
Los cinco exámenes demostrativos contienen **20 preguntas** cada uno y usan un banco ficticio de 40 preguntas, con distribuciones de ejemplo. No son pruebas oficiales del CNI. Los exámenes reales deben prepararse y publicarse con las cuotas académicas aprobadas por el equipo.

## Seguridad de la integración
- No se despliega ni se abre conexión de producción desde GitHub Actions.
- El área seleccionada persiste en `usuarios.area_postulacion_id`.
- Las cantidades por asignatura proceden de `simulacro_area` y el sorteo de preguntas se valida en Laravel.
- El navegador no puede enviar un identificador de área diferente para saltarse el control de acceso del simulacro asociado.
- El catálogo legado se detecta sin cambiar códigos ni IDs silenciosamente.

**No fusionar en un entorno compartido con MariaDB poblada hasta completar la auditoría local y aprobar la migración de datos heredados.**

## Integración segura a develop mediante bandera (feature flag)

La integración de código puede fusionarse a `develop` aunque la base compartida aún no haya sido auditada, **porque el flujo nuevo está desactivado por defecto**:

```dotenv
CNI_ALUMNO_AREAS_ENABLED=false
```

- Con `false`, el login del estudiante, su ruta `/panel` y los accesos docentes antiguos siguen como antes; las rutas del nuevo flujo responden 404.
- Con `true`, el login redirige a `/alumno/elegir-area` para quienes no hayan elegido todavía y luego a `/alumno/panel`, tras aplicar la nueva columna en la BD.
- **No activar en una instalación con catálogo incompatible o sin la columna**; primero respaldar, auditar y resolver el remapeo de códigos heredados.
- Para activar o desactivar, configurar la variable en `.env` de ese entorno y limpiar/recrear la caché de configuración, conforme al despliegue.
- El PR #13 de perfil sigue separado y requiere coordinación antes de fusionarse con este diseño.

Esta bandera protege el comportamiento de las sesiones existentes **sin tocar datos reales**; no sustituye las pruebas manuales ni crea automáticamente los catálogos correctos.
