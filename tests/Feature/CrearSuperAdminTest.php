<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrearSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_superadmin_con_password_hasheado_y_acceso_al_panel(): void
    {
        config(['cni.administracion_academica_enabled' => true]);

        $this->artisan('cni:crear-superadmin')
            ->expectsQuestion('Contraseña de Super_Admin (se ocultará al escribir):', 'ContraseñaUnicaDePrueba!2026')
            ->assertExitCode(0);

        $usuario = Usuario::query()->where('usuario', 'Super_Admin')->firstOrFail();

        $this->assertSame('admin', $usuario->rol);
        $this->assertTrue($usuario->activo);
        $this->assertTrue(Hash::check('ContraseñaUnicaDePrueba!2026', $usuario->password));
        $this->assertNotSame('ContraseñaUnicaDePrueba!2026', $usuario->password);

        $this->actingAs($usuario, 'web')
            ->get(route('admin.inicio'))
            ->assertOk()
            ->assertSee('Superadministrador');
    }

    public function test_segunda_ejecucion_no_duplica_ni_cambia_credenciales(): void
    {
        $usuario = Usuario::query()->create([
            'nombres' => 'Admin',
            'apellidos' => 'Original',
            'usuario' => 'Super_Admin',
            'password' => 'ClaveYaExistente!2026',
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->artisan('cni:crear-superadmin')->assertExitCode(0);

        $this->assertSame(1, Usuario::query()->where('usuario', 'Super_Admin')->count());
        $this->assertTrue(Hash::check('ClaveYaExistente!2026', $usuario->fresh()->password));
    }

    public function test_no_convierte_una_cuenta_de_estudiante_en_administrador(): void
    {
        $usuario = Usuario::query()->create([
            'nombres' => 'Alumno',
            'apellidos' => 'CNI',
            'usuario' => 'Super_Admin',
            'password' => 'ClaveEstudiante!2026',
            'rol' => 'estudiante',
            'activo' => true,
        ]);

        $this->artisan('cni:crear-superadmin')->assertExitCode(1);
        $this->assertSame('estudiante', $usuario->fresh()->rol);
    }

    public function test_no_crea_cuenta_si_contrasena_es_demasiado_corta(): void
    {
        $this->artisan('cni:crear-superadmin')
            ->expectsQuestion('Contraseña de Super_Admin (se ocultará al escribir):', '123')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('usuarios', ['usuario' => 'Super_Admin']);
    }
}
