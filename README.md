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

### 3. Colocar el respaldo descargado de Oracle

Copia el respaldo completo de la base de datos (archivo `.sql` o `.sql.gz`) en:

```text
database/backups/
```

Puedes crear esa carpeta si no existe. No uses `base-de-datos.sql`: es un esquema inicial antiguo incluido como referencia, no el respaldo actual de Oracle. El respaldo debe contener tanto la estructura como los datos (tablas, registros y, si corresponde, la tabla `migrations`).

Si guardaste el archivo en otra ubicación, puedes pasar su ruta al instalador (ver abajo).

### 4. Ejecutar un solo instalador

Desde la carpeta del proyecto:

```bash
bash scripts/preparar-xampp-antix.sh
```

El instalador:
- instala dependencias de Composer y npm;
- crea `.env` solo si no existe y genera `APP_KEY` si está vacía;
- detecta un único respaldo `.sql` o `.sql.gz` en `database/backups/`, `backups/` o la raíz del proyecto;
- crea la base local, importa el respaldo y ajusta `DB_DATABASE` si el dump declara otro nombre de esquema;
- compila CSS/JavaScript y prepara `public/storage`;
- configura Apache para publicar únicamente `public/` en `/rsu-cni`, valida la configuración y reinicia Apache.

Si el respaldo está en otra ruta:

```bash
bash scripts/preparar-xampp-antix.sh /ruta/al/respaldo-oracle.sql
```

No sobrescribe un `.env` existente. Revisa en él `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD`; en una instalación XAMPP nueva suele ser `root` sin contraseña. Si XAMPP está en otra carpeta, puedes indicar ambas opciones:

```bash
XAMPP_DIR=/ruta/xampp bash scripts/preparar-xampp-antix.sh /ruta/respaldo.sql
```

**Protección de datos:** el importador se detiene si la base de destino ya contiene tablas. No elimina bases, no ejecuta `migrate:fresh` ni sobrescribe una importación previa. Si encuentra varios respaldos, te pide indicar la ruta exacta. Si no encuentra ninguno, termina la preparación web y te indica dónde copiar el dump.

### 5. Abrir la aplicación

**http://localhost/rsu-cni**

Inicia sesión con una cuenta que exista en el respaldo de Oracle. El instalador no crea usuarios ni cambia contraseñas.

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
