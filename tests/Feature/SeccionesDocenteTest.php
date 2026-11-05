<?php

namespace Tests\Feature;

use App\Livewire\Docente\CalificacionesSalon;
use App\Models\Seccion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SeccionesDocenteTest extends TestCase
{
    use RefreshDatabase;

    private array $docentes = [];
    private array $alumnos = [];
    private array $intentos = [];

    protected function setUp(): void
    {
        parent::setUp();

        $simulacro = DB::table('simulacros')->insertGetId([
            'nombre' => 'Evaluación CNI',
            'tiempo_limite' => 30,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'activo' => true,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $seccion = Seccion::create(['nombre' => $letra, 'grado' => '5to', 'activo' => true]);
            $this->docentes[$letra] = $this->usuario('docente_'.strtolower($letra), 'docente', $seccion->id);
            $this->alumnos[$letra] = $this->usuario('alumno_'.strtolower($letra), 'estudiante', $seccion->id);

            $this->intentos[$letra] = DB::table('intentos')->insertGetId([
                'usuario_id' => $this->alumnos[$letra]->id,
                'simulacro_id' => $simulacro,
                'tipo' => 'simulacro',
                'fecha_inicio' => now(),
                'fecha_fin' => now(),
                'estado' => 'finalizado',
                'total_preguntas' => 1,
                'puntaje_total' => match ($letra) {
                    'A' => 10, 'B' => 20, 'C' => 30, 'D' => 40,
                },
            ]);
        }
    }

    private function usuario(string $nombre, string $rol, ?int $seccionId): Usuario
    {
        return Usuario::create([
            'nombres' => 'Persona',
            'apellidos' => strtoupper($nombre),
            'usuario' => $nombre,
            'password' => 'TestDocente123!',
            'rol' => $rol,
            'seccion_id' => $seccionId,
            'activo' => true,
        ]);
    }

    public function test_cada_docente_solo_ve_su_seccion_y_sus_alumnos(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $componente = Livewire::actingAs($this->docentes[$letra], 'web')
                ->test(CalificacionesSalon::class)
                ->assertSee('5.º '.$letra)
                ->assertSee(strtoupper('alumno_'.strtolower($letra)));

            foreach (['A', 'B', 'C', 'D'] as $otra) {
                if ($otra !== $letra) {
                    $componente->assertDontSee(strtoupper('alumno_'.strtolower($otra)));
                }
            }
        }
    }

    public function test_alumnos_sin_intentos_aparecen_en_la_tabla(): void
    {
        $sinIntento = $this->usuario('sin_examen', 'estudiante', $this->docentes['A']->seccion_id);

        Livewire::actingAs($this->docentes['A'], 'web')
            ->test(CalificacionesSalon::class)
            ->assertSee(strtoupper($sinIntento->usuario))
            ->assertSee('Sin simulacros rendidos')
            ->assertSee('10.000');
    }

    public function test_api_lista_solo_resultados_de_la_seccion_de_cada_docente(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $this->actingAs($this->docentes[$letra], 'web')
                ->getJson('/api/resultados')
                ->assertOk()
                ->assertJsonCount(1, 'data.data')
                ->assertJsonPath('data.data.0.usuario_id', $this->alumnos[$letra]->id);
        }
    }

    public function test_api_bloquea_consulta_y_calificacion_de_otra_seccion(): void
    {
        $this->actingAs($this->docentes['A'], 'web')
            ->getJson('/api/resultados/'.$this->intentos['B'])->assertForbidden();

        $this->postJson('/api/resultados/calificar', [
            'intento_id' => $this->intentos['B'],
        ])->assertForbidden();

        $this->getJson('/api/resultados/'.$this->intentos['A'])
            ->assertOk()
            ->assertJsonPath('data.usuario_id', $this->alumnos['A']->id);
    }

    public function test_docente_sin_seccion_no_accede_a_resultados_de_otros(): void
    {
        $sinSeccion = $this->usuario('sin_seccion', 'docente', null);
        Livewire::actingAs($sinSeccion, 'web')
            ->test(CalificacionesSalon::class)
            ->assertSee('no tiene una sección activa')
            ->assertDontSee('ALUMNO_A');

        $this->actingAs($sinSeccion, 'web')
            ->getJson('/api/resultados')->assertForbidden();
        $this->getJson('/api/resultados/'.$this->intentos['A'])->assertForbidden();
    }

    public function test_seccion_inactiva_impide_ver_alumnos_y_resultados(): void
    {
        Seccion::whereKey($this->docentes['A']->seccion_id)->update(['activo' => false]);

        Livewire::actingAs($this->docentes['A'], 'web')
            ->test(CalificacionesSalon::class)
            ->assertDontSee('ALUMNO_A');

        $this->actingAs($this->docentes['A'], 'web')
            ->getJson('/api/resultados')->assertForbidden();
    }

    public function test_estudiante_no_puede_abrir_el_panel_docente(): void
    {
        $this->actingAs($this->alumnos['A'], 'web')->get('/docente')->assertForbidden();
        $this->get('/docente/calificaciones')->assertForbidden();
    }

    public function test_login_docente_redirige_al_panel_compartido(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $this->post('/login', [
                'usuario' => $this->docentes[$letra]->usuario,
                'password' => 'TestDocente123!',
            ])->assertRedirect('/docente');

            $this->get('/docente')->assertOk();
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_administrador_conserva_acceso_global_a_resultados(): void
    {
        $admin = $this->usuario('admin_prueba', 'admin', null);

        $this->actingAs($admin, 'web')->getJson('/api/resultados')
            ->assertOk()->assertJsonCount(4, 'data.data');
    }
}
