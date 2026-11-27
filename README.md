# RSU-CNI — Simulador CNI

Aplicación web académica construida con **Laravel 12, Livewire 3 y MariaDB**.

Esta rama, `local-xampp-antix`, está preparada para instalar una copia de desarrollo en **antiX Linux + XAMPP** y abrirla en `http://localhost/rsu-cni`. La rama `OFICIAL#1` no se modifica.

## Instalación rápida: antiX Linux + XAMPP

### 1. Requisitos

- XAMPP para Linux (por defecto, `/opt/lampp`).
- PHP 8.2+ con extensiones Laravel y `pdo_mysql`.
- Composer 2, Node.js y npm.
- Git (solo si clonas el repositorio).

Inicia XAMPP:

```bash
sudo /opt/lampp/lampp start
```

### 2. Descargar esta rama

```bash
cd /opt/lampp/htdocs
git clone --branch local-xampp-antix https://github.com/JIMMY1012102/rsu-simulador-cni.git rsu-simulador-cni
cd rsu-simulador-cni
```

También puedes descargar el ZIP de la rama desde GitHub y descomprimirlo en `/opt/lampp/htdocs/rsu-simulador-cni`.

### 3. Ejecutar el preparador

Desde la carpeta del proyecto:

```bash
bash scripts/preparar-xampp-antix.sh
```

El script:
- instala dependencias de Composer y npm;
- crea `.env` solo si todavía no existe y genera `APP_KEY` si está vacía;
- compila CSS/JavaScript y prepara `public/storage`;
- configura un alias Apache para publicar solo `public/` en `/rsu-cni`;
- valida Apache y reinicia su servicio.

No sobrescribe un `.env` que ya exista. Si XAMPP está en otra carpeta, puedes indicarla así: `XAMPP_DIR=/ruta/xampp bash scripts/preparar-xampp-antix.sh`.

### 4. Crear y configurar la base local

Abre `http://localhost/phpmyadmin` y crea una base llamada `rsu_cni_local`.

En `.env`, revisa la conexión. En una instalación XAMPP nueva suele usarse `root` sin contraseña; si configuraste una contraseña, escribe la tuya localmente:

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
```

**Base vacía:** revisa las migraciones y ejecuta `php artisan migrate` solo si el esquema de esta copia debe crearse desde cero.

**Base SQL existente:** impórtala en `rsu_cni_local` y revisa `php artisan migrate:status` antes de ejecutar migraciones. No uses `migrate:fresh`; elimina las tablas. Haz una copia de seguridad antes de modificar una base importada.

### 5. Abrir la aplicación

**http://localhost/rsu-cni**

Inicia sesión con una cuenta que exista en la base de datos que preparaste o importaste. No se crean cuentas reales automáticamente.

Si Apache no inicia, revisa `/opt/lampp/logs/error_log`. El script guarda una copia inicial de `httpd.conf` en `httpd.conf.backup-rsu-cni` antes de agregar el Include. Para quitar la integración, elimina la línea `Include etc/extra/httpd-rsu-cni.conf` de `httpd.conf` y el archivo `etc/extra/httpd-rsu-cni.conf`, y reinicia Apache.

## Configuración adicional opcional

- **Davis (asistente docente):** requiere una clave válida en `GEMINI_API_KEY` dentro de `.env`. La clave no se incluye en Git.
- **Asistente de estudiante/administrador:** es un servicio Python separado; consulta la documentación de `agente/`. No es necesario para abrir el portal Laravel.
- `CNI_ALUMNO_AREAS_ENABLED` y `CNI_ADMIN_ACADEMICO_ENABLED` permanecen desactivados por defecto. Actívalos solo después de verificar que la base local tiene el esquema y los datos requeridos.
- Los seeders de demostración son opcionales. No los ejecutes sobre datos reales.

## Pruebas

```bash
composer validate --strict
php artisan test
```

Las pruebas usan SQLite en memoria mediante `phpunit.xml`; no deberían modificar tu MariaDB local.

## Oracle

Esta rama es para desarrollo local y no reemplaza `OFICIAL#1`. Consulta `docs/despliegue-oracle.md` para el despliegue de Oracle.
