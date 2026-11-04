<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VaciarBancoPreguntasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cni.administracion_academica_enabled' => true]);
        Storage::fake('public');
    }

    private function usuario(string $login, string $rol = 'admin'): Usuario
    {
        return Usuario::create([
            'usuario' => $login,
            'nombres' => 'Usuario',
            'apellidos' => 'CNI',
            'password' => 'ClaveSegura!2026',
            'rol' => $rol,
            'activo' => true,
        ]);
    }

    private function pregunta(string $codigo, ?string $imagen = null): int
    {
        $area = DB::table('areas')->insertGetId([
            'nombre' => 'Curso '.$codigo,
            'bloque' => 'conocimientos',
            'activo' => true,
        ]);
        $tema = DB::table('temas')->insertGetId([
            'area_id' => $area,
            'nombre' => 'Tema '.$codigo,
            'activo' => true,
        ]);
        $id = DB::table('preguntas')->insertGetId([
            'codigo' => $codigo,
            'tema_id' => $tema,
            'enunciado' => 'Pregunta '.$codigo,
            'nivel' => 'basico',
            'solucion' => 'Solución',
            'imagen' => $imagen,
            'activo' => true,
        ]);
        DB::table('alternativas')->insert([
            'pregunta_id' => $id,
            'letra' => 'A',
            'texto' => 'Alternativa A',
            'es_correcta' => true,
        ]);

        return $id;
    }

    public function test_solo_super_admin_ve_el_boton_y_puede_usarlo(): void
    {
        $id = $this->pregunta('AM_1');

        $this->actingAs($this->usuario('admin_comun'), 'web');
        $this->get(route('admin.preguntas.index'))->assertOk()
            ->assertDontSee('Borrar todas las preguntas');
        $this->delete(route('admin.preguntas.destroy-all'), ['confirmacion' => 'BORRAR TODO'])
            ->assertForbidden();
        $this->assertDatabaseHas('preguntas', ['id' => $id]);

        $this->actingAs($this->usuario('Super_Admin'), 'web');
        $this->get(route('admin.preguntas.index'))->assertOk()
            ->assertSee('Borrar todas las preguntas')
            ->assertSee('BORRAR TODO');
    }

    public function test_sin_confirmacion_valida_no_se_borra_nada(): void
    {
        $id = $this->pregunta('AM_1');
        $this->actingAs($this->usuario('Super_Admin'), 'web');

        $this->from(route('admin.preguntas.index'))
            ->delete(route('admin.preguntas.destroy-all'), ['confirmacion' => 'BORRAR'])
            ->assertRedirect(route('admin.preguntas.index'))
            ->assertSessionHasErrors('confirmacion');

        $this->assertDatabaseHas('preguntas', ['id' => $id, 'codigo' => 'AM_1']);
    }

    public function test_reinicia_completamente_banco_historial_imagenes_e_ids(): void
    {
        Storage::disk('public')->put('banco/AM_1.png', 'imagen-anterior');
        Storage::disk('public')->put('banco/historico/7/archivo.png', 'historial');
        Storage::disk('public')->put('preguntas/manual.png', 'manual');

        $historicaId = $this->pregunta('AM_1', 'storage/banco/AM_1.png');
        $nuevaId = $this->pregunta('LE_1');
        $temaId = DB::table('preguntas')->where('id', $nuevaId)->value('tema_id');
        $areaId = DB::table('temas')->where('id', $temaId)->value('area_id');
        DB::table('preguntas')->where('id', $historicaId)->update([
            'codigo' => 'HIST_'.$historicaId, 'activo' => false,
        ]);

        $alumno = $this->usuario('demo', 'estudiante');
        $intento = DB::table('intentos')->insertGetId([
            'usuario_id' => $alumno->id,
            'tipo' => 'practica',
            'estado' => 'finalizado',
            'total_preguntas' => 1,
            'fecha_inicio' => now(),
        ]);
        DB::table('respuestas_estudiante')->insert([
            'intento_id' => $intento,
            'pregunta_id' => $historicaId,
            'orden' => 1,
        ]);
        DB::table('resultados_area')->insert([
            'intento_id' => $intento,
            'area_id' => $areaId,
            'total_preguntas' => 1,
            'aciertos' => 0,
            'incorrectas' => 0,
            'en_blanco' => 1,
            'puntaje' => 0,
            'porcentaje' => 0,
        ]);

        $this->actingAs($this->usuario('Super_Admin'), 'web')
            ->delete(route('admin.preguntas.destroy-all'), ['confirmacion' => 'BORRAR TODO'])
            ->assertRedirect(route('admin.preguntas.index'))
            ->assertSessionHas('status');

        foreach (['respuestas_estudiante', 'resultados_area', 'intentos', 'alternativas', 'preguntas'] as $tabla) {
            $this->assertDatabaseCount($tabla, 0);
        }
        $this->assertDatabaseHas('usuarios', ['id' => $alumno->id]);
        $this->assertDatabaseHas('temas', ['id' => $temaId]);
        $this->assertDatabaseHas('areas', ['id' => $areaId]);
        Storage::disk('public')->assertMissing('banco/AM_1.png');
        Storage::disk('public')->assertMissing('banco/historico/7/archivo.png');
        Storage::disk('public')->assertMissing('preguntas/manual.png');
        $this->get(route('admin.preguntas.index'))->assertOk()->assertSee('No hay preguntas para mostrar.');

        $primerId = DB::table('preguntas')->insertGetId([
            'codigo' => 'AM_1',
            'tema_id' => $temaId,
            'enunciado' => 'Pregunta nueva',
            'nivel' => 'basico',
            'solucion' => '',
            'activo' => true,
        ]);
        $this->assertSame(1, $primerId);
    }

    public function test_se_puede_reiniciar_demo_con_intentos_en_curso(): void
    {
        $id = $this->pregunta('RM_1');
        $alumno = $this->usuario('alumno_examen', 'estudiante');
        $intento = DB::table('intentos')->insertGetId([
            'usuario_id' => $alumno->id,
            'tipo' => 'simulacro',
            'estado' => 'en_curso',
            'total_preguntas' => 1,
            'fecha_inicio' => now(),
        ]);
        DB::table('respuestas_estudiante')->insert([
            'intento_id' => $intento, 'pregunta_id' => $id, 'orden' => 1,
        ]);

        $this->actingAs($this->usuario('Super_Admin'), 'web')
            ->delete(route('admin.preguntas.destroy-all'), ['confirmacion' => 'BORRAR TODO'])
            ->assertRedirect(route('admin.preguntas.index'));

        $this->assertDatabaseCount('preguntas', 0);
        $this->assertDatabaseCount('intentos', 0);
        $this->assertDatabaseCount('respuestas_estudiante', 0);
        $this->assertDatabaseHas('usuarios', ['id' => $alumno->id]);
    }
}
