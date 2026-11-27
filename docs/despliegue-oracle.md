# Despliegue manual de Davis en Oracle Cloud

Davis es un servicio Laravel: no necesita levantar el agente Python/MCP ni abrir el puerto 8001. No requiere migraciones de base de datos.

## Requisitos

- PHP 8.2 o superior, Composer y extensiones requeridas por Laravel.
- .env existente en el servidor y permisos de escritura en storage y bootstrap/cache.
- Una clave de Gemini AI Studio con acceso a Gemini Developer API.
- El servidor debe poder realizar conexiones HTTPS salientes a generativelanguage.googleapis.com.

## Configuración de Gemini en Oracle

En el archivo .env del servidor (nunca subirlo a Git ni compartir su contenido), verifica que existan estas variables:

```dotenv
GEMINI_API_KEY=TU_CLAVE_REAL
GEMINI_MODEL=gemini-3.8-flash
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta
```

Conserva la clave real que ya tengas configurada. Si el proyecto usa otra variable para los otros asistentes, Davis no la detectará automáticamente: su nombre esperado es GEMINI_API_KEY. No es necesario copiar ni mostrar el valor de la clave.

## Actualizar manualmente desde GitHub

Antes de actualizar, revisa git status --short. No uses reset --hard, git clean ni descartes modificaciones locales. Si hay cambios locales en archivos que GitHub también modificó, resuélvelos antes del pull.

Desde la raíz del proyecto:

```bash
git fetch origin
git status --short
git pull --ff-only origin 'OFICIAL#1'
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

No ejecutes php artisan migrate para Davis: esta integración no incorpora migraciones. Tampoco ejecutes seeders de demostración en la base real.

## Comprobaciones sin revelar secretos

Comprueba que Laravel puede leer la configuración sin imprimir la clave:

```bash
php artisan tinker --execute="dump(['gemini_key_configured' => filled(config('services.gemini.key')), 'gemini_model' => config('services.gemini.model'), 'gemini_url' => config('services.gemini.url')]);"
```

El resultado esperado es gemini_key_configured => true, el modelo configurado y la URL de Google. Si acabas de editar .env, ejecuta antes php artisan config:clear y vuelve a generar la caché.

Después inicia sesión con una cuenta docente activa y prueba una consulta breve en /docente. Si falla, revisa storage/logs/laravel.log; no publiques la clave ni registros que puedan contenerla.

## Despliegue automatizado opcional

El workflow Deploy to Oracle server sigue siendo manual y requiere configurar previamente los cinco secrets SSH indicados en GitHub Actions. No es necesario usarlo si actualizas con SSH y git pull.

## Actualización manual por SSH (git pull)

Davis es un servicio Laravel: no necesita levantar el agente Python/MCP ni abrir el puerto 8001. No incorpora migraciones de base de datos.

Antes de actualizar, entra a la raíz del proyecto y revisa los cambios locales. No uses reset --hard, git clean ni descartes modificaciones locales. Si hay cambios locales en archivos que GitHub también modificó, resuélvelos antes del pull.

```bash
git fetch origin
git status --short
git pull --ff-only origin 'OFICIAL#1'
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

En el .env de Oracle conserva tu clave real y confirma estas variables (no compartas ni publiques el valor de la clave):

```dotenv
GEMINI_API_KEY=TU_CLAVE_REAL
GEMINI_MODEL=gemini-3.8-flash
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta
```

El nombre que Davis lee es GEMINI_API_KEY. Si otros asistentes usan otra variable de entorno, no se reutiliza automáticamente. Si editas .env, ejecuta php artisan optimize:clear y luego php artisan config:cache.

No ejecutes php artisan migrate para Davis ni seeders de demostración en la base real. Comprueba la configuración sin revelar secretos:

```bash
php artisan tinker --execute="dump(['gemini_key_configured' => filled(config('services.gemini.key')), 'gemini_model' => config('services.gemini.model'), 'gemini_url' => config('services.gemini.url')]);"
```

El valor gemini_key_configured debe ser true. Luego inicia sesión con una cuenta docente activa y prueba una consulta breve en /docente. Si falla, revisa storage/logs/laravel.log sin compartir claves ni datos sensibles.


## Modelos alternativos de Davis

Davis intenta primero el modelo indicado por GEMINI_MODEL. Si Google devuelve un error transitorio (408, 429, 500, 502, 503 o 504), un error de conexión o indica que el modelo solicitado no existe (404 relacionado con el modelo), prueba los modelos siguientes en orden. Los errores de autenticación (401/403) y de solicitud (400) no generan reintentos, porque cambiar de modelo no corrige esos problemas.

La configuración por defecto es:

```dotenv
GEMINI_MODEL=gemini-3.8-flash
GEMINI_FALLBACK_MODELS=gemini-3.5-flash-lite,gemini-4-hydrogen
```

GEMINI_FALLBACK_MODELS es opcional y acepta IDs separados por comas. Si no se define en .env, Laravel utiliza los dos respaldos predeterminados de config/services.php. Después de modificar .env, ejecuta:

```bash
php artisan config:clear
php artisan config:cache
```

Davis registra en laravel.log el modelo que falla y el modelo que finalmente responde, sin registrar la API key ni el contenido de la consulta.
