<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AreaPostulacionSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo de cinco áreas del modelo aprobado (base-de-datos.md, sección 4.1).
        $areas = [
            'A' => 'Ciencias de la Salud',
            'B' => 'Ciencias Básicas',
            'C' => 'Ingenierías',
            'D' => 'Ciencias Económicas y de la Gestión',
            'E' => 'Humanidades y Ciencias Jurídicas y Sociales',
        ];

        // La versión anterior tenía cuatro áreas y códigos incompatibles.
        // No reasignar códigos de una base poblada: cambiaría la carrera de sus alumnos.
        foreach (DB::table('areas_postulacion')->get(['codigo', 'nombre']) as $actual) {
            if (! isset($areas[$actual->codigo]) || $areas[$actual->codigo] !== $actual->nombre) {
                throw new RuntimeException(
                    "Catálogo de postulación incompatible en el código {$actual->codigo}. ".
                    'No ejecutar cambios automáticos sobre datos existentes: planificar migración de códigos y relaciones.'
                );
            }
        }

        foreach ($areas as $codigo => $nombre) {
            DB::table('areas_postulacion')->updateOrInsert(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'activo' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
