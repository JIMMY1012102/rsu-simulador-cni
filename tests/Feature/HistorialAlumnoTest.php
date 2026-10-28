<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\Alumno\HistorialAlumnoService;
use Database\Seeders\AreaPostulacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistorialAlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cni.alumno_areas_enabled' => true]);
        $this->seed(AreaPostulacionSeeder::class);
    }

    private function alumno(string $nombre): Usuario
    {
        return Usuario::create([
            'nombres' => $nombre, 'apellidos' => 'CNI',
            'usuario' => $nombre, 'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante', 'activo' => true,
            'area_postulacion_id' => DB::table('areas_postulacion')->where('codigo', 'C')->value('id'),
        ]);
    }

    private function simulacro(int $puntajePorAcierto): int
    {
        return DB::table('simulacros')->insertGetId([
            'nombre' => 'Simulacro de prueba',
            'tiempo_limite' => 45, 'total_preguntas' => 10,
            'cantidad_alternativas' => 4,
            'puntaje_correcta' => $puntajePorAcierto,
            'puntaje_incorrecta' => -1, 'puntaje_blanco' => 0,
            'fecha_inicio' => now()->subWeek(), 'fecha_fin' => now()->addWeek(),
            'activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function intento(Usuario $usuario, int $simulacroId, ?float $puntaje, int $dias, string $tipo = 'simulacro'): int
    {
        return DB::table('intentos')->insertGetId([
            'usuario_id' => $usuario->id,
            'simulacro_id' => $simulacroId,
            'tipo' => $tipo,
            'estado' => $puntaje === null ? 'en_curso' : 'finalizado',
            'fecha_inicio' => now()->subDays($dias),
            'fecha_fin' => $puntaje === null ? null : now()->subDays($dias)->addHour(),
            'total_preguntas' => 10,
            'puntaje_total' => $puntaje,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_indicadores_y_tabla_usan_notas_reales_sin_mostrar_otros_alumnos(): void
    {
        $alumno = $this->alumno('HistorialPropio');
        $otro = $this->alumno('OtroHistorial');
        $simulacro = $this->simulacro(4); // Máximo 40.
        $primero = $this->intento($alumno, $simulacro, 10, 4);
        $segundo = $this->intento($alumno, $simulacro, 30, 3);
        $pendiente = $this->intento($alumno, $simulacro, null, 2);
        $ajeno = $this->intento($otro, $simulacro, 40, 1);

        $h = app(HistorialAlumnoService::class)->obtener($alumno);
        $this->assertSame(3, $h['total']);
        $this->assertSame(2, $h['calificados']);
        $this->assertSame(30.0, $h['mejor']['puntaje']);
        $this->assertSame(30.0, $h['ultimo']['puntaje']);
        $this->assertSame(20.0, $h['promedio']['puntaje']);
        $this->assertSame(40.0, $h['promedio']['maximo']);
        $this->assertSame([$pendiente, $segundo, $primero], array_column($h['registros'], 'id'));
        $this->assertSame(20.0, $h['registros'][1]['evolucion']);
        $this->assertSame('pts', $h['registros'][1]['unidad_evolucion']);
        $this->assertSame('en-curso', $h['registros'][0]['nivel']);
        $this->assertNotContains($ajeno, array_column($h['registros'], 'id'));

        $html = $this->actingAs($alumno, 'web')->get(route('alumno.panel'))
            ->assertOk()
            ->assertSee('Historial de simulacros')
            ->assertSee('Buscar intento')
            ->assertSee('Todos los niveles')
            ->assertSee('Ordenar: más reciente')
            ->assertSee('30 / 40')
            ->assertSee('20 / 40')
            ->assertSee('data-historial-fila', false)
            ->getContent();

        $this->assertStringContainsString(route('alumno.resultados', ['intento' => $segundo]), $html);
        $this->assertStringNotContainsString(route('alumno.resultados', ['intento' => $ajeno]), $html);
    }

    public function test_maximos_diferentes_usan_porcentajes_y_no_inventan_niveles(): void
    {
        $alumno = $this->alumno('EscalasHistorial');
        $s1 = $this->simulacro(2); // 20 puntos posibles.
        $s2 = $this->simulacro(4); // 40 puntos posibles.
        $this->intento($alumno, $s1, 10, 3); // 50%.
        $this->intento($alumno, $s2, 30, 2); // 75%.

        $h = app(HistorialAlumnoService::class)->obtener($alumno);
        $this->assertSame(62.5, $h['promedio']['porcentaje']);
        $this->assertNull($h['promedio']['maximo']);
        $this->assertSame(25.0, $h['registros'][0]['evolucion']);
        $this->assertSame('pp', $h['registros'][0]['unidad_evolucion']);

        $this->actingAs($alumno, 'web')->get(route('alumno.panel'))
            ->assertOk()->assertSee('62,5%')->assertSee('Sin nivel')
            ->assertDontSee('Nivel Básico');
    }

    public function test_historial_admite_esquema_legacy_sin_carrera_id_en_simulacros(): void
    {
        $alumno = $this->alumno('HistorialLegacy');
        $simulacro = $this->simulacro(3);
        $intento = $this->intento($alumno, $simulacro, 12, 2);

        // Simula una instalación local antigua sin alterar ni borrar su esquema.
        Schema::shouldReceive('hasColumn')->once()
            ->with('simulacros', 'carrera_id')->andReturn(false);

        $historial = app(HistorialAlumnoService::class)->obtener($alumno);
        $this->assertSame(1, $historial['total']);
        $this->assertSame($intento, $historial['registros'][0]['id']);
        $this->assertSame('Simulacro general', $historial['registros'][0]['tipo']);
        $this->assertSame(12.0, $historial['registros'][0]['puntaje']);
    }

    public function test_sin_intentos_no_inventa_notas_y_docente_no_puede_entrar(): void
    {
        $alumno = $this->alumno('HistorialVacio');
        $h = app(HistorialAlumnoService::class)->obtener($alumno);
        $this->assertSame(0, $h['total']);
        $this->assertNull($h['ultimo']);
        $this->assertNull($h['promedio']);

        $this->actingAs($alumno, 'web')->get(route('alumno.panel'))
            ->assertOk()->assertSee('Aún no tienes simulacros registrados.');

        $docente = Usuario::create([
            'nombres' => 'Docente', 'apellidos' => 'CNI',
            'usuario' => 'docente_historial', 'password' => 'ClaveCNI2026!',
            'rol' => 'docente', 'activo' => true,
        ]);
        $this->actingAs($docente, 'web')->get(route('alumno.panel'))->assertForbidden();
    }
}
