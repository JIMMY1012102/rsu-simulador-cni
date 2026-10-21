<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutAlumnoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_usuario_real_con_iniciales_y_cierre_de_sesion(): void
    {
        $alumno = Usuario::create([
            'nombres' => 'María José', 'apellidos' => 'Quispe Rojas', 'usuario' => 'mquispe',
            'password' => 'PruebaLayout123!', 'rol' => 'estudiante', 'activo' => true,
        ]);
        $this->actingAs($alumno, 'web');

        $html = view('layouts.alumno', ['migas' => 'Examen en curso'])->render();

        $this->assertStringContainsString('MARÍA', $html);
        $this->assertStringContainsString('>MQ<', $html);
        $this->assertStringContainsString('Alumno CNI', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
        $this->assertStringContainsString('img/examen/escudo-cni.png', $html);
        $this->assertStringContainsString('css/alumno.css', $html);
        $this->assertStringNotContainsString('figma.com', $html);
        $this->assertStringNotContainsString('css/app.css', $html);
    }
}
