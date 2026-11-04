<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\ExamenDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integración del acceso del panel oficial al examen y al administrador.
 * No usa contraseñas ni datos reales.
 */
class AccesoPanelOficialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cni.alumno_areas_enabled' => true,
            'cni.administracion_academica_enabled' => true,
        ]);

        $this->seed(AreaPostulacionSeeder::class);
    }

    private function usuario(string $rol, string $codigo = 'B'): Usuario
    {
        return Usuario::create([
            'nombres' => 'Prueba',
            'apellidos' => 'Panel Oficial',
            'usuario' => uniqid($rol.'_'),
            'password' => 'SoloPruebaLocal2026!',
            'rol' => $rol,
            'activo' => true,
            'area_postulacion_id' => $rol === 'estudiante'
                ? DB::table('areas_postulacion')->where('codigo', $codigo)->value('id')
                : null,
        ]);
    }

    private function publicarSimulacroPara(string $codigo): int
    {
        $this->seed(ExamenDemoSeeder::class);
        $simulacro = (int) DB::table('simulacros')
            ->where('nombre', ExamenDemoSeeder::SIMULACRO)->value('id');
        $areaId = (int) DB::table('areas_postulacion')->where('codigo', $codigo)->value('id');

        DB::table('area_postulacion_simulacro')->insert([
            'area_postulacion_id' => $areaId,
            'simulacro_id' => $simulacro,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $simulacro;
    }

    public function test_alumno_inicia_un_simulacro_real_desde_el_boton_de_su_portada(): void
    {
        $alumno = $this->usuario('estudiante', 'B');
        $simulacro = $this->publicarSimulacroPara('B');

        $this->actingAs($alumno, 'web')->get(route('alumno.panel'))
            ->assertOk()
            ->assertSee('Dar examen')
            ->assertSee(route('examen.iniciar', ['simulacro' => $simulacro]), false)
            ->assertSee(ExamenDemoSeeder::SIMULACRO);

        $respuesta = $this->post(route('examen.iniciar', ['simulacro' => $simulacro]));
        $intento = DB::table('intentos')->where('usuario_id', $alumno->id)->first();

        $this->assertNotNull($intento);
        $this->assertSame('en_curso', $intento->estado);
        $this->assertSame($simulacro, (int) $intento->simulacro_id);
        $respuesta->assertRedirect(route('examen', ['intento' => $intento->id]));

        // Un segundo clic no crea otro intento del mismo simulacro.
        $this->post(route('examen.iniciar', ['simulacro' => $simulacro]))
            ->assertRedirect(route('examen', ['intento' => $intento->id]));
        $this->assertSame(1, DB::table('intentos')
            ->where('usuario_id', $alumno->id)->where('simulacro_id', $simulacro)->count());
    }

    public function test_alumno_de_otra_area_no_puede_iniciar_el_simulacro(): void
    {
        $simulacro = $this->publicarSimulacroPara('B');
        $alumno = $this->usuario('estudiante', 'E');

        $this->actingAs($alumno, 'web')->get(route('alumno.panel'))
            ->assertOk()
            ->assertSee('Aún no hay simulacros disponibles para tu área.');

        $this->post(route('examen.iniciar', ['simulacro' => $simulacro]))
            ->assertSessionHasErrors();
        $this->assertSame(0, DB::table('intentos')
            ->where('usuario_id', $alumno->id)->count());
    }

    public function test_panel_superadmin_esta_disponible_solo_para_rol_admin(): void
    {
        $admin = $this->usuario('admin');
        $this->actingAs($admin, 'web')
            ->get(route('admin.inicio'))
            ->assertOk()->assertSee('Panel del administrador')
            ->assertSee('Banco de preguntas');

        $estudiante = $this->usuario('estudiante');
        $this->actingAs($estudiante, 'web')
            ->get(route('admin.inicio'))->assertForbidden();
    }

    public function test_panel_superadmin_respeta_bandera_de_configuracion(): void
    {
        $admin = $this->usuario('admin');
        config(['cni.administracion_academica_enabled' => false]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.inicio'))->assertNotFound();
    }
}
