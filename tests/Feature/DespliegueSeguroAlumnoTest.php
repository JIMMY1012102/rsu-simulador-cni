<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DespliegueSeguroAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private function alumno(): Usuario
    {
        return Usuario::create([
            'nombres' => 'Alumno', 'apellidos' => 'Bandera',
            'usuario' => 'alumno_flag', 'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante', 'activo' => true,
        ]);
    }

    public function test_nuevo_flujo_esta_activo_en_el_entorno_de_pruebas(): void
    {
        $this->assertTrue(config('cni.alumno_areas_enabled'));
        $this->alumno();
        $this->post('/login', ['usuario' => 'alumno_flag', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('alumno.elegir-area'));
    }

    public function test_desactivado_conserva_panel_anterior_y_no_expone_las_nuevas_pantallas(): void
    {
        config(['cni.alumno_areas_enabled' => false]);
        $this->alumno();
        $this->post('/login', ['usuario' => 'alumno_flag', 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('panel'));
        $this->get(route('panel'))->assertOk();
        $this->get(route('alumno.elegir-area'))->assertNotFound();
        $this->get(route('alumno.panel'))->assertNotFound();
        $this->get(route('examen', ['intento' => 1]))->assertNotFound();
        $this->post(route('examen.iniciar', ['simulacro' => 1]))->assertNotFound();
    }
}
