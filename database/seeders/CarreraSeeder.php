<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            ['area' => 'B', 'nombre' => 'Ingeniería de Sistemas'],
            ['area' => 'B', 'nombre' => 'Ingeniería Industrial'],
            ['area' => 'C', 'nombre' => 'Administración'],
            ['area' => 'C', 'nombre' => 'Contabilidad'],
        ];

        foreach ($carreras as $carrera) {
            $areaId = DB::table('areas_postulacion')
                ->where('codigo', $carrera['area'])
                ->value('id');

            if ($areaId === null) {
                throw new \RuntimeException(
                    "No existe el área {$carrera['area']}"
                );
            }

            DB::table('carreras')->updateOrInsert(
                ['nombre' => $carrera['nombre']],
                [
                    'area_postulacion_id' => $areaId,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

