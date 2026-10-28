<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\CarreraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Una sola ruta y una sola plantilla Blade, con cinco identidades visuales.
 */
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

    private function estudiante(string $codigo, bool $conArea = true): Usuario
    {
        return Usuario::create([
            'nombres' => 'Alumno',
            'apellidos' => 'Area '.$codigo,
            'usuario' => uniqid('alumno_'.$codigo.'_'),
            'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante',
            'activo' => true,
            'area_postulacion_id' => $conArea
                ? DB::table('areas_postulacion')->where('codigo', $codigo)->value('id')
                : null,
        ]);
    }

    public function test_cinco_areas_usan_misma_ruta_y_plantilla_pero_identidad_diferente(): void
    {
        $temas = config('panel_alumno.areas');
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_keys($temas));

        foreach ($temas as $codigo => $tema) {
            $this->actingAs($this->estudiante($codigo), 'web');
            $this->get(route('alumno.panel'))
                ->assertOk()
                ->assertSee('panel-alumno.css')
                ->assertSee('data-area="'.$codigo.'"', false)
                ->assertSee($tema['descripcion'])
                ->assertSee($tema['lema'])
                ->assertSee('Iniciar Simulador CNI')
                ->assertSee('Mascota del área de')
                ->assertSee('href="#perfil"', false)
                ->assertSee('href="#intentos"', false)
                ->assertSee('href="#historial"', false)
                ->assertSee('aria-controls="cni-alumno-sidebar"', false)
                ->assertSee('data-sidebar-toggle', false);
        }
    }

    public function test_eleccion_persiste_y_llega_a_la_misma_pagina_para_cada_area(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $codigo) {
            $usuario = $this->estudiante($codigo, false);
            $idArea = DB::table('areas_postulacion')->where('codigo', $codigo)->value('id');

            $this->actingAs($usuario, 'web')
                ->post(route('alumno.area.guardar'), ['area_id' => $idArea])
                ->assertRedirect(route('alumno.panel'));

            $this->assertDatabaseHas('usuarios', [
                'id' => $usuario->id,
                'area_postulacion_id' => $idArea,
                'carrera_id' => null,
            ]);

            $this->get(route('alumno.panel'))->assertOk()
                ->assertSee('data-area="'.$codigo.'"', false);
        }
    }

    public function test_una_cuenta_sin_area_ve_el_selector_en_vez_del_panel(): void
    {
        $this->actingAs($this->estudiante('A', false), 'web');
        $this->get(route('alumno.panel'))
            ->assertRedirect(route('alumno.elegir-area'));
    }

    public function test_las_urls_anteriores_de_diego_y_dafne_redirigen_al_panel_unificado(): void
    {
        $this->actingAs($this->estudiante('B'), 'web');
        $this->get(route('alumno.ciencias-basicas'))->assertRedirect(route('alumno.panel'));

        $this->actingAs($this->estudiante('E'), 'web');
        $this->get(route('alumno.inicio'))->assertRedirect(route('alumno.panel'));
    }

    public function test_los_temas_de_otras_areas_no_se_abren_mediante_url_directa(): void
    {
        $this->actingAs($this->estudiante('E'), 'web');
        $this->get(route('alumno.tema', ['tema' => 9999]))->assertNotFound();
    }

    public function test_menu_lateral_tiene_exactamente_tres_opciones_y_un_boton_para_contraerlo(): void
    {
        $this->actingAs($this->estudiante('C'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

        $this->assertSame(1, preg_match(
            '/<nav class="cni-alumno-menu"[^>]*>(.*?)<\\/nav>/s',
            $html,
            $navegacion
        ));
        $this->assertSame(3, preg_match_all('/<a\\b/', $navegacion[1]));
        $this->assertStringContainsString('Mi perfil', $navegacion[1]);
        $this->assertStringContainsString('Mis intentos', $navegacion[1]);
        $this->assertStringContainsString('Historial', $navegacion[1]);
        $this->assertStringContainsString('aria-expanded="true"', $html);
        $this->assertStringContainsString('data-sidebar-backdrop', $html);
        $this->assertStringContainsString('id="intentos"', $html);
        $this->assertStringContainsString('id="historial"', $html);
        $this->assertStringContainsString('id="perfil"', $html);
    }

    public function test_mi_perfil_abre_de_entrada_con_solo_portada_y_dos_tarjetas(): void
    {
        $this->actingAs($this->estudiante('B'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

        $this->assertStringContainsString('id="perfil" data-panel-view="perfil"', $html);
        $this->assertSame(1, preg_match_all('/class="cni-alumno-banner"/', $html));
        $this->assertSame(1, preg_match_all('/class="cni-alumno-progress-card"/', $html));
        $this->assertSame(1, preg_match_all('/class="cni-alumno-streak-card"/', $html));
        $this->assertStringContainsString('href="#perfil" class="is-active"', $html);
        $this->assertStringNotContainsString('cni-alumno-topics', $html);
        $this->assertStringContainsString('id="intentos" data-panel-view="intentos"', $html);
        $this->assertStringContainsString('id="historial" data-panel-view="historial"', $html);
        $this->assertMatchesRegularExpression('/id="intentos"[^>]*hidden>/', $html);
        $this->assertMatchesRegularExpression('/id="historial"[^>]*hidden>/', $html);
        $this->assertStringContainsString('const mostrarSeccion =', $html);
    }

    public function test_la_pantalla_de_intentos_conserva_examenes_y_eleccion_de_carrera(): void
    {
        $this->actingAs($this->estudiante('E'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

        $this->assertStringContainsString('Mis intentos · Iniciar Simulador CNI', $html);
        $this->assertStringContainsString(route('alumno.carrera.guardar'), $html);
        $this->assertStringContainsString('Carrera de interés', $html);
        $this->assertStringContainsString('Mi perfil', $html);
    }

    public function test_docente_no_puede_abrir_el_panel_de_alumno(): void
    {
        $docente = Usuario::create([
            'nombres' => 'Docente', 'apellidos' => 'CNI',
            'usuario' => 'docente_unificado',
            'password' => 'ClaveCNI2026!',
            'rol' => 'docente', 'activo' => true,
        ]);

        $this->actingAs($docente, 'web')
            ->get(route('alumno.panel'))->assertForbidden();
    }
}
