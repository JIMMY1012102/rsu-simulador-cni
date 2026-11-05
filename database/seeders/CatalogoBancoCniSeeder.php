<?php

namespace Database\Seeders;

use App\Support\PerfilExamenCni;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoBancoCniSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $ahora = now();
            foreach (PerfilExamenCni::CURSOS as [$curso, $bloque]) {
                // Comparte las mismas claves que el importador; conserva los registros existentes.
                $areaId = DB::table('areas')->where('nombre', $curso)->value('id');
                if ($areaId === null) {
                    $areaId = DB::table('areas')->insertGetId([
                        'nombre' => $curso, 'bloque' => $bloque, 'activo' => true,
                        'created_at' => $ahora, 'updated_at' => $ahora,
                    ]);
                }
                if (! DB::table('temas')->where('area_id', $areaId)->where('nombre', $curso)->exists()) {
                    DB::table('temas')->insert([
                        'area_id' => $areaId, 'nombre' => $curso, 'activo' => true,
                        'created_at' => $ahora, 'updated_at' => $ahora,
                    ]);
                }
            }
        });
    }
}
