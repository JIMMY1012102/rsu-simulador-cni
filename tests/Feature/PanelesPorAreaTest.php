<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\CarreraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PanelesPorAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cni.alumno_areas_enabled' => true]);
        $this->seed(AreaPostulacionSeeder::class);
        $this->seed(CarreraSeeder::class);
    }

    private function estudiante(string $codigo): Usuario
    {
        return Usuario::create([
            'nombres' => 'Alumno',
            'apellidos' => 'Area '.$codigo,
            'usuario' => 'alumno_'.$codigo,
            'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante',
            'activo' => true,
            'area_postulacion_id' => DB::table('areas_postulacion')
                ->where('codigo', $codigo)->value('id'),
        ]);
    }

    public function test_redirige_al_panel_de_diego_para_ciencias_basicas(): void
    {
        $this->actingAs($this->estudiante('B'), 'web');
        $this->get(route('alumno.panel'))->assertRedirect(route('alumno.ciencias-basicas'));
        $this->get(route('alumno.ciencias-basicas'))->assertOk()->assertSee('Ciencias Básicas');
    }

    public function test_redirige_al_portal_de_dafne_para_humanidades(): void
    {
        $this->actingAs($this->estudiante('E'), 'web');
        $this->get(route('alumno.panel'))->assertRedirect(route('alumno.inicio'));
        $this->get(route('alumno.inicio'))->assertOk()->assertSee('Hola, Alumno');
    }

    public function test_panel_de_salud_se_presenta_sin_cambiar_el_panel_general(): void
    {
        $this->actingAs($this->estudiante('A'), 'web');
        $this->get(route('alumno.panel'))->assertOk()
            ->assertSee('dashboard-salud.css')
            ->assertSee('Ciencias de la Salud');
    }

    public function test_ingenierias_y_economicas_conservan_el_panel_general(): void
    {
        foreach (['C', 'D'] as $codigo) {
            $this->actingAs($this->estudiante($codigo), 'web');
            $this->get(route('alumno.panel'))->assertOk()
                ->assertSee('orientacion.css');
        }
    }

    public function test_las_rutas_de_otras_areas_no_se_pueden_abrir_con_el_usuario_equivocado(): void
    {
        $this->actingAs($this->estudiante('A'), 'web');
        $this->get(route('alumno.ciencias-basicas'))->assertRedirect(route('alumno.panel'));
        $this->get(route('alumno.inicio'))->assertRedirect(route('alumno.panel'));

        $this->actingAs($this->estudiante('E'), 'web');
        $this->get(route('alumno.ciencias-basicas'))->assertRedirect(route('alumno.panel'));
        $this->get(route('alumno.tema', ['tema' => 999]))->assertNotFound();
    }
}
