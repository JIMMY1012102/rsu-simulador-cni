<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ExamenDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrientacionAlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AreaPostulacionSeeder::class);
        $this->seed(CarreraSeeder::class);
    }

    private function alumno(string $nombre, ?int $carreraId = null): Usuario
    {
        return Usuario::create([
            'nombres' => $nombre, 'apellidos' => 'CNI', 'usuario' => $nombre,
            'password' => 'ClaveCNI2026!', 'rol' => 'estudiante', 'activo' => true,
            'carrera_id' => $carreraId,
        ]);
    }

    private function area(string $codigo): int
    {
        return DB::table('areas_postulacion')->where('codigo', $codigo)->value('id');
    }

    private function carrera(string $nombre): int
    {
        return DB::table('carreras')->where('nombre', $nombre)->value('id');
    }

    public function test_primer_login_ofrece_cinco_areas(): void
    {
        $this->assertSame(5, DB::table('areas_postulacion')->count());
        $this->alumno('primero');
        $this->post('/login', ['usuario' => 'primero', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('alumno.elegir-area'));
        $this->get(route('alumno.elegir-area'))->assertOk()
            ->assertSee('Ingenierías')->assertSee('Ciencias Básicas')
            ->assertSee('Humanidades y Ciencias Jurídicas y Sociales');
        $this->get(route('alumno.panel'))->assertRedirect(route('alumno.elegir-area'));
        $this->get('/panel')->assertRedirect(route('alumno.panel'));
    }

    public function test_seleccion_de_area_abre_panel_y_guarda_carrera_del_mismo_grupo(): void
    {
        $alumno = $this->alumno('nuevo');
        $this->actingAs($alumno, 'web');
        $this->post(route('alumno.area.guardar'), ['area_id' => $this->area('C')])
            ->assertRedirect(route('alumno.panel'));
        $this->get(route('alumno.panel'))->assertOk()
            ->assertSee('Ingenierías')->assertSee('Ingeniería de Sistemas')
            ->assertDontSee('Medicina Humana');
        $this->post(route('alumno.carrera.guardar'), ['carrera_id' => $this->carrera('Ingeniería de Sistemas')])
            ->assertRedirect(route('alumno.panel'));
        $this->assertDatabaseHas('usuarios', ['id' => $alumno->id,
            'carrera_id' => $this->carrera('Ingeniería de Sistemas')]);
        $this->get(route('alumno.panel'))->assertOk()
            ->assertSee('Ingeniería de Sistemas');
        $this->get(route('alumno.elegir-area'))->assertRedirect(route('alumno.panel'));
    }

    public function test_carrera_ajena_y_area_inactiva_son_rechazadas(): void
    {
        $this->actingAs($this->alumno('valida'), 'web');
        $this->post(route('alumno.area.guardar'), ['area_id' => $this->area('E')]);
        $this->post(route('alumno.carrera.guardar'), ['carrera_id' => $this->carrera('Ingeniería de Sistemas')])
            ->assertSessionHasErrors('carrera_id');
        $this->assertDatabaseHas('usuarios', ['usuario' => 'valida', 'carrera_id' => null]);

        DB::table('areas_postulacion')->where('codigo', 'A')->update(['activo' => false]);
        $this->post(route('alumno.area.guardar'), ['area_id' => $this->area('A')])
            ->assertSessionHasErrors('area_id');
        $this->post(route('alumno.area.guardar'), ['area_id' => 99999])
            ->assertSessionHasErrors('area_id');
    }

    public function test_area_y_carrera_persisten_entre_sesiones_y_no_pueden_reemplazarse(): void
    {
        $this->alumno('persistente', $this->carrera('Derecho'));
        $this->post('/login', ['usuario' => 'persistente', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('alumno.panel'));
        $this->get(route('alumno.panel'))->assertOk()
            ->assertSee('Humanidades y Ciencias Jurídicas y Sociales')->assertSee('Derecho');
        $this->post(route('alumno.area.guardar'), ['area_id' => $this->area('C')])->assertForbidden();
        $this->post(route('alumno.carrera.guardar'), ['carrera_id' => $this->carrera('Ingeniería de Sistemas')])
            ->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['usuario' => 'persistente', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('alumno.panel'));
    }

    public function test_solo_muestra_examenes_de_su_area_y_backend_rechaza_otras_areas(): void
    {
        $this->seed(ExamenDemoSeeder::class);
        $simulacro = DB::table('simulacros')->where('nombre', ExamenDemoSeeder::SIMULACRO)->value('id');
        DB::table('area_postulacion_simulacro')->insert([
            'area_postulacion_id' => $this->area('C'),
            'simulacro_id' => $simulacro, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($this->alumno('ingeniera', $this->carrera('Ingeniería de Sistemas')), 'web')
            ->get(route('alumno.panel'))->assertSee(ExamenDemoSeeder::SIMULACRO);
        $this->actingAs($this->alumno('abogada', $this->carrera('Derecho')), 'web')
            ->get(route('alumno.panel'))->assertDontSee(ExamenDemoSeeder::SIMULACRO);
        $this->post(route('examen.iniciar', $simulacro))->assertSessionHasErrors('carrera_id');
    }

    public function test_un_alumno_no_ve_resultados_ajenos(): void
    {
        $dueno = $this->alumno('dueno', $this->carrera('Derecho'));
        $otro = $this->alumno('otro', $this->carrera('Ingeniería de Sistemas'));
        $id = DB::table('intentos')->insertGetId([
            'usuario_id' => $dueno->id, 'tipo' => 'practica',
            'fecha_inicio' => now(), 'estado' => 'en_curso', 'total_preguntas' => 0,
        ]);
        $this->actingAs($otro, 'web')->get(route('alumno.resultados', $id))->assertNotFound();
        $docente = Usuario::create([
            'nombres' => 'Docente', 'apellidos' => 'CNI', 'usuario' => 'docente_x',
            'password' => 'ClaveCNI2026!', 'rol' => 'docente', 'activo' => true,
        ]);
        $this->actingAs($docente, 'web')->get(route('alumno.panel'))->assertForbidden();
    }

    public function test_url_del_examen_no_permite_saltarse_la_eleccion_de_carrera(): void
    {
        $this->seed(ExamenDemoSeeder::class);
        $alumno = $this->alumno('sin_carrera');
        $simulacroId = DB::table('simulacros')->where('nombre', ExamenDemoSeeder::SIMULACRO)->value('id');
        // API histórica admite pruebas genéricas, pero su interfaz web exige la elección previa.
        $intento = app(\App\Services\IntentoService::class)
            ->iniciar($alumno->id, ['tipo' => 'simulacro', 'simulacro_id' => $simulacroId]);
        $this->actingAs($alumno, 'web')->get(route('examen', $intento->id))
            ->assertRedirect(route('alumno.elegir-area'));
        $this->post(route('examen.iniciar', $simulacroId))->assertForbidden();
    }

    public function test_seeder_no_reasigna_codigos_legados_sin_migracion(): void
    {
        DB::table('areas_postulacion')->where('codigo', 'B')->update(['nombre' => 'Ingenierías (catálogo legado)']);
        $this->expectException(\RuntimeException::class);
        $this->seed(AreaPostulacionSeeder::class);
    }
}
