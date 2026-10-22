# Revisión de compatibilidad del panel del alumno

Fecha: 21 de octubre de 2026. Rama: `feature/panel-alumno`.

## Resultado

El módulo es compatible con el esquema versionado y los servicios existentes. La suite completa pasa: **69 pruebas, 505 aserciones**. También se comprobó en navegador contra MariaDB local con la cuenta de demostración `estudiante_demo` (seeder del proyecto): datos reales, sin errores de consola ni recursos faltantes. El error 1045 inicial se debía a `DB_PASSWORD` vacío en el `.env` local y se resolvió fuera del repositorio.

## Base de datos

- La consulta de progreso utiliza exclusivamente `intentos.usuario_id` y `intentos.tipo`, presentes tanto en la migración como en `base-de-datos.sql`.
- El perfil utiliza `usuarios.nombres`, `apellidos`, `rol` y `activo` mediante el modelo `Usuario` y el guard `web`.
- La condición de propietario procede del identificador de la sesión; no se acepta un identificador enviado desde el navegador.
- Correo electrónico, teléfono e inscripciones a cursos no están definidos en el esquema versionado. El panel no consulta columnas o tablas inexistentes ni muestra valores ficticios en su ruta real.
- No se agregaron migraciones, seeders ni escrituras desde el panel. No se aplicó el SQL original sobre ninguna base.

## Funciones comprobadas

| Función | Resultado |
| --- | --- |
| Perfil real `/alumno/perfil` | Sesión requerida; solo estudiante activo |
| Login y logout | Reutilizan el flujo existente; regreso a la URL solicitada y cierre comprobados |
| Actualización Livewire | Rechazada si la cuenta se desactiva o cambia a docente |
| Progreso | Un intento creado por la API existente cambia de 1/2 a 2/2 |
| Aislamiento | Intentos ajenos y prácticas no alteran el progreso del alumno |
| Consulta del perfil | No modifica el usuario, el intento ni las preguntas asignadas |
| Seguridad de salida | Datos personales escapados; sin exposición de contraseñas o claves de preguntas |
| Panel compartido | `/panel` conserva su funcionamiento |
| Recursos de diseño | Archivos locales, sin dependencias de URLs temporales |

Editar datos, cambiar contraseña, añadir teléfono, entrar al examen y abrir secciones de ayuda/recursos no están implementados como pantallas en el proyecto. Los controles del diseño permanecen deshabilitados; no se hacen llamadas JSON ni escrituras encubiertas desde ellos. Esos pendientes impiden describir el panel como completamente funcional.

## Alcance y límites

Las pruebas automáticas utilizan SQLite en memoria y las migraciones del proyecto; demuestran el recorrido entre API y panel, sin medir rendimiento o concurrencia. Las vistas compilan con `php artisan view:cache`.

Los cuatro archivos protegidos permanecen intactos. La única modificación en un archivo compartido es la línea de registro del proveedor en `bootstrap/providers.php`.
