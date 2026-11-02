<?php

namespace Tests\Feature;

use App\Livewire\Alumno\PanelAlumno;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'usuario' => uniqid('alumno'),
            'password' => 'secreto123', 'rol' => 'estudiante', 'activo' => true,
        ], $datos));
    }

    public function test_muestra_datos_reales_y_campos_sin_soporte_sin_inventarlos(): void
    {
        Livewire::actingAs($this->usuario(), 'web')->test(PanelAlumno::class)
            ->assertSee('¡Hola, Ana!')->assertSee('Pérez')->assertSee('1 / 2')
            ->assertSee('No disponible')->assertDontSee('juan123@correo.com')
            ->assertSee('Sin simulacros abiertos');
    }

    public function test_tarjeta_del_simulador_solo_cuenta_simulacros_activos_y_en_fecha(): void
    {
        $alumno = $this->usuario();
        $docente = $this->usuario(['rol' => 'docente']);
        $simulacro = fn (bool $activo, $inicio, $fin) => DB::table('simulacros')->insert([
            'nombre' => uniqid('Simulacro '), 'tiempo_limite' => 30, 'total_preguntas' => 1,
            'cantidad_alternativas' => 4, 'puntaje_correcta' => 1, 'puntaje_incorrecta' => 0,
            'puntaje_blanco' => 0, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'activo' => $activo, 'creado_por' => $docente->id,
        ]);
        $simulacro(false, now()->subHour(), now()->addHour());
        $simulacro(true, now()->subDays(2), now()->subDay());
        $simulacro(true, now()->addDay(), now()->addDays(2));
        Livewire::actingAs($alumno, 'web')->test(PanelAlumno::class)
            ->assertSee('Sin simulacros abiertos')->assertDontSee('Disponible ahora');

        $simulacro(true, now()->subHour(), now()->addHour());
        Livewire::actingAs($alumno, 'web')->test(PanelAlumno::class)->assertSee('Disponible ahora');
    }

    public function test_progreso_solo_cuenta_simulacros_del_alumno_actual(): void
    {
        $ana = $this->usuario();
        $otro = $this->usuario();
        foreach ([[$ana, 'practica'], [$otro, 'simulacro']] as [$usuario, $tipo]) {
            DB::table('intentos')->insert(['usuario_id' => $usuario->id, 'tipo' => $tipo,
                'fecha_inicio' => now(), 'estado' => 'en_curso', 'total_preguntas' => 0]);
        }
        Livewire::actingAs($ana, 'web')->test(PanelAlumno::class)->assertSee('1 / 2');
        DB::table('intentos')->insert(['usuario_id' => $ana->id, 'tipo' => 'simulacro',
            'fecha_inicio' => now(), 'estado' => 'en_curso', 'total_preguntas' => 0]);
        Livewire::actingAs($ana, 'web')->test(PanelAlumno::class)->assertSee('2 / 2');
    }

    public function test_rechaza_invitados_inactivos_y_otros_roles(): void
    {
        Livewire::test(PanelAlumno::class)->assertForbidden();
        foreach ([['activo' => false], ['rol' => 'docente'], ['rol' => 'admin']] as $datos) {
            Livewire::actingAs($this->usuario($datos), 'web')->test(PanelAlumno::class)->assertForbidden();
        }
    }

    public function test_layout_propio_y_escape_de_datos(): void
    {
        $usuario = $this->usuario(['nombres' => '<script>alert(1)</script>']);
        $this->actingAs($usuario, 'web');
        $this->get(route('alumno.perfil'))->assertOk()
            ->assertSee('css/alumno/panel.css')->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_ruta_del_modulo_exige_sesion_y_conserva_panel_compartido(): void
    {
        $this->get('/alumno/perfil')->assertRedirect(route('login'));
        $this->actingAs($this->usuario(), 'web')->get('/alumno/perfil')
            ->assertOk()->assertSee('Mi perfil')->assertSee('Ana');
        $this->get('/panel')->assertOk()->assertSee('Bienvenido, Ana');
    }

    public function test_ruta_del_modulo_rechaza_cuentas_inactivas_y_docentes(): void
    {
        $this->actingAs($this->usuario(['activo' => false]), 'web')
            ->get('/alumno/perfil')->assertForbidden();
        $this->actingAs($this->usuario(['rol' => 'docente']), 'web')
            ->get('/alumno/perfil')->assertForbidden();
    }

    public function test_login_regresa_al_perfil_solicitado_y_logout_cierra_la_sesion(): void
    {
        $usuario = $this->usuario();
        $this->get('/alumno/perfil')->assertRedirect(route('login'));
        $this->post('/login', ['usuario' => $usuario->usuario, 'password' => 'secreto123'])
            ->assertRedirect(route('alumno.perfil'));
        $this->get('/alumno/perfil')->assertOk();
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->get('/alumno/perfil')->assertRedirect(route('login'));
    }

    public function test_intento_creado_por_la_api_actualiza_el_progreso_sin_escribir_desde_el_perfil(): void
    {
        $alumno = $this->usuario();
        $docente = $this->usuario(['rol' => 'docente']);
        $area = DB::table('areas')->insertGetId([
            'nombre' => 'Álgebra', 'bloque' => 'conocimientos', 'activo' => true,
        ]);
        $tema = DB::table('temas')->insertGetId([
            'area_id' => $area, 'nombre' => 'Ecuaciones', 'activo' => true,
        ]);
        $this->actingAs($docente, 'web');
        $this->postJson('/api/preguntas', [
            'tema_id' => $tema, 'enunciado' => '¿Cuánto es 1 + 1?', 'nivel' => 'basico',
            'solucion' => 'La suma de dos unidades es 2.',
            'alternativas' => array_map(fn ($letra, $texto) => [
                'letra' => $letra, 'texto' => $texto, 'es_correcta' => $letra === 'B',
            ], ['A', 'B', 'C', 'D'], ['1', '2', '3', '4']),
        ])->assertCreated();
        $simulacro = $this->postJson('/api/simulacros', [
            'nombre' => 'Integración del perfil', 'tiempo_limite' => 30,
            'total_preguntas' => 1,
            'fecha_inicio' => now()->subHour()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addHour()->format('Y-m-d H:i:s'),
            'areas' => [['area_id' => $area, 'nivel' => 'mixto', 'cantidad_preguntas' => 1]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($alumno, 'web')->get('/alumno/perfil')->assertOk()->assertSee('1 / 2');
        $intento = $this->postJson('/api/intentos', [
            'tipo' => 'simulacro', 'simulacro_id' => $simulacro,
        ])->assertCreated()->json('data.id');
        $antes = DB::table('intentos')->where('id', $intento)->first();
        $usuarioAntes = DB::table('usuarios')->where('id', $alumno->id)->first();

        $this->get('/alumno/perfil')->assertOk()->assertSee('2 / 2');

        $this->assertEquals($antes, DB::table('intentos')->where('id', $intento)->first());
        $this->assertEquals($usuarioAntes, DB::table('usuarios')->where('id', $alumno->id)->first());
        $this->assertDatabaseCount('intentos', 1);
        $this->assertDatabaseCount('respuestas_estudiante', 1);
    }

    public function test_livewire_rechaza_actualizaciones_si_la_cuenta_pierde_acceso(): void
    {
        foreach ([['activo' => false], ['rol' => 'docente']] as $cambio) {
            $usuario = $this->usuario();
            $panel = Livewire::actingAs($usuario, 'web')->test(PanelAlumno::class);
            $usuario->update($cambio);
            $panel->call('$refresh')->assertForbidden();
        }
    }
}
