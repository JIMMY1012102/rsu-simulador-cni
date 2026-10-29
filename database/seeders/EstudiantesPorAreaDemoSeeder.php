<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Un estudiante de prueba por área de postulación (A–E), con el área ya elegida,
 * para revisar el simulacro de cada área. Solo para bases locales de prueba.
 *   php artisan db:seed --class=EstudiantesPorAreaDemoSeeder
 * Usuarios: estudiante_a_demo … estudiante_e_demo (misma contraseña que las cuentas demo).
 */
class EstudiantesPorAreaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();
        $seccion = DB::table('secciones')->orderBy('id')->value('id');

        foreach (DB::table('areas_postulacion')->orderBy('codigo')->get(['id', 'codigo', 'nombre']) as $area) {
            $usuario = 'estudiante_'.strtolower($area->codigo).'_demo';
            DB::table('usuarios')->updateOrInsert(['usuario' => $usuario], [
                'nombres' => 'Estudiante '.$area->codigo,
                'apellidos' => 'Demo',
                'password' => Hash::make('DemoCNI2026!'),
                'rol' => 'estudiante',
                'seccion_id' => $seccion,
                'area_postulacion_id' => $area->id,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
            $this->command?->info("{$usuario} → Área {$area->codigo}: {$area->nombre}");
        }
    }
}
