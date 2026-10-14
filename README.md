# RSU-CNI — Simulador CNI

Proyecto de Responsabilidad Social Universitaria: simulador de preparación académica para estudiantes del CNI.

**Stack inicial:** Laravel 12 (PHP 8.2+), MariaDB 10.11 (driver PDO MySQL), Vite.

## Estructura del repositorio

- `app/`: controladores, modelos y proveedores.
- `bootstrap/`, `config/`, `routes/`: arranque y configuración Laravel.
- `database/migrations/`: **reservado** para las migraciones de Nia, Dafne y Diego.
- `database/seeders/`: **reservado** para los datos de prueba y catálogos.
- `resources/`, `public/`: vistas y recursos.
- `tests/`: pruebas automatizadas.
- `base-de-datos.sql` y `base-de-datos.md`: diseño SQL y documentación de Sebastián, conservados en la raíz.

## Requisitos locales

PHP >= 8.2 con las extensiones requeridas por Laravel (`pdo_mysql`, `mbstring`, `xml`, etc.); Composer 2; MariaDB activo. Node.js/npm para los recursos web es opcional durante las primeras pruebas.

## Inicio rápido en Linux Mint

Trabaja con la rama aprobada por el equipo (tras fusionar este PR a `develop`):

```bash
git clone -b develop https://github.com/JIMMY1012102/rsu-simulador-cni.git
cd rsu-simulador-cni
composer install
cp .env.example .env
php artisan key:generate
```

Edita `.env` y configura la conexión **local** de cada integrante:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simulador_cni_validacion
DB_USERNAME=cni_dev
DB_PASSWORD=TU_CONTRASEÑA_LOCAL
```

`simulador_cni_validacion` es el nombre usado por el integrador para probar la versión 2 del SQL; otros colaboradores pueden usar su propia base y usuario con permisos limitados. **Nunca subas `.env` ni contraseñas a GitHub.**

Para comprobar el arranque:

```bash
php artisan --version
php artisan route:list
php artisan serve
```

Luego visita http://127.0.0.1:8000. Para comprobar la conexión (sin escribir tablas): `php artisan db:show --database=mysql`.

Para pruebas sin modificar MariaDB: `php artisan test`. Si necesitas frontend: `npm install && npm run build`.

## ⚠️ Importante: SQL existente y migraciones

- Las 14 tablas de `base-de-datos.sql` ya fueron verificadas en MariaDB por separado. **No vuelvas a ejecutar ese archivo sobre una base con datos sin revisar antes su instrucción `USE simulador_cni`.**
- **No ejecutes `php artisan migrate` ni `migrate:fresh` sobre las bases con el esquema SQL importado** hasta que las migraciones de Nia, Dafne y Diego estén revisadas y se acuerde cómo sincronizar el historial de migraciones. Una migración puede intentar recrear tablas existentes; `migrate:fresh` puede eliminarlas.
- Se omitieron las migraciones predeterminadas de Laravel para `users`, `cache` y `jobs` a fin de no introducir tablas fuera del diseño CNI.
- La autenticación queda **preparada**, no implementada: `App\Models\Usuario` apunta a la tabla SQL `usuarios`. Registro, inicio de sesión y recuperación de contraseña quedan para la fase backend.
- Se usan sesiones en archivos, caché en archivos y cola síncrona por defecto para no requerir tablas auxiliares que todavía no existen.

## Distribución técnica

- **Integración:** estructura Laravel, configuración MariaDB, PRs, pruebas y coordinación.
- **Sebas:** SQL y documentación de las 14 tablas.
- **Nia:** migraciones y seeders de secciones, áreas de postulación, carreras y usuarios.
- **Dafne:** migraciones y pruebas del banco de preguntas.
- **Diego:** migraciones y pruebas de simulacros, intentos y resultados.

Los aportes nuevos deben entregarse en ramas propias y en Pull Requests hacia `develop`; no modificar `main` directamente.

## Estado de esta entrega

Este PR publica **el esqueleto y configuración inicial de Laravel**, no una instalación ejecutada en la laptop del integrador ni las migraciones del equipo. La instalación de dependencias con Composer, generación de `composer.lock`, comprobación de la conexión local a MariaDB y ejecución real de los tests se completarán en el entorno Linux Mint del integrador.
