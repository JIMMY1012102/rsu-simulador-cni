<?php

namespace Tests\Feature;

use Database\Seeders\AreaPostulacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_de_catalogo_completo_es_lectura_y_no_modifica_registros(): void
    {
        $this->seed(AreaPostulacionSeeder::class);
        $antes = DB::table('areas_postulacion')->orderBy('codigo')->get()->toArray();
        $this->artisan('cni:auditar-areas')->assertExitCode(0);
        $this->assertEquals($antes, DB::table('areas_postulacion')->orderBy('codigo')->get()->toArray());
    }

    public function test_auditoria_detecta_catalogo_anterior_en_lugar_de_modificarlo(): void
    {
        DB::table('areas_postulacion')->insert([
            'codigo' => 'B', 'nombre' => 'Ingenierías', 'activo' => true,
        ]);
        $this->artisan('cni:auditar-areas')->assertExitCode(1);
        $this->assertDatabaseHas('areas_postulacion', ['codigo' => 'B', 'nombre' => 'Ingenierías']);
    }
}
