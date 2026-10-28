<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaginasAlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cni.alumno_areas_enabled', true);
        $this->seed(AreaPostulacionSeeder::class);
    }

    private function usuario(string $nombre, string $rol = 'estudiante'): Usuario
    {
        return Usuario::create([
            'nombres' => $nombre,
            'apellidos' => 'CNI',
            'usuario' => $nombre,
            'password' => 'ClaveCNI2026!',
            'rol' => $rol,
            'activo' => true,
            'area_postulacion_id' => DB::table('areas_postulacion')->where('codigo', 'A')->value('id'),
        ]);
    }

    public function test_solo_el_estudiante_puede_ver_sus_tres_paginas(): void
    {
        foreach (['alumno.perfil', 'alumno.progreso', 'alumno.historial'] as $ruta) {
            $this->get(route($ruta))->assertRedirect(route('login'));
        }
        $this->actingAs($this->usuario('profesor', 'docente'), 'web');
        foreach (['alumno.perfil', 'alumno.progreso', 'alumno.historial'] as $ruta) {
            $this->get(route($ruta))->assertForbidden();
        }
        $alumno = $this->usuario('estudianteA');
        $this->actingAs($alumno, 'web');
        $this->get(route('alumno.perfil'))->assertOk()->assertSee('estudianteA');
        $this->get(route('alumno.progreso'))->assertOk()->assertSee('Evolución de tu puntaje');
        $this->get(route('alumno.historial'))->assertOk()->assertSee('Historial de simulacros');
    }

    public function test_historial_y_progreso_no_muestran_calificaciones_ajenas(): void
    {
        $propio = $this->usuario('propio');
        $ajeno = $this->usuario('ajeno');
        DB::table('intentos')->insert([
            ['usuario_id' => $propio->id, 'tipo' => 'practica', 'fecha_inicio' => now()->subDay(),
                'estado' => 'finalizado', 'total_preguntas' => 10, 'puntaje_total' => 5,
                'created_at' => now(), 'updated_at' => now()],
            ['usuario_id' => $ajeno->id, 'tipo' => 'practica', 'fecha_inicio' => now(),
                'estado' => 'finalizado', 'total_preguntas' => 10, 'puntaje_total' => 99,
                'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->actingAs($propio, 'web');
        $this->get(route('alumno.progreso'))->assertOk()->assertSee('5.00')->assertDontSee('99.00');
        $this->get(route('alumno.historial'))->assertOk()->assertSee('5.00 pts')->assertDontSee('99.00 pts');
        $this->get(route('alumno.historial', ['estado' => 'en_curso']))->assertOk()
            ->assertSee('No se encontraron intentos');
    }
}
