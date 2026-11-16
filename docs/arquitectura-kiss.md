# Decisión: autenticación y estructura KISS

La aplicación web comparte Laravel, Blade, Livewire, sesiones y MariaDB. No se incorporan servicios externos de identidad ni un frontend React separado.

El navegador envía credenciales al login. `AuthService` usa `Auth::guard('web')->attempt`, comprueba cuenta activa y rol admitido, y el controlador regenera la sesión. Las siguientes solicitudes usan su cookie. El logout invalida la sesión y renueva CSRF. Los endpoints JSON dejan de emitir y aceptar tokens de API.

Todos los módulos requieren `auth:web` y `EnsureActiveUser`. La autenticación identifica a la persona; los controles existentes de rol y propietario deciden qué puede hacer. El middleware de cuenta activa se registra también como persistente para las solicitudes posteriores de Livewire.

Las rutas JSON se mantienen bajo `/api` para no rehacer los módulos existentes. Se cargan una única vez dentro del grupo web, con sesión y CSRF. El nombre del prefijo no significa que se siga usando autenticación con tokens.

El panel reutiliza `SimulacroService`. Otros componentes pueden llamar a `PreguntaService`, `IntentoService` y `ResultadoService` directamente, conservando sus validaciones y autorización. No se debe duplicar la lógica en las vistas ni realizar HTTP contra la propia aplicación.

No se añade registro público, recuperación de contraseña ni acceso social. La administración de cuentas sigue siendo responsabilidad del proyecto y no se incorpora una pantalla nueva para ello en este cambio.

Las pantallas completas de preguntas, examen y gestión de simulacros siguen pendientes. Esta entrega no cambia la lógica del examen ni declara terminado el sistema. No se aplican migraciones ni seeders a bases existentes y no se elimina la tabla histórica de tokens.
