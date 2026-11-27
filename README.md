# RSU-CNI — Simulador CNI

Aplicación Laravel 12 + Livewire 3 + MariaDB. Esta rama prepara una instalación local en **antiX Linux + XAMPP** y publica el portal en `http://localhost/rsu-cni`. No modifica `OFICIAL#1`.

## Instalación rápida con el respaldo descargado de Oracle

### 1. Descarga y coloca el respaldo

Descarga el proyecto ZIP de esta rama y descomprímelo en:

```bash
/opt/lampp/htdocs/rsu-simulador-cni
```

Copia el respaldo SQL descargado de Oracle (por ejemplo `respaldo-oracle.sql` o `respaldo-oracle.sql.gz`) a una de estas ubicaciones para que el instalador lo detecte:
- `database/backups/` dentro del proyecto;
- carpeta `Descargas` o `Downloads` del usuario;
- raíz del proyecto con el nombre `respaldo-oracle.sql`.

No subas el respaldo a GitHub: puede contener datos personales. Si tienes varios SQL, indica cuál quieres importar como argumento.

### 2. Ejecuta el instalador

```bash
cd /opt/lampp/htdocs/rsu-simulador-cni
sudo /opt/lampp/lampp start
bash scripts/preparar-xampp-antix.sh
```

Si el SQL está en otra ruta:

```bash
bash scripts/preparar-xampp-antix.sh "$HOME/Descargas/mi-respaldo-oracle.sql"
```

También acepta archivos `.sql.gz`.

El instalador:
- instala Composer y npm dependencies, genera `APP_KEY` si falta y compila frontend;
- lee la conexión MariaDB desde `.env`, crea la base configurada si no existe;
- detecta el respaldo SQL, comprueba que la base destino esté vacía e importa los datos;
- elimina del flujo de importación las líneas iniciales `CREATE DATABASE`/`USE` para dirigir el dump a la base local configurada;
- ejecuta `php artisan migrate --force` para aplicar migraciones pendientes;
- configura Apache para exponer únicamente `public/` en `/rsu-cni` y reinicia Apache.

### 3. Configuración de base de datos

El `.env.example` usa estos valores por defecto para XAMPP:

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

Si tu MariaDB local tiene otra contraseña, cambia `.env` antes de ejecutar el instalador.

**Protección de datos:** si `rsu_cni_local` ya contiene tablas, el instalador se detiene y no importa encima. Cambia `DB_DATABASE` a un nombre nuevo/vacío o respalda y prepara la base por separado. No se ejecuta `migrate:fresh`, ni se borran tablas.

### 4. Abre el portal

[http://localhost/rsu-cni](http://localhost/rsu-cni)

Inicia sesión con una cuenta que exista en el respaldo Oracle. El instalador no crea usuarios ficticios.

## Requisitos

- XAMPP para Linux en `/opt/lampp` (o define `XAMPP_DIR`).
- PHP 8.2+ y extensiones `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`.
- Composer 2, Node.js y npm.

## Servicios opcionales

- Davis requiere `GEMINI_API_KEY` en `.env`; no se incluye ninguna clave.
- El agente Python/FastAPI es independiente y no es necesario para abrir el portal Laravel.
- Los módulos experimentales permanecen desactivados por defecto; habilítalos solo si la base importada contiene sus tablas y datos.

## Problemas frecuentes

- Apache: revisa `/opt/lampp/logs/error_log`.
- MariaDB: confirma que XAMPP esté iniciado y que `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` coincidan con tu instalación.
- Si el importador indica que la base no está vacía, no borres las tablas a ciegas: usa una base local nueva.
- Para un respaldo grande, asegúrate de tener espacio libre y no cierres la terminal durante la importación.

