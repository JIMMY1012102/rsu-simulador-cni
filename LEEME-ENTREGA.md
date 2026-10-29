# Simulador CNI · código entregado

Proyecto Laravel 12 + Livewire 3 + MariaDB del simulador de admisión CNI, tal como se usó en las pruebas:
rama `develop` más el importador del banco de preguntas (rama `feature/banco-preguntas-excel`, commit 8c0e6f4).

No incluye `vendor/`, `.env`, la base de datos, las imágenes ya importadas ni el Excel del banco
(el Excel tiene las respuestas y no debe publicarse).

## Requisitos
- PHP 8.2 con las extensiones zip, xml, mbstring y pdo_mysql (XAMPP sirve).
- Composer.
- MariaDB o MySQL.

## Puesta en marcha
1. Instalar dependencias: `composer install`
2. Crear la configuración: copiar `.env.example` a `.env` y ejecutar `php artisan key:generate`.
3. En `.env`, poner los datos de la base (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) y agregar:
   ```
   CNI_ADMIN_ACADEMICO_ENABLED=true
   CNI_ALUMNO_AREAS_ENABLED=true
   ```
4. Crear las tablas y los usuarios de prueba: `php artisan migrate --seed`
5. Habilitar las imágenes públicas: `php artisan storage:link`
6. (Opcional) Datos de prueba:
   - `php artisan db:seed --class=ExamenDemoSeeder`
   - `php artisan db:seed --class=EstudiantesPorAreaDemoSeeder`
7. (Opcional) Cargar el banco del Excel con un simulacro de 100 preguntas por área:
   `php artisan banco:importar "ruta\Banco_preguntas_completo.xlsx" --simulacros`
8. Iniciar: `php artisan serve` y abrir http://localhost:8000/login

## Cuentas de prueba (solo para una base local)
La contraseña está en `database/seeders/UsuarioSeeder.php`.
- Administrador: `admin_demo` → panel del administrador en `/administracion`
- Docente: `docente_demo`
- Estudiante: `estudiante_demo`; uno por área: `estudiante_a_demo` … `estudiante_e_demo`

## Dónde está el panel del administrador
- Rutas: `routes/web.php` (grupo `/administracion`)
- Acceso solo para admin: `app/Http/Middleware/SoloAdministrador.php`
- Controladores: `app/Http/Controllers/Admin/`
- Vistas: `resources/views/admin/` (estructura en `shell.blade.php`, inicio con el gato en `inicio.blade.php`)
- Estilos: `public/css/admin.css`
- Documentación: `docs/administracion-academica.md`
