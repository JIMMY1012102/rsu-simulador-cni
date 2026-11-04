# Aislamiento docente por secciones (KISS)

Una aplicación Laravel 12, guard web, un panel Blade + Livewire para A/B/C/D y las tablas existentes.

## Flujo

1. En secciones existen los grados 5.º A, B, C y D.
2. En usuarios, seccion_id vincula tanto al estudiante como al docente responsable.
3. El login del docente lleva a /docente y /docente/calificaciones carga exclusivamente su salón.
4. El listado incluye alumnos sin simulacro y, si corresponde, el último intento calificado.
5. Las rutas JSON /api/resultados, /api/resultados/{intentoId} y /api/resultados/calificar verifican pertenencia por sección, rol y estado activo.
6. El administrador conserva los resultados globales; el estudiante solo los suyos.

## Demostración local

DatabaseSeeder crea cuentas de ejemplo A/B/C/D solo en APP_ENV local/testing, nunca en producción.
El seeder DocentesSeccionDemoSeeder rechaza el uso directo fuera de esos entornos.
Las credenciales conocidas son solo para pruebas y deben reemplazarse antes de usar cuentas reales.
Una cuenta sin sección asignada o con sección inactiva no obtiene datos del salón.

## Comprobación

php artisan test --filter=SeccionesDocenteTest
php artisan test
php artisan route:list

Revisar el resultado en MariaDB local con una copia de desarrollo y cuatro docentes.
No ejecutar migrate:fresh en la base compartida: elimina tablas y registros.
Una auditoría del repositorio no acredita los datos reales de la base en ejecución.
