<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ExamenDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SimulacrosCarreraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AreaPostulacionSeeder::class);
        $this->seed(CarreraSeeder::class);
        $this->seed(ExamenDemoSeeder::class);
    }

    private function usuario(string $rol, string $usuario, ?string $carrera = null): Usuario
    {
        return Usuario::create([
            'nombres' => 'Estudiante', 'apellidos' => 'Carreras', 'usuario' => $usuario,
            'password' => 'ClaveCNI2026!', 'activo' => true, 'rol' => $rol,
            'carrera_id' => $carrera ? DB::table('carreras')->where('nombre', $carrera)->value('id') : null,
        ]);
    }

    private function publicar(string $nombre, string $area, ?string $carrera, array $cuotas): int
    {
        $areaId = DB::table('areas_postulacion')->where('codigo', $area)->value('id');
        $datos = [
            'nombre' => $nombre, 'area_postulacion_id' => $areaId,
            'carrera_id' => $carrera ? DB::table('carreras')->where('nombre', $carrera)->value('id') : '',
            'tiempo_limite' => 60,
            'fecha_inicio' => now()->subHour()->format('Y-m-d H:i'),
            'fecha_fin' => now()->addWeek()->format('Y-m-d H:i'),
            'distribucion' => $cuotas,
        ];
        $this->post(route('admin.simulacros.store'), $datos)->assertRedirect(route('admin.simulacros.index'));

        return DB::table('simulacros')->where('nombre', $nombre)->value('id');
    }

    public function test_perfiles_distintos_según_carrera_y_bloqueo_de_acceso_cruzado(): void
    {
        $this->actingAs($this->usuario('admin', 'admin_area'), 'web');
        $mat = DB::table('areas')->where('nombre', 'Matemática')->value('id');
        $hum = DB::table('areas')->where('nombre', 'Humanidades')->value('id');
        $c = $this->publicar('Especial Ingeniería', 'C', 'Ingeniería de Sistemas', [$mat => 2]);
        $e = $this->publicar('Especial Derecho', 'E', 'Derecho', [$hum => 2]);
        $this->assertDatabaseHas('simulacros', ['id' => $c, 'carrera_id' => DB::table('carreras')->where('nombre', 'Ingeniería de Sistemas')->value('id')]);
        $this->assertDatabaseHas('simulacros', ['id' => $e, 'carrera_id' => DB::table('carreras')->where('nombre', 'Derecho')->value('id')]);

        $ing = $this->usuario('estudiante', 'ingeniera_cni', 'Ingeniería de Sistemas');
        $this->actingAs($ing, 'web');
        $this->get(route('alumno.panel'))->assertOk()->assertSee('Especial Ingeniería')
            ->assertDontSee('Especial Derecho');
        $this->post(route('examen.iniciar', $e))->assertSessionHasErrors('carrera_id');
        $this->assertSame(0, DB::table('intentos')->where('usuario_id', $ing->id)->count());
        $this->post(route('examen.iniciar', $c))->assertRedirect();
        $intento = DB::table('intentos')->where('usuario_id', $ing->id)->first();
        $this->assertSame(2, (int) $intento->total_preguntas);
        $this->assertSame((int) $ing->carrera_id, (int) $intento->carrera_id);

        $derecho = $this->usuario('estudiante', 'derecho_cni', 'Derecho');
        $this->actingAs($derecho, 'web')->get(route('alumno.panel'))
            ->assertSee('Especial Derecho')->assertDontSee('Especial Ingeniería');
        $this->post(route('examen.iniciar', $c))->assertSessionHasErrors('carrera_id');
    }

    public function test_no_se_puede_configurar_una_carrera_en_otro_grupo_ni_superar_banco(): void
    {
        $this->actingAs($this->usuario('admin', 'admin_validacion'), 'web');
        $area = DB::table('areas_postulacion')->where('codigo', 'E')->value('id');
        $carrera = DB::table('carreras')->where('nombre', 'Ingeniería de Sistemas')->value('id');
        $mat = DB::table('areas')->where('nombre', 'Matemática')->value('id');
        $base = [
            'nombre' => 'Simulacro inválido',
            'area_postulacion_id' => $area, 'carrera_id' => $carrera,
            'fecha_inicio' => now()->subHour()->format('Y-m-d H:i'),
            'fecha_fin' => now()->addWeek()->format('Y-m-d H:i'),
            'tiempo_limite' => 60, 'distribucion' => [$mat => 2],
        ];
        $this->post(route('admin.simulacros.store'), $base)->assertSessionHasErrors('carrera_id');
        $this->post(route('admin.simulacros.store'), array_merge($base, [
            'carrera_id' => '', 'distribucion' => [$mat => 100],
        ]))->assertSessionHasErrors('distribucion');
        $this->assertDatabaseMissing('simulacros', ['nombre' => 'Simulacro inválido']);
    }
}
