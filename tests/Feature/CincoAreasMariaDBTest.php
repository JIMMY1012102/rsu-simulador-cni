<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\IntentoService;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\SimulacrosCincoAreasDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CincoAreasMariaDBTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AreaPostulacionSeeder::class);
        $this->seed(SimulacrosCincoAreasDemoSeeder::class);
    }

    public function test_los_cinco_simulacros_tienen_cuotas_distintas_y_una_sola_area_asignada(): void
    {
        $this->assertSame(5, DB::table('simulacros')
            ->where('nombre', 'like', SimulacrosCincoAreasDemoSeeder::PREFIJO.'%')->count());

        $vistas = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $codigo) {
            $simulacro = DB::table('simulacros')
                ->where('nombre', SimulacrosCincoAreasDemoSeeder::PREFIJO.$codigo)->first();
            $this->assertNotNull($simulacro);
            $this->assertSame(20, (int) $simulacro->total_preguntas);
            $this->assertSame(1, DB::table('area_postulacion_simulacro')
                ->where('simulacro_id', $simulacro->id)->count());
            $this->assertSame($codigo, DB::table('area_postulacion_simulacro as aps')
                ->join('areas_postulacion as ap', 'ap.id', '=', 'aps.area_postulacion_id')
                ->where('aps.simulacro_id', $simulacro->id)->value('ap.codigo'));

            $cantidades = DB::table('simulacro_area as sa')
                ->join('areas as a', 'a.id', '=', 'sa.area_id')
                ->where('sa.simulacro_id', $simulacro->id)
                ->orderBy('a.nombre')->pluck('sa.cantidad_preguntas', 'a.nombre')->all();
            $this->assertSame(20, array_sum($cantidades));
            $vistas[$codigo] = $cantidades;
        }
        $this->assertGreaterThan($vistas['E']['Matemática'],
            $vistas['C']['Matemática']);
        $this->assertGreaterThan($vistas['C']['Razonamiento Verbal'],
            $vistas['E']['Razonamiento Verbal']);
        $this->assertNotEquals($vistas['C'], $vistas['E']);
    }

    public function test_login_area_guardada_examen_y_resultados_del_alumno_con_datos_reales(): void
    {
        $alumno = Usuario::create([
            'nombres' => 'Estudiante', 'apellidos' => 'Integración',
            'usuario' => 'alumno_test', 'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante', 'activo' => true,
        ]);

        $this->post('/login', ['usuario' => 'alumno_test', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('alumno.elegir-area'));

        $ingenieria = DB::table('areas_postulacion')->where('codigo', 'C')->value('id');
        $this->post(route('alumno.area.guardar'), ['area_id' => $ingenieria])
            ->assertRedirect(route('alumno.panel'));

        $nombre = SimulacrosCincoAreasDemoSeeder::PREFIJO.'C';
        $this->get(route('alumno.panel'))->assertOk()->assertSee($nombre)
            ->assertDontSee(SimulacrosCincoAreasDemoSeeder::PREFIJO.'E');

        $simulacro = DB::table('simulacros')->where('nombre', $nombre)->value('id');
        $this->post(route('examen.iniciar', $simulacro))->assertRedirect();
        $intento = DB::table('intentos')->where('usuario_id', $alumno->id)->first();
        $this->assertSame(20, (int) $intento->total_preguntas);
        $this->get(route('examen', $intento->id))->assertOk();

        $pregunta = app(IntentoService::class)->preguntas($intento->id)->first();
        app(IntentoService::class)->guardarRespuesta($intento->id, $alumno->id, [
            'pregunta_id' => $pregunta->id,
            'alternativa_id' => $pregunta->alternativas[0]->id,
        ]);
        app(IntentoService::class)->finalizar($intento->id, $alumno->id);

        $this->get(route('alumno.resultados', $intento->id))
            ->assertOk()->assertSee('Resultados de tu examen')
            ->assertSee('Resultados por asignatura');

        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['usuario' => 'alumno_test', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('alumno.panel'));
        $this->get(route('alumno.panel'))->assertOk()->assertSee($nombre);
    }

    public function test_el_servidor_rechaza_iniciar_examen_de_otra_area(): void
    {
        $alumno = Usuario::create([
            'nombres' => 'Estudiante', 'apellidos' => 'Test',
            'usuario' => 'alumno_derecho', 'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante', 'activo' => true,
            'area_postulacion_id' => DB::table('areas_postulacion')->where('codigo', 'E')->value('id'),
        ]);

        $simulacro = DB::table('simulacros')
            ->where('nombre', SimulacrosCincoAreasDemoSeeder::PREFIJO.'C')->value('id');
        $this->actingAs($alumno, 'web')
            ->post(route('examen.iniciar', $simulacro))->assertSessionHasErrors('carrera_id');
        $this->assertSame(0, DB::table('intentos')->where('usuario_id', $alumno->id)->count());
    }
}
