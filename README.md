# RSU-CNI — Simulador CNI

Proyecto de Responsabilidad Social Universitaria de preparación académica para estudiantes del CNI.

## Arquitectura KISS

Una sola aplicación **Laravel 12 + Blade + Livewire 3 + MariaDB**, con autenticación por sesiones de Laravel. Las cuentas usan el modelo `Usuario` y la tabla `usuarios`. No se emiten tokens Bearer y no se necesita Firebase ni un frontend separado.

- **Presentación:** vistas en `resources/views/`, estilos en `public/css/app.css` y componentes en `app/Livewire/`.
- **Autenticación:** `SessionController` y `AuthService`, usando exclusivamente el guard `web`. Login, logout, regeneración de sesión, CSRF y límite de solicitudes compartido entre HTML y JSON.
- **Acceso:** `auth:web` y `EnsureActiveUser`; se conserva la autorización por roles y propietario de cada módulo.
- **Negocio:** servicios existentes de preguntas, simulacros, intentos y resultados.
- **Persistencia:** modelos/consultas y MariaDB. No cambia el esquema de negocio.

Las rutas `/api/*` se conservan para reutilizar el trabajo de los módulos, pero ahora son **rutas JSON de la misma aplicación web**: se cargan desde `routes/web.php`, comparten la cookie de sesión y requieren CSRF en operaciones de escritura. No son una API pública ni una autenticación independiente. Los componentes Livewire pueden llamar directamente a los servicios.

## Qué incluye esta adaptación

- `/`: bienvenida con enlace al acceso.
- `/login`: formulario de usuario y contraseña.
- `/panel`: componente Livewire que lista simulacros e intentos propios, incluidos puntajes ya registrados.
- `/logout`: cierre de sesión por POST.
- Módulos JSON existentes unificados con la misma sesión; pruebas adaptadas al guard `web`.

**Alcance:** establece la arquitectura y el acceso web. El panel inicial no sustituye las pantallas completas de gestión de preguntas, creación de simulacros y resolución del examen. El recorrido backend de simulacro (asignación, respuestas, cierre y calificación) cuenta con pruebas de integración; consulta `docs/recorrido-backend.md` para sus reglas y límites. Las pantallas completas siguen pendientes.

## Preparación local

Requiere PHP 8.2+, Composer 2 y MariaDB. PHP necesita las extensiones habituales de Laravel, `pdo_mysql` y, para las pruebas, `pdo_sqlite`.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configura la conexión local en `.env`. No publiques ese archivo ni contraseñas.

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simulador_cni_validacion
DB_USERNAME=cni_dev
DB_PASSWORD=TU_CONTRASEÑA_LOCAL
SESSION_DRIVER=file
```

**Base nueva y vacía:** revisar primero las migraciones y luego ejecutar `php artisan migrate`. Los seeders existentes crean cuentas de demostración con contraseña conocida; úsalos solo en una base local de prueba. No se ejecutan automáticamente.

**Base con el SQL importado:** no ejecutar `migrate`, `migrate:fresh` ni volver a importar `base-de-datos.sql` sin acordar la sincronización del historial de migraciones. `migrate:fresh` elimina tablas. La adaptación KISS no modifica ni elimina datos ni migraciones existentes; la antigua tabla de tokens se conserva por compatibilidad histórica, pero ya no se usa para autenticar.

```bash
php artisan route:list
php artisan serve
```

Abre `http://127.0.0.1:8000/login`. No se necesita `npm run dev` para el login y el panel inicial: CSS local y recursos de Livewire se sirven desde Laravel.

## Uso de respuestas JSON desde el mismo navegador

Primero ingresa por `/login`. Para llamadas `fetch`, envía `Accept: application/json`; en POST/PUT/DELETE añade `X-CSRF-TOKEN` con el valor de la etiqueta `meta[name="csrf-token"]`. No guardes un token Bearer ni contraseñas en localStorage. Las solicitudes sin sesión reciben 401; las escrituras sin CSRF reciben 419. Desde Blade/Livewire se recomienda usar formularios y acciones en lugar de hacer solicitudes HTTP internas a la propia aplicación.

## Verificación

```bash
composer validate --strict
php artisan view:cache
php artisan test
```

`phpunit.xml` selecciona SQLite en memoria, caché y sesiones de prueba. No uses la base MariaDB compartida para ejecutar tests. El workflow de GitHub repite instalación, validación, compilación de vistas y pruebas.

Los cambios se proponen mediante Pull Request a `develop`; no se modifica `main` directamente.

## Recorrido del simulacro

`php artisan test --filter=RecorridoSimulacroTest` comprueba el recorrido usando SQLite en memoria. El inicio asigna preguntas, la finalización califica y los resultados se consultan con la misma sesión web. No requiere nuevas migraciones ni seeders.

El cierre de intentos vencidos está definido cada minuto en Laravel Scheduler; al desplegar hay que activar el scheduler. En desarrollo puede mantenerse `php artisan schedule:work` en otra terminal. Las preguntas y configuraciones ya usadas no se editan; se crean nuevas versiones para proteger los intentos existentes.
