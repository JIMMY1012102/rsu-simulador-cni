# OFICIAL#1 · Panel administrativo y examen del alumno

Esta rama incluye el panel de superadministración ya existente en Laravel, el panel unificado del alumno (áreas A–E) y un botón «Dar examen» directamente en **Mi perfil**.

## Configuración local (Linux Mint)

1. Descarga la rama (sin fusionarla con develop):
   ```bash
   cd ~/rsu-simulador-cni
   git fetch origin
   git switch 'OFICIAL#1'
   git pull --ff-only
   ```
2. Inicia MariaDB; si tienes XAMPP usa su servicio correspondiente, **no dos servidores en el mismo puerto**:
   ```bash
   sudo systemctl start mariadb
   sudo systemctl is-active mariadb
   ```
3. Mantén tu `.env` local (no está versionado). Configura tus credenciales reales de MariaDB y habilita estas opciones:
   ```ini
   CNI_ALUMNO_AREAS_ENABLED=true
   CNI_ADMIN_ACADEMICO_ENABLED=true
   ```
   **Nunca publiques tu `.env` ni contraseñas en GitHub.**
4. Sin borrar ni recrear la base de datos:
   ```bash
   php artisan optimize:clear
   php artisan db:show
   php artisan test --filter=AccesoPanelOficialTest
   php artisan test --filter=PanelesPorAreaTest
   php artisan test --filter=ProgresoAlumnoTest
   php artisan serve
   ```

## Recorrido funcional

- **Administrador:** con una cuenta de rol `admin`, el inicio de sesión lleva a `/administracion` si `CNI_ADMIN_ACADEMICO_ENABLED=true`. Allí ya están el resumen, el banco de preguntas y los formularios de simulacros.
- **Alumno:** entra a `/alumno/panel#perfil`. En la tarjeta principal ve «Dar examen» cuando existe un único simulacro activo, vigente y habilitado para su área/carrera. Se hace POST con CSRF a `/simulacros/{simulacro}/iniciar`, que reanuda o crea el intento y abre la pantalla Livewire.
- Cuando hay varios, «Elegir simulacro» muestra el listado del área con sus botones individuales.
- Si no hay exámenes disponibles, aparece un aviso sin crear intentos ficticios.
- Tras finalizar el examen, el puntaje calificado se refleja en **Mi progreso** e **Historial** mediante los servicios del proyecto.

La pantalla de examen incluida es la que ya estaba integrada en OFICIAL#1. La rama divergente `feature/examen-alumno` tiene otros cambios de interfaz y backend; no se ha fusionado de golpe porque podría sobreescribir la plantilla unificada y romper firmas Livewire.

## Publicar simulacros

Un administrador debe contar con preguntas válidas en el banco y crear un simulacro desde `/administracion/simulacros/nuevo`, eligiendo la **misma área de postulación del alumno**, una ventana vigente y una distribución con suficientes preguntas. Un examen que no cumpla esos requisitos no aparecerá en la portada del alumno.

No ejecutes `migrate:fresh` ni `db:wipe` en bases con usuarios o intentos: borran datos. Si faltan migraciones, revisa antes con `php artisan migrate:status` y realiza un respaldo.
