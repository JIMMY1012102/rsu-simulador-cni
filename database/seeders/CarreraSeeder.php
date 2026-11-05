<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo inicial ilustrativo y ampliable desde administración.
        $carreras = [
            'Medicina Humana' => 'A',
            'Matemática' => 'B',
            'Ingeniería de Sistemas' => 'C',
            'Ingeniería Industrial' => 'C',
            'Administración' => 'D',
            'Contabilidad' => 'D',
            'Derecho' => 'E',
        ];

        foreach ($carreras as $nombre => $codigoArea) {
            $areaId = DB::table('areas_postulacion')->where('codigo', $codigoArea)
                ->where('activo', true)->value('id');
            if (! $areaId) {
                throw new RuntimeException("Falta el área {$codigoArea}; ejecutar primero AreaPostulacionSeeder.");
            }
            $existente = DB::table('carreras')->where('nombre', $nombre)->first();
            if ($existente && (int) $existente->area_postulacion_id !== (int) $areaId) {
                throw new RuntimeException(
                    "La carrera {$nombre} ya pertenece a otra área. Requiere migración controlada, no actualización automática."
                );
            }
            DB::table('carreras')->updateOrInsert(
                ['nombre' => $nombre],
                ['area_postulacion_id' => $areaId, 'activo' => true,
                 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
