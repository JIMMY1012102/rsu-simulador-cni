<?php

namespace Tests\Feature;

use App\Livewire\Alumno\CienciasBasicas\Inicio;
use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ExamenDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CienciasBasicasPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AreaPostulacionSeeder::class);
        $this->seed(CarreraSeeder::class);
        $this->seed(ExamenDemoSeeder::class);
    }

    private function area(string $codigo): int
    {
        return DB::table('areas_postulacion')->where('codigo', $codigo)->value('id');
    }

    private function usuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombres' => 'Diego Andrés', 'apellidos' => 'Camayo Ruiz', 'usuario' => uniqid('alumno'),
            'password' => 'ClaveCNI2026!', 'rol' => 'estudiante', 'activo' => true,
        ], $datos));
    }

    private function alumnoB(): Usuario
    {
        return $this->usuario(['area_postulacion_id' => $this->area('B')]);
    }

    /** Simulacro publicado por el administrador con el flujo existente. */
    private function publicar(string $nombre, string $area): int
    {
        $this->actingAs($this->usuario(['rol' => 'admin']), 'web');
        $materia = DB::table('areas')->where('nombre', 'Razonamiento Matemático')->value('id');
        $this->post(route('admin.simulacros.store'), [
            'nombre' => $nombre, 'area_postulacion_id' => $this->area($area), 'carrera_id' => '',
            'tiempo_limite' => 60,
            'fecha_inicio' => now()->subHour()->format('Y-m-d H:i'),
            'fecha_fin' => now()->addWeek()->format('Y-m-d H:i'),
            'distribucion' => [$materia => 2],
        ])->assertRedirect(route('admin.simulacros.index'));

        return DB::table('simulacros')->where('nombre', $nombre)->value('id');
    }

    public function test_solo_abre_para_estudiantes_del_area_b_y_respeta_el_flujo_principal(): void
    {
        $this->get('/alumno/ciencias-basicas')->assertRedirect(route('login'));
        $this->actingAs($this->usuario(['rol' => 'docente']), 'web')->get('/alumno/ciencias-basicas')->assertForbidden();

        // Sin área o de otra área: vuelve al flujo del panel principal sin cambiar el perfil.
        $sinArea = $this->usuario();
        $this->actingAs($sinArea, 'web')->get('/alumno/ciencias-basicas')->assertRedirect(route('alumno.elegir-area'));
        $ingenieria = $this->usuario(['area_postulacion_id' => $this->area('C')]);
        $this->actingAs($ingenieria, 'web')->get('/alumno/ciencias-basicas')->assertRedirect(route('alumno.panel'));
        $this->assertNull($sinArea->fresh()->area_postulacion_id);
        $this->assertSame($this->area('C'), $ingenieria->fresh()->area_postulacion_id);

        // Cuenta antigua con carrera del área B y sin área explícita.
        $matematica = DB::table('carreras')->where('nombre', 'Matemática')->value('id');
        $this->actingAs($this->usuario(['carrera_id' => $matematica]), 'web')->get('/alumno/ciencias-basicas')->assertOk();

        config(['cni.alumno_areas_enabled' => false]);
        $this->actingAs($this->alumnoB(), 'web')->get('/alumno/ciencias-basicas')->assertNotFound();
    }

    public function test_panel_muestra_el_diseno_sin_datos_inventados_y_no_reemplaza_el_principal(): void
    {
        $alumno = $this->alumnoB();
        $this->actingAs($alumno, 'web')->get('/alumno/ciencias-basicas')->assertOk()
            ->assertSee('css/alumno/ciencias-basicas.css')->assertSee('Ciencias Básicas')
            ->assertSee('Diego Camayo!')->assertSee('0 días')->assertSee('Panel principal')
            ->assertSee(route('alumno.panel'))->assertSee('No hay simulacros disponibles para tu área')
            ->assertDontSee('42%')->assertDontSee('72%')->assertDontSee('1240 preguntas');

        $this->get(route('alumno.panel'))->assertOk()->assertDontSee('css/alumno/ciencias-basicas.css');
    }

    public function test_inicia_solo_simulacros_del_area_b_con_el_examen_existente(): void
    {
        $propio = $this->publicar('Simulacro Ciencias Básicas', 'B');
        $ajeno = $this->publicar('Simulacro Ingenierías', 'C');
        $alumno = $this->alumnoB();

        $this->actingAs($alumno, 'web')->get('/alumno/ciencias-basicas')->assertOk()
            ->assertSee(route('examen.iniciar', $propio))->assertDontSee(route('examen.iniciar', $ajeno))
            ->assertSee('Comenzar ahora');

        $this->post(route('examen.iniciar', $propio))->assertRedirect();
        $intento = DB::table('intentos')->where('usuario_id', $alumno->id)->first();
        $this->assertSame($propio, (int) $intento->simulacro_id);

        // Con el intento abierto, el botón lo reanuda mediante examen.iniciar sin crear otro.
        $this->get('/alumno/ciencias-basicas')->assertSee('Continuar simulacro');
        $this->post(route('examen.iniciar', $propio))->assertRedirect(route('examen', $intento->id));
        $this->assertSame(1, DB::table('intentos')->where('usuario_id', $alumno->id)->count());
    }

    public function test_temas_de_estudio_muestran_materias_reales_y_ver_todos(): void
    {
        foreach (range(1, 9) as $i) {
            DB::table('areas')->insert(['nombre' => "Materia {$i}", 'bloque' => 'conocimientos', 'activo' => true]);
        }
        $visibles = DB::table('areas')->where('activo', true)->orderBy('nombre')->pluck('nombre');

        Livewire::actingAs($this->alumnoB(), 'web')->test(Inicio::class)
            ->assertSee($visibles[7])->assertDontSee($visibles[8])->assertSee('Ver todos')
            ->toggle('verTodas')->assertSee($visibles[8])->assertSee('Ver menos');
    }
}
