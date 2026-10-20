<?php

namespace Tests\Feature;

use App\Models\AreaPostulacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SimulacrosModuleTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $docente;

    private Usuario $estudiante;

    private array $areas;

    private array $areasPostulacion;

    protected function setUp(): void
    {
        parent::setUp();

        // Mientras routes/api.php no cargue routes/modules/*.php, se registra el módulo aquí.
        if (! Route::has('simulacros.index')) {
            Route::prefix('api')->middleware('api')->group(base_path('routes/modules/simulacros.php'));
            Route::getRoutes()->refreshNameLookups();
        }

        $this->docente = $this->crearUsuario('docente');
        $this->estudiante = $this->crearUsuario('estudiante');

        $this->areas = [
            DB::table('areas')->insertGetId(['nombre' => 'Razonamiento Verbal', 'bloque' => 'habilidad_verbal', 'activo' => true]),
            DB::table('areas')->insertGetId(['nombre' => 'Álgebra', 'bloque' => 'conocimientos', 'activo' => true]),
        ];

        $this->areasPostulacion = [
            AreaPostulacion::create(['codigo' => 'A', 'nombre' => 'Ingenierías'])->id,
            AreaPostulacion::create(['codigo' => 'B', 'nombre' => 'Ciencias de la Salud'])->id,
        ];
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombres' => 'Prueba',
            'apellidos' => ucfirst($rol),
            'usuario' => $rol.'_'.uniqid(),
            'password' => 'secreto123',
            'rol' => $rol,
            'activo' => true,
        ]);
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Simulacro CNI 1',
            'tiempo_limite' => 120,
            'total_preguntas' => 10,
            'fecha_inicio' => '2026-10-20 08:00:00',
            'fecha_fin' => '2026-10-20 12:00:00',
            'areas' => [
                ['area_id' => $this->areas[0], 'nivel' => 'basico', 'cantidad_preguntas' => 6],
                ['area_id' => $this->areas[1], 'nivel' => 'mixto', 'cantidad_preguntas' => 4],
            ],
            'areas_postulacion' => $this->areasPostulacion,
        ], $cambios);
    }

    public function test_docente_crea_simulacro_con_sus_areas(): void
    {
        $respuesta = $this->actingAs($this->docente, 'web')
            ->postJson('/api/simulacros', $this->datosValidos());

        $respuesta->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Simulacro CNI 1')
            ->assertJsonPath('data.puntaje_maximo', 200)
            ->assertJsonCount(2, 'data.areas')
            ->assertJsonCount(2, 'data.areas_postulacion');

        $this->assertDatabaseHas('simulacros', ['nombre' => 'Simulacro CNI 1', 'creado_por' => $this->docente->id]);
        $this->assertDatabaseCount('simulacro_area', 2);
        $this->assertDatabaseCount('area_postulacion_simulacro', 2);
    }

    public function test_datos_incompletos_devuelven_error_de_validacion(): void
    {
        $this->actingAs($this->docente, 'web')
            ->postJson('/api/simulacros', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonValidationErrors(['nombre', 'tiempo_limite', 'total_preguntas', 'fecha_inicio', 'fecha_fin', 'areas']);
    }

    public function test_la_suma_de_preguntas_por_area_debe_igualar_el_total(): void
    {
        $this->actingAs($this->docente, 'web')
            ->postJson('/api/simulacros', $this->datosValidos(['total_preguntas' => 100]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['areas']);

        $this->assertDatabaseCount('simulacros', 0);
    }

    public function test_fecha_fin_debe_ser_posterior_a_fecha_inicio(): void
    {
        $this->actingAs($this->docente, 'web')
            ->postJson('/api/simulacros', $this->datosValidos(['fecha_fin' => '2026-10-19 08:00:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_fin']);
    }

    public function test_estudiante_no_puede_crear_ni_editar(): void
    {
        $this->actingAs($this->estudiante, 'web')
            ->postJson('/api/simulacros', $this->datosValidos())
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $id = $this->crearSimulacro();

        $this->actingAs($this->estudiante, 'web')
            ->putJson("/api/simulacros/{$id}", $this->datosValidos(['nombre' => 'Cambiado']))
            ->assertForbidden();

        $this->assertDatabaseMissing('simulacros', ['nombre' => 'Cambiado']);
    }

    public function test_sin_sesion_no_hay_acceso(): void
    {
        $this->getJson('/api/simulacros')->assertUnauthorized();
    }

    public function test_simulacro_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->docente, 'web')
            ->getJson('/api/simulacros/9999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);

        $this->actingAs($this->docente, 'web')
            ->putJson('/api/simulacros/9999', $this->datosValidos())
            ->assertNotFound();
    }

    public function test_actualizar_reemplaza_areas_sin_duplicar(): void
    {
        $id = $this->crearSimulacro();

        $datos = $this->datosValidos([
            'nombre' => 'Simulacro CNI 1 (corregido)',
            'areas' => [['area_id' => $this->areas[1], 'nivel' => 'avanzado', 'cantidad_preguntas' => 10]],
            'areas_postulacion' => [$this->areasPostulacion[0]],
        ]);

        // Dos PUT iguales deben dejar el mismo resultado.
        $this->actingAs($this->docente, 'web')->putJson("/api/simulacros/{$id}", $datos)->assertOk();
        $this->actingAs($this->docente, 'web')->putJson("/api/simulacros/{$id}", $datos)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Simulacro CNI 1 (corregido)')
            ->assertJsonPath('data.areas.0.nivel', 'avanzado');

        $this->assertDatabaseCount('simulacros', 1);
        $this->assertDatabaseCount('simulacro_area', 1);
        $this->assertDatabaseCount('area_postulacion_simulacro', 1);
    }

    public function test_estudiante_solo_ve_simulacros_activos(): void
    {
        $activo = $this->crearSimulacro();
        $inactivo = $this->crearSimulacro(['nombre' => 'Borrador', 'activo' => false]);

        $this->actingAs($this->estudiante, 'web')
            ->getJson('/api/simulacros')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activo);

        $this->actingAs($this->estudiante, 'web')
            ->getJson("/api/simulacros/{$inactivo}")
            ->assertNotFound();

        $this->actingAs($this->docente, 'web')
            ->getJson('/api/simulacros')
            ->assertJsonCount(2, 'data');
    }

    private function crearSimulacro(array $cambios = []): int
    {
        return $this->actingAs($this->docente, 'web')
            ->postJson('/api/simulacros', $this->datosValidos($cambios))
            ->assertCreated()
            ->json('data.id');
    }
}
