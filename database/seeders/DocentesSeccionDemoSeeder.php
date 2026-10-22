<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Datos ficticios para comprobar la separación por secciones en desarrollo. */
class DocentesSeccionDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $seccionId = DB::table('secciones')
                ->where('grado', '5to')
                ->where('nombre', $letra)
                ->where('activo', true)
                ->value('id');

            if ($seccionId === null) {
                throw new \RuntimeException("Primero crea la sección 5to {$letra}.");
            }

            DB::table('usuarios')->updateOrInsert(
                ['usuario' => 'docente_'.strtolower($letra).'_demo'],
                [
                    'nombres' => 'Docente',
                    'apellidos' => 'Demo '.$letra,
                    'password' => Hash::make('DemoCNI2026!'),
                    'rol' => 'docente',
                    'seccion_id' => $seccionId,
                    'carrera_id' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
