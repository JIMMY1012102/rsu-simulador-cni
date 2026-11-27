# RSU-CNI — Simulador CNI

Aplicación web académica construida con **Laravel 12, Livewire 3 y MariaDB**.

Esta rama, `local-xampp-antix`, está preparada para instalar una copia de desarrollo en **antiX Linux + XAMPP** y abrirla desde el navegador en `http://localhost/rsu-cni`. No modifica la rama `OFICIAL#1`.

## Instalación local en antiX Linux con XAMPP

### 1. Requisitos

- XAMPP para Linux instalado (normalmente en `/opt/lampp`).
- PHP 8.2 o superior con extensiones de Laravel y `pdo_mysql`.
- Composer 2.
- Node.js y npm (para compilar los recursos CSS/JS).
- Git (opcional, si clonas en vez de descargar ZIP).

Inicia Apache y MySQL desde el panel de XAMPP:

```bash
sudo /opt/lampp/lampp start
```

### 2. Descargar el proyecto

Descomprime esta rama dentro de `/opt/lampp/htdocs/rsu-simulador-cni` (o clónala allí):

```bash
cd /opt/lampp/htdocs
git clone --branch local-xampp-antix https://github.com/JIMMY1012102/rsu-simulador-cni.git rsu-simulador-cni
cd rsu-simulador-cni
```

Si descargaste el ZIP, entra en la carpeta que contiene `artisan`.

### 3. Instalar dependencias y preparar Laravel

Desde la raíz del proyecto:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan storage:link
```

Si `composer` no encuentra PHP, ejecuta Composer con el PHP de XAMPP o añade `/opt/lampp/bin` al PATH. Comprueba que el PHP usado por Composer tenga habilitadas las extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `xml` y `tokenizer`.

### 4. Crear la base de datos local

Entra a `http://localhost/phpmyadmin`, inicia sesión y crea una base de datos llamada:

`rsu_cni_local`

En el archivo `.env` configura la conexión de XAMPP. En una instalación XAMPP nueva, el usuario suele ser `root` y la contraseña suele estar vacía; si configuraste una contraseña, coloca la tuya solo en tu archivo local:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/rsu-cni

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rsu_cni_local
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

**Base vacía:** revisa las migraciones disponibles y luego ejecuta:

```bash
php artisan migrate
```

**Si vas a importar una base SQL existente:** impórtala en `rsu_cni_local` y no ejecutes `migrate:fresh`. Antes de ejecutar `migrate`, revisa el historial de migraciones con `php artisan migrate:status`; no mezcles una importación SQL con migraciones sin verificar su correspondencia. Haz siempre una copia de seguridad antes de modificar una base importada.

### 5. Configurar Apache para abrir localhost/rsu-cni

Por seguridad, Apache debe publicar **solo la carpeta `public`** de Laravel, nunca la raíz del proyecto. Crea el archivo de configuración de alias de XAMPP:

`/opt/lampp/etc/extra/httpd-rsu-cni.conf`

Con este contenido (ajusta la ruta si guardaste el proyecto en otro lugar):

```apache
Alias /rsu-cni "/opt/lampp/htdocs/rsu-simulador-cni/public"

<Directory "/opt/lampp/htdocs/rsu-simulador-cni/public">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Luego abre `/opt/lampp/etc/httpd.conf` y agrega al final:

```apache
Include etc/extra/httpd-rsu-cni.conf
```

Verifica que `mod_rewrite` esté habilitado (la línea `LoadModule rewrite_module modules/mod_rewrite.so` no debe tener `#) y que `AllowOverride All` esté permitido en el bloque anterior. Reinicia Apache:

```bash
sudo /opt/lampp/lampp restartapache
```

Abre:

**http://localhost/rsu-cni**

Si Apache no inicia, revisa `/opt/lampp/logs/error_log` y corrige la ruta del alias o la línea `Include`.

### 6. Comprobación rápida

```bash
php artisan optimize:clear
php artisan about
php artisan route:list
```

Inicia sesión con una cuenta que exista en la base de datos que importaste o preparaste. No se crean usuarios reales automáticamente.

## Funciones que requieren configuración adicional

- **Davis (asistente docente):** requiere una clave válida en `GEMINI_API_KEY` dentro de `.env`. La clave no se incluye en Git.
- **Asistente de estudiante/administrador:** es un servicio Python separado; consulta la documentación de `agente/`. No es necesario para abrir el portal Laravel.
- Los flags `CNI_ALUMNO_AREAS_ENABLED` y `CNI_ADMIN_ACADEMICO_ENABLED` permanecen desactivados por defecto. Actívalos solo después de verificar que la base local tiene el esquema y los datos requeridos.
- Los seeders de demostración son opcionales. No los ejecutes sobre datos reales.

## Pruebas

```bash
composer validate --strict
php artisan test
```

Las pruebas usan SQLite en memoria mediante `phpunit.xml`; no deberían modificar tu MariaDB local.

## Despliegue en Oracle

La rama `OFICIAL#1` y sus procedimientos de Oracle no se modifican con esta rama. Consulta `docs/despliegue-oracle.md` para el despliegue del servidor.
