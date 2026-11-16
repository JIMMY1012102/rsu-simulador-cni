<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Registra cinco estudiantes persistentes en la tabla usuarios de Laravel.
 *
 * Uso: php artisan db:seed --class=EstudiantesCniSeeder
 * Contraseña inicial de cada cuenta: exactamente su nombre de usuario.
 * Uso en base de desarrollo aislada solamente; contraseñas públicas no aptas para producción.
 */
class EstudiantesCniSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('La carga inicial con contraseñas previsibles solo se permite en APP_ENV=local.');
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('La carga inicial requiere MariaDB/MySQL local.');
        }

        $host = config('database.connections.'.config('database.default').'.host');
        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('No se permite insertar estas cuentas en una base de datos remota.');
        }

        foreach (['usuarios', 'secciones', 'areas_postulacion'] as $tabla) {
            if (! Schema::hasTable($tabla)) {
                throw new RuntimeException("Falta la tabla {$tabla}. Prepara las migraciones en una base de desarrollo aislada.");
            }
        }

        if (! Schema::hasColumn('usuarios', 'area_postulacion_id')) {
            throw new RuntimeException('Falta usuarios.area_postulacion_id. Aplica las migraciones en tu base de desarrollo.');
        }

        $alumnos = [
            ['nombres' => 'Jimmy', 'apellidos' => 'Malasquez', 'usuario' => 'Jimmy_Malasquez'],
            ['nombres' => 'Nia', 'apellidos' => 'Alvarez', 'usuario' => 'Nia_Alvarez'],
            ['nombres' => 'Dafne', 'apellidos' => 'Chamorro', 'usuario' => 'Dafne_Chamorro'],
            ['nombres' => 'Diego', 'apellidos' => 'Camayo', 'usuario' => 'Diego_Camayo'],
            ['nombres' => 'Sebastian', 'apellidos' => 'Rojas', 'usuario' => 'Sebastian_Rojas'],
        ];

        DB::transaction(function () use ($alumnos) {
            // Garantiza las cinco áreas sin cambiar códigos de catálogos antiguos incompatibles.
            $this->call(AreaPostulacionSeeder::class);

            $seccion = DB::table('secciones')
                ->where('grado', '5to')->where('nombre', 'A')->first();

            if ($seccion && ! $seccion->activo) {
                throw new RuntimeException('La sección 5to A está inactiva. No se modificará automáticamente.');
            }

            $seccionId = $seccion?->id ?? DB::table('secciones')->insertGetId([
                'grado' => '5to',
                'nombre' => 'A',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($alumnos as $alumno) {
                $usuario = $alumno['usuario'];
                $existente = DB::table('usuarios')->where('usuario', $usuario)->first();

                if ($existente) {
                    // No sobrescribir cuentas reales ni elecciones previas.
                    if ($existente->rol !== 'estudiante'
                        || ! Hash::check($usuario, $existente->password)) {
                        throw new RuntimeException(
                            "La cuenta {$usuario} ya existe con otros datos o contraseña. No se alteró la cuenta existente."
                        );
                    }
                    $this->command?->info("Ya existe: {$usuario}");
                    continue;
                }

                DB::table('usuarios')->insert([
                    'nombres' => $alumno['nombres'],
                    'apellidos' => $alumno['apellidos'],
                    'usuario' => $usuario,
                    'password' => Hash::make($usuario),
                    'rol' => 'estudiante',
                    'seccion_id' => $seccionId,
                    'carrera_id' => null,
                    'area_postulacion_id' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command?->info("Creado: {$usuario}");
            }
        });

        $this->command?->warn('Estos cinco registros son usuarios persistentes de rol estudiante. Cambiar las contraseñas iniciales antes de publicar el sistema.');
    }
}
