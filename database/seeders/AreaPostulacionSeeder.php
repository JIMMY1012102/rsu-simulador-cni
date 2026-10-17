<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaPostulacionSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['codigo' => 'A', 'nombre' => 'Ciencias de la Salud'],
            ['codigo' => 'B', 'nombre' => 'Ingenierías'],
            ['codigo' => 'C', 'nombre' => 'Ciencias Empresariales'],
            ['codigo' => 'D', 'nombre' => 'Humanidades'],
        ];

        foreach ($areas as $area) {
            DB::table('areas_postulacion')->updateOrInsert(
                ['codigo' => $area['codigo']],
                [
                    'nombre' => $area['nombre'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

