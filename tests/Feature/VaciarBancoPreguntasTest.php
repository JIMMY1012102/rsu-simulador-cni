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

    public function test_vacia_el_banco_y_conserva_historial_e_imagenes_de_preguntas_usadas(): void
    {
        Storage::disk('public')->put('banco/AM_1.png', 'imagen-original');
        Storage::disk('public')->put('banco/AM_1_A.png', 'alternativa-original');

        $historicaId = $this->pregunta('AM_1', 'storage/banco/AM_1.png');
        $nuevaId = $this->pregunta('LE_1');
        DB::table('alternativas')->where('pregunta_id', $historicaId)
            ->update(['imagen' => 'storage/banco/AM_1_A.png']);

        $alumno = $this->usuario('alumno_1', 'estudiante');
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

        $this->actingAs($this->usuario('Super_Admin'), 'web')
            ->delete(route('admin.preguntas.destroy-all'), ['confirmacion' => 'BORRAR TODO'])
            ->assertRedirect(route('admin.preguntas.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('preguntas', ['id' => $nuevaId]);
        $this->assertDatabaseMissing('alternativas', ['pregunta_id' => $nuevaId]);
        $this->assertDatabaseHas('preguntas', [
            'id' => $historicaId, 'codigo' => 'HIST_'.$historicaId, 'activo' => false,
            'imagen' => 'storage/banco/historico/'.$historicaId.'/AM_1.png',
        ]);
        $this->assertDatabaseHas('alternativas', [
            'pregunta_id' => $historicaId,
            'imagen' => 'storage/banco/historico/'.$historicaId.'/AM_1_A.png',
        ]);
        $this->assertDatabaseHas('respuestas_estudiante', [
            'intento_id' => $intento, 'pregunta_id' => $historicaId,
        ]);

        Storage::disk('public')->assertExists('banco/historico/'.$historicaId.'/AM_1.png');
        Storage::disk('public')->assertExists('banco/historico/'.$historicaId.'/AM_1_A.png');
        $this->assertSame('imagen-original', Storage::disk('public')
            ->get('banco/historico/'.$historicaId.'/AM_1.png'));

        $this->get(route('admin.preguntas.index'))->assertOk()
            ->assertSee('No hay preguntas para mostrar.')
            ->assertDontSee('HIST_'.$historicaId);

        // El código queda libre para importar una versión completamente nueva.
        DB::table('preguntas')->insert([
            'codigo' => 'AM_1',
            'tema_id' => DB::table('preguntas')->where('id', $historicaId)->value('tema_id'),
            'enunciado' => 'Nueva pregunta',
            'nivel' => 'basico',
            'solucion' => '',
            'activo' => true,
        ]);
        $this->assertDatabaseHas('preguntas', ['codigo' => 'AM_1', 'activo' => true]);
    }

    public function test_no_vacia_el_banco_si_hay_un_examen_en_curso(): void
    {
        $id = $this->pregunta('AM_1');
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
            ->from(route('admin.preguntas.index'))
            ->delete(route('admin.preguntas.destroy-all'), ['confirmacion' => 'BORRAR TODO'])
            ->assertSessionHasErrors('confirmacion');

        $this->assertDatabaseHas('preguntas', ['id' => $id, 'codigo' => 'AM_1', 'activo' => true]);
    }
}
