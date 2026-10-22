<?php

namespace Tests\Feature;

use App\Models\Seccion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecorridoSimulacroTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $docente;

    private Usuario $estudiante;

    private Usuario $otro;

    private int $area;

    private int $tema;

    private array $preguntas = [];

    private int $simulacro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $seccion = Seccion::create(['nombre' => 'A', 'grado' => '5to', 'activo' => true]);
        foreach (['docente', 'estudiante', 'otro'] as $nombre) {
            $this->$nombre = Usuario::create([
                'nombres' => $nombre, 'apellidos' => 'Integración', 'usuario' => $nombre,
                'password' => 'PruebaIntegracion123!', 'rol' => $nombre === 'docente' ? 'docente' : 'estudiante',
                'activo' => true,
                'seccion_id' => $seccion->id,
            ]);
        }
        $this->area = DB::table('areas')->insertGetId([
            'nombre' => 'Álgebra', 'bloque' => 'conocimientos', 'activo' => true,
        ]);
        $this->tema = DB::table('temas')->insertGetId([
            'area_id' => $this->area, 'nombre' => 'Ecuaciones', 'activo' => true,
        ]);
        $this->ingresar('docente');
        for ($i = 1; $i <= 3; $i++) {
            $this->preguntas[] = $this->postJson('/api/preguntas', [
                'tema_id' => $this->tema, 'enunciado' => "Pregunta de integración {$i}",
                'nivel' => 'basico', 'solucion' => 'Explicación reservada para después del examen.',
                'alternativas' => array_map(fn ($letra) => [
                    'letra' => $letra, 'texto' => "Opción {$letra}", 'es_correcta' => $letra === 'A',
                ], ['A', 'B', 'C', 'D']),
            ])->assertCreated()->json('data.id');
        }
        $this->simulacro = $this->postJson('/api/simulacros', [
            'nombre' => 'Simulacro de integración', 'tiempo_limite' => 30, 'total_preguntas' => 3,
            'fecha_inicio' => now()->subHour()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addHour()->format('Y-m-d H:i:s'),
            'areas' => [['area_id' => $this->area, 'nivel' => 'mixto', 'cantidad_preguntas' => 3]],
        ])->assertCreated()->json('data.id');
        $this->post('/logout')->assertRedirect('/login');
        $this->ingresar('estudiante');
    }

    private function ingresar(string $usuario): void
    {
        $this->post('/login', ['usuario' => $usuario, 'password' => 'PruebaIntegracion123!'])
            ->assertRedirect($usuario === 'docente' ? '/docente' : '/panel');
    }

    private function iniciar(): int
    {
        return $this->postJson('/api/intentos', ['tipo' => 'simulacro', 'simulacro_id' => $this->simulacro])
            ->assertCreated()->json('data.id');
    }

    private function opcion(int $pregunta, string $letra): int
    {
        return DB::table('alternativas')->where('pregunta_id', $pregunta)->where('letra', $letra)->value('id');
    }

    public function test_recorrido_completo_desde_login_hasta_resultados(): void
    {
        $this->getJson('/api/simulacros')->assertOk()->assertJsonPath('data.0.id', $this->simulacro);
        $intento = $this->iniciar();
        $this->assertDatabaseCount('respuestas_estudiante', 3);
        $this->getJson("/api/intentos/{$intento}")->assertOk()
            ->assertJsonCount(3, 'data.preguntas')->assertJsonCount(3, 'data.respuestas')
            ->assertJsonMissingPath('data.preguntas.0.solucion')
            ->assertJsonMissingPath('data.preguntas.0.alternativas.0.es_correcta');
        foreach (['A', 'B'] as $i => $letra) {
            $this->postJson("/api/intentos/{$intento}/respuestas", [
                'pregunta_id' => $this->preguntas[$i],
                'alternativa_id' => $this->opcion($this->preguntas[$i], $letra),
            ])->assertOk();
        }
        $this->getJson("/api/resultados/{$intento}")->assertUnprocessable();
        $this->postJson("/api/intentos/{$intento}/finalizar")->assertOk()
            ->assertJsonPath('data.estado', 'finalizado');
        $this->getJson("/api/resultados/{$intento}")->assertOk()
            ->assertJsonPath('data.totales.correctas', 1)
            ->assertJsonPath('data.totales.incorrectas', 1)
            ->assertJsonPath('data.totales.en_blanco', 1)
            ->assertJsonPath('data.totales.puntaje_total', 18.875)
            ->assertJsonCount(1, 'data.resultados_area');
        $this->getJson('/api/resultados')->assertOk()->assertJsonPath('data.data.0.id', $intento);
        // Reintentos HTTP no vuelven a sumar ni duplican resultados.
        $this->postJson("/api/intentos/{$intento}/finalizar")->assertOk();
        $this->postJson('/api/resultados/calificar', ['intento_id' => $intento])->assertOk()
            ->assertJsonPath('data.totales.puntaje_total', 18.875);
        $this->assertDatabaseCount('resultados_area', 1);
        $this->postJson("/api/intentos/{$intento}/respuestas", [
            'pregunta_id' => $this->preguntas[0], 'alternativa_id' => null,
        ])->assertUnprocessable();
    }

    public function test_no_crea_intentos_parciales_si_el_banco_no_alcanza(): void
    {
        DB::table('preguntas')->where('id', $this->preguntas[0])->update(['activo' => false]);
        $this->postJson('/api/intentos', ['tipo' => 'simulacro', 'simulacro_id' => $this->simulacro])
            ->assertUnprocessable()->assertJsonValidationErrors('preguntas');
        $this->assertDatabaseCount('intentos', 0);
        $this->assertDatabaseCount('respuestas_estudiante', 0);
    }

    public function test_no_sortea_preguntas_sin_una_unica_clave_o_con_numero_incorrecto_de_opciones(): void
    {
        DB::table('alternativas')->where('pregunta_id', $this->preguntas[0])->update(['es_correcta' => false]);
        DB::table('alternativas')->where('id', $this->opcion($this->preguntas[1], 'B'))->update(['es_correcta' => true]);
        DB::table('alternativas')->where('id', $this->opcion($this->preguntas[2], 'D'))->delete();
        $this->postJson('/api/intentos', ['tipo' => 'simulacro', 'simulacro_id' => $this->simulacro])
            ->assertUnprocessable()->assertJsonValidationErrors('preguntas');
        $this->assertDatabaseCount('intentos', 0);
    }

    public function test_el_sorteo_respeta_cuotas_especificas_y_mixtas_sin_duplicar(): void
    {
        DB::table('preguntas')->where('id', $this->preguntas[0])->update(['nivel' => 'avanzado']);
        DB::table('simulacro_area')->where('simulacro_id', $this->simulacro)->delete();
        // Insertar primero mixto permite comprobar que se atiende después de avanzado.
        foreach ([['mixto', 2], ['avanzado', 1]] as [$nivel, $cantidad]) {
            DB::table('simulacro_area')->insert([
                'simulacro_id' => $this->simulacro, 'area_id' => $this->area,
                'nivel' => $nivel, 'cantidad_preguntas' => $cantidad,
            ]);
        }
        $intento = $this->iniciar();
        $asignadas = DB::table('respuestas_estudiante')->where('intento_id', $intento)->orderBy('orden')->get();
        $this->assertCount(3, $asignadas->pluck('pregunta_id')->unique());
        $this->assertEqualsCanonicalizing($this->preguntas, $asignadas->pluck('pregunta_id')->all());
        $this->assertEqualsCanonicalizing([1, 2, 3], $asignadas->pluck('orden')->all());
        // Reanudar no vuelve a sortear ni cambia el orden.
        $this->getJson("/api/intentos/{$intento}")->assertOk();
        $this->assertSame($asignadas->pluck('pregunta_id')->all(), DB::table('respuestas_estudiante')
            ->where('intento_id', $intento)->orderBy('orden')->pluck('pregunta_id')->all());
    }

    public function test_rechaza_simulacro_inactivo_y_fuera_de_fechas(): void
    {
        foreach ([
            ['activo' => false],
            ['activo' => true, 'fecha_inicio' => now()->addMinute()],
            ['fecha_inicio' => now()->subHour(), 'fecha_fin' => now()],
        ] as $datos) {
            DB::table('simulacros')->where('id', $this->simulacro)->update($datos);
            $this->postJson('/api/intentos', ['tipo' => 'simulacro', 'simulacro_id' => $this->simulacro])
                ->assertUnprocessable();
        }
        $this->assertDatabaseCount('intentos', 0);
    }

    public function test_otro_estudiante_no_lee_ni_modifica_intentos_o_resultados_ajenos(): void
    {
        $intento = $this->iniciar();
        $this->post('/logout');
        $this->ingresar('otro');
        $this->getJson("/api/intentos/{$intento}")->assertNotFound();
        $this->postJson("/api/intentos/{$intento}/respuestas", [
            'pregunta_id' => $this->preguntas[0], 'alternativa_id' => null,
        ])->assertNotFound();
        $this->postJson("/api/intentos/{$intento}/finalizar")->assertNotFound();
        $this->getJson("/api/resultados/{$intento}")->assertForbidden();
        $this->postJson('/api/resultados/calificar', ['intento_id' => $intento])->assertForbidden();
        $this->getJson('/api/resultados')->assertJsonCount(0, 'data.data');
        $this->assertDatabaseHas('intentos', ['id' => $intento, 'estado' => 'en_curso', 'puntaje_total' => null]);
    }

    public function test_se_puede_cambiar_y_borrar_una_respuesta_pero_no_cruzar_alternativas(): void
    {
        $intento = $this->iniciar();
        $pregunta = $this->preguntas[0];
        foreach (['A', 'B', null] as $letra) {
            $opcion = $letra ? $this->opcion($pregunta, $letra) : null;
            $this->postJson("/api/intentos/{$intento}/respuestas", [
                'pregunta_id' => $pregunta, 'alternativa_id' => $opcion,
            ])->assertOk()->assertJsonPath('data.alternativa_id', $opcion);
        }
        $this->postJson("/api/intentos/{$intento}/respuestas", [
            'pregunta_id' => $pregunta, 'alternativa_id' => $this->opcion($this->preguntas[1], 'A'),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('respuestas_estudiante', 3);
    }

    public function test_respuesta_fuera_de_tiempo_se_rechaza_y_el_cierre_si_persiste(): void
    {
        $intento = $this->iniciar();
        $this->travel(30)->minutes(); // Exactamente en el límite tampoco se admite.
        $this->postJson("/api/intentos/{$intento}/respuestas", [
            'pregunta_id' => $this->preguntas[0], 'alternativa_id' => $this->opcion($this->preguntas[0], 'A'),
        ])->assertUnprocessable();
        $this->assertDatabaseHas('intentos', ['id' => $intento, 'estado' => 'enviado_auto', 'puntaje_total' => 0]);
        $this->assertDatabaseHas('respuestas_estudiante', [
            'intento_id' => $intento, 'pregunta_id' => $this->preguntas[0], 'alternativa_id' => null,
        ]);
        $this->getJson("/api/resultados/{$intento}")->assertOk()->assertJsonPath('data.totales.en_blanco', 3);
    }

    public function test_consultar_intento_vencido_lo_finaliza_y_califica(): void
    {
        $intento = $this->iniciar();
        $this->travel(31)->minutes();
        $this->getJson("/api/intentos/{$intento}")->assertOk()->assertJsonPath('data.intento.estado', 'enviado_auto');
        $this->getJson("/api/resultados/{$intento}")->assertOk();
    }

    public function test_comando_cierra_y_califica_sin_necesitar_que_el_navegador_siga_abierto(): void
    {
        $intento = $this->iniciar();
        $this->travel(31)->minutes();
        $this->artisan('intentos:cerrar-vencidos')->expectsOutput('Intentos cerrados: 1. Fallos: 0.')->assertSuccessful();
        $this->artisan('intentos:cerrar-vencidos')->expectsOutput('Intentos cerrados: 0. Fallos: 0.')->assertSuccessful();
        $this->assertDatabaseHas('intentos', ['id' => $intento, 'estado' => 'enviado_auto', 'en_blanco' => 3]);
        $this->assertDatabaseCount('resultados_area', 1);
    }

    public function test_una_configuracion_usada_no_se_edita_y_no_se_pierden_respuestas(): void
    {
        $intento = $this->iniciar();
        $this->postJson("/api/intentos/{$intento}/respuestas", [
            'pregunta_id' => $this->preguntas[0], 'alternativa_id' => $this->opcion($this->preguntas[0], 'A'),
        ])->assertOk();
        $this->post('/logout');
        $this->ingresar('docente');
        $pregunta = $this->getJson('/api/preguntas/'.$this->preguntas[0])->assertOk()->json('data');
        $pregunta['enunciado'] = 'Intento de cambiar una pregunta usada';
        $this->putJson('/api/preguntas/'.$this->preguntas[0], $pregunta)->assertUnprocessable();
        $this->putJson('/api/simulacros/'.$this->simulacro, [
            'nombre' => 'Configuración cambiada', 'tiempo_limite' => 5, 'total_preguntas' => 3,
            'fecha_inicio' => now()->subHour()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addHour()->format('Y-m-d H:i:s'),
            'areas' => [['area_id' => $this->area, 'cantidad_preguntas' => 3]],
        ])->assertUnprocessable();
        $this->assertDatabaseHas('respuestas_estudiante', [
            'intento_id' => $intento, 'alternativa_id' => $this->opcion($this->preguntas[0], 'A'),
        ]);
    }

    public function test_fallo_al_calificar_no_deja_finalizacion_ni_resultados_parciales(): void
    {
        $intento = $this->iniciar();
        // Simular corrupción externa; las ediciones por el servicio ya se bloquean.
        DB::table('alternativas')->where('pregunta_id', $this->preguntas[0])->update(['es_correcta' => false]);
        $this->postJson("/api/intentos/{$intento}/finalizar")->assertUnprocessable();
        $this->assertDatabaseHas('intentos', ['id' => $intento, 'estado' => 'en_curso', 'puntaje_total' => null]);
        $this->assertDatabaseCount('resultados_area', 0);
        $this->assertSame(0, DB::table('respuestas_estudiante')->whereNotNull('puntaje_obtenido')->count());
    }

    public function test_el_sorteo_conserva_la_secuencia_de_bloques(): void
    {
        $verbal = DB::table('areas')->insertGetId(['nombre' => 'Verbal', 'bloque' => 'habilidad_verbal', 'activo' => true]);
        $tema = DB::table('temas')->insertGetId(['area_id' => $verbal, 'nombre' => 'Lectura', 'activo' => true]);
        DB::table('preguntas')->where('id', $this->preguntas[0])->update(['tema_id' => $tema]);
        DB::table('simulacro_area')->where('simulacro_id', $this->simulacro)->update(['cantidad_preguntas' => 2]);
        DB::table('simulacro_area')->insert([
            'simulacro_id' => $this->simulacro, 'area_id' => $verbal, 'nivel' => 'mixto', 'cantidad_preguntas' => 1,
        ]);
        $intento = $this->iniciar();
        $this->getJson("/api/intentos/{$intento}")->assertOk()->assertJsonPath('data.preguntas.0.id', $this->preguntas[0]);
    }

    public function test_area_de_postulacion_se_comprueba_con_el_perfil_y_no_con_un_id_enviado(): void
    {
        $area = DB::table('areas_postulacion')->insertGetId(['codigo' => 'A', 'nombre' => 'Ingenierías', 'activo' => true]);
        $carrera = DB::table('carreras')->insertGetId(['area_postulacion_id' => $area, 'nombre' => 'Sistemas', 'activo' => true]);
        DB::table('area_postulacion_simulacro')->insert(['simulacro_id' => $this->simulacro, 'area_postulacion_id' => $area]);
        $this->postJson('/api/intentos', [
            'tipo' => 'simulacro', 'simulacro_id' => $this->simulacro, 'carrera_id' => $carrera,
        ])->assertUnprocessable()->assertJsonValidationErrors('carrera_id');
        $this->assertDatabaseCount('intentos', 0);
        DB::table('usuarios')->where('id', $this->estudiante->id)->update(['carrera_id' => $carrera]);
        $intento = $this->iniciar();
        $this->assertDatabaseHas('intentos', ['id' => $intento, 'carrera_id' => $carrera]);
    }

    public function test_pregunta_existente_pero_ajena_al_sorteo_no_se_puede_responder(): void
    {
        $intento = $this->iniciar();
        $ajena = DB::table('preguntas')->insertGetId([
            'tema_id' => $this->tema, 'enunciado' => 'Otra pregunta', 'nivel' => 'basico',
            'solucion' => 'Otra solución', 'activo' => true,
        ]);
        $this->postJson("/api/intentos/{$intento}/respuestas", ['pregunta_id' => $ajena, 'alternativa_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('pregunta_id');
        $this->assertDatabaseCount('respuestas_estudiante', 3);
    }

    public function test_se_conserva_el_puntaje_negativo_de_las_penalizaciones(): void
    {
        $intento = $this->iniciar();
        foreach ($this->preguntas as $pregunta) {
            $this->postJson("/api/intentos/{$intento}/respuestas", [
                'pregunta_id' => $pregunta, 'alternativa_id' => $this->opcion($pregunta, 'B'),
            ])->assertOk();
        }
        $this->postJson("/api/intentos/{$intento}/finalizar")->assertOk();
        $this->getJson("/api/resultados/{$intento}")->assertOk()
            ->assertJsonPath('data.totales.puntaje_total', -3.375)
            ->assertJsonPath('data.totales.incorrectas', 3);
    }
}
