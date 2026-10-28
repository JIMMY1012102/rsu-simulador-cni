<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\IntentoService;
use Database\Seeders\ExamenDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamenServiceTest extends TestCase
{
    use RefreshDatabase;

    private IntentoService $servicio;

    private Usuario $alumno;

    private Usuario $otro;

    private int $simulacro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 09:00:00'));
        $this->seed(ExamenDemoSeeder::class);
        foreach (['alumno', 'otro'] as $nombre) {
            $this->$nombre = Usuario::create([
                'nombres' => $nombre, 'apellidos' => 'Examen', 'usuario' => $nombre,
                'password' => 'PruebaExamen123!', 'rol' => 'estudiante', 'activo' => true,
            ]);
        }
        $this->simulacro = DB::table('simulacros')->where('nombre', ExamenDemoSeeder::SIMULACRO)->value('id');
        $this->servicio = app(IntentoService::class);
    }

    private function iniciar(?Usuario $usuario = null): object
    {
        return $this->servicio->iniciar(($usuario ?? $this->alumno)->id, ['tipo' => 'simulacro', 'simulacro_id' => $this->simulacro]);
    }

    public function test_el_seeder_deja_un_simulacro_de_40_preguntas_y_60_minutos(): void
    {
        $intento = $this->iniciar();

        $this->assertSame(40, (int) $intento->total_preguntas);
        $this->assertEquals(now()->addMinutes(60), Carbon::parse($intento->fecha_limite));
        $this->assertSame(40, DB::table('alternativas')->where('es_correcta', true)->count());
    }

    public function test_las_preguntas_incluyen_area_tema_y_bloque_sin_la_clave(): void
    {
        $preguntas = $this->servicio->preguntas($this->iniciar()->id);

        $this->assertCount(40, $preguntas);
        $primera = $preguntas->first();
        $this->assertSame('habilidad_verbal', $primera->bloque);
        $this->assertSame('Razonamiento Verbal', $primera->area);
        $this->assertContains($primera->tema, ['Sinónimos', 'Analogías']);
        $this->assertSame('conocimientos', $preguntas->last()->bloque);
        $this->assertObjectNotHasProperty('solucion', $primera);
        $this->assertCount(4, $primera->alternativas);
        $this->assertObjectNotHasProperty('es_correcta', $primera->alternativas->first());
    }

    public function test_en_curso_reanuda_el_mismo_intento_solo_para_su_dueno(): void
    {
        $this->assertNull($this->servicio->enCurso($this->alumno->id, $this->simulacro));
        $intento = $this->iniciar();

        $this->assertSame($intento->id, $this->servicio->enCurso($this->alumno->id, $this->simulacro)->id);
        $this->assertNull($this->servicio->enCurso($this->otro->id, $this->simulacro));

        $this->servicio->finalizar($intento->id, $this->alumno->id);
        $this->assertNull($this->servicio->enCurso($this->alumno->id, $this->simulacro));
    }

    public function test_en_curso_cierra_el_intento_vencido(): void
    {
        $intento = $this->iniciar();
        $this->travel(61)->minutes();

        $this->assertNull($this->servicio->enCurso($this->alumno->id, $this->simulacro));
        $this->assertSame('enviado_auto', DB::table('intentos')->where('id', $intento->id)->value('estado'));
    }

    public function test_respuestas_devuelve_lo_guardado_en_orden(): void
    {
        $intento = $this->iniciar();
        $pregunta = $this->servicio->preguntas($intento->id)->get(2);
        $alternativa = $pregunta->alternativas->first()->id;
        $this->servicio->guardarRespuesta($intento->id, $this->alumno->id, [
            'pregunta_id' => $pregunta->id, 'alternativa_id' => $alternativa,
        ]);

        $respuestas = $this->servicio->respuestas($intento->id);

        $this->assertSame(range(1, 40), $respuestas->pluck('orden')->map(fn ($o) => (int) $o)->all());
        $this->assertSame($alternativa, (int) $respuestas->get(2)->alternativa_id);
        $this->assertSame(1, $respuestas->whereNotNull('alternativa_id')->count());
    }

    public function test_el_endpoint_mostrar_conserva_el_formato_de_respuestas(): void
    {
        $intento = $this->iniciar();
        $this->post('/login', ['usuario' => 'alumno', 'password' => 'PruebaExamen123!'])->assertRedirect('/alumno/inicio');

        $this->getJson("/api/intentos/{$intento->id}")->assertOk()
            ->assertJsonCount(40, 'data.respuestas')
            ->assertJsonStructure(['data' => [
                'respuestas' => [['id', 'pregunta_id', 'orden', 'alternativa_id', 'respondida_en']],
                'preguntas' => [['id', 'enunciado', 'area', 'tema', 'bloque', 'alternativas']],
            ]]);
    }
}
